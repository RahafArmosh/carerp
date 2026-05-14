<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubProduct;
use App\Models\CarAccessoryRequest;
use App\Models\CarAccessoryRequestItem;
use Illuminate\Support\Facades\DB;
use App\Helpers\GeneralLedgerHelper;
use App\Models\GeneralLedger;
use App\Models\StockMovement;
class CarManufacturerController extends Controller
{
    public function index()
    {
        $carAccessories = CarAccessoryRequest::withSum('items as items_sum_quantity', 'quantity')
            ->withCount([
                'items as car_id_count' => function ($q) {
                    $q->select(DB::raw('COUNT(DISTINCT car_id)'));
                },
            ])
            ->leftJoin('users as u', 'u.id', '=', 'car_accessory_requests.created_by')
            ->addSelect('car_accessory_requests.*', 'u.name as created_by_name')
            ->addSelect(DB::raw("(
                SELECT GROUP_CONCAT(DISTINCT CONCAT(i.invoice_id, ':', i.payment_status) SEPARATOR ', ')
                FROM car_accessory_request_items cai
                JOIN invoice_products ip ON ip.sub_product_id = cai.car_id
                JOIN invoices i ON i.id = ip.invoice_id
                WHERE cai.request_id = car_accessory_requests.id
            ) AS invoices_list"))
            ->addSelect(DB::raw("(
                SELECT GROUP_CONCAT(DISTINCT CONCAT(b.bill_id, ':', b.status) SEPARATOR ', ')
                FROM car_accessory_request_items cai
                JOIN bill_products bp ON bp.sub_product_id = cai.car_id
                JOIN bills b ON b.id = bp.bill_id
                WHERE cai.request_id = car_accessory_requests.id
            ) AS bills_list"))
            ->where('car_accessory_requests.created_by', \Auth::user()->creatorId())
            ->latest('car_accessory_requests.id')
            ->get();
        return view('car_manufacturers.index', compact('carAccessories'));
    }

    public function create()
    {
        return $this->search();
    }

    public function store(Request $request)
    {
        return redirect()->route('car_accessories.index');
    }

    public function destroy(Request $request, CarAccessoryRequest $carAccessory)
    {
        // Get the delete date from the request
        $deleteDate = $request->input('delete_date');
        if (!$deleteDate) {
            return back()->with('error', 'Delete date is required.');
        }

        // Validate that delete date is not before today
        if (strtotime($deleteDate) < strtotime(date('Y-m-d'))) {
            return back()->with('error', 'Delete date cannot be before today.');
        }

        // If the request was assigned, validate that delete date is after all ledger send dates
        if ($carAccessory->status === 'assigned') {
            // Get all ledger entries for this request to check send dates
            $ledgerEntries = GeneralLedger::where('ref_id', $carAccessory->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('send_date')
                ->get();

            foreach ($ledgerEntries as $entry) {
                if (strtotime($deleteDate) <= strtotime($entry->send_date)) {
                    return back()->with('error', 'Delete date must be after the assignment send date: ' . date('Y-m-d', strtotime($entry->send_date)));
                }
            }
        }

        DB::beginTransaction();
        try {
            // Load the request with items and their relationships
            $carAccessory->load(['items.accessory', 'items.car.productService.category']);
            
            if ($carAccessory->status === 'on_hold') {
                // Unhold all items and return quantities to sub-products
                foreach ($carAccessory->items as $item) {
                    if ($item->accessory_id) {
                        // Get the assigned sub-product
                        $assignedSubProduct = \DB::table('sub_products')
                            ->where('id', $item->accessory_id)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();
                        
                        if ($assignedSubProduct) {
                            // Return the held quantity to the sub-product
                            $newQuantity = $assignedSubProduct->quantity + $item->quantity;
                            
                            \DB::table('sub_products')
                                ->where('id', $assignedSubProduct->id)
                                ->update([
                                    'quantity' => $newQuantity,
                                    'booked' => 0 // Set back to free/available
                                ]);
                        }
                    }
                }
            } elseif ($carAccessory->status === 'assigned') {
                // Unhold items and create ledger reversals
                foreach ($carAccessory->items as $item) {
                    if ($item->accessory_id) {
                            $product = $item->accessory->productService;
                            
                            // Check cost calculation method
                            $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';
                            
                            if ($costCalculationMethod === 'avg') {
                                // Calculate average cost using formula:
                                // Average Cost = ((Last Purchased Qty from Sub Product × Last Avg) - (Sell Qty × Last Avg)) ÷ (Last Purchased Qty from Sub Product - Sell Qty)
                                
                                // Get purchased bill IDs (sent bills only)
                                $purchasedBillIds = \App\Models\Bill::whereNotIn('status', [0, 1, 2])
                                    ->where('created_by', \Auth::user()->creatorId())
                                    ->pluck('id')
                                    ->toArray();
                                
                                // Count purchased subproduct quantities (last purchased qty from sub product)
                                $lastPurchasedQty = SubProduct::where('product_id', $product->id)
                                    ->whereIn('bill_id', $purchasedBillIds)
                                    ->where('flag', '!=', 0)
                                    ->whereNotNull('bill_id')
                                    ->sum('quantity') ?? 0;
                                
                                // Get last avg from parent product
                                $lastAvg = ($product->avg_cost > 0) ? $product->avg_cost : ($item->accessory->purchase_price ?? 0);
                                
                                // Sell qty for this item
                                $sellQty = $item->quantity;
                                
                                // Calculate average cost using formula
                                $remainingQty = $lastPurchasedQty - $sellQty;
                                if ($remainingQty > 0) {
                                    $avgCost = (($lastPurchasedQty * $lastAvg) - ($sellQty * $lastAvg)) / $remainingQty;
                                } else {
                                    $avgCost = 0;
                                }
                            } else {
                                // Use actual cost (purchase price from accessory)
                                $avgCost = $item->accessory->purchase_price ?? 0;
                            }

                            // Create a new StockMovement for the stock out (sale)
                            $stockMovement = new StockMovement();
                            $stockMovement->product_id = $item->accessory->product_id;
                            $stockMovement->sub_product_id = $item->accessory->id;
                            $stockMovement->invoice_id = null;
                            $stockMovement->bill_id = null;
                            $stockMovement->qty_in = 0; // No stock in for a sale
                            $stockMovement->qty_out = $sellQty; // Quantity sold
                            $stockMovement->avg_cost = $avgCost; // New calculated average cost
                            $stockMovement->cost_price = $lastAvg;
                            $stockMovement->activity = 'Sale via Car Accessory';
                            $stockMovement->use_id = null; // No invoice/bill, so no customer/vendor
                            $stockMovement->item = $item->accessory->id; // sub_product_id
                            $stockMovement->created_by = \Auth::user()->creatorId();
                            $stockMovement->save();
                            
                            // Update product average cost
                            $product->avg_cost = $avgCost;
                            $product->save();
                        $carProduct = $item->car;
                        $accProduct = $item->accessory;
                        
                        if ($carProduct && $accProduct && $carProduct->productService && $accProduct->productService) {
                            // Calculate reversal amounts
                            $debitAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                            $creditAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                            
                            if ($debitAmount > 0 && $creditAmount > 0) {
                                // Get latest voucher ID
                                $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                                $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;
                                
                                // Create reversal ledger entries with the delete date
                                GeneralLedger::create([
                                    'vid' => $newVid,
                                    'account' => $accProduct->productService->category->purchase_account_id,
                                    'type' => 'Delete - manufacturer ' . $carAccessory->request_no,
                                    'ref_number' => 'Delete - manufacturer ' . $carAccessory->request_no,
                                    'debit' => 0,
                                    'credit' => $debitAmount,
                                    'ref_id' => $carAccessory->id,
                                    'user_id' => 0,
                                    'created_by' => \Auth::user()->creatorId(),
                                    'send_date' => $deleteDate,
                                    'reference' => 'Delete - Assign Car Accessory',
                                ]);

                                GeneralLedger::create([
                                    'vid' => $newVid,
                                    'account' => $carProduct->productService->category->purchase_account_id,
                                    'type' => 'Delete - manufacturer ' . $carAccessory->request_no,
                                    'ref_number' => 'Delete - manufacturer ' . $carAccessory->request_no,
                                    'debit' => $creditAmount,
                                    'credit' => 0,
                                    'ref_id' => $carAccessory->id,
                                    'user_id' => 0,
                                    'created_by' => \Auth::user()->creatorId(),
                                    'send_date' => $deleteDate,
                                    'reference' => 'Delete - Assign Car Accessory',
                                ]);
                            }
                        }
                        
                        // Update accessory book status back to 0 (free)
                        \DB::table('sub_products')
                            ->where('id', $accProduct->id)
                            ->update(['booked' => 0]);
                    }
                }
            }
            
            // Delete the request and all its items
            $carAccessory->items()->delete();
            $carAccessory->delete();
            
            DB::commit();
            return back()->with('success', 'Car accessory request deleted successfully with delete date: ' . $deleteDate);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting request: ' . $e->getMessage());
        }
    }

    public function show(CarAccessoryRequest $car_accessory)
    {
        $car_accessory->load(['items' => function($q){
            $q->with([
                'car' => function($cq){
                    $cq->select('id','chassis_no','product_id')
                       ->with(['productService' => function($pq){
                            $pq->select('id','name','sku','category_id','brand_id','sub_brand_id')
                               ->with([
                                   'category:id,name',
                                   'brand:id,name',
                                   'subBrand:id,name',
                               ]);
                       }]);
                },
                'accessory' => function($aq){
                    $aq->select('id','chassis_no','product_id')
                       ->with(['productService' => function($pq){
                            $pq->select('id','name','sku','category_id','brand_id','sub_brand_id')
                               ->with([
                                   'category:id,name',
                                   'brand:id,name',
                                   'subBrand:id,name',
                               ]);
                       }]);
                },
                'product' => function($pqRoot){
                    $pqRoot->select('id','name','sku','category_id','brand_id','sub_brand_id')
                           ->with([
                               'category:id,name',
                               'brand:id,name',
                               'subBrand:id,name',
                           ]);
                },
            ]);
        }]);

        $availableByItemId = [];
        $neededByItemId = [];
        $requestStatus = $car_accessory->status;

        if ($car_accessory->status === 'approved') {
            // First, calculate total available quantity for each product
            $totalAvailableByProduct = [];
            $assignedQuantityByProduct = [];
            
            foreach ($car_accessory->items as $it) {
                $productId = $it->product_id;
                if ($productId) {
                    // Calculate total available quantity for this product from ALL sub-products
                    if (!isset($totalAvailableByProduct[$productId])) {
                        $totalAvailableByProduct[$productId] = \DB::table('sub_products')
                            ->where('product_id', $productId)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->where('quantity', '>', 0) // Only count sub-products with available quantity
                            ->sum('quantity');
                    }
                    
                    // Calculate total assigned quantity for this product in this request
                    if (!isset($assignedQuantityByProduct[$productId])) {
                        $assignedQuantityByProduct[$productId] = $car_accessory->items
                            ->where('product_id', $productId)
                            ->where('accessory_id', '!=', null)
                            ->sum('quantity');
                    }
                }
            }
            
            // Now calculate availability for each item
            foreach ($car_accessory->items as $it) {
                $productId = $it->product_id;
                if ($productId) {
                    // If item already has an assigned sub-product, show it as available
                    if ($it->accessory_id) {
                        $availableByItemId[$it->id] = $it->quantity;
                        $neededByItemId[$it->id] = 0;
                    } else {
                        // Calculate remaining available quantity
                        $totalAvailable = $totalAvailableByProduct[$productId] ?? 0;
                        // Don't subtract assigned quantity because it's already deducted from sub-products
                        $remainingAvailable = $totalAvailable;
                        
                        if ($remainingAvailable >= $it->quantity) {
                            $availableByItemId[$it->id] = $remainingAvailable;
                            $neededByItemId[$it->id] = 0; // Can hold from available stock
                        } else {
                            $availableByItemId[$it->id] = $remainingAvailable;
                            $neededByItemId[$it->id] = $it->quantity - $remainingAvailable; // Need to purchase the difference
                        }
                    }
                } else {
                    $availableByItemId[$it->id] = 0;
                    $neededByItemId[$it->id] = $it->quantity;
                }
            }
        }

        $vendors = \DB::table('venders')
            ->where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get(['id','name']);

        // Check if all items are approved (have accessory_id assigned)
        $allItemsApproved = $car_accessory->items->whereNotNull('accessory_id')->count() === $car_accessory->items->count();

        return view('car_manufacturers.show', compact('car_accessory','availableByItemId','neededByItemId','vendors','allItemsApproved'));
    }

    public function update(Request $request, CarAccessoryRequest $car_accessory)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $car_accessory->update(['status' => $validated['status']]);

        return redirect()->route('car_accessories.show', $car_accessory->id)->with('success', 'Status updated.');
    }

    public function unholdItem(Request $request, CarAccessoryRequestItem $item)
    {
        // Check if the request status is 'on_hold'
        if ($item->request->status !== 'on_hold') {
            return back()->with('error', 'Can only unhold items when request status is "On Hold".');
        }

        // Check if the item has an assigned accessory
        if (!$item->accessory_id) {
            return back()->with('error', 'This item has no assigned accessory to unhold.');
        }

        // Get the assigned sub-product
        $assignedSubProduct = \DB::table('sub_products')
            ->where('id', $item->accessory_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$assignedSubProduct) {
            return back()->with('error', 'Assigned sub-product not found.');
        }

        // Calculate the quantity to return (the quantity that was held)
        $quantityToReturn = $item->quantity;
        
        // Calculate new quantity after returning
        $newQuantity = $assignedSubProduct->quantity + $quantityToReturn;
        
        // Determine book status based on new quantity
        $bookStatus = $newQuantity > 0 ? 0 : 0; // 0 = free (available)
        
        // Update the sub-product quantity and book status
        \DB::table('sub_products')
            ->where('id', $assignedSubProduct->id)
            ->update([
                'quantity' => $newQuantity,
                'booked' => $bookStatus
            ]);

        // Remove the accessory assignment from the request item
        $item->update([
            'accessory_id' => null
        ]);

        // Check if any items in this request still have accessory_id assigned
        $request = $item->request;
        $remainingHeldItems = $request->items()->whereNotNull('accessory_id')->count();
        
        // If no items are held anymore, change the request status back to 'approved'
        if ($remainingHeldItems === 0) {
            $request->update(['status' => 'approved']);
            return back()->with('success', "Unheld {$quantityToReturn} unit(s) from sub-product ID {$assignedSubProduct->id}. No items are held anymore - request status changed back to 'Approved'.");
        }

        return back()->with('success', "Unheld {$quantityToReturn} unit(s) from sub-product ID {$assignedSubProduct->id}.");
    }

    public function deleteItem(Request $request, CarAccessoryRequestItem $item)
    {
        // Get delete date from request
        $deleteDate = $request->input('delete_date');
        if (!$deleteDate) {
            return back()->with('error', 'Delete date is required.');
        }

        // Validate delete date
        if (strtotime($deleteDate) < strtotime(date('Y-m-d'))) {
            return back()->with('error', 'Delete date cannot be before today.');
        }

        DB::beginTransaction();
        try {
            $carAccessory = $item->request;
            $item->load('accessory', 'car');

            // If item has accessory_id (on hold), release the hold
            if ($item->accessory_id) {
                $assignedSubProduct = SubProduct::where('id', $item->accessory_id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();

                if ($assignedSubProduct) {
                    // Return the held quantity to the sub-product
                    $newQuantity = $assignedSubProduct->quantity + $item->quantity;
                    $bookStatus = $newQuantity > 0 ? 0 : 0; // 0 = free

                    $assignedSubProduct->update([
                        'quantity' => $newQuantity,
                        'booked' => $bookStatus
                    ]);
                }
            }

            // If request status is 'assigned', reverse the general ledger entries for this item
            if ($carAccessory->status === 'assigned' && $item->accessory_id) {
                $carProduct = $item->car;
                $accProduct = $item->accessory;

                if ($carProduct && $accProduct && $carProduct->productService && $accProduct->productService) {
                    $carPurchaseAccountId = $carProduct->productService->category->purchase_account_id;
                    $accPurchaseAccountId = $accProduct->productService->category->purchase_account_id;
                    
                    $debitAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                    $creditAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;

                    if ($debitAmount > 0 && $creditAmount > 0) {
                        // Get a new unique vid for reversal entries
                        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
                        $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                        if (GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists()) {
                            return redirect()->back()->with('error', __("Something went wrong, please try again."));
                        }

                        // Reverse: Credit the car's purchase account (opposite of original debit)
                        GeneralLedger::create([
                            'vid' => $newVid,
                            'account' => $carPurchaseAccountId,
                            'type' => 'Delete Item - manufacturer ' . $carAccessory->request_no,
                            'ref_number' => 'Delete Item - manufacturer ' . $carAccessory->request_no,
                            'debit' => 0,
                            'credit' => $debitAmount,
                            'ref_id' => $carAccessory->id,
                            'user_id' => 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'send_date' => $deleteDate,
                            'reference' => 'Delete Item - Assign Car Accessory',
                        ]);

                        // Reverse: Debit the accessory's purchase account (opposite of original credit)
                        GeneralLedger::create([
                            'vid' => $newVid,
                            'account' => $accPurchaseAccountId,
                            'type' => 'Delete Item - manufacturer ' . $carAccessory->request_no,
                            'ref_number' => 'Delete Item - manufacturer ' . $carAccessory->request_no,
                            'debit' => $creditAmount,
                            'credit' => 0,
                            'ref_id' => $carAccessory->id,
                            'user_id' => 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'send_date' => $deleteDate,
                            'reference' => 'Delete Item - Assign Car Accessory',
                        ]);

                        // Update accessory book status back to 0 (free)
                        if ($accProduct) {
                            $accProduct->update(['booked' => 0]);
                        }
                    }
                }
            }

            // Delete the item
            $item->delete();

            DB::commit();
            return back()->with('success', 'Item deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting item: ' . $e->getMessage());
        }
    }

    public function holdItem(Request $request, CarAccessoryRequestItem $item)
    {
        $productId = $item->product_id; // Use the stored product_id instead of accessory->product_id
        if (!$productId) {
            return back()->with('error', 'Invalid accessory product for this item.');
        }

        // Check if the car is sold or reserved (booked status 2 or 3)
        $car = $item->car;
        if ($car && in_array($car->booked, [2, 3])) {
            return back()->with('error', 'Cannot hold accessories for a car that is sold or reserved (booked status: ' . $car->booked . ').');
        }

        $quantityToHold = (int)$request->quantity;
        
        // Enforce holding exactly the required quantity for this item
        if ((int)$quantityToHold !== (int)$item->quantity) {
            return back()->with('error', 'Quantity must equal the required quantity: ' . (int)$item->quantity);
        }

        // Get IDs of sub-products already assigned to other items in this request
        $assignedSubProductIds = $item->request->items
            ->where('product_id', $productId)
            ->where('accessory_id', '!=', null)
            ->pluck('accessory_id')
            ->toArray();
        
        // Check if there's an available sub-product for this car that has enough quantity
        $assignedSubProduct = \DB::table('sub_products')
            ->where('product_id', $productId)
            // ->where('flag', '!=', 0) // Check if sub-product is active
            ->where('created_by', \Auth::user()->creatorId())
            ->where('quantity', '>=', $quantityToHold) // Check if quantity is equal or more than requested
            // ->whereNotIn('id', $assignedSubProductIds) // Exclude already assigned sub-products
            ->first();
            
        if (!$assignedSubProduct) {
            return back()->with('error', 'No sub-product found with enough quantity to hold the requested amount.');
        }

        // Calculate new quantity after holding
        $newQuantity = $assignedSubProduct->quantity - $quantityToHold;
        
        // Determine book status based on new quantity
        $bookStatus = $newQuantity <= 0 ? 1 : 0; // 1   = reserved, 0 = free (keep available)
        
        // Update the sub-product quantity and book status
        \DB::table('sub_products')
            ->where('id', $assignedSubProduct->id)
            ->update([
                'quantity' => max(0, $newQuantity), // Ensure quantity doesn't go below 0
                'booked' => $bookStatus
            ]);

        // Update the request item with the assigned sub-product ID
        $item->update([
            'accessory_id' => $assignedSubProduct->id
        ]);

        // Check if all items in this request are now held (have accessory_id assigned)
        $request = $item->request;
        $allItemsHeld = $request->items()->whereNull('accessory_id')->count() === 0;
        
        // If all items are held, automatically change the request status to 'on_hold'
        if ($allItemsHeld && $request->status === 'approved') {
            $request->update(['status' => 'on_hold']);
            return back()->with('success', "Held {$quantityToHold} unit(s) from assigned sub-product ID {$assignedSubProduct->id} and assigned it to this request item. All items in this request are now held - request status changed to 'On Hold'.");
        }

        return back()->with('success', "Held {$quantityToHold} unit(s) from assigned sub-product ID {$assignedSubProduct->id} and assigned it to this request item.");
    }

    public function assignStock(Request $request, CarAccessoryRequest $car_accessory)
    {
        if ($car_accessory->status !== 'on_hold') {
            return back()->with('error', 'Request must be On Hold to assign.');
        }

        $sendDate = $request->input('send_date');
        if (!$sendDate) {
            return back()->with('error', 'Send date is required.');
        }

        $car_accessory->load('items.accessory', 'items.car');

        // Check if any car is sold or reserved (booked status 2 or 3)
        foreach ($car_accessory->items as $item) {
            $car = $item->car;
            if ($car && in_array($car->booked, [2, 3])) {
                return back()->with('error', 'Cannot assign stock: One or more cars are sold or reserved (booked status: ' . $car->booked . ').');
            }
        }

        // Check if any accessory item has flag == 0 (inactive)
        foreach ($car_accessory->items as $item) {
            if ($item->accessory && $item->accessory->flag == 0) {
                return back()->with('error', 'Cannot assign stock: One or more accessory items are un purchase.');
            }
        }

        // Validate send date against purchase dates from bill_status_changes
        foreach ($car_accessory->items as $item) {
            $accProduct = $item->accessory;
            if (!$accProduct) {
                continue;
            }

            // Get all bills that contain this accessory product
            $bills = \DB::table('bill_products')
                ->where('product_id', $accProduct->product_id)
                ->pluck('bill_id')
                ->toArray();

            if (!empty($bills)) {
                // Get purchase dates from bill_status_changes for these bills
                $purchaseDates = \DB::table('bill_status_changes')
                    ->whereIn('bill_id', $bills)
                    ->where('status', '1') // Assuming 'purchased' is the status for purchase
                    ->pluck('created_at')
                    ->toArray();

                // Check if send date is before any purchase date
                foreach ($purchaseDates as $purchaseDate) {
                    if (date('Y-m-d', strtotime($sendDate)) < date('Y-m-d', strtotime($purchaseDate))) {
                        return back()->with('error', 'Send date cannot be before purchase date. Purchase date: ' . date('Y-m-d', strtotime($purchaseDate)) . ', Send date: ' . $sendDate);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

            if (GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists()) {
                return redirect()->back()->with('error', __("Something went wrong, please try again."));
            }
            foreach ($car_accessory->items as $item) {
                $carProduct = $item->car;
                $accProduct = $item->accessory;

                if (!$carProduct || !$accProduct) {
                    throw new \Exception('Missing car or accessory for an item.');
                }

                if (empty($carProduct->bill_id) || empty($accProduct->bill_id)) {
                    throw new \Exception('Car and Accessory must be purchased before assigning.');
                }

                // Mark item as delivered/assigned
                $item->update(['status' => 'delivered']);

                // Update accessory book status to 3 (assigned/delivered)
                \DB::table('sub_products')
                    ->where('id', $accProduct->id)
                    ->update(['booked' => 3]);

                // Accounting entries
                $debitAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                $creditAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                
                if ($debitAmount <= 0 || $creditAmount <= 0) {
                    throw new \Exception('Purchase price missing for accounting entries.');
                }
               
                GeneralLedger::create([
                    'vid' => $newVid,
                    'account' => $carProduct->productService->category->purchase_account_id,
                    'type' => 'manufacturer '. $car_accessory->request_no,
                    'ref_number' => 'manufacturer '. $car_accessory->request_no,
                    'debit' => $debitAmount,
                    'credit' => 0,
                    'ref_id' => $car_accessory->id,
                    'user_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'send_date' => $sendDate,
                    'reference' => 'Assign Car Accessory',
                ]);

                GeneralLedger::create([
                    'vid' => $newVid,
                    'account' => $accProduct->productService->category->purchase_account_id,
                    'type' => 'manufacturer '. $car_accessory->request_no,
                    'ref_number' => 'manufacturer '. $car_accessory->request_no,
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'ref_id' => $car_accessory->id,
                    'user_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'send_date' => $sendDate,
                    'reference' => 'Assign Car Accessory',
                ]);
            }

            $car_accessory->update(['status' => 'assigned']);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock assigned and accounting entries created.');
    }

    public function assignStockWithDate(Request $request, CarAccessoryRequest $car_accessory)
    {
        if ($car_accessory->status !== 'on_hold') {
            return response()->json([
                'success' => false,
                'message' => 'Request must be On Hold to assign.'
            ], 400);
        }

        $sendDate = $request->input('send_date');
        if (!$sendDate) {
            return response()->json([
                'success' => false,
                'message' => 'Send date is required.'
            ], 400);
        }

        $car_accessory->load('items.accessory', 'items.car');

        // Check if any car is sold or reserved (booked status 2 or 3)
        foreach ($car_accessory->items as $item) {
            $car = $item->car;
            if ($car && in_array($car->booked, [2, 3])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign stock: One or more cars are sold or reserved (booked status: ' . $car->booked . ').'
                ], 400);
            }
        }

        // Check if any accessory item has flag == 0 (inactive)
        foreach ($car_accessory->items as $item) {
            if ($item->accessory && $item->accessory->flag == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign stock: One or more accessory items are un purchase.'
                ], 400);
            }
        }

        // Validate send date against purchase dates from bill_status_changes
        foreach ($car_accessory->items as $item) {
            $accProduct = $item->accessory;
            if (!$accProduct) {
                continue;
            }

            // Get all bills that contain this accessory product
            $bills = \DB::table('bill_products')
                ->where('product_id', $accProduct->product_id)
                ->pluck('bill_id')
                ->toArray();

            if (!empty($bills)) {
                // Get purchase dates from bill_status_changes for these bills
                $purchaseDates = \DB::table('bill_status_changes')
                    ->whereIn('bill_id', $bills)
                    ->where('status', 'purchased') // Assuming 'purchased' is the status for purchase
                    ->pluck('created_at')
                    ->toArray();

                // Check if send date is before any purchase date
                foreach ($purchaseDates as $purchaseDate) {
                    if (date('Y-m-d', strtotime($sendDate)) < date('Y-m-d', strtotime($purchaseDate))) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Send date cannot be before purchase date. Purchase date: ' . date('Y-m-d', strtotime($purchaseDate)) . ', Send date: ' . $sendDate
                        ], 400);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
            $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

            if (GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again.'
                ], 400);
            }

            foreach ($car_accessory->items as $item) {
                $carProduct = $item->car;
                $accProduct = $item->accessory;

                if (!$carProduct || !$accProduct) {
                    throw new \Exception('Missing car or accessory for an item.');
                }

                if (empty($carProduct->bill_id) || empty($accProduct->bill_id)) {
                    throw new \Exception('Car and Accessory must be purchased before assigning.');
                }

                // Mark item as delivered/assigned
                $item->update(['status' => 'delivered']);

                // Update accessory book status to 3 (assigned/delivered)
                \DB::table('sub_products')
                    ->where('id', $accProduct->id)
                    ->update(['booked' => 3]);

                // Accounting entries with custom send date
                $debitAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                $creditAmount = (float)($accProduct->productService->avg_cost ?? 0) * $item->quantity;
                
                if ($debitAmount <= 0 || $creditAmount <= 0) {
                    throw new \Exception('Purchase price missing for accounting entries.');
                }
               
                GeneralLedger::create([
                    'vid' => $newVid,
                    'account' => $carProduct->productService->category->purchase_account_id,
                    'type' => 'manufacturer '. $car_accessory->request_no,
                    'ref_number' => 'manufacturer '. $car_accessory->request_no,
                    'debit' => $debitAmount,
                    'credit' => 0,
                    'ref_id' => $car_accessory->id,
                    'user_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'send_date' => $sendDate,
                    'reference' => 'Assign Car Accessory',
                ]);

                GeneralLedger::create([
                    'vid' => $newVid,
                    'account' => $accProduct->productService->category->purchase_account_id,
                    'type' => 'manufacturer '. $car_accessory->request_no,
                    'ref_number' => 'manufacturer '. $car_accessory->request_no,
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'ref_id' => $car_accessory->id,
                    'user_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'send_date' => $sendDate,
                    'reference' => 'Assign Car Accessory',
                ]);
            }

            $car_accessory->update(['status' => 'assigned']);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock assigned successfully with send date: ' . $sendDate
        ]);
    }

    public function search()
    {
        $userId = \Auth::user()->creatorId();

        $invoices = \DB::table('invoices')->where('created_by', $userId)->orderByDesc('id')->get(['id','invoice_id']);
        $bills = \DB::table('bills')->where('created_by', $userId)->orderByDesc('id')->get(['id','bill_id']);
        $warehouses = \DB::table('warehouses')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $customers = \DB::table('customers')->where('created_by', $userId)->orderBy('name')->get(['id','name']);
        $vendors = \DB::table('venders')->where('created_by', $userId)->orderBy('name')->get(['id','name']);

        // Clear any existing search session request
        session()->forget('current_search_request_id');

        // Get accessories for the accessories section
        $accessories = $this->getAccessories();

        return view('car_manufacturers.search', compact('invoices','bills','warehouses','customers','vendors','accessories'));
    }

    public function doSearch(Request $request)
    {
        // Clear any existing search session request when starting a new search
        session()->forget('current_search_request_id');
        
        $query = \DB::table('sub_products as sp')
            ->leftJoin('product_services as ps', 'ps.id', '=', 'sp.product_id')
            ->leftJoin('product_service_categories as c', 'c.id', '=', 'ps.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'ps.brand_id')
            ->leftJoin('sub_brands as sb', 'sb.id', '=', 'ps.sub_brand_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sp.warehouse_id')
            ->where('sp.created_by', \Auth::user()->creatorId())
            ->select(
                'sp.*', 
                'ps.name as product_name',
                'ps.sku as product_sku',
                'c.name as category_name',
                'b.name as brand_name',
                'sb.name as sub_brand_name',
                'w.name as warehouse_name'
            );

        // Filter to only show purchased products (Purchase Status is not Pending)
        $query->where('sp.flag', '!=', '0');
        
        // Filter out sold cars (booked status 2 or 3)
        $query->whereNotIn('sp.booked', [2, 3]);

        if ($request->filled('invoice_id')) {
            $query->join('invoice_products as ip', 'ip.sub_product_id', '=', 'sp.id')
                  ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
                  ->where('i.id', $request->invoice_id)
                  ->where('i.created_by', \Auth::user()->creatorId());
        }

        if ($request->filled('bill_id')) {
            $query->join('bill_products as bp', 'bp.sub_product_id', '=', 'sp.id')
                  ->join('bills as bills_b', 'bills_b.id', '=', 'bp.bill_id')
                  ->where('bills_b.id', $request->bill_id)
                  ->where('bills_b.created_by', \Auth::user()->creatorId());
        }

        if ($request->filled('vins')) {
            $vins = array_map('trim', preg_split('/\s+/', $request->vins));
            $query->whereIn('sp.chassis_no as product_no', $vins);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('w.id', $request->warehouse_id);
        }

        if ($request->filled('customer_id')) {
            $query->join('invoice_products as ip2', 'ip2.sub_product_id', '=', 'sp.id')
                  ->join('invoices as inv2', 'inv2.id', '=', 'ip2.invoice_id')
                  ->where('inv2.customer_id', $request->customer_id);
        }

        if ($request->filled('vender_id')) {
            $query->join('bill_products as bp2', 'bp2.sub_product_id', '=', 'sp.id')
                  ->join('bills as b2', 'b2.id', '=', 'bp2.bill_id')
                  ->where('b2.vender_id', $request->vender_id);
        }

        $cars = $query->distinct()->get();

        // Get accessories for the accessories section
        $accessories = $this->getAccessories();

        return view('car_manufacturers.search', compact('cars', 'accessories'));
    }

    /**
     * Get all manufacturer accessories for linking with cars
     * Returns all products from manufacturer categories regardless of stock quantity or sub-products existence
     */
    private function getAccessories()
    {
        return \DB::table('product_services as ps')
            ->leftJoin('product_service_categories as c', 'c.id', '=', 'ps.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'ps.brand_id')
            ->leftJoin('sub_brands as sb', 'sb.id', '=', 'ps.sub_brand_id')
            ->where('ps.created_by', \Auth::user()->creatorId())
            ->where('c.is_manufacturer', true) // Only show products from manufacturer categories
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->orderBy('sb.name')
            ->orderBy('ps.name')
            ->get([
                'ps.id as product_id',
                \DB::raw("CONCAT(COALESCE(c.name,''),' / ',COALESCE(b.name,''),' / ',COALESCE(ps.name,'')) as label")
            ]);
    }

    /**
     * Link selected cars with a specific accessory
     */
    public function linkCarsWithAccessory(Request $request)
    {
        $validated = $request->validate([
            'accessory_id' => 'required|integer|exists:product_services,id',
            'car_ids' => 'required|array|min:1',
            'car_ids.*' => 'integer|exists:sub_products,id',
            'quantity' => 'nullable|integer|min:1',
            'sell_price' => 'nullable|numeric|min:0',
        ]);

        try {
            // Check if there's an existing request in session for this search
            $sessionRequestId = session('current_search_request_id');
            
            if (!$sessionRequestId) {
                // Create a new car accessory request for this search session
                $requestNo = 'CAR-REQ-'.now()->format('Ymd').'-'.str_pad((string) (CarAccessoryRequest::max('id') + 1), 4, '0', STR_PAD_LEFT);

                $carRequest = CarAccessoryRequest::create([
                    'request_no' => $requestNo,
                    'request_date' => now()->toDateString(),
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);
                
                // Store the request ID in session
                session(['current_search_request_id' => $carRequest->id]);
            } else {
                // Use existing request from session
                $carRequest = CarAccessoryRequest::find($sessionRequestId);
                if (!$carRequest) {
                    // If session request doesn't exist, create a new one
                    $requestNo = 'CAR-REQ-'.now()->format('Ymd').'-'.str_pad((string) (CarAccessoryRequest::max('id') + 1), 4, '0', STR_PAD_LEFT);

                    $carRequest = CarAccessoryRequest::create([
                        'request_no' => $requestNo,
                        'request_date' => now()->toDateString(),
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                    ]);
                    
                    session(['current_search_request_id' => $carRequest->id]);
                }
            }

            // Create items for each car with the accessory
            foreach ($validated['car_ids'] as $carId) {
                CarAccessoryRequestItem::create([
                    'request_id' => $carRequest->id,
                    'car_id' => $carId,
                    'accessory_id' => null, // Set to null as requested
                    'product_id' => $validated['accessory_id'], // Store the product ID directly
                    'quantity' => $validated['quantity'] ?? null, // Use provided quantity or null for later request
                    'sell_price' => $validated['sell_price'] ?? null, // Use provided sell_price or null
                ]);
            }

            $quantityText = $validated['quantity'] ? " with quantity {$validated['quantity']}" : " (quantity to be determined later)";
            $priceText = $validated['sell_price'] ? " and price {$validated['sell_price']}" : "";
            
            return response()->json([
                'success' => true,
                'message' => "Cars successfully linked with accessory{$quantityText}{$priceText}! Request ID: {$carRequest->id}",
                'request_id' => $carRequest->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error linking cars with accessory: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createRequest(Request $request)
    {
        $validated = $request->validate([
            'selected_cars' => 'required|array|min:1',
            'selected_cars.*' => 'integer|exists:sub_products,id',
        ]);

        $cars = SubProduct::whereIn('id', $validated['selected_cars'])->get();
        $accessories = \DB::table('product_services as ps')
            ->leftJoin('product_service_categories as c', 'c.id', '=', 'ps.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'ps.brand_id')
            ->leftJoin('sub_brands as sb', 'sb.id', '=', 'ps.sub_brand_id')
            ->where('ps.created_by', \Auth::user()->creatorId())
            ->where('c.is_manufacturer', true) // Only show products from manufacturer categories
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->orderBy('sb.name')
            ->orderBy('ps.name')
            ->get([
                'ps.id as product_id',
                \DB::raw("CONCAT(COALESCE(c.name,''),' / ',COALESCE(b.name,''),' / ',COALESCE(sb.name,''),' / ',COALESCE(ps.name,'')) as label")
            ]);

        return view('car_manufacturers.request_form', compact('cars', 'accessories'));
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'request_date' => 'nullable|date',
            'cars' => 'required|array|min:1',
            'cars.*.car_id' => 'required|integer|exists:sub_products,id',
            'cars.*.items' => 'required|array|min:1',
            'cars.*.items.*.accessory_id' => 'required|integer|exists:sub_products,id',
            'cars.*.items.*.quantity' => 'required|integer|min:1',
            'cars.*.items.*.sell_price' => 'nullable|numeric|min:0',
        ]);

        $requestNo = 'CAR-REQ-'.now()->format('Ymd').'-'.str_pad((string) (CarAccessoryRequest::max('id') + 1), 4, '0', STR_PAD_LEFT);

        $carRequest = CarAccessoryRequest::create([
            'request_no' => $requestNo,
            'request_date' => $validated['request_date'] ?? now()->toDateString(),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['cars'] as $carData) {
            $carId = $carData['car_id'];
            foreach ($carData['items'] as $item) {
                CarAccessoryRequestItem::create([
                    'request_id' => $carRequest->id,
                    'car_id' => $carId,
                    'accessory_id' => $item['accessory_id'],
                    'quantity' => $item['quantity'],
                    'sell_price' => $item['sell_price'] ?? null,
                ]);
            }
        }

        return redirect()->route('car_accessories.index')->with('success', 'Car accessory request saved.');
    }

    /**
     * Clear the current search session and redirect to index
     */
    public function clearSession()
    {
        // Clear the current search session request
        session()->forget('current_search_request_id');
        session()->forget('saved_accessories_list');
        
        return redirect()->route('car_accessories.index')->with('success', 'Search session cleared. You can start a new search.');
    }

    /**
     * Save accessories list to session
     */
    public function saveList(Request $request)
    {
        $validated = $request->validate([
            'accessories' => 'required|array|min:1',
            'accessories.*.accessoryId' => 'required|integer|exists:product_services,id',
            'accessories.*.productId' => 'required|integer|exists:product_services,id',
            'accessories.*.label' => 'required|string',
            'accessories.*.quantity' => 'required|integer|min:1',
            'accessories.*.price' => 'nullable|numeric|min:0',
            'accessories.*.available' => 'required|integer|min:0',
            'accessories.*.carId' => 'required|integer|exists:sub_products,id',
            'accessories.*.carName' => 'required|string',
        ]);

        try {
            // Store the accessories list in session
            session(['saved_accessories_list' => $validated['accessories']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Accessories list saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving accessories list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create accessories request from saved list
     */
    public function createRequestFromList(Request $request)
    {
        $validated = $request->validate([
            'accessories' => 'required|array|min:1',
            'accessories.*.accessoryId' => 'required|integer|exists:product_services,id',
            'accessories.*.productId' => 'required|integer|exists:product_services,id',
            'accessories.*.label' => 'required|string',
            'accessories.*.quantity' => 'required|integer|min:1',
            'accessories.*.price' => 'nullable|numeric|min:0',
            'accessories.*.available' => 'required|integer|min:0',
            'accessories.*.carId' => 'required|integer|exists:sub_products,id',
            'accessories.*.carName' => 'required|string',
        ]);

        try {
            // Create a new car accessory request
            $requestNo = 'CAR-REQ-'.now()->format('Ymd').'-'.str_pad((string) (CarAccessoryRequest::max('id') + 1), 4, '0', STR_PAD_LEFT);

            $carRequest = CarAccessoryRequest::create([
                'request_no' => $requestNo,
                'request_date' => now()->toDateString(),
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Create items for each car-accessory combination
            foreach ($validated['accessories'] as $accessory) {
                CarAccessoryRequestItem::create([
                    'request_id' => $carRequest->id,
                    'car_id' => $accessory['carId'],
                    'accessory_id' => null, // Set to null as requested
                    'product_id' => $accessory['productId'], // Store the product ID directly
                    'quantity' => $accessory['quantity'],
                    'sell_price' => $accessory['price'] ?? null,
                ]);
            }

            // Clear the saved list from session
            session()->forget('saved_accessories_list');
            
            return response()->json([
                'success' => true,
                'message' => 'Accessories request created successfully!',
                'redirect_url' => route('car_accessories.show', $carRequest->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating accessories request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear saved accessories list from session
     */
    public function clearSavedList()
    {
        session()->forget('saved_accessories_list');
        
        return response()->json([
            'success' => true,
            'message' => 'Saved accessories list cleared successfully!'
        ]);
    }
    

}
