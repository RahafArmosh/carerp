<?php

namespace App\Http\Controllers;

use App\Models\ComboOffer;
use App\Models\ProductService;
use App\Models\warehouse;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\SubProduct;
use App\Models\PosLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\PseudoTypes\True_;
use Carbon\Carbon;

class ComboOfferController extends Controller
{
    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view combo'))
        {
            $comboOffers = ComboOffer::where('created_by', \Auth::user()->creatorId())
                ->with(['productService', 'products', 'brand', 'subBrand', 'warehouse'])
                ->paginate(10);
            return view('combo_offers.index', compact('comboOffers'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create combo'))
        {
            // Small lists only; heavy data (sub-brands, products) are loaded lazily via AJAX
            $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
            $brands     = Brand::where('created_by',\Auth::user()->creatorId())->get();
            $subBrands  = collect();   // no eager load of all sub-brands
            $products   = collect();   // no eager load of all products

            return view('combo_offers.create', compact('warehouses', 'brands', 'subBrands', 'products'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create combo'))
        {
            $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sub_brand_id' => 'nullable|exists:sub_brands,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:product_services,id',
            'type' => 'required|in:bogo,tiered_pricing',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:0',
            'tiered_price' => 'nullable|numeric|min:0',
            'valid_until' => 'nullable|date|after_or_equal:today',
            'active' => 'nullable',
        ]);

            $validated['active'] = $request->has('active');
            $validated['created_by'] = \Auth::user()->creatorId();
            
            // Extract product_ids before creating
            $productIds = $validated['product_ids'];
            unset($validated['product_ids']);

            $comboOffer = ComboOffer::create($validated);
            
            // Attach products
            $comboOffer->products()->attach($productIds);
            
            // Log combo offer creation
            PosLog::logAction('create_combo', [
                'type' => 'combo',
                'reference_id' => $comboOffer->id,
                'warehouse_id' => $validated['warehouse_id'],
                'new_value' => [
                    'id' => $comboOffer->id,
                    'warehouse_id' => $comboOffer->warehouse_id,
                    'type' => $comboOffer->type,
                    'buy_quantity' => $comboOffer->buy_quantity,
                    'get_quantity' => $comboOffer->get_quantity,
                    'tiered_price' => $comboOffer->tiered_price,
                    'product_ids' => $productIds,
                ],
                'description' => "Created combo offer ID {$comboOffer->id} ({$comboOffer->type})",
            ]);

            return redirect()->route('combo_offers.index')->with('success', 'Combo offer created.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit(ComboOffer $comboOffer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update combo'))
        {
            if ($comboOffer->created_by != \Auth::user()->creatorId()) {
                return redirect()->route('combo_offers.index')->with('error', 'Permission denied.');
            }
            
            $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
            $brands = Brand::where('created_by',\Auth::user()->creatorId())->get();
            $subBrands = VehicleModel::where('created_by',\Auth::user()->creatorId())
                ->get();
            $products = ProductService::where('created_by',\Auth::user()->creatorId())->get();
            
            // Load the selected products
            $comboOffer->load('products');
            
            // Get logs related to this combo
            $logs = PosLog::where('type', 'combo')
                ->where('reference_id', $comboOffer->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('combo_offers.edit', compact('comboOffer', 'warehouses', 'brands', 'subBrands', 'products', 'logs'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Lazily fetch products for combo-offer creation/edit, filtered by warehouse / brand / sub-brand and optional search.
     */
    public function getProductsForCombo(Request $request)
    {
        $hasComboPermission = \Auth::user()->type == 'company' || \Auth::user()->can('view combo') || \Auth::user()->can('create combo');
        if (!$hasComboPermission) {
            return response()->json([
                'success' => false,
                'error' => __('Permission denied.')
            ], 403);
        }

        $warehouseId = $request->input('warehouse_id');
        if (!$warehouseId) {
            return response()->json([
                'success' => false,
                'message' => __('Warehouse ID is required')
            ], 400);
        }

        $brandId = $request->input('brand_id');
        $subBrandId = $request->input('sub_brand_id');
        $search = trim($request->input('q', ''));

        $creatorId = \Auth::user()->creatorId();

        // Start from products that actually have stock in this warehouse
        $productIdsInWarehouse = SubProduct::where('warehouse_id', $warehouseId)
            ->where('created_by', $creatorId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->toArray();

        if (empty($productIdsInWarehouse)) {
            return response()->json([
                'success' => true,
                'products' => [],
            ]);
        }

        $query = ProductService::whereIn('id', $productIdsInWarehouse)
            ->where('created_by', $creatorId);

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }
        if ($subBrandId) {
            $query->where('sub_brand_id', $subBrandId);
        }
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        // Limit for performance; front-end can request again with search
        $products = $query
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'sku', 'brand_id', 'sub_brand_id']);

        $formatted = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'brand_id' => $p->brand_id,
                'sub_brand_id' => $p->sub_brand_id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'products' => $formatted,
        ]);
    }

    public function update(Request $request, ComboOffer $comboOffer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update combo'))
        {
            if ($comboOffer->created_by != \Auth::user()->creatorId()) {
                return redirect()->route('combo_offers.index')->with('error', 'Permission denied.');
            }
            
            if ($request['type'] =='bogo' ){
            $vlist = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sub_brand_id' => 'nullable|exists:sub_brands,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:product_services,id',
            'type' => 'required|in:bogo,tiered_pricing',
            'buy_quantity' => 'required|integer|min:1',
            'get_quantity' => 'required|integer|min:0',
            'tiered_price' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date|after_or_equal:today',
            'active' => 'nullable',
            ];
        }else{
            $vlist = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sub_brand_id' => 'nullable|exists:sub_brands,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:product_services,id',
            'type' => 'required|in:bogo,tiered_pricing',
            'buy_quantity' => 'required|integer|min:1',
            'get_quantity' => 'nullable|integer|min:0',
            'tiered_price' => 'required|numeric|min:0',
            'valid_until' => 'required|date|after_or_equal:today',
            'active' => 'nullable',
            ];
            }

            $validated = $request->validate($vlist);

            $validated['active'] = $request->has('active');
            
            // Extract product_ids before updating
            $productIds = $validated['product_ids'];
            unset($validated['product_ids']);

            // Store old values for logging
            $oldValues = [
                'warehouse_id' => $comboOffer->warehouse_id,
                'type' => $comboOffer->type,
                'buy_quantity' => $comboOffer->buy_quantity,
                'get_quantity' => $comboOffer->get_quantity,
                'tiered_price' => $comboOffer->tiered_price,
                'product_ids' => $comboOffer->products->pluck('id')->toArray(),
            ];
            
            $comboOffer->update($validated);
            
            // Sync products
            $comboOffer->products()->sync($productIds);
            
            // Log combo offer update
            PosLog::logAction('update_combo', [
                'type' => 'combo',
                'reference_id' => $comboOffer->id,
                'warehouse_id' => $comboOffer->warehouse_id,
                'old_value' => $oldValues,
                'new_value' => [
                    'id' => $comboOffer->id,
                    'warehouse_id' => $comboOffer->warehouse_id,
                    'type' => $comboOffer->type,
                    'buy_quantity' => $comboOffer->buy_quantity,
                    'get_quantity' => $comboOffer->get_quantity,
                    'tiered_price' => $comboOffer->tiered_price,
                    'product_ids' => $productIds,
                ],
                'description' => "Updated combo offer ID {$comboOffer->id}",
            ]);

            return redirect()->route('combo_offers.index')->with('success', 'Combo offer updated.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(ComboOffer $comboOffer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete combo'))
        {
            if ($comboOffer->created_by != \Auth::user()->creatorId()) {
                return redirect()->route('combo_offers.index')->with('error', 'Permission denied.');
            }
            
            // Log combo offer deletion before deleting
            PosLog::logAction('delete_combo', [
                'type' => 'combo',
                'reference_id' => $comboOffer->id,
                'warehouse_id' => $comboOffer->warehouse_id,
                'old_value' => [
                    'id' => $comboOffer->id,
                    'warehouse_id' => $comboOffer->warehouse_id,
                    'type' => $comboOffer->type,
                    'buy_quantity' => $comboOffer->buy_quantity,
                    'get_quantity' => $comboOffer->get_quantity,
                    'tiered_price' => $comboOffer->tiered_price,
                    'product_ids' => $comboOffer->products->pluck('id')->toArray(),
                ],
                'description' => "Deleted combo offer ID {$comboOffer->id}",
            ]);
            
            $comboOffer->delete();
            return redirect()->route('combo_offers.index')->with('success', 'Combo offer deleted.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Check for multi-product combo offers that can be applied to the cart
     */
    public function checkMultiProduct(Request $request)
    {
        // Allow access if user has 'manage pos' or 'add pos' permission (same as POS page)
        $hasPosPermission = \Auth::user()->can('manage pos') || \Auth::user()->can('add pos');
        if (!$hasPosPermission) {
            return response()->json([
                'success' => false,
                'error' => __('Permission denied.'),
                'applicable_combos' => [],
                'updated_cart' => []
            ], 403);
        }

        $productIds = $request->input('product_ids', []);
        $requestedWarehouseId = $request->input('warehouse_id');
        $cartData = $request->input('cart_data', []);
        $sessionKey = $request->input('session_key', 'pos');

        $user = \Auth::user();
        $creatorId = $user->creatorId();
        
        // Validate warehouse access and get correct warehouse ID
        $warehouseId = $requestedWarehouseId;
        $hasWarehouseAccess = false;
        
        if ($warehouseId) {
            $warehouse = \App\Models\warehouse::where('id', '=', $warehouseId)->first();
            
            if ($warehouse) {
                // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
                $isAssigned = \DB::table('user_warehouses')
                    ->where('user_id', $user->id)
                    ->where('warehouse_id', $warehouseId)
                    ->exists();
                
                if ($isAssigned) {
                    $hasWarehouseAccess = true;
                }
                
                // Option 2: Warehouse belongs to the same company
                if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                    $hasWarehouseAccess = true;
                }
                
                // Option 3: User is company type
                if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                    if ($warehouse->created_by == $creatorId) {
                        $hasWarehouseAccess = true;
                    }
                }
                
                // If no access, use first assigned warehouse as fallback
                if (!$hasWarehouseAccess) {
                    // Use warehouse_id directly from user_warehouses table (not row id)
                    $assignedWarehouseId = DB::table('user_warehouses')
                        ->where('user_id', $user->id)
                        ->value('warehouse_id');
                    
                    if ($assignedWarehouseId) {
                        $warehouseId = $assignedWarehouseId;
                        \Log::info('checkMultiProduct: Using first assigned warehouse instead', [
                            'requested_warehouse_id' => $requestedWarehouseId,
                            'new_warehouse_id' => $warehouseId,
                            'user_id' => $user->id
                        ]);
                    } else {
                        // No assigned warehouses - use first company warehouse
                        $companyWarehouse = \App\Models\warehouse::where('created_by', $creatorId)->first();
                        if ($companyWarehouse) {
                            $warehouseId = $companyWarehouse->id;
                            \Log::info('checkMultiProduct: Using first company warehouse', [
                                'requested_warehouse_id' => $requestedWarehouseId,
                                'new_warehouse_id' => $warehouseId,
                                'user_id' => $user->id
                            ]);
                        }
                    }
                }
            }
        }

        \Log::info('checkMultiProduct called', [
            'product_ids' => $productIds,
            'requested_warehouse_id' => $requestedWarehouseId,
            'warehouse_id' => $warehouseId,
            'cart_data_count' => count($cartData),
            'session_key' => $sessionKey,
            'user_id' => $user->id,
            'creator_id' => $creatorId
        ]);

        if (empty($productIds) || !$warehouseId || count($productIds) < 2) {
            \Log::info('checkMultiProduct: Early return - insufficient data', [
                'product_ids_count' => count($productIds),
                'warehouse_id' => $warehouseId
            ]);
            return response()->json([
                'success' => false,
                'applicable_combos' => [],
                'updated_cart' => $cartData
            ]);
        }

        // Map product_no (P_num) to product_service_id
        $productNoToServiceId = [];
        $serviceIdToProductNo = [];
        foreach ($cartData as $productNo => $item) {
            $subProduct = \App\Models\SubProduct::where('warehouse_id', $warehouseId)
                ->where('chassis_no', $productNo)
                ->first();
            
            if ($subProduct) {
                $productNoToServiceId[$productNo] = $subProduct->product_id;
                $serviceIdToProductNo[$subProduct->product_id] = $productNo;
            }
        }

        // Get all combo offers for this warehouse that have multiple products
        // Filter by creatorId to ensure users only see combos from their company
        $comboOffers = ComboOffer::where('warehouse_id', $warehouseId)
            ->where('created_by', $creatorId)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', \Carbon\Carbon::today());
            })
            ->with('products')
            ->get();

        \Log::info('checkMultiProduct: Found combos', [
            'combo_count' => $comboOffers->count(),
            'warehouse_id' => $warehouseId
        ]);

        $applicableCombos = [];
        $updatedCart = $cartData; // Start with current cart data

        foreach ($comboOffers as $combo) {
            // Skip if combo has no products via pivot table
            if ($combo->products->isEmpty()) {
                \Log::info('checkMultiProduct: Combo skipped - no products', ['combo_id' => $combo->id]);
                continue;
            }
            
            \Log::info('checkMultiProduct: Checking combo', [
                'combo_id' => $combo->id,
                'combo_products_count' => $combo->products->count(),
                'combo_type' => $combo->type,
                'buy_quantity' => $combo->buy_quantity
            ]);

            // Get product IDs from combo
            $comboProductIds = $combo->products->pluck('id')->toArray();
            
            // Check which combo products are in cart by iterating through cart data directly
            // This ensures we catch all products that match the combo, including same product added multiple times
            $matchedProductNos = [];
            $totalQuantity = 0;

            // Iterate through cart data to find matching products
            foreach ($cartData as $productNo => $item) {
                // Get the product_service_id for this product_no
                $cartServiceId = $productNoToServiceId[$productNo] ?? null;
                
                // Check if this product matches any of the combo's products
                if ($cartServiceId && in_array($cartServiceId, $comboProductIds)) {
                    // This product is part of the combo
                    if (!in_array($productNo, $matchedProductNos)) {
                        $matchedProductNos[] = $productNo;
                    }
                    // Add its quantity to total (even if same product added multiple times)
                    $totalQuantity += (int)($item['quantity'] ?? 0);
                }
            }
            
            // For Tiered_pricing: Check if total quantity meets requirement (no minimum product count required)
            // For BOGO: Check if ANY combination of combo products are in cart AND total quantity meets requirement
            $canApplyCombo = false;
            $requiredQuantity = $combo->buy_quantity ?? 1;
            
            if ($combo->type == 'tiered_pricing') {
                // For Tiered_pricing: Check if total quantity of matched products >= buy_quantity
                // No requirement for minimum number of products - just check total quantity
                // Combo will trigger if:
                // - Scenario 1: User adds desired qty from ONE product (e.g., 2 units of Product A when buy_quantity = 2)
                // - Scenario 2: User adds desired qty from MULTIPLE products (e.g., 1 unit of A + 1 unit of B = 2 total when buy_quantity = 2)
                // Example: If buy_quantity = 2:
                //   - 2 units of Product A → combo applies ✓
                //   - 1 unit of A + 1 unit of B = 2 total → combo applies ✓
                //   - 1 unit of A = 1 total → combo doesn't apply ✗
                $canApplyCombo = $totalQuantity >= $requiredQuantity;
            } else {
                // For BOGO (Buy X Get Y): Check if ANY product from combo is in cart
                // If total quantity >= (buy_quantity + get_quantity), make get_quantity free
                // Free items should be based on cheapest price among combo products
                if (!empty($matchedProductNos)) {
                    // Check if total quantity meets buy_quantity + get_quantity
                    $requiredQuantity = $combo->buy_quantity + ($combo->get_quantity ?? 0);
                    $canApplyCombo = $totalQuantity >= $requiredQuantity;
                }
            }

            \Log::info('checkMultiProduct: Combo quantity check', [
                'combo_id' => $combo->id,
                'combo_type' => $combo->type,
                'combo_product_ids' => $comboProductIds,
                'total_quantity' => $totalQuantity,
                'required_quantity' => $requiredQuantity,
                'can_apply' => $canApplyCombo,
                'matched_products' => $matchedProductNos,
                'matched_products_count' => count($matchedProductNos),
                'cart_product_nos' => array_keys($cartData),
                'product_no_to_service_id' => $productNoToServiceId
            ]);

                if ($canApplyCombo && !empty($matchedProductNos)) {
                    \Log::info('checkMultiProduct: Applying combo', [
                        'combo_id' => $combo->id,
                        'matched_products_count' => count($matchedProductNos),
                        'total_quantity' => $totalQuantity
                    ]);
                    // Calculate updated prices for each product and update cart
                    // For Tiered_pricing: Only apply combo pricing to the FIRST buy_quantity items
                    // Any additional items beyond buy_quantity should use regular pricing
                    $productsData = [];
                    
                    if ($combo->type == 'tiered_pricing') {
                        // Calculate how many complete combo sets can be applied
                        // Example: if buy_quantity = 2 and totalQuantity = 5, we have 2 complete sets (4 items) + 1 remaining
                        $completeSets = (int)($totalQuantity / $combo->buy_quantity);
                        $totalComboQuantity = $completeSets * $combo->buy_quantity; // Total items that get combo pricing
                        
                        $pricePerItem = $combo->tiered_price / $combo->buy_quantity;
                        
                        // Track how many items have been given combo pricing so far
                        $remainingComboQuantity = $totalComboQuantity;
                        
                        // Distribute combo pricing sequentially across products until all complete sets are used
                        foreach ($matchedProductNos as $productNo) {
                            if (isset($cartData[$productNo])) {
                                $item = $cartData[$productNo];
                                $price = (float)($item['price'] ?? 0);
                                $discount = (float)($item['discount'] ?? 0);
                                $quantity = (int)($item['quantity'] ?? 0);
                                
                                $pprice = $price - ($price * ($discount / 100));
                                
                                // Calculate how many of this product's quantity get combo pricing
                                // Take as many as possible from remaining combo quantity, up to this product's quantity
                                $comboQtyForThisProduct = min($quantity, $remainingComboQuantity);
                                $regularQtyForThisProduct = $quantity - $comboQtyForThisProduct;
                                
                                // Calculate subtotal: combo price for combo items + regular price for remaining items
                                $comboSubtotal = $comboQtyForThisProduct * $pricePerItem;
                                $regularSubtotal = $regularQtyForThisProduct * $pprice;
                                $newSubtotal = $comboSubtotal + $regularSubtotal;
                                
                                // Only set combo_id if this product actually gets combo pricing
                                // Products that don't get combo pricing should not show combo badge
                                $comboIdForThisProduct = ($comboQtyForThisProduct > 0) ? $combo->id : 0;
                                
                                // Update remaining combo quantity
                                $remainingComboQuantity -= $comboQtyForThisProduct;
                                
                                \Log::info('checkMultiProduct: Tiered pricing calculation (multiple sets)', [
                                    'product_no' => $productNo,
                                    'quantity' => $quantity,
                                    'combo_qty' => $comboQtyForThisProduct,
                                    'regular_qty' => $regularQtyForThisProduct,
                                    'total_quantity' => $totalQuantity,
                                    'buy_quantity' => $combo->buy_quantity,
                                    'complete_sets' => $completeSets,
                                    'total_combo_quantity' => $totalComboQuantity,
                                    'remaining_combo_quantity' => $remainingComboQuantity,
                                    'tiered_price' => $combo->tiered_price,
                                    'price_per_item' => $pricePerItem,
                                    'combo_subtotal' => $comboSubtotal,
                                    'regular_subtotal' => $regularSubtotal,
                                    'new_subtotal' => $newSubtotal,
                                    'regular_price' => $pprice,
                                    'combo_id' => $comboIdForThisProduct
                                ]);
                                
                                // Update the cart data with combo_id (only if product gets combo pricing) and new subtotal
                                if (isset($updatedCart[$productNo])) {
                                    $updatedCart[$productNo]['compo_id'] = $comboIdForThisProduct;
                                    $updatedCart[$productNo]['subtotal'] = $newSubtotal;
                                }
                                
                                // Include in productsData for session update (even if combo_id is 0, to clear previous combo)
                                $productsData[$productNo] = [
                                    'product_no' => $productNo,
                                    'subtotal' => $newSubtotal,
                                    'combo_id' => $comboIdForThisProduct  // Will be 0 if product doesn't get combo pricing
                                ];
                            }
                        }
                    } else {
                        // BOGO (Buy X Get Y) logic: Make get_quantity free based on cheapest price
                        $buyQty = $combo->buy_quantity ?? 1;
                        $getQty = $combo->get_quantity ?? 0;
                        $groupSize = $buyQty + $getQty; // Total items in one combo group (X + Y)
                        
                        if ($groupSize <= 0) {
                            // Invalid combo configuration, skip
                            \Log::warning('checkMultiProduct: Invalid BOGO combo configuration', [
                                'combo_id' => $combo->id,
                                'buy_quantity' => $buyQty,
                                'get_quantity' => $getQty
                            ]);
                            continue;
                        }
                        
                        // Calculate how many complete combo groups can be formed
                        // Example: buy 2 get 1 free (groupSize = 3), totalQuantity = 7
                        //   → 2 complete groups (6 items) + 1 remainder
                        //   → Free items: 2 groups * 1 get_quantity = 2 items (cheapest ones)
                        $completeGroups = (int)($totalQuantity / $groupSize);
                        $remainder = $totalQuantity % $groupSize;
                        
                        // Calculate total free items (only from complete groups)
                        $totalFreeItems = $completeGroups * $getQty;
                        // Remainder items are all paid (not enough for another complete group)
                        $totalPaidItems = $totalQuantity - $totalFreeItems;
                        
                        \Log::info('checkMultiProduct: BOGO calculation (cheapest price)', [
                            'combo_id' => $combo->id,
                            'total_quantity' => $totalQuantity,
                            'buy_quantity' => $buyQty,
                            'get_quantity' => $getQty,
                            'group_size' => $groupSize,
                            'complete_groups' => $completeGroups,
                            'remainder' => $remainder,
                            'total_paid_items' => $totalPaidItems,
                            'total_free_items' => $totalFreeItems
                        ]);
                        
                        // Prepare product data with prices for sorting by cheapest
                        $productData = [];
                        foreach ($matchedProductNos as $productNo) {
                            if (isset($cartData[$productNo])) {
                                $item = $cartData[$productNo];
                                $price = (float)($item['price'] ?? 0);
                                $discount = (float)($item['discount'] ?? 0);
                                $quantity = (int)($item['quantity'] ?? 0);
                                
                                $pprice = $price - ($price * ($discount / 100));
                                
                                $productData[$productNo] = [
                                    'quantity' => $quantity,
                                    'price' => $pprice,
                                    'paid_items' => $quantity, // Initially all paid, will adjust for free items
                                    'free_items' => 0
                                ];
                            }
                        }
                        
                        // Sort products by price (cheapest first) to allocate free items
                        uasort($productData, function($a, $b) {
                            return $a['price'] <=> $b['price'];
                        });
                        
                        // Allocate free items to cheapest products first
                        $remainingFreeItems = $totalFreeItems;
                        foreach ($productData as $productNo => &$data) {
                            if ($remainingFreeItems <= 0) {
                                break;
                            }
                            
                            // Allocate free items from this product (up to its quantity)
                            $allocatedFree = min($data['quantity'], $remainingFreeItems);
                            $data['free_items'] = $allocatedFree;
                            $data['paid_items'] = $data['quantity'] - $allocatedFree;
                            $remainingFreeItems -= $allocatedFree;
                        }
                        unset($data); // Unset reference
                        
                        // Track which products are part of complete combo groups
                        // A product is part of combo if it contributes to at least one complete group
                        $productsInComboGroups = [];
                        $remainingGroupItems = $completeGroups * $groupSize; // Total items in complete groups
                        
                        // Distribute group items across products to identify which are in combo groups
                        foreach ($matchedProductNos as $productNo) {
                            if (isset($productData[$productNo]) && $remainingGroupItems > 0) {
                                $allocatedToGroup = min($productData[$productNo]['quantity'], $remainingGroupItems);
                                if ($allocatedToGroup > 0) {
                                    $productsInComboGroups[$productNo] = true;
                                }
                                $remainingGroupItems -= $allocatedToGroup;
                            }
                        }
                        
                        // Calculate subtotals and update cart (iterate in original order)
                        foreach ($matchedProductNos as $productNo) {
                            if (isset($productData[$productNo])) {
                                $paidItems = $productData[$productNo]['paid_items'];
                                $freeItems = $productData[$productNo]['free_items'];
                                $pprice = $productData[$productNo]['price'];
                                
                                // Calculate subtotal: only paid items contribute to subtotal (free items = 0)
                                $newSubtotal = $paidItems * $pprice;
                                
                                // Only set combo_id if this product is part of at least one complete combo group
                                // This ensures combo ID only shows when product actually benefits from/completes a combo
                                // Products with quantities beyond complete groups won't show combo ID
                                $comboIdForThisProduct = isset($productsInComboGroups[$productNo]) ? $combo->id : 0;
                                
                                \Log::info('checkMultiProduct: BOGO product calculation (cheapest)', [
                                    'product_no' => $productNo,
                                    'quantity' => $productData[$productNo]['quantity'],
                                    'price' => $pprice,
                                    'paid_items' => $paidItems,
                                    'free_items' => $freeItems,
                                    'new_subtotal' => $newSubtotal,
                                    'combo_id' => $comboIdForThisProduct,
                                    'in_combo_group' => isset($productsInComboGroups[$productNo]),
                                    'complete_groups' => $completeGroups
                                ]);
                                
                                // Update the cart data with combo_id and new subtotal
                                if (isset($updatedCart[$productNo])) {
                                    $updatedCart[$productNo]['compo_id'] = $comboIdForThisProduct;
                                    $updatedCart[$productNo]['subtotal'] = $newSubtotal;
                                }
                                
                                $productsData[$productNo] = [
                                    'product_no' => $productNo,
                                    'subtotal' => $newSubtotal,
                                    'combo_id' => $comboIdForThisProduct
                                ];
                            }
                        }
                    }

                    if (!empty($productsData)) {
                        $applicableCombos[] = [
                            'combo_id' => $combo->id,
                            'type' => $combo->type,
                            'products' => $productsData
                        ];
                    }
                }
        }

        // Update session with combo_ids if any combos were found
        if (!empty($applicableCombos)) {
            $sessionCart = session()->get($sessionKey, []);
            foreach ($applicableCombos as $comboData) {
                foreach ($comboData['products'] as $productNo => $productData) {
                    if (isset($sessionCart[$productNo])) {
                        $sessionCart[$productNo]['compo_id'] = $productData['combo_id'];
                        $sessionCart[$productNo]['subtotal'] = $productData['subtotal'];
                    }
                }
            }
            session()->put($sessionKey, $sessionCart);
        }

        \Log::info('checkMultiProduct: Returning response', [
            'applicable_combos_count' => count($applicableCombos),
            'applicable_combos' => $applicableCombos
        ]);

        return response()->json([
            'success' => true,
            'applicable_combos' => $applicableCombos,
            'updated_cart' => $updatedCart
        ]);
    }

    /**
     * Get sub-brands filtered by warehouse and brand
     */
    public function getSubBrandsByWarehouse(Request $request)
    {
        // Allow access if user has 'manage pos' or 'add pos' permission, or can manage combo offers
        $hasPosPermission = \Auth::user()->can('manage pos') || \Auth::user()->can('add pos');
        $hasComboPermission = \Auth::user()->type == 'company' || \Auth::user()->can('view combo') || \Auth::user()->can('create combo');
        
        if (!$hasPosPermission && !$hasComboPermission) {
            return response()->json([
                'success' => false,
                'error' => __('Permission denied.')
            ], 403);
        }

        $warehouseId = $request->input('warehouse_id');
        $brandId = $request->input('brand_id');
        $getProducts = $request->input('get_products', false);
        
        if (!$warehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse ID is required'
            ], 400);
        }

        $creatorId = \Auth::user()->creatorId();

        // Get all product IDs that exist in the selected warehouse
        $productIdsInWarehouse = SubProduct::where('warehouse_id', $warehouseId)
            ->where('created_by', $creatorId)
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('product_id')
            ->toArray();

        if (empty($productIdsInWarehouse)) {
            return response()->json([
                'success' => true,
                'brands' => [],
                'sub_brands' => [],
                'product_ids' => $getProducts ? [] : []
            ]);
        }

        // Get products that exist in warehouse, optionally filtered by brand
        $productsQuery = ProductService::whereIn('id', $productIdsInWarehouse)
            ->where('created_by', $creatorId)
            ->whereNotNull('sub_brand_id');

        if ($brandId) {
            $productsQuery->where('brand_id', $brandId);
        }

        // Get unique sub-brand IDs from products in warehouse
        $subBrandIds = $productsQuery->distinct()
            ->pluck('sub_brand_id')
            ->toArray();

        // Initialize empty arrays for brands and sub-brands
        $brands = [];
        $subBrands = [];
        
        if (empty($subBrandIds)) {
            // Still return brands even if no sub-brands found
            $brandIds = ProductService::whereIn('id', $productIdsInWarehouse)
                ->where('created_by', $creatorId)
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id')
                ->toArray();

            if (!empty($brandIds)) {
                $brands = Brand::whereIn('id', $brandIds)
                    ->where('created_by', $creatorId)
                    ->orderBy('name')
                    ->get()
                    ->map(function($brand) {
                        return [
                            'id' => $brand->id,
                            'name' => $brand->name
                        ];
                    });
            }
            
            return response()->json([
                'success' => true,
                'brands' => $brands,
                'sub_brands' => [],
                'product_ids' => $getProducts ? $productIdsInWarehouse : []
            ]);
        }

        $subBrandsQuery = VehicleModel::whereIn('id', $subBrandIds)
            ->where('created_by', $creatorId)
            ->with('brand:id,name');

        $subBrands = $subBrandsQuery->get()->map(function ($modelRow) {
            return [
                'id' => $modelRow->id,
                'name' => $modelRow->name,
                'brand_id' => $modelRow->brand_id,
            ];
        });

        // Get brands that have products in this warehouse
        $brandIds = ProductService::whereIn('id', $productIdsInWarehouse)
            ->where('created_by', $creatorId)
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id')
            ->toArray();

        $brands = [];
        if (!empty($brandIds)) {
            $brands = Brand::whereIn('id', $brandIds)
                ->where('created_by', $creatorId)
                ->orderBy('name')
                ->get()
                ->map(function($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name
                    ];
                });
        }

        $response = [
            'success' => true,
            'brands' => $brands,
            'sub_brands' => $subBrands
        ];

        if ($getProducts) {
            $response['product_ids'] = $productIdsInWarehouse;
        }

        return response()->json($response);
    }
}
