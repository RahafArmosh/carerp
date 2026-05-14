<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\ProductService;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\SubProduct;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SalesReturnController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('manage invoice') && !\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $salesReturns = SalesReturn::with(['invoice', 'customer', 'items'])
            ->where('created_by', \Auth::user()->creatorId())
            ->orderByDesc('id')
            ->get();

        return view('sales_return.index', compact('salesReturns'));
    }

    public function create()
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $invoices = Invoice::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'regular')
            ->whereIn('status', [4, 6])
            ->orderByDesc('id')
            ->get();

        return view('sales_return.create', compact('invoices'));
    }

    public function createImport()
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $invoices = Invoice::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'regular')
            ->whereIn('status', [4, 6])
            ->orderByDesc('id')
            ->get();

        return view('sales_return.create_import', compact('invoices'));
    }

    public function downloadImportSample()
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $csv = "sub_product_id,sub_product_no,quantity\n101,SP-000101,1\n102,SP-000102,2\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_return_import_sample.csv"',
        ]);
    }

    public function show($id)
    {
        if (!\Auth::user()->can('manage invoice') && !\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $salesReturn = SalesReturn::with(['invoice', 'customer', 'items.product', 'items.subProduct', 'items.invoiceProduct'])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        return view('sales_return.view', compact('salesReturn'));
    }

    public function sales_return_ledger($salesReturnId)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $salesReturnId)
                    ->where('reference', 'Sales Return')
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();

                $filter = [
                    'balance' => 0,
                    'credit' => 0,
                    'debit' => 0,
                    'startDateRange' => $start,
                    'endDateRange' => $end,
                ];

                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            }

            return redirect()->back()->with('error', __('Permission Denied.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }

    public function invoiceItems($invoiceId)
    {
        if (!\Auth::user()->can('create invoice')) {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $invoice = Invoice::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'regular')
            ->findOrFail($invoiceId);

        return response()->json($this->getInvoiceReturnableItems($invoice));
    }

    public function importInvoiceItems(Request $request)
    {
        if (!\Auth::user()->can('create invoice')) {
            return response()->json(['message' => __('Permission denied.')], 403);
        }

        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $invoice = Invoice::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'regular')
            ->findOrFail((int) $request->invoice_id);

        $availableItems = collect($this->getInvoiceReturnableItems($invoice));
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

    private function getInvoiceReturnableItems(Invoice $invoice): array
    {
        $returnedByInvoiceProduct = SalesReturnItem::select('invoice_product_id', DB::raw('SUM(quantity) as returned_qty'))
            ->whereHas('salesReturn', function ($q) use ($invoice) {
                $q->where('invoice_id', $invoice->id)->where('created_by', \Auth::user()->creatorId());
            })
            ->groupBy('invoice_product_id')
            ->pluck('returned_qty', 'invoice_product_id');

        return InvoiceProduct::with(['product', 'subProduct'])
            ->where('invoice_id', $invoice->id)
            ->get()
            ->map(function ($item) use ($returnedByInvoiceProduct) {
                $invoicedQty = (float) ($item->quantity ?? 0);
                $alreadyReturned = (float) ($returnedByInvoiceProduct[$item->id] ?? 0);
                $availableQty = max(0, $invoicedQty - $alreadyReturned);

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
                    'invoice_product_id' => $item->id,
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
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'invoice_id' => 'required|integer|exists:invoices,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.invoice_product_id' => 'required|integer|exists:invoice_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $invoice = Invoice::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'regular')
            ->find($request->invoice_id);

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invalid invoice selected.'))->withInput();
        }

        $returnDate = Carbon::parse($request->return_date)->startOfDay();
        $invoiceSendDate = !empty($invoice->send_date) ? Carbon::parse($invoice->send_date)->startOfDay() : null;
        if ($invoiceSendDate && $returnDate->lt($invoiceSendDate)) {
            return redirect()->back()
                ->with('error', __('Return date cannot be earlier than the invoice send date.'))
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $creatorId = \Auth::user()->creatorId();
            $returnDate = $returnDate->toDateString();
            $sendDate = $returnDate;
            $newVoucherId = null;
            if ($invoice->status != 0) {
                $latestVoucher = GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
                $newVoucherId = $latestVoucher ? ((int) $latestVoucher->vid + 1) : 1;
            }

            $salesReturn = SalesReturn::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'return_date' => $returnDate,
                'notes' => $request->notes,
                'created_by' => $creatorId,
            ]);

            foreach ($request->items as $inputItem) {
                $invoiceProduct = InvoiceProduct::with(['product', 'subProduct'])
                    ->where('invoice_id', $invoice->id)
                    ->findOrFail($inputItem['invoice_product_id']);

                $alreadyReturned = (float) SalesReturnItem::where('invoice_product_id', $invoiceProduct->id)
                    ->whereHas('salesReturn', function ($q) use ($invoice, $creatorId) {
                        $q->where('invoice_id', $invoice->id)->where('created_by', $creatorId);
                    })
                    ->sum('quantity');
                $availableQty = max(0, (float) $invoiceProduct->quantity - $alreadyReturned);
                $returnQty = (float) $inputItem['quantity'];

                if ($returnQty <= 0) {
                    continue;
                }

                if ($returnQty > $availableQty) {
                    throw new \Exception(__('Return quantity exceeds available quantity for :item', [
                        'item' => optional($invoiceProduct->product)->name ?? ('#' . $invoiceProduct->id),
                    ]));
                }

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'invoice_product_id' => $invoiceProduct->id,
                    'sub_product_id' => $invoiceProduct->sub_product_id,
                    'product_id' => $invoiceProduct->product_id,
                    'quantity' => $returnQty,
                    'unit_price' => $invoiceProduct->price ?? 0,
                    'created_by' => $creatorId,
                ]);

                // Return qty back to stock.
                $subProduct = $invoiceProduct->subProduct;
                if ($subProduct) {
                    $subProduct->quantity = (float) $subProduct->quantity + $returnQty;
                    $subProduct->invoice_id = null;
                    $subProduct->pos_id = null;
                    $subProduct->booked = 0;
                    if ($subProduct->flag == SubProduct::FLAG_CANCELLED) {
                        $subProduct->flag = SubProduct::FLAG_PURCHASED;
                    }
                    $subProduct->save();
                }

                ProductService::where('id', $invoiceProduct->product_id)->increment('quantity', $returnQty);

                $stockMovement = new StockMovement();
                $stockMovement->product_id = $invoiceProduct->product_id;
                $stockMovement->sub_product_id = $invoiceProduct->sub_product_id;
                $stockMovement->invoice_id = $invoice->id;
                $stockMovement->bill_id = null;
                $stockMovement->qty_out = 0;
                $stockMovement->qty_in = $returnQty;
                $stockMovement->avg_cost = (float) ($invoiceProduct->product->avg_cost ?? $invoiceProduct->price ?? 0);
                $stockMovement->cost_price = (float) ($invoiceProduct->price ?? 0);
                $stockMovement->activity = 'Sales Return';
                $stockMovement->use_id = $invoice->customer_id;
                $stockMovement->item = $invoiceProduct->sub_product_id;
                $stockMovement->created_by = $creatorId;
                $stockMovement->save();

                // Reverse posted Invoice ledger entries (including tax) for this returned item.
                if ($invoice->status != 0 && $newVoucherId !== null) {
                    $baseQty = max((float) ($invoiceProduct->quantity ?? 0), 0.000001);
                    $reverseFactor = min(1, max(0, $returnQty / $baseQty));

                    $invoiceLedgerRows = GeneralLedger::where('created_by', $creatorId)
                        ->where('reference', 'Invoice')
                        ->where('ref_id', $invoice->id)
                        ->where('sub_product_id', $invoiceProduct->sub_product_id)
                        ->get();

                    foreach ($invoiceLedgerRows as $sourceRow) {
                        $reversedDebit = round(((float) $sourceRow->credit) * $reverseFactor, 2);
                        $reversedCredit = round(((float) $sourceRow->debit) * $reverseFactor, 2);

                        if ($reversedDebit == 0.0 && $reversedCredit == 0.0) {
                            continue;
                        }

                        $reverseEntry = new GeneralLedger();
                        $reverseEntry->vid = $newVoucherId;
                        $reverseEntry->account = $sourceRow->account;
                        $reverseEntry->type = 'Sales Return For ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $reverseEntry->debit = $reversedDebit;
                        $reverseEntry->credit = $reversedCredit;
                        $reverseEntry->ref_id = $salesReturn->id;
                        $reverseEntry->user_id = $sourceRow->user_id ?? 0;
                        $reverseEntry->sub_product_id = $invoiceProduct->sub_product_id;
                        $reverseEntry->created_by = $creatorId;
                        $reverseEntry->balance = $sourceRow->balance;
                        $reverseEntry->send_date = $sendDate;
                        $reverseEntry->deleted_qty = $returnQty;
                        $reverseEntry->reference = 'Sales Return';
                        $reverseEntry->ref_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        $reverseEntry->save();
                    }
                }
            }

            DB::commit();

            return redirect()->route('sales.return.index')->with('success', __('Sales return created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
