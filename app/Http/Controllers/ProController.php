<?php

namespace App\Http\Controllers;

use App\Models\Pro;
use App\Models\ProItem;
use App\Models\AdvanceSaleOrder;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\Currency;
use App\Imports\ProImport;
use App\Imports\ProCreateSubProductImport;
use App\Exports\ProExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProController extends Controller
{
    /**
     * Generate PRO number
     */
    private function proNumber()
    {
        $creatorId = \Auth::user()->creatorId();

        $latest = Pro::withTrashed()
            ->where('created_by', $creatorId)
            ->orderByRaw('CAST(pro_no AS UNSIGNED) DESC')
            ->first();

        if (!$latest || !is_numeric($latest->pro_no)) {
            return 1;
        }

        return (int)$latest->pro_no + 1;
    }

    /**
     * Get description from stock (SubProduct) by matching part_no to product_no
     */
    private function getDescriptionFromStock($partNo, $creatorId)
    {
        if (empty(trim($partNo))) {
            \Log::info('PRO getDescriptionFromStock: empty part no', [
                'creator_id' => (int) $creatorId,
            ]);
            return null;
        }

        $partNoTrimmed = trim((string) $partNo);
        $creatorIdInt = (int) $creatorId;

        $subProduct = $this->findImportedSubProductByPartNo($partNoTrimmed, $creatorIdInt);

        \Log::info('PRO getDescriptionFromStock: lookup result', [
            'creator_id' => $creatorIdInt,
            'part_no' => $partNoTrimmed,
            'matched' => (bool) $subProduct,
            'sub_product_id' => $subProduct->id ?? null,
            'sub_product_product_id' => $subProduct->product_id ?? null,
            'sub_product_product_no' => $subProduct->chassis_no ?? null,
            'sub_product_import_source' => $subProduct->import_source ?? null,
            'parent_product_sku' => $subProduct?->productService?->sku,
            'parent_product_name' => $subProduct?->productService?->name,
        ]);

        if ($subProduct && $subProduct->productService) {
            \Log::info('PRO getDescriptionFromStock: returning description', [
                'creator_id' => $creatorIdInt,
                'part_no' => $partNoTrimmed,
                'sub_product_id' => $subProduct->id,
                'description' => $subProduct->productService->name,
            ]);
            return $subProduct->productService->name;
        }

        \Log::info('PRO getDescriptionFromStock: no description found', [
            'creator_id' => $creatorIdInt,
            'part_no' => $partNoTrimmed,
        ]);
        return null;
    }

    /**
     * Find Item Master sub-product (import_source=item_master) by part number.
     * Used to reliably link PRO items to the correct parent product.
     */
    private function findImportedSubProductByPartNo(?string $partNo, int $creatorId): ?SubProduct
    {
        $partNo = trim((string) $partNo);
        if ($partNo === '') {
            return null;
        }

        // Prefer a SubProduct whose parent ProductService sku matches the same part no.
        // This resolves duplicates where same product_no exists under different product parents.
        $preferred = SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', $partNo)
            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
            ->whereHas('productService', function ($q) use ($partNo, $creatorId) {
                $q->where('created_by', $creatorId)->where('sku', $partNo);
            })
            ->with('productService')
            ->latest()
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', $partNo)
            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
            ->with('productService')
            ->latest()
            ->first();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            $suppliers = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            $query = Pro::where('created_by', '=', \Auth::user()->creatorId())->with('supplier', 'items', 'advanceSaleOrder');

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', '=', $request->supplier_id);
            }

            if ($request->filled('po_date')) {
                $query->where('po_date', '=', $request->po_date);
            }

            if ($request->filled('pro_no')) {
                $query->where('pro_no', 'like', '%' . $request->pro_no . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', '=', $request->status);
            }

            $pros = $query->orderBy('id', 'desc')->get();

            return view('pro.index', compact('pros', 'suppliers'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (\Auth::user()->can('create bill')) {
            $pro_number = $this->proNumber();
            $pro_number_formatted = \Auth::user()->proNumberFormat($pro_number);
            $suppliers = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            $products = ProductService::where('created_by', \Auth::user()->creatorId())
                ->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productCode = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                    ];
                })
                ->pluck('name', 'id');
            $products->prepend('Select Product', '');

            $currencies = \App\Models\Currency::get()->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');

            return view('pro.create', compact('pro_number', 'pro_number_formatted', 'suppliers', 'products', 'currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $validated = $request->validate([
                'supplier_id' => 'nullable|exists:venders,id',
                'supplier_name' => 'nullable|string|max:255',
                // 'supplier_code' => 'nullable|string|max:255',
                'po_date' => 'required|date',
                'supplier_proforma_no' => 'nullable|string|max:255',
                'supplier_proforma_date' => 'nullable|date',
                'our_order_ref' => 'nullable|string|max:255',
                'supplier_ref' => 'nullable|string|max:255',
                'eta_date' => 'nullable|date',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:open,delivered',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:product_services,id',
                'items.*.sub_product_id' => 'nullable|exists:sub_products,id',
                'items.*.part_no' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.order_qty' => 'required|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            // Validate that vendor exists if supplier_name is provided
            $supplierId = $validated['supplier_id'] ?? null;
            $supplierName = $validated['supplier_name'] ?? null;
            
            // If supplier_name is provided but supplier_id is not, check if vendor exists
            if (!empty($supplierName) && empty($supplierId)) {
                $existingVendor = Vender::where('created_by', \Auth::user()->creatorId())
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($supplierName))])
                    ->first();
                
                if (!$existingVendor) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Vendor does not exist. Please select an existing vendor from the dropdown or create the vendor first.'));
                }
                
                // Use the found vendor's ID
                $supplierId = $existingVendor->id;
            }
            
            // Require at least supplier_id or supplier_name
            if (empty($supplierId) && empty($supplierName)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Please select a supplier.'));
            }

            // Create PRO
            $pro = new Pro();
            $pro->pro_no = $this->proNumber();
            $pro->supplier_id = $supplierId;
            $pro->supplier_name = $supplierName;
            // $pro->supplier_code = $validated['supplier_code'] ?? null;
            $pro->po_date = $validated['po_date'];
            $pro->supplier_proforma_no = $validated['supplier_proforma_no'] ?? null;
            $pro->supplier_proforma_date = $validated['supplier_proforma_date'] ?? null;
            $pro->our_order_ref = $validated['our_order_ref'] ?? null;
            $pro->supplier_ref = $validated['supplier_ref'] ?? null;
            $pro->eta_date = $validated['eta_date'] ?? null;
            $pro->currency_id = $validated['currency_id'] ?? null;
            $pro->exchange_rate = $validated['exchange_rate'] ?? 1.0;
            $pro->status = $validated['status'] ?? 'open';
            $pro->created_by = \Auth::user()->creatorId();
            $pro->save();

            // Create PRO Items
            foreach ($validated['items'] as $itemData) {
                $item = new ProItem();
                $item->pro_id = $pro->id;
                $item->product_id = null;
                $item->part_no = $itemData['part_no'] ?? null;

                // PRO flow: resolve parent product_id from part_no only; do not save sub_product_id yet.
                if (!empty(trim((string) $item->part_no))) {
                    $matchedSubProduct = $this->findImportedSubProductByPartNo($item->part_no, \Auth::user()->creatorId());
                    if ($matchedSubProduct) {
                        $item->product_id = $matchedSubProduct->product_id;
                    }
                }
                
                // Auto-fill description from stock if empty
                $description = $itemData['description'] ?? null;
                if (empty(trim($description ?? '')) && !empty($item->part_no)) {
                    $description = $this->getDescriptionFromStock($item->part_no, \Auth::user()->creatorId());
                }
                $item->description = $description;
                
                $item->order_qty = $itemData['order_qty'];
                $item->supplied_qty = 0;
                $item->unit_price = $itemData['unit_price'];
                // remaining_qty and total_amount will be auto-calculated by model
                $item->save();
            }

            return redirect()->route('pro.index')->with('success', __('PRO created successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pro $pro)
    {
        if (\Auth::user()->can('manage bill')) {
            $pro->load(['items.product', 'supplier', 'creator', 'currency', 'advanceSaleOrder.customer']);
            
            // Load products for each item based on description matching product name
            $creatorId = \Auth::user()->creatorId();
            foreach ($pro->items as $item) {
                if (!empty($item->description)) {
                    $product = ProductService::where('created_by', $creatorId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string)$item->description))])
                        ->with(['category', 'brand', 'subBrand'])
                        ->first();
                    
                    // If exact match not found, try partial match
                    if (!$product) {
                        $product = ProductService::where('created_by', $creatorId)
                            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower(trim((string)$item->description)) . '%'])
                            ->with(['category', 'brand', 'subBrand'])
                            ->first();
                    }
                    
                    // Attach product to item for use in view
                    $item->matchedProduct = $product;
                }
            }
            
            return view('pro.show', compact('pro'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pro $pro)
    {
        if (\Auth::user()->can('edit bill')) {
            $pro->load('items');
            $suppliers = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            $products = ProductService::where('created_by', \Auth::user()->creatorId())
                ->with(['brand', 'subBrand', 'category'])
                ->get()
                ->map(function ($productService) {
                    $category = $productService->category->name ?? '';
                    $brand = $productService->brand->name ?? '';
                    $subBrand = $productService->subBrand->name ?? '';
                    $productName = $productService->name;
                    $productCode = $productService->sku;

                    return [
                        'id' => $productService->id,
                        'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productCode,
                    ];
                })
                ->pluck('name', 'id');
            $products->prepend('Select Product', '');

            $currencies = \App\Models\Currency::get()->pluck('name', 'id');
            $currencies->prepend('Select Currency', '');

            return view('pro.edit', compact('pro', 'suppliers', 'products', 'currencies'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pro $pro)
    {
        if (\Auth::user()->can('edit bill')) {
            $validated = $request->validate([
                'supplier_id' => 'nullable|exists:venders,id',
                'supplier_name' => 'nullable|string|max:255',
                // 'supplier_code' => 'nullable|string|max:255',
                'po_date' => 'required|date',
                'supplier_proforma_no' => 'nullable|string|max:255',
                'supplier_proforma_date' => 'nullable|date',
                'our_order_ref' => 'nullable|string|max:255',
                'supplier_ref' => 'nullable|string|max:255',
                'eta_date' => 'nullable|date',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:open,delivered',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:product_services,id',
                'items.*.part_no' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.order_qty' => 'required|numeric|min:0',
                'items.*.supplied_qty' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            // Validate that vendor exists if supplier_name is provided
            $supplierId = $validated['supplier_id'] ?? null;
            $supplierName = $validated['supplier_name'] ?? null;
            
            // If supplier_name is provided but supplier_id is not, check if vendor exists
            if (!empty($supplierName) && empty($supplierId)) {
                $existingVendor = Vender::where('created_by', \Auth::user()->creatorId())
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($supplierName))])
                    ->first();
                
                if (!$existingVendor) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Vendor does not exist. Please select an existing vendor from the dropdown or create the vendor first.'));
                }
                
                // Use the found vendor's ID
                $supplierId = $existingVendor->id;
            }
            
            // Require at least supplier_id or supplier_name
            if (empty($supplierId) && empty($supplierName)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Please select a supplier.'));
            }

            // Update PRO
            $pro->supplier_id = $supplierId;
            $pro->supplier_name = $supplierName;
            // $pro->supplier_code = $validated['supplier_code'] ?? null;
            $pro->po_date = $validated['po_date'];
            $pro->supplier_proforma_no = $validated['supplier_proforma_no'] ?? null;
            $pro->supplier_proforma_date = $validated['supplier_proforma_date'] ?? null;
            $pro->our_order_ref = $validated['our_order_ref'] ?? null;
            $pro->supplier_ref = $validated['supplier_ref'] ?? null;
            $pro->eta_date = $validated['eta_date'] ?? null;
            $pro->currency_id = $validated['currency_id'] ?? null;
            $pro->exchange_rate = $validated['exchange_rate'] ?? $pro->exchange_rate ?? 1.0;
            $pro->status = $validated['status'] ?? $pro->status ?? 'open';
            $pro->save();

            // Delete existing items
            $pro->items()->delete();

            // Create updated PRO Items
            foreach ($validated['items'] as $itemData) {
                $item = new ProItem();
                $item->pro_id = $pro->id;
                $item->product_id = null;
                $item->part_no = $itemData['part_no'] ?? null;

                // PRO flow: resolve parent product_id from part_no only; do not save sub_product_id yet.
                if (!empty(trim((string) $item->part_no))) {
                    $matchedSubProduct = $this->findImportedSubProductByPartNo($item->part_no, \Auth::user()->creatorId());
                    if ($matchedSubProduct) {
                        $item->product_id = $matchedSubProduct->product_id;
                    }
                }
                
                // Auto-fill description from stock if empty
                $description = $itemData['description'] ?? null;
                if (empty(trim($description ?? '')) && !empty($item->part_no)) {
                    $description = $this->getDescriptionFromStock($item->part_no, \Auth::user()->creatorId());
                }
                $item->description = $description;
                
                $item->order_qty = $itemData['order_qty'];
                $item->supplied_qty = $itemData['supplied_qty'] ?? 0;
                $item->unit_price = $itemData['unit_price'];
                // remaining_qty and total_amount will be auto-calculated by model
                $item->save();
            }

            return redirect()->route('pro.index')->with('success', __('PRO updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pro $pro)
    {
        if (\Auth::user()->can('delete bill')) {
            $pro->delete();
            return redirect()->route('pro.index')->with('success', __('PRO deleted successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the import file form
     */
    public function importFile()
    {
        if (\Auth::user()->can('create bill')) {
            return view('pro.import');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show import form for PRO with "create missing stock" option (creates SubProduct if part_no not in stock).
     */
    public function importFileCreateSubProducts()
    {
        if (\Auth::user()->can('create bill')) {
            return view('pro.import_create_sub');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show import form for PRO items-only import.
     * Header fields are entered in the form; file contains only item rows.
     */
    public function importFileItemsOnly()
    {
        if (\Auth::user()->can('create bill')) {
            $creatorId = \Auth::user()->creatorId();
            $suppliers = Vender::where('created_by', \Auth::user()->creatorId())
                ->orderBy('name')
                ->pluck('name', 'id');

            $currencies = Currency::select('id', 'name', 'exchange_rate')
                ->orderBy('name')
                ->get();

            $advanceSaleOrders = AdvanceSaleOrder::where('created_by', $creatorId)
                ->with('customer')
                ->orderByDesc('id')
                ->get();

            return view('pro.import_items_only', compact('suppliers', 'currencies', 'advanceSaleOrders'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Import PRO from Excel file (creates SubProduct when part_no does not exist in stock).
     */
    public function importCreateSubProducts(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx,xls,csv|max:51200',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route('pro.index')
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', __('Import failed. Please upload a valid Excel/CSV file.'));
            }

            try {
                @ini_set('max_execution_time', '7200');
                @ini_set('memory_limit', '2048M');
                set_time_limit(7200);

                Excel::import(new ProCreateSubProductImport(\Auth::user()->creatorId()), $request->file('file'));
                return redirect()->route('pro.index')->with('success', __('PRO imported successfully. New sub-products were created for part numbers not in stock.'));
            } catch (\Exception $e) {
                \Log::error('PRO import (create stock) failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => \Auth::user()->creatorId()
                ]);
                return redirect()->route('pro.index')->with('error', __('Import failed: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Import PRO from Excel file
     */
    public function import(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $request->validate([
                'file' => 'required|mimes:xlsx,csv',
            ]);

            try {
                Excel::import(new ProImport(\Auth::user()->creatorId()), $request->file('file'));
                
                return back()->with('success', __('PRO imported successfully!'));
            } catch (\Exception $e) {
                \Log::error('PRO import failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => \Auth::user()->creatorId()
                ]);
                
                return back()->with('error', __('Import failed: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Import PRO from file that contains only item columns.
     * Header fields are taken from request form.
     */
    public function importItemsOnly(Request $request)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:venders,id',
            'advance_sale_order_id' => 'nullable|exists:advance_sale_orders,id',
            'supplier_proforma_no' => 'nullable|string|max:255',
            'supplier_proforma_date' => 'nullable|date',
            'our_order_ref' => 'nullable|string|max:255',
            'supplier_ref' => 'nullable|string|max:255',
            'eta_date' => 'nullable|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            $creatorId = \Auth::user()->creatorId();
            $supplier = Vender::where('id', $validated['supplier_id'])
                ->where('created_by', $creatorId)
                ->first();

            if (!$supplier) {
                return back()->with('error', __('Selected supplier is invalid for this company.'));
            }

            $selectedAdvanceSaleOrder = null;
            if (!empty($validated['advance_sale_order_id'])) {
                $selectedAdvanceSaleOrder = AdvanceSaleOrder::where('id', $validated['advance_sale_order_id'])
                    ->where('created_by', $creatorId)
                    ->with('items')
                    ->first();

                if (!$selectedAdvanceSaleOrder) {
                    return back()->with('error', __('Selected advance sale order is invalid for this company.'));
                }
            }

            $sheets = Excel::toArray([], $request->file('file'));
            $data = $sheets[0] ?? [];

            if (empty($data)) {
                throw new \Exception('Import file is empty.');
            }

            $headerRowIndex = null;
            $columnMap = [];

            // Locate item header row by PART NO.
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
                    } elseif (in_array($normalized, ['ORDER QTY', 'ORDER QUANTITY', 'ORDERQTY'])) {
                        $candidateMap['order_qty'] = $colIndex;
                    } elseif (in_array($normalized, ['SUPPLIED QTY', 'SUPPLIED QUANTITY', 'SUPPLIEDQTY'])) {
                        $candidateMap['supplied_qty'] = $colIndex;
                    } elseif (in_array($normalized, ['REMAINING QTY', 'REMAINING QUANTITY', 'REMAININGQTY'])) {
                        $candidateMap['remaining_qty'] = $colIndex;
                    } elseif (in_array($normalized, ['UNIT PRICE', 'PRICE', 'UNITPRICE'])) {
                        $candidateMap['unit_price'] = $colIndex;
                    }
                }

                if (isset($candidateMap['part_no']) && isset($candidateMap['order_qty']) && isset($candidateMap['unit_price'])) {
                    $headerRowIndex = $i;
                    $columnMap = $candidateMap;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('Could not find item header row. Required headers: PART NO, ORDER QTY, UNIT PRICE.');
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

                $orderQty = (float)($row[$columnMap['order_qty']] ?? 0);
                $unitPrice = (float)($row[$columnMap['unit_price']] ?? 0);
                $suppliedQty = isset($columnMap['supplied_qty']) ? (float)($row[$columnMap['supplied_qty']] ?? 0) : 0;
                $description = isset($columnMap['description']) ? trim((string)($row[$columnMap['description']] ?? '')) : '';

                if ($orderQty < 0 || $unitPrice < 0 || $suppliedQty < 0) {
                    throw new \Exception("Negative values are not allowed in row {$excelRowNo}.");
                }

                $preparedItems[] = [
                    'part_no' => $partNo,
                    'description' => $description,
                    'order_qty' => $orderQty,
                    'supplied_qty' => $suppliedQty,
                    'unit_price' => $unitPrice,
                ];

                $excelRowNo++;
            }

            if (empty($preparedItems)) {
                throw new \Exception('No valid item rows found in the uploaded file.');
            }

            if ($selectedAdvanceSaleOrder) {
                $poQtyByPart = [];
                foreach ($preparedItems as $itemData) {
                    $partKey = strtoupper(trim((string) ($itemData['part_no'] ?? '')));
                    if ($partKey === '') {
                        continue;
                    }
                    $poQtyByPart[$partKey] = ($poQtyByPart[$partKey] ?? 0) + (float) ($itemData['order_qty'] ?? 0);
                }

                $asoQtyByPart = [];
                foreach ($selectedAdvanceSaleOrder->items as $asoItem) {
                    $partKey = strtoupper(trim((string) ($asoItem->part_no ?? '')));
                    if ($partKey === '') {
                        continue;
                    }
                    $asoQtyByPart[$partKey] = ($asoQtyByPart[$partKey] ?? 0) + (float) ($asoItem->req_qty ?? 0);
                }

                $missingInPo = [];
                foreach ($asoQtyByPart as $partNo => $qty) {
                    if (!array_key_exists($partNo, $poQtyByPart)) {
                        $missingInPo[] = $partNo;
                    }
                }

                $extraInPo = [];
                foreach ($poQtyByPart as $partNo => $qty) {
                    if (!array_key_exists($partNo, $asoQtyByPart)) {
                        $extraInPo[] = $partNo;
                    }
                }

                $qtyMismatches = [];
                foreach ($asoQtyByPart as $partNo => $asoQty) {
                    if (!array_key_exists($partNo, $poQtyByPart)) {
                        continue;
                    }
                    $poQty = (float) $poQtyByPart[$partNo];
                    if (abs($poQty - (float) $asoQty) > 0.0001) {
                        $qtyMismatches[] = "{$partNo} (Advance SO: {$asoQty}, PO: {$poQty})";
                    }
                }

                if (!empty($missingInPo) || !empty($extraInPo) || !empty($qtyMismatches)) {
                    $messages = [];
                    if (!empty($missingInPo)) {
                        $messages[] = 'Missing parts from PO import: ' . implode(', ', $missingInPo);
                    }
                    if (!empty($extraInPo)) {
                        $messages[] = 'Extra parts in PO import not found in selected Advance SO: ' . implode(', ', $extraInPo);
                    }
                    if (!empty($qtyMismatches)) {
                        $messages[] = 'Quantity mismatch by part no: ' . implode('; ', $qtyMismatches);
                    }

                    return back()->withInput()->with('error', __('Advance SO validation failed. ') . implode(' | ', $messages));
                }
            }

            DB::beginTransaction();
            try {
                $pro = new Pro();
                $pro->pro_no = $this->proNumber();
                $pro->advance_sale_order_id = $selectedAdvanceSaleOrder?->id;
                $pro->supplier_id = $supplier->id;
                $pro->supplier_name = $supplier->name;
                $pro->supplier_code = $supplier->supplier_code ?? null;
                $pro->po_date = date('Y-m-d');
                $pro->supplier_proforma_no = $validated['supplier_proforma_no'] ?? null;
                $pro->supplier_proforma_date = $validated['supplier_proforma_date'] ?? null;
                $pro->our_order_ref = $validated['our_order_ref'] ?? null;
                $pro->supplier_ref = $validated['supplier_ref'] ?? null;
                $pro->eta_date = $validated['eta_date'] ?? null;
                $pro->currency_id = $validated['currency_id'] ?? null;
                $pro->exchange_rate = $validated['exchange_rate'] ?? 1;
                $pro->status = 'open';
                $pro->created_by = $creatorId;
                $pro->save();

                foreach ($preparedItems as $itemData) {
                    $item = new ProItem();
                    $item->pro_id = $pro->id;
                    $item->product_id = null;
                    $item->part_no = $itemData['part_no'];

                    if (!empty($itemData['part_no'])) {
                        $matchedSubProduct = $this->findImportedSubProductByPartNo($itemData['part_no'], $creatorId);
                        if ($matchedSubProduct) {
                            $item->product_id = $matchedSubProduct->product_id;
                        }
                    }

                    $description = $itemData['description'];
                    if (empty(trim($description ?? '')) && !empty($item->part_no)) {
                        $description = $this->getDescriptionFromStock($item->part_no, $creatorId);
                    }

                    $item->description = $description;
                    $item->order_qty = $itemData['order_qty'];
                    $item->supplied_qty = $itemData['supplied_qty'];
                    $item->unit_price = $itemData['unit_price'];
                    $item->save();
                }

                $pro->updateStatusBasedOnItems();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return back()->with('success', __('PRO imported successfully (items-only format).'));
        } catch (\Exception $e) {
            \Log::error('PRO items-only import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId(),
            ]);

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    /**
     * Download sample Excel file for PRO import
     */
    public function downloadSample()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);

            // Row 1: Title
            $sheet->mergeCells('D1:E1');
            $sheet->setCellValue('D1', 'Purchase Order');
            $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row 2: Supplier Name and PRO No
            $sheet->setCellValue('A2', 'SUPPLIER NAME');
            $sheet->setCellValue('B2', 'Sample Supplier Ltd');
            // $sheet->setCellValue('F2', 'PRO NO');
            // $sheet->setCellValue('G2', '#PRO00001');

            // Row 3: Supplier Proforma No and PO Date
            $sheet->setCellValue('A3', 'SUPPLIER PROFORMA NO');
            $sheet->setCellValue('B3', 'PF-2025-001');
            // $sheet->setCellValue('F3', 'PO DATE');
            // $sheet->setCellValue('G3', date('Y-m-d'));

            // Row 4: Supplier Proforma Date
            $sheet->setCellValue('A4', 'SUPPLIER PROFORMA DATE');
            $sheet->setCellValue('B4', date('Y-m-d'));

            // Row 5: Our Order Ref
            $sheet->setCellValue('A5', 'OUR ORDER REF');
            $sheet->setCellValue('B5', 'ORD-2025-001');

            // Row 6: Supplier Ref
            $sheet->setCellValue('A6', 'SUPPLIER REF');
            $sheet->setCellValue('B6', 'SUP-REF-001');

            // Row 7: ETA Date
            $sheet->setCellValue('A7', 'ETA DATE');
            $sheet->setCellValue('B7', date('Y-m-d', strtotime('+30 days')));

            // Row 8: Currency ID
            $sheet->setCellValue('A8', 'CURRENCY ID');
            $sheet->setCellValue('B8', '1'); // Default currency ID (can be changed)

            // Row 9: Exchange Rate
            $sheet->setCellValue('A9', 'EXCHANGE RATE');
            $sheet->setCellValue('B9', '1.0'); // Default exchange rate

            // Row 10: Empty row for spacing
            $sheet->setCellValue('A10', '');

            // Row 11: Column Headers
            $headers = ['PART NO', 'DESCRIPTION', 'ORDER QTY', 'SUPPLIED QTY', 'REMAINING QTY', 'UNIT PRICE', 'TOTAL AMOUNT'];
            $headerColumn = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($headerColumn . '11', $header);
                $sheet->getStyle($headerColumn . '11')->getFont()->setBold(true);
                $sheet->getStyle($headerColumn . '11')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D3D3D3');
                $sheet->getStyle($headerColumn . '11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerColumn++;
            }

            // Sample data rows (Row 12-14)
            $sampleData = [
                ['04465-60280', 'FRONT BRAKE PAD', 100, 70, 30, 200.00, 20000.00],
                ['04465-60380', 'FRONT BRAKE PAD', 100, 0, 100, 190.00, 19000.00],
                ['04466-60160', 'REAR BRAKE PAD', 80, 0, 80, 175.00, 14000.00],
            ];

            $row = 12;
            foreach ($sampleData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    if (in_array($col, ['F', 'G'])) {
                        // Format as number for price columns
                        $sheet->setCellValue($col . $row, $value);
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        $sheet->setCellValue($col . $row, $value);
                    }
                    $col++;
                }
                $row++;
            }

            // Style all header labels (left column)
            $sheet->getStyle('A2:A9')->getFont()->setBold(true);
            $sheet->getStyle('F2:F3')->getFont()->setBold(true);

            // Add borders to item table
            $sheet->getStyle('A11:G' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Create temporary file
            $filename = 'sample-pro-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            // Download the file
            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error generating PRO sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Download sample file for PRO items-only import.
     * File contains only item columns.
     */
    public function downloadSampleItemsOnly()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);

            $headers = ['PART NO', 'DESCRIPTION', 'ORDER QTY', 'UNIT PRICE'];
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
                foreach ($dataRow as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    if (in_array($col, ['C', 'D'])) {
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

            $filename = 'sample-pro-items-only-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error generating PRO items-only sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Download sample Excel for "Import & Create Sub-Products" (parts not in stock).
     * Includes custom field columns so users can fill data to create new parts on import.
     */
    public function downloadSampleCreateSub()
    {
        try {
            $creatorId = \Auth::user()->creatorId();
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // PRO header (same as standard sample)
            $sheet->mergeCells('D1:E1');
            $sheet->setCellValue('D1', 'Purchase Order (Create new parts if not in stock)');
            $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'SUPPLIER NAME');
            $sheet->setCellValue('B2', 'Sample Supplier Ltd');
            $sheet->setCellValue('A3', 'SUPPLIER PROFORMA NO');
            $sheet->setCellValue('B3', 'PF-2025-001');
            $sheet->setCellValue('A4', 'SUPPLIER PROFORMA DATE');
            $sheet->setCellValue('B4', date('Y-m-d'));
            $sheet->setCellValue('A5', 'OUR ORDER REF');
            $sheet->setCellValue('B5', 'ORD-2025-001');
            $sheet->setCellValue('A6', 'SUPPLIER REF');
            $sheet->setCellValue('B6', 'SUP-REF-001');
            $sheet->setCellValue('A7', 'ETA DATE');
            $sheet->setCellValue('B7', date('Y-m-d', strtotime('+30 days')));
            $sheet->setCellValue('A8', 'CURRENCY ID');
            $sheet->setCellValue('B8', '1');
            $sheet->setCellValue('A9', 'EXCHANGE RATE');
            $sheet->setCellValue('B9', '1.0');
            $sheet->setCellValue('A10', '');

            // Item headers: SKU = product identifier (one product per SKU). No SUB_PRODUCT_SKU.
            $baseHeaders = ['PART NO', 'DESCRIPTION', 'SKU', 'ORDER QTY', 'SUPPLIED QTY', 'SALE PRICE', 'UNIT PRICE', 'NOTE', 'CATEGORY_ID', 'BRAND_NAME', 'SUB_BRAND_NAME'];
            $customFields = CustomField::where('module', 'sub-product')
                ->where('created_by', $creatorId)
                ->orderBy('id')
                ->get();
            $cfHeaders = $customFields->pluck('name')->map(function ($name) {
                return 'sub_product_' . preg_replace('/\s+/', '_', trim($name));
            })->values()->all();
            $headers = array_merge($baseHeaders, $cfHeaders);

            $numCols = count($headers);
            for ($colIndex = 0; $colIndex < $numCols; $colIndex++) {
                $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($col . '11', $headers[$colIndex]);
                $sheet->getStyle($col . '11')->getFont()->setBold(true);
                $sheet->getStyle($col . '11')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D3D3D3');
                $sheet->getStyle($col . '11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getColumnDimension($col)->setWidth(max(12, strlen($headers[$colIndex]) + 2));
            }

            // Sample rows: each SKU = one product. Part no = sub-product identifier.
            $sampleRow1 = array_merge(
                ['04465-60280', 'FRONT BRAKE PAD', 'SKU-001', 100, 0, 240.00, 200.00, '', '', '', ''],
                array_fill(0, count($cfHeaders), '')
            );
            $sampleRow2 = array_merge(
                ['NEW-PART-001', 'New Part Description (fill to create)', 'NEW-SKU-001', 50, 0, 180.00, 150.00, 'Optional note', '1', 'Brand Name', 'Sub-Brand Name'],
                array_fill(0, count($cfHeaders), '')
            );
            $sampleData = [$sampleRow1, $sampleRow2];

            $row = 12;
            foreach ($sampleData as $data) {
                for ($i = 0; $i < count($data); $i++) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $val = $data[$i] ?? '';
                    if (($i === 5 || $i === 6) && is_numeric($val)) {
                        $sheet->setCellValue($col . $row, $val);
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        $sheet->setCellValue($col . $row, $val);
                    }
                }
                $row++;
            }

            $lastCol = Coordinate::stringFromColumnIndex($numCols);
            $sheet->getStyle('A2:A9')->getFont()->setBold(true);
            $lastDataRow = $row - 1;
            $range = 'A11:' . $lastCol . $lastDataRow;
            $sheet->getStyle($range)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $filename = 'sample-pro-create-sub-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error generating PRO create-sub sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Export PROs to Excel
     */
    public function export(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            try {
                // Get filters from request
                $filters = [
                    'supplier_id' => $request->get('supplier_id'),
                    'po_date' => $request->get('po_date'),
                    'pro_no' => $request->get('pro_no'),
                    'status' => $request->get('status'),
                ];

                // Remove empty filters
                $filters = array_filter($filters);

                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

                $name = 'pro_' . date('Y-m-d_H-i-s');
                $data = Excel::download(new ProExport(\Auth::user()->creatorId(), $filters), $name . '.xlsx');

                return $data;
            } catch (\Exception $e) {
                \Log::error('PRO export failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => \Auth::user()->creatorId()
                ]);

                return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
