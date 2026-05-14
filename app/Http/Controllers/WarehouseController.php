<?php

namespace App\Http\Controllers;

use App\Models\Utility;
use App\Models\warehouse;
use App\Models\WarehouseProduct;
use App\Models\Country;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\StockMovement;
use App\Models\PosLog;
use App\Models\Tax;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\ApplyStockCountImportJob;
use App\Models\WarehouseStockCountImport;
use App\Models\WarehouseStockCountImportLine;
use App\Services\StockCountImportSnapshotService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class WarehouseController extends Controller
{
    public function stockCountImportStatus(string $token)
    {
        $status = Cache::get('stock_count_import_status:' . $token, null);
        if (!$status) {
            return response()->json(['status' => 'unknown', 'message' => 'Not found'], 404);
        }
        return response()->json($status, 200);
    }

    /**
     * Used by the background job to apply counts without Auth session and to report progress.
     */
    public function applyStockCountFromImportedDataForJob(warehouse $warehouse, array $importedData, int $creatorId, string $statusKey): void
    {
        // Update status at start
        Cache::put($statusKey, ['status' => 'running', 'progress' => 0, 'message' => 'Starting...', 'updated_at' => now()->toDateTimeString()], now()->addHours(2));

        $groupQuantities = [];
        foreach ($importedData as $productNo => $data) {
            $productNo = is_string($productNo) ? trim($productNo) : (string) $productNo;
            if ($productNo === '') {
                continue;
            }
            $qty = is_array($data) ? ($data['quantity'] ?? 0) : (int) $data;
            $groupQuantities[$productNo] = (int) $qty;
        }
        if (empty($groupQuantities)) {
            Cache::put($statusKey, ['status' => 'done', 'progress' => 100, 'message' => 'No items', 'updated_at' => now()->toDateTimeString()], now()->addHours(2));
            return;
        }

        set_time_limit(1800);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $lossAccount = ChartOfAccount::where('name', 'LIKE', '%Loss on Count%')->where('created_by', $creatorId)->first();
        if (!$lossAccount) {
            throw new \RuntimeException('Please create a "Loss on Count" account in Chart of Accounts before performing stock count.');
        }
        $profitAccount = ChartOfAccount::where('name', 'LIKE', '%Profit on Count%')->where('created_by', $creatorId)->first();
        if (!$profitAccount) {
            throw new \RuntimeException('Please create a "Profit on Count" account in Chart of Accounts before performing stock count.');
        }

        $latestVoucher = GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
        $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

        $productNos = array_keys($groupQuantities);
        $subProductsByProductNo = [];
        $chunkSize = 500;
        $loaded = 0;
        foreach (array_chunk($productNos, $chunkSize) as $chunk) {
            $subs = SubProduct::where('warehouse_id', $warehouse->id)
                ->where('created_by', $creatorId)
                ->whereIn('chassis_no', $chunk)
                ->with(['productService', 'productService.category'])
                ->orderBy('id')
                ->get();
            foreach ($subs as $sub) {
                $pn = (string) $sub->product_no;
                if (!isset($subProductsByProductNo[$pn])) {
                    $subProductsByProductNo[$pn] = collect();
                }
                $subProductsByProductNo[$pn]->push($sub);
            }
            $loaded += count($chunk);
            $pct = (int) min(20, round(($loaded / max(1, count($productNos))) * 20));
            Cache::put($statusKey, ['status' => 'running', 'progress' => $pct, 'message' => 'Loading items...', 'updated_at' => now()->toDateTimeString()], now()->addHours(2));
        }

        DB::beginTransaction();
        try {
            $stockCountChanges = [];
            $i = 0;
            $total = count($groupQuantities);

            foreach ($groupQuantities as $productNo => $newTotal) {
                $i++;
                $subProducts = $subProductsByProductNo[(string) $productNo] ?? collect();
                if ($subProducts->isEmpty()) {
                    continue;
                }

                $currentTotal = $subProducts->sum('quantity');
                $diff = ((int) $newTotal) - $currentTotal;
                if ($diff == 0) {
                    continue;
                }

                $product = $subProducts->first()->productService;
                $productName = $product ? $product->name : 'N/A';
                $productId = $subProducts->first()->product_id;

                if ($diff > 0) {
                    $newest = $subProducts->sortByDesc('id')->first();
                    $oldNewestQty = (int) $newest->quantity;
                    $newest->quantity += $diff;
                    // If this sub product was zero and now increased, make it available again.
                    if ($oldNewestQty === 0 && (int) $newest->quantity > 0) {
                        $newest->booked = 0;
                    }
                    $newest->save();
                    $stockCountChanges[] = ['sub_product_id' => $newest->id, 'product_no' => (string) $productNo, 'product_id' => $productId, 'product_name' => $productName, 'old_quantity' => $currentTotal, 'new_quantity' => (int) $newTotal, 'difference' => $diff];
                    $this->applyStockCountProfitForCreator($warehouse, $newest, $diff, $newVoucherId, $profitAccount, $lossAccount, $creatorId);
                } else {
                    $toSubtract = (int) abs($diff);
                    foreach ($subProducts as $sub) {
                        if ($toSubtract <= 0) {
                            break;
                        }
                        $deduct = min($sub->quantity, $toSubtract);
                        if ($deduct <= 0) {
                            continue;
                        }
                        $sub->quantity -= $deduct;
                        $sub->save();
                        $toSubtract -= $deduct;
                        $stockCountChanges[] = ['sub_product_id' => $sub->id, 'product_no' => (string) $productNo, 'product_id' => $productId, 'product_name' => $productName, 'old_quantity' => $sub->quantity + $deduct, 'new_quantity' => $sub->quantity, 'difference' => -$deduct];
                        $this->applyStockCountLossForCreator($warehouse, $sub, $deduct, $newVoucherId, $profitAccount, $lossAccount, $creatorId);
                    }
                }

                if ($i % 100 === 0) {
                    $pct = 20 + (int) min(79, round(($i / max(1, $total)) * 79));
                    Cache::put($statusKey, ['status' => 'running', 'progress' => $pct, 'message' => 'Applying...', 'updated_at' => now()->toDateTimeString()], now()->addHours(2));
                }
            }

            $productIdsUpdated = array_unique(array_column($stockCountChanges, 'product_id'));
            foreach ($productIdsUpdated as $pid) {
                if (!$pid) {
                    continue;
                }
                $mainProduct = ProductService::find($pid);
                if ($mainProduct) {
                    $mainProduct->quantity = SubProduct::where('product_id', $pid)->sum('quantity');
                    $mainProduct->save();
                }
            }

            DB::commit();
            Cache::put($statusKey, ['status' => 'done', 'progress' => 100, 'message' => 'Completed', 'updated_at' => now()->toDateTimeString()], now()->addHours(2));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $warehouses = warehouse::where('created_by', '=', \Auth::user()->creatorId())
            ->with('tax')
            ->get();
        
        // Get sub-product counts for each warehouse
        $subProductCounts = [];
        foreach ($warehouses as $warehouse) {
            $subProductCounts[$warehouse->id] = SubProduct::where('warehouse_id', $warehouse->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->count();
        }
        
        return view('warehouse.index', compact('warehouses', 'subProductCounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $countries = Country::get()->pluck('name', 'id')->toArray();
        $taxes = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        return view('warehouse.create', compact('countries', 'taxes'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(\Auth::user()->can('create warehouse'))
        {
            $creatorId = \Auth::user()->creatorId();
            $validator = \Validator::make(
                $request->all(), [
                    'name' => [
                        'required',
                        Rule::unique('warehouses', 'name')->where('created_by', $creatorId),
                    ],
                ],
                [
                    'name.unique' => __('A warehouse with this name already exists for your company.'),
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $warehouse             = new warehouse();
            $warehouse->name       = $request->name;
            $warehouse->company_name = $request->filled('company_name') ? trim((string) $request->company_name) : null;
            $warehouse->address    = trim((string) $request->input('address', ''));
            $warehouse->city       = trim((string) $request->input('city', ''));
            $warehouse->city_zip   = trim((string) $request->input('city_zip', ''));
            $warehouse->country_id   = $request->country_id;
            $warehouse->tax_id     = $request->tax_id ?: null;
            $warehouse->created_by = $creatorId;
            
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = 'warehouse_' . time() . '_' . \Auth::user()->creatorId() . '.' . $logo->getClientOriginalExtension();
                $dir = 'warehouse_logo';
                
                // Create directory if it doesn't exist
                if (!\File::exists(public_path('storage/uploads/' . $dir))) {
                    \File::makeDirectory(public_path('storage/uploads/' . $dir), 0755, true);
                }
                
                $logo->move(public_path('storage/uploads/' . $dir), $logoName);
                $warehouse->logo = $logoName;
            }
            
            $warehouse->save();
            
            // Log warehouse creation
            PosLog::logAction('create_warehouse', [
                'type' => 'warehouse',
                'reference_id' => $warehouse->id,
                'warehouse_id' => $warehouse->id,
                'new_value' => [
                    'name' => $warehouse->name,
                    'company_name' => $warehouse->company_name,
                    'address' => $warehouse->address,
                    'city' => $warehouse->city,
                    'city_zip' => $warehouse->city_zip,
                    'country_id' => $warehouse->country_id,
                    'tax_id' => $warehouse->tax_id,
                ],
                'description' => "Warehouse '{$warehouse->name}' created",
            ]);

            return redirect()->route('warehouse.index')->with('success', __('Warehouse successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function show(warehouse $warehouse)
    {

        $id = WarehouseProduct::where('warehouse_id' , $warehouse->id)->first();

        if(\Auth::user()->can('show warehouse'))
        {

            // Get logs related to this warehouse (always fetch logs)
            $logs = PosLog::where('type', 'warehouse')
                ->where('reference_id', $warehouse->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            if(WarehouseProduct::where('warehouse_id' , $warehouse->id)->exists())
            {
                $warehouseProducts = WarehouseProduct::where('warehouse_id' , $warehouse->id)->where('created_by', '=', \Auth::user()->creatorId())->with(['product'])->get();

                return view('warehouse.show', compact('warehouse', 'warehouseProducts', 'logs'));
            }
            else
            {
                $warehouseProducts = collect([]); // Empty collection instead of array
                return view('warehouse.show', compact('warehouse', 'warehouseProducts', 'logs'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function edit(warehouse $warehouse)
    {

        if(\Auth::user()->can('edit warehouse'))
        {
            if($warehouse->created_by == \Auth::user()->creatorId())
            {
                $countries     = Country::get()->pluck('name', 'id');
                $countries->prepend('Select Country', '');
                $taxes = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $taxes->prepend('Select Tax', '');
                return view('warehouse.edit', compact('warehouse','countries', 'taxes'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, warehouse $warehouse)
    {

        if(\Auth::user()->can('edit warehouse'))
        {
            if($warehouse->created_by == \Auth::user()->creatorId())
            {
                $creatorId = \Auth::user()->creatorId();
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => [
                            'required',
                            Rule::unique('warehouses', 'name')
                                ->where('created_by', $creatorId)
                                ->ignore($warehouse->id),
                        ],
                    ],
                    [
                        'name.unique' => __('A warehouse with this name already exists for your company.'),
                    ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $warehouse->name       = $request->name;
                $warehouse->company_name = $request->filled('company_name') ? trim((string) $request->company_name) : null;
                $warehouse->address    = trim((string) $request->input('address', ''));
                $warehouse->city       = trim((string) $request->input('city', ''));
                $warehouse->city_zip   = trim((string) $request->input('city_zip', ''));
                $warehouse->country_id   = $request->country_id;
                $warehouse->tax_id     = $request->tax_id ?: null;
                
                // Handle logo upload
                if ($request->hasFile('logo')) {
                    // Delete old logo if exists
                    if ($warehouse->logo && \File::exists(public_path('storage/uploads/warehouse_logo/' . $warehouse->logo))) {
                        \File::delete(public_path('storage/uploads/warehouse_logo/' . $warehouse->logo));
                    }
                    
                    $logo = $request->file('logo');
                    $logoName = 'warehouse_' . time() . '_' . \Auth::user()->creatorId() . '.' . $logo->getClientOriginalExtension();
                    $dir = 'warehouse_logo';
                    
                    // Create directory if it doesn't exist
                    if (!\File::exists(public_path('storage/uploads/' . $dir))) {
                        \File::makeDirectory(public_path('storage/uploads/' . $dir), 0755, true);
                    }
                    
                    $logo->move(public_path('storage/uploads/' . $dir), $logoName);
                    $warehouse->logo = $logoName;
                }
                
                // Store old values for logging
                $oldValues = [
                    'name' => $warehouse->getOriginal('name'),
                    'company_name' => $warehouse->getOriginal('company_name'),
                    'address' => $warehouse->getOriginal('address'),
                    'city' => $warehouse->getOriginal('city'),
                    'city_zip' => $warehouse->getOriginal('city_zip'),
                    'country_id' => $warehouse->getOriginal('country_id'),
                    'tax_id' => $warehouse->getOriginal('tax_id'),
                ];
                
                $warehouse->save();
                
                // Log warehouse update
                PosLog::logAction('update_warehouse', [
                    'type' => 'warehouse',
                    'reference_id' => $warehouse->id,
                    'warehouse_id' => $warehouse->id,
                    'old_value' => $oldValues,
                    'new_value' => [
                        'name' => $warehouse->name,
                        'company_name' => $warehouse->company_name,
                        'address' => $warehouse->address,
                        'city' => $warehouse->city,
                        'city_zip' => $warehouse->city_zip,
                        'country_id' => $warehouse->country_id,
                        'tax_id' => $warehouse->tax_id,
                    ],
                    'description' => "Warehouse '{$warehouse->name}' updated",
                ]);

                return redirect()->route('warehouse.index')->with('success', __('Warehouse successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function destroy(warehouse $warehouse)
    {
        if(\Auth::user()->can('delete warehouse'))
        {
            if($warehouse->created_by == \Auth::user()->creatorId())
            {
                // Check if there are any sub-products using this warehouse
                $subProductsCount = SubProduct::where('warehouse_id', $warehouse->id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->count();
                
                if ($subProductsCount > 0) {
                    return redirect()->route('warehouse.index')
                        ->with('error', __('Cannot delete warehouse. There are :count sub-product(s) associated with this warehouse.', ['count' => $subProductsCount]));
                }
                
                // Log warehouse deletion before deleting
                PosLog::logAction('delete_warehouse', [
                    'type' => 'warehouse',
                    'reference_id' => $warehouse->id,
                    'warehouse_id' => $warehouse->id,
                    'old_value' => [
                        'name' => $warehouse->name,
                        'company_name' => $warehouse->company_name,
                        'address' => $warehouse->address,
                        'city' => $warehouse->city,
                        'city_zip' => $warehouse->city_zip,
                        'country_id' => $warehouse->country_id,
                        'tax_id' => $warehouse->tax_id,
                    ],
                    'description' => "Warehouse '{$warehouse->name}' deleted",
                ]);
                
                $warehouse->delete();

                return redirect()->route('warehouse.index')->with('success', __('Warehouse successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show stock count page for a warehouse
     */
    public function stockCount(Request $request, warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Allow user to cancel a large import and return to normal stock count view
        if ($request->has('clear_import')) {
            $keys = ['imported_data', 'import_success_count', 'import_error_count', 'import_errors', 'import_error_items', 'import_error_items_' . $warehouse->id];
            foreach ($keys as $key) {
                session()->forget($key);
            }
            return redirect()->route('warehouse.stock-count', $warehouse->id);
        }

        // Get imported data from session (if any) - check early to determine if we need to filter
        $importedData = session('imported_data', []);
        $hasImportedData = !empty($importedData);
        $importedCount = $hasImportedData ? count($importedData) : 0;
        // When too many imported items, don't load/render them (causes browser crash). Use "Apply from import" instead.
        $bulkImportLimit = 200;
        $bulkImportApply = $hasImportedData && $importedCount > $bulkImportLimit;

        if ($bulkImportApply) {
            // Don't load sub_products or build table - show "Apply stock count from import" card only
            $productData = [];
            $brands = collect();
            $subBrands = collect();
        } else {
        // Optimized query with pagination - only select needed columns
        $perPage = $request->get('per_page', 100); // Default 100 items per page

        $subProductsQuery = SubProduct::where('warehouse_id', $warehouse->id)
            ->where('created_by', \Auth::user()->creatorId())
            ->select('id', 'chassis_no', 'product_id', 'quantity', 'purchase_price')
            ->with([
                'productService:id,name,category_id,brand_id,sub_brand_id',
                'productService.category:id,name',
                'productService.brand:id,name',
                'productService.subBrand:id,name'
            ])
            ->orderBy('chassis_no');

        // If we have imported data, filter to show only imported products.
        if ($hasImportedData) {
            $importedProductNos = array_keys($importedData);
            $subProductsQuery->whereIn('chassis_no', $importedProductNos);
        }

        // Filters (apply to all data, before pagination)
        $sku = trim((string)$request->get('sku', ''));
        if ($sku !== '') {
            $subProductsQuery->whereHas('productService', function ($q) use ($sku) {
                $q->where('sku', 'like', '%' . $sku . '%');
            });
        }

        $subProductNo = trim((string)$request->get('sub_product_no', ''));
        if ($subProductNo !== '') {
            $subProductsQuery->where('chassis_no', 'like', '%' . $subProductNo . '%');
        }

        // Product No list: paste from Excel (one per line, or comma/tab separated)
        $productNosRaw = trim((string)$request->get('product_nos', ''));
        $productNosFilter = [];
        if ($productNosRaw !== '') {
            $productNosFilter = preg_split('/[\r\n\t,]+/', $productNosRaw, -1, PREG_SPLIT_NO_EMPTY);
            $productNosFilter = array_map('trim', $productNosFilter);
            $productNosFilter = array_values(array_filter($productNosFilter, function ($v) {
                return $v !== '';
            }));
        }
        if (!empty($productNosFilter)) {
            $subProductsQuery->whereIn('chassis_no', $productNosFilter);
        }

        $brandId = $request->get('brand_id');
        if ($brandId !== null && $brandId !== '' && is_numeric($brandId)) {
            $brandId = (int)$brandId;
            $subProductsQuery->whereHas('productService', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
        }

        $subBrandId = $request->get('sub_brand_id');
        if ($subBrandId !== null && $subBrandId !== '' && is_numeric($subBrandId)) {
            $subBrandId = (int)$subBrandId;
            $subProductsQuery->whereHas('productService', function ($q) use ($subBrandId) {
                $q->where('sub_brand_id', $subBrandId);
            });
        }

        // Load sub-products.
        // If we have imported data with many product_nos, run chunked queries to avoid
        // a single huge IN clause (slow query / max bindings).
        if ($hasImportedData) {
            $importedProductNos = array_keys($importedData);
            if (count($importedProductNos) > 500) {
                $subProducts = collect();
                foreach (array_chunk($importedProductNos, 500) as $chunk) {
                    $baseQuery = SubProduct::where('warehouse_id', $warehouse->id)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->select('id', 'chassis_no', 'product_id', 'quantity', 'purchase_price')
                        ->with([
                            'productService:id,name,category_id,brand_id,sub_brand_id',
                            'productService.category:id,name',
                            'productService.brand:id,name',
                            'productService.subBrand:id,name'
                        ])
                        ->whereIn('chassis_no', $chunk)
                        ->orderBy('chassis_no');
                    if ($sku !== '') {
                        $baseQuery->whereHas('productService', function ($q) use ($sku) {
                            $q->where('sku', 'like', '%' . $sku . '%');
                        });
                    }
                    if ($subProductNo !== '') {
                        $baseQuery->where('chassis_no', 'like', '%' . $subProductNo . '%');
                    }
                    if (!empty($productNosFilter)) {
                        $baseQuery->whereIn('chassis_no', $productNosFilter);
                    }
                    if ($brandId !== null && $brandId !== '' && is_numeric($brandId)) {
                        $baseQuery->whereHas('productService', function ($q) use ($brandId) {
                            $q->where('brand_id', $brandId);
                        });
                    }
                    if ($subBrandId !== null && $subBrandId !== '' && is_numeric($subBrandId)) {
                        $baseQuery->whereHas('productService', function ($q) use ($subBrandId) {
                            $q->where('sub_brand_id', $subBrandId);
                        });
                    }
                    $subProducts = $subProducts->merge($baseQuery->get());
                }
            } else {
                $subProducts = $subProductsQuery->get();
            }
        } else {
            $subProducts = $subProductsQuery->paginate($perPage);
        }

        // Build product data array for sub-products,
        // grouped by Product No and summing the quantities
        $groupedProducts = [];
        $subProductItems = $subProducts instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $subProducts->items()
            : $subProducts;

        foreach ($subProductItems as $subProduct) {
            $product = $subProduct->productService;

            // Build full product name hierarchy: Category → Brand → Sub Brand → Product Name
            $nameParts = [];
            $categoryName = '';
            $brandName = '';
            $subBrandName = '';
            $productName = $product->name ?? 'N/A';

            if ($product) {
                if ($product->category) {
                    $categoryName = $product->category->name ?? '';
                    if (!empty($categoryName)) {
                        $nameParts[] = $categoryName;
                    }
                }

                if ($product->brand) {
                    $brandName = $product->brand->name ?? '';
                    if (!empty($brandName)) {
                        $nameParts[] = $brandName;
                    }
                }

                if ($product->subBrand) {
                    $subBrandName = $product->subBrand->name ?? '';
                    if (!empty($subBrandName)) {
                        $nameParts[] = $subBrandName;
                    }
                }

                if (!empty($productName) && $productName !== 'N/A') {
                    $nameParts[] = $productName;
                }
            }

            $fullProductName = !empty($nameParts) ? implode(' → ', $nameParts) : $productName;

            $productNo = $subProduct->chassis_no;
            if (!isset($groupedProducts[$productNo])) {
                $groupedProducts[$productNo] = [
                    'product_no' => $productNo,
                    'product_id' => $product->id ?? null,
                    'product_name' => $fullProductName,
                    'product_name_raw' => $productName,
                    'category_name' => $categoryName,
                    'brand_name' => $brandName,
                    'sub_brand_name' => $subBrandName,
                    'current_qty' => 0,
                    'purchase_price' => $subProduct->purchase_price,
                    'category' => $product->category ?? null,
                    'sub_products' => [],
                ];
            }

            // Sum quantity for this Product No
            $groupedProducts[$productNo]['current_qty'] += $subProduct->quantity;

            // Keep list of underlying sub products so that when counting
            // we can distribute the final quantity back, making the last
            // one fit the barcode by subtracting the others.
            $groupedProducts[$productNo]['sub_products'][] = [
                'sub_product_id' => $subProduct->id,
                'current_qty' => $subProduct->quantity,
            ];
        }

        // Order sub_products by id ASC (oldest first) so profit adds to newest (last), loss subtracts from oldest (first)
        foreach ($groupedProducts as &$group) {
            usort($group['sub_products'], function ($a, $b) {
                return $a['sub_product_id'] <=> $b['sub_product_id'];
            });
        }
        unset($group);

        $productDataArray = array_values($groupedProducts);

        if ($hasImportedData) {
            // For imported data, return all grouped products as a plain array
            // (no pagination) so the user can save all imported items at once.
            $productData = $productDataArray;
        } else {
            // Create a custom paginator with transformed data
            $productData = new \Illuminate\Pagination\LengthAwarePaginator(
                $productDataArray,
                $subProducts->total(),
                $subProducts->perPage(),
                $subProducts->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        // Dropdown options for filters (from all warehouse data, not just current page)
        $warehouseProductIds = SubProduct::where('warehouse_id', $warehouse->id)
            ->where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id');

        $brandIds = ProductService::whereIn('id', $warehouseProductIds)
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id');
        $brands = Brand::whereIn('id', $brandIds)->orderBy('name')->get(['id', 'name']);

        $subBrandIds = ProductService::whereIn('id', $warehouseProductIds)
            ->whereNotNull('sub_brand_id')
            ->distinct()
            ->pluck('sub_brand_id');
        $subBrands = VehicleModel::whereIn('id', $subBrandIds)->orderBy('name')->get(['id', 'name']);
        }

        // Get remaining imported data from session (already retrieved above)
        $importErrors = session('import_errors', []);
        $importErrorItems = session('import_error_items', []); // Flash data for immediate display
        if (empty($importErrorItems) && !empty($importErrorItemsPersistent)) {
            $importErrorItems = $importErrorItemsPersistent;
        }
        $importSuccessCount = session('import_success_count', 0);
        $importErrorCount = session('import_error_count', 0);

        // When bulk apply: do not pass full importedData to view (avoids 4000+ items and slow load)
        $importedDataForView = $bulkImportApply ? [] : $importedData;

        return view('warehouse.stock_count', compact(
            'warehouse',
            'productData',
            'importedDataForView',
            'importErrors',
            'importErrorItems',
            'importSuccessCount',
            'importErrorCount',
            'hasImportedData',
            'bulkImportApply',
            'importedCount',
            'brands',
            'subBrands'
        ));
    }

    /**
     * Get categories for stock count filtering
     */
    public function getStockCountCategories(Request $request)
    {
        try {
            $warehouseId = $request->warehouse_id;
            
            if (!$warehouseId) {
                return response()->json(['error' => 'Warehouse ID is required'], 400);
            }

            // Get unique category IDs from products that have sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            $categoryIds = ProductService::whereIn('id', $productIds)
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $categories = ProductServiceCategory::whereIn('id', $categoryIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json(['categories' => $categories]);
        } catch (\Exception $e) {
            \Log::error('Error getting stock count categories', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id
            ]);
            return response()->json(['error' => 'Error getting categories'], 500);
        }
    }

    /**
     * Get brands for stock count filtering
     */
    public function getStockCountBrands(Request $request)
    {
        try {
            $warehouseId = $request->warehouse_id;
            $categoryId = $request->category_id;
            
            if (!$warehouseId || !$categoryId) {
                return response()->json(['error' => 'Warehouse ID and Category ID are required'], 400);
            }

            // Get product IDs from sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            // Get brand IDs from products that match category
            $brandIds = ProductService::whereIn('id', $productIds)
                ->where('category_id', $categoryId)
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id');

            $brands = Brand::whereIn('id', $brandIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json(['brands' => $brands]);
        } catch (\Exception $e) {
            \Log::error('Error getting stock count brands', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id,
                'category_id' => $request->category_id
            ]);
            return response()->json(['error' => 'Error getting brands'], 500);
        }
    }

    /**
     * Get products for stock count filtering
     */
    public function getStockCountProducts(Request $request)
    {
        try {
            $warehouseId = $request->warehouse_id;
            $categoryId = $request->category_id;
            $brandId = $request->brand_id;
            
            if (!$warehouseId || !$categoryId || !$brandId) {
                return response()->json(['error' => 'Warehouse ID, Category ID, and Brand ID are required'], 400);
            }

            // Get product IDs from sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('product_id')
                ->distinct()
                ->pluck('product_id');

            // Get products that match category and brand
            $products = ProductService::whereIn('id', $productIds)
                ->where('category_id', $categoryId)
                ->where('brand_id', $brandId)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']);

            return response()->json(['products' => $products]);
        } catch (\Exception $e) {
            \Log::error('Error getting stock count products', [
                'error' => $e->getMessage(),
                'warehouse_id' => $request->warehouse_id,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id
            ]);
            return response()->json(['error' => 'Error getting products'], 500);
        }
    }

    /**
     * Store stock count results
     */
    public function storeStockCount(Request $request, warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Accept group_quantities (one new total per barcode/product_no). If not sent, build from quantities for backward compat.
        $groupQuantities = (array) $request->input('group_quantities', []);
        if (empty($groupQuantities)) {
            $quantities = (array) $request->input('quantities', []);
            if (empty($quantities)) {
                return redirect()->back()->with('error', __('Please provide stock count quantities (group by barcode) or per-item quantities.'));
            }
            $request->validate(['quantities' => 'required|array', 'quantities.*' => 'required|integer|min:0']);
            $subProductIds = array_keys($quantities);
            $subs = SubProduct::whereIn('id', $subProductIds)
                ->where('warehouse_id', $warehouse->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->get(['id', 'chassis_no']);
            foreach ($subs as $s) {
                $pn = (string) $s->product_no;
                $groupQuantities[$pn] = ($groupQuantities[$pn] ?? 0) + (int) ($quantities[$s->id] ?? 0);
            }
        }
        $request->validate([
            'group_quantities' => 'sometimes|array',
            'group_quantities.*' => 'required|integer|min:0',
        ]);

        // Get Loss on Count account
        $lossAccount = ChartOfAccount::where('name', 'LIKE', '%Loss on Count%')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$lossAccount) {
            return redirect()->back()->with('error', __('Please create a "Loss on Count" account in Chart of Accounts before performing stock count.'));
        }

        // Get Profit on Count account
        $profitAccount = ChartOfAccount::where('name', 'LIKE', '%Profit on Count%')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$profitAccount) {
            return redirect()->back()->with('error', __('Please create a "Profit on Count" account in Chart of Accounts before performing stock count.'));
        }

        // Get latest voucher ID
        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
            ->orderBy('vid', 'desc')
            ->first();
        $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;

        DB::beginTransaction();
        try {
            $hasChanges = false;
            $stockCountChanges = [];
            $creatorId = \Auth::user()->creatorId();

            foreach ($groupQuantities as $productNo => $newTotal) {
                $productNo = (string) $productNo;
                $newTotal = (int) $newTotal;

                // Sub-products for this barcode in this warehouse, oldest first (id ASC)
                $subProducts = SubProduct::where('warehouse_id', $warehouse->id)
                    ->where('created_by', $creatorId)
                    ->where('chassis_no', $productNo)
                    ->with(['productService', 'productService.category'])
                    ->orderBy('id')
                    ->get();

                if ($subProducts->isEmpty()) {
                    continue;
                }

                $currentTotal = $subProducts->sum('quantity');
                $diff = $newTotal - $currentTotal;
                if ($diff == 0) {
                    continue;
                }

                $hasChanges = true;
                $product = $subProducts->first()->productService;
                $productName = $product ? $product->name : 'N/A';
                $productId = $subProducts->first()->product_id;

                if ($diff > 0) {
                    // Profit: add the difference to the NEWEST sub_product (highest id)
                    $newest = $subProducts->sortByDesc('id')->first();
                    $oldNewestQty = (int) $newest->quantity;
                    $newest->quantity += $diff;
                    // If this sub product was zero and now increased, make it available again.
                    if ($oldNewestQty === 0 && (int) $newest->quantity > 0) {
                        $newest->booked = 0;
                    }
                    $newest->save();

                    $stockCountChanges[] = [
                        'sub_product_id' => $newest->id,
                        'product_no' => $productNo,
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'old_quantity' => $currentTotal,
                        'new_quantity' => $newTotal,
                        'difference' => $diff,
                    ];

                    $this->applyStockCountProfit($warehouse, $newest, $diff, $newVoucherId, $profitAccount, $lossAccount);
                } else {
                    // Loss: subtract from OLDEST sub_products first (FIFO) until difference is 0; ledger per sub_product with sub_product_id
                    $toSubtract = (int) abs($diff);
                    foreach ($subProducts as $sub) {
                        if ($toSubtract <= 0) {
                            break;
                        }
                        $deduct = min($sub->quantity, $toSubtract);
                        if ($deduct <= 0) {
                            continue;
                        }
                        $sub->quantity -= $deduct;
                        $sub->save();
                        $toSubtract -= $deduct;

                        $stockCountChanges[] = [
                            'sub_product_id' => $sub->id,
                            'product_no' => $productNo,
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'old_quantity' => $sub->quantity + $deduct,
                            'new_quantity' => $sub->quantity,
                            'difference' => -$deduct,
                        ];

                        $this->applyStockCountLoss($warehouse, $sub, $deduct, $newVoucherId, $profitAccount, $lossAccount);
                    }
                }
            }

            // Update main product quantity (sum of sub_products per product_id)
            $productIdsUpdated = array_unique(array_column($stockCountChanges, 'product_id'));
            foreach ($productIdsUpdated as $pid) {
                if (!$pid) {
                    continue;
                }
                $mainProduct = ProductService::find($pid);
                if ($mainProduct) {
                    $mainProduct->quantity = SubProduct::where('product_id', $pid)->sum('quantity');
                    $mainProduct->save();
                }
            }

            DB::commit();

            // Log stock count operation
            if ($hasChanges && !empty($stockCountChanges)) {
                // Log individual changes for each product
                foreach ($stockCountChanges as $change) {
                    PosLog::logAction('stock_count', [
                        'type' => 'warehouse',
                        'reference_id' => $warehouse->id,
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $change['product_id'],
                        'product_no' => $change['product_no'],
                        'quantity' => $change['difference'],
                        'old_value' => [
                            'quantity' => $change['old_quantity'],
                            'product_no' => $change['product_no'],
                            'product_name' => $change['product_name'],
                        ],
                        'new_value' => [
                            'quantity' => $change['new_quantity'],
                            'product_no' => $change['product_no'],
                            'product_name' => $change['product_name'],
                            'difference' => $change['difference'],
                        ],
                        'description' => "Stock count: Product #{$change['product_no']} ({$change['product_name']}) changed from {$change['old_quantity']} to {$change['new_quantity']} (difference: {$change['difference']})",
                    ]);
                }
                
                // Log summary of stock count
                $totalProductsChanged = count($stockCountChanges);
                $totalProfit = array_sum(array_column(array_filter($stockCountChanges, function($c) { return $c['difference'] > 0; }), 'difference'));
                $totalLoss = abs(array_sum(array_column(array_filter($stockCountChanges, function($c) { return $c['difference'] < 0; }), 'difference')));
                
                PosLog::logAction('stock_count_summary', [
                    'type' => 'warehouse',
                    'reference_id' => $warehouse->id,
                    'warehouse_id' => $warehouse->id,
                    'new_value' => [
                        'total_products_changed' => $totalProductsChanged,
                        'total_profit_qty' => $totalProfit,
                        'total_loss_qty' => $totalLoss,
                        'products' => $stockCountChanges,
                    ],
                    'description' => "Stock count completed for warehouse '{$warehouse->name}': {$totalProductsChanged} product(s) changed. Profit: {$totalProfit}, Loss: {$totalLoss}",
                ]);
            }

            // Preserve filters in redirect
            $redirectParams = [];
            if ($request->has('filter_category_id')) {
                $redirectParams['category_id'] = $request->filter_category_id;
            }
            if ($request->has('filter_brand_id')) {
                $redirectParams['brand_id'] = $request->filter_brand_id;
            }
            if ($request->has('filter_product_id')) {
                $redirectParams['product_id'] = $request->filter_product_id;
            }

            if ($hasChanges) {
                return redirect()->route('warehouse.stock-count', array_merge([$warehouse->id], $redirectParams))
                    ->with('success', __('Stock count saved successfully.'));
            } else {
                return redirect()->route('warehouse.stock-count', array_merge([$warehouse->id], $redirectParams))
                    ->with('info', __('No changes detected in stock count.'));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stock count failed', [
                'error' => $e->getMessage(),
                'warehouse_id' => $warehouse->id,
                'user_id' => \Auth::user()->creatorId()
            ]);
            return redirect()->back()->with('error', __('Stock count failed: ') . $e->getMessage());
        }
    }

    /**
     * Apply stock count from imported data in session (used when user clicks "Apply" after small import).
     */
    public function applyStockCountFromImport(Request $request, warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $importedData = session('imported_data', []);
        if (empty($importedData)) {
            return redirect()->route('warehouse.stock-count', $warehouse->id)->with('error', __('No imported data found. Please import again.'));
        }

        try {
            $this->applyStockCountFromImportedData($warehouse, $importedData);
        } catch (\Exception $e) {
            \Log::error('Stock count apply from import failed', ['error' => $e->getMessage(), 'warehouse_id' => $warehouse->id]);
            return redirect()->back()->with('error', __('Stock count failed: ') . $e->getMessage());
        }

        session()->forget(['imported_data', 'import_success_count', 'import_error_count', 'import_errors', 'import_error_items']);
        session()->forget('import_error_items_' . $warehouse->id);

        return redirect()->route('warehouse.stock-count', $warehouse->id)->with('success', __('Stock count from import applied successfully.'));
    }

    /**
     * Apply stock count from an imported data array (group by barcode, profit/loss, ledger). Used by applyStockCountFromImport and by large import (auto-apply).
     */
    protected function applyStockCountFromImportedData(warehouse $warehouse, array $importedData): void
    {
        $groupQuantities = [];
        foreach ($importedData as $productNo => $data) {
            $productNo = is_string($productNo) ? trim($productNo) : (string) $productNo;
            if ($productNo === '') {
                continue;
            }
            $qty = is_array($data) ? ($data['quantity'] ?? 0) : (int) $data;
            $groupQuantities[$productNo] = (int) $qty;
        }

        if (empty($groupQuantities)) {
            return;
        }

        // Large apply: extend time and memory so request does not break or refresh before finish
        $count = count($groupQuantities);
        if ($count > 500) {
            set_time_limit(900);
            if (function_exists('ini_set')) {
                @ini_set('memory_limit', '512M');
            }
        }

        $lossAccount = ChartOfAccount::where('name', 'LIKE', '%Loss on Count%')->where('created_by', \Auth::user()->creatorId())->first();
        if (!$lossAccount) {
            throw new \RuntimeException(__('Please create a "Loss on Count" account in Chart of Accounts before performing stock count.'));
        }
        $profitAccount = ChartOfAccount::where('name', 'LIKE', '%Profit on Count%')->where('created_by', \Auth::user()->creatorId())->first();
        if (!$profitAccount) {
            throw new \RuntimeException(__('Please create a "Profit on Count" account in Chart of Accounts before performing stock count.'));
        }

        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
        $newVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;
        $creatorId = \Auth::user()->creatorId();

        // Pre-load all sub_products for these product_nos in chunks (avoids 4000+ queries and timeout)
        $productNos = array_keys($groupQuantities);
        $subProductsByProductNo = [];
        $chunkSize = 500;
        foreach (array_chunk($productNos, $chunkSize) as $chunk) {
            $subs = SubProduct::where('warehouse_id', $warehouse->id)
                ->where('created_by', $creatorId)
                ->whereIn('chassis_no', $chunk)
                ->with(['productService', 'productService.category'])
                ->orderBy('id')
                ->get();
            foreach ($subs as $sub) {
                $pn = (string) $sub->product_no;
                if (!isset($subProductsByProductNo[$pn])) {
                    $subProductsByProductNo[$pn] = collect();
                }
                $subProductsByProductNo[$pn]->push($sub);
            }
        }

        DB::beginTransaction();
        try {
            $stockCountChanges = [];

            foreach ($groupQuantities as $productNo => $newTotal) {
                $productNo = (string) $productNo;
                $newTotal = (int) $newTotal;

                $subProducts = $subProductsByProductNo[$productNo] ?? collect();
                if ($subProducts->isEmpty()) {
                    continue;
                }

                $currentTotal = $subProducts->sum('quantity');
                $diff = $newTotal - $currentTotal;
                if ($diff == 0) {
                    continue;
                }

                $product = $subProducts->first()->productService;
                $productName = $product ? $product->name : 'N/A';
                $productId = $subProducts->first()->product_id;

                if ($diff > 0) {
                    $newest = $subProducts->sortByDesc('id')->first();
                    $oldNewestQty = (int) $newest->quantity;
                    $newest->quantity += $diff;
                    // If this sub product was zero and now increased, make it available again.
                    if ($oldNewestQty === 0 && (int) $newest->quantity > 0) {
                        $newest->booked = 0;
                    }
                    $newest->save();
                    $stockCountChanges[] = ['sub_product_id' => $newest->id, 'product_no' => $productNo, 'product_id' => $productId, 'product_name' => $productName, 'old_quantity' => $currentTotal, 'new_quantity' => $newTotal, 'difference' => $diff];
                    $this->applyStockCountProfit($warehouse, $newest, $diff, $newVoucherId, $profitAccount, $lossAccount);
                } else {
                    $toSubtract = (int) abs($diff);
                    foreach ($subProducts as $sub) {
                        if ($toSubtract <= 0) {
                            break;
                        }
                        $deduct = min($sub->quantity, $toSubtract);
                        if ($deduct <= 0) {
                            continue;
                        }
                        $sub->quantity -= $deduct;
                        $sub->save();
                        $toSubtract -= $deduct;
                        $stockCountChanges[] = ['sub_product_id' => $sub->id, 'product_no' => $productNo, 'product_id' => $productId, 'product_name' => $productName, 'old_quantity' => $sub->quantity + $deduct, 'new_quantity' => $sub->quantity, 'difference' => -$deduct];
                        $this->applyStockCountLoss($warehouse, $sub, $deduct, $newVoucherId, $profitAccount, $lossAccount);
                    }
                }
            }

            $productIdsUpdated = array_unique(array_column($stockCountChanges, 'product_id'));
            foreach ($productIdsUpdated as $pid) {
                if (!$pid) {
                    continue;
                }
                $mainProduct = ProductService::find($pid);
                if ($mainProduct) {
                    $mainProduct->quantity = SubProduct::where('product_id', $pid)->sum('quantity');
                    $mainProduct->save();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apply stock count profit: add qty to one sub_product, create ledger entries with sub_product_id.
     */
    protected function applyStockCountProfit(warehouse $warehouse, SubProduct $subProduct, int $profitQty, int $newVoucherId, ChartOfAccount $profitAccount, ChartOfAccount $lossAccount)
    {
        $product = $subProduct->productService;
        if (!$product || !$product->category) {
            return;
        }
        $costPerUnit = (float) $subProduct->purchase_price;
        if ($subProduct->bill_id) {
            $bill = \App\Models\Bill::find($subProduct->bill_id);
            if ($bill && $bill->accounts) {
                $totalExpenses = $bill->accounts->sum('price');
                $totalItems = $bill->items->sum('quantity');
                if ($totalItems > 0) {
                    $costPerUnit += $totalExpenses / $totalItems;
                }
            }
        }
        $profitAmount = $profitQty * $costPerUnit;
        $purchaseAccountId = $product->category->purchase_account_id;
        $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);
        $purchasePriceForItem = $subProduct->purchase_price ?? 0;

        if ($product->category->type === 'Qty product') {
            $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
            $avgCost = $purchasePriceForItem;
            if ($costCalculationMethod === 'avg') {
                $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])->where('created_by', \Auth::user()->creatorId())->pluck('id')->toArray();
                $purchasedSubProductQty = SubProduct::where('product_id', $product->id)->whereIn('bill_id', $purchasedBillIds)->where('flag', '!=', 0)->whereNotNull('bill_id')->sum('quantity') ?: 0;
                $totalQty = $purchasedSubProductQty + $profitQty;
                if ($totalQty > 0) {
                    $avgCost = (($purchasedSubProductQty * $lastAvg) + ($profitQty * $purchasePriceForItem)) / $totalQty;
                }
            }
            $sm = new StockMovement();
            $sm->product_id = $product->id;
            $sm->sub_product_id = $subProduct->id;
            $sm->invoice_id = $sm->bill_id = $sm->pos_id = null;
            $sm->qty_in = $profitQty;
            $sm->qty_out = 0;
            $sm->avg_cost = $avgCost;
            $sm->cost_price = $purchasePriceForItem;
            $sm->activity = 'Stock Count Profit';
            $sm->use_id = null;
            $sm->item = $subProduct->id;
            $sm->created_by = \Auth::user()->creatorId();
            $sm->save();
            $product->avg_cost = $avgCost;
            $product->save();
        }

        if ($purchaseAccountId) {
            $ref = 'Stock Count Profit - Product: ' . $product->name . ' (Qty: ' . $profitQty . ')';
            GeneralLedger::create([
                'vid' => $newVoucherId, 'account' => $purchaseAccountId, 'type' => 'Stock Count - ' . $warehouse->name,
                'ref_number' => 'Stock Count Profit - ' . $warehouse->name, 'debit' => $profitAmount, 'credit' => 0,
                'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => \Auth::user()->creatorId(), 'send_date' => now(),
                'reference' => $ref, 'sub_product_id' => $subProduct->id, 'deleted_qty' => $profitQty,
            ]);
            GeneralLedger::create([
                'vid' => $newVoucherId, 'account' => $profitAccount->id, 'type' => 'Stock Count - ' . $warehouse->name,
                'ref_number' => 'Stock Count Profit - ' . $warehouse->name, 'debit' => 0, 'credit' => $profitAmount,
                'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => \Auth::user()->creatorId(), 'send_date' => now(),
                'reference' => $ref, 'sub_product_id' => $subProduct->id, 'deleted_qty' => $profitQty,
            ]);
        }
    }

    protected function applyStockCountProfitForCreator(warehouse $warehouse, SubProduct $subProduct, int $profitQty, int $newVoucherId, ChartOfAccount $profitAccount, ChartOfAccount $lossAccount, int $creatorId): void
    {
        // Reuse existing method logic but with fixed creator id
        $originalUser = null;
        try {
            // Most of the existing method uses \Auth::user()->creatorId() for created_by.
            // In job context we don't have Auth session, so we temporarily call a copy of the logic here.
            $product = $subProduct->productService;
            if (!$product || !$product->category) {
                return;
            }
            $costPerUnit = (float) $subProduct->purchase_price;
            if ($subProduct->bill_id) {
                $bill = \App\Models\Bill::find($subProduct->bill_id);
                if ($bill && $bill->accounts) {
                    $totalExpenses = $bill->accounts->sum('price');
                    $totalItems = $bill->items->sum('quantity');
                    if ($totalItems > 0) {
                        $costPerUnit += $totalExpenses / $totalItems;
                    }
                }
            }
            $profitAmount = $profitQty * $costPerUnit;
            $purchaseAccountId = $product->category->purchase_account_id;
            $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);
            $purchasePriceForItem = $subProduct->purchase_price ?? 0;

            if ($product->category->type === 'Qty product') {
                $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                $avgCost = $purchasePriceForItem;
                if ($costCalculationMethod === 'avg') {
                    $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])->where('created_by', $creatorId)->pluck('id')->toArray();
                    $purchasedSubProductQty = SubProduct::where('product_id', $product->id)->whereIn('bill_id', $purchasedBillIds)->where('flag', '!=', 0)->whereNotNull('bill_id')->sum('quantity') ?: 0;
                    $totalQty = $purchasedSubProductQty + $profitQty;
                    if ($totalQty > 0) {
                        $avgCost = (($purchasedSubProductQty * $lastAvg) + ($profitQty * $purchasePriceForItem)) / $totalQty;
                    }
                }
                $sm = new StockMovement();
                $sm->product_id = $product->id;
                $sm->sub_product_id = $subProduct->id;
                $sm->invoice_id = $sm->bill_id = $sm->pos_id = null;
                $sm->qty_in = $profitQty;
                $sm->qty_out = 0;
                $sm->avg_cost = $avgCost;
                $sm->cost_price = $purchasePriceForItem;
                $sm->activity = 'Stock Count Profit';
                $sm->use_id = null;
                $sm->item = $subProduct->id;
                $sm->created_by = $creatorId;
                $sm->save();
                $product->avg_cost = $avgCost;
                $product->save();
            }

            if ($purchaseAccountId) {
                $ref = 'Stock Count Profit - Product: ' . $product->name . ' (Qty: ' . $profitQty . ')';
                GeneralLedger::create([
                    'vid' => $newVoucherId, 'account' => $purchaseAccountId, 'type' => 'Stock Count - ' . $warehouse->name,
                    'ref_number' => 'Stock Count Profit - ' . $warehouse->name, 'debit' => $profitAmount, 'credit' => 0,
                    'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => $creatorId, 'send_date' => now(),
                    'reference' => $ref, 'description' => $ref, 'sub_product_id' => $subProduct->id,
                ]);
                GeneralLedger::create([
                    'vid' => $newVoucherId, 'account' => $profitAccount->id, 'type' => 'Stock Count - ' . $warehouse->name,
                    'ref_number' => 'Stock Count Profit - ' . $warehouse->name, 'debit' => 0, 'credit' => $profitAmount,
                    'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => $creatorId, 'send_date' => now(),
                    'reference' => $ref, 'description' => $ref, 'sub_product_id' => $subProduct->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('applyStockCountProfitForCreator failed', ['error' => $e->getMessage()]);
        }
    }

    protected function applyStockCountLossForCreator(warehouse $warehouse, SubProduct $subProduct, int $lossQty, int $newVoucherId, ChartOfAccount $profitAccount, ChartOfAccount $lossAccount, int $creatorId): void
    {
        try {
            $product = $subProduct->productService;
            if (!$product || !$product->category) {
                return;
            }
            $costPerUnit = (float) $subProduct->purchase_price;
            if ($subProduct->bill_id) {
                $bill = \App\Models\Bill::find($subProduct->bill_id);
                if ($bill && $bill->accounts) {
                    $totalExpenses = $bill->accounts->sum('price');
                    $totalItems = $bill->items->sum('quantity');
                    if ($totalItems > 0) {
                        $costPerUnit += $totalExpenses / $totalItems;
                    }
                }
            }
            $lossAmount = $lossQty * $costPerUnit;
            $purchaseAccountId = $product->category->purchase_account_id;
            $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);
            $purchasePriceForItem = $subProduct->purchase_price ?? 0;

            if ($product->category->type === 'Qty product') {
                $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                $avgCost = $purchasePriceForItem;
                if ($costCalculationMethod === 'avg') {
                    $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])->where('created_by', $creatorId)->pluck('id')->toArray();
                    $purchasedSubProductQty = SubProduct::where('product_id', $product->id)->whereIn('bill_id', $purchasedBillIds)->where('flag', '!=', 0)->whereNotNull('bill_id')->sum('quantity') ?: 0;
                    $totalQty = max(0, $purchasedSubProductQty - $lossQty);
                    if (($purchasedSubProductQty) > 0) {
                        $avgCost = (($purchasedSubProductQty * $lastAvg) - ($lossQty * $purchasePriceForItem)) / max(1, $totalQty);
                    }
                }
                $sm = new StockMovement();
                $sm->product_id = $product->id;
                $sm->sub_product_id = $subProduct->id;
                $sm->invoice_id = $sm->bill_id = $sm->pos_id = null;
                $sm->qty_in = 0;
                $sm->qty_out = $lossQty;
                $sm->avg_cost = $avgCost;
                $sm->cost_price = $purchasePriceForItem;
                $sm->activity = 'Stock Count Loss';
                $sm->use_id = null;
                $sm->item = $subProduct->id;
                $sm->created_by = $creatorId;
                $sm->save();
                $product->avg_cost = $avgCost;
                $product->save();
            }

            if ($purchaseAccountId) {
                $ref = 'Stock Count Loss - Product: ' . $product->name . ' (Qty: ' . $lossQty . ')';
                GeneralLedger::create([
                    'vid' => $newVoucherId, 'account' => $lossAccount->id, 'type' => 'Stock Count - ' . $warehouse->name,
                    'ref_number' => 'Stock Count Loss - ' . $warehouse->name, 'debit' => $lossAmount, 'credit' => 0,
                    'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => $creatorId, 'send_date' => now(),
                    'reference' => $ref, 'description' => $ref, 'sub_product_id' => $subProduct->id,
                ]);
                GeneralLedger::create([
                    'vid' => $newVoucherId, 'account' => $purchaseAccountId, 'type' => 'Stock Count - ' . $warehouse->name,
                    'ref_number' => 'Stock Count Loss - ' . $warehouse->name, 'debit' => 0, 'credit' => $lossAmount,
                    'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => $creatorId, 'send_date' => now(),
                    'reference' => $ref, 'description' => $ref, 'sub_product_id' => $subProduct->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('applyStockCountLossForCreator failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Apply stock count loss: deduct qty from one sub_product, create ledger entries with sub_product_id.
     */
    protected function applyStockCountLoss(warehouse $warehouse, SubProduct $subProduct, int $lossQty, int $newVoucherId, ChartOfAccount $profitAccount, ChartOfAccount $lossAccount)
    {
        $product = $subProduct->productService;
        if (!$product || !$product->category) {
            return;
        }
        $costPerUnit = (float) $subProduct->purchase_price;
        if ($subProduct->bill_id) {
            $bill = \App\Models\Bill::find($subProduct->bill_id);
            if ($bill && $bill->accounts) {
                $totalExpenses = $bill->accounts->sum('price');
                $totalItems = $bill->items->sum('quantity');
                if ($totalItems > 0) {
                    $costPerUnit += $totalExpenses / $totalItems;
                }
            }
        }
        $lossAmount = $lossQty * $costPerUnit;
        $purchaseAccountId = $product->category->purchase_account_id;
        $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);

        if ($product->category->type === 'Qty product') {
            $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
            $avgCost = $lastAvg;
            if ($costCalculationMethod === 'avg') {
                $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])->where('created_by', \Auth::user()->creatorId())->pluck('id')->toArray();
                $purchasedSubProductQty = SubProduct::where('product_id', $product->id)->whereIn('bill_id', $purchasedBillIds)->where('flag', '!=', 0)->whereNotNull('bill_id')->sum('quantity') ?: 0;
                $remainingQty = $purchasedSubProductQty - $lossQty;
                $avgCost = $remainingQty > 0 ? (($purchasedSubProductQty * $lastAvg) - ($lossQty * $lastAvg)) / $remainingQty : 0;
            }
            $sm = new StockMovement();
            $sm->product_id = $product->id;
            $sm->sub_product_id = $subProduct->id;
            $sm->invoice_id = $sm->bill_id = $sm->pos_id = null;
            $sm->qty_in = 0;
            $sm->qty_out = $lossQty;
            $sm->avg_cost = $avgCost;
            $sm->cost_price = $lastAvg;
            $sm->activity = 'Stock Count Loss';
            $sm->use_id = null;
            $sm->item = $subProduct->id;
            $sm->created_by = \Auth::user()->creatorId();
            $sm->save();
            $product->avg_cost = $avgCost;
            $product->save();
        }

        if ($purchaseAccountId) {
            $ref = 'Stock Count Loss - Product: ' . $product->name . ' (Qty: ' . $lossQty . ')';
            GeneralLedger::create([
                'vid' => $newVoucherId, 'account' => $lossAccount->id, 'type' => 'Stock Count - ' . $warehouse->name,
                'ref_number' => 'Stock Count Loss - ' . $warehouse->name, 'debit' => $lossAmount, 'credit' => 0,
                'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => \Auth::user()->creatorId(), 'send_date' => now(),
                'reference' => $ref, 'sub_product_id' => $subProduct->id, 'deleted_qty' => $lossQty,
            ]);
            GeneralLedger::create([
                'vid' => $newVoucherId, 'account' => $purchaseAccountId, 'type' => 'Stock Count - ' . $warehouse->name,
                'ref_number' => 'Stock Count Loss - ' . $warehouse->name, 'debit' => 0, 'credit' => $lossAmount,
                'ref_id' => $warehouse->id, 'user_id' => 0, 'created_by' => \Auth::user()->creatorId(), 'send_date' => now(),
                'reference' => $ref, 'sub_product_id' => $subProduct->id, 'deleted_qty' => $lossQty,
            ]);
        }
    }

    /**
     * Show import stock count form
     */
    public function showImportStockCount()
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        return view('warehouse.import_stock_count', compact('warehouses'));
    }

    /**
     * Process stock count import from Excel
     */
    public function importStockCount(Request $request)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // Get Loss on Count account
        $lossAccount = ChartOfAccount::where('name', 'LIKE', '%Loss on Count%')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$lossAccount) {
            return redirect()->back()->with('error', __('Please create a "Loss on Count" account in Chart of Accounts before performing stock count.'));
        }

        // Get Profit on Count account
        $profitAccount = ChartOfAccount::where('name', 'LIKE', '%Profit on Count%')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$profitAccount) {
            return redirect()->back()->with('error', __('Please create a "Profit on Count" account in Chart of Accounts before performing stock count.'));
        }

        try {
            // Read Excel file
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $path = $file->getRealPath();

            // Read Excel file into array
            $data = Excel::toArray([], $path, null, 
                $extension === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            if (empty($data) || empty($data[0])) {
                return redirect()->back()->with('error', __('Excel file is empty or invalid.'));
            }

            $rows = $data[0];
            
            // First row should be headers
            if (empty($rows)) {
                return redirect()->back()->with('error', __('Excel file has no data.'));
            }

            $headers = array_map('trim', $rows[0]);
            
            // First column should be "Sub Product No" or similar
            $productNoColumnIndex = 0;
            $productNoHeader = strtolower($headers[0]);
            if (strpos($productNoHeader, 'product') === false && strpos($productNoHeader, 'no') === false) {
                return redirect()->back()->with('error', __('First column must be "Sub Product No" or similar.'));
            }

            // Get all warehouses for the user
            $userWarehouses = warehouse::where('created_by', \Auth::user()->creatorId())
                ->pluck('name', 'id')
                ->toArray();

            // Map warehouse names to column indices
            $warehouseColumns = [];
            foreach ($headers as $index => $header) {
                $headerLower = strtolower(trim($header));
                foreach ($userWarehouses as $warehouseId => $warehouseName) {
                    if (strtolower(trim($warehouseName)) === $headerLower) {
                        $warehouseColumns[$warehouseId] = $index;
                        break;
                    }
                }
            }

            if (empty($warehouseColumns)) {
                return redirect()->back()->with('error', __('No matching warehouses found in Excel columns. Please ensure warehouse names match exactly.'));
            }

            // Get latest voucher ID once for the entire import
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())
                ->orderBy('vid', 'desc')
                ->first();
            $baseVoucherId = $latestVoucher ? $latestVoucher->vid + 1 : 1;
            $currentVoucherId = $baseVoucherId;

            // Pre-calculate purchased bill IDs once (used in cost calculations)
            $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                ->where('created_by', \Auth::user()->creatorId())
                ->pluck('id')
                ->toArray();

            // Cache for purchased sub-product quantities per product (to avoid repeating SUMs)
            $productPurchasedQtyCache = [];

            // Process each warehouse
            $results = [];
            $errors = [];
            $successCount = 0;
            $errorCount = 0;

            $stockCountImport = WarehouseStockCountImport::create([
                'created_by' => \Auth::user()->creatorId(),
                'user_id' => \Auth::id(),
                'warehouse_id' => null,
                'source_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
                'import_mode' => 'multi',
                'status' => 'applied',
                'job_token' => null,
                'line_count' => 0,
                'error_count' => 0,
                'meta' => ['warehouse_ids' => array_values(array_map('intval', array_keys($warehouseColumns)))],
            ]);
            $totalSnapshotLines = 0;

            foreach ($warehouseColumns as $warehouseId => $columnIndex) {
                $warehouse = warehouse::find($warehouseId);
                
                if (!$warehouse || $warehouse->created_by != \Auth::user()->creatorId()) {
                    $errors[] = "Warehouse ID {$warehouseId} not found or access denied.";
                    $errorCount++;
                    continue;
                }

                // Use current voucher ID and increment for next warehouse
                $newVoucherId = $currentVoucherId;
                $warehouseSuccessCount = 0;
                $warehouseErrorCount = 0;

                // Preload all sub-products for this warehouse into memory and index by product_no
                $warehouseSubProducts = SubProduct::where('warehouse_id', $warehouseId)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->with(['productService.category'])
                    ->get();

                $subProductsByProductNo = [];
                $billIdsForWarehouse = [];
                foreach ($warehouseSubProducts as $sp) {
                    $subProductsByProductNo[trim($sp->product_no)] = $sp;
                    if ($sp->bill_id) {
                        $billIdsForWarehouse[] = $sp->bill_id;
                    }
                }

                // Preload all related bills (with accounts & items) for this warehouse's sub-products
                $billsById = [];
                if (!empty($billIdsForWarehouse)) {
                    $billsById = \App\Models\Bill::whereIn('id', array_unique($billIdsForWarehouse))
                        ->with(['accounts', 'items'])
                        ->get()
                        ->keyBy('id')
                        ->toArray();
                }

                // Track products whose total quantity needs recalculation at the end
                $productsToRecalculate = [];

                DB::beginTransaction();
                try {
                    $warehouseChanges = [];
                    $hasChanges = false;
                    $snapshotLinesForWarehouse = [];

                    // Process each row (skip header row)
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        
                        // Skip empty rows
                        if (empty($row[$productNoColumnIndex])) {
                            continue;
                        }

                        $productNo = trim($row[$productNoColumnIndex]);
                        $quantity = isset($row[$columnIndex]) ? trim($row[$columnIndex]) : null;

                        // Skip if quantity is empty or not numeric
                        if ($quantity === null || $quantity === '' || !is_numeric($quantity)) {
                            continue;
                        }

                        $newQty = (int)$quantity;

                        // Find sub-product from preloaded collection by product_no
                        $subProduct = $subProductsByProductNo[$productNo] ?? null;

                        if (!$subProduct) {
                            $warehouseErrorCount++;
                            $errorCount++;
                            $errors[] = "Product No '{$productNo}' not found in warehouse '{$warehouse->name}' (Row " . ($i + 1) . ")";
                            continue; // Continue processing other products
                        }

                        $oldQty = $subProduct->quantity;

                        $snapshotLinesForWarehouse[] = [
                            'warehouse_stock_count_import_id' => $stockCountImport->id,
                            'warehouse_id' => $warehouse->id,
                            'product_no' => (string) $subProduct->chassis_no,
                            'sub_product_id' => $subProduct->id,
                            'counted_qty' => $newQty,
                            'system_qty_before' => (int) $oldQty,
                            'excel_row' => $i + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        if ($oldQty == $newQty) {
                            continue; // No change needed
                        }

                        $hasChanges = true;
                        $difference = $newQty - $oldQty;
                        
                        // Track change
                        $warehouseChanges[] = [
                            'sub_product_id' => $subProduct->id,
                            'product_no' => $subProduct->chassis_no,
                            'product_id' => $subProduct->product_id,
                            'product_name' => $subProduct->productService->name ?? 'N/A',
                            'old_quantity' => $oldQty,
                            'new_quantity' => $newQty,
                            'difference' => $difference,
                        ];

                        // Update quantity
                        $subProduct->quantity = $newQty;
                        if ((int) $oldQty === 0 && (int) $newQty > 0) {
                            $subProduct->booked = 0;
                        }
                        $subProduct->save();

                        // Get product info
                        $product = $subProduct->productService;

                        // Calculate cost per unit
                        $costPerUnit = $subProduct->purchase_price ?? 0;
                        if ($subProduct->bill_id) {
                            $billId = $subProduct->bill_id;
                            $bill = $billsById[$billId] ?? null;
                            if ($bill && !empty($bill['accounts'])) {
                                $totalExpenses = collect($bill['accounts'])->sum('price');
                                $totalItems = collect($bill['items'] ?? [])->sum('quantity');
                                if ($totalItems > 0) {
                                    $expensesPerUnit = $totalExpenses / $totalItems;
                                    $costPerUnit += $expensesPerUnit;
                                }
                            }
                        }

                        // Process profit (increase)
                        if ($difference > 0 && $product && $product->category) {
                            $profitQty = $difference;
                            $profitAmount = $profitQty * $costPerUnit;
                            $purchaseAccountId = $product->category->purchase_account_id;
                            
                            // Calculate average cost for Qty product type
                            $avgCost = null;
                            $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);
                            $purchasePriceForItem = $subProduct->purchase_price ?? 0;
                            
                            if ($product->category->type === "Qty product") {
                                $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                                
                                    if ($costCalculationMethod === 'avg') {
                                        // Get purchased sub-product quantity for this product, cached
                                        if (!array_key_exists($product->id, $productPurchasedQtyCache)) {
                                            $productPurchasedQtyCache[$product->id] = SubProduct::where('product_id', $product->id)
                                                ->whereIn('bill_id', $purchasedBillIds)
                                                ->where('flag', '!=', 0)
                                                ->whereNotNull('bill_id')
                                                ->sum('quantity') ?? 0;
                                        }

                                        $purchasedSubProductQty = $productPurchasedQtyCache[$product->id];
                                    
                                    $totalQty = $purchasedSubProductQty + $profitQty;
                                    if ($totalQty > 0) {
                                        $avgCost = (($purchasedSubProductQty * $lastAvg) + ($profitQty * $purchasePriceForItem)) / $totalQty;
                                    } else {
                                        $avgCost = $purchasePriceForItem;
                                    }
                                } else {
                                    $avgCost = $subProduct->purchase_price ?? 0;
                                }
                                
                                // Create stock movement
                                $stockMovement = new StockMovement();
                                $stockMovement->product_id = $product->id;
                                $stockMovement->sub_product_id = $subProduct->id;
                                $stockMovement->invoice_id = null;
                                $stockMovement->bill_id = null;
                                $stockMovement->pos_id = null;
                                $stockMovement->qty_in = $profitQty;
                                $stockMovement->qty_out = 0;
                                $stockMovement->avg_cost = $avgCost;
                                $stockMovement->cost_price = $purchasePriceForItem;
                                $stockMovement->activity = 'Stock Count Profit (Excel Import)';
                                $stockMovement->use_id = null;
                                $stockMovement->item = $subProduct->id;
                                $stockMovement->created_by = \Auth::user()->creatorId();
                                $stockMovement->save();
                                
                                $product->avg_cost = $avgCost;
                                $product->save();
                            }
                            
                            if ($purchaseAccountId) {
                                // Debit: Purchase Account
                                $purchaseEntry = new GeneralLedger();
                                $purchaseEntry->vid = $newVoucherId;
                                $purchaseEntry->account = $purchaseAccountId;
                                $purchaseEntry->type = 'Stock Count - ' . $warehouse->name;
                                $purchaseEntry->ref_number = 'Stock Count Profit (Excel Import) - ' . $warehouse->name;
                                $purchaseEntry->debit = $profitAmount;
                                $purchaseEntry->credit = 0;
                                $purchaseEntry->ref_id = $warehouse->id;
                                $purchaseEntry->user_id = 0;
                                $purchaseEntry->created_by = \Auth::user()->creatorId();
                                $purchaseEntry->send_date = now();
                                $purchaseEntry->reference = 'Stock Count Profit (Excel Import) - Product: ' . $product->name . ' (Qty: ' . $profitQty . ')';
                                $purchaseEntry->sub_product_id = $subProduct->id;
                                $purchaseEntry->deleted_qty = $profitQty;
                                $purchaseEntry->save();

                                // Credit: Profit on Count account
                                $profitEntry = new GeneralLedger();
                                $profitEntry->vid = $newVoucherId;
                                $profitEntry->account = $profitAccount->id;
                                $profitEntry->type = 'Stock Count - ' . $warehouse->name;
                                $profitEntry->ref_number = 'Stock Count Profit (Excel Import) - ' . $warehouse->name;
                                $profitEntry->debit = 0;
                                $profitEntry->credit = $profitAmount;
                                $profitEntry->ref_id = $warehouse->id;
                                $profitEntry->user_id = 0;
                                $profitEntry->created_by = \Auth::user()->creatorId();
                                $profitEntry->send_date = now();
                                $profitEntry->reference = 'Stock Count Profit (Excel Import) - Product: ' . $product->name . ' (Qty: ' . $profitQty . ')';
                                $profitEntry->sub_product_id = $subProduct->id;
                                $profitEntry->deleted_qty = $profitQty;
                                $profitEntry->save();
                            }
                        }

                        // Process loss (decrease)
                        if ($difference < 0 && $product && $product->category) {
                            $lossQty = abs($difference);
                            $lossAmount = $lossQty * $costPerUnit;
                            $purchaseAccountId = $product->category->purchase_account_id;
                            
                            $avgCost = null;
                            $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($subProduct->purchase_price ?? 0);
                            
                            if ($product->category->type === "Qty product") {
                                $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                                
                                    if ($costCalculationMethod === 'avg') {
                                        // Get purchased sub-product quantity for this product, cached
                                        if (!array_key_exists($product->id, $productPurchasedQtyCache)) {
                                            $productPurchasedQtyCache[$product->id] = SubProduct::where('product_id', $product->id)
                                                ->whereIn('bill_id', $purchasedBillIds)
                                                ->where('flag', '!=', 0)
                                                ->whereNotNull('bill_id')
                                                ->sum('quantity') ?? 0;
                                        }

                                        $purchasedSubProductQty = $productPurchasedQtyCache[$product->id];
                                    
                                    $purchasePriceForItem = $subProduct->purchase_price ?? 0;
                                    $remainingQty = $purchasedSubProductQty - $lossQty;
                                    if ($remainingQty > 0) {
                                        $avgCost = (($purchasedSubProductQty * $lastAvg) - ($lossQty * $lastAvg)) / $remainingQty;
                                    } else {
                                        $avgCost = 0;
                                    }
                                } else {
                                    $avgCost = $subProduct->purchase_price ?? 0;
                                }
                                
                                // Create stock movement
                                $stockMovement = new StockMovement();
                                $stockMovement->product_id = $product->id;
                                $stockMovement->sub_product_id = $subProduct->id;
                                $stockMovement->invoice_id = null;
                                $stockMovement->bill_id = null;
                                $stockMovement->pos_id = null;
                                $stockMovement->qty_in = 0;
                                $stockMovement->qty_out = $lossQty;
                                $stockMovement->avg_cost = $avgCost;
                                $stockMovement->cost_price = $lastAvg;
                                $stockMovement->activity = 'Stock Count Loss (Excel Import)';
                                $stockMovement->use_id = null;
                                $stockMovement->item = $subProduct->id;
                                $stockMovement->created_by = \Auth::user()->creatorId();
                                $stockMovement->save();
                                
                                $product->avg_cost = $avgCost;
                                $product->save();
                            }
                            
                            if ($purchaseAccountId) {
                                // Debit: Loss on Count account
                                $lossEntry = new GeneralLedger();
                                $lossEntry->vid = $newVoucherId;
                                $lossEntry->account = $lossAccount->id;
                                $lossEntry->type = 'Stock Count - ' . $warehouse->name;
                                $lossEntry->ref_number = 'Stock Count Loss (Excel Import) - ' . $warehouse->name;
                                $lossEntry->debit = $lossAmount;
                                $lossEntry->credit = 0;
                                $lossEntry->ref_id = $warehouse->id;
                                $lossEntry->user_id = 0;
                                $lossEntry->created_by = \Auth::user()->creatorId();
                                $lossEntry->send_date = now();
                                $lossEntry->reference = 'Stock Count Loss (Excel Import) - Product: ' . $product->name . ' (Qty: ' . $lossQty . ')';
                                $lossEntry->sub_product_id = $subProduct->id;
                                $lossEntry->deleted_qty = $lossQty;
                                $lossEntry->save();

                                // Credit: Purchase Account
                                $purchaseEntry = new GeneralLedger();
                                $purchaseEntry->vid = $newVoucherId;
                                $purchaseEntry->account = $purchaseAccountId;
                                $purchaseEntry->type = 'Stock Count - ' . $warehouse->name;
                                $purchaseEntry->ref_number = 'Stock Count Loss (Excel Import) - ' . $warehouse->name;
                                $purchaseEntry->debit = 0;
                                $purchaseEntry->credit = $lossAmount;
                                $purchaseEntry->ref_id = $warehouse->id;
                                $purchaseEntry->user_id = 0;
                                $purchaseEntry->created_by = \Auth::user()->creatorId();
                                $purchaseEntry->send_date = now();
                                $purchaseEntry->reference = 'Stock Count Loss (Excel Import) - Product: ' . $product->name . ' (Qty: ' . $lossQty . ')';
                                $purchaseEntry->sub_product_id = $subProduct->id;
                                $purchaseEntry->deleted_qty = $lossQty;
                                $purchaseEntry->save();
                            }
                        }

                        // Mark main product for quantity recalculation at the end
                        if ($subProduct && $product) {
                            $productId = $subProduct->product_id;
                            $productsToRecalculate[$productId] = true;
                        }

                        $warehouseSuccessCount++;
                        $successCount++;
                    }

                    // Recalculate main product quantities once per affected product
                    if (!empty($productsToRecalculate)) {
                        foreach (array_keys($productsToRecalculate) as $productId) {
                            $mainProduct = ProductService::find($productId);
                            if ($mainProduct) {
                                $totalQty = SubProduct::where('product_id', $productId)->sum('quantity');
                                $mainProduct->quantity = $totalQty;
                                $mainProduct->save();
                            }
                        }
                    }

                    if (!empty($snapshotLinesForWarehouse)) {
                        foreach (array_chunk($snapshotLinesForWarehouse, 500) as $chunk) {
                            WarehouseStockCountImportLine::insert($chunk);
                        }
                        $totalSnapshotLines += count($snapshotLinesForWarehouse);
                    }

                    DB::commit();

                    // Increment voucher ID for next warehouse
                    $currentVoucherId++;

                    // Log stock count operation
                    if ($hasChanges && !empty($warehouseChanges)) {
                        foreach ($warehouseChanges as $change) {
                            PosLog::logAction('stock_count', [
                                'type' => 'warehouse',
                                'reference_id' => $warehouse->id,
                                'warehouse_id' => $warehouse->id,
                                'product_id' => $change['product_id'],
                                'product_no' => $change['product_no'],
                                'quantity' => $change['difference'],
                                'old_value' => [
                                    'quantity' => $change['old_quantity'],
                                    'product_no' => $change['product_no'],
                                    'product_name' => $change['product_name'],
                                ],
                                'new_value' => [
                                    'quantity' => $change['new_quantity'],
                                    'product_no' => $change['product_no'],
                                    'product_name' => $change['product_name'],
                                    'difference' => $change['difference'],
                                ],
                                'description' => "Stock count (Excel Import): Product #{$change['product_no']} ({$change['product_name']}) changed from {$change['old_quantity']} to {$change['new_quantity']} (difference: {$change['difference']})",
                            ]);
                        }
                        
                        $totalProductsChanged = count($warehouseChanges);
                        $totalProfit = array_sum(array_column(array_filter($warehouseChanges, function($c) { return $c['difference'] > 0; }), 'difference'));
                        $totalLoss = abs(array_sum(array_column(array_filter($warehouseChanges, function($c) { return $c['difference'] < 0; }), 'difference')));
                        
                        PosLog::logAction('stock_count_summary', [
                            'type' => 'warehouse',
                            'reference_id' => $warehouse->id,
                            'warehouse_id' => $warehouse->id,
                            'new_value' => [
                                'total_products_changed' => $totalProductsChanged,
                                'total_profit_qty' => $totalProfit,
                                'total_loss_qty' => $totalLoss,
                                'products' => $warehouseChanges,
                                'import_method' => 'excel',
                            ],
                            'description' => "Stock count (Excel Import) completed for warehouse '{$warehouse->name}': {$totalProductsChanged} product(s) changed. Profit: {$totalProfit}, Loss: {$totalLoss}",
                        ]);
                    }

                    $results[$warehouse->name] = [
                        'success' => $warehouseSuccessCount,
                        'errors' => $warehouseErrorCount,
                    ];

                } catch (\Exception $e) {
                    DB::rollBack();
                    $warehouseErrorCount++;
                    $errorCount++;
                    $errors[] = "Error processing warehouse '{$warehouse->name}': " . $e->getMessage();
                    \Log::error('Stock count import failed for warehouse', [
                        'error' => $e->getMessage(),
                        'warehouse_id' => $warehouseId,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'user_id' => \Auth::user()->creatorId()
                    ]);
                }
            }

            if (!empty($stockCountImport)) {
                $stockCountImport->update([
                    'line_count' => $totalSnapshotLines,
                    'error_count' => $errorCount,
                ]);
            }

            // Build success message
            $message = __('Stock count import completed.') . ' ';
            $message .= __('Successfully processed: :count product(s).', ['count' => $successCount]);
            
            if ($errorCount > 0) {
                $message .= ' ' . __('Errors: :count', ['count' => $errorCount]);
            }

            if (!empty($errors)) {
                $message .= "\n\n" . __('Errors encountered:') . "\n" . implode("\n", array_slice($errors, 0, 20));
                if (count($errors) > 20) {
                    $message .= "\n" . __('... and :more more errors', ['more' => count($errors) - 20]);
                }
            }

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'errors' => $errors,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'redirect' => route('warehouse.index')
                ]);
            }

            return redirect()->route('warehouse.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            \Log::error('Stock count import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            $errorMessage = __('Import failed: ') . $e->getMessage();
            
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Show import stock count form for a single warehouse
     */
    public function showImportStockCountSingle(warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            $errorMsg = __('Permission denied.');
            // Handle AJAX requests properly
            if (request()->ajax() || request()->wantsJson() || request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            $errorMsg = __('Permission denied.');
            // Handle AJAX requests properly
            if (request()->ajax() || request()->wantsJson() || request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        return view('warehouse.import_stock_count_single', compact('warehouse'));
    }

    /**
     * Process stock count import from Excel for a single warehouse
     */
    public function importStockCountSingle(Request $request, warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            $errorMsg = __('Permission denied.');
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            $errorMsg = __('Permission denied.');
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 403);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        // Allow long run for large Excel files (e.g. 4000+ rows)
        set_time_limit(600);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        // Validate file
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:' . (ini_get('upload_max_filesize') ? (int)ini_get('upload_max_filesize') * 1024 : 10240), // Default 10MB
            ], [
                'file.required' => __('Please select a file to import.'),
                'file.mimes' => __('File must be Excel (.xlsx, .xls) or CSV format.'),
                'file.max' => __('File size exceeds maximum allowed size.'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            // Use Arr::flatten for Laravel 6+ compatibility
            $flattenedErrors = [];
            foreach ($errors as $key => $value) {
                if (is_array($value)) {
                    $flattenedErrors = array_merge($flattenedErrors, $value);
                } else {
                    $flattenedErrors[] = $value;
                }
            }
            $errorMsg = __('Validation failed: ') . implode(', ', $flattenedErrors);
            
            \Log::warning('Single warehouse import - Validation failed', [
                'errors' => $errors,
                'warehouse_id' => $warehouse->id,
                'is_ajax' => $request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest'
            ]);
            
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'errors' => $errors
                ], 422);
            }
            return redirect()->back()->withErrors($errors)->with('error', $errorMsg);
        }

        try {
            // Increase memory and time limits for large files (server-specific)
            // For 7579+ rows, we need more memory and time
            ini_set('memory_limit', '2048M'); // 2GB for large imports
            set_time_limit(0); // Unlimited time for large imports
            ini_set('max_execution_time', 0); // Also set max execution time
            
            // Disable query logging for performance
            \DB::connection()->disableQueryLog();
            
            // Read Excel file
            $file = $request->file('file');
            
            if (!$file || !$file->isValid()) {
                $errorMsg = __('File upload failed or file is invalid.');
                \Log::error('Single warehouse import - File upload validation failed', [
                    'has_file' => $request->hasFile('file'),
                    'file_valid' => $file ? $file->isValid() : false,
                    'error' => $file ? $file->getError() : 'no file',
                    'warehouse_id' => $warehouse->id
                ]);
                
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }
            
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Use temporary file path - more reliable on servers
            $path = $file->getRealPath();
            if (!$path || !file_exists($path)) {
                // Fallback: store temporarily
                $tempPath = $file->storeAs('temp', 'import_' . time() . '_' . $file->getClientOriginalName());
                $path = storage_path('app/' . $tempPath);
            }
            
            \Log::info('Single warehouse import - Reading Excel file', [
                'extension' => $extension,
                'path' => $path,
                'file_exists' => file_exists($path),
                'file_size' => file_exists($path) ? filesize($path) : 0,
                'warehouse_id' => $warehouse->id
            ]);

            // Read Excel file into array
            try {
                $readerType = $extension === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
                
                // Wrap in try-catch with detailed error handling
                try {
                    $data = Excel::toArray([], $path, null, $readerType);
                } catch (\PhpOffice\PhpSpreadsheet\Exception $spreadsheetEx) {
                    \Log::error('Single warehouse import - PhpSpreadsheet exception', [
                        'error' => $spreadsheetEx->getMessage(),
                        'code' => $spreadsheetEx->getCode(),
                        'path' => $path,
                        'extension' => $extension
                    ]);
                    throw $spreadsheetEx;
                } catch (\Exception $readEx) {
                    \Log::error('Single warehouse import - Excel read exception', [
                        'error' => $readEx->getMessage(),
                        'class' => get_class($readEx),
                        'path' => $path,
                        'extension' => $extension
                    ]);
                    throw $readEx;
                }
                
                \Log::info('Single warehouse import - Excel read completed', [
                    'data_is_array' => is_array($data),
                    'data_count' => is_array($data) ? count($data) : 0,
                    'first_sheet_exists' => (is_array($data) && isset($data[0])),
                    'first_sheet_rows' => (is_array($data) && isset($data[0])) ? count($data[0]) : 0,
                    'warehouse_id' => $warehouse->id
                ]);
            } catch (\Exception $ex) {
                \Log::error('Single warehouse import - Excel read failed', [
                    'error' => $ex->getMessage(),
                    'error_class' => get_class($ex),
                    'path' => $path,
                    'extension' => $extension,
                    'file_exists' => file_exists($path),
                    'file_readable' => file_exists($path) ? is_readable($path) : false,
                    'trace' => $ex->getTraceAsString(),
                    'warehouse_id' => $warehouse->id
                ]);
                
                $errorMsg = __('Failed to read Excel file: :error', ['error' => $ex->getMessage()]);
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['success' => false, 'message' => $errorMsg], 500);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            if (empty($data) || empty($data[0])) {
                \Log::warning('Single warehouse import - Empty data after read', [
                    'data_empty' => empty($data),
                    'data_0_empty' => (isset($data[0]) && empty($data[0])),
                    'warehouse_id' => $warehouse->id
                ]);
                
                $errorMsg = __('Excel file is empty or invalid.');
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            $rows = $data[0];
            
            \Log::info('Single warehouse import - Processing rows', [
                'total_rows' => count($rows),
                'warehouse_id' => $warehouse->id
            ]);
            
            // First row should be headers
            if (empty($rows)) {
                $errorMsg = __('Excel file has no data.');
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            $headers = array_map('trim', $rows[0]);
            
            // Find column indices
            $productNoColumnIndex = null;
            $qtyColumnIndex = null;
            $qtyCandidateByHeaderScore = [];
            
            foreach ($headers as $index => $header) {
                $headerLower = strtolower(trim($header));
                if (strpos($headerLower, 'product') !== false && strpos($headerLower, 'no') !== false) {
                    $productNoColumnIndex = $index;
                }
                // Score qty column candidates by header keywords (avoid price/cost columns)
                $score = 0;
                if (strpos($headerLower, 'qty') !== false || strpos($headerLower, 'quantity') !== false) {
                    $score += 10;
                }
                if (strpos($headerLower, 'count') !== false || strpos($headerLower, 'stock') !== false || strpos($headerLower, 'inventory') !== false) {
                    $score += 6;
                }
                if (strpos($headerLower, 'new') !== false) {
                    $score += 2;
                }
                if (strpos($headerLower, 'price') !== false || strpos($headerLower, 'cost') !== false || strpos($headerLower, 'amount') !== false || strpos($headerLower, 'purchase') !== false) {
                    $score -= 20;
                }
                if ($score > 0) {
                    $qtyCandidateByHeaderScore[$index] = $score;
                }
            }

            // Default to first two columns if headers not found
            if ($productNoColumnIndex === null) {
                $productNoColumnIndex = 0;
            }
            if ($qtyColumnIndex === null) {
                if (!empty($qtyCandidateByHeaderScore)) {
                    arsort($qtyCandidateByHeaderScore);
                    $qtyColumnIndex = (int) array_key_first($qtyCandidateByHeaderScore);
                } else {
                    // Fallback heuristic: choose the column (not product_no) with most numeric values in first 100 rows,
                    // excluding columns whose header looks like price/cost.
                    $maxScanRows = min(100, count($rows) - 1);
                    $numericCounts = [];
                    for ($r = 1; $r <= $maxScanRows; $r++) {
                        $row = $rows[$r] ?? [];
                        foreach ($row as $c => $val) {
                            if ($c == $productNoColumnIndex) continue;
                            $h = strtolower(trim((string)($headers[$c] ?? '')));
                            if (strpos($h, 'price') !== false || strpos($h, 'cost') !== false || strpos($h, 'amount') !== false || strpos($h, 'purchase') !== false) {
                                continue;
                            }
                            $v = is_string($val) ? trim($val) : $val;
                            if ($v === null || $v === '') continue;
                            if (is_numeric($v)) {
                                $numericCounts[$c] = ($numericCounts[$c] ?? 0) + 1;
                            }
                        }
                    }
                    if (!empty($numericCounts)) {
                        arsort($numericCounts);
                        $qtyColumnIndex = (int) array_key_first($numericCounts);
                    } else {
                        $qtyColumnIndex = 1;
                    }
                }
            }

            // Re-read product_no column as formatted string so leading zeros are preserved (e.g. "03355807" not 3355807).
            // This fixes under-reported "not found" when Excel stores barcodes as number and we wrongly match DB "3355807".
            $totalRows = count($rows);
            try {
                $colLetter = Coordinate::stringFromColumnIndex($productNoColumnIndex + 1);
                $spreadsheet = IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $maxRow = min($sheet->getHighestRow(), $totalRows);
                for ($r = 2; $r <= $maxRow; $r++) {
                    $cell = $sheet->getCell($colLetter . $r);
                    $formatted = $cell->getFormattedValue();
                    if ($formatted !== null && $formatted !== '') {
                        $rows[$r - 1][$productNoColumnIndex] = is_string($formatted) ? $formatted : (string) $formatted;
                    }
                }
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (\Throwable $e) {
                \Log::warning('Single warehouse import - Could not re-read product_no as formatted', [
                    'error' => $e->getMessage(),
                    'warehouse_id' => $warehouse->id
                ]);
            }

            // Process each row (skip header row)
            $importedData = [];
            $errors = [];
            $errorItems = []; // Store structured error items for export
            $successCount = 0;
            $errorCount = 0;
            $creatorId = \Auth::user()->creatorId();

            // Collect unique product_nos from Excel (exact values only). Do NOT normalize leading zeros:
            // "0123" and "123" must stay different so we don't wrongly match 55→42 not-found items.
            $productNosInExcel = [];
            $totalRows = count($rows);
            for ($i = 1; $i < $totalRows; $i++) {
                $row = $rows[$i];
                $raw = $row[$productNoColumnIndex] ?? '';
                $pn = trim((string) $raw);
                if ($pn === '') {
                    continue;
                }
                // Only strip trailing .0 from decimals (Excel float), never convert "0123" -> "123"
                if (is_numeric($pn) && strpos($pn, '.') !== false) {
                    $pn = (string) (int) (float) $pn;
                }
                $productNosInExcel[$pn] = true;
            }
            $productNosInExcel = array_keys($productNosInExcel);

            // Load only sub_products for these exact product_nos (chunked); cache key = exact DB value.
            $subProductsCache = collect();
            $chunkSize = 500;
            foreach (array_chunk($productNosInExcel, $chunkSize) as $chunk) {
                $chunkSubs = SubProduct::where('warehouse_id', $warehouse->id)
                    ->where('created_by', $creatorId)
                    ->whereIn('chassis_no', $chunk)
                    ->get();
                foreach ($chunkSubs as $sub) {
                    $dbPn = trim((string) $sub->product_no);
                    if (is_numeric($dbPn) && strpos($dbPn, '.') !== false) {
                        $dbPn = (string) (int) (float) $dbPn;
                    }
                    $subProductsCache->put($dbPn, $sub);
                }
            }

            \Log::info('Single warehouse import - Starting row processing', [
                'total_rows' => $totalRows,
                'rows_to_process' => $totalRows - 1,
                'product_no_column' => $productNoColumnIndex,
                'qty_column' => $qtyColumnIndex,
                'warehouse_id' => $warehouse->id,
                'cached_sub_products' => $subProductsCache->count()
            ]);

            // Process in batches to manage memory
            $batchSize = 500; // Process 500 rows at a time

            for ($i = 1; $i < $totalRows; $i++) {
                // Memory management: clear variables periodically
                if ($i % $batchSize === 0) {
                    // Force garbage collection every batch
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                    \Log::info('Single warehouse import - Processed batch', [
                        'rows_processed' => $i,
                        'total_rows' => $totalRows,
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
                    ]);
                }
                
                $row = $rows[$i];

                // Normalize product_no: same format as cache (Excel may return number as int/float)
                $rawProductNo = $row[$productNoColumnIndex] ?? '';
                $productNo = trim((string) $rawProductNo);
                // Report empty product_no as error so it appears in the error export (don't skip silently)
                if ($productNo === '') {
                    $errorItems[] = [
                        'row' => $i + 1,
                        'product_no' => '',
                        'quantity' => isset($row[$qtyColumnIndex]) ? trim($row[$qtyColumnIndex]) : '',
                        'error_type' => 'empty_product_no',
                        'error_message' => __('Row :row: Product No is empty.', ['row' => $i + 1])
                    ];
                    $errorCount++;
                    if ($errorCount < 1000) {
                        $errors[] = __('Row :row: Product No is empty.', ['row' => $i + 1]);
                    }
                    continue;
                }
                // Only strip trailing .0 (Excel float); never convert "0123" to "123" (would cause false matches)
                if (is_numeric($productNo) && strpos($productNo, '.') !== false) {
                    $productNo = (string) (int) (float) $productNo;
                }
                $quantity = isset($row[$qtyColumnIndex]) ? trim($row[$qtyColumnIndex]) : null;

                // Skip if quantity is empty or not numeric
                if ($quantity === null || $quantity === '' || !is_numeric($quantity)) {
                    // Limit in-page error messages to prevent memory issues; always add to errorItems for full Excel export
                    if ($errorCount < 1000) {
                        $errors[] = __('Row :row: Product No ":product_no" has invalid quantity.', [
                            'row' => $i + 1,
                            'product_no' => $productNo
                        ]);
                    }
                    $errorItems[] = [
                        'row' => $i + 1,
                        'product_no' => $productNo,
                        'quantity' => $quantity,
                        'error_type' => 'invalid_quantity',
                        'error_message' => __('Product No ":product_no" has invalid quantity.', ['product_no' => $productNo])
                    ];
                    $errorCount++;
                    continue;
                }

                $newQty = (int)$quantity;

                // Use cached sub-product lookup: exact product_no match only (no leading-zero fallback)
                $subProduct = $subProductsCache->get($productNo);

                if (!$subProduct) {
                    // Limit in-page error messages to prevent memory issues; always add to errorItems for full Excel export
                    if ($errorCount < 1000) {
                        $errors[] = __('Row :row: Product No ":product_no" not found in warehouse ":warehouse".', [
                            'row' => $i + 1,
                            'product_no' => $productNo,
                            'warehouse' => $warehouse->name
                        ]);
                    }
                    $errorItems[] = [
                        'row' => $i + 1,
                        'product_no' => $productNo,
                        'quantity' => $newQty,
                        'error_type' => 'not_found',
                        'error_message' => __('Product No ":product_no" not found in warehouse ":warehouse".', [
                            'product_no' => $productNo,
                            'warehouse' => $warehouse->name
                        ])
                    ];
                    $errorCount++;
                    continue; // Continue processing other products
                }

                // Store imported quantity for display.
                // If the same Product No appears multiple times in the Excel,
                // treat it as "final quantity" (last row wins).
                // Use the DB product_no as the key (preserves leading zeros and avoids mismatches later).
                // Track duplicates: Excel may contain the same product_no multiple times (last row wins).
                $dbProductNo = trim((string) $subProduct->chassis_no);
                if (isset($importedData[$dbProductNo])) {
                    // Duplicate row for same barcode
                    // Keep last quantity, just count duplicates for logging
                    $duplicateRows = ($duplicateRows ?? 0) + 1;
                }
                if (!isset($importedData[$dbProductNo])) {
                    $successCount++;
                }

                $importedData[$dbProductNo] = [
                    'quantity' => $newQty,
                    'sub_product_id' => $subProduct->id,
                ];
            }
            
            // Clear cache to free memory
            unset($subProductsCache);
            
            \Log::info('Single warehouse import - Row processing finished', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'duplicate_rows' => (int) ($duplicateRows ?? 0),
                'imported_data_count' => count($importedData),
                'warehouse_id' => $warehouse->id
            ]);

            $bulkImportLimit = 200;
            $isLargeImport = count($importedData) > $bulkImportLimit;
            $token = $isLargeImport ? (string) Str::uuid() : null;

            if (!empty($importedData)) {
                app(StockCountImportSnapshotService::class)->recordSingleWarehouseSnapshot(
                    $importedData,
                    $warehouse,
                    $file->getClientOriginalName(),
                    $creatorId,
                    \Auth::id(),
                    $errorCount,
                    $token
                );
            }

            if ($isLargeImport) {
                // Large import: apply in background job (prevents request timeout/refresh before completing)
                $cacheKey = 'stock_count_import_data:' . $token;
                Cache::put($cacheKey, $importedData, now()->addHours(2));
                Cache::put('stock_count_import_status:' . $token, [
                    'status' => 'queued',
                    'progress' => 0,
                    'message' => 'Queued',
                    'updated_at' => now()->toDateTimeString(),
                ], now()->addHours(2));

                // Persist import errors so stock count page can show "barcodes not found" and offer Download Errors Excel
                if (!is_array($errorItems)) {
                    $errorItems = [];
                }
                session()->put('import_error_items_' . $warehouse->id, $errorItems);

                // afterResponse prevents browser/proxy timeout even if queue is sync
                ApplyStockCountImportJob::dispatch($token, (int) $warehouse->id, (int) \Auth::user()->creatorId(), $cacheKey)->afterResponse();

                $redirectUrl = route('warehouse.stock-count', $warehouse->id) . '?import_job=' . urlencode($token);
                $message = __('Import received. Applying stock count in background for :count item(s).', ['count' => $successCount]);
                if ($errorCount > 0) {
                    $message .= ' ' . __(':errors barcode(s) not found or invalid — see errors below and download Excel.', ['errors' => $errorCount]);
                }

                if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'error_items' => $errorItems,
                        'redirect' => $redirectUrl
                    ], 200);
                }
                return redirect($redirectUrl)
                    ->with('success', $message)
                    ->with('import_error_count', $errorCount)
                    ->with('import_errors', $errors);
            }

            // Small import: store in session and show import items in UI (user can review and save)
            session()->flash('imported_data', $importedData);
            session()->flash('import_errors', $errors);
            if (!is_array($errorItems)) {
                $errorItems = [];
            }
            session()->put('import_error_items_' . $warehouse->id, $errorItems);
            session()->flash('import_success_count', $successCount);
            session()->flash('import_error_count', $errorCount);
            session()->flash('import_error_items', $errorItems);

            \Log::info('Single warehouse import - Processing completed (small import, show in UI)', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'warehouse_id' => $warehouse->id
            ]);

            $isAjax = $request->ajax()
                || $request->wantsJson()
                || $request->expectsJson()
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->header('Accept') === 'application/json'
                || strpos($request->header('Accept', ''), 'application/json') !== false;

            if ($isAjax) {
                $redirectUrl = route('warehouse.stock-count', $warehouse->id);
                $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'auto_save=1';
                if ($request->query('debug_stock_count') == '1' || $request->input('debug_stock_count') == '1') {
                    $redirectUrl .= '&debug_stock_count=1';
                }
                session()->put('import_error_items_' . $warehouse->id, $errorItems);
                return response()->json([
                    'success' => true,
                    'message' => __('Import completed. :success successful, :errors errors.', ['success' => $successCount, 'errors' => $errorCount]),
                    'errors' => $errors,
                    'error_items' => $errorItems,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'redirect' => $redirectUrl
                ], 200);
            }

            $redirectUrl = route('warehouse.stock-count', $warehouse->id);
            $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'auto_save=1';
            if ($request->query('debug_stock_count') == '1' || $request->input('debug_stock_count') == '1') {
                $redirectUrl .= '&debug_stock_count=1';
            }
            session()->put('import_error_items_' . $warehouse->id, $errorItems);
            return redirect($redirectUrl)
                ->with('success', __('Import completed. :success successful, :errors errors.', ['success' => $successCount, 'errors' => $errorCount]))
                ->with('imported_data', $importedData)
                ->with('import_errors', $errors)
                ->with('import_error_items', $errorItems)
                ->with('import_success_count', $successCount)
                ->with('import_error_count', $errorCount);

        } catch (\Exception $e) {
            \Log::error('Single warehouse stock count import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'warehouse_id' => $warehouse->id,
                'user_id' => \Auth::user()->creatorId(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = __('Import failed: ') . $e->getMessage();
            
            // Handle AJAX/JSON requests - check multiple ways to detect AJAX
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Export stock count import errors to Excel
     */
    public function exportStockCountErrors(Request $request, warehouse $warehouse)
    {
        if (!\Auth::user()->can('edit warehouse')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($warehouse->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Get error items from persistent session (keyed by warehouse ID)
        $errorItems = session('import_error_items_' . $warehouse->id, []);
        
        // Also try flash data as fallback
        if (empty($errorItems)) {
            $errorItems = session('import_error_items', []);
        }

        // Debug: Check all session keys related to import
        $sessionKey = 'import_error_items_' . $warehouse->id;
        $allSessionKeys = array_keys(session()->all());
        $relatedKeys = array_filter($allSessionKeys, function($key) {
            return strpos($key, 'import_error_items') !== false || strpos($key, 'import') !== false;
        });
        
        \Log::info('Export stock count errors - Checking session', [
            'warehouse_id' => $warehouse->id,
            'error_items_count' => count($errorItems),
            'has_persistent' => !empty(session($sessionKey)),
            'has_flash' => !empty(session('import_error_items')),
            'session_key' => $sessionKey,
            'all_import_keys' => array_values($relatedKeys),
            'error_items_sample' => !empty($errorItems) ? array_slice($errorItems, 0, 3) : []
        ]);

        if (empty($errorItems)) {
            return redirect()->back()->with('error', __('No error items to export.'));
        }

        try {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $fileName = 'stock_count_errors_' . $warehouse->id . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Clear the error items from session after export (optional - comment out if you want to keep them)
            // session()->forget('import_error_items_' . $warehouse->id);
            
            return Excel::download(
                new \App\Exports\StockCountErrorsExport($errorItems),
                $fileName
            );
        } catch (\Exception $e) {
            \Log::error('Stock count errors export failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'warehouse_id' => $warehouse->id,
                'user_id' => \Auth::user()->creatorId()
            ]);

            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }
}
