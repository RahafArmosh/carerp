<?php

namespace App\Http\Controllers;

use App\Models\Asn;
use App\Models\AsnBill;
use App\Models\AsnItem;
use App\Models\AsnItemBill;
use App\Models\AdvanceSaleOrder;
use App\Models\ProductService;
use App\Models\Pro;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Vender;
use App\Models\Currency;
use App\Models\SubProduct;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\StockMovement;
use App\Models\InvoiceProduct;
use App\Models\PosProduct;
use App\Exceptions\AsnImportValidationException;
use App\Exports\AsnImportErrorsExport;
use App\Imports\AsnImport;
use App\Models\MasterlistLeadger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AsnController extends Controller
{
    /**
     * Generate ASN number
     */
    private function asnNumber()
    {
        $latest = Asn::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest || !is_numeric($latest->asn_no)) {
            return 1;
        }
        return (int)$latest->asn_no + 1;
    }

    /**
     * Get description from stock (SubProduct) by matching part_no to product_no
     */
    private function getDescriptionFromStock($partNo, $creatorId)
    {
        if (empty(trim($partNo))) {
            return null;
        }

        $subProduct = SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', trim($partNo))
            ->with('productService')
            ->latest()
            ->first();

        if ($subProduct && $subProduct->productService) {
            return $subProduct->productService->name;
        }

        return null;
    }

    /**
     * Whether this ASN can no longer be edited (converted to inventory or bill).
     */
    private function isAsnLockedForEditing(Asn $asn, int $creatorId): bool
    {
        if ((int) $asn->created_by !== (int) $creatorId) {
            return false;
        }

        $asn->loadMissing(['items', 'asnBills']);

        if (!empty($asn->bill_id) || $asn->asnBills->isNotEmpty()) {
            return true;
        }

        foreach ($asn->items as $item) {
            if ((float) ($item->converted_qty ?? 0) > 0) {
                return true;
            }
            if (!empty($item->inventory_converted_at)) {
                return true;
            }
            if ($item->sub_product_id) {
                $sub = SubProduct::find($item->sub_product_id);
                if ($sub && $sub->isConsignment()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Item Master sub-product (import_source=item_master) by part number — same rules as PRO import.
     */
    private function findItemMasterSubProductByPartNo(string $partNo, int $creatorId): ?SubProduct
    {
        $partNo = trim($partNo);
        if ($partNo === '') {
            return null;
        }

        $preferred = SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', $partNo)
            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
            ->whereHas('productService', function ($q) use ($partNo, $creatorId) {
                $q->where('created_by', $creatorId)->where('sku', $partNo);
            })
            ->with(['productService.category', 'customFieldValues'])
            ->latest()
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', $partNo)
            ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
            ->with(['productService.category', 'customFieldValues'])
            ->latest()
            ->first();
    }

    private function nextSaleOrderNumber(int $creatorId): int
    {
        $latestSaleOrder = SaleOrder::where('created_by', $creatorId)->withTrashed()->latest()->first();

        if (!$latestSaleOrder || !is_numeric($latestSaleOrder->sale_order_no)) {
            return 1;
        }

        return ((int) $latestSaleOrder->sale_order_no) + 1;
    }

    private function bookAutoSaleOrderSubProducts(int $saleOrderId, int $creatorId, array $rows): void
    {
        $qtyBySubProduct = [];
        foreach ($rows as $row) {
            $subProductId = (int) ($row['sub_product_id'] ?? 0);
            $qty = (float) ($row['qty'] ?? 0);
            if ($subProductId <= 0 || $qty <= 0) {
                continue;
            }
            $qtyBySubProduct[$subProductId] = ($qtyBySubProduct[$subProductId] ?? 0.0) + $qty;
        }

        if (empty($qtyBySubProduct)) {
            return;
        }

        $subProducts = SubProduct::whereIn('id', array_keys($qtyBySubProduct))
            ->where('created_by', $creatorId)
            ->get()
            ->keyBy('id');

        $productIds = $subProducts->pluck('product_id')->filter()->unique()->values()->all();
        $products = ProductService::with('category')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($qtyBySubProduct as $subProductId => $qtyToBook) {
            /** @var SubProduct|null $subProduct */
            $subProduct = $subProducts->get($subProductId);
            if (!$subProduct || $qtyToBook <= 0) {
                continue;
            }

            $categoryType = optional(optional($products->get((int) $subProduct->product_id))->category)->type;

            if ($categoryType === 'Qty product') {
                $availableQty = (float) $subProduct->quantity;
                $reservedQty = min($availableQty, (float) $qtyToBook);
                if ($reservedQty <= 0) {
                    continue;
                }

                $subProduct->quantity = $availableQty - $reservedQty;
                $subProduct->booked = ((float) $subProduct->quantity <= 0) ? 1 : 0;
                $subProduct->sale_order_id = $saleOrderId;
                $subProduct->so_qty_reserved = $reservedQty;
                $subProduct->save(); 

                
                $user = \Auth::user();
                $creatorId = $user->creatorId();

                $target_document_type = "";
                $target_document = 0;
                if($subProduct->asn_id){
                    $target_document_type = "ASN";
                    $target_document = $subProduct->asn_id;
                }else{
                    $target_document_type = "BILL";
                    $target_document = $subProduct->bill_id;
                }
                MasterlistLeadger::addBooked($subProduct->product_id,$subProduct->warehouse_id,$availableQty,'SO',$saleOrderId,$creatorId,$target_document_type,$target_document);
    
            } else {
                $subProduct->booked = 1;
                $subProduct->sale_order_id = $saleOrderId;
                $subProduct->save();
            }
        }
    }

    private function applyAdvanceSaleOrderConvertedQty(AdvanceSaleOrder $advanceSaleOrder, array $rows): void
    {
        $qtyByPartNo = [];
        foreach ($rows as $row) {
            $partNo = strtoupper(trim((string) ($row['part_no'] ?? '')));
            $qty = (float) ($row['qty'] ?? 0);
            if ($partNo === '' || $qty <= 0) {
                continue;
            }
            $qtyByPartNo[$partNo] = ($qtyByPartNo[$partNo] ?? 0.0) + $qty;
        }

        if (empty($qtyByPartNo)) {
            return;
        }

        $advanceSaleOrder->loadMissing('items');
        foreach ($qtyByPartNo as $partNo => $qtyRemaining) {
            $matchingItems = $advanceSaleOrder->items
                ->filter(function ($item) use ($partNo) {
                    return strtoupper(trim((string) $item->part_no)) === $partNo;
                })
                ->sortBy('id')
                ->values();

            foreach ($matchingItems as $asoItem) {
                if ($qtyRemaining <= 0) {
                    break;
                }

                $currentConverted = (float) ($asoItem->converted_qty ?? 0);
                $requested = (float) ($asoItem->req_qty ?? 0);
                $remainingCapacity = max(0.0, $requested - $currentConverted);
                if ($remainingCapacity <= 0) {
                    continue;
                }

                $qtyToApply = min($remainingCapacity, $qtyRemaining);
                $asoItem->converted_qty = $currentConverted + $qtyToApply;
                $asoItem->save();
                $qtyRemaining -= $qtyToApply;
            }
        }
    }

    private function createAutoSaleOrdersFromAdvanceSo(int $creatorId, Asn $asn, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $advanceSaleOrderId = (int) ($row['advance_sale_order_id'] ?? 0);
            if ($advanceSaleOrderId <= 0) {
                continue;
            }
            $grouped[$advanceSaleOrderId][] = $row;
        }

        if (empty($grouped)) {
            return;
        }

        $advanceSaleOrders = AdvanceSaleOrder::with('items')
            ->whereIn('id', array_keys($grouped))
            ->where('created_by', $creatorId)
            ->get()
            ->keyBy('id');

        $nextOrderNumber = $this->nextSaleOrderNumber($creatorId);

        foreach ($grouped as $advanceSaleOrderId => $groupRows) {
            /** @var AdvanceSaleOrder|null $advanceSaleOrder */
            $advanceSaleOrder = $advanceSaleOrders->get((int) $advanceSaleOrderId);
            if (!$advanceSaleOrder || empty($advanceSaleOrder->customer_id)) {
                continue;
            }

            $validRows = array_values(array_filter($groupRows, function ($row) {
                return (float) ($row['qty'] ?? 0) > 0;
            }));
            if (empty($validRows)) {
                continue;
            }

            $saleOrder = new SaleOrder();
            $saleOrder->sale_order_no = (string) $nextOrderNumber++;
            $saleOrder->advance_sale_order_id = $advanceSaleOrder->id;
            $saleOrder->customer_id = $advanceSaleOrder->customer_id;
            $saleOrder->customer_trn_no = $advanceSaleOrder->customer_trn_no;
            $saleOrder->sales_order_date = $asn->asn_date ?: now()->toDateString();
            $saleOrder->currency_id = $advanceSaleOrder->currency_id ?: $asn->currency_id;
            $saleOrder->exchange_rate = $advanceSaleOrder->exchange_rate ?: ($asn->exchange_rate ?: 1.0);
            $saleOrder->tax_id = $advanceSaleOrder->tax_id;
            $saleOrder->status = 'draft';
            $saleOrder->created_by = $creatorId;
            $saleOrder->save();

            foreach ($validRows as $row) {
                $qty = (float) ($row['qty'] ?? 0);

                $saleOrderItem = new SaleOrderItem();
                $saleOrderItem->sale_order_id = $saleOrder->id;
                $saleOrderItem->part_no = $row['part_no'] ?? null;
                $saleOrderItem->description = $row['description'] ?? null;
                $saleOrderItem->req_qty = $qty;
                $saleOrderItem->stock_qty = $qty;
                $saleOrderItem->picking_qty = 0.0;
                $saleOrderItem->packed_qty = 0.0;
                $saleOrderItem->unit_price = (float) ($row['unit_price'] ?? 0);
                $saleOrderItem->product_id = $row['product_id'] ?? null;
                $saleOrderItem->sub_product_id = $row['sub_product_id'] ?? null;
                $saleOrderItem->save();
            }

            $this->bookAutoSaleOrderSubProducts($saleOrder->id, $creatorId, $validRows);
            $this->applyAdvanceSaleOrderConvertedQty($advanceSaleOrder, $validRows);
        }
    }

    /**
     * Bulk-load sub-products and product rows for ASN → inventory conversion (avoids N+1 per line).
     *
     * @return array{item_master_by_part: array<string, SubProduct>, fallback_by_part: array<string, SubProduct>, product_by_sku: array<string, ProductService>, product_by_lower_name: array<string, ProductService>}
     */
    private function prefetchConvertToInventoryLookups(Collection $asnItems, int $creatorId): array
    {
        $partNos = $asnItems->pluck('part_no')->map(fn ($p) => trim((string) $p))->filter()->unique()->values();
        $itemMasterByPart = [];

        if ($partNos->isNotEmpty()) {
            $candidates = SubProduct::where('created_by', $creatorId)
                ->whereIn('chassis_no', $partNos->all())
                ->whereRaw('LOWER(COALESCE(import_source, "")) = ?', ['item_master'])
                ->with(['productService.category', 'customFieldValues'])
                ->orderByDesc('id')
                ->get()
                ->groupBy('chassis_no');

            foreach ($candidates as $pn => $group) {
                $preferred = $group->first(function ($sub) use ($pn, $creatorId) {
                    return $sub->productService
                        && (int) $sub->productService->created_by === (int) $creatorId
                        && (string) $sub->productService->sku === (string) $pn;
                });
                $itemMasterByPart[$pn] = $preferred ?: $group->first();
            }
        }

        $fallbackPartNos = [];
        foreach ($asnItems as $it) {
            $pn = trim((string) ($it->part_no ?? ''));
            if ($pn === '') {
                continue;
            }
            $im = $itemMasterByPart[$pn] ?? null;
            if (!$im || !$im->productService) {
                $fallbackPartNos[$pn] = true;
            }
        }

        $fallbackByPart = [];
        if ($fallbackPartNos !== []) {
            $fallbackByPart = SubProduct::where('created_by', $creatorId)
                ->whereIn('chassis_no', array_keys($fallbackPartNos))
                ->with(['productService.category', 'customFieldValues'])
                ->orderByDesc('id')
                ->get()
                ->groupBy('chassis_no')
                ->map(fn ($g) => $g->first())
                ->all();
        }

        $productBySku = [];
        if ($partNos->isNotEmpty()) {
            $productBySku = ProductService::where('created_by', $creatorId)
                ->whereIn('sku', $partNos->all())
                ->with('category')
                ->get()
                ->keyBy(fn ($p) => (string) $p->sku)
                ->all();
        }

        $descLowerKeys = [];
        foreach ($asnItems as $it) {
            $pn = trim((string) ($it->part_no ?? ''));
            $itemMasterSub = $pn !== '' ? ($itemMasterByPart[$pn] ?? null) : null;
            $fallbackSub = null;
            if ($pn !== '' && (!$itemMasterSub || !$itemMasterSub->productService)) {
                $fallbackSub = $fallbackByPart[$pn] ?? null;
            }
            $product = null;
            if ($pn !== '') {
                if ($itemMasterSub && $itemMasterSub->productService) {
                    $product = $itemMasterSub->productService;
                } elseif ($fallbackSub && $fallbackSub->productService) {
                    $product = $fallbackSub->productService;
                } else {
                    $product = $productBySku[$pn] ?? null;
                }
            }
            if (!$product && trim((string) ($it->description ?? '')) !== '') {
                $descLowerKeys[mb_strtolower(trim((string) $it->description))] = true;
            }
        }

        $productByLowerName = [];
        if ($descLowerKeys !== []) {
            $keys = array_keys($descLowerKeys);
            $productByLowerName = ProductService::where('created_by', $creatorId)
                ->whereIn(DB::raw('LOWER(name)'), $keys)
                ->with('category')
                ->get()
                ->keyBy(fn ($p) => mb_strtolower(trim((string) $p->name)))
                ->all();
        }

        return [
            'item_master_by_part' => $itemMasterByPart,
            'fallback_by_part' => $fallbackByPart,
            'product_by_sku' => $productBySku,
            'product_by_lower_name' => $productByLowerName,
        ];
    }

    /**
     * @return array{product: ?ProductService, item_master_sub: ?SubProduct, fallback_sub: ?SubProduct}
     */
    private function resolveProductForConvertToInventoryLine(AsnItem $asnItem, array $prefetch, int $creatorId): array
    {
        $partNo = trim((string) ($asnItem->part_no ?? ''));
        $itemMasterSub = $partNo !== '' ? ($prefetch['item_master_by_part'][$partNo] ?? null) : null;
        $fallbackSub = null;
        if ($partNo !== '' && (!$itemMasterSub || !$itemMasterSub->productService)) {
            $fallbackSub = $prefetch['fallback_by_part'][$partNo] ?? null;
        }

        $product = null;
        if ($partNo !== '') {
            if ($itemMasterSub && $itemMasterSub->productService) {
                $product = $itemMasterSub->productService;
            } elseif ($fallbackSub && $fallbackSub->productService) {
                $product = $fallbackSub->productService;
            } else {
                $product = $prefetch['product_by_sku'][$partNo] ?? null;
            }
        }

        if (!$product && trim((string) ($asnItem->description ?? '')) !== '') {
            $dk = mb_strtolower(trim((string) $asnItem->description));
            $product = $prefetch['product_by_lower_name'][$dk] ?? null;
            if (!$product) {
                $product = ProductService::where('created_by', $creatorId)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . $dk . '%'])
                    ->with('category')
                    ->first();
            }
        }

        return [
            'product' => $product,
            'item_master_sub' => $itemMasterSub,
            'fallback_sub' => $fallbackSub,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $customFieldMapsByCategory
     */
    private function getCustomFieldMapForConvertToInventory(int $categoryId, int $creatorId, array &$customFieldMapsByCategory): array
    {
        if (isset($customFieldMapsByCategory[$categoryId])) {
            return $customFieldMapsByCategory[$categoryId];
        }

        $customFields = CustomField::where('created_by', $creatorId)
            ->where('module', 'sub-product')
            ->forCategory($categoryId)
            ->get();

        $customFieldMap = [];
        foreach ($customFields as $customField) {
            $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $customField->name)));
            $customFieldMap[$normalizedName] = $customField->id;
        }

        $customFieldMapsByCategory[$categoryId] = [
            'fields' => $customFields,
            'map' => $customFieldMap,
        ];

        return $customFieldMapsByCategory[$categoryId];
    }

    /**
     * @param array<int, int> $asnItemIdToSubProductId
     */
    private function batchUpdateAsnItemsAfterInventoryConvert(array $asnItemIdToSubProductId): void
    {
        if ($asnItemIdToSubProductId === []) {
            return;
        }

        $ts = now()->format('Y-m-d H:i:s');
        $chunks = array_chunk($asnItemIdToSubProductId, 150, true);
        foreach ($chunks as $chunk) {
            $casesSub = [];
            foreach ($chunk as $itemId => $subId) {
                $casesSub[] = 'WHEN ' . (int) $itemId . ' THEN ' . (int) $subId;
            }
            $ids = implode(',', array_map('intval', array_keys($chunk)));
            $sql = 'UPDATE asn_items SET sub_product_id = CASE id ' . implode(' ', $casesSub) . ' END, inventory_converted_at = ?, updated_at = ? WHERE id IN (' . $ids . ')';
            DB::statement($sql, [$ts, $ts]);
        }
    }

    /**
     * Ensure each line has received_qty <= qty (ordered quantity).
     */
    private function validateAsnItemsReceivedVersusQty(array $items): ?\Illuminate\Http\RedirectResponse
    {
        foreach ($items as $index => $itemData) {
            $qty = (float) ($itemData['qty'] ?? 0);
            $received = (float) ($itemData['received_qty'] ?? 0);
            if ($received > $qty + 0.00001) {
                $label = trim((string) ($itemData['part_no'] ?? ''));
                if ($label === '') {
                    $label = '#' . ($index + 1);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Received quantity cannot exceed ordered quantity (QTY) for line :line. Received: :received, QTY: :qty.', [
                        'line' => $label,
                        'received' => $received,
                        'qty' => $qty,
                    ]));
            }
        }

        return null;
    }

    /**
     * Sync matching PRO item's parent product from ASN item part number.
     *
     * Legacy note: method name is kept because existing ASN create/update flow
     * already calls this method.
     */
    private function syncProItemSubProductFromAsnItem(AsnItem $asnItem, int $creatorId): void
    {
        if (empty($asnItem->our_pro_id) || empty(trim((string) $asnItem->part_no))) {
            return;
        }

        $partNo = trim((string) $asnItem->part_no);

        $matchedSubProduct = SubProduct::where('created_by', $creatorId)
            ->where('chassis_no', $partNo)
            ->with('productService')
            ->latest()
            ->first();

        if (!$matchedSubProduct || !$matchedSubProduct->product_id) {
            return;
        }

        $proItem = \App\Models\ProItem::where('pro_id', $asnItem->our_pro_id)
            ->where('part_no', $partNo)
            ->first();

        if (!$proItem) {
            return;
        }

        // pro_items.sub_product_id was removed; keep parent product_id in sync.
        if ((int) $proItem->product_id !== (int) $matchedSubProduct->product_id) {
            $proItem->product_id = $matchedSubProduct->product_id;
            $proItem->save();
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            $suppliers = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            $query = Asn::where('created_by', '=', \Auth::user()->creatorId())->with('supplier', 'items', 'creator');

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', '=', $request->supplier_id);
            }

            if ($request->filled('asn_date')) {
                $query->where('asn_date', '=', $request->asn_date);
            }

            if ($request->filled('asn_no')) {
                $query->where('asn_no', 'like', '%' . $request->asn_no . '%');
            }

            $asns = $query->orderBy('id', 'desc')->get();

            return view('asn.index', compact('asns', 'suppliers'));
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
            $asn_number = $this->asnNumber();
            $asn_number_formatted = \Auth::user()->asnNumberFormat($asn_number);
            $suppliers = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            // Get PROs for linking
            $pros = Pro::where('created_by', \Auth::user()->creatorId())
                ->orderBy('id', 'desc')
                ->get()
                ->map(function($pro) {
                    return [
                        'id' => $pro->id,
                        'name' => \Auth::user()->proNumberFormat($pro->pro_no)
                    ];
                })
                ->pluck('name', 'id');
            $pros->prepend('Select PRO', '');

            $currencies = \App\Models\Currency::select('id', 'name', 'exchange_rate')
                ->orderBy('name')
                ->get();

            $warehouses = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');
            $warehouses->prepend('Select Warehouse', '');

            return view('asn.create', compact('asn_number', 'asn_number_formatted', 'suppliers', 'pros', 'currencies', 'warehouses'));
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
                'supplier_code' => 'nullable|string|max:255',
                'supplier_inv_no' => 'nullable|string|max:255',
                'container_no' => 'nullable|string|max:255',
                'dec_no' => 'nullable|string|max:255',
                'dec_date' => 'nullable|date',
                'asn_date' => 'required|date',
                'warehouse_id' => 'required|exists:warehouses,id',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.id' => 'nullable|exists:asn_items,id',
                'items.*.box_no' => 'nullable|string|max:255',
                'items.*.supplier_po_no' => 'nullable|string|max:255',
                'items.*.our_pro_id' => 'nullable|exists:pros,id',
                'items.*.order_ref' => 'nullable|string|max:255',
                'items.*.part_no' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:0',
                'items.*.received_qty' => 'required|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.unit_weight' => 'nullable|numeric|min:0',
            ]);

            if ($redirect = $this->validateAsnItemsReceivedVersusQty($validated['items'])) {
                return $redirect;
            }

            // Create ASN header
            $asn = new Asn();
            $asn->asn_no = $this->asnNumber();
            $asn->supplier_id = $validated['supplier_id'] ?? null;
            $asn->supplier_name = $validated['supplier_name'] ?? null;
            $asn->supplier_code = $validated['supplier_code'] ?? null;
            $asn->supplier_inv_no = $validated['supplier_inv_no'] ?? null;
            $asn->container_no = $validated['container_no'] ?? null;
            $asn->dec_no = $validated['dec_no'] ?? null;
            $asn->dec_date = $validated['dec_date'] ?? null;
            $asn->asn_date = $validated['asn_date'];
            $asn->warehouse_id = $validated['warehouse_id'] ?? null;
            $asn->currency_id = $validated['currency_id'] ?? null;
            $asn->exchange_rate = $validated['exchange_rate'] ?? 1.0;
            $asn->status = 'sent';
            $asn->created_by = \Auth::user()->creatorId();
            $asn->save();

            // Create ASN Items with header values copied to all items
            foreach ($validated['items'] as $itemData) {
                $item = new AsnItem();
                $item->asn_id = $asn->id;
                $item->box_no = $itemData['box_no'] ?? null;
                $item->supplier_po_no = $itemData['supplier_po_no'] ?? null;
                $item->our_pro_id = $itemData['our_pro_id'] ?? null;
                
                // Store PRO number string if PRO is linked
                if ($item->our_pro_id) {
                    $pro = Pro::find($item->our_pro_id);
                    $item->our_pro_no = $pro ? \Auth::user()->proNumberFormat($pro->pro_no) : null;
                }
                
                $item->order_ref = $itemData['order_ref'] ?? null;
                $item->part_no = $itemData['part_no'] ?? null;
                
                // Auto-fill description from stock if empty
                $description = $itemData['description'] ?? null;
                if (empty(trim($description ?? '')) && !empty($item->part_no)) {
                    $description = $this->getDescriptionFromStock($item->part_no, \Auth::user()->creatorId());
                }
                $item->description = $description;
                
                $item->qty = $itemData['qty'];
                $item->received_qty = $itemData['received_qty'];
                $item->unit_price = $itemData['unit_price'];
                $item->unit_weight = $itemData['unit_weight'] ?? 0;
                
                // Copy header values to all items
                $item->hs_code = $validated['hs_code'] ?? null;
                $item->container_no = $validated['container_no'] ?? null;
                $item->dec_no = $validated['dec_no'] ?? null;
                $item->dec_date = $validated['dec_date'] ?? null;
                $item->origin = $validated['origin'] ?? null;
                
                // discrepancy, total_price, total_weight will be auto-calculated by model
                $item->save();

                // After ASN save, link PRO item to stock sub-product by part_no (PRO + part_no match).
                $this->syncProItemSubProductFromAsnItem($item, \Auth::user()->creatorId());
            }

            // Set status based on received vs ordered immediately after creation
            $asn->updateStatusBasedOnItems();

            return redirect()->route('asn.index')->with('success', __('ASN created successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Asn $asn)
    {
        if (\Auth::user()->can('manage bill')) {
            $creatorId = \Auth::user()->creatorId();
            $asn->load(['supplier', 'creator', 'currency', 'warehouse', 'asnBills.bill']);

            // Ensure ASN view also includes sub-products that carry this ASN link
            // but don't yet have an ASN item row (e.g. transferred stock slices).
            $this->ensureAsnItemsForLinkedSubProducts($asn, $creatorId);

            $itemsQuery = AsnItem::where('asn_id', $asn->id)->orderBy('id');
            $totalItems = (int) $itemsQuery->count();
            $isLargeItemSet = $totalItems > 300;
            $perPage = $isLargeItemSet ? 150 : 200;
            $asnItems = $itemsQuery->paginate($perPage)->appends(request()->query());
            $itemsCollection = $asnItems->getCollection();

            $binLocationField = \App\Models\CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->whereRaw('UPPER(TRIM(name)) = ?', ['BIN LOCATION 1'])
                ->first();

            $hsCodeField = \App\Models\CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->whereRaw('UPPER(TRIM(name)) = ?', ['HS CODE'])
                ->first();

            $normalizedPartNos = $itemsCollection
                ->pluck('part_no')
                ->map(fn ($partNo) => strtoupper(trim((string) $partNo)))
                ->filter()
                ->unique()
                ->values();

            $subProductsByPartNo = collect();
            if ($normalizedPartNos->isNotEmpty()) {
                $subProductsByPartNo = SubProduct::where('created_by', $creatorId)
                    ->whereIn(DB::raw('UPPER(TRIM(chassis_no))'), $normalizedPartNos)
                    ->select('id', 'chassis_no')
                    ->get()
                    ->groupBy(fn ($subProduct) => strtoupper(trim((string) $subProduct->chassis_no)))
                    ->map(fn ($group) => $group->first());
            }

            $pageSubProductIds = $subProductsByPartNo->pluck('id')->filter()->values()->all();
            $customFieldValuesByRecord = collect();
            $fieldIds = collect([$binLocationField->id ?? null, $hsCodeField->id ?? null])->filter()->values();
            if (!empty($pageSubProductIds) && $fieldIds->isNotEmpty()) {
                $customFieldValuesByRecord = \App\Models\CustomFieldValue::whereIn('record_id', $pageSubProductIds)
                    ->whereIn('field_id', $fieldIds)
                    ->get(['record_id', 'field_id', 'value'])
                    ->groupBy('record_id');
            }

            $grnItemsByAsnItem = GrnItem::whereIn('asn_item_id', $itemsCollection->pluck('id'))
                ->with('grn:id,grn_no')
                ->get()
                ->groupBy('asn_item_id');

            $enableDescriptionProductMatching = !$isLargeItemSet;

            $allSubProductIds = AsnItem::where('asn_id', $asn->id)
                ->whereNotNull('sub_product_id')
                ->distinct()
                ->pluck('sub_product_id')
                ->values()
                ->all();

            $consignmentSubProductIds = !empty($allSubProductIds)
                ? SubProduct::whereIn('id', $allSubProductIds)
                    ->whereIn('flag', [SubProduct::FLAG_CONSIGNMENT, SubProduct::FLAG_CANCELLED])
                    ->pluck('id')
                    ->all()
                : [];

            // Sold qty should include all ASN-linked sub-products (not only consignment),
            // so "Bill Sold" can work for transferred/linked items as well.
            $invoiceSoldBySub = [];
            $posSoldBySub = [];
            if (!empty($allSubProductIds)) {
                $invoiceSoldBySub = InvoiceProduct::whereIn('sub_product_id', $allSubProductIds)
                    ->selectRaw('sub_product_id, SUM(quantity) as total')
                    ->groupBy('sub_product_id')
                    ->pluck('total', 'sub_product_id')
                    ->toArray();
                $posSoldBySub = PosProduct::whereIn('sub_product_id', $allSubProductIds)
                    ->selectRaw('sub_product_id, SUM(quantity) as total')
                    ->groupBy('sub_product_id')
                    ->pluck('total', 'sub_product_id')
                    ->toArray();
            }

            // Pricing lookup for sold-to-bill preview modal (sell / purchase).
            $subProductsForPricing = !empty($allSubProductIds)
                ? SubProduct::whereIn('id', $allSubProductIds)
                    ->with(['productService:id,sale_price,purchase_price'])
                    ->get()
                    ->keyBy('id')
                : collect();

            $totalSoldQty = AsnItem::where('asn_id', $asn->id)
                ->whereNotNull('sub_product_id')
                ->get(['sub_product_id'])
                ->sum(function ($item) use ($invoiceSoldBySub, $posSoldBySub) {
                    $subId = $item->sub_product_id;
                    return (int) ($invoiceSoldBySub[$subId] ?? 0) + (int) ($posSoldBySub[$subId] ?? 0);
                });

            // Build "sold items" list across ALL ASN lines (not just current page) for Bill Sold modal
            $soldBillItems = AsnItem::where('asn_id', $asn->id)
                ->where(function ($q) {
                    // Include root lines and transfer-split child lines (bill_id is null).
                    // Exclude bill-generated split lines (already converted).
                    $q->whereNull('split_from_asn_item_id')
                      ->orWhere(function ($q2) {
                          $q2->whereNotNull('split_from_asn_item_id')
                             ->whereNull('bill_id');
                      });
                })
                ->whereNotNull('sub_product_id')
                ->get(['id', 'part_no', 'sub_product_id', 'received_qty', 'converted_qty', 'unit_price'])
                ->map(function ($item) use ($invoiceSoldBySub, $posSoldBySub, $subProductsForPricing) {
                    $subId = $item->sub_product_id;
                    $soldQty = (float) (($invoiceSoldBySub[$subId] ?? 0) + ($posSoldBySub[$subId] ?? 0));
                    $receivedQty = (float) ($item->received_qty ?? 0);
                    $convertedQty = (float) ($item->converted_qty ?? 0);
                    $unconvertedSoldQty = max(0, $soldQty - $convertedQty);
                    $remainingQty = max(0, $receivedQty - $convertedQty);
                    $billQty = min($unconvertedSoldQty, $remainingQty);

                    $subProduct = $subProductsForPricing->get($subId);
                    $product = $subProduct?->productService;
                    $sellPrice = (float) ($subProduct->sale_price ?? 0);
                    if ($sellPrice <= 0) {
                        $sellPrice = (float) ($product->sale_price ?? 0);
                    }
                    $purchasePrice = (float) ($subProduct->purchase_price ?? 0);
                    if ($purchasePrice <= 0) {
                        $purchasePrice = (float) ($product->purchase_price ?? 0);
                    }
                    if ($purchasePrice <= 0) {
                        $purchasePrice = (float) ($item->unit_price ?? 0);
                    }

                    $item->sold_qty = $soldQty;
                    $item->unconverted_sold_qty = $unconvertedSoldQty;
                    $item->remaining_qty = $remainingQty;
                    $item->default_bill_qty = $billQty;
                    $item->sell_price = $sellPrice;
                    $item->purchase_price = $purchasePrice;
                    $item->purchase_total = $purchasePrice * $billQty;
                    return $item;
                })
                ->filter(function ($item) {
                    return (float) ($item->unconverted_sold_qty ?? 0) > 0 && (float) ($item->default_bill_qty ?? 0) > 0;
                })
                ->values();

            $inventorySubProductLookup = array_flip($consignmentSubProductIds);

            foreach ($itemsCollection as $item) {
                $item->matchedProduct = null;
                if ($enableDescriptionProductMatching && !empty($item->description)) {
                    $product = \App\Models\ProductService::where('created_by', $creatorId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $item->description))])
                        ->with(['category', 'brand', 'subBrand'])
                        ->first();

                    if (!$product) {
                        $product = \App\Models\ProductService::where('created_by', $creatorId)
                            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower(trim((string) $item->description)) . '%'])
                            ->with(['category', 'brand', 'subBrand'])
                            ->first();
                    }

                    $item->matchedProduct = $product;
                }

                $normalizedPartNo = strtoupper(trim((string) ($item->part_no ?? '')));
                $subProduct = $normalizedPartNo !== '' ? ($subProductsByPartNo[$normalizedPartNo] ?? null) : null;
                $valuesForSubProduct = $subProduct ? ($customFieldValuesByRecord[$subProduct->id] ?? collect()) : collect();

                $binLocation = null;
                if ($binLocationField && $valuesForSubProduct->isNotEmpty()) {
                    $binLocationValue = $valuesForSubProduct->firstWhere('field_id', $binLocationField->id);
                    if ($binLocationValue && !empty(trim((string) $binLocationValue->value))) {
                        $binLocation = trim((string) $binLocationValue->value);
                    }
                }

                $hsCode = null;
                if ($hsCodeField && $valuesForSubProduct->isNotEmpty()) {
                    $hsCodeValue = $valuesForSubProduct->firstWhere('field_id', $hsCodeField->id);
                    if ($hsCodeValue && !empty(trim((string) $hsCodeValue->value))) {
                        $hsCode = trim((string) $hsCodeValue->value);
                    }
                }

                $item->binLocation = $binLocation;
                $asnHsStored = trim((string) ($item->hs_code ?? ''));
                $item->hsCode = $asnHsStored !== '' ? $item->hs_code : $hsCode;

                $assignedGrnItems = $grnItemsByAsnItem[$item->id] ?? collect();
                $item->assignedGrnNumbers = $assignedGrnItems
                    ->map(function ($grnItem) {
                        return $grnItem->grn ? 'GRN' . str_pad($grnItem->grn->grn_no, 5, '0', STR_PAD_LEFT) : null;
                    })
                    ->filter()
                    ->values()
                    ->all();
                $item->isAssigned = !empty($item->assignedGrnNumbers);

                $sold = (int) ($invoiceSoldBySub[$item->sub_product_id] ?? 0) + (int) ($posSoldBySub[$item->sub_product_id] ?? 0);
                $item->sold_qty = $sold;

                $convertedQty = (float) ($item->converted_qty ?? 0);
                $soldQty = (float) $sold;
                $isReversibleConsignment = !empty($item->sub_product_id) && isset($inventorySubProductLookup[$item->sub_product_id]);
                $item->canReverseInventory = $isReversibleConsignment && round($soldQty, 4) <= round($convertedQty, 4);
            }

            $hasInventoryItems = !empty($consignmentSubProductIds);
            $hasBillItems = AsnItem::where('asn_id', $asn->id)->where('converted_qty', '>', 0)->exists();
            $canConvertMoreToInventory = AsnItem::where('asn_id', $asn->id)
                ->where('received_qty', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('converted_qty')->orWhere('converted_qty', '<=', 0);
                })
                ->whereNull('inventory_converted_at')
                ->whereNull('sub_product_id')
                ->exists();

            // Shipment totals (exclude split child rows created for partial bill conversions)
            $rootLinesQuery = AsnItem::where('asn_id', $asn->id)->whereNull('split_from_asn_item_id');
            $asnTotalQty = (float) $rootLinesQuery->sum('qty');
            $asnTotalAmount = (float) ($rootLinesQuery->sum(DB::raw('qty * unit_price')) ?? 0);

            $asnTotalPrice = (float) AsnItem::where('asn_id', $asn->id)->sum('total_price');
            $asnTotalWeight = (float) AsnItem::where('asn_id', $asn->id)->sum('total_weight');
            
            // Get warehouses for convert to bill modal
            $warehouses = \App\Models\warehouse::where('created_by', $creatorId)
                ->get()
                ->pluck('name', 'id');
            
            // Get taxes for convert to bill modal
            $taxes = \App\Models\Tax::where('created_by', $creatorId)
                ->get()
                ->pluck('name', 'id');

            $resolvedSupplierCode = $asn->supplier_code ?: '-';
            if ($asn->supplier && !empty($asn->supplier->supplier_code)) {
                $resolvedSupplierCode = $asn->supplier->supplier_code;
            } elseif (!empty($asn->supplier_name)) {
                $vendor = Vender::where('created_by', $creatorId)
                    ->where('name', $asn->supplier_name)
                    ->first(['supplier_code']);
                if ($vendor && !empty($vendor->supplier_code)) {
                    $resolvedSupplierCode = $vendor->supplier_code;
                }
            }

            $asnLockedForEdit = $this->isAsnLockedForEditing($asn, $creatorId);

            return view('asn.show', compact('asn', 'asnItems', 'totalItems', 'warehouses', 'taxes', 'hasInventoryItems', 'hasBillItems', 'canConvertMoreToInventory', 'totalSoldQty', 'soldBillItems', 'asnTotalQty', 'asnTotalAmount', 'asnTotalPrice', 'asnTotalWeight', 'asnLockedForEdit', 'resolvedSupplierCode', 'isLargeItemSet'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Create missing ASN item rows for sub-products linked by asn_id.
     * This makes them visible in ASN view and eligible for convert-to-bill workflows.
     */
    private function ensureAsnItemsForLinkedSubProducts(Asn $asn, int $creatorId): void
    {
        $linkedSubProducts = SubProduct::where('asn_id', $asn->id)
            ->where('created_by', $creatorId)
            ->whereNotNull('id')
            ->get(['id', 'product_id', 'chassis_no', 'quantity', 'purchase_price', 'sale_price']);

        if ($linkedSubProducts->isEmpty()) {
            return;
        }

        $existingSubIds = AsnItem::where('asn_id', $asn->id)
            ->whereNotNull('sub_product_id')
            ->get(['id', 'sub_product_id', 'qty', 'received_qty', 'converted_qty'])
            ->keyBy('sub_product_id');

        $subIds = $linkedSubProducts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invoiceSoldBySub = [];
        $posSoldBySub = [];
        if (!empty($subIds)) {
            $invoiceSoldBySub = InvoiceProduct::whereIn('sub_product_id', $subIds)
                ->selectRaw('sub_product_id, SUM(quantity) as total')
                ->groupBy('sub_product_id')
                ->pluck('total', 'sub_product_id')
                ->toArray();
            $posSoldBySub = PosProduct::whereIn('sub_product_id', $subIds)
                ->selectRaw('sub_product_id, SUM(quantity) as total')
                ->groupBy('sub_product_id')
                ->pluck('total', 'sub_product_id')
                ->toArray();
        }

        foreach ($linkedSubProducts as $subProduct) {
            $productName = \App\Models\ProductService::where('id', $subProduct->product_id)
                ->value('name');

            $soldQty = (float) (($invoiceSoldBySub[$subProduct->id] ?? 0) + ($posSoldBySub[$subProduct->id] ?? 0));
            $onHandQty = max(0, (float) ($subProduct->quantity ?? 0));
            $qty = max($onHandQty, $soldQty);
            $unitPrice = (float) ($subProduct->purchase_price ?? $subProduct->sale_price ?? 0);

            $existing = $existingSubIds->get($subProduct->id);
            if ($existing) {
                // Backfill existing linked rows created with zero received qty
                // so sold items can appear in "Bill Sold" modal.
                $converted = (float) ($existing->converted_qty ?? 0);
                $targetQty = max((float) ($existing->qty ?? 0), $qty, $converted);
                $targetReceived = max((float) ($existing->received_qty ?? 0), $qty, $converted);

                if (round($targetQty, 4) !== round((float) ($existing->qty ?? 0), 4) ||
                    round($targetReceived, 4) !== round((float) ($existing->received_qty ?? 0), 4)) {
                    $existing->qty = $targetQty;
                    $existing->received_qty = $targetReceived;
                    if (empty($existing->unit_price) && $unitPrice > 0) {
                        $existing->unit_price = $unitPrice;
                    }
                    $existing->save();
                }
                continue;
            }

            AsnItem::create([
                'asn_id' => $asn->id,
                'sub_product_id' => $subProduct->id,
                'part_no' => $subProduct->chassis_no,
                'description' => $productName ?: ($subProduct->chassis_no ?? 'Linked sub-product'),
                'qty' => $qty,
                'received_qty' => $qty,
                'converted_qty' => 0,
                'unit_price' => $unitPrice,
                'unit_weight' => 0,
                'box_no' => null,
                'supplier_po_no' => null,
                'our_pro_id' => null,
                'our_pro_no' => null,
                'order_ref' => null,
                'hs_code' => null,
                'container_no' => null,
                'dec_no' => null,
                'dec_date' => null,
                'origin' => null,
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Asn $asn)
    {
        if (\Auth::user()->can('edit bill')) {
            if ($this->isAsnLockedForEditing($asn, \Auth::user()->creatorId())) {
                return redirect()->route('asn.show', $asn->id)->with('error', __('This ASN cannot be edited because it has been converted to inventory or bill.'));
            }

            $itemsQuery = AsnItem::where('asn_id', $asn->id)
                ->whereNull('split_from_asn_item_id')
                ->orderBy('id');
            $totalItems = (int) $itemsQuery->count();
            $isLargeItemSet = $totalItems > 500;
            $perPage = $isLargeItemSet ? 150 : 200;
            $asnItems = $itemsQuery->paginate($perPage)->appends(request()->query());
            $suppliers = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $suppliers->prepend('Select Supplier', '');

            // Get PROs for linking
            $pros = collect();
            $proNamesById = [];
            if (!$isLargeItemSet) {
                $pros = Pro::where('created_by', \Auth::user()->creatorId())
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function($pro) {
                        return [
                            'id' => $pro->id,
                            'name' => \Auth::user()->proNumberFormat($pro->pro_no)
                        ];
                    })
                    ->pluck('name', 'id');
                $pros->prepend('Select PRO', '');
            } else {
                // Large ASN optimization: only resolve names for PROs already selected on rows.
                $usedProIds = $asnItems->getCollection()
                    ->pluck('our_pro_id')
                    ->filter()
                    ->unique()
                    ->values();

                $proNamesById = Pro::where('created_by', \Auth::user()->creatorId())
                    ->whereIn('id', $usedProIds)
                    ->get(['id', 'pro_no'])
                    ->mapWithKeys(function ($pro) {
                        return [$pro->id => \Auth::user()->proNumberFormat($pro->pro_no)];
                    })
                    ->toArray();
            }

            $currencies = \App\Models\Currency::select('id', 'name', 'exchange_rate')
                ->orderBy('name')
                ->get();

            // Get users for GRN assignment (no prepend: blade has its own "Select User" placeholder)
            $users = \App\Models\User::where('created_by', \Auth::user()->creatorId())
                ->orWhere('id', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');

            $itemIds = $asnItems->getCollection()->pluck('id')->all();
            $grnItemsByAsnItem = GrnItem::whereIn('asn_item_id', $itemIds)
                ->with('grn:id,grn_no')
                ->get()
                ->groupBy('asn_item_id');

            $assignedItemIds = $grnItemsByAsnItem->keys()->map(fn ($id) => (int) $id)->all();
            $assignedItemsInfo = [];
            foreach ($grnItemsByAsnItem as $itemId => $grnItems) {
                $grnNumbers = $grnItems
                    ->map(function ($grnItem) {
                        return $grnItem->grn ? 'GRN' . str_pad($grnItem->grn->grn_no, 5, '0', STR_PAD_LEFT) : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                $assignedItemsInfo[(int) $itemId] = [
                    'grn_numbers' => $grnNumbers,
                    'grn_count' => count($grnNumbers),
                ];
            }

            $warehouses = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');
            $warehouses->prepend('Select Warehouse', '');

            $hasConvertedItems = AsnItem::where('asn_id', $asn->id)
                ->where(function ($query) {
                    $query->where('converted_qty', '>', 0)
                          ->orWhereNotNull('sub_product_id');
                })
                ->exists();

            $allBoxNos = AsnItem::where('asn_id', $asn->id)
                ->whereNotNull('box_no')
                ->where('box_no', '!=', '')
                ->distinct()
                ->orderBy('box_no')
                ->pluck('box_no');

            // All item IDs eligible for GRN selection (exclude lines already assigned to GRN), across all pages.
            $allEditItemIds = AsnItem::where('asn_id', $asn->id)
                ->whereNull('split_from_asn_item_id')
                ->pluck('id');
            $assignedToGrnAllIds = GrnItem::whereIn('asn_item_id', $allEditItemIds)
                ->distinct()
                ->pluck('asn_item_id');
            $allSelectableItemIds = $allEditItemIds->diff($assignedToGrnAllIds)->values()->all();

            return view('asn.edit', compact('asn', 'asnItems', 'totalItems', 'suppliers', 'pros', 'proNamesById', 'currencies', 'warehouses', 'users', 'assignedItemIds', 'assignedItemsInfo', 'hasConvertedItems', 'isLargeItemSet', 'allBoxNos', 'allSelectableItemIds'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asn $asn)
    {
        if (\Auth::user()->can('edit bill')) {
            if ($this->isAsnLockedForEditing($asn, \Auth::user()->creatorId())) {
                return redirect()->route('asn.show', $asn->id)->with('error', __('This ASN cannot be edited because it has been converted to inventory or bill.'));
            }

            $validated = $request->validate([
                'supplier_id' => 'nullable|exists:venders,id',
                'supplier_name' => 'nullable|string|max:255',
                'supplier_code' => 'nullable|string|max:255',
                'supplier_inv_no' => 'nullable|string|max:255',
                'container_no' => 'nullable|string|max:255',
                'dec_no' => 'nullable|string|max:255',
                'dec_date' => 'nullable|date',
                'asn_date' => 'required|date',
                'warehouse_id' => 'required|exists:warehouses,id',
                'currency_id' => 'nullable|exists:currencies,id',
                'exchange_rate' => 'nullable|numeric|min:0',
                'status' => 'required|in:created,sent,partially_received,fully_received,manually_received',
                'hs_code' => 'nullable|string|max:255',
                'origin' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.box_no' => 'nullable|string|max:255',
                'items.*.supplier_po_no' => 'nullable|string|max:255',
                'items.*.our_pro_id' => 'nullable|exists:pros,id',
                'items.*.order_ref' => 'nullable|string|max:255',
                'items.*.part_no' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.qty' => 'required|numeric|min:0',
                'items.*.received_qty' => 'required|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.unit_weight' => 'nullable|numeric|min:0',
                'items.*.hs_code' => 'nullable|string|max:255',
                'items.*.container_no' => 'nullable|string|max:255',
                'items.*.dec_no' => 'nullable|string|max:255',
                'items.*.dec_date' => 'nullable|date',
                'items.*.origin' => 'nullable|string|max:255',
            ]);

            if ($redirect = $this->validateAsnItemsReceivedVersusQty($validated['items'])) {
                return $redirect;
            }

            // Update ASN header
            $asn->supplier_id = $validated['supplier_id'] ?? null;
            $asn->supplier_name = $validated['supplier_name'] ?? null;
            $asn->supplier_code = $validated['supplier_code'] ?? null;
            $asn->supplier_inv_no = $validated['supplier_inv_no'] ?? null;
            $asn->container_no = $validated['container_no'] ?? null;
            $asn->dec_no = $validated['dec_no'] ?? null;
            $asn->dec_date = $validated['dec_date'] ?? null;
            $asn->asn_date = $validated['asn_date'];
            $asn->warehouse_id = $validated['warehouse_id'] ?? null;
            $asn->currency_id = $validated['currency_id'] ?? null;
            $asn->exchange_rate = $validated['exchange_rate'] ?? $asn->exchange_rate ?? 1.0;
            $asn->status = $validated['status'];
            
            $asn->save();

            // Load existing ASN items once; we update these rows in place.
            $asn->load('items');
            $grns = Grn::where('asn_id', $asn->id)->with('items')->get();
            $oldAsnItemMapping = [];
            foreach ($asn->items as $oldItem) {
                $oldAsnItemMapping[$oldItem->id] = [
                    'part_no' => $oldItem->part_no,
                    'description' => $oldItem->description,
                ];
            }

            $existingItemsById = $asn->items->keyBy('id');
            $existingItemsInOrder = $asn->items->values();

            // Update existing rows only (no new ASN item creation in update).
            $newAsnItems = [];
            foreach ($validated['items'] as $rowIndex => $itemData) {
                $incomingItemId = isset($itemData['id']) ? (int) $itemData['id'] : 0;

                // Fallback: if hidden id is missing, map by existing row order.
                if ($incomingItemId <= 0 && isset($existingItemsInOrder[$rowIndex])) {
                    $incomingItemId = (int) $existingItemsInOrder[$rowIndex]->id;
                }

                if ($incomingItemId <= 0) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Could not match submitted item rows to existing ASN items. Please refresh and try again.'));
                }
                if (!isset($existingItemsById[$incomingItemId])) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Invalid ASN item detected in update request. Please refresh and try again.'));
                }
                $item = $existingItemsById[$incomingItemId];

                $item->box_no = $itemData['box_no'] ?? null;
                $item->supplier_po_no = $itemData['supplier_po_no'] ?? null;
                $item->our_pro_id = $itemData['our_pro_id'] ?? null;
                
                // Store PRO number string if PRO is linked
                if ($item->our_pro_id) {
                    $pro = Pro::find($item->our_pro_id);
                    $item->our_pro_no = $pro ? \Auth::user()->proNumberFormat($pro->pro_no) : null;
                }
                
                $item->order_ref = $itemData['order_ref'] ?? null;
                $item->part_no = $itemData['part_no'] ?? null;
                
                // Auto-fill description from stock if empty
                $description = $itemData['description'] ?? null;
                if (empty(trim($description ?? '')) && !empty($item->part_no)) {
                    $description = $this->getDescriptionFromStock($item->part_no, \Auth::user()->creatorId());
                }
                $item->description = $description;
                
                $item->qty = $itemData['qty'];
                $item->received_qty = $itemData['received_qty'];
                $item->unit_price = $itemData['unit_price'];
                $item->unit_weight = $itemData['unit_weight'] ?? 0;
                
                // Use item-specific values if provided, otherwise fall back to header values
                $item->hs_code = $itemData['hs_code'] ?? $validated['hs_code'] ?? null;
                $item->container_no = $itemData['container_no'] ?? $validated['container_no'] ?? null;
                $item->dec_no = $itemData['dec_no'] ?? $validated['dec_no'] ?? null;
                $item->dec_date = $itemData['dec_date'] ?? $validated['dec_date'] ?? null;
                $item->origin = $itemData['origin'] ?? $validated['origin'] ?? null;
                
                // discrepancy, total_price, total_weight will be auto-calculated by model
                $item->save();
                // After ASN save, link PRO item to stock sub-product by part_no (PRO + part_no match).
                $this->syncProItemSubProductFromAsnItem($item, \Auth::user()->creatorId());
                $newAsnItems[] = $item;
            }

            // Sync GRN data with updated ASN
            $this->syncGrnWithAsn($asn, $oldAsnItemMapping, $newAsnItems, $grns);

            return redirect()
                ->route('asn.edit', ['asn' => $asn->id, 'page' => (int) $request->input('current_page', 1)])
                ->with('success', __('ASN updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Sync GRN data with updated ASN
     */
    private function syncGrnWithAsn($asn, $oldAsnItemMapping, $newAsnItems, $grns)
    {
        if ($grns->isEmpty()) {
            return; // No GRNs to sync
        }
        
        // Create a mapping of new ASN items by part_no for easy lookup
        $asnItemMapByPartNo = [];
        foreach ($newAsnItems as $newItem) {
            if (!empty($newItem->part_no)) {
                $asnItemMapByPartNo[$newItem->part_no] = $newItem;
            }
        }
        
        // Also create mapping by description as fallback
        $asnItemMapByDescription = [];
        foreach ($newAsnItems as $newItem) {
            if (!empty($newItem->description)) {
                $descKey = strtolower(trim($newItem->description));
                if (!isset($asnItemMapByDescription[$descKey])) {
                    $asnItemMapByDescription[$descKey] = $newItem;
                }
            }
        }
        
        foreach ($grns as $grn) {
            // Update GRN header fields from ASN
            $grn->supplier_id = $asn->supplier_id;
            $grn->supplier_name = $asn->supplier_name;
            $grn->save();
            
            // Update GRN items
            foreach ($grn->items as $grnItem) {
                $matchedAsnItem = null;
                
                // Get old ASN item info from mapping
                $oldAsnItemInfo = null;
                if ($grnItem->asn_item_id && isset($oldAsnItemMapping[$grnItem->asn_item_id])) {
                    $oldAsnItemInfo = $oldAsnItemMapping[$grnItem->asn_item_id];
                }
                
                // Try to find matching new ASN item by part_no first
                if ($oldAsnItemInfo && !empty($oldAsnItemInfo['part_no']) && isset($asnItemMapByPartNo[$oldAsnItemInfo['part_no']])) {
                    $matchedAsnItem = $asnItemMapByPartNo[$oldAsnItemInfo['part_no']];
                }
                // Try current GRN item part_no
                elseif (!empty($grnItem->part_no) && isset($asnItemMapByPartNo[$grnItem->part_no])) {
                    $matchedAsnItem = $asnItemMapByPartNo[$grnItem->part_no];
                }
                // Try matching by description as fallback
                elseif ($oldAsnItemInfo && !empty($oldAsnItemInfo['description'])) {
                    $descKey = strtolower(trim($oldAsnItemInfo['description']));
                    if (isset($asnItemMapByDescription[$descKey])) {
                        $matchedAsnItem = $asnItemMapByDescription[$descKey];
                    }
                }
                elseif (!empty($grnItem->description)) {
                    $descKey = strtolower(trim($grnItem->description));
                    if (isset($asnItemMapByDescription[$descKey])) {
                        $matchedAsnItem = $asnItemMapByDescription[$descKey];
                    }
                }
                
                if ($matchedAsnItem) {
                    // Update GRN item fields from ASN item
                    $grnItem->asn_item_id = $matchedAsnItem->id; // Update reference to new ASN item
                    $grnItem->part_no = $matchedAsnItem->part_no;
                    $grnItem->description = $matchedAsnItem->description;
                    $grnItem->qty = $matchedAsnItem->qty; // Update expected quantity
                    $grnItem->unit_price = $matchedAsnItem->unit_price;
                    // Preserve received_qty - don't overwrite it as it's manually entered
                    // discrepancy and total_price will be auto-calculated by model
                    $grnItem->save();
                }
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asn $asn)
    {
        if (\Auth::user()->can('delete bill')) {
            // Only allow deletion if status is 'created'
            if ($asn->status !== 'created') {
                return redirect()->back()->with('error', __('Cannot delete ASN. Only ASNs with "Created" status can be deleted.'));
            }
            
            $asn->delete();
            return redirect()->route('asn.index')->with('success', __('ASN deleted successfully.'));
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
            return view('asn.import');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show ASN items-only import form.
     * Header fields are entered from the modal, file contains items only.
     */
    public function importFileItemsOnly()
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $creatorId = \Auth::user()->creatorId();
        $suppliers = Vender::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');
        $currencies = Currency::select('id', 'name', 'exchange_rate')
            ->orderBy('name')
            ->get();
        $warehouses = \App\Models\warehouse::where('created_by', $creatorId)->orderBy('name')->pluck('name', 'id');

        return view('asn.import_items_only', compact('suppliers', 'currencies', 'warehouses'));
    }

    /**
     * Import ASN from Excel file
     */
    public function import(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $request->validate([
                'file' => 'required|mimes:xlsx,csv',
            ]);

            try {
                Excel::import(new AsnImport(\Auth::user()->creatorId()), $request->file('file'));
                
                return back()->with('success', __('ASN imported successfully!'));
            } catch (AsnImportValidationException $e) {
                $validationErrors = $e->getValidationErrors();
                $count = count($validationErrors);
                $filename = 'asn_import_errors/' . uniqid('asn_', true) . '.xlsx';
                Storage::disk('local')->makeDirectory('asn_import_errors');
                Excel::store(new AsnImportErrorsExport($validationErrors), $filename, 'local');

                $token = Str::random(64);
                Cache::put('asn_import_error_report_' . $token, $filename, now()->addMinutes(15));

                \Log::warning('ASN import validation failed', [
                    'error_count' => $count,
                    'user_id' => \Auth::user()->creatorId()
                ]);

                return redirect()->back()
                    ->with('error', __('ASN import failed. :count error(s) found. Download the error report below to see what to fix.', ['count' => $count]))
                    ->with('asn_import_error_report_token', $token);
            } catch (\Exception $e) {
                \Log::error('ASN import failed', [
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
     * Import ASN using items-only file.
     * ASN header fields are provided from form inputs.
     */
    public function importItemsOnly(Request $request)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:venders,id',
            'eta_date' => 'nullable|date',
            'supplier_inv_no' => 'nullable|string|max:255',
            'container_no' => 'nullable|string|max:255',
            'dec_date' => 'nullable|date',
            'boe_number' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:255',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'warehouse_id' => 'required|exists:warehouses,id',
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

            $warehouse = \App\Models\warehouse::where('id', $validated['warehouse_id'])
                ->where('created_by', $creatorId)
                ->first();
            if (!$warehouse) {
                return back()->with('error', __('Selected warehouse is invalid for this company.'));
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

                    if (in_array($normalized, ['BOX NO', 'BOX NO.'])) {
                        $candidateMap['box_no'] = $colIndex;
                    } elseif ($normalized === 'SUPPLIER PO NO') {
                        $candidateMap['supplier_po_no'] = $colIndex;
                    } elseif ($normalized === 'OUR PRO NO') {
                        $candidateMap['our_pro_no'] = $colIndex;
                    } elseif ($normalized === 'ORDER REF') {
                        $candidateMap['order_ref'] = $colIndex;
                    } elseif (in_array($normalized, ['PART NO', 'PART NUMBER', 'PARTNO'])) {
                        $candidateMap['part_no'] = $colIndex;
                    } elseif (in_array($normalized, ['DESCRIPTION', 'DESC'])) {
                        $candidateMap['description'] = $colIndex;
                    } elseif (in_array($normalized, ['QTY', 'QUANTITY'])) {
                        $candidateMap['qty'] = $colIndex;
                    } elseif (in_array($normalized, ['UNIT PRICE', 'PRICE', 'UNITPRICE'])) {
                        $candidateMap['unit_price'] = $colIndex;
                    } elseif ($normalized === 'UNIT WEIGHT') {
                        $candidateMap['unit_weight'] = $colIndex;
                    } elseif ($normalized === 'DEC NO') {
                        $candidateMap['dec_no'] = $colIndex;
                    } elseif (in_array($normalized, ['DEC DATE', 'DED DATE'])) {
                        $candidateMap['dec_date'] = $colIndex;
                    } elseif ($normalized === 'ORIGIN') {
                        $candidateMap['origin'] = $colIndex;
                    }
                }

                if (isset($candidateMap['part_no']) && isset($candidateMap['qty']) && isset($candidateMap['unit_price'])) {
                    $headerRowIndex = $i;
                    $columnMap = $candidateMap;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \Exception('Could not find item header row. Required headers: PART NO, QTY, UNIT PRICE.');
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

                $qty = (float)($row[$columnMap['qty']] ?? 0);
                $unitPrice = (float)($row[$columnMap['unit_price']] ?? 0);
                $unitWeight = isset($columnMap['unit_weight']) ? (float)($row[$columnMap['unit_weight']] ?? 0) : 0;
                $description = isset($columnMap['description']) ? trim((string)($row[$columnMap['description']] ?? '')) : '';

                if ($qty < 0 || $unitPrice < 0 || $unitWeight < 0) {
                    throw new \Exception("Negative values are not allowed in row {$excelRowNo}.");
                }

                $preparedItems[] = [
                    'box_no' => isset($columnMap['box_no']) ? trim((string)($row[$columnMap['box_no']] ?? '')) : null,
                    'supplier_po_no' => isset($columnMap['supplier_po_no']) ? trim((string)($row[$columnMap['supplier_po_no']] ?? '')) : null,
                    'our_pro_no' => isset($columnMap['our_pro_no']) ? trim((string)($row[$columnMap['our_pro_no']] ?? '')) : null,
                    'order_ref' => isset($columnMap['order_ref']) ? trim((string)($row[$columnMap['order_ref']] ?? '')) : null,
                    'part_no' => $partNo,
                    'description' => $description,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_weight' => $unitWeight,
                    'dec_no' => isset($columnMap['dec_no']) ? trim((string)($row[$columnMap['dec_no']] ?? '')) : null,
                    'dec_date' => isset($columnMap['dec_date']) ? ($row[$columnMap['dec_date']] ?? null) : null,
                    'origin' => isset($columnMap['origin']) ? trim((string)($row[$columnMap['origin']] ?? '')) : null,
                ];

                $excelRowNo++;
            }

            if (empty($preparedItems)) {
                throw new \Exception('No valid item rows found in the uploaded file.');
            }

            DB::beginTransaction();
            try {
                $asn = new Asn();
                $asn->asn_no = (string)$this->asnNumber();
                $asn->supplier_id = $supplier->id;
                $asn->supplier_name = $supplier->name;
                $asn->supplier_code = $supplier->contact ?? null;
                $asn->supplier_inv_no = $validated['supplier_inv_no'] ?? null;
                $asn->container_no = $validated['container_no'] ?? null;
                $asn->dec_no = null;
                $asn->boe_number = $validated['boe_number'] ?? null;
                $asn->dec_date = $validated['dec_date'] ?? null;
                $asn->asn_date = date('Y-m-d');
                $asn->warehouse_id = $warehouse->id;
                $asn->currency_id = $validated['currency_id'] ?? null;
                $asn->exchange_rate = $validated['exchange_rate'] ?? 1;
                $asn->status = 'created';
                $asn->created_by = $creatorId;
                $asn->save();

                $user = \App\Models\User::find($creatorId);
                foreach ($preparedItems as $itemData) {
                    $description = $itemData['description'];
                    if (empty(trim($description ?? '')) && !empty($itemData['part_no'])) {
                        $description = $this->getDescriptionFromStock($itemData['part_no'], $creatorId);
                    }

                    $ourProId = null;
                    $ourProNoFormatted = $itemData['our_pro_no'];
                    if (!empty($itemData['our_pro_no'])) {
                        $proNoNumeric = preg_replace('/[^0-9]/', '', $itemData['our_pro_no']);
                        if (!empty($proNoNumeric)) {
                            $pro = Pro::where('created_by', $creatorId)
                                ->where('pro_no', $proNoNumeric)
                                ->first();
                            if ($pro) {
                                $ourProId = $pro->id;
                                $ourProNoFormatted = $user ? $user->proNumberFormat($pro->pro_no) : $itemData['our_pro_no'];
                            }
                        }
                    }

                    $item = new AsnItem();
                    $item->asn_id = $asn->id;
                    $item->box_no = $itemData['box_no'];
                    $item->supplier_po_no = $itemData['supplier_po_no'];
                    $item->our_pro_id = $ourProId;
                    $item->our_pro_no = $ourProNoFormatted;
                    $item->order_ref = $itemData['order_ref'];
                    $item->part_no = $itemData['part_no'];
                    $item->description = $description;
                    $item->qty = $itemData['qty'];
                    $item->received_qty = 0;
                    $item->unit_price = $itemData['unit_price'];
                    $item->unit_weight = $itemData['unit_weight'];
                    $item->hs_code = $validated['hs_code'] ?? null;
                    $item->container_no = $validated['container_no'] ?? null;
                    $item->dec_no = $itemData['dec_no'] ?: null;
                    $item->dec_date = $this->parseImportDate($itemData['dec_date']) ?: ($validated['dec_date'] ?? null);
                    $item->origin = $itemData['origin'];
                    $item->save();
                }

                $asn->updateStatusBasedOnItems();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return back()->with('success', __('ASN imported successfully (items-only format).'));
        } catch (\Exception $e) {
            \Log::error('ASN items-only import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId(),
            ]);

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    /**
     * Download ASN import errors report (Excel) generated after a failed import.
     * Uses token in URL so the file is returned as Excel without relying on session.
     */
    public function downloadImportErrorsReport(Request $request)
    {
        $token = $request->query('token');
        $path = $token ? Cache::get('asn_import_error_report_' . $token) : null;

        if (!$path || !Storage::disk('local')->exists($path)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => __('Error report no longer available.')], 404);
            }
            return redirect()->back()->with('error', __('Error report no longer available. It may have expired or already been downloaded.'));
        }

        $downloadName = 'ASN_Import_Errors_' . date('Y-m-d_His') . '.xlsx';

        // Read file content before deleting (we were deleting before sending, which caused FileNotFoundException)
        $contents = Storage::disk('local')->get($path);
        Cache::forget('asn_import_error_report_' . $token);
        Storage::disk('local')->delete($path);

        return response($contents, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        ]);
    }

    /**
     * Download sample Excel file for ASN import
     */
    public function downloadSample()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(18);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(12);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(12);
            $sheet->getColumnDimension('I')->setWidth(12);
            $sheet->getColumnDimension('J')->setWidth(12);
            $sheet->getColumnDimension('K')->setWidth(12);
            $sheet->getColumnDimension('L')->setWidth(12);
            $sheet->getColumnDimension('M')->setWidth(12);
            $sheet->getColumnDimension('N')->setWidth(12);
            $sheet->getColumnDimension('O')->setWidth(12);
            $sheet->getColumnDimension('P')->setWidth(15);
            $sheet->getColumnDimension('Q')->setWidth(15);
            $sheet->getColumnDimension('R')->setWidth(12);
            $sheet->getColumnDimension('S')->setWidth(12);

            // Row 1: Title
            $sheet->mergeCells('D1:F1');
            $sheet->setCellValue('D1', 'Advanced Shipping Notice');
            $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row 2: Supplier Name
            $sheet->setCellValue('A2', 'SUPPLIER NAME');
            $sheet->setCellValue('B2', 'Sample Supplier Ltd');

            // Row 3: ETA Date
            $sheet->setCellValue('A3', 'ETA DATE');
            $sheet->setCellValue('B3', date('Y-m-d', strtotime('+30 days')));

            // Row 4: Supplier Inv No
            $sheet->setCellValue('A4', 'SUPPLIER INV NO.');
            $sheet->setCellValue('B4', 'INV-2025-001');

            // Row 5: Container No
            $sheet->setCellValue('A5', 'CONTAINER NO');
            $sheet->setCellValue('B5', 'BICU123456');

            // Row 6: DEC DATE
            $sheet->setCellValue('A6', 'DEC DATE');
            $sheet->setCellValue('B6', date('Y-m-d'));

            // Row 8: BOE Number
            $sheet->setCellValue('A8', 'BOE NUMBER');
            $sheet->setCellValue('B8', 'BOE-123456');

            // Row 9: HS Code
            $sheet->setCellValue('A9', 'HS CODE');
            $sheet->setCellValue('B9', '87083000');

            // Row 10: Currency ID
            $sheet->setCellValue('A10', 'CURRENCY ID');
            $sheet->setCellValue('B10', '1'); // Default currency ID (can be changed)

            // Row 11: Exchange Rate
            $sheet->setCellValue('A11', 'EXCHANGE RATE');
            $sheet->setCellValue('B11', '1.0'); // Default exchange rate

            // Row 12: Warehouse
            $sheet->setCellValue('A12', 'WAREHOUSE');
            // Get first warehouse name as example, or use ID
            $firstWarehouse = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())->first();
            $sheet->setCellValue('B12', $firstWarehouse ? $firstWarehouse->name : '1'); // Warehouse name or ID
            // Row 13: Empty row for spacing
            $sheet->setCellValue('A13', '');

            // Row 14: Column Headers (simplified for import)
            // Removed: RECEIVED QTY (defaults to QTY), DISCREPANCY, TOTAL PRICE, TOTAL WEIGHT
            $headers = ['BOX NO.', 'SUPPLIER PO NO', 'OUR PRO NO', 'ORDER REF', 'PART NO', 'DESCRIPTION', 'QTY', 'UNIT PRICE', 'UNIT WEIGHT', 'DEC NO', 'DED DATE', 'ORIGIN'];
            $headerColumn = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($headerColumn . '14', $header);
                $sheet->getStyle($headerColumn . '14')->getFont()->setBold(true);
                $sheet->getStyle($headerColumn . '14')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFF00'); // Yellow background
                $sheet->getStyle($headerColumn . '14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerColumn++;
            }

            // Sample data rows (Row 15+) - match simplified headers
            // Columns: BOX NO, SUPPLIER PO NO, OUR PRO NO, ORDER REF, PART NO, DESCRIPTION, QTY, UNIT PRICE, UNIT WEIGHT, DEC NO, DED DATE, ORIGIN
            $sampleData = [
                ['BOX00001', 'SUP11111', 'PO12345', 'FLCN001', '04465-60280', 'FRONT BRAKE PAD', 20, 55.00, 1.500, '1234567891011', date('Y-m-d'), 'JAPAN'],
                ['BOX00001', 'SUP11111', 'PO12345', 'FLCN001', '13568-39016', 'TIMING BELT', 30, 80.00, 0.600, '1234567891011', date('Y-m-d'), 'JAPAN'],
                ['BOX00001', 'SUP11111', 'PO12345', 'FLCN001', '90916-03093', 'THERMOSTAT', 6, 25.00, 0.300, '1234567891011', date('Y-m-d'), 'JAPAN'],
            ];

            $row = 15;
            foreach ($sampleData as $data) {
                $col = 'A';
                foreach ($data as $index => $value) {
                    // Format numeric columns: QTY, UNIT PRICE, UNIT WEIGHT
                    if (in_array($index, [6, 7, 8])) {
                        $sheet->setCellValue($col . $row, $value);
                        if (in_array($index, [7, 8])) { // Prices and weights
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                        }
                    } else {
                        $sheet->setCellValue($col . $row, $value);
                    }
                    $col++;
                }
                $row++;
            }

            // Style all header labels (left column)
            $sheet->getStyle('A2:A12')->getFont()->setBold(true);
            // Removed right-side ASN labels; keep left headers bold
            // $sheet->getStyle('F2:F3')->getFont()->setBold(true);

            // Add borders to item table (A..L = 12 columns)
            $sheet->getStyle('A14:L' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Create temporary file
            $filename = 'sample-asn-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            // Download the file
            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error generating ASN sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Download sample file for ASN items-only import.
     * File contains only item columns.
     */
    public function downloadSampleItemsOnly()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(14);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(25);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(12);
            $sheet->getColumnDimension('I')->setWidth(12);
            $sheet->getColumnDimension('J')->setWidth(12);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(12);
            $headers = ['BOX NO.', 'SUPPLIER PO NO', 'OUR PRO NO', 'ORDER REF', 'PART NO', 'DESCRIPTION', 'QTY', 'UNIT PRICE', 'UNIT WEIGHT', 'DEC NO', 'DED DATE', 'ORIGIN'];
            $headerColumn = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($headerColumn . '1', $header);
                $sheet->getStyle($headerColumn . '1')->getFont()->setBold(true);
                $sheet->getStyle($headerColumn . '1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFF00');
                $sheet->getStyle($headerColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerColumn++;
            }

            $sampleData = [
                ['BOX00001', 'SUP11111', 'PO12345', 'FLCN001', '04465-60280', 'FRONT BRAKE PAD', 20, 55.00, 1.500, '1234567891011', date('Y-m-d'), 'JAPAN'],
                ['BOX00001', 'SUP11111', 'PO12345', 'FLCN001', '13568-39016', 'TIMING BELT', 30, 80.00, 0.600, '1234567891011', date('Y-m-d'), 'JAPAN'],
            ];

            $row = 2;
            foreach ($sampleData as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $index => $value) {
                    $sheet->setCellValue($col . $row, $value);
                    if (in_array($index, [6, 7, 8])) {
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    $col++;
                }
                $row++;
            }

            $sheet->getStyle('A1:L' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $filename = 'sample-asn-items-only-' . date('Y-m-d') . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error generating ASN items-only sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Parse date from import cell value (string or Excel serial).
     */
    private function parseImportDate($dateValue): ?string
    {
        if ($dateValue === null || $dateValue === '') {
            return null;
        }

        if (is_numeric($dateValue)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse((string)$dateValue)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function grn(Request $request, $asnId)
    {
        if (!\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
        $creatorId = \Auth::user()->creatorId();
        $asn = \App\Models\Asn::where('created_by', $creatorId)->findOrFail($asnId);
        
        // Prevent GRN if status is 'open'
        if ($asn->status === 'open') {
            return redirect()->back()->with('error', __('Cannot access GRN. ASN status must not be "Open". Please fill Dec No and Dec Date and update the ASN first.'));
        }

        $allItemCount = (int) AsnItem::where('asn_id', $asn->id)
            ->whereNull('split_from_asn_item_id')
            ->count();
        $isLargeItemSet = $allItemCount > 1000;
        $perPage = $isLargeItemSet ? 150 : 200;

        $asnItemsQuery = AsnItem::where('asn_id', $asn->id)
            ->whereNull('split_from_asn_item_id')
            ->orderBy('id');

        if ($request->filled('box_no')) {
            $asnItemsQuery->where('box_no', $request->box_no);
        }

        $asnItems = $asnItemsQuery
            ->paginate($perPage)
            ->withQueryString();

        $allBoxNos = AsnItem::where('asn_id', $asn->id)
            ->whereNull('split_from_asn_item_id')
            ->whereNotNull('box_no')
            ->where('box_no', '!=', '')
            ->distinct()
            ->orderBy('box_no')
            ->pluck('box_no');

        $itemCollection = $asnItems->getCollection();

        $descriptionKeys = $itemCollection
            ->pluck('description')
            ->filter(function ($value) {
                return trim((string) $value) !== '';
            })
            ->map(function ($value) {
                return mb_strtolower(trim((string) $value));
            })
            ->unique()
            ->values();

        $matchedProductsByDescription = collect();
        if ($descriptionKeys->isNotEmpty()) {
            $matchedProductsByDescription = \App\Models\ProductService::where('created_by', $creatorId)
                ->whereIn(DB::raw('LOWER(name)'), $descriptionKeys->all())
                ->with(['category', 'brand', 'subBrand'])
                ->get()
                ->keyBy(function ($product) {
                    return mb_strtolower(trim((string) $product->name));
                });
        }

        $partNos = $itemCollection->pluck('part_no')->filter()->unique()->values();
        $subProductsByPartNo = collect();
        $customFieldsByCategory = [];
        $customFieldValuesByRecord = collect();

        if ($partNos->isNotEmpty()) {
            $subProductsByPartNo = \App\Models\SubProduct::where('created_by', $creatorId)
                ->whereIn('chassis_no', $partNos->all())
                ->with('productService:id,category_id')
                ->orderByDesc('id')
                ->get()
                ->unique('product_no')
                ->keyBy('product_no');

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

        $pageItemIds = $itemCollection->pluck('id')->all();
        $assignedGrnItemsByAsnItem = GrnItem::whereIn('asn_item_id', $pageItemIds)
            ->with('grn:id,grn_no')
            ->get()
            ->groupBy('asn_item_id');

        $itemCollection->transform(function ($item) use ($matchedProductsByDescription, $subProductsByPartNo, $customFieldValuesByRecord, $customFieldsByCategory, $assignedGrnItemsByAsnItem) {
            $description = trim((string) ($item->description ?? ''));
            $descriptionKey = $description === '' ? null : mb_strtolower($description);
            $item->matchedProduct = $descriptionKey ? ($matchedProductsByDescription->get($descriptionKey) ?? null) : null;

            $subProduct = !empty($item->part_no) ? ($subProductsByPartNo->get($item->part_no) ?? null) : null;
            $item->matchedSubProduct = $subProduct;
            $categoryId = optional(optional($subProduct)->productService)->category_id;
            $item->customFields = $categoryId && isset($customFieldsByCategory[$categoryId]) ? $customFieldsByCategory[$categoryId] : collect();
            $item->customFieldValues = $subProduct ? ($customFieldValuesByRecord->get($subProduct->id) ?? collect()) : collect();

            $grnItems = $assignedGrnItemsByAsnItem->get($item->id, collect());
            $item->assignedGrnNumbers = $grnItems
                ->map(function ($grnItem) {
                    return $grnItem->grn ? 'GRN' . str_pad($grnItem->grn->grn_no, 5, '0', STR_PAD_LEFT) : null;
                })
                ->filter()
                ->values()
                ->all();
            $item->isAssigned = !empty($item->assignedGrnNumbers);

            return $item;
        });

        return view('asn.grn', compact('asn', 'asnItems', 'allBoxNos', 'allItemCount', 'isLargeItemSet'));
    }

    public function grnStore(\Illuminate\Http\Request $request, $asnId)
    {
        if (!\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
        $asn = \App\Models\Asn::with('items')->where('created_by', \Auth::user()->creatorId())->findOrFail($asnId);
        
        // Prevent GRN store if status is 'open'
        if ($asn->status === 'open') {
            return redirect()->back()->with('error', __('Cannot save GRN. ASN status must not be "Open". Please fill Dec No and Dec Date and update the ASN first.'));
        }
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:asn_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'selected_box_no' => 'nullable|string|max:255',
        ]);

        $proItemsToUpdate = [];
        $selectedBoxNo = trim((string) ($data['selected_box_no'] ?? ''));

        try {
            \DB::beginTransaction();

            if ($selectedBoxNo !== '') {
                // Auto-fill selected box lines across all pages: received_qty = qty.
                $boxItems = AsnItem::where('asn_id', $asn->id)
                    ->whereNull('split_from_asn_item_id')
                    ->where('box_no', $selectedBoxNo)
                    ->get();

                foreach ($boxItems as $item) {
                    $oldReceived = (float) $item->received_qty;
                    $newReceived = (float) $item->qty;
                    $delta = $newReceived - $oldReceived;
                    if (abs($delta) <= 0.0000001) {
                        continue;
                    }

                    $item->received_qty = $newReceived;
                    $item->save();

                    if (!empty($item->part_no)) {
                        $partNo = $item->part_no;
                        if (!empty($item->our_pro_id)) {
                            $key = 'pro_id_' . $item->our_pro_id . '_' . $partNo;
                        } elseif (!empty($item->our_pro_no)) {
                            $proNoDigits = preg_replace('/[^0-9]/', '', $item->our_pro_no);
                            $key = 'pro_no_' . $proNoDigits . '_' . $partNo;
                        } else {
                            $key = null;
                        }
                        if ($key !== null) {
                            if (!isset($proItemsToUpdate[$key])) {
                                $proItemsToUpdate[$key] = [
                                    'pro_id' => $item->our_pro_id ?? null,
                                    'pro_no' => !empty($item->our_pro_no) ? preg_replace('/[^0-9]/', '', $item->our_pro_no) : null,
                                    'part_no' => $partNo,
                                    'our_pro_no' => $item->our_pro_no,
                                    'delta' => 0.0,
                                ];
                            }
                            $proItemsToUpdate[$key]['delta'] += $delta;
                        }
                    }
                }
            }

            // Update ASN items with received quantities
            foreach ($asn->items as $item) {
                if (!isset($data['items'][$item->id])) {
                    continue;
                }
                $oldReceived = (float) $item->received_qty;
                $newReceived = (float) $data['items'][$item->id]['received_qty'];
                $delta = $newReceived - $oldReceived;
                $item->received_qty = $newReceived;
                // discrepancy, total_price/weight will be recalculated by model mutators
                $item->save();

                // Accumulate GRN delta per PRO line for supplied_qty (additive, not ASN sum)
                if (!empty($item->part_no) && abs($delta) > 0.0000001) {
                    $partNo = $item->part_no;
                    if (!empty($item->our_pro_id)) {
                        $key = 'pro_id_' . $item->our_pro_id . '_' . $partNo;
                    } elseif (!empty($item->our_pro_no)) {
                        $proNoDigits = preg_replace('/[^0-9]/', '', $item->our_pro_no);
                        $key = 'pro_no_' . $proNoDigits . '_' . $partNo;
                    } else {
                        $key = null;
                    }
                    if ($key !== null) {
                        if (!isset($proItemsToUpdate[$key])) {
                            $proItemsToUpdate[$key] = [
                                'pro_id' => $item->our_pro_id ?? null,
                                'pro_no' => !empty($item->our_pro_no) ? preg_replace('/[^0-9]/', '', $item->our_pro_no) : null,
                                'part_no' => $partNo,
                                'our_pro_no' => $item->our_pro_no,
                                'delta' => 0.0,
                            ];
                        }
                        $proItemsToUpdate[$key]['delta'] += $delta;
                    }
                }
            }

            // Apply deltas to PRO supplied_qty
            foreach ($proItemsToUpdate as $key => $proInfo) {
                $deltaApply = (float) ($proInfo['delta'] ?? 0);
                if (abs($deltaApply) <= 0.0000001) {
                    continue;
                }
                $pro = null;
                if (!empty($proInfo['pro_id'])) {
                    $pro = \App\Models\Pro::where('created_by', \Auth::user()->creatorId())
                        ->where('id', $proInfo['pro_id'])
                        ->with('items')
                        ->first();
                }

                // Normalize PRO numbers to handle '#PRO00001', '00001', '1', etc.
                $numericProNo = (int)($proInfo['pro_no'] ?? 0); // turns '00001' into 1
                $formattedDigits = null;
                if (!empty($proInfo['our_pro_no'])) {
                    $formattedDigits = (int)preg_replace('/[^0-9]/', '', $proInfo['our_pro_no']);
                }

                // Find the PRO by number (numeric match first)
                if (!$pro) {
                    $pro = \App\Models\Pro::where('created_by', \Auth::user()->creatorId())
                        ->where(function($q) use ($numericProNo) {
                            $q->where('pro_no', $numericProNo)
                              ->orWhere('pro_no', (string)$numericProNo);
                        })
                        ->with('items')
                        ->first();
                }
                
                // Try with formatted digits fallback
                if (!$pro && $formattedDigits) {
                    $pro = \App\Models\Pro::where('created_by', \Auth::user()->creatorId())
                        ->where(function($q) use ($formattedDigits) {
                            $q->where('pro_no', $formattedDigits)
                              ->orWhere('pro_no', (string)$formattedDigits);
                        })
                        ->with('items')
                        ->first();
                }
                
                // Final fallback: try exact stored formatted string
                if (!$pro && !empty($proInfo['our_pro_no'])) {
                    $pro = \App\Models\Pro::where('created_by', \Auth::user()->creatorId())
                        ->where('pro_no', $proInfo['our_pro_no'])
                        ->with('items')
                        ->first();
                }
                
                if (!$pro) {
                    \Log::warning('PRO not found for GRN update', [
                        'pro_no_numeric' => $proInfo['pro_no'],
                        'pro_no_formatted' => $proInfo['our_pro_no'] ?? null,
                        'part_no' => $proInfo['part_no'],
                        'user_id' => \Auth::user()->creatorId()
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
                        'available_part_nos' => $pro->items->pluck('part_no')->toArray()
                    ]);
                    continue;
                }

                $newSupplied = (float) $proItem->supplied_qty + $deltaApply;
                $proItem->supplied_qty = max(0, min((float) $proItem->order_qty, $newSupplied));
                $proItem->remaining_qty = max(0, (float) $proItem->order_qty - (float) $proItem->supplied_qty);
                $proItem->save();
                
                \Log::info('PRO item updated', [
                    'pro_item_id' => $proItem->id,
                    'part_no' => $proItem->part_no,
                    'delta_supplied_qty' => $deltaApply,
                    'supplied_qty' => $proItem->supplied_qty,
                    'remaining_qty' => $proItem->remaining_qty,
                ]);

                // Update PRO header status after item updates
                $pro->updateStatusBasedOnItems();
            }

            // Update ASN header status based on received quantities
            $asn->updateStatusBasedOnItems();

            \DB::commit();
            return redirect()->route('grn.index')->with('success', __('GRN saved successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GRN save failed', [
                'error' => $e->getMessage(),
                'asn_id' => $asnId,
                'user_id' => \Auth::user()->creatorId()
            ]);
            return redirect()->back()->with('error', __('GRN save failed: ') . $e->getMessage());
        }
    }


    /**
     * Create GRN from selected ASN items
     */
    public function createGrn(Request $request, $asnId)
    {
        if (!\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $asn = Asn::with('items')->where('created_by', \Auth::user()->creatorId())->findOrFail($asnId);

        $validated = $request->validate([
            'selected_items' => 'required|array|min:1',
            'selected_items.*' => 'required|exists:asn_items,id',
            'assigned_user_id' => 'required|exists:users,id',
            'grn_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get selected ASN items (root lines only)
            $selectedAsnItems = AsnItem::whereIn('id', $validated['selected_items'])
                ->where('asn_id', $asn->id)
                ->whereNull('split_from_asn_item_id')
                ->get();

            if ($selectedAsnItems->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', __('No valid items selected.'));
            }

            // Check if any selected items are already assigned to GRNs
            $alreadyAssignedItems = [];
            foreach ($selectedAsnItems as $asnItem) {
                if ($asnItem->isAssignedToGrn()) {
                    $grnItems = $asnItem->grnItems()->with('grn')->get();
                    $grnNumbers = $grnItems->map(function($grnItem) {
                        return $grnItem->grn ? 'GRN' . str_pad($grnItem->grn->grn_no, 5, '0', STR_PAD_LEFT) : null;
                    })->filter()->toArray();
                    $alreadyAssignedItems[] = [
                        'part_no' => $asnItem->part_no ?? __('N/A'),
                        'grn_numbers' => $grnNumbers
                    ];
                }
            }

            if (!empty($alreadyAssignedItems)) {
                DB::rollBack();
                $errorMessage = __('Cannot assign items that are already assigned to GRN(s):') . "\n";
                foreach ($alreadyAssignedItems as $item) {
                    $errorMessage .= "- " . $item['part_no'] . " (" . __('Already assigned to') . ": " . implode(', ', $item['grn_numbers']) . ")\n";
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            // Generate GRN number
            $grnNumber = $this->grnNumber();

            // Create GRN
            $grn = new Grn();
            $grn->grn_no = $grnNumber;
            $grn->asn_id = $asn->id;
            $grn->supplier_id = $asn->supplier_id;
            $grn->supplier_name = $asn->supplier_name;
            $grn->grn_date = $validated['grn_date'];
            $grn->status = 'draft';
            $grn->notes = $validated['notes'] ?? null;
            $grn->created_by = \Auth::user()->creatorId();
            $grn->assigned_to = $validated['assigned_user_id'];
            $grn->save();

            // Create GRN items from selected ASN items
            foreach ($selectedAsnItems as $asnItem) {
                $grnItem = new GrnItem();
                $grnItem->grn_id = $grn->id;
                $grnItem->asn_item_id = $asnItem->id;
                $grnItem->part_no = $asnItem->part_no;
                $grnItem->description = $asnItem->description;
                $grnItem->qty = $asnItem->qty;
                $grnItem->received_qty = $asnItem->received_qty ?? 0;
                $grnItem->unit_price = $asnItem->unit_price;
                // discrepancy and total_price will be auto-calculated by model
                $grnItem->save();
            }

            DB::commit();

            \Log::info('GRN created from ASN', [
                'grn_id' => $grn->id,
                'grn_no' => $grnNumber,
                'asn_id' => $asn->id,
                'assigned_to' => $grn->assigned_to,
                'items_count' => $selectedAsnItems->count()
            ]);

            // Format GRN number (similar to ASN format)
            $formattedGrnNo = 'GRN' . str_pad($grnNumber, 5, '0', STR_PAD_LEFT);
            return redirect()->route('asn.index')->with('success', __('GRN created successfully. GRN Number: ') . $formattedGrnNo);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('GRN creation failed', [
                'error' => $e->getMessage(),
                'asn_id' => $asnId,
                'user_id' => \Auth::user()->creatorId()
            ]);
            return redirect()->back()->with('error', __('GRN creation failed: ') . $e->getMessage());
        }
    }

    /**
     * Generate GRN number
     */
    private function grnNumber()
    {
        $latest = Grn::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest || !is_numeric($latest->grn_no)) {
            return 1;
        }
        return (int)$latest->grn_no + 1;
    }

    /**
     * Print barcode for an ASN item
     */
    public function printItemBarcode($asnId, $itemId)
    {
        if (!\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $asn = \App\Models\Asn::where('created_by', \Auth::user()->creatorId())->findOrFail($asnId);
        $item = \App\Models\AsnItem::where('asn_id', $asnId)->where('id', $itemId)->firstOrFail();

        if (empty($item->part_no)) {
            return redirect()->back()->with('error', __('Part number is required to print barcode.'));
        }

        // Get quantity - use qty (full quantity) for printing
        $quantity = (int) $item->qty ?? 1;
        
        // Get currency symbol
        $currencySymbol = \Auth::user()->currencySymbol();

        // Prepare product name from description
        $productName = $item->description ?? $item->part_no;

        // Find sub-product by product_no (part_no)
        $subproduct = SubProduct::where('chassis_no', $item->part_no)
            ->where('created_by', \Auth::user()->creatorId())
            ->with('productService.category', 'productService.brand')
            ->latest()
            ->first();

        // Initialize custom fields array
        $customFields = [];
        $customFieldValues = [];
        $finalPrice = 0; // Price with tax

        if ($subproduct && $subproduct->productService) {
            $categoryId = $subproduct->productService->category_id;
            $productService = $subproduct->productService;

            // Get custom fields for this category
            $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
                ->where('module', 'sub-product')
                ->forCategory($categoryId)
                ->get();

            // Get custom field values for this subproduct
            $customFieldValues = CustomFieldValue::where('record_id', $subproduct->id)
                ->get()
                ->keyBy('field_id');

            // Calculate tax from parent product and add to sale price
            $salePrice = $subproduct->sale_price ?? $productService->sale_price ?? 0;
            
            if ($salePrice > 0 && !empty($productService->tax_id)) {
                // Get tax data
                $taxData = \App\Models\Utility::getTaxData();
                $totalTaxRate = 0;
                
                // Parse tax IDs (can be comma-separated)
                $taxArr = explode(',', (string) $productService->tax_id);
                
                // Sum all tax rates
                foreach ($taxArr as $taxId) {
                    $taxId = trim($taxId);
                    if (!empty($taxId) && isset($taxData[$taxId]['rate'])) {
                        $totalTaxRate += (float) $taxData[$taxId]['rate'];
                    }
                }
                
                // Calculate tax amount and add to sale price
                if ($totalTaxRate > 0) {
                    $taxAmount = $salePrice * ($totalTaxRate / 100);
                    $finalPrice = $salePrice + $taxAmount;
                } else {
                    $finalPrice = $salePrice;
                }
            } else {
                $finalPrice = $salePrice;
            }
            
            // Add finalPrice to subproduct object for use in view
            if (is_object($subproduct)) {
                $subproduct->final_price_with_tax = $finalPrice;
            }
        }

        // Create a mock subproduct object for barcode generation if not found
        if (!$subproduct) {
            $subproduct = (object)[
                'product_no' => $item->part_no,
                'final_price_with_tax' => 0,
            ];
        }

        return view('asn.barcode', compact(
            'subproduct',
            'quantity',
            'productName',
            'currencySymbol',
            'item',
            'asn',
            'customFields',
            'customFieldValues'
        ));
    }

    /**
     * Export ASNs to Excel (bulk or single)
     */
    public function export(Request $request, $asnId = null)
    {
        if (!\Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            // If asnId is provided, export only that ASN
            if ($asnId) {
                $asn = Asn::where('created_by', \Auth::user()->creatorId())->findOrFail($asnId);
                $asnNumberFormatted = \Auth::user()->asnNumberFormat($asn->asn_no);
                $name = 'asn_' . preg_replace('/[^a-zA-Z0-9]/', '_', $asnNumberFormatted) . '_' . date('Y-m-d_H-i-s');
                $data = Excel::download(new \App\Exports\AsnExport(\Auth::user()->creatorId(), [], $asnId), $name . '.xlsx');
            } else {
                // Bulk export with filters
                $filters = [
                    'supplier_id' => $request->get('supplier_id'),
                    'asn_date' => $request->get('asn_date'),
                    'asn_no' => $request->get('asn_no'),
                ];

                // Remove empty filters
                $filters = array_filter($filters);

                $name = 'asn_' . date('Y-m-d_H-i-s');
                $data = Excel::download(new \App\Exports\AsnExport(\Auth::user()->creatorId(), $filters), $name . '.xlsx');
            }

            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('ASN export failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId(),
                'asn_id' => $asnId
            ]);

            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }

    /**
     * Convert ASN to Bill
     */
    public function convertToBill(Request $request, $id)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'tax_id' => 'required|exists:taxes,id',
        ]);

        try {
            $asnId = $id;
            $user = \Auth::user();
            $creatorId = $user->creatorId();

            $asn = Asn::with(['items', 'supplier'])
                ->where('created_by', $creatorId)
                ->findOrFail($asnId);

            $proToAdvanceSaleOrder = Pro::where('created_by', $creatorId)
                ->whereIn('id', $asn->items->pluck('our_pro_id')->filter()->unique()->values())
                ->pluck('advance_sale_order_id', 'id')
                ->toArray();

            // Check if ASN already has a bill
            if ($asn->bill_id) {
                return redirect()->route('bill.show', Crypt::encrypt($asn->bill_id))->with('info', __('This ASN has already been converted to a bill.'));
            }

            // Check if any items have already been converted to inventory
            $hasInventoryItems = false;
            foreach ($asn->items as $item) {
                if ($item->sub_product_id) {
                    $subProduct = SubProduct::find($item->sub_product_id);
                    if ($subProduct && $subProduct->isInventory()) {
                        $hasInventoryItems = true;
                        break;
                    }
                }
            }
            if ($hasInventoryItems) {
                return redirect()->back()->with('error', __('Cannot convert to Bill. Some items have already been converted to Inventory.'));
            }

            // Check if ASN is fully completed (status must be 'fully_received' or 'manually_received')
            if (!in_array($asn->status, ['fully_received', 'manually_received'], true)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('Cannot convert ASN to Bill. The ASN must be fully or manually received before conversion. Current status: :status', ['status' => $asn->status ?? 'created']));
            }

            // Use warehouse from ASN
            $warehouseId = $asn->warehouse_id;
            if (!$warehouseId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('ASN has no warehouse set. Please set warehouse on the ASN before converting to bill.'));
            }
            $billDate = $request->input('bill_date');
            $dueDate = $request->input('due_date');

            // Verify warehouse belongs to company
            $warehouse = \App\Models\warehouse::where('id', $warehouseId)
                ->where('created_by', $creatorId)
                ->firstOrFail();

            try {
                DB::beginTransaction();
                $autoSaleOrderRows = [];

                // Generate bill number
                $billNumber = $this->billNumber();

                // Determine supplier
                $supplierId = null;
                if ($asn->supplier_id) {
                    $supplierId = $asn->supplier_id;
                } elseif ($asn->supplier) {
                    $supplierId = $asn->supplier->id;
                } elseif ($asn->supplier_name) {
                    $supplier = \App\Models\Vender::where('created_by', $creatorId)
                        ->where('name', $asn->supplier_name)
                        ->first();
                    if ($supplier) {
                        $supplierId = $supplier->id;
                    }
                }

                // Get currency from ASN
                $currencyId = $asn->currency_id;
                $exchangeRate = $asn->exchange_rate ?? 1;

                // Create bill using ASN data
                $bill = new \App\Models\Bill();
                $bill->bill_id = (string) $billNumber;
                $bill->vender_id = $supplierId;
                $bill->bill_date = $billDate;
                $bill->due_date = $dueDate;
                $bill->status = 0; // Draft
                $bill->type = 'Bill';
                $bill->user_type = 'vendor';
                $bill->warehouse_id = $warehouseId; // Use selected warehouse
                $bill->category_id = 0;
                $bill->created_by = $creatorId;
                $bill->salesman_id = $creatorId;
                
                // Get tax from request or use default tax from company
                $taxId = $request->input('tax_id');
                if ($taxId) {
                    // Verify tax belongs to company
                    $tax = \App\Models\Tax::where('id', $taxId)
                        ->where('created_by', $creatorId)
                        ->first();
                    $bill->tax_id = $tax ? (string)$tax->id : '';
                } else {
                    // Use default tax if none selected
                    $defaultTax = \App\Models\Tax::where('created_by', $creatorId)->first();
                    $bill->tax_id = $defaultTax ? (string)$defaultTax->id : '';
                }
                
                $bill->currency_id = $currencyId;
                $bill->exchange_rate = $exchangeRate;
                
                $bill->save();

                // Create bill status change record
                $statusChange = new \App\Models\BillStatusChange();
                $statusChange->bill_id = $bill->id;
                $statusChange->status = 0;
                $statusChange->payment_status = -1;
                $statusChange->changed_at = now();
                $statusChange->save();

                // Process ASN items and create bill products (new sub-product per line; CF from ASN + item_master)
                foreach ($asn->items as $asnItem) {
                    if ($asnItem->received_qty <= 0) {
                        continue; // Skip items with zero received quantity
                    }

                    $partNo = trim((string) ($asnItem->part_no ?? ''));
                    if ($partNo === '') {
                        \Log::warning('ASN convert to bill: empty part_no', ['asn_item_id' => $asnItem->id]);
                        continue;
                    }

                    $itemMasterSub = $this->findItemMasterSubProductByPartNo($partNo, $creatorId);

                    $fallbackSub = null;
                    if (!$itemMasterSub || !$itemMasterSub->productService) {
                        $fallbackSub = SubProduct::where('created_by', $creatorId)
                            ->where('chassis_no', $partNo)
                            ->with(['productService.category', 'customFieldValues'])
                            ->latest()
                            ->first();
                    }

                    $sourceSub = $itemMasterSub ?: $fallbackSub;

                    $product = ($itemMasterSub && $itemMasterSub->productService)
                        ? $itemMasterSub->productService
                        : ($fallbackSub && $fallbackSub->productService ? $fallbackSub->productService : null);

                    if (!$product) {
                        \Log::warning('SubProduct / product not found for ASN item', [
                            'part_no' => $partNo,
                            'asn_item_id' => $asnItem->id,
                        ]);
                        continue;
                    }

                    $category = $product->category;

                    if (!$category) {
                        \Log::warning('Category not found for product', [
                            'product_id' => $product->id,
                            'part_no' => $partNo,
                        ]);
                        continue;
                    }

                    // Get custom fields for this category to match ASN item fields
                    $customFields = CustomField::where('created_by', $creatorId)
                        ->where('module', 'sub-product')
                        ->forCategory($category->id)
                        ->get();
                    
                    // Create a mapping of normalized custom field names to field IDs
                    $customFieldMap = [];
                    foreach ($customFields as $customField) {
                        $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $customField->name)));
                        $customFieldMap[$normalizedName] = $customField->id;
                    }
                    
                    // Map ASN item fields to potential custom field names
                    // Normalize ASN field names and match them to custom fields
                    $asnFieldMapping = [
                        'box_no' => ['boxno', 'boxnumber', 'box_number', 'box'],
                        'supplier_po_no' => ['supplierpono', 'supplierponumber', 'supplier_po_no', 'pono', 'ponumber', 'po'],
                        'order_ref' => ['orderref', 'orderreference', 'order_ref', 'ref', 'orderreference'],
                        'unit_weight' => ['weightkg', 'weight_kg', 'unitweight', 'unit_weight', 'weight', 'kg'],
                        'container_no' => ['containerno', 'containernumber', 'container_no', 'container'],
                        'dec_no' => ['decno', 'decnumber', 'dec_no', 'dec'],
                        'dec_date' => ['decdate', 'dec_date', 'decdate'],
                        'origin' => ['origin', 'countryoforigin', 'country_of_origin', 'countryorigin'],
                        'hs_code' => ['hscode', 'hs_code', 'harmonizedcode', 'harmonized_code'],
                    ];
                    
                    // Prepare ASN field values to copy
                    $asnFieldValues = [];
                    foreach ($asnFieldMapping as $asnField => $possibleNames) {
                        $value = $asnItem->$asnField ?? null;
                        
                        // For unit_weight, allow 0 values; for other fields, skip empty values
                        $shouldProcess = false;
                        if ($asnField === 'unit_weight') {
                            // Allow 0 values for unit_weight
                            $shouldProcess = ($value !== null && $value !== '');
                        } else {
                            // Skip empty values for other fields
                            $shouldProcess = !empty($value);
                        }
                        
                        if ($shouldProcess) {
                            // Format date fields
                            if ($asnField === 'dec_date' && $value) {
                                try {
                                    $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                                } catch (\Exception $e) {
                                    // Keep original value if parsing fails
                                }
                            }
                            
                            // Format numeric fields (unit_weight) - format as decimal with 2 decimal places
                            if ($asnField === 'unit_weight' && ($value !== null && $value !== '')) {
                                $value = number_format((float)$value, 2, '.', '');
                            }
                            
                            // Try to match to custom field
                            foreach ($possibleNames as $possibleName) {
                                $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $possibleName)));
                                if (isset($customFieldMap[$normalizedName])) {
                                    $asnFieldValues[$customFieldMap[$normalizedName]] = (string)$value;
                                    break; // Found match, move to next field
                                }
                            }
                        }
                    }

                    // Also map ASN header-level BOE Number to a sub-product custom field (if configured)
                    if (!empty($asn->boe_number)) {
                        $boePossibleNames = ['boenumber', 'boe_number', 'boeno', 'boe'];
                        foreach ($boePossibleNames as $possibleName) {
                            $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $possibleName)));
                            if (isset($customFieldMap[$normalizedName])) {
                                $asnFieldValues[$customFieldMap[$normalizedName]] = (string)$asn->boe_number;
                                break;
                            }
                        }
                    }

                    // Calculate prices - use unit_price from ASN item
                    $unitPriceOriginal = $asnItem->unit_price ?? 0;
                    $quantity = $asnItem->received_qty;

                    // Calculate prices in AED and foreign currency
                    if ($bill->currency_id && $bill->exchange_rate > 0) {
                        $unitPriceAED = $unitPriceOriginal * $bill->exchange_rate;
                        $exchangePrice = $unitPriceOriginal;
                    } else {
                        $unitPriceAED = $unitPriceOriginal;
                        $exchangePrice = $unitPriceOriginal;
                    }

                    // New sub-product row for this bill line (do not reuse existing stock rows)
                    $subProduct = new SubProduct();
                    $subProduct->chassis_no = $partNo;
                    $subProduct->product_id = $product->id;
                    $subProduct->quantity = $quantity;
                    $subProduct->sale_price = (float) (($itemMasterSub ? $itemMasterSub->sale_price : null) ?? ($fallbackSub ? $fallbackSub->sale_price : null) ?? $product->sale_price ?? 0);
                    $subProduct->purchase_price = $unitPriceAED;
                    $subProduct->flag = SubProduct::FLAG_PURCHASED;
                    $subProduct->booked = 0;
                    $subProduct->bill_id = $bill->id;
                    $subProduct->asn_id = $asn->id;
                    $subProduct->warehouse_id = $warehouseId;
                    $subProduct->created_by = $creatorId;
                    if ($sourceSub) {
                        if ($sourceSub->SP_sku !== null && $sourceSub->SP_sku !== '') {
                            $subProduct->SP_sku = $sourceSub->SP_sku;
                        }
                        if ($sourceSub->price_multiplier !== null) {
                            $subProduct->price_multiplier = $sourceSub->price_multiplier;
                        }
                        if (!empty($sourceSub->price_rule_id)) {
                            $subProduct->price_rule_id = $sourceSub->price_rule_id;
                        }
                        if ($sourceSub->note !== null && $sourceSub->note !== '') {
                            $subProduct->note = $sourceSub->note;
                        }
                    }
                    $subProduct->save();
                    MasterlistLeadger::addFree($subProduct->productService->id,$warehouseId,$quantity,"ASN",$asnId,$creatorId);

                    // Custom fields: fill gaps from matched stock row; ASN values override below
                    if ($sourceSub && $sourceSub->customFieldValues && $sourceSub->customFieldValues->isNotEmpty()) {
                        foreach ($sourceSub->customFieldValues as $customFieldValue) {
                            if (!isset($asnFieldValues[$customFieldValue->field_id])) {
                                CustomFieldValue::updateOrCreate(
                                    [
                                        'record_id' => $subProduct->id,
                                        'field_id' => $customFieldValue->field_id,
                                    ],
                                    [
                                        'value' => $customFieldValue->value,
                                    ]
                                );
                            }
                        }
                    }

                    foreach ($asnFieldValues as $fieldId => $value) {
                        if ($value !== null && $value !== '') {
                            CustomFieldValue::updateOrCreate(
                                [
                                    'record_id' => $subProduct->id,
                                    'field_id' => $fieldId,
                                ],
                                [
                                    'value' => $value,
                                ]
                            );
                        }
                    }

                    // Create bill product line linked to the same sub-product.
                    $billProduct = new \App\Models\BillProduct();
                    $billProduct->bill_id = $bill->id;
                    $billProduct->product_id = $product->id;
                    $billProduct->sub_product_id = $subProduct->id;
                    $billProduct->quantity = $quantity;
                    $billProduct->tax = $bill->tax_id;
                    $billProduct->discount = 0;
                    $billProduct->price = $unitPriceAED;
                    $billProduct->exchange_price = $exchangePrice;
                    $billProduct->exchange_discount = 0;
                    $billProduct->description = $asnItem->description ?? '';
                    $billProduct->save();

                    // Full convert to bill: converted qty = received qty
                    $asnItem->converted_qty = $asnItem->received_qty;
                    $asnItem->sub_product_id = $subProduct->id;
                    $asnItem->save();

                    $advanceSaleOrderId = (int) ($proToAdvanceSaleOrder[(int) ($asnItem->our_pro_id ?? 0)] ?? 0);
                    if ($advanceSaleOrderId > 0) {
                        $autoSaleOrderRows[] = [
                            'advance_sale_order_id' => $advanceSaleOrderId,
                            'part_no' => $partNo,
                            'description' => $asnItem->description,
                            'qty' => (float) $quantity,
                            'unit_price' => (float) $unitPriceOriginal,
                            'product_id' => (int) $product->id,
                            'sub_product_id' => (int) $subProduct->id,
                        ];
                    }

                    // Record which item qty went to this bill
                    AsnItemBill::create([
                        'asn_item_id' => $asnItem->id,
                        'bill_id' => $bill->id,
                        'quantity' => $asnItem->received_qty,
                    ]);
                }

                // Link bill to ASN (asn_bills + legacy bill_id for first bill)
                AsnBill::create(['asn_id' => $asn->id, 'bill_id' => $bill->id]);
                if (!$asn->bill_id) {
                    $asn->bill_id = $bill->id;
                }
                $asn->save();

                $this->createAutoSaleOrdersFromAdvanceSo($creatorId, $asn, $autoSaleOrderRows);

                DB::commit();

                \Log::info('ASN converted to bill', [
                    'asn_id' => $asn->id,
                    'bill_id' => $bill->id,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $user->id
                ]);

                return redirect()->route('bill.show', Crypt::encrypt($bill->id))->with('success', __('ASN successfully converted to bill.'));
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('ASN convert to bill failed', [
                    'error' => $e->getMessage(),
                    'asn_id' => $asnId,
                    'user_id' => $creatorId,
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('Failed to convert ASN to bill: ') . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('ASN convert to bill validation failed', [
                'error' => $e->getMessage(),
                'asn_id' => $id,
                'user_id' => \Auth::user()->id
            ]);
            return redirect()->back()->with('error', __('Failed to convert ASN to bill: ') . $e->getMessage());
        }
    }

    function billNumber()
    {
        $latest = \App\Models\Bill::where('created_by', '=', \Auth::user()->creatorId())->where('bill_id', 'not like', '%#EXP%')->withTrashed()->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    /**
     * Convert ASN items to Inventory
     */
    public function convertToInventory(Request $request, $id)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        @ini_set('max_execution_time', '600');
        set_time_limit(600);

        try {
            $asnId = $id;
            $user = \Auth::user();
            $creatorId = $user->creatorId();

            $asn = Asn::with(['supplier', 'currency'])
                ->where('created_by', $creatorId)
                ->findOrFail($asnId);

            // Convert to inventory only when ASN is fully or manually received
            if (!in_array($asn->status, ['fully_received', 'manually_received'], true)) {
                return redirect()->back()->with('error', __('Cannot convert to Inventory. The ASN must be fully or manually received first. Current status: :status.', ['status' => $asn->status_label ?? $asn->status ?? 'created']));
            }

            $selectedItemIds = $request->input('selected_items');
            if (is_string($selectedItemIds)) {
                $selectedItemIds = array_filter(array_map('trim', explode(',', $selectedItemIds)));
            }
            $selectedItemIds = is_array($selectedItemIds) ? array_values(array_filter($selectedItemIds)) : null;

            $eligibleQuery = AsnItem::query()
                ->where('asn_id', $asn->id)
                ->where('received_qty', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('converted_qty')->orWhere('converted_qty', '<=', 0);
                })
                ->whereNull('inventory_converted_at')
                ->whereNull('sub_product_id');

            if (!empty($selectedItemIds)) {
                $eligibleQuery->whereIn('id', $selectedItemIds);
            }

            if (!$eligibleQuery->exists()) {
                return redirect()->route('asn.show', $asn->id)->with('error', __('No ASN items left to convert to inventory. All eligible lines may already be converted.'));
            }

            // Find Inventory and Goods Received Clearing accounts
            $inventoryAccount = \App\Models\ChartOfAccount::where('created_by', $creatorId)
                ->whereRaw('LOWER(name) = ?', ['inventory'])
                ->first();

            $goodsReceivedClearingAccount = \App\Models\ChartOfAccount::where('created_by', $creatorId)
                ->whereRaw('LOWER(name) = ?', ['Goods Received Clearing account'])
                ->first();

            if (!$inventoryAccount) {
                return redirect()->back()->with('error', __('Inventory account not found. Please create it first.'));
            }

            if (!$goodsReceivedClearingAccount) {
                return redirect()->back()->with('error', __('Goods Received Clearing account not found. Please create it first.'));
            }

            try {
                DB::beginTransaction();

                $asnItems = $eligibleQuery->orderBy('id')->get();
                $proToAdvanceSaleOrder = Pro::where('created_by', $creatorId)
                    ->whereIn('id', $asnItems->pluck('our_pro_id')->filter()->unique()->values())
                    ->pluck('advance_sale_order_id', 'id')
                    ->toArray();
                $prefetch = $this->prefetchConvertToInventoryLookups($asnItems, $creatorId);

                $latestVoucher = \App\Models\GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
                $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

                $asnNoFormatted = $user->asnNumberFormat($asn->asn_no);
                $sendDate = now()->format('Y-m-d');
                $now = now()->format('Y-m-d H:i:s');
                $warehouseId = $asn->warehouse_id;

                $productRuntime = [];
                $customFieldMapsByCategory = [];
                $masterlistQtyByProduct = [];
                $stockRows = [];
                $glRows = [];
                $cfvRows = [];
                $asnItemIdToSubProductId = [];
                $autoSaleOrderRows = [];

                $convertedCount = 0;

                foreach ($asnItems as $asnItem) {
                    $resolved = $this->resolveProductForConvertToInventoryLine($asnItem, $prefetch, $creatorId);
                    /** @var SubProduct|null $itemMasterSub */
                    $itemMasterSub = $resolved['item_master_sub'];
                    /** @var SubProduct|null $fallbackSub */
                    $fallbackSub = $resolved['fallback_sub'];
                    /** @var ProductService|null $resolvedProduct */
                    $resolvedProduct = $resolved['product'];
                    $sourceSub = $itemMasterSub ?: $fallbackSub;

                    if (!$resolvedProduct) {
                        \Log::warning('Product not found for ASN item when converting to inventory', [
                            'part_no' => $asnItem->part_no,
                            'description' => $asnItem->description,
                            'asn_item_id' => $asnItem->id,
                        ]);
                        continue;
                    }

                    $pid = (int) $resolvedProduct->id;
                    if (!isset($productRuntime[$pid])) {
                        $productRuntime[$pid] = ProductService::with('category')->find($pid);
                    }
                    $product = $productRuntime[$pid];
                    if (!$product) {
                        continue;
                    }

                    $category = $product->category;
                    if (!$category) {
                        \Log::warning('Category not found for product when converting to inventory', [
                            'product_id' => $product->id,
                            'part_no' => $asnItem->part_no,
                        ]);
                        continue;
                    }

                    $cfData = $this->getCustomFieldMapForConvertToInventory((int) $category->id, $creatorId, $customFieldMapsByCategory);
                    $customFieldMap = $cfData['map'];

                    $asnFieldMapping = [
                        'box_no' => ['boxno', 'boxnumber', 'box_number', 'box'],
                        'supplier_po_no' => ['supplierpono', 'supplierponumber', 'supplier_po_no', 'pono', 'ponumber', 'po'],
                        'order_ref' => ['orderref', 'orderreference', 'order_ref', 'ref', 'orderreference'],
                        'unit_weight' => ['weightkg', 'weight_kg', 'unitweight', 'unit_weight', 'weight', 'kg'],
                        'container_no' => ['containerno', 'containernumber', 'container_no', 'container'],
                        'dec_no' => ['decno', 'decnumber', 'dec_no', 'dec'],
                        'dec_date' => ['decdate', 'dec_date', 'decdate'],
                        'origin' => ['origin', 'countryoforigin', 'country_of_origin', 'countryorigin'],
                        'hs_code' => ['hscode', 'hs_code', 'harmonizedcode', 'harmonized_code'],
                    ];

                    $asnFieldValues = [];
                    foreach ($asnFieldMapping as $asnField => $possibleNames) {
                        $value = $asnItem->$asnField ?? null;

                        if ($asnField === 'unit_weight') {
                            $shouldProcess = ($value !== null && $value !== '');
                        } else {
                            $shouldProcess = !empty($value);
                        }

                        if ($shouldProcess) {
                            if ($asnField === 'dec_date' && $value) {
                                try {
                                    $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                                } catch (\Exception $e) {
                                }
                            }

                            if ($asnField === 'unit_weight' && ($value !== null && $value !== '')) {
                                $value = number_format((float) $value, 2, '.', '');
                            }

                            foreach ($possibleNames as $possibleName) {
                                $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $possibleName)));
                                if (isset($customFieldMap[$normalizedName])) {
                                    $asnFieldValues[$customFieldMap[$normalizedName]] = (string) $value;
                                    break;
                                }
                            }
                        }
                    }

                    if (!empty($asn->boe_number)) {
                        $boePossibleNames = ['boenumber', 'boe_number', 'boeno', 'boe'];
                        foreach ($boePossibleNames as $possibleName) {
                            $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '', $possibleName)));
                            if (isset($customFieldMap[$normalizedName])) {
                                $asnFieldValues[$customFieldMap[$normalizedName]] = (string) $asn->boe_number;
                                break;
                            }
                        }
                    }

                    $unitPriceOriginal = (float) ($asnItem->unit_price ?? 0);
                    $exchangeRate = 1.0;
                    if (!empty($asn->currency_id)) {
                        $exchangeRate = (float) ($asn->exchange_rate ?? 0);
                        if ($exchangeRate <= 0) {
                            $currency = $asn->currency ?: \App\Models\Currency::find($asn->currency_id);
                            $exchangeRate = (float) (($currency->exchange_rate ?? 1) ?: 1);
                        }
                    }
                    $unitPriceAED = !empty($asn->currency_id) ? ($unitPriceOriginal * $exchangeRate) : $unitPriceOriginal;
                    $amount = ((float) $asnItem->received_qty) * $unitPriceAED;

                    $categoryType = $category->type ?? null;
                    $costCalculationMethod = $product->category->cost_calculation_method ?? 'avg';

                    if ($categoryType === 'Qty product' && $costCalculationMethod === 'avg') {
                        $oldQuantity = $product->quantity ?? 0;
                        $oldAvgCost = ($product->avg_cost > 0) ? $product->avg_cost : $unitPriceAED;
                        $newQuantity = $asnItem->received_qty;
                        $newPricePerUnit = $unitPriceAED;
                        $oldTotalCost = $oldQuantity * $oldAvgCost;
                        $newItemTotalCost = $newQuantity * $newPricePerUnit;
                        $totalQuantity = $oldQuantity + $newQuantity;
                        $avgCost = $totalQuantity > 0
                            ? ($oldTotalCost + $newItemTotalCost) / $totalQuantity
                            : $newPricePerUnit;
                    } else {
                        $avgCost = $unitPriceAED;
                    }

                    $product->quantity = ($product->quantity ?? 0) + $asnItem->received_qty;
                    $product->avg_cost = $avgCost;
                    $product->save();

                    $partNo = trim((string) ($asnItem->part_no ?? ''));

                    $subProduct = new SubProduct();
                    $subProduct->chassis_no = $partNo !== '' ? $partNo : ($asnItem->part_no ?? null);
                    $subProduct->product_id = $product->id;
                    $subProduct->quantity = $asnItem->received_qty;
                    $subProduct->sale_price = (float) (($itemMasterSub ? $itemMasterSub->sale_price : null) ?? ($fallbackSub ? $fallbackSub->sale_price : null) ?? $product->sale_price ?? 0);
                    $subProduct->purchase_price = $unitPriceAED;
                    $subProduct->flag = SubProduct::FLAG_CONSIGNMENT;
                    $subProduct->booked = 0;
                    $subProduct->asn_id = $asn->id;
                    if ($warehouseId) {
                        $subProduct->warehouse_id = $warehouseId;
                    }
                    $subProduct->created_by = $creatorId;
                    if ($sourceSub) {
                        if ($sourceSub->SP_sku !== null && $sourceSub->SP_sku !== '') {
                            $subProduct->SP_sku = $sourceSub->SP_sku;
                        }
                        if ($sourceSub->price_multiplier !== null) {
                            $subProduct->price_multiplier = $sourceSub->price_multiplier;
                        }
                        if (!empty($sourceSub->price_rule_id)) {
                            $subProduct->price_rule_id = $sourceSub->price_rule_id;
                        }
                        if ($sourceSub->note !== null && $sourceSub->note !== '') {
                            $subProduct->note = $sourceSub->note;
                        }
                    }
                    $subProduct->save();

                    $masterlistQtyByProduct[$pid] = ($masterlistQtyByProduct[$pid] ?? 0) + (float) $asnItem->received_qty;

                    $cfvMerge = [];
                    if ($sourceSub && $sourceSub->customFieldValues && $sourceSub->customFieldValues->isNotEmpty()) {
                        foreach ($sourceSub->customFieldValues as $customFieldValue) {
                            if (!isset($asnFieldValues[$customFieldValue->field_id])) {
                                $cfvMerge[(int) $customFieldValue->field_id] = $customFieldValue->value;
                            }
                        }
                    }
                    foreach ($asnFieldValues as $fieldId => $value) {
                        if ($value !== null && $value !== '') {
                            $cfvMerge[(int) $fieldId] = $value;
                        }
                    }
                    foreach ($cfvMerge as $fieldId => $value) {
                        $cfvRows[] = [
                            'record_id' => $subProduct->id,
                            'field_id' => (int) $fieldId,
                            'value' => (string) $value,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $asnItemIdToSubProductId[(int) $asnItem->id] = (int) $subProduct->id;

                    $advanceSaleOrderId = (int) ($proToAdvanceSaleOrder[(int) ($asnItem->our_pro_id ?? 0)] ?? 0);
                    if ($advanceSaleOrderId > 0) {
                        $autoSaleOrderRows[] = [
                            'advance_sale_order_id' => $advanceSaleOrderId,
                            'part_no' => $partNo !== '' ? $partNo : ($asnItem->part_no ?? null),
                            'description' => $asnItem->description,
                            'qty' => (float) $asnItem->received_qty,
                            'unit_price' => (float) $unitPriceOriginal,
                            'product_id' => (int) $product->id,
                            'sub_product_id' => (int) $subProduct->id,
                        ];
                    }

                    $stockRows[] = [
                        'product_id' => $product->id,
                        'sub_product_id' => $subProduct->id,
                        'bill_id' => null,
                        'invoice_id' => null,
                        'pos_id' => null,
                        'qty_in' => $asnItem->received_qty,
                        'qty_out' => 0,
                        'avg_cost' => $avgCost,
                        'cost_price' => $unitPriceAED,
                        'activity' => 'ASN to Inventory',
                        'use_id' => $asn->supplier_id ?? null,
                        'item' => $subProduct->id,
                        'created_by' => $creatorId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $glRows[] = [
                        'vid' => $newVid,
                        'account' => $inventoryAccount->id,
                        'type' => $asnNoFormatted,
                        'ref_number' => $asnNoFormatted,
                        'debit' => $amount,
                        'credit' => 0,
                        'ref_id' => $asn->id,
                        'user_id' => 0,
                        'created_by' => $creatorId,
                        'send_date' => $sendDate,
                        'reference' => 'ASN Inventory',
                        'sub_product_id' => $subProduct->id,
                        'balance' => 0,
                        'deleted_qty' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $glRows[] = [
                        'vid' => $newVid,
                        'account' => $goodsReceivedClearingAccount->id,
                        'type' => $asnNoFormatted,
                        'ref_number' => $asnNoFormatted,
                        'debit' => 0,
                        'credit' => $amount,
                        'ref_id' => $asn->id,
                        'user_id' => 0,
                        'created_by' => $creatorId,
                        'send_date' => $sendDate,
                        'reference' => 'ASN Inventory',
                        'sub_product_id' => $subProduct->id,
                        'balance' => 0,
                        'deleted_qty' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $convertedCount++;
                }

                foreach ($masterlistQtyByProduct as $productId => $qtyAdd) {
                    if ($qtyAdd <= 0) {
                        continue;
                    }
                    MasterlistLeadger::addFree((int) $productId, $warehouseId, $qtyAdd, 'ASN', (int) $asnId, $creatorId);
                }

                foreach (array_chunk($stockRows, 400) as $chunk) {
                    StockMovement::insert($chunk);
                }

                foreach (array_chunk($glRows, 400) as $chunk) {
                    DB::table('general_ledger')->insert($chunk);
                }

                foreach (array_chunk($cfvRows, 500) as $chunk) {
                    DB::table('custom_field_values')->insert($chunk);
                }

                $this->batchUpdateAsnItemsAfterInventoryConvert($asnItemIdToSubProductId);
                $this->createAutoSaleOrdersFromAdvanceSo($creatorId, $asn, $autoSaleOrderRows);

                DB::commit();

                \Log::info('ASN converted to inventory', [
                    'asn_id' => $asn->id,
                    'user_id' => $user->id,
                    'items_converted' => $convertedCount,
                ]);

                if ($convertedCount <= 0) {
                    return redirect()->route('asn.show', $asn->id)->with('error', __('No ASN items were converted. Items may already be converted, converted to Bill, or have zero received quantity.'));
                }

                return redirect()->route('asn.show', $asn->id)->with('success', __(':count ASN item(s) successfully converted to inventory.', ['count' => $convertedCount]));
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('ASN convert to inventory failed', [
                    'error' => $e->getMessage(),
                    'asn_id' => $asnId,
                    'user_id' => $creatorId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect()->back()->with('error', __('Failed to convert ASN to inventory: ') . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('ASN convert to inventory validation failed', [
                'error' => $e->getMessage(),
                'asn_id' => $id,
                'user_id' => \Auth::user()->id,
            ]);
            return redirect()->back()->with('error', __('Failed to convert ASN to inventory: ') . $e->getMessage());
        }
    }

    /**
     * Reverse ASN -> Inventory conversion for a single ASN item.
     * Requirements:
     * - Item must have been converted to inventory (sub_product_id with flag = consignment).
     * - Sold qty (Invoice + POS for that sub_product) must equal converted_qty to bill.
     * - Remaining consignment qty (received_qty - converted_qty) will be reversed from stock and ledger.
     * - Sub-product flag set to CANCELLED if quantity becomes zero.
     */
    public function reverseInventoryItem(Request $request, $asnId, $itemId)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();

        $asn = Asn::with('items')->where('created_by', $creatorId)->findOrFail($asnId);
        $item = $asn->items()->where('id', $itemId)->firstOrFail();

        if (!$item->sub_product_id) {
            return redirect()->back()->with('error', __('This ASN item has no inventory (sub-product) linked to it.'));
        }

        $subProduct = SubProduct::where('created_by', $creatorId)->find($item->sub_product_id);
        if (!$subProduct || !$subProduct->isConsignment()) {
            return redirect()->back()->with('error', __('Only consignment inventory created from ASN can be reversed.'));
        }

        // Recompute sold qty for this sub-product (Invoice + POS)
        $soldFromInvoice = \App\Models\InvoiceProduct::where('sub_product_id', $subProduct->id)->sum('quantity');
        $soldFromPos = \App\Models\PosProduct::where('sub_product_id', $subProduct->id)->sum('quantity');
        $soldQty = (float)$soldFromInvoice + (float)$soldFromPos;
        $convertedQty = (float)($item->converted_qty ?? 0);

        if (round($soldQty, 4) > round($convertedQty, 4)) {
            return redirect()->back()->with('error', __('Cannot reverse inventory for this item. Sold quantity (:sold) must equal converted quantity (:converted).', [
                'sold' => $soldQty,
                'converted' => $convertedQty,
            ]));
        }

        // Remaining consignment quantity that was never converted to bill
        $remainingQty = (float)$item->received_qty - $soldQty;
        if ($remainingQty <= 0) {
            return redirect()->back()->with('error', __('No remaining consignment quantity to reverse for this item.'));
        }

        if ($subProduct->quantity < $remainingQty) {
            return redirect()->back()->with('error', __('Cannot reverse :qty units because only :stock are available in stock for this sub-product.', [
                'qty' => $remainingQty,
                'stock' => $subProduct->quantity,
            ]));
        }

        // Find Inventory and Goods Received Clearing accounts (same as convertToInventory)
        $inventoryAccount = \App\Models\ChartOfAccount::where('created_by', $creatorId)
            ->whereRaw('LOWER(name) = ?', ['inventory'])
            ->first();

        $goodsReceivedClearingAccount = \App\Models\ChartOfAccount::where('created_by', $creatorId)
            ->whereRaw('LOWER(name) = ?', ['Goods Received Clearing account'])
            ->first();

        if (!$inventoryAccount || !$goodsReceivedClearingAccount) {
            return redirect()->back()->with('error', __('Inventory or Goods Received Clearing account not found. Please configure accounts first.'));
        }

        $product = $subProduct->productService;
        if (!$product) {
            return redirect()->back()->with('error', __('Product not found for this ASN item sub-product.'));
        }

        try {
            DB::beginTransaction();

            // Get latest voucher ID for reversal
            $latestVoucher = \App\Models\GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
            $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;

            // Amount to reverse: remaining consignment qty * ASN unit price
            $unitPriceOriginal = (float) ($item->unit_price ?? 0);
            $exchangeRate = 1.0;
            if (!empty($asn->currency_id)) {
                $exchangeRate = (float) ($asn->exchange_rate ?? 0);
                if ($exchangeRate <= 0) {
                    $currency = $asn->currency ?: \App\Models\Currency::find($asn->currency_id);
                    $exchangeRate = (float) (($currency->exchange_rate ?? 1) ?: 1);
                }
            }
            $unitPriceAED = !empty($asn->currency_id) ? ($unitPriceOriginal * $exchangeRate) : $unitPriceOriginal;
            $amount = ((float) $remainingQty) * $unitPriceAED;

            // Update product quantity (remove remaining consignment from global product qty)
            $product->quantity = max(0, (float)($product->quantity ?? 0) - $remainingQty);
            $product->save();

            // Update sub-product quantity and flag
            $subProduct->quantity = max(0, (float)$subProduct->quantity - $remainingQty);
            if ($subProduct->quantity <= 0) {
                $subProduct->flag = SubProduct::FLAG_CANCELLED;
            }
            $subProduct->save();
            
            MasterlistLeadger::addFree($product->id,$subProduct->warehouse_id,$subProduct->quantity,'ASN',$asnId,$creatorId);

            // Create stock movement reversal (stock out)
            $stockMovement = new StockMovement();
            $stockMovement->product_id = $product->id;
            $stockMovement->sub_product_id = $subProduct->id;
            $stockMovement->bill_id = null;
            $stockMovement->invoice_id = null;
            $stockMovement->pos_id = null;
            $stockMovement->qty_in = 0;
            $stockMovement->qty_out = $remainingQty;
            $stockMovement->avg_cost = $product->avg_cost ?? $unitPriceAED;
            $stockMovement->cost_price = $unitPriceAED;
            $stockMovement->activity = 'ASN Inventory Reversal';
            $stockMovement->use_id = $asn->supplier_id ?? null;
            $stockMovement->item = $subProduct->id;
            $stockMovement->created_by = $creatorId;
            $stockMovement->save();

            $sendDate = now()->format('Y-m-d');

            // Reverse ledger: Debit Goods Received Clearing, Credit Inventory
            $debitEntry = new \App\Models\GeneralLedger();
            $debitEntry->vid = $newVid;
            $debitEntry->account = $goodsReceivedClearingAccount->id;
            $debitEntry->type = \Auth::user()->asnNumberFormat($asn->asn_no);
            $debitEntry->ref_number = \Auth::user()->asnNumberFormat($asn->asn_no);
            $debitEntry->debit = $amount;
            $debitEntry->credit = 0;
            $debitEntry->ref_id = $asn->id;
            $debitEntry->user_id = 0;
            $debitEntry->created_by = $creatorId;
            $debitEntry->send_date = $sendDate;
            $debitEntry->reference = 'ASN Inventory Reversal';
            $debitEntry->sub_product_id = $subProduct->id;
            $debitEntry->save();

            $creditEntry = new \App\Models\GeneralLedger();
            $creditEntry->vid = $newVid;
            $creditEntry->account = $inventoryAccount->id;
            $creditEntry->type = \Auth::user()->asnNumberFormat($asn->asn_no);
            $creditEntry->ref_number = \Auth::user()->asnNumberFormat($asn->asn_no);
            $creditEntry->debit = 0;
            $creditEntry->credit = $amount;
            $creditEntry->ref_id = $asn->id;
            $creditEntry->user_id = 0;
            $creditEntry->created_by = $creatorId;
            $creditEntry->send_date = $sendDate;
            $creditEntry->reference = 'ASN Inventory Reversal';
            $creditEntry->sub_product_id = $subProduct->id;
            $creditEntry->save();

            $item->inventory_reversed_qty = (float) ($item->inventory_reversed_qty ?? 0) + $remainingQty;
            $item->save();

            DB::commit();

            return redirect()->route('asn.show', $asn->id)->with('success', __('ASN inventory reversed for this item. Remaining consignment stock removed and ledger reversed.'));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ASN reverse inventory failed', [
                'error' => $e->getMessage(),
                'asn_id' => $asnId,
                'asn_item_id' => $itemId,
                'user_id' => $creatorId,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', __('Failed to reverse ASN inventory for this item: ') . $e->getMessage());
        }
    }

    /**
     * Convert selected ASN items to Bill
     */
    public function convertSelectedItemsToBill(Request $request, $id)
    {
        if (!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'selected_items' => 'required|array|min:1',
            'selected_items.*' => 'required|exists:asn_items,id',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'tax_id' => 'required|exists:taxes,id',
        ]);

        try {
            $asnId = $id;
            $user = \Auth::user();
            $creatorId = $user->creatorId();
            $isSoldOnlyMode = (bool) $request->input('sold_only_mode', false);

            $asn = Asn::with(['items', 'supplier'])
                ->where('created_by', $creatorId)
                ->findOrFail($asnId);

            // Get selected items (root lines only; split bill lines cannot be selected)
            $selectedItemIds = $request->input('selected_items');
            $selectedItems = $asn->items->whereIn('id', $selectedItemIds)
                ->filter(function ($i) {
                    // Allow root lines and transfer-split child lines (unbilled).
                    // Keep bill-split child lines non-selectable.
                    return empty($i->split_from_asn_item_id) || (!empty($i->split_from_asn_item_id) && empty($i->bill_id));
                })
                ->values();

            if ($selectedItems->isEmpty()) {
                return redirect()->back()->with('error', __('No valid items selected.'));
            }

            // Convert selected to bill uses existing inventory (sub product); items must have been converted to inventory first
            $itemsWithoutSubProduct = $selectedItems->filter(fn($i) => !$i->sub_product_id);
            if ($itemsWithoutSubProduct->isNotEmpty()) {
                return redirect()->back()->with('error', __('Selected items must be converted to inventory first. Then use "Convert Selected to Bill" to create a bill linking to that stock.'));
            }

            // Remaining qty per item (user can convert until full received qty is converted)
            $convertedQtys = $request->input('converted_qty', []);
            foreach ($selectedItems as $asnItem) {
                $convertedSoFar = (float)($asnItem->converted_qty ?? 0);
                $remaining = max(0, (float)$asnItem->received_qty - $convertedSoFar);
                if ($remaining <= 0) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Item :part has no remaining qty to convert (already converted :done of :received).', ['part' => $asnItem->part_no ?? $asnItem->id, 'done' => $convertedSoFar, 'received' => $asnItem->received_qty]));
                }
                $qty = array_key_exists($asnItem->id, $convertedQtys) ? $convertedQtys[$asnItem->id] : $remaining;
                $qty = is_numeric($qty) ? (float) $qty : (float) $remaining;
                if ($qty < 0 || $qty > $remaining) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', __('Converted qty for item :part must be between 0 and :max (remaining qty).', ['part' => $asnItem->part_no ?? $asnItem->id, 'max' => $remaining]));
                }
            }

            // Note: Items are selectable until converted_qty >= received_qty (validated above).
            // We allow converting items that were converted to consignment (sub_product_id with flag=FLAG_CONSIGNMENT)
            // This is the intended workflow: convert to inventory first, then convert selected items to bill

            // Use warehouse from ASN
            $warehouseId = $asn->warehouse_id;
            $billDate = $request->input('bill_date');
            $dueDate = $request->input('due_date');

            if (!$warehouseId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', __('ASN has no warehouse set. Please set warehouse on the ASN before converting to bill.'));
            }

            $warehouse = \App\Models\warehouse::where('id', $warehouseId)
                ->where('created_by', $creatorId)
                ->firstOrFail();

            // Find Goods Received Clearing account
            $goodsReceivedClearingAccount = \App\Models\ChartOfAccount::where('created_by', $creatorId)
                ->whereRaw('LOWER(name) = ?', ['Goods Received Clearing account'])
                ->first();

            if (!$goodsReceivedClearingAccount) {
                return redirect()->back()->with('error', __('Goods Received Clearing account not found. Please create it first.'));
            }

            // Get supplier (vendor) and its chart account
            $supplierId = null;
            $supplier = null;
            if ($asn->supplier_id) {
                $supplierId = $asn->supplier_id;
                $supplier = \App\Models\Vender::where('created_by', $creatorId)->find($supplierId);
            } elseif ($asn->supplier) {
                $supplierId = $asn->supplier->id;
                $supplier = $asn->supplier;
            } elseif ($asn->supplier_name) {
                $supplier = \App\Models\Vender::where('created_by', $creatorId)
                    ->where('name', $asn->supplier_name)
                    ->first();
                if ($supplier) {
                    $supplierId = $supplier->id;
                }
            }

            if (!$supplierId || !$supplier) {
                return redirect()->back()->with('error', __('Supplier not found. Please ensure ASN has a valid supplier.'));
            }

            if (!$supplier->chart_account_id) {
                return redirect()->back()->with('error', __('Supplier chart account not found. Please assign a chart account to the supplier.'));
            }

            try {
                DB::beginTransaction();

                // Generate bill number
                $billNumber = $this->billNumber();

                // Get currency from ASN
                $currencyId = $asn->currency_id;
                $exchangeRate = $asn->exchange_rate ?? 1;

                // Create bill
                $bill = new \App\Models\Bill();
                $bill->bill_id = (string) $billNumber;
                $bill->vender_id = $supplierId;
                $bill->bill_date = $billDate;
                $bill->due_date = $dueDate;
                $bill->status = 4; // Sent status
                $bill->type = 'Bill';
                $bill->user_type = 'vendor';
                $bill->warehouse_id = $warehouseId;
                $bill->category_id = 0;
                $bill->created_by = $creatorId;
                $bill->salesman_id = $creatorId;
                
                // Get tax from request or use default tax from company
                $taxId = $request->input('tax_id');
                if ($taxId) {
                    $tax = \App\Models\Tax::where('id', $taxId)
                        ->where('created_by', $creatorId)
                        ->first();
                    $bill->tax_id = $tax ? (string)$tax->id : '';
                } else {
                    $defaultTax = \App\Models\Tax::where('created_by', $creatorId)->first();
                    $bill->tax_id = $defaultTax ? (string)$defaultTax->id : '';
                }
                
                $bill->currency_id = $currencyId;
                $bill->exchange_rate = $exchangeRate;
                $bill->send_date = $billDate;
                $bill->save();

                // Create bill status change record
                $statusChange = new \App\Models\BillStatusChange();
                $statusChange->bill_id = $bill->id;
                $statusChange->status = 4; // Sent
                $statusChange->payment_status = -1;
                $statusChange->changed_at = now();
                $statusChange->save();

                // Link this bill to ASN (one ASN can have many bills)
                AsnBill::create(['asn_id' => $asn->id, 'bill_id' => $bill->id]);
                if (!$asn->bill_id) {
                    $asn->bill_id = $bill->id;
                    $asn->save();
                }

                // Get latest voucher ID for ledger entries
                $latestVoucher = \App\Models\GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
                $newVid = $latestVoucher ? $latestVoucher->vid + 1 : 1;
                $sendDate = $billDate;

                // Process selected ASN items
                foreach ($selectedItems as $asnItem) {
                    if ($asnItem->received_qty <= 0) {
                        continue; // Skip items with zero received quantity
                    }

                    // Converted qty: user choice, limited to remaining (received - already converted)
                    $convertedSoFar = (float)($asnItem->converted_qty ?? 0);
                    $remaining = max(0, (float)$asnItem->received_qty - $convertedSoFar);
                    $convertedQtys = $request->input('converted_qty', []);
                    $quantity = array_key_exists($asnItem->id, $convertedQtys) ? (float) $convertedQtys[$asnItem->id] : $remaining;
                    $quantity = min(max(0, $quantity), $remaining);
                    if ($quantity <= 0) {
                        continue; // Skip if user set converted qty to 0
                    }

                    // Use existing sub product from convert-to-inventory (no new stock created; do not set bill_id on sub product)
                    if (!$asnItem->sub_product_id) {
                        \Log::warning('ASN item has no sub_product_id when converting to bill; item must be converted to inventory first', [
                            'part_no' => $asnItem->part_no,
                            'asn_item_id' => $asnItem->id
                        ]);
                        continue;
                    }

                    $subProduct = SubProduct::where('id', $asnItem->sub_product_id)
                        ->where('created_by', $creatorId)
                        ->with(['productService', 'customFieldValues'])
                        ->first();

                    if (!$subProduct || !$subProduct->productService) {
                        \Log::warning('SubProduct not found for ASN item when converting to bill', [
                            'sub_product_id' => $asnItem->sub_product_id,
                            'asn_item_id' => $asnItem->id
                        ]);
                        continue;
                    }

                    $product = $subProduct->productService;

                    // Calculate prices
                    $unitPriceOriginal = $asnItem->unit_price ?? 0;
                    if ($bill->currency_id && $bill->exchange_rate > 0) {
                        $unitPriceAED = $unitPriceOriginal * $bill->exchange_rate;
                        $exchangePrice = $unitPriceOriginal;
                    } else {
                        $unitPriceAED = $unitPriceOriginal;
                        $exchangePrice = $unitPriceOriginal;
                    }

                    $amount = $quantity * $unitPriceAED;

                    // Partial bill qty: split stock onto a new sub-product and add a child ASN line (remaining stays on original sub)
                    $needsSplit = false;
                    if (round($quantity, 4) < round($remaining, 4)) {
                        $needsSplit = true;
                    }
                    $billSubProduct = $subProduct;

                    if ($needsSplit) {
                        $onHandSubQty = (float) $subProduct->quantity;
                        if (!$isSoldOnlyMode && round($onHandSubQty, 4) < round($quantity, 4)) {
                            \Log::warning('ASN convert to bill: sub-product qty less than bill qty; skipping item', [
                                'asn_item_id' => $asnItem->id,
                                'sub_product_id' => $subProduct->id,
                                'sub_qty' => $onHandSubQty,
                                'bill_qty' => $quantity,
                            ]);
                            continue;
                        }

                        $newSub = $subProduct->replicate(['bill_id']);
                        // In "Bill Sold" mode, sold qty is already consumed from stock by Invoice/POS.
                        // Keep stock as-is and use split rows only to represent ownership conversion.
                        $newSub->quantity = $isSoldOnlyMode ? 0 : $quantity;
                        $newSub->bill_id = $bill->id;
                        $newSub->asn_id = $asn->id;
                        $newSub->flag = SubProduct::FLAG_PURCHASED;
                        $newSub->created_by = $creatorId;
                        $newSub->save();

                        foreach ($subProduct->customFieldValues as $cfv) {
                            CustomFieldValue::updateOrCreate(
                                [
                                    'record_id' => $newSub->id,
                                    'field_id' => $cfv->field_id,
                                ],
                                [
                                    'value' => $cfv->value,
                                ]
                            );
                        }

                        if (!$isSoldOnlyMode) {
                            $subProduct->quantity = max(0, $onHandSubQty - $quantity);
                            $subProduct->save();

                            $moveAvg = $product->avg_cost ?? $unitPriceAED;
                            $stockOut = new StockMovement();
                            $stockOut->product_id = $product->id;
                            $stockOut->sub_product_id = $subProduct->id;
                            $stockOut->bill_id = null;
                            $stockOut->invoice_id = null;
                            $stockOut->pos_id = null;
                            $stockOut->qty_in = 0;
                            $stockOut->qty_out = $quantity;
                            $stockOut->avg_cost = $moveAvg;
                            $stockOut->cost_price = $unitPriceAED;
                            $stockOut->activity = 'ASN split to bill (out)';
                            $stockOut->use_id = $asn->supplier_id ?? null;
                            $stockOut->item = $subProduct->id;
                            $stockOut->created_by = $creatorId;
                            $stockOut->save();

                            $stockIn = new StockMovement();
                            $stockIn->product_id = $product->id;
                            $stockIn->sub_product_id = $newSub->id;
                            $stockIn->bill_id = $bill->id;
                            $stockIn->invoice_id = null;
                            $stockIn->pos_id = null;
                            $stockIn->qty_in = $quantity;
                            $stockIn->qty_out = 0;
                            $stockIn->avg_cost = $moveAvg;
                            $stockIn->cost_price = $unitPriceAED;
                            $stockIn->activity = 'ASN split to bill (in)';
                            $stockIn->use_id = $asn->supplier_id ?? null;
                            $stockIn->item = $newSub->id;
                            $stockIn->created_by = $creatorId;
                            $stockIn->save();
                        }

                        $splitAsnItem = $asnItem->replicate([
                            'id',
                            'converted_qty',
                            'sub_product_id',
                            'inventory_converted_at',
                            'inventory_reversed_qty',
                            'bill_id',
                            'deleted_at',
                            'split_from_asn_item_id',
                        ]);
                        $splitAsnItem->qty = $quantity;
                        $splitAsnItem->received_qty = $quantity;
                        $splitAsnItem->split_from_asn_item_id = $asnItem->id;
                        $splitAsnItem->sub_product_id = $newSub->id;
                        $splitAsnItem->inventory_converted_at = now();
                        $splitAsnItem->converted_qty = $quantity;
                        $splitAsnItem->bill_id = $bill->id;
                        $splitAsnItem->save();

                        $billSubProduct = $newSub;
                    }

                    $subProductIds = [$billSubProduct->id];

                    // Bill line uses the sub-product row that receives the billed qty (split or original)
                    $billProduct = new \App\Models\BillProduct();
                    $billProduct->bill_id = $bill->id;
                    $billProduct->product_id = $product->id;
                    $billProduct->sub_product_id = $billSubProduct->id;
                    $billProduct->quantity = $quantity;
                    $billProduct->tax = $bill->tax_id;
                    $billProduct->discount = 0;
                    $billProduct->price = $unitPriceAED;
                    $billProduct->exchange_price = $exchangePrice;
                    $billProduct->exchange_discount = 0;
                    $billProduct->description = $asnItem->description ?? '';
                    $billProduct->save();

                    if (!$needsSplit) {
                        $subProduct->bill_id = $bill->id;
                        $subProduct->asn_id = $asn->id;
                        $subProduct->save();
                    }

                    // Record this conversion (asn_item_bills) and update running total (converted_qty) on the root line
                    AsnItemBill::create([
                        'asn_item_id' => $asnItem->id,
                        'bill_id' => $bill->id,
                        'quantity' => $quantity,
                    ]);
                    $asnItem->converted_qty = $convertedSoFar + $quantity;
                    $asnItem->save();

                    // Calculate tax amount
                    $taxAmount = 0;
                    $taxAccountId = null;
                    if (!empty($bill->tax_id)) {
                        $taxes = \App\Models\Utility::tax($bill->tax_id);
                        foreach ($taxes as $tax) {
                            if ($tax && $tax->rate) {
                                // Tax is calculated on the amount (quantity * unit_price_AED)
                                $taxAmount += ($amount * ($tax->rate / 100));
                                // Get tax account ID (use first tax's account)
                                if (!$taxAccountId && $tax->chart_account_id) {
                                    $taxAccountId = $tax->chart_account_id;
                                }
                            }
                        }
                    }
                    $taxAmount = round($taxAmount, 2);
                    
                    // Total amount including tax
                    $totalAmount = $amount + $taxAmount;

                    // Use first sub product ID for ledger entry (or null if none created)
                    $ledgerSubProductId = !empty($subProductIds) ? $subProductIds[0] : null;

                    // Debit Goods Received Clearing account (amount without tax)
                    $debitGoodsReceivedClearing = new \App\Models\GeneralLedger();
                    $debitGoodsReceivedClearing->vid = $newVid;
                    $debitGoodsReceivedClearing->account = $goodsReceivedClearingAccount->id;
                    $debitGoodsReceivedClearing->type = \Auth::user()->billNumberFormat($bill->id);
                    $debitGoodsReceivedClearing->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                    $debitGoodsReceivedClearing->debit = $amount;
                    $debitGoodsReceivedClearing->credit = 0;
                    $debitGoodsReceivedClearing->ref_id = $bill->id;
                    $debitGoodsReceivedClearing->user_id = $supplierId;
                    $debitGoodsReceivedClearing->created_by = $creatorId;
                    $debitGoodsReceivedClearing->send_date = $sendDate;
                    $debitGoodsReceivedClearing->reference = 'Bill';
                    $debitGoodsReceivedClearing->sub_product_id = $ledgerSubProductId;
                    $debitGoodsReceivedClearing->save();

                    // Credit Vendor account (total amount including tax)
                    $creditVendor = new \App\Models\GeneralLedger();
                    $creditVendor->vid = $newVid;
                    $creditVendor->account = $supplier->chart_account_id;
                    $creditVendor->type = \Auth::user()->billNumberFormat($bill->id);
                    $creditVendor->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                    $creditVendor->debit = 0;
                    $creditVendor->credit = $totalAmount;
                    $creditVendor->ref_id = $bill->id;
                    $creditVendor->user_id = $supplierId;
                    $creditVendor->created_by = $creatorId;
                    $creditVendor->send_date = $sendDate;
                    $creditVendor->reference = 'Bill';
                    $creditVendor->sub_product_id = $ledgerSubProductId;
                    $creditVendor->save();

                    // Debit Tax account (if tax exists)
                    if ($taxAmount > 0 && $taxAccountId) {
                        $debitTax = new \App\Models\GeneralLedger();
                        $debitTax->vid = $newVid;
                        $debitTax->account = $taxAccountId;
                        $debitTax->type = \Auth::user()->billNumberFormat($bill->id);
                        $debitTax->ref_number = \Auth::user()->billNumberFormat($bill->bill_id);
                        $debitTax->debit = $taxAmount;
                        $debitTax->credit = 0;
                        $debitTax->ref_id = $bill->id;
                        $debitTax->user_id = $supplierId;
                        $debitTax->created_by = $creatorId;
                        $debitTax->send_date = $sendDate;
                        $debitTax->reference = 'Bill';
                        $debitTax->sub_product_id = $ledgerSubProductId;
                        $debitTax->save();
                    }
                }

                DB::commit();

                \Log::info('Selected ASN items converted to bill', [
                    'asn_id' => $asn->id,
                    'bill_id' => $bill->id,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $user->id,
                    'items_count' => $selectedItems->count()
                ]);

                return redirect()->route('bill.show', Crypt::encrypt($bill->id))->with('success', __('Selected ASN items successfully converted to bill.'));
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('ASN convert selected items to bill failed', [
                    'error' => $e->getMessage(),
                    'asn_id' => $asnId,
                    'user_id' => $creatorId,
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('Failed to convert selected items to bill: ') . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('ASN convert selected items to bill validation failed', [
                'error' => $e->getMessage(),
                'asn_id' => $id,
                'user_id' => \Auth::user()->id
            ]);
            return redirect()->back()->with('error', __('Failed to convert selected items to bill: ') . $e->getMessage());
        }
    }
}
