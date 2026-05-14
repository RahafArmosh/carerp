<?php

namespace App\Http\Controllers;

use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Asn;
use App\Models\AsnItem;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Pro;
use App\Models\ProItem;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\BillStatusChange;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class GrnController extends Controller
{
    /**
     * Display a listing of GRNs
     */
    public function index(Request $request)
    {
        // Allow access if user can view GRN or manage bills
        if (!\Auth::user()->can('view grn') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();

        // Build query with aggregated totals (avoid loading all item rows per GRN list row)
        $query = Grn::query()
            ->with([
                'asn:id,asn_no,supplier_inv_no',
                'supplier:id,name',
                'assignedUser:id,name',
                'bill:id,bill_id',
            ])
            ->withSum('items as total_qty', 'qty')
            ->withSum('items as total_received_qty', 'received_qty')
            ->withSum('items as total_amount', 'total_price')
            ->where('created_by', $creatorId);

        // Filter by assigned user - regular users only see their assigned GRNs, company users see all
        if ($user->type != 'company' && $user->type != 'super admin') {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('assigned_to')) {
            // Company users can filter by assigned user
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('grn_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('grn_date', '<=', $request->end_date);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by ASN
        if ($request->filled('asn_id')) {
            $query->where('asn_id', $request->asn_id);
        }

        // Filter by Supplier Invoice Number (from ASN)
        if ($request->filled('supplier_inv_no')) {
            $inv = trim($request->supplier_inv_no);
            $query->whereHas('asn', function ($q) use ($inv) {
                $q->where('supplier_inv_no', 'like', '%' . $inv . '%');
            });
        }

        // Filter by Box No (GRNs that contain at least one item with this box no via ASN item)
        if ($request->filled('box_no')) {
            $boxNo = trim($request->box_no);
            $query->whereHas('items', function ($q) use ($boxNo) {
                $q->whereHas('asnItem', function ($q2) use ($boxNo) {
                    $q2->where('box_no', $boxNo);
                });
            });
        }

        $grns = $query->orderBy('grn_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Distinct box numbers from GRN data (ASN items linked to GRN items) - no duplicates
        $boxNosQuery = AsnItem::whereHas('grnItems.grn', function ($q) use ($creatorId, $user) {
            $q->where('created_by', $creatorId);
            if ($user->type != 'company' && $user->type != 'super admin') {
                $q->where('assigned_to', $user->id);
            }
        })
            ->whereNotNull('box_no')
            ->where('box_no', '!=', '');
        $boxNos = $boxNosQuery->distinct()->orderBy('box_no')->pluck('box_no', 'box_no');

        // Get filter options for company users
        $users = null;
        $suppliers = null;
        $asns = null;
        if ($user->type == 'company' || $user->type == 'super admin') {
            $users = \App\Models\User::where('created_by', $creatorId)
                ->orWhere('id', $creatorId)
                ->get()
                ->pluck('name', 'id');
            
            $suppliers = \App\Models\Vender::where('created_by', $creatorId)
                ->get()
                ->pluck('name', 'id');
            
            $asns = Asn::where('created_by', $creatorId)
                ->get()
                ->map(function($asn) {
                    return [
                        'id' => $asn->id,
                        'name' => \Auth::user()->asnNumberFormat($asn->asn_no)
                    ];
                })
                ->pluck('name', 'id');
        }

        return view('grn.index', compact('grns', 'users', 'suppliers', 'asns', 'boxNos'));
    }

    /**
     * Display the specified GRN
     */
    public function show(Request $request, $id)
    {
        // Allow access if user can view GRN or manage bills
        if (!\Auth::user()->can('view grn') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $grnId = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('GRN Not Found.'));
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();

        $grn = Grn::with(['asn.currency', 'asn.supplier', 'supplier', 'assignedUser', 'bill'])
            ->where('created_by', $creatorId)
            ->findOrFail($grnId);

        $lockGrnQtyEditing = $this->isGrnQtyLockedByAsnConversion($grn->asn_id, $creatorId);

        // Check if user has access (regular users can only see their assigned GRNs)
        if ($user->type != 'company' && $user->type != 'super admin') {
            if ($grn->assigned_to != $user->id) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }

        $allItemsBaseQuery = GrnItem::where('grn_id', $grn->id);
        $summary = (clone $allItemsBaseQuery)
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(received_qty), 0) as total_received_qty')
            ->selectRaw('COALESCE(SUM(total_price), 0) as total_price')
            ->first();

        $totalItems = (int) (clone $allItemsBaseQuery)->count();
        $isLargeItemSet = $totalItems > 1000;
        $perPage = $isLargeItemSet ? 150 : 200;

        $itemsQuery = (clone $allItemsBaseQuery);
        if ($request->filled('box_no')) {
            $itemsQuery->whereHas('asnItem', function ($q) use ($request) {
                $q->where('box_no', $request->box_no);
            });
        }

        $grnItems = (clone $itemsQuery)
            ->with(['asnItem:id,box_no,supplier_po_no,our_pro_no,order_ref,description'])
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $allBoxNos = AsnItem::whereHas('grnItems', function ($q) use ($grn) {
            $q->where('grn_id', $grn->id);
        })
            ->whereNotNull('box_no')
            ->where('box_no', '!=', '')
            ->distinct()
            ->orderBy('box_no')
            ->pluck('box_no');

        $itemCollection = $grnItems->getCollection();

        // Batch-resolve product descriptions for current page only.
        $descriptionKeys = $itemCollection
            ->map(function ($item) {
                $description = trim((string) ($item->asnItem->description ?? $item->description ?? ''));
                return $description === '' ? null : mb_strtolower($description);
            })
            ->filter()
            ->unique()
            ->values();

        $matchedProductsByDescription = collect();
        if ($descriptionKeys->isNotEmpty()) {
            $matchedProductsByDescription = ProductService::where('created_by', $creatorId)
                ->whereIn(DB::raw('LOWER(name)'), $descriptionKeys->all())
                ->with(['category', 'brand', 'subBrand'])
                ->get()
                ->keyBy(function ($product) {
                    return mb_strtolower(trim((string) $product->name));
                });
        }

        // Batch-resolve sub products and custom fields for current page part numbers.
        $partNos = $itemCollection->pluck('part_no')->filter()->unique()->values();
        $subProductsByPartNo = collect();
        $customFieldsByCategory = [];
        $customFieldValuesByRecord = collect();

        if ($partNos->isNotEmpty()) {
            $subProductsByPartNo = SubProduct::where('created_by', $creatorId)
                ->whereIn('chassis_no', $partNos->all())
                ->with('productService:id,category_id')
                ->orderByDesc('id')
                ->get()
                ->unique('chassis_no')
                ->keyBy('chassis_no');

            $categoryIds = $subProductsByPartNo
                ->map(function ($subProduct) {
                    return optional($subProduct->productService)->category_id;
                })
                ->filter()
                ->unique()
                ->values();

            if ($categoryIds->isNotEmpty()) {
                $categoryFieldRows = CustomField::where('created_by', $creatorId)
                    ->where('module', 'sub-product')
                    ->forCategory($categoryIds->all())
                    ->with('categories:id')
                    ->get();

                foreach ($categoryFieldRows as $field) {
                    foreach ($field->categories as $category) {
                        $categoryId = (int) $category->id;
                        if ($categoryId <= 0) {
                            continue;
                        }
                        if (!isset($customFieldsByCategory[$categoryId])) {
                            $customFieldsByCategory[$categoryId] = collect();
                        }
                        $customFieldsByCategory[$categoryId]->push($field);
                    }
                }

                foreach ($customFieldsByCategory as $categoryId => $fields) {
                    $customFieldsByCategory[$categoryId] = $fields->unique('id')->values();
                }
            }

            $recordIds = $subProductsByPartNo->pluck('id')->filter()->values();
            if ($recordIds->isNotEmpty()) {
                $customFieldValuesByRecord = CustomFieldValue::whereIn('record_id', $recordIds->all())
                    ->get()
                    ->groupBy('record_id')
                    ->map(function ($group) {
                        return $group->pluck('value', 'field_id');
                    });
            }
        }

        $itemCollection->transform(function ($item) use ($matchedProductsByDescription, $subProductsByPartNo, $customFieldValuesByRecord, $customFieldsByCategory) {
            $description = trim((string) ($item->asnItem->description ?? $item->description ?? ''));
            $descriptionKey = $description === '' ? null : mb_strtolower($description);
            $item->matchedProduct = $descriptionKey ? ($matchedProductsByDescription->get($descriptionKey) ?? null) : null;

            $subProduct = !empty($item->part_no) ? ($subProductsByPartNo->get($item->part_no) ?? null) : null;
            $item->matchedSubProduct = $subProduct;
            $categoryId = optional(optional($subProduct)->productService)->category_id;

            $item->customFields = $categoryId && isset($customFieldsByCategory[$categoryId])
                ? $customFieldsByCategory[$categoryId]
                : collect();
            $item->customFieldValues = $subProduct
                ? ($customFieldValuesByRecord->get($subProduct->id) ?? collect())
                : collect();

            return $item;
        });

        return view('grn.show', compact('grn', 'grnItems', 'lockGrnQtyEditing', 'summary', 'totalItems', 'isLargeItemSet', 'allBoxNos'));
    }

    /**
     * Update GRN received quantities
     */
    public function update(Request $request, $id)
    {
        // Allow access if user can edit GRN or manage bills
        if (!\Auth::user()->can('edit grn') && !\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $grnId = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('GRN Not Found.'));
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();

        $grn = Grn::with(['items.asnItem', 'asn'])
            ->where('created_by', $creatorId)
            ->findOrFail($grnId);

        $qtyLockedByAsn = $this->isGrnQtyLockedByAsnConversion($grn->asn_id, $creatorId);

        // Check if user has access
        if ($user->type != 'company' && $user->type != 'super admin') {
            if ($grn->assigned_to != $user->id) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:grn_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:draft,received,manually_received,completed,cancelled',
            'selected_box_no' => 'nullable|string|max:255',
        ]);

        $proItemsToUpdate = [];
        $selectedBoxNo = trim((string) ($validated['selected_box_no'] ?? ''));

        try {
            DB::beginTransaction();

            if (!$qtyLockedByAsn && $selectedBoxNo !== '') {
                // Auto-fill received qty = qty for all items in selected box across all pages.
                $boxItems = GrnItem::where('grn_id', $grn->id)
                    ->whereHas('asnItem', function ($q) use ($selectedBoxNo) {
                        $q->where('box_no', $selectedBoxNo);
                    })
                    ->with('asnItem')
                    ->get();

                foreach ($boxItems as $item) {
                    $oldReceived = (float) $item->received_qty;
                    $newReceived = (float) $item->qty;
                    if (abs($newReceived - $oldReceived) <= 0.0000001) {
                        continue;
                    }

                    $item->received_qty = $newReceived;
                    $item->save();

                    if ($item->asnItem) {
                        $item->asnItem->received_qty = $newReceived;
                        $item->asnItem->save();

                        $delta = $newReceived - $oldReceived;
                        if (abs($delta) > 0.0000001 && !empty($item->part_no)) {
                            $partNo = $item->part_no;
                            $proId = $item->asnItem->our_pro_id ?? null;
                            $ourProNo = $item->asnItem->our_pro_no ?? null;
                            $proNo = null;

                            if ($proId) {
                                $key = 'pro_id_' . $proId . '_' . $partNo;
                            } elseif (!empty($ourProNo)) {
                                $proNo = preg_replace('/[^0-9]/', '', $ourProNo);
                                $key = 'pro_no_' . $proNo . '_' . $partNo;
                            } else {
                                $key = null;
                            }

                            if ($key !== null) {
                                if (!isset($proItemsToUpdate[$key])) {
                                    $proItemsToUpdate[$key] = [
                                        'pro_id' => $proId,
                                        'pro_no' => $proNo ?? null,
                                        'part_no' => $partNo,
                                        'our_pro_no' => $ourProNo,
                                        'delta' => 0.0,
                                    ];
                                }
                                $proItemsToUpdate[$key]['delta'] += $delta;
                            }
                        }
                    }
                }
            }

            if (!$qtyLockedByAsn) {
                // Update GRN items with received quantities
                foreach ($grn->items as $item) {
                    if (!isset($validated['items'][$item->id])) {
                        continue;
                    }
                    $oldReceived = (float) $item->received_qty;
                    $newReceived = (float) $validated['items'][$item->id]['received_qty'];
                    $item->received_qty = $newReceived;
                    // discrepancy and total_price will be auto-calculated by model
                    $item->save();

                    // Update corresponding ASN item if exists
                    if ($item->asnItem) {
                        $item->asnItem->received_qty = $newReceived;
                        $item->asnItem->save();

                        // Accumulate delta (new − old) per PRO line for supplied_qty — do not replace with ASN sum
                        $delta = $newReceived - $oldReceived;
                        if (abs($delta) > 0.0000001 && !empty($item->part_no)) {
                            $partNo = $item->part_no;
                            $proId = $item->asnItem->our_pro_id ?? null;
                            $ourProNo = $item->asnItem->our_pro_no ?? null;

                            if ($proId) {
                                $key = 'pro_id_' . $proId . '_' . $partNo;
                            } elseif (!empty($ourProNo)) {
                                $proNo = preg_replace('/[^0-9]/', '', $ourProNo);
                                $key = 'pro_no_' . $proNo . '_' . $partNo;
                            } else {
                                $key = null;
                            }

                            if ($key !== null) {
                                if (!isset($proItemsToUpdate[$key])) {
                                    $proItemsToUpdate[$key] = [
                                        'pro_id' => $proId,
                                        'pro_no' => $proNo ?? null,
                                        'part_no' => $partNo,
                                        'our_pro_no' => $ourProNo,
                                        'delta' => 0.0,
                                    ];
                                }
                                $proItemsToUpdate[$key]['delta'] += $delta;
                            }
                        }
                    }
                }

                // Apply GRN deltas to PRO supplied_qty (additive, capped at order_qty)
                foreach ($proItemsToUpdate as $key => $proInfo) {
                $delta = (float) ($proInfo['delta'] ?? 0);
                if (abs($delta) <= 0.0000001) {
                    continue;
                }

                $pro = null;
                
                // First try to find PRO by ID (most reliable)
                if (!empty($proInfo['pro_id'])) {
                    $pro = Pro::where('created_by', $creatorId)
                        ->where('id', $proInfo['pro_id'])
                        ->with('items')
                        ->first();
                }
                
                // If not found by ID, try to find by PRO number
                if (!$pro && !empty($proInfo['pro_no'])) {
                    $numericProNo = (int)($proInfo['pro_no'] ?? 0);
                    $pro = Pro::where('created_by', $creatorId)
                        ->where(function($q) use ($numericProNo) {
                            $q->where('pro_no', $numericProNo)
                              ->orWhere('pro_no', (string)$numericProNo);
                        })
                        ->with('items')
                        ->first();
                }
                
                // If still not found and we have formatted PRO number, try that
                if (!$pro && !empty($proInfo['our_pro_no'])) {
                    $formattedDigits = (int)preg_replace('/[^0-9]/', '', $proInfo['our_pro_no']);
                    if ($formattedDigits > 0) {
                        $pro = Pro::where('created_by', $creatorId)
                            ->where(function($q) use ($formattedDigits) {
                                $q->where('pro_no', $formattedDigits)
                                  ->orWhere('pro_no', (string)$formattedDigits);
                            })
                            ->with('items')
                            ->first();
                    }
                }
                
                if (!$pro) {
                    \Log::warning('PRO not found for GRN update', [
                        'pro_id' => $proInfo['pro_id'] ?? null,
                        'pro_no_numeric' => $proInfo['pro_no'] ?? null,
                        'pro_no_formatted' => $proInfo['our_pro_no'] ?? null,
                        'part_no' => $proInfo['part_no'],
                        'user_id' => $creatorId
                    ]);
                    continue;
                }

                // Find the matching PRO item by part_no
                $proItem = $pro->items()
                    ->where('part_no', $proInfo['part_no'])
                    ->first();
                
                if (!$proItem) {
                    \Log::warning('PRO item not found for GRN update', [
                        'pro_id' => $pro->id,
                        'pro_no' => $pro->pro_no,
                        'part_no' => $proInfo['part_no'],
                    ]);
                    continue;
                }

                // Add this GRN save's delta to supplied_qty (do not overwrite with ASN totals)
                $newSupplied = (float) $proItem->supplied_qty + $delta;
                $proItem->supplied_qty = max(0, min((float) $proItem->order_qty, $newSupplied));
                $proItem->remaining_qty = max(0, (float) $proItem->order_qty - (float) $proItem->supplied_qty);
                $proItem->save();

                \Log::info('PRO item updated from GRN receive', [
                    'pro_id' => $pro->id,
                    'pro_no' => $pro->pro_no,
                    'pro_item_id' => $proItem->id,
                    'part_no' => $proInfo['part_no'],
                    'order_qty' => $proItem->order_qty,
                    'delta_supplied_qty' => $delta,
                    'supplied_qty' => $proItem->supplied_qty,
                    'remaining_qty' => $proItem->remaining_qty,
                    'asn_id' => $grn->asn_id,
                ]);

                // Update PRO header status after item updates
                $pro->updateStatusBasedOnItems();
                }
            }

            // Update GRN status:
            // - If status explicitly set to 'manually_received' or 'cancelled', keep that manual choice
            // - Otherwise, auto-calculate from received quantities (so status changes automatically when received in GRN)
            $manualStatus = $validated['status'] ?? null;
            if (!empty($manualStatus) && in_array($manualStatus, ['manually_received', 'cancelled'], true)) {
                $grn->status = $manualStatus;
            } else {
                $totalQty = $grn->getTotalQty();
                $totalReceivedQty = $grn->getTotalReceivedQty();
                if ($totalReceivedQty >= $totalQty && $totalQty > 0) {
                    $grn->status = 'completed';
                } elseif ($totalReceivedQty > 0) {
                    $grn->status = 'received';
                } else {
                    $grn->status = 'draft';
                }
            }
            $grn->save();

            // Update ASN status if exists
            if ($grn->asn) {
                $grn->asn->updateStatusBasedOnItems();
                if ($grn->asn_id) {
                    $this->maybeSetAsnManuallyReceivedFromAllGrns((int) $grn->asn_id, $creatorId);
                }
            }

            DB::commit();

            \Log::info('GRN updated', [
                'grn_id' => $grn->id,
                'grn_no' => $grn->grn_no,
                'user_id' => $user->id
            ]);

            return redirect()->route('grn.index')->with('success', __('GRN updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GRN update failed', [
                'error' => $e->getMessage(),
                'grn_id' => $grnId,
                'user_id' => $creatorId
            ]);
            return redirect()->back()->with('error', __('GRN update failed: ') . $e->getMessage());
        }
    }

    /**
     * When every non-cancelled GRN for the ASN is either completed (fully received) or
     * manually_received, and at least one is manually_received, set ASN status to manually_received.
     */
    private function maybeSetAsnManuallyReceivedFromAllGrns(int $asnId, int $creatorId): void
    {
        $asn = Asn::where('id', $asnId)->where('created_by', $creatorId)->first();
        if (!$asn) {
            return;
        }

        $grns = Grn::where('asn_id', $asnId)
            ->where('created_by', $creatorId)
            ->get();

        $active = $grns->filter(function (Grn $g) {
            return $g->status !== 'cancelled';
        });

        if ($active->isEmpty()) {
            return;
        }

        $terminal = ['completed', 'manually_received'];
        foreach ($active as $g) {
            if (!in_array($g->status, $terminal, true)) {
                return;
            }
        }

        $hasManual = $active->contains(function (Grn $g) {
            return $g->status === 'manually_received';
        });

        if (!$hasManual) {
            return;
        }

        $asn->status = 'manually_received';
        $asn->save();
    }

    /**
     * Lock GRN qty editing when related ASN was converted to inventory (consignment) or bill.
     */
    private function isGrnQtyLockedByAsnConversion(?int $asnId, int $creatorId): bool
    {
        if (empty($asnId)) {
            return false;
        }

        $asn = Asn::where('id', $asnId)->where('created_by', $creatorId)->first();
        if (!$asn) {
            return false;
        }

        if ($asn->bill_id || $asn->asnBills()->exists()) {
            return true;
        }

        if (AsnItem::where('asn_id', $asnId)->where('converted_qty', '>', 0)->exists()) {
            return true;
        }

        if (AsnItem::where('asn_id', $asnId)->whereNotNull('inventory_converted_at')->exists()) {
            return true;
        }

        return AsnItem::where('asn_id', $asnId)
            ->whereHas('subProduct', function ($q) {
                $q->where('flag', SubProduct::FLAG_CONSIGNMENT);
            })
            ->exists();
    }

    /**
     * Format GRN number
     */
    private function formatGrnNumber($number)
    {
        return 'GRN' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Convert GRN to Bill
     */
    public function convertToBill(Request $request, $id)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $grnId = Crypt::decrypt($id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('GRN Not Found.'));
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();

        $grn = Grn::with(['items.asnItem', 'asn', 'supplier'])
            ->where('created_by', $creatorId)
            ->findOrFail($grnId);

        // Check if GRN already has a bill
        if ($grn->bill_id) {
            return redirect()->route('bill.show', Crypt::encrypt($grn->bill_id))->with('info', __('This GRN has already been converted to a bill.'));
        }

        // Use default dates if not provided (for direct conversion without popup)
        $billDate = $request->input('bill_date', date('Y-m-d'));
        $dueDate = $request->input('due_date', date('Y-m-d', strtotime('+30 days')));

        try {
            DB::beginTransaction();

            // Generate bill number
            $billNumber = $this->billNumber();

            // Get default warehouse (first assigned warehouse or first company warehouse, or 0)
            $defaultWarehouseId = 0;
            if ($user->warehouses()->count() > 0) {
                $defaultWarehouseId = $user->warehouses()->first()->id;
            } else {
                $firstWarehouse = \App\Models\warehouse::where('created_by', $creatorId)->first();
                if ($firstWarehouse) {
                    $defaultWarehouseId = $firstWarehouse->id;
                }
            }

            // Determine supplier - use GRN supplier (prefer supplier_id, fallback to supplier_name lookup)
            $supplierId = null;
            if ($grn->supplier_id) {
                $supplierId = $grn->supplier_id;
            } elseif ($grn->supplier) {
                $supplierId = $grn->supplier->id;
            } elseif ($grn->supplier_name) {
                // Try to find supplier by name
                $supplier = \App\Models\Vender::where('created_by', $creatorId)
                    ->where('name', $grn->supplier_name)
                    ->first();
                if ($supplier) {
                    $supplierId = $supplier->id;
                }
            }

            // Determine currency - use ASN currency if available, otherwise default
            $currencyId = null;
            $exchangeRate = 1;
            if ($grn->asn) {
                $currencyId = $grn->asn->currency_id;
                $exchangeRate = $grn->asn->exchange_rate ?? 1;
            }

            // Create bill using GRN data
            $bill = new Bill();
            $bill->bill_id = (string) $billNumber;
            $bill->vender_id = $supplierId; // Use supplier from GRN
            $bill->bill_date = $billDate;
            $bill->due_date = $dueDate;
            $bill->status = 0; // Draft
            $bill->type = 'Bill';
            $bill->user_type = 'vendor';
            $bill->warehouse_id = $defaultWarehouseId; // Default warehouse
            $bill->category_id = 0;
            $bill->created_by = $creatorId;
            $bill->salesman_id = $creatorId;
            
            // Get default tax from company (first tax created by the company)
            $defaultTax = \App\Models\Tax::where('created_by', $creatorId)->first();
            $bill->tax_id = $defaultTax ? (string)$defaultTax->id : ''; // Use default company tax if available
            
            $bill->currency_id = $currencyId; // Use currency from ASN if available
            $bill->exchange_rate = $exchangeRate; // Use exchange rate from ASN if available
            
            $bill->save();

            // Create bill status change record
            $statusChange = new BillStatusChange();
            $statusChange->bill_id = $bill->id;
            $statusChange->status = 0;
            $statusChange->payment_status = -1;
            $statusChange->changed_at = now();
            $statusChange->save();

            // Process GRN items and create bill products
            foreach ($grn->items as $grnItem) {
                if ($grnItem->received_qty <= 0) {
                    continue; // Skip items with zero received quantity
                }

                // Find sub product by part_no (product_no) - this is the key requirement
                $subProduct = SubProduct::where('created_by', $creatorId)
                    ->where('chassis_no', $grnItem->part_no)
                    ->with(['productService.category', 'customFieldValues'])
                    ->latest()
                    ->first();

                if (!$subProduct || !$subProduct->productService) {
                    \Log::warning('SubProduct not found for GRN item', [
                        'part_no' => $grnItem->part_no,
                        'grn_item_id' => $grnItem->id
                    ]);
                    continue;
                }

                // Get product from sub product
                $product = $subProduct->productService;
                $category = $product->category;

                if (!$category) {
                    \Log::warning('Category not found for product', [
                        'product_id' => $product->id,
                        'part_no' => $grnItem->part_no
                    ]);
                    continue;
                }

                // Update product quantity
                $product->quantity += $grnItem->received_qty;
                $product->save();

                // Calculate prices - use unit_price from ASN item, fallback to GRN item unit_price
                $asnItem = $grnItem->asnItem; // Get ASN item via relationship
                $unitPriceOriginal = $asnItem && !empty($asnItem->unit_price) ? $asnItem->unit_price : $grnItem->unit_price;
                $quantity = $grnItem->received_qty;
                $discount = 0; // No discount by default
                
                // Calculate prices in AED and foreign currency
                // If currency exists: price = AED (converted), exchange_price = original currency
                // If no currency: price = exchange_price = same value
                if ($bill->currency_id && $bill->exchange_rate > 0) {
                    // Convert to AED using exchange rate
                    $unitPriceAED = $unitPriceOriginal * $bill->exchange_rate;
                    $exchangePrice = $unitPriceOriginal; // Original price in foreign currency
                } else {
                    // No currency, use same price for both
                    $unitPriceAED = $unitPriceOriginal;
                    $exchangePrice = $unitPriceOriginal;
                }
                
                // Calculate total prices
                $priceAED = $unitPriceAED * $quantity - $discount;
                $priceOriginal = $unitPriceOriginal * $quantity - $discount;

                // Handle different product category types
                if ($category->type === "product") {
                    // Create individual subproducts (one per quantity)
                    for ($j = 0; $j < $quantity; $j++) {
                        $newSubProduct = new SubProduct();
                        $newSubProduct->product_id = $product->id;
                        $newSubProduct->sale_price = $product->sale_price ?? 0;
                        $newSubProduct->quantity = 1;
                        $newSubProduct->purchase_price = $unitPriceAED; // Save in AED
                        $newSubProduct->created_by = $creatorId;
                        $newSubProduct->flag = 0;
                        $newSubProduct->bill_id = $bill->id;
                        $newSubProduct->warehouse_id = $defaultWarehouseId; // Use default warehouse
                        $newSubProduct->save();

                        // Create bill product
                        $billProduct = new BillProduct();
                        $billProduct->bill_id = $bill->id;
                        $billProduct->product_id = $product->id;
                        $billProduct->sub_product_id = $newSubProduct->id;
                        $billProduct->quantity = 1;
                        $billProduct->tax = $bill->tax_id; // Use bill's tax_id (default company tax)
                        $billProduct->discount = 0;
                        $billProduct->price = $unitPriceAED; // Price in AED
                        $billProduct->exchange_price = $exchangePrice; // Original price in foreign currency (or same if no currency)
                        $billProduct->exchange_discount = 0;
                        $billProduct->description = $grnItem->description ?? '';
                        $billProduct->save();

                        // Copy custom fields from existing subproduct if available
                        if ($subProduct->customFieldValues) {
                            foreach ($subProduct->customFieldValues as $customFieldValue) {
                                $newCustomFieldValue = new CustomFieldValue();
                                $newCustomFieldValue->record_id = $newSubProduct->id;
                                $newCustomFieldValue->field_id = $customFieldValue->field_id;
                                $newCustomFieldValue->value = $customFieldValue->value;
                                $newCustomFieldValue->save();
                            }
                        }
                    }
                } elseif ($category->type === "Qty product") {
                    // Create single subproduct with quantity
                    $newSubProduct = new SubProduct();
                    $newSubProduct->product_id = $product->id;
                    $newSubProduct->sale_price = $product->sale_price ?? 0;
                    $newSubProduct->quantity = $quantity;
                    $newSubProduct->purchase_price = $priceAED; // Total price in AED
                    $newSubProduct->chassis_no = $grnItem->part_no;
                    $newSubProduct->created_by = $creatorId;
                    $newSubProduct->flag = 0;
                    $newSubProduct->bill_id = $bill->id;
                    $newSubProduct->warehouse_id = $defaultWarehouseId; // Use default warehouse
                    $newSubProduct->save();

                    // Create bill product
                    $billProduct = new BillProduct();
                    $billProduct->bill_id = $bill->id;
                    $billProduct->product_id = $product->id;
                    $billProduct->sub_product_id = $newSubProduct->id;
                    $billProduct->quantity = $quantity;
                    $billProduct->tax = $bill->tax_id; // Use bill's tax_id (default company tax)
                    $billProduct->discount = 0;
                    $billProduct->price = $unitPriceAED; // Unit price in AED
                    $billProduct->exchange_price = $exchangePrice; // Original unit price in foreign currency (or same if no currency)
                    $billProduct->exchange_discount = 0;
                    $billProduct->description = $grnItem->description ?? '';
                    $billProduct->save();

                    // Copy custom fields from existing subproduct if available
                    if ($subProduct->customFieldValues) {
                        foreach ($subProduct->customFieldValues as $customFieldValue) {
                            $newCustomFieldValue = new CustomFieldValue();
                            $newCustomFieldValue->record_id = $newSubProduct->id;
                            $newCustomFieldValue->field_id = $customFieldValue->field_id;
                            $newCustomFieldValue->value = $customFieldValue->value;
                            $newCustomFieldValue->save();
                        }
                    }
                }
            }

            // Link bill to GRN
            $grn->bill_id = $bill->id;
            $grn->save();

            DB::commit();

            \Log::info('GRN converted to bill', [
                'grn_id' => $grn->id,
                'bill_id' => $bill->id,
                'user_id' => $user->id
            ]);

            return redirect()->route('bill.show', Crypt::encrypt($bill->id))->with('success', __('GRN successfully converted to bill.'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GRN convert to bill failed', [
                'error' => $e->getMessage(),
                'grn_id' => $grnId,
                'user_id' => $creatorId,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', __('Failed to convert GRN to bill: ') . $e->getMessage());
        }
    }

    /**
     * Generate bill number
     */
    private function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())
            ->where('bill_id', 'not like', '%#EXP%')
            ->withTrashed()
            ->latest()
            ->first();
        
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }
}
