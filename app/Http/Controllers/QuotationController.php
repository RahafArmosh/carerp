<?php

namespace App\Http\Controllers;

use App\Exports\QoutaionsCreatExport;
use App\Exports\QuotationItemsExport;
use App\Exports\QuotationShowExport;
use App\Exports\QuotationShowExport as ExportsQuotationShowExport;
use App\Models\ProductService;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Validators\ValidationException;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QuotationItemsImport;
use App\Models\AltPartNumber;
use App\Models\Customer;
use App\Models\MasterlistLeadger;
use App\Models\PricingList;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\SubProduct;
use App\Models\Tax;
use Barryvdh\DomPDF\Facade\Pdf;
use item;

class QuotationController extends Controller
{
    public function index()
    {
        
        $quotations = Quotation::where('created_by', \Auth::user()->creatorId())
            ->with('customer', 'tax', 'warehouse', 'priceGroup')
            ->latest()
            ->paginate(20);

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        $products = \App\Models\ProductService::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        $warehouses = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())->get();

        $priceListTypes = \App\Models\PricingListType::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        return view('quotations.create', compact('customers', 'products', 'warehouses', 'priceListTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'quotation_date'    => 'required|date',
            'customer_id'       => 'required|exists:customers,id',
            'warehouse_id'      => 'required|exists:warehouses,id',
            'tax_id'            => 'nullable|exists:taxes,id',
            'delivery_location' => 'nullable|string|max:255',
            'price_group'       => 'required|exists:pricing_list_types,id',
            'item_input_method' => 'required|in:manual,upload',
        ]);

        DB::beginTransaction();

        try {
            $quotation = Quotation::create([
                'quotation_date'    => $request->quotation_date,
                'customer_id'       => $request->customer_id,
                'warehouse_id'      => $request->warehouse_id,
                'tax_id'            => $request->tax_id,
                'delivery_location' => $request->delivery_location,
                'price_group'       => $request->price_group, 
                'created_by'        => \Auth::user()->creatorId(),
            ]);

            $warehouse = warehouse::findOrFail($request->warehouse_id);

            
            
            
            $rawItems = $request->item_input_method === 'manual'
                ? ($request->items ?? [])
                : $this->parseQuotationItemsFromExcel($request->file('items_file'));

            if (empty($rawItems)) {
                throw new \Exception('No quotation items found.');
            }

            
            
            
            $items = [];
            foreach ($rawItems as $sku => $qty) {
                $sku = trim($sku ?? '');
                $qty = (int) ($qty ?? 0);
                if (!$sku || $qty <= 0) continue;
                $items[$sku] = ($items[$sku] ?? 0) + $qty;
            }
            // dd($items);
            if (empty($items)) {
                throw new \Exception('No valid quotation items found.');
            }

            
            
            
            // $invalidSkus = [];
            // foreach (array_keys($items) as $sku) {
            //     if (!ProductService::where('sku', $sku)->exists()) {
            //         $invalidSkus[] = "Product with SKU '{$sku}' does not exist.";
            //     }
            // }
            // if (!empty($invalidSkus)) {
            //     throw ValidationException::withMessages(['items' => $invalidSkus]);
            // }
            
            $subtotal = 0;
            $mainItems = [];
            // dd($items);
            foreach ($items as $sku => $requestedQty) {
                $product = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $sku)->first();
                if ($product){

                        $unitPrice = PricingList::where('created_by', \Auth::user()->creatorId())->where('product_service_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->where('pricing_list_type_id', $request->price_group)
                            ->first();

                        $availableQty = $warehouse->GetFreeQuantity($sku);

                        $usedQty = min($requestedQty, $availableQty);
                        
                        if (!$unitPrice){
                            $unitPrice = 0;
                            $usedQty = 0;
                        }else{
                            $unitPrice =$unitPrice->current_price;
                        }
                        
                        if ($usedQty == 0){
                            $unitPrice = 0;
                        }
                        $mainItem = QuotationItem::create([
                            'quotation_id'       => $quotation->id,
                            'partnumber' => $product->sku,
                            'product_service_id' => $product->id,
                            're_quantity'        => $requestedQty,
                            'av_quantity'        => $usedQty,
                            'unit_price'         => $unitPrice,
                            'total_price'        => $usedQty * $unitPrice,
                            'is_alternative'     => false,
                            'is_selected'        => true,
                            'form_state'         => 'new',
                            'updated_by'         => \Auth::user()->creatorId(),
                        ]);

                        $subtotal += $usedQty * $unitPrice;

                        $mainItems[$sku] = [
                            'mainItemId' => $mainItem->id,
                            'usedQty' => (int)$usedQty,
                            'requestedQty' => $requestedQty,
                        ];
                }else{
                        QuotationItem::create([
                            'quotation_id'       => $quotation->id,
                            'partnumber' => $sku,
                            'product_service_id' => null,
                            're_quantity'        => $requestedQty,
                            'av_quantity'        => 0,
                            'unit_price'         => 0,
                            'total_price'        => 0,
                            'is_alternative'     => false,
                            'is_selected'        => true,
                            'form_state'         => 'out of system',
                            'updated_by'         => \Auth::user()->creatorId(),
                        ]);
                }
                
            }
            // dd($mainItems);
            
            
            
            
            foreach ($mainItems as $sku => $mainData) {
                $remainingQty = $mainData['requestedQty'] - $mainData['usedQty'];
                if ($remainingQty <= 0) continue;

                $alternatives = AltPartNumber::where('part_number', $sku)->where('created_by',\Auth::user()->creatorId())
                    ->where('is_active', true)
                    ->orderBy('priority')
                    ->get();

                foreach ($alternatives as $alt) {
                    if ($remainingQty <= 0) break;

                    $altSku = $alt->alternative_part_number;
                    $altQtyAvailable = $warehouse->GetFreeQuantity($altSku);

                    
                    $mainUsedQty = $mainItems[$altSku]['usedQty'] ?? 0;

                    $freeQty = $altQtyAvailable - $mainUsedQty;
                    if ($freeQty <= 0) continue;

                    
                    $altUsed = min($remainingQty, $freeQty);
                    $altProductId = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $altSku)->first()->id;
                    $altPrice = PricingList::where('product_service_id', $altProductId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('pricing_list_type_id', $request->price_group)
                    ->first();
                    if (!$altPrice){
                        $altPrice = 0;
                        $altUsed = 0;
                    }else{
                        $altPrice = $altPrice->current_price;

                    }
                    if ($freeQty == 0){
                        $altPrice = 0;
                    }

                    QuotationItem::create([
                        'quotation_id'       => $quotation->id,
                        'parent_id'          => $mainData['mainItemId'],
                        'partnumber' => $altSku,
                        'product_service_id' => $altProductId,
                        're_quantity'        => $altUsed,
                        'av_quantity'        => min($freeQty,$altUsed),
                        'unit_price'         => $altPrice,
                        'total_price'        => $altUsed * $altPrice,
                        'is_alternative'     => true,
                        'is_selected'        => false,
                        'form_state'         => 'new',
                        'updated_by'         => \Auth::user()->creatorId(),
                    ]);

                    $subtotal += $altUsed * $altPrice;
                    $remainingQty -= $altUsed;
                }
            }
            $taxAmount = 0;
            if ($quotation->tax_id) {
                $tax = Tax::find($quotation->tax_id);
                $taxAmount = $subtotal * ($tax->rate / 100);
            }

            $quotation->update([
                'subtotal'   => $subtotal,
                'tax_amount' => $taxAmount,
                'total'      => $subtotal + $taxAmount,
            ]);

            DB::commit();

            return redirect()
                ->route('quotations.index')
                ->with('success', __('Quotation created successfully.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    private function parseQuotationItemsFromExcel($file): array
    {
        if (!$file) {
            throw new \Exception('No file uploaded.');
        }

        $import = new QuotationItemsImport();
        Excel::import($import, $file);

        return $import->getItems();
    }

    public function show($id)
    {
        $quotation = Quotation::with([
            'customer',
            'warehouse',
            'items' => function ($q) {
                $q->orderBy('parent_id')
                ->orderBy('is_alternative');
            },
            'items.productService',
        ])->findOrFail($id);

        return view('quotations.show', compact('quotation'));
    }
    public function medit(Quotation $quotation)
    {
        try {
            if ($quotation->is_converted()) {
                return redirect()
                ->back()
                ->with('error', 'This quotation has already been converted to a sales order.');
       
            }

        } catch (\Exception $e) {
            \Log::error('Sale order conversion failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('error', 'This quotation has already been converted to a sales order.');
       
        }
        $quotation->load([
            'items.productService',
        ]);


        return view('quotations.medit', compact('quotation'));
    }
    
    public function edit(Quotation $quotation)
    {
        try {
            if ($quotation->is_converted()) {
                return redirect()
                ->back()
                ->with('error', 'This quotation has already been converted to a sales order.');
       
            }

        } catch (\Exception $e) {
            \Log::error('Sale order conversion failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('error', 'This quotation has already been converted to a sales order.');
       
        }
        // Get all customers, products, warehouses for selection fieldswhere('created_by', \Auth::user()->creatorId())
        $customers   = Customer::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get();
        $products    = ProductService::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get();
        $warehouses  = warehouse::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get();

        // Return the edit view
        return view('quotations.edit', compact('quotation', 'customers', 'products', 'warehouses'));
    }


    public function update(Request $request, Quotation $quotation)
    {
        // dd($request);
        
        $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'delivery_location'  => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            
            $quotation->update([
                'customer_id'        => $request->customer_id,
                'delivery_location'  => $request->delivery_location,
                'updated_by'         => \Auth::user()->creatorId(),
            ]);

            DB::commit();

            return redirect()
                ->route('quotations.edit', $quotation->id)
                ->with('success', __('Quotation info updated successfully.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function quotations_part_manual_update(Request $request, Quotation $quotation)
    {
        $request->validate([
            'items' => 'nullable|array',
            'items.*.qty' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            
            if ($request->has('inline_edit') && $request->inline_edit == 1) {
                $quotation->update([
                    'customer_id'        => $request->customer_id ?? $quotation->customer_id,
                    'delivery_location'  => $request->delivery_location ?? $quotation->delivery_location,
                    'pick_from_location' => $request->pick_from_location ?? $quotation->pick_from_location,
                    'price_group'        => $request->price_group ?? $quotation->price_group,
                ]);
            }

            $editedItems = $request->input('edited_items', []);
            $itemsInput  = $request->input('items', []);

            foreach ($editedItems as $itemId => $flag) {
                if ($flag != 1) continue; 
                if (!isset($itemsInput[$itemId]['qty'])) continue;

                $quantity = (int) $itemsInput[$itemId]['qty'];

                $quotationItem = $quotation->items()->find($itemId);
                if (!$quotationItem) continue;

                $productService = $quotationItem->productService;
                $sku = $productService->sku;

                $warehouse = $quotation->warehouse;

                
                
                $usedInOtherItems = $quotation->items()
                    ->where('product_service_id', $productService->id)
                    ->where('id', '!=', $quotationItem->id)
                    ->sum('re_quantity');

                $freeQty = $warehouse->GetFreeQuantity($sku) - $usedInOtherItems;
                $availableQty = max(min($quantity, $freeQty), 0);

                
                $quotationItem->update([
                    're_quantity' => $quantity,
                    'av_quantity' => $availableQty,
                    'total_price' => $quotationItem->unit_price * $availableQty,
                ]);
            }

            
            $subtotal = $quotation->items()->sum('total_price');
            $taxAmount = 0;
            if ($quotation->tax_id) {
                $tax = \App\Models\Tax::find($quotation->tax_id);
                if ($tax) $taxAmount = $subtotal * ($tax->rate / 100);
            }

            $quotation->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ]);

            DB::commit();

            return redirect()->back()->with('success', __('Quotation updated successfully.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
    

    public function export(Quotation $quotation)
    {
        $fileName = 'Quotation_'.$quotation->quotation_no.'.xlsx';
        return Excel::download(new QuotationItemsExport($quotation), $fileName);
    }
   public function createexport()
    {
        $fileName = 'Quotation_template.xlsx';

        return Excel::download(new QoutaionsCreatExport, $fileName);
    }
    public function import(Request $request, Quotation $quotation)
    {
        $request->validate([
            'items_file' => 'required|file|mimes:xlsx,xls',
        ]);

        DB::beginTransaction();

        try {

            $warehouse = $quotation->warehouse;
            $priceGroup = $quotation->price_group;

            $q_items = $quotation->items;
            $import = new QuotationItemsImport();
            Excel::import($import, $request->file('items_file'));
            $items = $import->getItems(); 
            $quotation->items()->where('is_alternative', 1)->delete();

            $subtotal = 0;
            $mainItems = [];

            foreach ($items as $sku => $requestedQty) {
                $product = ProductService::where('created_by', \Auth::user()->creatorId())->where('sku', $sku)->first();

                if ($product){
                    $pricing = PricingList::where('product_service_id', $product->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->where('pricing_list_type_id', $priceGroup)
                        ->first();

                    $availableQty = $warehouse->GetFreeQuantity($sku);
                    $usedQty = min($requestedQty, $availableQty);

                    $unitPrice = $pricing?->current_price ?? 0;
                    if ($usedQty === 0) {
                        $unitPrice = 0;
                    }
                    $old_item = $q_items
                        ->where('partnumber', $sku)
                        ->where('is_alternative', 0)
                        ->first();

                    if($old_item){
                        
                        $old_item->re_quantity = $requestedQty;
                        $old_item->av_quantity = $usedQty;
                        $old_item->unit_price = $unitPrice;
                        $old_item->total_price = $usedQty * $unitPrice;
                        if($usedQty == 0 ){
                            $old_item->form_state = 'canceled';
                        }
                        $old_item->updated_by = \Auth::user()->creatorId();
                        $old_item->save();
                        

                        $subtotal += $usedQty * $unitPrice;

                        $mainItems[$sku] = [
                            'mainItemId'   => $old_item->id,
                            'usedQty'      => $usedQty,
                            'requestedQty' => $requestedQty,
                        ];
                    }else{
                        $mainItem = QuotationItem::create([
                            'quotation_id'       => $quotation->id,
                            'product_service_id' => $product->id,
                            'partnumber' => $product->sku,
                            're_quantity'        => $requestedQty,
                            'av_quantity'        => $usedQty,
                            'unit_price'         => $unitPrice,
                            'total_price'        => $usedQty * $unitPrice,
                            'is_alternative'     => false,
                            'is_selected'        => true,
                            'form_state'         => 'new',
                            'updated_by'         => \Auth::user()->creatorId(),
                        ]);

                        $subtotal += $usedQty * $unitPrice;

                        $mainItems[$sku] = [
                            'mainItemId'   => $mainItem->id,
                            'usedQty'      => $usedQty,
                            'requestedQty' => $requestedQty,
                        ];
                    }
                }else{
                    $old_item = $q_items
                        ->where('partnumber', $sku)
                        ->first();

                    if($old_item){
                        $old_item->re_quantity = $requestedQty;
                    }else{
                        $mainItem = QuotationItem::create([
                            'quotation_id'       => $quotation->id,
                            'partnumber' => $sku,
                            'product_service_id' => null,
                            're_quantity'        => $requestedQty,
                            'av_quantity'        => 0,
                            'unit_price'         => 0,
                            'total_price'        => 0,
                            'is_alternative'     => false,
                            'is_selected'        => true,
                            'form_state'         => 'out of system',
                            'updated_by'         => \Auth::user()->creatorId(),
                        ]);
                    }
                }
                
            }

            
            foreach ($mainItems as $sku => $mainData) {

                $remainingQty = $mainData['requestedQty'] - $mainData['usedQty'];
                if ($remainingQty <= 0) continue;

                $alternatives = AltPartNumber::where('part_number', $sku)
                    ->where('is_active', true)
                    ->orderBy('priority')
                    ->get();

                foreach ($alternatives as $alt) {
                    if ($remainingQty <= 0) break;

                    $altSku = $alt->alternative_part_number;
                    $freeQty = $warehouse->GetFreeQuantity($altSku);

                    if ($freeQty <= 0) continue;

                    $altUsed = min($remainingQty, $freeQty);
                    $altProductId = $warehouse->GetProduct_id($altSku);

                    $altPricing = PricingList::where('product_service_id', $altProductId)
                        ->where('warehouse_id', $warehouse->id)
                        ->where('pricing_list_type_id', $priceGroup)
                        ->first();

                    $altPrice = $altPricing?->current_price ?? 0;

                    QuotationItem::create([
                        'quotation_id'       => $quotation->id,
                        'parent_id'          => $mainData['mainItemId'],
                        'product_service_id' => $altProductId,
                        'partnumber' => $altSku,
                        're_quantity'        => $altUsed,
                        'av_quantity'        => $altUsed,
                        'unit_price'         => $altPrice,
                        'total_price'        => $altUsed * $altPrice,
                        'is_alternative'     => true,
                        'is_selected'        => false,
                        'form_state'         => 'new',
                        'updated_by'         => \Auth::user()->creatorId(),
                    ]);

                    $subtotal += $altUsed * $altPrice;
                    $remainingQty -= $altUsed;
                }
            }

            $taxAmount = 0;
            if ($quotation->tax_id) {
                $tax = Tax::find($quotation->tax_id);
                $taxAmount = $subtotal * ($tax->rate / 100);
            }
            $quotation->update([
                'subtotal'   => $subtotal,
                'tax_amount' => $taxAmount,
                'total'      => $subtotal + $taxAmount,
            ]);

            DB::commit();

            return back()->with('success', 'Quotation items imported successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function showexport(Quotation $quotation)
    {
        return Excel::download(
            new QuotationShowExport($quotation),
            'quotation_'.$quotation->id.'.xlsx'
        );
    }

    public function exportPdf(Quotation $quotation)
    {
        $quotation->load([
            'customer',
            'warehouse',
            'priceGroup',
            'items.productService',
        ]);

        $pdf = Pdf::loadView(
            'quotations.export_pdf',
            compact('quotation')
        )->setPaper('a4', 'portrait');

        return $pdf->download(
            'Quotation-' . $quotation->quotation_no . '.pdf'
        );
    }
    
    public function convert_to_sale_order_creaet(Quotation $quotation)
    {
        try {
            if ($quotation->is_converted()) {
                return response('
                    <div class="alert alert-danger m-3">
                        This quotation has already been converted to a sales order.
                    </div>
                ');
            }

            return view('quotations.convert_to_sales_order', compact('quotation'));

        } catch (\Exception $e) {
            \Log::error('Sale order conversion failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return response('
                <div class="alert alert-danger m-3">
                    Something went wrong.
                </div>
            ');
        }
    }



    public function convert_to_sale_order(Request $request,Quotation $quotation){
        
        if ($quotation->is_converted()) { 
            throw new \Exception('No data rows found in the file.');
        }
        $request->validate([
            'inforce_add' => 'nullable|boolean',
        ]);

        $salesOrderDate = date('Y-m-d');
        $currencyId = null;
        $exchangeRate = 1.0;
        $user = \App\Models\User::find(auth()->id());
        $creatorId = $user ? $user->creatorId() : auth()->id();
        
        $q_items =  $quotation->items->whereNotIn('form_state', ['out of system']);
        $errors = [];
        $test = [];
        $pricing_list = $quotation->priceGroup;
        $warehouse = $quotation->warehouse;
        $trn =  $quotation->customer->customer_trn_no;

        foreach($q_items as $key=>$item){
            $pricing = PricingList::where('created_by', \Auth::user()->creatorId())
                        ->where('product_service_id', $item->productService->id)
                        ->where('warehouse_id',$warehouse->id)
                        ->where('pricing_list_type_id', $pricing_list->id)
                        ->first();
                        
            $actual_price = $pricing?->current_price ?? 0;
            if( $item->unit_price < $actual_price && $item->av_quantity != 0){
                $uprice = max($item->unit_price,1);
                $per = $actual_price / $uprice ;
                $per = round($per, 4);

                if ($per>1){
                    $errors[$item->productService->sku] = " price increased {$per}% old price {$item->unit_price} actual price {$actual_price}  ";
                }
            }elseif($item->unit_price > $actual_price && $item->av_quantity != 0){
                $actual_price = max($actual_price,1);
                $per = $item->unit_price / $actual_price ;
                $per = round($per, 4);

                if ($per>1){
                    $errors[$item->productService->sku] = "price decrise {$per}% old price {$item->unit_price} actual price {$actual_price} ";
                }
            }
        }

        $statistics  = [];
        $Errorscounter = 0;
        foreach($quotation->items->whereNotIn('form_state', ['out of system']) as $item){
            $sku = $item->productService->sku;
            $freequant = $warehouse->GetFreeQuantity($sku);
            if (!isset($statistics[$sku]['total'])) {
                $statistics[$sku]['total'] = 0;
            }
            $statistics[$sku]['avalable'] = $freequant;
            $statistics[$sku]['resulte']  = $freequant - $statistics[$sku]['total'] ;
            $statistics[$sku]['total'] += $item->av_quantity;
            if($statistics[$sku]['resulte'] < 0) {
                $statistics[$sku]['status']  = false;
                $Errorscounter += 1;
            }else{
                $statistics[$sku]['status']  = true;
            }
        }

        if (!empty($errors) || $Errorscounter > 0 ){
            $errorRows = [];

                // Price errors
                foreach ($quotation->items->whereNotIn('form_state', ['out of system']) as $item) {
                    $sku = $item->productService->sku;

                    if (isset($errors[$sku])) {
                        $errorRows[] = [
                            'part_number' => $sku,
                            'quantity'    => $item->av_quantity,
                            'description' => $errors[$sku],
                        ];
                    }
                }

                // Stock errors
                foreach ($statistics as $sku => $stat) {
                    if ($stat['status'] === false) {
                        $errorRows[] = [
                            'part_number' => $sku,
                            'quantity'    => $stat['total'],
                            'description' => 'Not enough stock. Available: '.$stat['avalable'],
                        ];
                    }
                }
                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\QuotationErrorsExport($errorRows),
                    'quotation_errors.xlsx'
                );

        }

        // dd($errors);
        ///////////////////////////////////////////////////////////////// create number for Sales order 
        $salesOrderNo = null;
        if (empty($salesOrderNo)) {
            $lastSaleOrder = SaleOrder::where('created_by',\Auth::user()->creatorId())
                ->withTrashed()
                ->latest()
                ->first();

            $salesOrderNo = $lastSaleOrder ? ((int)$lastSaleOrder->sale_order_no + 1) : 1;
        }

        ///////////////////////////////////////////////////////////////// create Sale order and saleorder items 

        // dd($test);
        try{
         DB::beginTransaction();
            try {
                $saleOrder = new SaleOrder();
                $saleOrder->sale_order_no = $salesOrderNo;
                $saleOrder->customer_id = $quotation->customer->id;
                $saleOrder->customer_trn_no = $trn;
                $saleOrder->sales_order_date = $salesOrderDate;
                $saleOrder->currency_id = $currencyId;
                $saleOrder->exchange_rate = $exchangeRate;
                $saleOrder->tax_id = $quotation->tax->id;
                $saleOrder->status = 'draft';
                $saleOrder->created_by = auth()->id();
                $saleOrder->converted_quotation_id = $quotation->id;
                $saleOrder->save();

                $mergedItems = [];
                foreach ($quotation->items->whereNotIn('form_state', ['out of system']) as $itemData) {
                    $quantity = 0;
                    $quantity = $itemData->av_quantity;
                    if ($quantity <= 0) {
                        continue;
                    }
                    $sku = $itemData->productService->sku;

                    // 🔹 if already exists → merge quantity
                    if (isset($mergedItems[$sku])) {
                        $mergedItems[$sku]['quantity'] += $quantity;
                    } else {
                        // 🔹 create new SO line
                        $mergedItems[$sku] = [
                            "partnumber" => $sku,
                            "description" => $itemData->productService->name,
                            "unit_price" => $itemData->unit_price,
                            "product_id" => $itemData->productService->id,
                            "quantity" => $quantity,
                        ];
                    }
                }

                // $mergedItems = array_values($mergedItems);
                // foreach ($mergedItems as $item)  {
                //     dd($item['unit_price']);
                // }
                foreach ($mergedItems as $mergedItem)  {

                    $availableSubProducts = SubProduct::whereRaw('UPPER(TRIM(chassis_no)) = ?', [strtoupper(trim($mergedItem['partnumber']))])
                                            ->where('created_by', $creatorId)
                                            ->where('flag', '!=', 2)
                                            ->where('booked', 0)
                                            ->where('quantity', '>', 0)
                                            ->orderBy('created_at', 'ASC') 
                                            ->get();
                    $remainingQty = $mergedItem['quantity'];
                    $bookedSubProductIds = [];

                    foreach ($availableSubProducts as $sp) {
                        if ($remainingQty <= 0) {
                            break;
                        }
                        
                        $availableQty = (float)$sp->quantity;
                        $qtyToBook = min($availableQty, $remainingQty);
                        
                        // Update sub-product
                        $sp->quantity = $availableQty - $qtyToBook;
                        $sp->booked = ($sp->quantity <= 0) ? 2 : 0; // Set booked = 2 (sold) if quantity becomes 0, otherwise keep free
                        $sp->sale_order_id = $saleOrder->id;
                        $sp->save();
                        
                        $bookedSubProductIds[] = $sp->id;

                        //// create SO Items
                        $item = new SaleOrderItem();
                        $item->sale_order_id = $saleOrder->id;
                        $item->part_no = $mergedItem['partnumber'];
                        $item->description = $mergedItem['description'] ?? null;
                        $item->req_qty = $remainingQty;
                        $item->stock_qty = $qtyToBook;
                        $item->packed_qty = 0;
                        $item->discrepancy = 0;
                        $item->unit_price = $mergedItem['unit_price'];
                        $item->product_id = $mergedItem['product_id'];
                        $item->sub_product_id = $sp->id;
                        $item->save();
                        
                        $target_document_type = "";
                        $target_document = 0;
                        if($sp->asn_id){
                            $target_document_type = "ASN";
                            $target_document = $sp->asn_id;
                        }else{
                            $target_document_type = "BILL";
                            $target_document = $sp->bill_id;
                        }
                        MasterlistLeadger::addBooked($sp->product_id,$sp->warehouse_id,$qtyToBook,'SO',$saleOrder->id,$saleOrder->created_by,$target_document_type,$target_document);
    

                        
                        $remainingQty -= $qtyToBook;
                    }
                }

                DB::commit();
                
                $saleOrder->refresh();

                return redirect()->route('saleorder.show',$saleOrder->id)->with('success', __('Sale order updated successfully.'));
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



}
