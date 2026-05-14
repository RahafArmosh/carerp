<?php

namespace App\Http\Controllers;

use App\Models\PickList;
use App\Models\PickListItem;
use App\Models\PickListStatusLog;
use App\Models\SaleOrder;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\PackingList;
use App\Models\PackingListItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickListController extends Controller
{
    /**
     * Display a listing of pick lists
     */
    public function index(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $query = PickList::where('created_by', \Auth::user()->creatorId())
            ->with(['saleOrder', 'customer', 'picker', 'assignedUser', 'packingList']);

        if (!empty($request->customer_id)) {
            $query->where('customer_id', $request->customer_id);
        }

        if (!empty($request->date_from) && !empty($request->date_to)) {
            $query->whereBetween('pick_list_date', [$request->date_from, $request->date_to]);
        }

        $pickLists = $query->orderBy('pick_list_date', 'desc')->get();

        $customers = \App\Models\Customer::where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');
        $customers->prepend('All', '');

        return view('picklist.index', compact('pickLists', 'customers'));
    }

    /**
     * Display the specified pick list
     */
    public function show($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['saleOrder', 'customer', 'picker', 'assignedUser', 'items', 'creator', 'packingList'])
                ->firstOrFail();

            // Company users (including the company user) for assigning pick list
            $assignUsers = User::where(function ($q) {
                    $q->where('created_by', \Auth::user()->creatorId())->where('type', '!=', 'client')
                      ->orWhere('id', \Auth::user()->creatorId());
                })
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id');

            // Get bin location custom field for auto-population
            $creatorId = \Auth::user()->creatorId();
            $binLocationField = CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->whereRaw('LOWER(TRIM(REPLACE(REPLACE(REPLACE(name, " ", ""), "_", ""), "-", ""))) = ?', [
                    strtolower(trim(str_replace([' ', '_', '-'], '', 'BIN LOCATION 1')))
                ])
                ->first();

            // Pre-load bin locations for items that don't have them yet
            if ($binLocationField) {
                foreach ($pickList->items as $item) {
                    if (empty($item->bin_location) && !empty($item->part_no)) {
                        // Find sub-product by part_no
                        $subProduct = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($item->part_no))])
                            ->where('created_by', $creatorId)
                            ->first();
                        
                        if ($subProduct) {
                            // Get custom field value for bin location
                            $binLocationValue = CustomFieldValue::where('record_id', $subProduct->id)
                                ->where('field_id', $binLocationField->id)
                                ->first();
                            
                            if ($binLocationValue && !empty(trim($binLocationValue->value))) {
                                $item->bin_location = trim($binLocationValue->value);
                                $item->save(); // Auto-save if found
                            }
                        }
                    }
                }
                
                // Reload items after updates
                $pickList->load('items');
            }

            return view('picklist.show', compact('pickList', 'assignUsers'));
        } catch (\Exception $e) {
            return redirect()->route('picklist.index')->with('error', __('Pick list not found.'));
        }
    }

    /**
     * Get bin location from custom fields for a part number
     */
    public function getBinLocation(Request $request)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $request->validate([
                'part_no' => 'required|string',
            ]);

            $partNo = trim($request->part_no);
            $creatorId = \Auth::user()->creatorId();
            
            // Find bin location custom field
            $binLocationField = CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->whereRaw('LOWER(TRIM(REPLACE(REPLACE(REPLACE(name, " ", ""), "_", ""), "-", ""))) = ?', [
                    strtolower(trim(str_replace([' ', '_', '-'], '', 'Bin Location')))
                ])
                ->first();

            if (!$binLocationField) {
                return response()->json([
                    'success' => false,
                    'message' => __('Bin Location custom field not found.')
                ]);
            }

            // Find sub-product by part_no
            $subProduct = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper($partNo)])
                ->where('created_by', $creatorId)
                ->first();

            if (!$subProduct) {
                return response()->json([
                    'success' => false,
                    'message' => __('Sub-product not found for part number: ') . $partNo
                ]);
            }

            // Get custom field value for bin location
            $binLocationValue = CustomFieldValue::where('record_id', $subProduct->id)
                ->where('field_id', $binLocationField->id)
                ->first();

            $binLocation = null;
            if ($binLocationValue && !empty(trim($binLocationValue->value))) {
                $binLocation = trim($binLocationValue->value);
            }

            return response()->json([
                'success' => true,
                'bin_location' => $binLocation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => __('Failed to get bin location: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pick list item tick status
     */
    public function updateItemTick(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $request->validate([
                'item_id' => 'required|exists:pick_list_items,id',
                'tick' => 'required|boolean',
            ]);

            $item = PickListItem::where('id', $request->item_id)
                ->where('pick_list_id', $pickList->id)
                ->firstOrFail();

            $item->tick = $request->tick;
            $item->save();

            // If ticking, set picked_by if not already set
            if ($request->tick && !$pickList->picked_by) {
                $pickList->picked_by = \Auth::user()->id;
                $pickList->save();
            }

            return response()->json(['success' => true, 'message' => __('Item updated successfully.')]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Failed to update item: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the pick list
     */
    public function edit($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['saleOrder', 'customer', 'picker', 'assignedUser', 'items', 'creator', 'packingList'])
                ->firstOrFail();

            if ($pickList->packingList) {
                return redirect()->route('picklist.show', \Crypt::encrypt($pickList->id))
                    ->with('info', __('Cannot edit pick list once it has been converted to a packing list.'));
            }

            // Do not allow editing picking until the pick list is assigned
            if (!$pickList->assigned_to) {
                return redirect()->route('picklist.show', \Crypt::encrypt($pickList->id))
                    ->with('error', __('Please assign this pick list to a user before editing picking.'));
            }

            // Get bin location custom field for auto-population
            $creatorId = \Auth::user()->creatorId();
            $binLocationField = CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->whereRaw('LOWER(TRIM(REPLACE(REPLACE(REPLACE(name, " ", ""), "_", ""), "-", ""))) = ?', [
                    strtolower(trim(str_replace([' ', '_', '-'], '', 'Bin Location')))
                ])
                ->first();

            // Pre-load bin locations for items that don't have them yet
            if ($binLocationField) {
                foreach ($pickList->items as $item) {
                    if (empty($item->bin_location) && !empty($item->part_no)) {
                        // Find sub-product by part_no
                        $subProduct = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($item->part_no))])
                            ->where('created_by', $creatorId)
                            ->first();
                        
                        if ($subProduct) {
                            // Get custom field value for bin location
                            $binLocationValue = CustomFieldValue::where('record_id', $subProduct->id)
                                ->where('field_id', $binLocationField->id)
                                ->first();
                            
                            if ($binLocationValue && !empty(trim($binLocationValue->value))) {
                                $item->bin_location = trim($binLocationValue->value);
                                $item->save(); // Auto-save if found
                            }
                        }
                    }
                }
                
                // Reload items after updates
                $pickList->load('items');
            }

            return view('picklist.edit', compact('pickList'));
        } catch (\Exception $e) {
            return redirect()->route('picklist.index')->with('error', __('Pick list not found.'));
        }
    }

    /**
     * Update pick list details
     */
    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['saleOrder', 'packingList'])
                ->firstOrFail();
            $oldStatus = $pickList->status;

            if ($pickList->packingList) {
                return redirect()->back()->with('error', __('Cannot edit pick list once it has been converted to a packing list.'));
            }

            // Do not allow saving picking changes unless the pick list is assigned
            if (!$pickList->assigned_to) {
                return redirect()->back()->with('error', __('Please assign this pick list to a user before saving picking changes.'));
            }

            // Normalize items: ensure each row has an id and treat empty picked_qty as 0
            $items = $request->input('items', []);
            $normalizedItems = [];
            foreach ($items as $row) {
                $itemId = $row['id'] ?? null;
                if ($itemId === null) {
                    // Skip malformed rows; validation will also guard against this
                    continue;
                }

                $normalizedItems[] = [
                    'id' => $itemId,
                    'bin_location' => $row['bin_location'] ?? null,
                    // If user clears the field, submitted value is ""; convert that to 0 so numeric|min:0 passes
                    'picked_qty' => (isset($row['picked_qty']) && $row['picked_qty'] !== '') ? $row['picked_qty'] : 0,
                    // Let validation handle boolean casting; missing tick is fine (treated as null here)
                    'tick' => $row['tick'] ?? null,
                ];
            }
            $request->merge(['items' => $normalizedItems]);

            $validated = $request->validate([
                'packing_ref' => 'nullable|string|max:255',
                'pick_list_date' => 'required|date',
                'assigned_to' => 'nullable|exists:users,id',
                'status' => 'nullable|in:draft,under_picking,partially_picked,picking_completed',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:pick_list_items,id',
                'items.*.bin_location' => 'nullable|string|max:255',
                'items.*.picked_qty' => 'nullable|numeric|min:0',
                'items.*.tick' => 'nullable|boolean',
            ]);

            DB::beginTransaction();
            try {
                $pickList->packing_ref = $validated['packing_ref'] ?? null;
                $pickList->pick_list_date = $validated['pick_list_date'];
                $pickList->assigned_to = $validated['assigned_to'] ?? $pickList->assigned_to;
                // Always set picked_by to the current logged-in user when saving picking
                $pickList->picked_by = \Auth::id();
                if (array_key_exists('status', $validated) && $validated['status'] !== null) {
                    $pickList->status = $validated['status'];
                }
                $pickList->save();

                // Update items
                foreach ($validated['items'] as $itemData) {
                    $item = PickListItem::where('id', $itemData['id'])
                        ->where('pick_list_id', $pickList->id)
                        ->firstOrFail();

                    $item->bin_location = $itemData['bin_location'] ?? null;
                    $item->picked_qty = $itemData['picked_qty'] ?? 0;
                    $item->tick = $itemData['tick'] ?? false;
                    $item->save();
                }

                // Auto-update status from picked vs required qty: picking_completed when total picked >= total req, otherwise partially_picked when any picked
                $totals = PickListItem::where('pick_list_id', $pickList->id)
                    ->selectRaw('COALESCE(SUM(req_qty), 0) as total_req, COALESCE(SUM(picked_qty), 0) as total_picked')
                    ->first();
                $totalReq = (float)($totals->total_req ?? 0);
                $totalPicked = (float)($totals->total_picked ?? 0);
                if ($totalReq > 0) {
                    if ($totalPicked >= $totalReq) {
                        $pickList->status = 'picking_completed';
                    } elseif ($totalPicked > 0) {
                        $pickList->status = 'partially_picked';
                    }
                    $pickList->save();
                }

                $this->logStatusChangeIfNeeded($pickList, $oldStatus, $pickList->status);

                // After all items are updated, sync picking_qty back to sale order items
                // IMPORTANT: Only update picking_qty, NOT packed_qty. Packed_qty should only be updated
                // when packing lists are created/updated, not when picking lists are created/updated.
                if ($pickList->saleOrder) {
                    // Get all unique part_nos from this pick list
                    $partNos = PickListItem::where('pick_list_id', $pickList->id)
                        ->whereNotNull('part_no')
                        ->where('part_no', '!=', '')
                        ->distinct()
                        ->pluck('part_no');

                    // Get all pick lists for this sale order
                    $allPickLists = PickList::where('sales_order_id', $pickList->sales_order_id)
                        ->pluck('id');

                    // For each part_no: distribute total picked qty across SO items (same part_no) one by one
                    foreach ($partNos as $partNo) {
                        $totalPickedQty = (float) PickListItem::whereIn('pick_list_id', $allPickLists)
                            ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper(trim($partNo))])
                            ->sum('picked_qty');

                        $saleOrderItems = \App\Models\SaleOrderItem::where('sale_order_id', $pickList->sales_order_id)
                            ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper(trim($partNo))])
                            ->orderBy('id')
                            ->get();

                        $remaining = $totalPickedQty;
                        foreach ($saleOrderItems as $saleOrderItem) {
                            $stockQty = (float)($saleOrderItem->stock_qty ?? $saleOrderItem->req_qty ?? 0);
                            $assign = $remaining <= 0 ? 0 : min($stockQty, $remaining);

                            // Only update picking_qty; keep packed_qty as-is (it is managed by packing lists)
                            $saleOrderItem->picking_qty = $assign;
                            $saleOrderItem->discrepancy = ($saleOrderItem->packed_qty ?? 0) - $stockQty;
                            $saleOrderItem->save();

                            $remaining -= $assign;
                        }
                    }
                }

                DB::commit();

                return redirect()->route('picklist.show', $id)->with('success', __('Pick list updated successfully. Sale order picking quantities have been updated.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Pick list update failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', __('Failed to update pick list: ') . $e->getMessage());
        }
    }

    /**
     * Update pick list status only (manual status change)
     * Status: draft, under_picking, partially_picked, picking_completed
     */
    public function updateStatus(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,under_picking,partially_picked,picking_completed',
        ]);

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();
            $oldStatus = $pickList->status;

            $pickList->status = $validated['status'];
            $pickList->save();
            $this->logStatusChangeIfNeeded($pickList, $oldStatus, $pickList->status);

            return redirect()->back()->with('success', __('Status updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update status: ') . $e->getMessage());
        }
    }

    /**
     * Display pick list status history.
     */
    public function statusLogs($id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['saleOrder', 'customer'])
                ->firstOrFail();

            $statusLogs = PickListStatusLog::where('pick_list_id', $pickList->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user:id,name')
                ->orderBy('changed_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            return view('picklist.status_logs', compact('pickList', 'statusLogs'));
        } catch (\Exception $e) {
            return redirect()->route('picklist.index')->with('error', __('Pick list not found.'));
        }
    }

    /**
     * Assign pick list to a user (picking assignment)
     */
    public function assign(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'assign_note' => 'nullable|string|max:1000',
        ]);

        try {
            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $pickList->assigned_to = $validated['assigned_to'];
            $pickList->assign_note = $validated['assign_note'] ?? $pickList->assign_note;
            $pickList->save();

            return redirect()->back()->with('success', __('Pick list has been assigned successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to assign pick list: ') . $e->getMessage());
        }
    }

    /**
     * Convert pick list to packing list
     */
    public function convertToPackingList(Request $request, $id)
    {
        if (!\Auth::user()->can('create sale order')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $creatorId = \Auth::user()->creatorId();
            $validated = $request->validate([
                'packed_by' => 'required|exists:users,id',
            ]);

            $allowedPacker = User::where('id', $validated['packed_by'])
                ->where(function ($q) use ($creatorId) {
                    $q->where(function ($sub) use ($creatorId) {
                        $sub->where('created_by', $creatorId)
                            ->where('type', '!=', 'client');
                    })->orWhere('id', $creatorId);
                })
                ->exists();

            if (!$allowedPacker) {
                return redirect()->back()->with('error', __('Selected packing user is invalid for this company.'));
            }

            $pickListId = \Crypt::decrypt($id);
            $pickList = PickList::where('id', $pickListId)
                ->where('created_by', $creatorId)
                ->with(['saleOrder', 'customer', 'items', 'packingList'])
                ->firstOrFail();

            // Check if packing list already exists
            if ($pickList->packingList) {
                return redirect()->route('packinglist.show', \Crypt::encrypt($pickList->packingList->id))
                    ->with('info', __('Packing list already exists for this pick list.'));
            }

            DB::beginTransaction();
            try {
                // Generate packing list number
                $lastPackingList = PackingList::where('created_by', \Auth::user()->creatorId())
                    ->withTrashed()
                    ->latest()
                    ->first();
                $packingListNo = $lastPackingList ? ((int)$lastPackingList->packing_list_no + 1) : 1;

                // Create packing list
                $packingList = new PackingList();
                $packingList->packing_list_no = $packingListNo;
                $packingList->customer_id = $pickList->customer_id;
                $packingList->sale_order_id = $pickList->sales_order_id;
                $packingList->pick_list_id = $pickList->id;
                $packingList->packing_ref = $pickList->packing_ref;
                $packingList->packing_list_date = date('Y-m-d');
                $packingList->packed_by = (int) $validated['packed_by'];
                $packingList->status = 'draft';
                $packingList->created_by = $creatorId;
                $packingList->save();

                // Create packing list items from pick list items
                foreach ($pickList->items as $pickListItem) {
                    // Only include items that have been picked (picked_qty > 0)
                    if ($pickListItem->picked_qty > 0) {
                        $packingListItem = new PackingListItem();
                        $packingListItem->packing_list_id = $packingList->id;
                        $packingListItem->box_no = null; // Can be set later
                        $packingListItem->part_no = $pickListItem->part_no;
                        $packingListItem->description = $pickListItem->description;
                        $packingListItem->packed_qty = $pickListItem->picked_qty;
                        $packingListItem->box_l = null;
                        $packingListItem->box_w = null;
                        $packingListItem->box_h = null;
                        $packingListItem->box_weight = null;
                        $packingListItem->save();
                    }
                }

                // Update related sale order status to indicate packing is in progress
                if (!empty($pickList->sales_order_id)) {
                    SaleOrder::where('id', $pickList->sales_order_id)
                        ->where('created_by', $creatorId)
                        ->update(['status' => 'packing_in_progress']);
                }

                // Do NOT sync packed_qty to sale order here — SO packed_qty is updated only when the user edits/saves the packing list.

                DB::commit();

                return redirect()->route('packinglist.show', \Crypt::encrypt($packingList->id))
                    ->with('success', __('Pick list converted to packing list successfully.'));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Convert pick list to packing list failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->id,
                'pick_list_id' => $id,
            ]);
            return redirect()->back()->with('error', __('Failed to convert to packing list: ') . $e->getMessage());
        }
    }

    /**
     * Save pick list status log when status actually changes.
     */
    private function logStatusChangeIfNeeded(PickList $pickList, ?string $oldStatus, ?string $newStatus): void
    {
        $normalizedOldStatus = $oldStatus ?: 'draft';
        $normalizedNewStatus = $newStatus ?: 'draft';

        if ($normalizedOldStatus === $normalizedNewStatus) {
            return;
        }

        PickListStatusLog::create([
            'pick_list_id' => $pickList->id,
            'user_id' => \Auth::id(),
            'old_status' => $normalizedOldStatus,
            'new_status' => $normalizedNewStatus,
            'changed_at' => now(),
            'created_by' => \Auth::user()->creatorId(),
        ]);
    }
}
