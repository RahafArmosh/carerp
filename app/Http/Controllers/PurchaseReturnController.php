<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\GeneralLedger;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\SubProduct;
use App\Models\Tax;
use App\Models\Vender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('manage bill') && !\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $purchaseReturns = PurchaseReturn::with(['bill', 'vender', 'items'])
            ->where('created_by', \Auth::user()->creatorId())
            ->orderByDesc('id')
            ->get();

        return view('purchase_return.index', compact('purchaseReturns'));
    }

    public function create()
    {
        if (!\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $bills = Bill::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'Bill')
            ->whereIn('status', [4, 6])
            ->orderByDesc('id')
            ->get();

        return view('purchase_return.create', compact('bills'));
    }

    public function createImport()
    {
        if (!\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $bills = Bill::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'Bill')
            ->whereIn('status', [4, 6])
            ->orderByDesc('id')
            ->get();

        return view('purchase_return.create_import', compact('bills'));
    }

    public function downloadImportSample()
    {
        if (!\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $csv = "sub_product_id,sub_product_no,quantity\n101,SP-000101,1\n102,SP-000102,2\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="purchase_return_import_sample.csv"',
        ]);
    }

    public function show($id)
    {
        if (!\Auth::user()->can('manage bill') && !\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $purchaseReturn = PurchaseReturn::with([
            'bill',
            'vender',
            'items.product',
            'items.subProduct',
            'items.billProduct',
        ])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        return view('purchase_return.view', compact('purchaseReturn'));
    }

    /**
     * Show accounting ledger for a purchase return.
     */
    public function purchase_return_ledger($purchaseReturnId)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = \App\Models\GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $purchaseReturnId)
                    ->where('reference', 'Purchase Return')
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();

                $balance = 0;
                $debit = 0;
                $credit = 0;
                $filter['balance'] = $balance;
                $filter['credit'] = $credit;
                $filter['debit'] = $debit;
                $filter['startDateRange'] = $start;
                $filter['endDateRange'] = $end;

                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }

    public function billItems($billId)
    {
        if (!\Auth::user()->can('create purchase')) {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $bill = Bill::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'Bill')
            ->findOrFail($billId);

        return response()->json($this->getBillReturnableItems($bill));
    }

    public function importBillItems(Request $request)
    {
        if (!\Auth::user()->can('create purchase')) {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $request->validate([
            'bill_id' => 'required|integer|exists:bills,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $bill = Bill::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'Bill')
            ->findOrFail((int) $request->bill_id);

        $availableItems = collect($this->getBillReturnableItems($bill));
        $bySubProductId = $availableItems->keyBy(function ($item) {
            return (string) ($item['sub_product_id'] ?? '');
        });
        $bySubProductNo = $availableItems->keyBy(function ($item) {
            return strtoupper(trim((string) ($item['sub_product_no'] ?? '')));
        });

        $sheets = Excel::toArray(new \stdClass(), $request->file('file'));
        $rows = $sheets[0] ?? [];
        if (empty($rows) || count($rows) < 2) {
            return response()->json(['message' => __('Excel file is empty or missing data rows.')], 422);
        }

        $headers = array_map(function ($header) {
            return strtolower(trim((string) $header));
        }, $rows[0]);
        $headerIndex = array_flip($headers);

        $subProductIdKey = null;
        foreach (['sub_product_id', 'sub product id'] as $candidate) {
            if (array_key_exists($candidate, $headerIndex)) {
                $subProductIdKey = $candidate;
                break;
            }
        }

        $subProductNoKey = null;
        foreach (['sub_product_no', 'sub product no', 'sub_product_number', 'sub product number'] as $candidate) {
            if (array_key_exists($candidate, $headerIndex)) {
                $subProductNoKey = $candidate;
                break;
            }
        }

        $qtyKey = null;
        foreach (['quantity', 'qty', 'return_qty', 'return quantity'] as $candidate) {
            if (array_key_exists($candidate, $headerIndex)) {
                $qtyKey = $candidate;
                break;
            }
        }

        if (($subProductIdKey === null && $subProductNoKey === null) || $qtyKey === null) {
            return response()->json([
                'message' => __('Excel must contain quantity plus sub_product_id or sub_product_no columns.')
            ], 422);
        }

        $importItems = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $subProductId = $subProductIdKey !== null ? trim((string) ($row[$headerIndex[$subProductIdKey]] ?? '')) : '';
            $subProductNo = $subProductNoKey !== null ? strtoupper(trim((string) ($row[$headerIndex[$subProductNoKey]] ?? ''))) : '';
            $qty = (float) ($row[$headerIndex[$qtyKey]] ?? 0);

            if ((empty($subProductId) && empty($subProductNo)) || $qty <= 0) {
                continue;
            }

            $baseItem = null;
            if (!empty($subProductId) && $bySubProductId->has((string) $subProductId)) {
                $baseItem = $bySubProductId->get((string) $subProductId);
            }
            if (!$baseItem && !empty($subProductNo) && $bySubProductNo->has($subProductNo)) {
                $baseItem = $bySubProductNo->get($subProductNo);
            }
            if (!$baseItem) {
                continue;
            }
            $baseItem['return_qty'] = min($qty, (float) $baseItem['available_qty']);
            $importItems[] = $baseItem;
        }

        if (empty($importItems)) {
            return response()->json(['message' => __('No valid returnable rows found in Excel file.')], 422);
        }

        return response()->json(array_values($importItems));
    }

    private function getBillReturnableItems(Bill $bill): array
    {
        $subProductQtyByProduct = SubProduct::where('bill_id', $bill->id)
            ->select('product_id', DB::raw('SUM(quantity) as remaining_qty'))
            ->groupBy('product_id')
            ->pluck('remaining_qty', 'product_id');

        return BillProduct::with(['product', 'subProduct'])
            ->where('bill_id', $bill->id)
            ->get()
            ->map(function ($item) use ($subProductQtyByProduct) {
                $category = null;
                if (!empty(optional($item->product)->category_id)) {
                    $category = ProductServiceCategory::find($item->product->category_id);
                }
                $categoryType = strtolower((string) optional($category)->type);
                $isQtyProduct = str_contains($categoryType, 'qty');

                $availableQty = 0;
                if ($item->subProduct && $isQtyProduct) {
                    $availableQty = max(0, (float) $item->subProduct->quantity);
                } elseif ($item->subProduct && !$isQtyProduct) {
                    // Item-wise product: return eligibility is by unsold status, not numeric qty.
                    $availableQty = (empty($item->subProduct->invoice_id) && empty($item->subProduct->pos_id)) ? 1 : 0;
                } elseif ($item->product_id && $isQtyProduct) {
                    $availableQty = max(0, (float) ($subProductQtyByProduct[$item->product_id] ?? 0));
                } elseif (!$isQtyProduct && !empty($item->quantity)) {
                    // Legacy item-wise rows without sub-product linkage: keep item count available.
                    $availableQty = max(0, (float) $item->quantity);
                }

                // Legacy fallback for qty products when sub-product linkage is missing.
                if ($isQtyProduct && $availableQty <= 0 && !empty($item->quantity)) {
                    $availableQty = max(0, (float) $item->quantity);
                }

                $discount = (float) ($item->discount ?? 0);
                $price = (float) ($item->price ?? 0);
                $taxRate = 0;
                $taxLabel = '-';

                if (!empty($item->tax)) {
                    $taxIds = array_filter(explode(',', (string) $item->tax));
                    if (!empty($taxIds)) {
                        $taxes = Tax::whereIn('id', $taxIds)->get(['name', 'rate']);
                        $taxRate = (float) $taxes->sum('rate');
                        if ($taxes->count() > 0) {
                            $taxLabel = $taxes->map(function ($tax) {
                                return $tax->name . ' (' . number_format((float) $tax->rate, 2) . '%)';
                            })->implode(', ');
                        }
                    }
                }

                $lineBase = max($price - $discount, 0);
                $lineTax = ($lineBase * $taxRate) / 100;
                $lineTotal = $lineBase + $lineTax;

                return [
                    'bill_product_id' => $item->id,
                    'sub_product_id' => $item->sub_product_id,
                    'sub_product_no' => optional($item->subProduct)->product_no ?? '',
                    'sku' => optional($item->product)->sku ?? '',
                    'product_name' => trim(
                        (optional($item->product)->name ?? __('Unknown Item')) .
                        (!empty(optional($item->subProduct)->product_no) ? ' - ' . optional($item->subProduct)->product_no : '')
                    ),
                    'available_qty' => $availableQty,
                    'price' => $price,
                    'discount' => $discount,
                    'tax_rate' => $taxRate,
                    'tax_label' => $taxLabel,
                    'line_total' => round($lineTotal, 2),
                ];
            })
            ->filter(function ($item) {
                return $item['available_qty'] > 0;
            })
            ->values()
            ->toArray();
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create purchase')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'bill_id' => 'required|integer|exists:bills,id',
                'return_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.bill_product_id' => 'required|integer|exists:bill_products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $bill = Bill::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'Bill')
            ->find($request->bill_id);

        if (!$bill) {
            return redirect()->back()->with('error', __('Invalid bill selected.'))->withInput();
        }

        $returnDate = Carbon::parse($request->return_date)->startOfDay();
        $billSendDate = !empty($bill->send_date) ? Carbon::parse($bill->send_date)->startOfDay() : null;
        if ($billSendDate && $returnDate->lt($billSendDate)) {
            return redirect()->back()
                ->with('error', __('Return date cannot be earlier than the bill send date.'))
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $creatorId = \Auth::user()->creatorId();
            $returnDate = $returnDate->toDateString();
            $sendDate = $returnDate;
            $newVoucherId = null;
            if ($bill->status != 0) {
                $latestVoucher = GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
                $newVoucherId = $latestVoucher ? ((int) $latestVoucher->vid + 1) : 1;
            }

            $purchaseReturn = PurchaseReturn::create([
                'bill_id' => $bill->id,
                'vender_id' => $bill->vender_id,
                'return_date' => $returnDate,
                'notes' => $request->notes,
                'created_by' => $creatorId,
            ]);

            foreach ($request->items as $inputItem) {
                $billProduct = BillProduct::with(['subProduct', 'product'])
                    ->where('bill_id', $bill->id)
                    ->findOrFail($inputItem['bill_product_id']);

                // Always return against sub-product stock, not bill_products.quantity.
                $subProduct = $billProduct->subProduct;
                if (!$subProduct && !empty($billProduct->sub_product_id)) {
                    // In case sub-product is soft-deleted, still allow stock adjustment.
                    $subProduct = SubProduct::withTrashed()->find($billProduct->sub_product_id);
                }

                if (!$subProduct) {
                    throw new \Exception(__('Cannot return this item because it is not linked to a sub-product.'));
                }

                $category = null;
                if (!empty(optional($billProduct->product)->category_id)) {
                    $category = ProductServiceCategory::find($billProduct->product->category_id);
                }
                $categoryType = strtolower((string) optional($category)->type);
                $isQtyProduct = str_contains($categoryType, 'qty');

                $returnQty = (float) $inputItem['quantity'];
                $availableQty = 0;
                if ($isQtyProduct) {
                    $availableQty = max(0, (float) $subProduct->quantity);
                } else {
                    // Item-wise product can be returned only if still unsold.
                    $availableQty = (empty($subProduct->invoice_id) && empty($subProduct->pos_id)) ? 1 : 0;
                    if ($returnQty > 0) {
                        $returnQty = 1;
                    }
                }

                if ($returnQty <= 0) {
                    continue;
                }

                if ($returnQty > $availableQty) {
                    throw new \Exception(__('Return quantity exceeds available unsold quantity for :item', [
                        'item' => optional($billProduct->product)->name ?? ('#' . $billProduct->id),
                    ]));
                }

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'bill_product_id' => $billProduct->id,
                    'sub_product_id' => $billProduct->sub_product_id,
                    'product_id' => $billProduct->product_id,
                    'quantity' => $returnQty,
                    'unit_price' => $billProduct->price ?? 0,
                    'created_by' => $creatorId,
                ]);

                $newQty = $isQtyProduct ? ($availableQty - $returnQty) : 0;
                $subProduct->quantity = $newQty;
                if ($newQty <= 0) {
                    // Mark fully returned stock as cancelled.
                    $subProduct->flag = SubProduct::FLAG_CANCELLED;
                }
                $subProduct->save();

                ProductService::where('id', $billProduct->product_id)->decrement('quantity', $returnQty);

                // Record stock movement for purchase return (stock out).
                $stockMovement = new StockMovement();
                $stockMovement->product_id = $billProduct->product_id;
                $stockMovement->sub_product_id = $billProduct->sub_product_id;
                $stockMovement->invoice_id = null;
                $stockMovement->bill_id = $bill->id;
                $stockMovement->qty_out = $returnQty;
                $stockMovement->qty_in = 0;
                $stockMovement->avg_cost = (float) ($billProduct->product->avg_cost ?? $billProduct->price ?? 0);
                $stockMovement->cost_price = (float) ($billProduct->price ?? 0);
                $stockMovement->activity = 'Purchase Return';
                $stockMovement->use_id = $bill->vender_id;
                $stockMovement->item = $billProduct->sub_product_id;
                $stockMovement->created_by = $creatorId;
                $stockMovement->save();

                // Reverse Bill ledger for each returned item including tax.
                if ($bill->status != 0 && $newVoucherId !== null) {
                    $vender = Vender::find($bill->vender_id);
                    $product = $billProduct->product;

                    $unitPrice = (float) ($billProduct->price ?? 0);
                    $unitDiscount = (float) ($billProduct->discount ?? 0);
                    $lineBaseAmount = max(($unitPrice - $unitDiscount) * $returnQty, 0);

                    $taxIds = array_filter(explode(',', (string) ($billProduct->tax ?: $bill->tax_id)));
                    $totalTaxPrice = 0.0;
                    $taxLines = [];
                    if (!empty($taxIds)) {
                        $taxes = Tax::whereIn('id', $taxIds)->get();
                        foreach ($taxes as $taxModel) {
                            $taxAmount = ((float) $taxModel->rate / 100) * $lineBaseAmount;
                            $totalTaxPrice += $taxAmount;
                            if (!empty($taxModel->chart_account_id) && $taxAmount > 0) {
                                $taxLines[] = [
                                    'account_id' => (int) $taxModel->chart_account_id,
                                    'amount' => $taxAmount,
                                ];
                            }
                        }
                    }

                    if ($vender && $lineBaseAmount + $totalTaxPrice > 0) {
                        $vendorEntry = new GeneralLedger();
                        $vendorEntry->vid = $newVoucherId;
                        $vendorEntry->account = $vender->chart_account_id;
                        $vendorEntry->type = 'Purchase Return For ' . \Auth::user()->billNumberFormat($bill->bill_id);
                        $vendorEntry->debit = $lineBaseAmount + $totalTaxPrice;
                        $vendorEntry->credit = 0;
                        $vendorEntry->ref_id = $purchaseReturn->id;
                        $vendorEntry->user_id = $vender->id;
                        $vendorEntry->sub_product_id = $billProduct->sub_product_id;
                        $vendorEntry->created_by = $creatorId;
                        $vendorEntry->balance = $vender->balance;
                        $vendorEntry->send_date = $sendDate;
                        $vendorEntry->deleted_qty = $returnQty;
                        $vendorEntry->reference = 'Purchase Return';
                        $vendorEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $vendorEntry->save();
                    }

                    $purchaseAccountId = null;
                    if ($product) {
                        $category = ProductServiceCategory::find($product->category_id);
                        $purchaseAccountId = $category ? $category->purchase_account_id : null;
                    }
                    if (!empty($purchaseAccountId) && $lineBaseAmount > 0) {
                        $purchaseEntry = new GeneralLedger();
                        $purchaseEntry->vid = $newVoucherId;
                        $purchaseEntry->account = $purchaseAccountId;
                        $purchaseEntry->type = 'Purchase Return For ' . \Auth::user()->billNumberFormat($bill->bill_id);
                        $purchaseEntry->debit = 0;
                        $purchaseEntry->credit = $lineBaseAmount;
                        $purchaseEntry->ref_id = $purchaseReturn->id;
                        $purchaseEntry->user_id = 0;
                        $purchaseEntry->sub_product_id = $billProduct->sub_product_id;
                        $purchaseEntry->created_by = $creatorId;
                        $purchaseEntry->send_date = $sendDate;
                        $purchaseEntry->deleted_qty = $returnQty;
                        $purchaseEntry->reference = 'Purchase Return';
                        $purchaseEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $purchaseEntry->save();
                    }

                    foreach ($taxLines as $taxLine) {
                        $taxEntry = new GeneralLedger();
                        $taxEntry->vid = $newVoucherId;
                        $taxEntry->account = $taxLine['account_id'];
                        $taxEntry->type = 'Purchase Return For ' . \Auth::user()->billNumberFormat($bill->bill_id);
                        $taxEntry->debit = 0;
                        $taxEntry->credit = $taxLine['amount'];
                        $taxEntry->ref_id = $purchaseReturn->id;
                        $taxEntry->user_id = 0;
                        $taxEntry->sub_product_id = $billProduct->sub_product_id;
                        $taxEntry->created_by = $creatorId;
                        $taxEntry->send_date = $sendDate;
                        $taxEntry->deleted_qty = $returnQty;
                        $taxEntry->reference = 'Purchase Return';
                        $taxEntry->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $taxEntry->save();
                    }
                }
            }

            DB::commit();

            return redirect()->route('purchase.return.index')->with('success', __('Purchase return created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
