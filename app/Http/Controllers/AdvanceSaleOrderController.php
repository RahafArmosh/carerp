<?php

namespace App\Http\Controllers;

use App\Models\AdvanceSaleOrder;
use App\Models\AdvanceSaleOrderItem;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdvanceSaleOrderController extends Controller
{
    private function canViewAdvanceSaleOrder(): bool
    {
        return \Auth::user()->can('view advance sale order') || \Auth::user()->can('create advance sale order') || \Auth::user()->can('create sale order');
    }

    private function canCreateAdvanceSaleOrder(): bool
    {
        return \Auth::user()->can('create advance sale order') || \Auth::user()->can('create sale order');
    }

    private function canEditAdvanceSaleOrder(): bool
    {
        return \Auth::user()->can('edit advance sale order') || \Auth::user()->can('create sale order');
    }

    private function canDeleteAdvanceSaleOrder(): bool
    {
        return \Auth::user()->can('delete advance sale order') || \Auth::user()->can('delete sale order');
    }

    private function getDefaultTax($creatorId)
    {
        $defaultTax = Tax::where('created_by', $creatorId)->where('rate', 5)->first();
        if ($defaultTax) {
            return (string) $defaultTax->id;
        }

        $firstTax = Tax::where('created_by', $creatorId)->first();
        return $firstTax ? (string) $firstTax->id : '';
    }

    public function index(Request $request)
    {
        if (!$this->canViewAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $customer = Customer::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
        $customer->prepend('All', '');

        $query = AdvanceSaleOrder::where('created_by', \Auth::user()->creatorId());

        if (!empty($request->customer)) {
            $query->where('customer_id', $request->customer);
        }
        if (!empty($request->sales_order_date)) {
            $dateRange = explode('to', $request->sales_order_date);
            if (count($dateRange) === 2) {
                $query->whereBetween('sales_order_date', [trim($dateRange[0]), trim($dateRange[1])]);
            }
        }
        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $advanceSaleOrders = $query->with(['customer', 'currency', 'items'])->orderBy('created_at', 'desc')->get();

        $statuses = [
            'draft' => __('CREATED'),
            'approved' => __('APPROVED'),
            'shipped' => __('SHIPPED'),
            'converted' => __('CONVERTED'),
        ];

        return view('advancesaleorder.index', compact('advanceSaleOrders', 'customer', 'statuses'));
    }

    public function importFile()
    {
        if (!$this->canCreateAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = \Auth::user()->creatorId();
        $customers = Customer::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        $currencies = Currency::select('id', 'name', 'exchange_rate')->orderBy('name')->get();
        $taxes = Tax::where('created_by', $creatorId)->orderBy('name')->get();

        return view('advancesaleorder.import', compact('customers', 'currencies', 'taxes'));
    }

    public function import(Request $request)
    {
        if (!$this->canCreateAdvanceSaleOrder()) {
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
                $partNo = trim((string) ($row[$columnMap['part_no']] ?? ''));
                if ($partNo === '') {
                    $excelRowNo++;
                    continue;
                }

                $reqQty = $this->parseNumeric($row[$columnMap['req_qty']] ?? 0, 0);
                $unitPrice = isset($columnMap['unit_price']) ? $this->parseNumeric($row[$columnMap['unit_price']] ?? 0, 0) : 0;
                $description = isset($columnMap['description']) ? trim((string) ($row[$columnMap['description']] ?? '')) : '';

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
                $latestOrder = AdvanceSaleOrder::where('created_by', $creatorId)->withTrashed()->latest()->first();
                $advanceSaleOrderNo = $latestOrder ? ((int) $latestOrder->advance_sale_order_no + 1) : 1;

                $currency = null;
                if (!empty($validated['currency_id'])) {
                    $currency = Currency::find($validated['currency_id']);
                }
                if (!$currency) {
                    $currency = Currency::where('code', 'AED')->first() ?: Currency::first();
                }

                $exchangeRate = $validated['exchange_rate'] ?? ($currency->exchange_rate ?? 1);
                $taxId = $validated['tax_id'] ?? $this->getDefaultTax($creatorId);

                $advanceSaleOrder = new AdvanceSaleOrder();
                $advanceSaleOrder->advance_sale_order_no = (string) $advanceSaleOrderNo;
                $advanceSaleOrder->customer_id = $customer->id;
                $advanceSaleOrder->customer_trn_no = $customer->tax_number ?? null;
                $advanceSaleOrder->sales_order_date = $validated['sales_order_date'] ?? date('Y-m-d');
                $advanceSaleOrder->currency_id = $currency ? $currency->id : null;
                $advanceSaleOrder->exchange_rate = $exchangeRate;
                $advanceSaleOrder->tax_id = $taxId;
                $advanceSaleOrder->status = 'draft';
                $advanceSaleOrder->created_by = $creatorId;
                $advanceSaleOrder->save();

                foreach ($preparedItems as $itemData) {
                    $item = new AdvanceSaleOrderItem();
                    $item->advance_sale_order_id = $advanceSaleOrder->id;
                    $item->part_no = $itemData['part_no'];
                    $item->description = $itemData['description'] ?: null;
                    $item->req_qty = $itemData['req_qty'];
                    $item->converted_qty = 0.0;
                    $item->unit_price = $itemData['unit_price'];
                    $item->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return redirect()->route('advance-saleorder.index')->with('success', __('Advance sale order imported successfully (items-only format).'));
        } catch (\Exception $e) {
            \Log::error('Advance sale order items-only import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::id(),
            ]);
            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    /**
     * Download sample file for advance sale order items-only import.
     */
    public function downloadSampleItemsOnly()
    {
        if (!$this->canCreateAdvanceSaleOrder()) {
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

            $filename = 'sample-advance-saleorder-items-only-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error generating advance sale order items-only sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    private function parseNumeric($value, $default = 0)
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = str_replace([',', '$', ' '], '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : $default;
    }

    public function show($id)
    {
        if (!$this->canViewAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $orderId = \Crypt::decrypt($id);
            $advanceSaleOrder = AdvanceSaleOrder::where('id', $orderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with([
                    'customer',
                    'currency',
                    'items',
                    'creator',
                    'saleOrders' => function ($query) {
                        $query->orderByDesc('id');
                    },
                ])
                ->firstOrFail();

            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();
            return view('advancesaleorder.show', compact('advanceSaleOrder', 'taxes'));
        } catch (\Exception $e) {
            return redirect()->route('advance-saleorder.index')->with('error', __('Advance sale order not found.'));
        }
    }

    public function edit($id)
    {
        if (!$this->canEditAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $orderId = \Crypt::decrypt($id);
            $advanceSaleOrder = AdvanceSaleOrder::where('id', $orderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'currency', 'items'])
                ->firstOrFail();

            $customers = Customer::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $currencies = Currency::pluck('name', 'id');
            $currencies->prepend('Select Currency', '');
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            if (empty($advanceSaleOrder->tax_id)) {
                $advanceSaleOrder->tax_id = $this->getDefaultTax(\Auth::user()->creatorId());
                $advanceSaleOrder->save();
            }

            return view('advancesaleorder.edit', compact('advanceSaleOrder', 'customers', 'currencies', 'taxes'));
        } catch (\Exception $e) {
            return redirect()->route('advance-saleorder.index')->with('error', __('Advance sale order not found.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (!$this->canEditAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $orderId = \Crypt::decrypt($id);
            $advanceSaleOrder = AdvanceSaleOrder::where('id', $orderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'sales_order_date' => 'required|date',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|array',
                'status' => 'required|in:draft,approved,shipped,converted',
                'items' => 'required|array|min:1',
                'items.*.part_no' => 'required|string',
                'items.*.description' => 'nullable|string',
                'items.*.req_qty' => 'required|numeric|min:0',
                'items.*.converted_qty' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();
            try {
                $advanceSaleOrder->customer_id = $validated['customer_id'];
                $advanceSaleOrder->sales_order_date = $validated['sales_order_date'];
                $advanceSaleOrder->currency_id = $validated['currency_id'] ?? null;
                $advanceSaleOrder->exchange_rate = $validated['exchange_rate'] ?? 1.0;
                $advanceSaleOrder->tax_id = !empty($validated['tax_id'])
                    ? implode(',', $validated['tax_id'])
                    : $this->getDefaultTax(\Auth::user()->creatorId());
                $advanceSaleOrder->status = $validated['status'];
                $advanceSaleOrder->save();

                $advanceSaleOrder->items()->delete();

                foreach ($validated['items'] as $itemData) {
                    $item = new AdvanceSaleOrderItem();
                    $item->advance_sale_order_id = $advanceSaleOrder->id;
                    $item->part_no = $itemData['part_no'];
                    $item->description = $itemData['description'] ?? null;
                    $item->req_qty = $itemData['req_qty'];
                    $item->converted_qty = $itemData['converted_qty'] ?? 0;
                    $item->unit_price = $itemData['unit_price'] ?? 0;
                    $item->save();
                }

                DB::commit();
                return redirect()->route('advance-saleorder.show', $id)->with('success', __('Advance sale order updated successfully.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update advance sale order: ') . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (!$this->canDeleteAdvanceSaleOrder()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $orderId = \Crypt::decrypt($id);
            $advanceSaleOrder = AdvanceSaleOrder::where('id', $orderId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $advanceSaleOrder->delete();
            return redirect()->route('advance-saleorder.index')->with('success', __('Advance sale order deleted successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to delete advance sale order: ') . $e->getMessage());
        }
    }
}
