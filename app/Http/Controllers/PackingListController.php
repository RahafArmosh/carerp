<?php

namespace App\Http\Controllers;

use App\Models\PackingList;
use App\Models\PackingListItem;
use App\Models\PackingBoxItem;
use App\Models\PickList;
use App\Models\SaleOrder;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class PackingListController extends Controller
{
    /**
     * Display a listing of packing lists
     */
    public function index(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = \Auth::user()->creatorId();
        $customers = Customer::where('created_by', $creatorId)->pluck('name', 'id');
        $customers->prepend('All', '');

        $statusOptions = [
            '' => __('All Statuses'),
            'draft' => __('Draft'),
            'under_packing' => __('Under Packing'),
            'partially_packed' => __('Partially Packed'),
            'packing_completed' => __('Packing Completed'),
        ];

        $query = PackingList::where('created_by', $creatorId)
            ->with(['customer', 'saleOrder', 'pickList', 'packer']);

        if (!empty($request->customer_id)) {
            $query->where('customer_id', $request->customer_id);
        }
        if (!empty($request->date_from) && !empty($request->date_to)) {
            $query->whereBetween('packing_list_date', [$request->date_from, $request->date_to]);
        }
        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $packingLists = $query->orderBy('packing_list_date', 'desc')->get();

        return view('packinglist.index', compact('packingLists', 'customers', 'statusOptions'));
    }

    /**
     * Display the specified packing list
     */
    public function show($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'saleOrder', 'pickList', 'packer', 'items', 'creator'])
                ->firstOrFail();

            // Filter items: only show items with box_no, and group by part_no + box_no to show unique combinations
            $filteredItems = $packingList->items
                ->filter(function($item) {
                    // Only show items that have a box_no (not null and not empty)
                    return !empty($item->box_no) && trim($item->box_no) !== '';
                })
                ->groupBy(function($item) {
                    // Group by part_no and box_no combination
                    return strtoupper(trim($item->part_no ?? '')) . '_' . trim($item->box_no ?? '');
                })
                ->map(function($groupedItems) {
                    // For each unique combination, take the first item and sum packed_qty
                    $firstItem = $groupedItems->first();
                    $totalPackedQty = $groupedItems->sum('packed_qty');
                    
                    // Create a new item object with summed quantity
                    $item = clone $firstItem;
                    $item->packed_qty = $totalPackedQty;
                    return $item;
                })
                ->values(); // Reset keys to sequential numbers

            // Replace items collection with filtered items
            $packingList->setRelation('items', $filteredItems);

            return view('packinglist.show', compact('packingList'));
        } catch (\Exception $e) {
            return redirect()->route('packinglist.index')->with('error', __('Packing list not found.'));
        }
    }

    /**
     * Update packing list status only (manual status change)
     * Status: draft, under_packing, partially_packed, packing_completed
     */
    public function updateStatus(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:draft,under_packing,partially_packed,packing_completed',
        ]);

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $packingList->status = $validated['status'];
            $packingList->save();

            return redirect()->back()->with('success', __('Status updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update status: ') . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the packing list
     */
    public function edit($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'saleOrder', 'pickList', 'packer', 'items', 'creator'])
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return redirect()->route('packinglist.show', \Crypt::encrypt($packingList->id))
                    ->with('info', __('Cannot edit packing list once it has been closed or fully packed.'));
            }

            // Filter items: only show items with box_no, and group by part_no + box_no to show unique combinations
            $filteredItems = $packingList->items
                ->filter(function($item) {
                    // Only show items that have a box_no (not null and not empty)
                    return !empty($item->box_no) && trim($item->box_no) !== '';
                })
                ->groupBy(function($item) {
                    // Group by part_no and box_no combination
                    return strtoupper(trim($item->part_no ?? '')) . '_' . trim($item->box_no ?? '');
                })
                ->map(function($groupedItems) {
                    // For each unique combination, take the first item and sum packed_qty
                    $firstItem = $groupedItems->first();
                    $totalPackedQty = $groupedItems->sum('packed_qty');
                    
                    // Create a new item object with summed quantity
                    $item = clone $firstItem;
                    $item->packed_qty = $totalPackedQty;
                    return $item;
                })
                ->values(); // Reset keys to sequential numbers

            // Replace items collection with filtered items
            $packingList->setRelation('items', $filteredItems);

            $users = User::where('created_by', \Auth::user()->creatorId())
                ->orWhere('id', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');

            return view('packinglist.edit', compact('packingList', 'users'));
        } catch (\Exception $e) {
            return redirect()->route('packinglist.index')->with('error', __('Packing list not found.'));
        }
    }

    /**
     * Update the packing list
     */
    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['saleOrder'])
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return redirect()->back()->with('error', __('Cannot edit packing list once it has been closed or fully packed.'));
            }

            $validated = $request->validate([
                'packing_ref' => 'nullable|string|max:255',
                'packing_list_date' => 'required|date',
                'packed_by' => 'nullable|exists:users,id',
                'status' => 'nullable|string|in:draft,under_packing,partially_packed,packing_completed',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:packing_list_items,id',
                'items.*.box_no' => 'nullable|string|max:255',
                'items.*.part_no' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.packed_qty' => 'required|numeric|min:0',
                'items.*.box_l' => 'nullable|numeric|min:0',
                'items.*.box_w' => 'nullable|numeric|min:0',
                'items.*.box_h' => 'nullable|numeric|min:0',
                'items.*.box_weight' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();
            try {
                $packingList->packing_ref = $validated['packing_ref'] ?? null;
                $packingList->packing_list_date = $validated['packing_list_date'];
                $packingList->packed_by = $validated['packed_by'] ?? null;
                $packingList->save();

                // Update items and sync with sale order items
                foreach ($validated['items'] as $itemData) {
                    $item = PackingListItem::where('id', $itemData['id'])
                        ->where('packing_list_id', $packingList->id)
                        ->firstOrFail();

                    $item->box_no = $itemData['box_no'] ?? null;
                    $item->part_no = $itemData['part_no'] ?? null;
                    $item->description = $itemData['description'] ?? null;
                    $item->packed_qty = $itemData['packed_qty'] ?? 0;
                    $item->box_l = $itemData['box_l'] ?? null;
                    $item->box_w = $itemData['box_w'] ?? null;
                    $item->box_h = $itemData['box_h'] ?? null;
                    $item->box_weight = $itemData['box_weight'] ?? null;
                    $item->save();

                    // Update corresponding sale order item's packed_qty
                    // This will be done after all items are processed
                }

                // After all items are updated, recalculate packed_qty for all related sale order items.
                $this->syncPackedQtyToSaleOrder($packingList);
                $this->refreshPackingListStatus($packingList);
                $this->syncSaleOrderStatusFromPackedQty($packingList);

                DB::commit();

                return redirect()->route('packinglist.show', $id)->with('success', __('Packing list updated successfully. Sale order packed quantities have been updated.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Packing list update failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', __('Failed to update packing list: ') . $e->getMessage());
        }
    }

    /**
     * Generate a new box number
     */
    public function generateBox($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return response()->json(['error' => __('Cannot add more boxes. Packing list is closed/completed.')], 400);
            }

            // Get the highest box number for this packing list
            $maxBoxNo = PackingListItem::where('packing_list_id', $packingList->id)
                ->whereNotNull('box_no')
                ->where('box_no', '!=', '')
                ->selectRaw('CAST(SUBSTRING_INDEX(box_no, "-", -1) AS UNSIGNED) as box_num')
                ->orderBy('box_num', 'desc')
                ->value('box_num');

            $nextBoxNum = ($maxBoxNo ?? 0) + 1;
            $boxNo = $packingList->packing_list_no . '-' . str_pad($nextBoxNum, 3, '0', STR_PAD_LEFT);

            // Store current box in session
            session(['current_box_' . $packingList->id => $boxNo]);

            return response()->json([
                'success' => true,
                'box_no' => $boxNo,
                'message' => __('Box number generated successfully.')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to generate box number: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Scan and validate part number
     */
    public function scanPart(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['pickList'])
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return response()->json(['error' => __('Cannot scan parts. Packing list is closed/completed.')], 400);
            }

            $request->validate([
                'part_no' => 'required|string|max:255',
            ]);

            $partNo = trim($request->part_no);

            // Check if part number exists in the sale order items (aggregate all lines with same part_no)
            if ($packingList->saleOrder) {
                $saleOrderItems = \App\Models\SaleOrderItem::where('sale_order_id', $packingList->sale_order_id)
                    ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                    ->get();

                if ($saleOrderItems->isNotEmpty()) {
                    // Total required for packing = sum of stock_qty (reserved) across all SO lines with this part_no
                    $reqQty = (float) $saleOrderItems->sum(fn ($i) => (float)($i->stock_qty ?? $i->req_qty ?? 0));
                    $firstItem = $saleOrderItems->first();
                    $currentPackedQty = PackingBoxItem::where('packing_list_id', $packingList->id)
                        ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                        ->sum('qty');
                    $remainingQty = $reqQty - $currentPackedQty;

                    return response()->json([
                        'success' => true,
                        'part_no' => $firstItem->part_no,
                        'description' => $firstItem->description ?? '',
                        'req_qty' => $reqQty,
                        'current_packed_qty' => $currentPackedQty,
                        'remaining_qty' => max(0, $remainingQty),
                        'message' => __('Part number found.')
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => __('Part number not found in this packing list.')
            ], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to scan part number: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Add item to current box
     */
    public function addToBox(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return response()->json(['error' => __('Cannot add items. Packing list is closed/completed.')], 400);
            }

            $request->validate([
                'part_no' => 'required|string|max:255',
                'qty' => 'required|numeric|min:0',
            ]);

            $partNo = trim($request->part_no);
            $qty = (float)$request->qty;
            $currentBoxNo = session('current_box_' . $packingList->id);

            if (!$currentBoxNo) {
                return response()->json(['error' => __('No box is currently open. Please generate a box number first.')], 400);
            }

            if ($qty <= 0) {
                return response()->json(['error' => __('Quantity must be greater than 0.')], 400);
            }

            // Validate part number belongs to sale order
            if (!$packingList->saleOrder) {
                return response()->json(['error' => __('Sale order not found for this packing list.')], 404);
            }

            $saleOrderItems = \App\Models\SaleOrderItem::where('sale_order_id', $packingList->sale_order_id)
                ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                ->get();

            if ($saleOrderItems->isEmpty()) {
                return response()->json(['error' => __('Part number not found in the sale order.')], 404);
            }

            // Total required for packing = sum of stock_qty across all SO lines with this part_no (e.g. 50 + 10 = 60)
            $reqQtyForPacking = (float) $saleOrderItems->sum(fn ($i) => (float)($i->stock_qty ?? $i->req_qty ?? 0));
            $currentPackedQty = PackingBoxItem::where('packing_list_id', $packingList->id)
                ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                ->sum('qty');
            $remainingQty = $reqQtyForPacking - $currentPackedQty;

            if ($qty > $remainingQty) {
                return response()->json([
                    'error' => __('Quantity exceeds remaining quantity. Required: ') . number_format($reqQtyForPacking, 2) .
                               ', Current Packed: ' . number_format($currentPackedQty, 2) .
                               ', Remaining: ' . number_format($remainingQty, 2)
                ], 400);
            }

            $firstSaleOrderItem = $saleOrderItems->first();

            DB::beginTransaction();
            try {
                $packingBoxItem = new PackingBoxItem();
                $packingBoxItem->packing_list_id = $packingList->id;
                $packingBoxItem->box_no = $currentBoxNo;
                $packingBoxItem->part_no = $partNo;
                $packingBoxItem->description = $firstSaleOrderItem->description ?? '';
                $packingBoxItem->qty = $qty;
                $packingBoxItem->save();

                DB::commit();

                $currentPackedQty = PackingBoxItem::where('packing_list_id', $packingList->id)
                    ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                    ->sum('qty');
                $remainingQty = $reqQtyForPacking - $currentPackedQty;

                return response()->json([
                    'success' => true,
                    'message' => __('Item added to box successfully.'),
                    'current_packed_qty' => $currentPackedQty,
                    'remaining_qty' => max(0, $remainingQty),
                    'item_id' => $packingBoxItem->id
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to add item to box: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Close current box and save box details to items
     */
    public function closeBox(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $validated = $request->validate([
                'box_l' => 'nullable|numeric|min:0',
                'box_w' => 'nullable|numeric|min:0',
                'box_h' => 'nullable|numeric|min:0',
                'box_weight' => 'nullable|numeric|min:0',
            ]);

            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            if (($packingList->status ?? '') === 'packing_completed') {
                return response()->json(['error' => __('Cannot close box. Packing list is already closed/completed.')], 400);
            }

            $currentBoxNo = session('current_box_' . $packingList->id);

            if (!$currentBoxNo) {
                return response()->json(['error' => __('No box is currently open.')], 400);
            }

            DB::beginTransaction();
            try {
                // Get all box items in the current box
                $boxItems = PackingBoxItem::where('packing_list_id', $packingList->id)
                    ->where('box_no', $currentBoxNo)
                    ->get();

                // Consolidate box items into packing_list_items (group by part_no)
                foreach ($boxItems->groupBy(function($item) {
                    return strtoupper(trim($item->part_no));
                }) as $partNo => $items) {
                    $totalQty = $items->sum('qty');
                    $firstItem = $items->first();
                    
                    // Find or create packing list item
                    $packingListItem = PackingListItem::where('packing_list_id', $packingList->id)
                        ->where('box_no', $currentBoxNo)
                        ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper($partNo)])
                        ->first();

                    if ($packingListItem) {
                        // Update existing item
                        $packingListItem->packed_qty = $totalQty;
                    } else {
                        // Create new item
                        $packingListItem = new PackingListItem();
                        $packingListItem->packing_list_id = $packingList->id;
                        $packingListItem->box_no = $currentBoxNo;
                        $packingListItem->part_no = $firstItem->part_no;
                        $packingListItem->description = $firstItem->description ?? '';
                        $packingListItem->packed_qty = $totalQty;
                    }

                    // Save entered box dimensions/weight for this closed box.
                    $packingListItem->box_l = $validated['box_l'] ?? null;
                    $packingListItem->box_w = $validated['box_w'] ?? null;
                    $packingListItem->box_h = $validated['box_h'] ?? null;
                    $packingListItem->box_weight = $validated['box_weight'] ?? null;
                    $packingListItem->save();
                }

                // Sync packed quantities to sale order
                $this->syncPackedQtyToSaleOrder($packingList);
                $this->refreshPackingListStatus($packingList);
                $this->syncSaleOrderStatusFromPackedQty($packingList);

                // Clear current box from session
                session()->forget('current_box_' . $packingList->id);

                DB::commit();

                // Get box items count
                $boxItemsCount = $boxItems->count();

                return response()->json([
                    'success' => true,
                    'message' => __('Box closed successfully. ' . $boxItemsCount . ' item(s) saved.'),
                    'box_no' => $currentBoxNo,
                    'items_count' => $boxItemsCount
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to close box: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Close packing list (set status to packed)
     */
    public function closePackingList($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            // Close any open box first
            session()->forget('current_box_' . $packingList->id);

            // Always resync SO packed values before finalizing status.
            $this->syncPackedQtyToSaleOrder($packingList);

            // Explicit close action always finalizes/locks the packing list.
            $packingList->status = 'packing_completed';
            $packingList->save();
            $this->syncSaleOrderStatusFromPackedQty($packingList);

            return response()->json([
                'success' => true,
                'message' => __('Packing list closed successfully.'),
                'redirect_url' => route('packinglist.show', $id)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to close packing list: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Get current box items
     */
    public function getCurrentBoxItems($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $packingListId = \Crypt::decrypt($id);
            $packingList = PackingList::where('id', $packingListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $currentBoxNo = session('current_box_' . $packingList->id);

            if (!$currentBoxNo) {
                return response()->json([
                    'success' => true,
                    'box_no' => null,
                    'items' => []
                ]);
            }

            $items = PackingBoxItem::where('packing_list_id', $packingList->id)
                ->where('box_no', $currentBoxNo)
                ->select('part_no', 'description', 'qty as packed_qty')
                ->get();

            return response()->json([
                'success' => true,
                'box_no' => $currentBoxNo,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to get box items: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to sync packed_qty to sale order
     * Uses PackingBoxItem (for box-level tracking) and PackingListItem (for summary) to calculate totals
     */
    private function syncPackedQtyToSaleOrder($packingList)
    {
        if (!$packingList->saleOrder) {
            return;
        }

        // Get all packing lists for this sale order
        $allPackingLists = PackingList::where('sale_order_id', $packingList->sale_order_id)
            ->pluck('id');

        // Build packed totals by normalized part no. Prefer box-level source of truth when available.
        $packedByPartFromBoxItems = PackingBoxItem::whereIn('packing_list_id', $allPackingLists)
            ->whereNotNull('part_no')
            ->where('part_no', '!=', '')
            ->selectRaw('UPPER(TRIM(part_no)) as normalized_part_no, SUM(qty) as total_qty')
            ->groupBy('normalized_part_no')
            ->pluck('total_qty', 'normalized_part_no');

        $packedByPartFromListItems = PackingListItem::whereIn('packing_list_id', $allPackingLists)
            ->whereNotNull('part_no')
            ->where('part_no', '!=', '')
            ->selectRaw('UPPER(TRIM(part_no)) as normalized_part_no, SUM(packed_qty) as total_qty')
            ->groupBy('normalized_part_no')
            ->pluck('total_qty', 'normalized_part_no');

        $packedByPart = $packedByPartFromBoxItems->isNotEmpty()
            ? $packedByPartFromBoxItems
            : $packedByPartFromListItems;

        $saleOrderItems = \App\Models\SaleOrderItem::where('sale_order_id', $packingList->sale_order_id)
            ->orderBy('id')
            ->get()
            ->groupBy(function ($item) {
                return strtoupper(trim((string) ($item->part_no ?? '')));
            });

        // Recalculate every SO item from scratch to avoid stale values from prior packing attempts.
        foreach ($saleOrderItems as $normalizedPartNo => $items) {
            if ($normalizedPartNo === '') {
                foreach ($items as $saleOrderItem) {
                    $stockQty = (float) ($saleOrderItem->stock_qty ?? $saleOrderItem->req_qty ?? 0);
                    $saleOrderItem->packed_qty = 0;
                    $saleOrderItem->discrepancy = 0 - $stockQty;
                    $saleOrderItem->save();
                }
                continue;
            }

            $remaining = (float) ($packedByPart[$normalizedPartNo] ?? 0);

            foreach ($items as $saleOrderItem) {
                $stockQty = (float) ($saleOrderItem->stock_qty ?? $saleOrderItem->req_qty ?? 0);
                $assign = $remaining <= 0 ? 0 : min($stockQty, $remaining);
                $saleOrderItem->packed_qty = $assign;
                $saleOrderItem->discrepancy = $assign - $stockQty;
                $saleOrderItem->save();
                $remaining -= $assign;
            }
        }
    }

    /**
     * Recalculate packing list status from packed quantity progress.
     */
    private function refreshPackingListStatus(PackingList $packingList): void
    {
        $totalExpected = $this->getPackingExpectedQty($packingList);
        $totalPacked = $this->getPackingPackedQty($packingList);

        if ($totalPacked <= 0) {
            $packingList->status = 'draft';
        } elseif ($totalExpected > 0 && $totalPacked >= $totalExpected) {
            $packingList->status = 'packing_completed';
        } else {
            $packingList->status = 'partially_packed';
        }

        $packingList->save();
    }

    /**
     * Determine expected qty for the packing list.
     */
    private function getPackingExpectedQty(PackingList $packingList): float
    {
        if (!empty($packingList->sale_order_id)) {
            return (float) \App\Models\SaleOrderItem::where('sale_order_id', $packingList->sale_order_id)
                ->sum(DB::raw('COALESCE(stock_qty, req_qty, 0)'));
        }

        if (!empty($packingList->pick_list_id)) {
            return (float) \App\Models\PickListItem::where('pick_list_id', $packingList->pick_list_id)->sum('req_qty');
        }

        return (float) PackingListItem::where('packing_list_id', $packingList->id)->sum('packed_qty');
    }

    /**
     * Determine packed qty for the packing list.
     * Prefer box-level entries when available.
     */
    private function getPackingPackedQty(PackingList $packingList): float
    {
        $packedFromBoxes = (float) PackingBoxItem::where('packing_list_id', $packingList->id)->sum('qty');
        if ($packedFromBoxes > 0) {
            return $packedFromBoxes;
        }

        return (float) PackingListItem::where('packing_list_id', $packingList->id)->sum('packed_qty');
    }

    /**
     * Update related sale order status from current packed progress.
     */
    private function syncSaleOrderStatusFromPackedQty(PackingList $packingList): void
    {
        if (empty($packingList->sale_order_id)) {
            return;
        }

        $saleOrder = SaleOrder::where('id', $packingList->sale_order_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->first();

        if (!$saleOrder || in_array($saleOrder->status, ['shipped', 'invoiced', 'converted'], true)) {
            return;
        }

        $saleOrderItems = \App\Models\SaleOrderItem::where('sale_order_id', $saleOrder->id)->get();
        $totalRequired = (float) $saleOrderItems->sum(function ($item) {
            return (float) ($item->stock_qty ?? $item->req_qty ?? 0);
        });
        $totalPacked = (float) $saleOrderItems->sum(function ($item) {
            return (float) ($item->packed_qty ?? 0);
        });

        $saleOrder->status = ($totalRequired > 0 && $totalPacked >= $totalRequired)
            ? 'packed'
            : 'packing_in_progress';
        $saleOrder->save();
    }
}
