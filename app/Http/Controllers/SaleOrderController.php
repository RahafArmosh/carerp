<?php

namespace App\Http\Controllers;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\PickList;
use App\Models\PickListItem;
use App\Models\PackingList;
use App\Models\PackingListItem;
use App\Models\PackingBoxItem;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SaleOrderImport;
use App\Models\MasterlistLeadger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SaleOrderController extends Controller
{
    /**
     * Get unique sub_product_ids for a sale order (optionally filtered by part_no).
     * Reads from sale_order_items table (NOT from sub_products.sale_order_id).
     */
    private function getSaleOrderSubProductIdsFromItems(int $saleOrderId, ?string $partNo = null)
    {
        $query = SaleOrderItem::where('sale_order_id', $saleOrderId)
            ->whereNotNull('sub_product_id');

        if ($partNo !== null) {
            $partNoKey = strtoupper(trim($partNo));
            $query->whereRaw('UPPER(TRIM(part_no)) = ?', [$partNoKey]);
        }

        return $query->pluck('sub_product_id')->filter()->unique()->values();
    }

    /**
     * Allocate req_qty for a part_no from available stock (FIFO). Does NOT modify DB.
     * Returns array of [['sub_product' => SubProduct, 'qty' => float], ...] - one entry per sub-product with allocated qty.
     */
    private function allocatePartNoFromStock($creatorId, $partNo, $reqQty)
    {
        $partNo = strtoupper(trim($partNo));
        if ($partNo === '' || $reqQty <= 0) {
            return [];
        }

        $availableSubProducts = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [$partNo])
            ->where('created_by', $creatorId)
            ->where('flag', '!=', 2)
            ->where('booked', 0)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'ASC')
            ->get();

        if ($availableSubProducts->isEmpty()) {
            return [];
        }

        $product = ProductService::find($availableSubProducts->first()->product_id);
        $categoryType = ($product && $product->category) ? $product->category->type : null;
        $reqQty = (float)$reqQty;
        $allocations = [];

        if ($categoryType === 'Qty product') {
            $remaining = $reqQty;
            foreach ($availableSubProducts as $sp) {
                if ($remaining <= 0) {
                    break;
                }
                $availableQty = (float)$sp->quantity;
                $qty = min($availableQty, $remaining);
                if ($qty > 0) {
                    $allocations[] = ['sub_product' => $sp, 'qty' => $qty];
                    $remaining -= $qty;
                }
            }
        } else {
            $count = (int)$reqQty;
            $taken = 0;
            foreach ($availableSubProducts as $sp) {
                if ($taken >= $count) {
                    break;
                }
                $allocations[] = ['sub_product' => $sp, 'qty' => 1];
                $taken++;
            }
        }

        return $allocations;
    }

    /**
     * Book sub-products for sale order. Each SO item already has sub_product_id and stock_qty (one row per sub-product).
     */
    private function bookSubProductsForSaleOrder($saleOrder)
    {
        $creatorId = \Auth::user()->creatorId();

        foreach ($saleOrder->items as $item) {
            if (empty($item->sub_product_id) || (float)($item->stock_qty ?? 0) <= 0) {
                continue;
            }

            $sp = SubProduct::where('id', $item->sub_product_id)
                ->where('created_by', $creatorId)
                ->first();

            if (!$sp) {
                continue;
            }

            $product = ProductService::find($sp->product_id);
            $categoryType = ($product && $product->category) ? $product->category->type : null;
            $qtyToBook = (float)$item->stock_qty;

            if ($categoryType === 'Qty product') {
                $availableQty = (float)$sp->quantity;
                $take = min($availableQty, $qtyToBook);
                if ($take <= 0) {
                    continue;
                }
                $sp->quantity = $availableQty - $take;
                $sp->booked = ($sp->quantity <= 0) ? 1 : 0;
                $sp->sale_order_id = $saleOrder->id;
                $sp->so_qty_reserved = $take;
                $sp->save();
            } else {
                $sp->booked = 1;
                $sp->sale_order_id = $saleOrder->id;
                $sp->save();
            }
        }
    }

    /**
     * Get default tax for the company
     * Tries to find 5% tax first, then falls back to first tax
     */
    private function getDefaultTax($creatorId)
    {
        // Try to find 5% tax first (common default)
        $defaultTax = Tax::where('created_by', $creatorId)
            ->where('rate', 5)
            ->first();
        
        if ($defaultTax) {
            return (string)$defaultTax->id;
        }
        
        // Fallback to first tax for the company
        $firstTax = Tax::where('created_by', $creatorId)->first();
        if ($firstTax) {
            return (string)$firstTax->id;
        }
        
        // Return empty string if no tax found
        return '';
    }

    /**
     * Display a listing of sale orders
     */
    public function index(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $customer->prepend('All', '');

        $query = SaleOrder::where('created_by', '=', \Auth::user()->creatorId());

        if (!empty($request->customer)) {
            $query->where('customer_id', '=', $request->customer);
        }
        if (!empty($request->sales_order_date)) {
            $date_range = explode('to', $request->sales_order_date);
            if (count($date_range) == 2) {
                $query->whereBetween('sales_order_date', [trim($date_range[0]), trim($date_range[1])]);
            }
        }
        if (!empty($request->status)) {
            $query->where('status', '=', $request->status);
        }

        $saleOrders = $query->with(['customer', 'currency', 'items', 'invoice', 'pickList'])->orderBy('created_at', 'desc')->get();

        // Sale order status options (display labels)
        $statuses = [
            'draft' => __('CREATED'),
            'picking' => __('PICKING IN PROGRESS'),
            'packing_in_progress' => __('PACKING IN PROGRESS'),
            'packed' => __('PACKED'),
            'shipped' => __('SHIPPED'),
            'invoiced' => __('INVOICED'),
            'converted' => __('INVOICED'), // legacy, same as invoiced
        ];

        return view('saleorder.index', compact('saleOrders', 'customer', 'statuses'));
    }

    /**
     * Show import form
     */
    public function importFile()
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('saleorder.import');
    }

    /**
     * Show import form for items-only sale order import.
     * Header fields are provided in blade, file contains only items.
     */
    public function importFileItemsOnly()
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = \Auth::user()->creatorId();
        $customers = Customer::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        $currencies = Currency::select('id', 'name', 'exchange_rate')->orderBy('name')->get();
        $taxes = Tax::where('created_by', $creatorId)->orderBy('name')->get();

        return view('saleorder.import_items_only', compact('customers', 'currencies', 'taxes'));
    }

    /**
     * Handle import
     */
    public function import(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new SaleOrderImport(auth()->id()), $request->file('file'));

            return redirect()->route('saleorder.index')->with('success', __('Sale order imported successfully!'));
        } catch (\Exception $e) {
            \Log::error('Sale order import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            // Format error message for display
            $errorMessage = $e->getMessage();
            
            // If it's a stock validation error, format it nicely
            if (stripos($errorMessage, 'Stock validation failed') !== false) {
                // Split by newlines and format as HTML list
                $errorLines = explode("\n", $errorMessage);
                $formattedError = '<div class="alert alert-danger" style="max-height: 400px; overflow-y: auto;">';
                $formattedError .= '<h6><i class="ti ti-alert-triangle"></i> ' . __('Stock Validation Failed') . '</h6>';
                $formattedError .= '<ul class="mb-0 mt-2">';
                
                foreach ($errorLines as $line) {
                    $line = trim($line);
                    if (!empty($line) && stripos($line, 'Stock validation failed') === false) {
                        // Skip the "Stock validation failed:" header line
                        $formattedError .= '<li>' . htmlspecialchars($line) . '</li>';
                    }
                }
                
                $formattedError .= '</ul>';
                $formattedError .= '<p class="mt-2 mb-0"><small>' . __('Please check your stock levels and adjust the quantities in your import file.') . '</small></p>';
                $formattedError .= '</div>';
                
                return back()->with('error', $formattedError);
            } else {
                // For other errors, format as simple HTML
                $formattedError = '<div class="alert alert-danger">';
                $formattedError .= '<h6><i class="ti ti-alert-triangle"></i> ' . __('Import Failed') . '</h6>';
                $formattedError .= '<p class="mb-0">' . nl2br(htmlspecialchars($errorMessage)) . '</p>';
                $formattedError .= '</div>';
                
                return back()->with('error', $formattedError);
            }
        }
    }

    /**
     * Import sale order from items-only file.
     * Header fields are entered from the page.
     */
    public function importItemsOnly(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_date' => 'nullable|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            $creatorId = \Auth::user()->creatorId();
            $customer = Customer::where('id', $validated['customer_id'])
                ->where('created_by', $creatorId)
                ->first();

            if (!$customer) {
                return back()->with('error', __('Selected customer is invalid for this company.'));
            }

            $sheets = Excel::toArray([], $request->file('file'));
            $data = $sheets[0] ?? [];
            if (empty($data)) {
                throw new \Exception('Import file is empty.');
            }

            $headerRowIndex = null;
            $columnMap = [];

            for ($i = 0; $i < min(30, count($data)); $i++) {
                $row = $data[$i] ?? [];
                $candidateMap = [];

                foreach ($row as $colIndex => $headerValue) {
                    $normalized = strtoupper(trim((string) $headerValue));
                    $normalized = str_replace(['_', '-'], ' ', $normalized);
                    $normalized = preg_replace('/\s+/', ' ', $normalized);

                    if (in_array($normalized, ['PART NO', 'PART NUMBER', 'PARTNO'])) {
                        $candidateMap['part_no'] = $colIndex;
                    } elseif (in_array($normalized, ['DESCRIPTION', 'DESC'])) {
                        $candidateMap['description'] = $colIndex;
                    } elseif (in_array($normalized, ['REQ QTY', 'REQUIRED QTY', 'REQ QUANTITY'])) {
                        $candidateMap['req_qty'] = $colIndex;
                    } elseif (in_array($normalized, ['UNIT PRICE', 'PRICE', 'UNITPRICE'])) {
                        $candidateMap['unit_price'] = $colIndex;
                    }
                }

                if (isset($candidateMap['part_no']) && isset($candidateMap['req_qty'])) {
                    $headerRowIndex = $i;
                    $columnMap = $candidateMap;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('Could not find item header row. Required headers: PART NO and REQ QTY.');
            }

            $itemRows = array_slice($data, $headerRowIndex + 1);
            $preparedItems = [];
            $excelRowNo = $headerRowIndex + 2;

            foreach ($itemRows as $row) {
                $partNo = trim((string)($row[$columnMap['part_no']] ?? ''));
                if ($partNo === '') {
                    $excelRowNo++;
                    continue;
                }

                $reqQty = (float)($row[$columnMap['req_qty']] ?? 0);
                $unitPrice = isset($columnMap['unit_price']) ? (float)($row[$columnMap['unit_price']] ?? 0) : 0;
                $description = isset($columnMap['description']) ? trim((string)($row[$columnMap['description']] ?? '')) : '';

                if ($reqQty < 0 || $unitPrice < 0) {
                    throw new \Exception("Negative values are not allowed in row {$excelRowNo}.");
                }

                $preparedItems[] = [
                    'part_no' => $partNo,
                    'description' => $description,
                    'req_qty' => $reqQty,
                    'unit_price' => $unitPrice,
                ];
                $excelRowNo++;
            }

            if (empty($preparedItems)) {
                throw new \Exception('No valid item rows found in the uploaded file.');
            }

            DB::beginTransaction();
            try {
                $latestSaleOrder = SaleOrder::where('created_by', $creatorId)->withTrashed()->latest()->first();
                $saleOrderNo = $latestSaleOrder ? ((int)$latestSaleOrder->sale_order_no + 1) : 1;

                $currency = null;
                if (!empty($validated['currency_id'])) {
                    $currency = Currency::find($validated['currency_id']);
                }
                if (!$currency) {
                    $currency = Currency::where('code', 'AED')->first() ?: Currency::first();
                }

                $exchangeRate = $validated['exchange_rate'] ?? ($currency->exchange_rate ?? 1);
                $taxId = $validated['tax_id'] ?? $this->getDefaultTax($creatorId);

                $saleOrder = new SaleOrder();
                $saleOrder->sale_order_no = $saleOrderNo;
                $saleOrder->customer_id = $customer->id;
                $saleOrder->sales_order_date = $validated['sales_order_date'] ?? date('Y-m-d');
                $saleOrder->currency_id = $currency ? $currency->id : null;
                $saleOrder->exchange_rate = $exchangeRate;
                $saleOrder->tax_id = $taxId;
                $saleOrder->status = 'draft';
                $saleOrder->created_by = $creatorId;
                $saleOrder->save();

                foreach ($preparedItems as $itemData) {
                    $description = $itemData['description'];
                    if (empty(trim($description ?? ''))) {
                        $subProductForDesc = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($itemData['part_no']))])
                            ->where('created_by', $creatorId)
                            ->with('productService')
                            ->latest()
                            ->first();
                        if ($subProductForDesc && $subProductForDesc->productService) {
                            $description = $subProductForDesc->productService->name;
                        }
                    }

                    $allocations = $this->allocatePartNoFromStock($creatorId, $itemData['part_no'], $itemData['req_qty']);
                    if (empty($allocations)) {
                        $item = new SaleOrderItem();
                        $item->sale_order_id = $saleOrder->id;
                        $item->part_no = $itemData['part_no'];
                        $item->description = $description;
                        $item->req_qty = $itemData['req_qty'];
                        $item->stock_qty = 0;
                        $item->packed_qty = 0;
                        $item->unit_price = $itemData['unit_price'];
                        $item->product_id = null;
                        $item->sub_product_id = null;
                        $item->save();
                    } else {
                        foreach ($allocations as $alloc) {
                            $sp = $alloc['sub_product'];
                            $qty = $alloc['qty'];
                            $item = new SaleOrderItem();
                            $item->sale_order_id = $saleOrder->id;
                            $item->part_no = $itemData['part_no'];
                            $item->description = $description;
                            $item->req_qty = $qty;
                            $item->stock_qty = $qty;
                            $item->packed_qty = 0;
                            $item->unit_price = $itemData['unit_price'];
                            $item->product_id = $sp->product_id;
                            $item->sub_product_id = $sp->id;
                            $item->save();
                        }
                    }
                }

                DB::commit();

                $saleOrder->refresh();
                $this->bookSubProductsForSaleOrder($saleOrder);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return redirect()->route('saleorder.index')->with('success', __('Sale order imported successfully (items-only format).'));
        } catch (\Exception $e) {
            \Log::error('Sale order items-only import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    /**
     * Download sample file
     */
    public function downloadSample()
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $export = new \App\Exports\SaleOrderSampleExport();
            return $export();
        } catch (\Exception $e) {
            \Log::error('Error generating sale order sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Download sample file for sale order items-only import.
     */
    public function downloadSampleItemsOnly()
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);

            $headers = ['PART NO', 'DESCRIPTION', 'REQ QTY', 'UNIT PRICE'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $sheet->getStyle($col . '1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D3D3D3');
                $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }

            $sampleData = [
                ['04465-60280', 'FRONT BRAKE PAD', 100, 200.00],
                ['04465-60380', 'FRONT BRAKE PAD', 100, 190.00],
                ['04466-60160', 'REAR BRAKE PAD', 80, 175.00],
            ];

            $row = 2;
            foreach ($sampleData as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $index => $value) {
                    $sheet->setCellValue($col . $row, $value);
                    if (in_array($index, [2, 3])) {
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    $col++;
                }
                $row++;
            }

            $sheet->getStyle('A1:D' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $filename = 'sample-saleorder-items-only-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error generating sale order items-only sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Display the specified sale order
     */
    public function show($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'currency', 'items.product', 'items.subProduct', 'creator', 'pickList', 'invoice'])
                ->firstOrFail();

            // Group items by part_no for display: show one row per part_no
            $groupedItems = $saleOrder->items
                ->groupBy(function ($item) {
                    return strtoupper(trim((string)$item->part_no));
                })
                ->map(function ($group) {
                    $first = $group->first();
                    $reqQty = $group->sum(function ($i) {
                        return (float)($i->req_qty ?? 0);
                    });
                    $stockQty = $group->sum(function ($i) {
                        return (float)($i->stock_qty ?? 0);
                    });
                    $pickingQty = $group->sum(function ($i) {
                        return (float)($i->picking_qty ?? 0);
                    });
                    $packedQty = $group->sum(function ($i) {
                        return (float)($i->packed_qty ?? 0);
                    });

                    // Discrepancy for display = packed_qty - stock_qty (same logic as per-item)
                    $discrepancy = $packedQty - ($stockQty ?: $reqQty);

                    // Clone a shallow copy for display to avoid mutating original collection
                    $displayItem = clone $first;
                    $displayItem->req_qty = $reqQty;
                    $displayItem->stock_qty = $stockQty;
                    $displayItem->picking_qty = $pickingQty;
                    $displayItem->packed_qty = $packedQty;
                    $displayItem->discrepancy = $discrepancy;

                    return $displayItem;
                })
                ->values();

            // Get taxes for display
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();
            // Company users (including the company user) for Assign to when converting SO to pick list
            $assignUsers = \App\Models\User::where(function ($q) {
                $q->where('created_by', \Auth::user()->creatorId())->where('type', '!=', 'client')
                  ->orWhere('id', \Auth::user()->creatorId());
            })
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id');

            return view('saleorder.show', [
                'saleOrder' => $saleOrder,
                'taxes' => $taxes,
                'assignUsers' => $assignUsers,
                'groupedItems' => $groupedItems,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('saleorder.index')->with('error', __('Sale order not found.'));
        }
    }

    public function show2($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = $id;
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'currency', 'items.product', 'items.subProduct', 'creator', 'pickList', 'invoice'])
                ->firstOrFail();

            // Group items by part_no for display: show one row per part_no
            $groupedItems = $saleOrder->items
                ->groupBy(function ($item) {
                    return strtoupper(trim((string)$item->part_no));
                })
                ->map(function ($group) {
                    $first = $group->first();
                    $reqQty = $group->sum(function ($i) {
                        return (float)($i->req_qty ?? 0);
                    });
                    $stockQty = $group->sum(function ($i) {
                        return (float)($i->stock_qty ?? 0);
                    });
                    $pickingQty = $group->sum(function ($i) {
                        return (float)($i->picking_qty ?? 0);
                    });
                    $packedQty = $group->sum(function ($i) {
                        return (float)($i->packed_qty ?? 0);
                    });

                    // Discrepancy for display = packed_qty - stock_qty (same logic as per-item)
                    $discrepancy = $packedQty - ($stockQty ?: $reqQty);

                    // Clone a shallow copy for display to avoid mutating original collection
                    $displayItem = clone $first;
                    $displayItem->req_qty = $reqQty;
                    $displayItem->stock_qty = $stockQty;
                    $displayItem->picking_qty = $pickingQty;
                    $displayItem->packed_qty = $packedQty;
                    $displayItem->discrepancy = $discrepancy;

                    return $displayItem;
                })
                ->values();

            // Get taxes for display
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();
            // Company users (including the company user) for Assign to when converting SO to pick list
            $assignUsers = \App\Models\User::where(function ($q) {
                $q->where('created_by', \Auth::user()->creatorId())->where('type', '!=', 'client')
                  ->orWhere('id', \Auth::user()->creatorId());
            })
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id');

            return view('saleorder.show', [
                'saleOrder' => $saleOrder,
                'taxes' => $taxes,
                'assignUsers' => $assignUsers,
                'groupedItems' => $groupedItems,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('saleorder.index')->with('error', __('Sale order not found.'));
        }
    }

    /**
     * Print sale order.
     * Optional query param: show_custom_fields=1 to include sub-product custom fields.
     */
    public function print(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $creatorId = \Auth::user()->creatorId();
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', $creatorId)
                ->with(['customer', 'currency', 'items.product', 'items.subProduct', 'creator'])
                ->firstOrFail();

            $showCustomFields = $request->boolean('show_custom_fields', false);
            $customFields = collect();
            $customValuesBySubProduct = collect();

            if ($showCustomFields) {
                $subProductIds = $saleOrder->items->pluck('sub_product_id')->filter()->unique()->values();
                $customFields = CustomField::where('created_by', $creatorId)
                    ->where('module', 'sub-product')
                    ->orderBy('id')
                    ->get(['id', 'name']);

                if ($subProductIds->isNotEmpty() && $customFields->isNotEmpty()) {
                    $customValuesBySubProduct = CustomFieldValue::whereIn('record_id', $subProductIds->all())
                        ->whereIn('field_id', $customFields->pluck('id')->all())
                        ->whereNotNull('value')
                        ->get(['record_id', 'field_id', 'value'])
                        ->groupBy('record_id');
                }
            }

            // Group items by part_no for normal print (same as show)
            $groupedItems = $saleOrder->items
                ->groupBy(function ($item) {
                    return strtoupper(trim((string) $item->part_no));
                })
                ->map(function ($group) use ($showCustomFields, $customFields, $customValuesBySubProduct) {
                    $first = $group->first();
                    $reqQty = $group->sum(function ($i) {
                        return (float) ($i->req_qty ?? 0);
                    });
                    $stockQty = $group->sum(function ($i) {
                        return (float) ($i->stock_qty ?? 0);
                    });
                    $pickingQty = $group->sum(function ($i) {
                        return (float) ($i->picking_qty ?? 0);
                    });
                    $packedQty = $group->sum(function ($i) {
                        return (float) ($i->packed_qty ?? 0);
                    });

                    $discrepancy = $packedQty - ($stockQty ?: $reqQty);

                    $displayItem = clone $first;
                    $displayItem->req_qty = $reqQty;
                    $displayItem->stock_qty = $stockQty;
                    $displayItem->picking_qty = $pickingQty;
                    $displayItem->packed_qty = $packedQty;
                    $displayItem->discrepancy = $discrepancy;
                    $displayItem->custom_fields_text = '';

                    if ($showCustomFields && $customFields->isNotEmpty()) {
                        $pairs = [];
                        foreach ($group as $groupItem) {
                            if (empty($groupItem->sub_product_id)) {
                                continue;
                            }
                            $vals = $customValuesBySubProduct->get($groupItem->sub_product_id) ?? collect();
                            foreach ($customFields as $cf) {
                                $val = optional($vals->firstWhere('field_id', $cf->id))->value;
                                if ($val !== null && trim((string) $val) !== '') {
                                    $pairs[$cf->name . ':' . $val] = $cf->name . ': ' . $val;
                                }
                            }
                        }
                        $displayItem->custom_fields_text = implode(', ', array_values($pairs));
                    }

                    return $displayItem;
                })
                ->values();

            // For "Print + Custom Fields", print item-level rows and show only fields with values per item.
            $printItems = $groupedItems;
            if ($showCustomFields) {
                $printItems = $saleOrder->items->map(function ($item) use ($customFields, $customValuesBySubProduct) {
                    $displayItem = clone $item;
                    $displayItem->custom_fields_text = '';

                    if (!empty($item->sub_product_id) && $customFields->isNotEmpty()) {
                        $vals = $customValuesBySubProduct->get($item->sub_product_id) ?? collect();
                        $pairs = [];
                        foreach ($customFields as $cf) {
                            $val = optional($vals->firstWhere('field_id', $cf->id))->value;
                            if ($val !== null && trim((string) $val) !== '') {
                                $pairs[] = $cf->name . ': ' . $val;
                            }
                        }
                        $displayItem->custom_fields_text = implode(', ', $pairs);
                    }

                    return $displayItem;
                })->values();
            }

            $taxes = Tax::where('created_by', $creatorId)->get();

            $settings = \App\Models\Utility::settings();
            $color = '#' . ($settings['bill_color'] ?? '000000');
            $font_color = \App\Models\Utility::getFontColor($color);

            return view('saleorder.print', compact('saleOrder', 'groupedItems', 'printItems', 'taxes', 'showCustomFields', 'color', 'font_color'));
        } catch (\Exception $e) {
            return redirect()->route('saleorder.index')->with('error', __('Sale order not found.'));
        }
    }

    /**
     * Show the form for editing the specified sale order
     */
    public function edit($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'currency', 'items.product', 'items.subProduct'])
                ->firstOrFail();

            // Don't allow editing if already converted to invoice
            if ($saleOrder->isConverted()) {
                return redirect()->route('saleorder.show', $id)->with('error', __('Cannot edit a converted sale order.'));
            }

            // Don't allow editing once converted to picking (pick list exists)
            if (PickList::where('sales_order_id', $saleOrder->id)->exists()) {
                return redirect()->route('saleorder.show', $id)->with('error', __('Cannot edit a sale order that has been sent for pick and pack.'));
            }

            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');

            $currencies = Currency::get()->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');

            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Set default tax if not set
            if (empty($saleOrder->tax_id)) {
                $saleOrder->tax_id = $this->getDefaultTax(\Auth::user()->creatorId());
                $saleOrder->save();
            }

            return view('saleorder.edit', compact('saleOrder', 'customers', 'currencies', 'taxes'));
        } catch (\Exception $e) {
            return redirect()->route('saleorder.index')->with('error', __('Sale order not found.'));
        }
    }

    /**
     * Update the specified sale order
     */
    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            // Don't allow editing if already converted to invoice
            if ($saleOrder->isConverted()) {
                return redirect()->route('saleorder.show', $id)->with('error', __('Cannot edit a converted sale order.'));
            }

            // Don't allow editing once converted to picking (pick list exists)
            if (PickList::where('sales_order_id', $saleOrder->id)->exists()) {
                return redirect()->route('saleorder.show', $id)->with('error', __('Cannot edit a sale order that has been sent for pick and pack.'));
            }

                $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'sales_order_date' => 'required|date',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|array',
                'status' => 'required|in:draft,picking,packing_in_progress,packed,invoiced,shipped,converted',
                'items' => 'required|array|min:1',
                'items.*.part_no' => 'required|string',
                'items.*.description' => 'nullable|string',
                'items.*.req_qty' => 'required|numeric|min:0',
                'items.*.stock_qty' => 'nullable|numeric|min:0',
                'items.*.packed_qty' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.product_id' => 'nullable|exists:product_services,id',
                'items.*.sub_product_id' => 'nullable|exists:sub_products,id',
            ]);

            DB::beginTransaction();
            try {
                // Preserve packed_qty, stock_qty and picking_qty from existing items by (part_no, sub_product_id)
                $existingByPartNoAndSub = [];
                foreach ($saleOrder->items as $oldItem) {
                    $partKey = strtoupper(trim($oldItem->part_no ?? ''));
                    $subId = $oldItem->sub_product_id ?? 'new';
                    if ($partKey !== '') {
                        $existingByPartNoAndSub[$partKey . '_' . $subId] = [
                            'packed_qty' => (float)($oldItem->packed_qty ?? 0),
                            'stock_qty' => (float)($oldItem->stock_qty ?? 0),
                            'picking_qty' => (float)($oldItem->picking_qty ?? 0),
                        ];
                    }
                }

                // Unbook sub-products linked to this SO and restore quantity for Qty products
                $subProductIds = $this->getSaleOrderSubProductIdsFromItems((int)$saleOrder->id);
                SubProduct::whereIn('id', $subProductIds)->get()->each(function ($sp) {
                    if ((float)($sp->so_qty_reserved ?? 0) > 0) {
                        $sp->quantity = (float)$sp->quantity + (float)$sp->so_qty_reserved;
                    }
                    $sp->booked = 0;
                    $sp->sale_order_id = null;
                    $sp->so_qty_reserved = null;
                    $sp->save();
                });

                // Update sale order
                $saleOrder->customer_id = $validated['customer_id'];
                $saleOrder->sales_order_date = $validated['sales_order_date'];
                $saleOrder->currency_id = $validated['currency_id'] ?? null;
                $saleOrder->exchange_rate = $validated['exchange_rate'] ?? 1.0;
                $saleOrder->tax_id = !empty($validated['tax_id']) ? implode(',', $validated['tax_id']) : $this->getDefaultTax(\Auth::user()->creatorId());
                $saleOrder->status = $validated['status'];
                $saleOrder->save();

                // Delete existing items
                $saleOrder->items()->delete();

                $creatorId = \Auth::user()->creatorId();

                // Create new items: either one per (part_no + sub_product_id) from form, or expand from part_no + req_qty via allocation
                foreach ($validated['items'] as $itemData) {
                    $partNo = $itemData['part_no'] ?? '';
                    $partNoKey = strtoupper(trim($partNo));
                    $reqQty = (float)($itemData['req_qty'] ?? 0);
                    $subProductId = $itemData['sub_product_id'] ?? null;

                    if (!empty($subProductId)) {
                        // Existing row from edit form: one SO item with this sub_product_id
                        $preserveKey = $partNoKey . '_' . $subProductId;
                        $existing = $existingByPartNoAndSub[$preserveKey] ?? null;
                        $item = new SaleOrderItem();
                        $item->sale_order_id = $saleOrder->id;
                        $item->part_no = $partNo;
                        $item->description = $itemData['description'] ?? null;
                        $item->req_qty = $reqQty;
                        $item->stock_qty = $existing ? $existing['stock_qty'] : ($itemData['stock_qty'] ?? $reqQty);
                        $item->packed_qty = $existing ? $existing['packed_qty'] : ($itemData['packed_qty'] ?? 0);
                        $item->picking_qty = $existing ? $existing['picking_qty'] : ($itemData['picking_qty'] ?? 0);
                        $item->unit_price = $itemData['unit_price'] ?? 0;
                        $item->product_id = $itemData['product_id'] ?? null;
                        $item->sub_product_id = $subProductId;
                        $item->save();
                    } else {
                        // New or aggregated line: allocate from stock (FIFO) and create one SO item per sub-product with allocated qty
                        $allocations = $this->allocatePartNoFromStock($creatorId, $partNo, $reqQty);
                        if (empty($allocations)) {
                            $item = new SaleOrderItem();
                            $item->sale_order_id = $saleOrder->id;
                            $item->part_no = $partNo;
                            $item->description = $itemData['description'] ?? null;
                            $item->req_qty = $reqQty;
                            $item->stock_qty = 0;
                            $item->packed_qty = 0;
                            $item->picking_qty = 0;
                            $item->unit_price = $itemData['unit_price'] ?? 0;
                            $item->product_id = $itemData['product_id'] ?? null;
                            $item->sub_product_id = null;
                            $item->save();
                        } else {
                            foreach ($allocations as $alloc) {
                                $sp = $alloc['sub_product'];
                                $qty = $alloc['qty'];
                                $item = new SaleOrderItem();
                                $item->sale_order_id = $saleOrder->id;
                                $item->part_no = $partNo;
                                $item->description = $itemData['description'] ?? null;
                                $item->req_qty = $qty;
                                $item->stock_qty = $qty;
                                $item->packed_qty = 0;
                                $item->picking_qty = 0;
                                $item->unit_price = $itemData['unit_price'] ?? 0;
                                $item->product_id = $sp->product_id;
                                $item->sub_product_id = $sp->id;
                                $item->save();
                            }
                        }
                    }
                }

                DB::commit();
                
                // Book sub-products using FIFO after sale order is updated
                $saleOrder->refresh(); // Reload with items
                $this->bookSubProductsForSaleOrder($saleOrder);

                return redirect()->route('saleorder.show', $id)->with('success', __('Sale order updated successfully.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Sale order update failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', __('Failed to update sale order: ') . $e->getMessage());
        }
    }

    /**
     * Update sale order status only (manual status change)
     */
    public function updateStatus(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,picking,packing_in_progress,packed,invoiced,shipped,converted',
        ]);

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $saleOrder->status = $validated['status'];
            $saleOrder->save();

            return redirect()->back()->with('success', __('Status updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update status: ') . $e->getMessage());
        }
    }

    /**
     * Convert sale order to pick list (POST: optional assigned_to user)
     */
    public function convertToPickList(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['items', 'customer'])
                ->firstOrFail();

            // Check if pick list already exists
            if ($saleOrder->pickList) {
                return redirect()->route('picklist.show', \Crypt::encrypt($saleOrder->pickList->id))
                    ->with('info', __('Pick list already exists for this sale order.'));
            }

            DB::beginTransaction();
            try {
                // Create pick list (assigned_to set by user who converts)
                $pickList = new PickList();
                $pickList->sales_order_id = $saleOrder->id;
                $pickList->customer_id = $saleOrder->customer_id;
                $pickList->packing_ref = null; // Can be set later
                $pickList->pick_list_date = date('Y-m-d');
                $pickList->picked_by = null; // Set when items are picked
                $pickList->assigned_to = $request->input('assigned_to') ?: null;
                $pickList->created_by = \Auth::user()->creatorId();
                $pickList->save();

                // Set sale order status to picking when converted to pick list
                $saleOrder->status = 'picking';
                $saleOrder->save();

                // Get bin location custom field (find by name - case insensitive, normalized)
                // Try "BIN LOCATION 1" first, then "BIN LOCATION" as fallback
                $creatorId = \Auth::user()->creatorId();
                $binLocationField = CustomField::where('created_by', $creatorId)
                    ->where('module', 'sub-product')
                    ->where(function($query) {
                        $query->whereRaw('UPPER(TRIM(name)) = ?', ['BIN LOCATION 1'])
                              ->orWhereRaw('LOWER(TRIM(REPLACE(REPLACE(REPLACE(name, " ", ""), "_", ""), "-", ""))) = ?', [
                                  strtolower(trim(str_replace([' ', '_', '-'], '', 'Bin Location')))
                              ]);
                    })
                    ->first();

                // Create pick list items from sale order items
                // NOTE:
                // - We do NOT update sale order items' packed_qty here. Packed_qty should only be
                //   updated when packing lists are created/updated, not when picking lists are created.
                // - If stock_qty is 0 or empty, the item is NOT sent to Pick & Pack.
                foreach ($saleOrder->items as $saleOrderItem) {
                    $reservedQty = (float)($saleOrderItem->stock_qty ?? 0);
                    if ($reservedQty <= 0) {
                        continue;
                    }
                    $binLocation = null;
                    
                    // Get bin location from booked sub-products for this sale order item
                    if (!empty($saleOrderItem->part_no) && $binLocationField) {
                        // Find sub-products from sale order items for this part_no (do NOT read via sub_products.sale_order_id)
                        $idsForPartNo = $this->getSaleOrderSubProductIdsFromItems((int)$saleOrder->id, (string)$saleOrderItem->part_no);
                        $bookedSubProducts = $idsForPartNo->isEmpty()
                            ? collect()
                            : SubProduct::whereIn('id', $idsForPartNo)
                                ->where('created_by', $creatorId)
                                ->get();
                        
                        // Collect unique bin locations from all booked sub-products
                        $binLocations = [];
                        foreach ($bookedSubProducts as $subProduct) {
                            // Get custom field value for bin location
                            $binLocationValue = CustomFieldValue::where('record_id', $subProduct->id)
                                ->where('field_id', $binLocationField->id)
                                ->first();
                            
                            if ($binLocationValue && !empty(trim($binLocationValue->value))) {
                                $location = trim($binLocationValue->value);
                                if (!in_array($location, $binLocations)) {
                                    $binLocations[] = $location;
                                }
                            }
                        }
                        
                        // Join multiple bin locations with comma, or use single location
                        if (!empty($binLocations)) {
                            $binLocation = implode(', ', $binLocations);
                        }
                    }
                    
                    $pickListItem = new PickListItem();
                    $pickListItem->pick_list_id = $pickList->id;
                    $pickListItem->bin_location = $binLocation; // Auto-filled from booked sub-products' custom fields
                    $pickListItem->part_no = $saleOrderItem->part_no;
                    $pickListItem->description = $saleOrderItem->description;
                    $pickListItem->req_qty = $reservedQty; // Use reserved qty (stock_qty), not customer req_qty
                    $pickListItem->picked_qty = 0; // Will be filled when user picks
                    $pickListItem->tick = false;
                    $pickListItem->save();
                }

                DB::commit();

                return redirect()->route('picklist.edit', \Crypt::encrypt($pickList->id))
                    ->with('success', __('Sale order converted to pick list successfully. You can now edit the picking quantities.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Convert sale order to pick list failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', __('Failed to convert to pick list: ') . $e->getMessage());
        }
    }

    /**
     * Convert sale order to invoice (only if status is approved)
     */
    public function convertToInvoice($id)
    {
        if (!\Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'currency', 'items.product', 'items.subProduct', 'invoice'])
                ->firstOrFail();

            // Check if already converted
            if ($saleOrder->isConverted()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($saleOrder->invoice_id))
                    ->with('info', __('Sale order already converted to invoice.'));
            }

            // Allow convert when status is approved (legacy) or packed
            $allowedStatuses = ['approved', 'packed'];
            if (!in_array($saleOrder->status, $allowedStatuses)) {
                return redirect()->back()->with('error', __('Sale order must be Packed (or Approved) before converting to invoice. Current status: ') . $saleOrder->status);
            }

            // Check if sale order has items
            if ($saleOrder->items->isEmpty()) {
                return redirect()->back()->with('error', __('Sale order has no items to convert.'));
            }

            DB::beginTransaction();
            try {
                // Get default category (first category for the user)
                $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->first();
                if (!$category) {
                    throw new \Exception(__('No category found. Please create a category first.'));
                }

                // Use sale order's tax_id if available, otherwise get default tax
                $taxId = !empty($saleOrder->tax_id) ? $saleOrder->tax_id : $this->getDefaultTax(\Auth::user()->creatorId());

                // Generate invoice number
                $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->withTrashed()->latest()->first();
                $invoiceNumber = $latest ? ($latest->invoice_id + 1) : 1;

                // Create invoice
                $invoice = new Invoice();
                $invoice->invoice_id = $invoiceNumber;
                $invoice->customer_id = $saleOrder->customer_id;
                $invoice->status = 0; // Draft
                $invoice->issue_date = $saleOrder->sales_order_date ?? date('Y-m-d');
                $invoice->due_date = date('Y-m-d', strtotime('+30 days')); // Default 30 days from issue date
                $invoice->category_id = $category->id;
                $invoice->ref_number = 'SO-' . $saleOrder->sale_order_no; // Reference to sale order
                $invoice->type = 'regular';
                $invoice->currency_id = $saleOrder->currency_id;
                $invoice->exchange_rate = $saleOrder->exchange_rate ?? 1.0;
                $invoice->tax_id = $taxId ? (string)$taxId : '';
                $invoice->created_by = \Auth::user()->creatorId();
                $invoice->salesman_id = \Auth::user()->creatorId();
                $invoice->save();

                // Create invoice products from sale order items
                // Use PACKED QTY only. Do NOT book or adjust stock - just save sub_product_id, sale price, VAT, and qty to invoice.
                $creatorId = \Auth::user()->creatorId();
                foreach ($saleOrder->items as $saleOrderItem) {
                    $packedQty = $saleOrderItem->packed_qty ?? 0;
                    if ($packedQty <= 0) {
                        continue;
                    }

                    $unitPrice = $saleOrderItem->unit_price ?? 0;
                    $exchangeRate = $saleOrder->exchange_rate ?? 1.0;

                    // Save unit price (per piece), not line total — invoice view uses (qty * rate) for totals/tax
                    $invoiceProduct = new InvoiceProduct();
                    $invoiceProduct->invoice_id = $invoice->id;
                    $invoiceProduct->product_id = $saleOrderItem->product_id;
                    $invoiceProduct->sub_product_id = $saleOrderItem->sub_product_id; // From SO item only; no stock booking
                    $invoiceProduct->quantity = $packedQty; // Qty from PACKED QTY
                    $invoiceProduct->tax = $taxId; // VAT
                    $invoiceProduct->exchange_price = $unitPrice;
                    $invoiceProduct->price = $unitPrice * $exchangeRate;
                    $invoiceProduct->exchange_discount = 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->description = $saleOrderItem->description;
                    $invoiceProduct->save();

                    // Save invoice_id and booked on the sub_product that has been booked for this invoice
                    if (!empty($saleOrderItem->sub_product_id)) {
                        SubProduct::where('id', $saleOrderItem->sub_product_id)
                            ->where('created_by', $creatorId)
                            ->update([
                                'invoice_id' => $invoice->id,
                                'booked' => 1,
                            ]);
                    }
                }

                // Return discrepancy (stock_qty - packed_qty) when > 0 to the sub-product saved on the SO item (old qty + discrepancy)
                foreach ($saleOrder->items as $saleOrderItem) {
                    $stockQty = (float)($saleOrderItem->stock_qty ?? 0);
                    $packedQty = (float)($saleOrderItem->packed_qty ?? 0);
                    $excessQty = $stockQty - $packedQty;
                    if ($excessQty <= 0) {
                        continue;
                    }

                    // Prefer the sub-product linked to this SO item (sub_product_id)
                    $bookedSubProducts = collect();
                    if (!empty($saleOrderItem->sub_product_id)) {
                        $subProduct = SubProduct::where('id', $saleOrderItem->sub_product_id)
                            ->where('created_by', $creatorId)
                            ->first();
                        if ($subProduct) {
                            $bookedSubProducts = collect([$subProduct]);
                        }
                    }
                    if ($bookedSubProducts->isEmpty()) {
                        $idsForPartNo = $this->getSaleOrderSubProductIdsFromItems((int)$saleOrder->id, (string)$saleOrderItem->part_no);
                        $bookedSubProducts = $idsForPartNo->isEmpty()
                            ? collect()
                            : SubProduct::whereIn('id', $idsForPartNo)
                                ->where('created_by', $creatorId)
                                ->orderBy('created_at', 'ASC')
                                ->get();
                    }
                    if ($bookedSubProducts->isEmpty()) {
                        continue;
                    }

                    $product = ProductService::find($saleOrderItem->product_id);
                    if (!$product) {
                        continue;
                    }
                    $categoryType = $product->category ? $product->category->type : null;
                    $isQtyProduct = $categoryType === 'Qty product';

                    $this->returnExcessQuantityToStock(
                        $saleOrderItem,
                        $bookedSubProducts,
                        $excessQty,
                        $isQtyProduct,
                        $product,
                        $saleOrder
                    );
                }

                // Link invoice to sale order
                $saleOrder->invoice_id = $invoice->id;
                $saleOrder->status = 'invoiced';
                $saleOrder->save();

                DB::commit();

                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))
                    ->with('success', __('Sale order converted to invoice successfully.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Convert sale order to invoice failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->id,
                'sale_order_id' => $id,
            ]);
            return redirect()->back()->with('error', __('Failed to convert to invoice: ') . $e->getMessage());
        }
    }

    /**
     * Return excess quantity (req_qty - packed_qty) back to stock
     * 
     * @param SaleOrderItem $saleOrderItem
     * @param \Illuminate\Database\Eloquent\Collection $bookedSubProducts
     * @param float $excessQty
     * @param bool $isQtyProduct
     * @param ProductService $product
     * @param SaleOrder $saleOrder
     */
    private function returnExcessQuantityToStock(
        $saleOrderItem,
        $bookedSubProducts,
        $excessQty,
        $isQtyProduct,
        $product,
        $saleOrder
    ) {
        $creatorId = \Auth::user()->creatorId();
        $category = $product->category;
        
        if ($isQtyProduct) {
            $totalReturned = 0;
            // When returning to the single sub-product from SO item (sub_product_id): old qty + discrepancy (full excess to that sub-product)
            $singleSubProductBySoItem = $bookedSubProducts->count() === 1 && !empty($saleOrderItem->sub_product_id)
                && (int)$bookedSubProducts->first()->id === (int)$saleOrderItem->sub_product_id;
            if ($singleSubProductBySoItem) {
                $subProduct = $bookedSubProducts->first();
                $subProduct->refresh();
                $subProduct->quantity = (float)$subProduct->quantity + $excessQty;
                $subProduct->so_qty_reserved = null;
                if ($subProduct->quantity > 0 && $subProduct->booked > 0 && !$subProduct->invoice_id) {
                    $subProduct->booked = 0;
                }
                $subProduct->save();
                $totalReturned = $excessQty;
                $avgCost = $product->avg_cost ?? ($subProduct->purchase_price ?? 0);
                $stockMovement = new StockMovement();
                $stockMovement->product_id = $product->id;
                $stockMovement->sub_product_id = $subProduct->id;
                $stockMovement->invoice_id = $subProduct->invoice_id;
                $stockMovement->bill_id = null;
                $stockMovement->pos_id = null;
                $stockMovement->qty_in = $excessQty;
                $stockMovement->qty_out = 0;
                $stockMovement->avg_cost = $avgCost;
                $stockMovement->cost_price = $subProduct->purchase_price ?? 0;
                $stockMovement->activity = 'Return from Sale Order (Excess Quantity)';
                $stockMovement->use_id = $saleOrder->customer_id;
                $stockMovement->item = $subProduct->id;
                $stockMovement->created_by = $creatorId;
                $stockMovement->save();
            } else {
                $packedQty = (float)($saleOrderItem->packed_qty ?? 0);
                $subProductsInOrder = $bookedSubProducts->values(); // FIFO order

                // If we have so_qty_reserved on sub-products, distribute return per sub-product (return = reserved - packed_allocated in FIFO)
                $hasReserved = $bookedSubProducts->contains(fn ($sp) => $sp->so_qty_reserved !== null && (float)$sp->so_qty_reserved > 0);
                if ($hasReserved) {
                    $packedRemaining = $packedQty;
                    foreach ($subProductsInOrder as $subProduct) {
                        $subProduct->refresh();
                        $reserved = (float)($subProduct->so_qty_reserved ?? 0);
                        $packedFromThis = $reserved > 0 ? min($reserved, max(0, $packedRemaining)) : 0;
                        $packedRemaining -= $packedFromThis;
                        $qtyToReturn = $reserved - $packedFromThis;
                        if ($qtyToReturn <= 0) {
                            $subProduct->so_qty_reserved = null;
                            $subProduct->save();
                            continue;
                        }
                        $subProduct->quantity += $qtyToReturn;
                        $subProduct->so_qty_reserved = null;
                        if ($subProduct->quantity > 0 && $subProduct->booked > 0 && !$subProduct->invoice_id) {
                            $subProduct->booked = 0;
                        }
                        $subProduct->save();
                        $totalReturned += $qtyToReturn;
                        $avgCost = $product->avg_cost ?? ($subProduct->purchase_price ?? 0);
                        $stockMovement = new StockMovement();
                        $stockMovement->product_id = $product->id;
                        $stockMovement->sub_product_id = $subProduct->id;
                        $stockMovement->invoice_id = $subProduct->invoice_id;
                        $stockMovement->bill_id = null;
                        $stockMovement->pos_id = null;
                        $stockMovement->qty_in = $qtyToReturn;
                        $stockMovement->qty_out = 0;
                        $stockMovement->avg_cost = $avgCost;
                        $stockMovement->cost_price = $subProduct->purchase_price ?? 0;
                        $stockMovement->activity = 'Return from Sale Order (Excess Quantity)';
                        $stockMovement->use_id = $saleOrder->customer_id;
                        $stockMovement->item = $subProduct->id;
                        $stockMovement->created_by = $creatorId;
                        $stockMovement->save();
                    }
                } else {
                    // Fallback: no so_qty_reserved (e.g. old data) – return all to last booked first (reverse FIFO)
                    $remainingExcess = $excessQty;
                    $reversedSubProducts = $bookedSubProducts->reverse()->values();
                    foreach ($reversedSubProducts as $subProduct) {
                        if ($remainingExcess <= 0) {
                            break;
                        }
                        $subProduct->refresh();
                        $qtyToReturn = $remainingExcess;
                        $subProduct->quantity += $qtyToReturn;
                        if ($subProduct->quantity > 0 && $subProduct->booked > 0 && !$subProduct->invoice_id) {
                            $subProduct->booked = 0;
                        }
                        $subProduct->save();
                        $totalReturned += $qtyToReturn;
                        $avgCost = $product->avg_cost ?? ($subProduct->purchase_price ?? 0);
                        $stockMovement = new StockMovement();
                        $stockMovement->product_id = $product->id;
                        $stockMovement->sub_product_id = $subProduct->id;
                        $stockMovement->invoice_id = $subProduct->invoice_id;
                        $stockMovement->bill_id = null;
                        $stockMovement->pos_id = null;
                        $stockMovement->qty_in = $qtyToReturn;
                        $stockMovement->qty_out = 0;
                        $stockMovement->avg_cost = $avgCost;
                        $stockMovement->cost_price = $subProduct->purchase_price ?? 0;
                        $stockMovement->activity = 'Return from Sale Order (Excess Quantity)';
                        $stockMovement->use_id = $saleOrder->customer_id;
                        $stockMovement->item = $subProduct->id;
                        $stockMovement->created_by = $creatorId;
                        $stockMovement->save();
                        $remainingExcess -= $qtyToReturn;
                    }
                }
            }

            // Log if we didn't return all excess (shouldn't happen, but good for debugging)
            if ($totalReturned < $excessQty) {
                \Log::warning('Not all excess quantity was returned to stock', [
                    'sale_order_id' => $saleOrder->id,
                    'sale_order_item_id' => $saleOrderItem->id,
                    'part_no' => $saleOrderItem->part_no,
                    'excess_qty' => $excessQty,
                    'returned' => $totalReturned,
                    'booked_subproducts_count' => $bookedSubProducts->count(),
                ]);
            }

            // Update product average cost if category type is Qty product and using avg method
            if ($category && $category->type === "Qty product" && $excessQty > 0) {
                $costCalculationMethod = $category->cost_calculation_method ?? 'avg';
                if ($costCalculationMethod === 'avg') {
                    $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                        ->where('created_by', $creatorId)
                        ->pluck('id')
                        ->toArray();
                    $lastPurchasedSubProductQty = SubProduct::where('product_id', $product->id)
                        ->whereIn('bill_id', $purchasedBillIds)
                        ->where('flag', '!=', 0)
                        ->whereNotNull('bill_id')
                        ->sum('quantity') ?? 0;
                    $product->refresh();
                    $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($bookedSubProducts->first()->purchase_price ?? 0);
                    $totalQty = $lastPurchasedSubProductQty + $excessQty;
                    if ($totalQty > 0) {
                        $newAvgCost = (($lastPurchasedSubProductQty * $lastAvg) + ($excessQty * $lastAvg)) / $totalQty;
                        $product->avg_cost = $newAvgCost;
                        $product->save();
                    }
                }
            }
        } else {
            // For regular products: Unlink excess SubProducts from invoice and unbook them
            // Since regular products are booked individually, we need to unlink the excess count
            // IMPORTANT: Don't unlink the first SubProduct as it's used in the invoice product line
            $excessCount = (int)$excessQty;
            if ($excessCount > 0 && $bookedSubProducts->count() > 1) {
                // Skip the first one (used in invoice product) and take excess from the rest
                $reversedSubProducts = $bookedSubProducts->reverse()->skip(1)->take($excessCount);
                
                foreach ($reversedSubProducts as $subProduct) {
                    // Refresh to get latest state
                    $subProduct->refresh();
                    
                    // Unlink from invoice and unbook this SubProduct
                    $subProduct->invoice_id = null;
                    $subProduct->booked = 0;
                    $subProduct->sale_order_id = null;
                    $subProduct->so_qty_reserved = null;
                    $subProduct->save();
                    
                    // Create stock movement record (for regular products, quantity wasn't reduced, so qty_in = 0)
                    // But we still record the unbooking activity
                    $stockMovement = new StockMovement();
                    $stockMovement->product_id = $product->id;
                    $stockMovement->sub_product_id = $subProduct->id;
                    $stockMovement->invoice_id = null;
                    $stockMovement->bill_id = null;
                    $stockMovement->pos_id = null;
                    $stockMovement->qty_in = 0; // Regular products don't have quantity to return
                    $stockMovement->qty_out = 0;
                    $stockMovement->avg_cost = $product->avg_cost ?? ($subProduct->purchase_price ?? 0);
                    $stockMovement->cost_price = $subProduct->purchase_price ?? 0;
                    $stockMovement->activity = 'Return from Sale Order (Excess Quantity - Unbooked)';
                    $stockMovement->use_id = $saleOrder->customer_id;
                    $stockMovement->item = $subProduct->id;
                    $stockMovement->created_by = $creatorId;
                    $stockMovement->save();
                }
            }
            
            // Note: The invoice product line uses packed_qty, which is independent of how many SubProducts are linked
            // So unlinking excess SubProducts is correct - only the packed quantity should be invoiced
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!\Auth::user()->can('delete sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $saleOrderId = \Crypt::decrypt($id);
            $saleOrder = SaleOrder::where('id', $saleOrderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['items', 'pickList', 'invoice'])
                ->firstOrFail();

            // Prevent deletion if already converted to invoice
            if ($saleOrder->isConverted()) {
                return redirect()->back()->with('error', __('Cannot delete sale order that has been converted to invoice.'));
            }

            DB::beginTransaction();
            try {
                $creatorId = \Auth::user()->creatorId();

                // 1. Unbook all sub-products linked to this sale order
                // Group by part_no to handle Qty products correctly
                $itemsByPartNo = [];
                foreach ($saleOrder->items as $item) {
                    if (!empty($item->part_no)) {
                        $partNoKey = strtoupper(trim($item->part_no));
                        if (!isset($itemsByPartNo[$partNoKey])) {
                            $itemsByPartNo[$partNoKey] = [];
                        }
                        $itemsByPartNo[$partNoKey][] = $item;
                    }
                }

                // Process each part_no group: return reserved/picked quantity to stock (even with pick/pack lists), then unbook
                foreach ($itemsByPartNo as $partNo => $items) {
                    $totalStockQtyToReturn = array_sum(array_map(function ($item) {
                        return (float)($item->stock_qty ?? 0);
                    }, $items));

                    // Get all booked sub-products for this part_no and sale order (FIFO order for correct return distribution)
                    $idsForPartNo = $this->getSaleOrderSubProductIdsFromItems((int)$saleOrder->id, (string)$partNo);
                    $bookedSubProducts = $idsForPartNo->isEmpty()
                        ? collect()
                        : SubProduct::whereIn('id', $idsForPartNo)
                            ->where('created_by', $creatorId)
                            ->orderBy('created_at', 'ASC')
                            ->get();

                    $product = null;
                    $isQtyProduct = false;
                    if ($bookedSubProducts->isNotEmpty()) {
                        $firstSubProduct = $bookedSubProducts->first();
                        $product = ProductService::find($firstSubProduct->product_id);
                        $isQtyProduct = $product && $product->category && $product->category->type === 'Qty product';
                    } else {
                        // No booked sub-products (e.g. pick/pack list updated stock_qty only): get product from SO item to return qty to stock
                        $firstItem = $items[0];
                        $product = $firstItem->product_id ? ProductService::find($firstItem->product_id) : null;
                        if (!$product && !empty($firstItem->part_no)) {
                            $anySub = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($partNo))])
                                ->where('created_by', $creatorId)->first();
                            $product = $anySub ? ProductService::find($anySub->product_id) : null;
                        }
                        $isQtyProduct = $product && $product->category && $product->category->type === 'Qty product';
                    }

                    // Return reserved (stock_qty) back to sub-product quantity in stock.
                    // When deleting a sale order we must return the FULL reserved quantity,
                    // regardless of any packed_qty that may exist on individual SO lines.
                    if ($totalStockQtyToReturn > 0 && $product) {
                        // Force packed_qty = 0 for this operation so that returnExcessQuantityToStock
                        // does not subtract packed quantities when computing what to return.
                        $items[0]->packed_qty = 0;

                        if ($bookedSubProducts->isNotEmpty()) {
                            $this->returnExcessQuantityToStock(
                                $items[0],
                                $bookedSubProducts,
                                $totalStockQtyToReturn,
                                $isQtyProduct,
                                $product,
                                $saleOrder
                            );
                        } else {
                            // Fallback: no booked sub-products (e.g. SO had pick/pack only); return qty to any sub-product with this part_no
                            $anySubProducts = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($partNo))])
                                ->where('created_by', $creatorId)
                                ->orderBy('created_at', 'ASC')
                                ->get();
                            if ($anySubProducts->isNotEmpty()) {
                                $this->returnExcessQuantityToStock(
                                    $items[0],
                                    $anySubProducts,
                                    $totalStockQtyToReturn,
                                    $isQtyProduct,
                                    $product,
                                    $saleOrder
                                );
                            } else if ($isQtyProduct) {
                                // Qty product but no sub-products at all: create stock movement for audit (qty has nowhere to go)
                                $firstSub = SubProduct::where('product_id', $product->id)
                                    ->where('created_by', $creatorId)->orderBy('created_at', 'ASC')->first();
                                if ($firstSub) {
                                    $firstSub->quantity = (float)$firstSub->quantity + $totalStockQtyToReturn;
                                    $firstSub->save();
                                    $sm = new StockMovement();
                                    $sm->product_id = $product->id;
                                    $sm->sub_product_id = $firstSub->id;
                                    $sm->invoice_id = null;
                                    $sm->bill_id = null;
                                    $sm->pos_id = null;
                                    $sm->qty_in = $totalStockQtyToReturn;
                                    $sm->qty_out = 0;
                                    $sm->avg_cost = $product->avg_cost ?? ($firstSub->purchase_price ?? 0);
                                    $sm->cost_price = $firstSub->purchase_price ?? 0;
                                    $sm->activity = 'Return from Sale Order (Delete - No booked sub-products)';
                                    $sm->use_id = $saleOrder->customer_id;
                                    $sm->item = $firstSub->id;
                                    $sm->created_by = $creatorId;
                                    $sm->save();
                                }
                            }
                        }
                    }

                    // Unbook all sub-products for this part_no
                    foreach ($bookedSubProducts as $subProduct) {
                        $subProduct->booked = 0;
                        $subProduct->sale_order_id = null;
                        $subProduct->so_qty_reserved = null;
                        $subProduct->save();
                    }
                }

                // Also handle any remaining booked sub-products that might not match items
                $allSubProductIds = $this->getSaleOrderSubProductIdsFromItems((int)$saleOrder->id);
                if ($allSubProductIds->isNotEmpty()) {
                    SubProduct::whereIn('id', $allSubProductIds)
                        ->where('created_by', $creatorId)
                        ->update([
                            'booked' => 0,
                            'sale_order_id' => null,
                            'so_qty_reserved' => null
                        ]);
                }

                // 2. Delete all related packing lists (cascades to packing list items and box items)
                $packingLists = PackingList::where('sale_order_id', $saleOrder->id)->get();
                foreach ($packingLists as $packingList) {
                    // Delete packing list items
                    PackingListItem::where('packing_list_id', $packingList->id)->delete();
                    // Delete packing box items
                    PackingBoxItem::where('packing_list_id', $packingList->id)->delete();
                    // Delete packing list
                    $packingList->delete();
                }

                // 3. Delete all related pick lists (cascades to pick list items)
                $pickLists = PickList::where('sales_order_id', $saleOrder->id)->get();
                foreach ($pickLists as $pickList) {
                    // Delete pick list items
                    PickListItem::where('pick_list_id', $pickList->id)->delete();
                    // Delete pick list
                    $pickList->delete();
                }

                // 4. Delete sale order items
                $saleOrder->items()->delete();

                // 5. Delete the sale order
                $saleOrder->delete();

                DB::commit();

                return redirect()->route('saleorder.index')->with('success', __('Sale order deleted successfully. All booked sub-products have been unbooked and related pick/packing lists have been removed.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Sale order deletion failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->id,
                'sale_order_id' => $id,
            ]);

            return redirect()->back()->with('error', __('Failed to delete sale order: ') . $e->getMessage());
        }
    }
}
