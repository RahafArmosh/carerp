@extends('layouts.admin')
@section('page-title')
    {{ __('Sell Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sell Report') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('subproduct.sell_report') }}" method="GET" id="sell_report_form">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="q" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('VIN / Product / SKU / Product No') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="product_no" class="form-label">{{ __('Product No') }}</label>
                                    <input type="text" name="product_no" value="{{ request('product_no') }}" class="form-control" placeholder="{{ __('Product No') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" class="form-control select2">
                                        <option value="">{{ __('All Customers') }}</option>
                                        @foreach ($customers as  $cust)
                                            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                    <select name="vender_id" class="form-control select2">
                                        <option value="">{{ __('All Vendors') }}</option>
                                        @foreach ($vendors as  $vend)
                                            <option value="{{ $vend->id }}" {{ request('vender_id') == $vend->id ? 'selected' : '' }}>{{ $vend->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="brand_id" class="form-label">{{ __('Brand') }}</label>
                                    <select name="brand_id" class="form-control select2">
                                        <option value="">{{ __('All Brands') }}</option>
                                        @foreach ($brands as  $brand)
                                            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="sub_brand_id" class="form-label">{{ __('Sub Brand') }}</label>
                                    <select name="sub_brand_id" class="form-control select2">
                                        <option value="">{{ __('All Sub Brands') }}</option>
                                        @foreach ($subBrands as  $subBrand)
                                            <option value="{{ $subBrand->id }}" {{ request('sub_brand_id') == $subBrand->id ? 'selected' : '' }}>{{ $subBrand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                    <select name="category_id" class="form-control select2">
                                        <option value="">{{ __('All Categories') }}</option>
                                        @foreach ($categories as $id => $cat)
                                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="product_id" class="form-label">{{ __('Product') }}</label>
                                    <select name="product_id" class="form-control select2">
                                        <option value="">{{ __('All Products') }}</option>
                                        @foreach ($products as $id => $prod)
                                            <option value="{{ $id }}" {{ request('product_id') == $id ? 'selected' : '' }}>{{ $prod }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" class="form-control select2">
                                        <option value="">{{ __('All Warehouses') }}</option>
                                        @foreach ($warehouses as  $wh)
                                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="invoice_id" class="form-label">{{ __('Invoice') }}</label>
                                    <select name="invoice_id" class="form-control select2">
                                        <option value="">{{ __('All Invoices') }}</option>
                                        @foreach ($invoices as  $invoice)
                                            <option value="{{ $invoice->id }}" {{ request('invoice_id') == $invoice->id ? 'selected' : '' }}>{{ Auth::user()->invoiceNumberFormat( $invoice->invoice_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                                    <select name="bill_id" class="form-control select2">
                                        <option value="">{{ __('All Bills') }}</option>
                                        @foreach ($bills as  $bill)
                                            <option value="{{ $bill->id }}" {{ request('bill_id') == $bill->id ? 'selected' : '' }}>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="pos_id" class="form-label">{{ __('POS') }}</label>
                                    <select name="pos_id" class="form-control select2">
                                        <option value="">{{ __('All POS') }}</option>
                                        @foreach ($poses as  $pos)
                                            <option value="{{ $pos->id }}" {{ request('pos_id') == $pos->id ? 'selected' : '' }}>{{ '#' . __('POS') . sprintf('%05d', $pos->pos_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-flex align-items-center justify-content-between">
                                        <span>{{ __('Group By') }}</span>
                                        <button type="button" class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" data-bs-target="#groupByOptions" aria-expanded="{{ request('group_by_product') || request('group_by_sub_brand') || request('group_by_salesman') || request('group_by_purchase_man') || request('group_by_customer') || request('group_by_vendor') || request('group_by_warehouse') ? 'true' : 'false' }}" aria-controls="groupByOptions">
                                            <i class="ti ti-chevron-down" id="groupByToggleIcon"></i>
                                        </button>
                                    </label>
                                    <div class="collapse {{ request('group_by_product') || request('group_by_sub_brand') || request('group_by_salesman') || request('group_by_purchase_man') || request('group_by_customer') || request('group_by_vendor') || request('group_by_warehouse') ? 'show' : '' }}" id="groupByOptions">
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_product" id="group_by_product" value="1" {{ request('group_by_product') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_product">
                                                {{ __('Group by Product') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_sub_brand" id="group_by_sub_brand" value="1" {{ request('group_by_sub_brand') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_sub_brand">
                                                {{ __('Group by Sub Brand') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_salesman" id="group_by_salesman" value="1" {{ request('group_by_salesman') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_salesman">
                                                {{ __('Group by Salesman') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_purchase_man" id="group_by_purchase_man" value="1" {{ request('group_by_purchase_man') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_purchase_man">
                                                {{ __('Group by Purchase Man') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_customer" id="group_by_customer" value="1" {{ request('group_by_customer') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_customer">
                                                {{ __('Group by Customer') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_vendor" id="group_by_vendor" value="1" {{ request('group_by_vendor') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_vendor">
                                                {{ __('Group by Vendor') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input group-by-checkbox" type="checkbox" name="group_by_warehouse" id="group_by_warehouse" value="1" {{ request('group_by_warehouse') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="group_by_warehouse">
                                                {{ __('Group by Warehouse') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('subproduct.sell_report') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    @if($groupByProduct)
                                        <th>{{ __('Product') }}</th>
                                    @elseif($groupBySubBrand)
                                        <th>{{ __('Sub Brand') }}</th>
                                    @elseif($groupBySalesman)
                                        <th>{{ __('Salesman') }}</th>
                                    @elseif($groupByPurchaseMan)
                                        <th>{{ __('Purchase Man') }}</th>
                                    @elseif($groupByCustomer)
                                        <th>{{ __('Customer') }}</th>
                                    @elseif($groupByVendor)
                                        <th>{{ __('Vendor') }}</th>
                                    @elseif($groupByWarehouse)
                                        <th>{{ __('Warehouse') }}</th>
                                    @endif
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Product No') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    @can('view_all_stock_columns')
                                    <th>{{ __('Purchase Price') }}</th>
                                    @endcan
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Purchase Status') }}</th>
                                    <th>{{ __('Book Status') }}</th>
                                    @can('view_all_stock_columns')
                                    <th>{{ __('Bill') }}</th>
                                    @endcan
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('POS') }}</th>
                                    <th>{{ __('Location') }}</th>
                                    @can('view_all_stock_columns')
                                    @foreach ($customFields as $customField)
                                        <th>{{ __($customField->name) }}</th>
                                    @endforeach
                                    @endcan
                                    <th>{{ __('Custom Fields') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $colspan = 13 + (auth()->user()->can('view_all_stock_columns') ? 1 + count($customFields) : 0);
                                    if ($groupByProduct || $groupBySubBrand || $groupBySalesman || $groupByPurchaseMan || $groupByCustomer || $groupByVendor || $groupByWarehouse) {
                                        $colspan += 1;
                                    }
                                    $hasGrouping = $groupByProduct || $groupBySubBrand || $groupBySalesman || $groupByPurchaseMan || $groupByCustomer || $groupByVendor || $groupByWarehouse;
                                @endphp
                                @if($hasGrouping && isset($groupedByCriteria))
                                    @foreach ($groupedByCriteria as $groupKey => $groupProducts)
                                        @php
                                            // Get group name
                                            $groupName = null;
                                            if ($groupByProduct) {
                                                $firstProduct = reset($groupProducts);
                                                $groupName = optional($firstProduct['product'])->name ?? 'N/A';
                                            } elseif ($groupBySubBrand) {
                                                $firstProduct = reset($groupProducts);
                                                $groupName = optional(optional($firstProduct['product'])->subBrand)->name ?? 'N/A';
                                            } elseif ($groupBySalesman) {
                                                $groupName = ($groupKey !== 'no_salesman' && isset($salesmen[$groupKey])) ? $salesmen[$groupKey] : 'No Salesman';
                                            } elseif ($groupByPurchaseMan) {
                                                $groupName = ($groupKey !== 'no_purchase_man' && isset($purchaseMen[$groupKey])) ? $purchaseMen[$groupKey] : 'No Purchase Man';
                                            } elseif ($groupByCustomer) {
                                                $customer = ($groupKey !== 'no_customer') ? $customers->firstWhere('id', $groupKey) : null;
                                                $groupName = $customer ? $customer->name : 'No Customer';
                                            } elseif ($groupByVendor) {
                                                $vendor = ($groupKey !== 'no_vendor') ? $vendors->firstWhere('id', $groupKey) : null;
                                                $groupName = $vendor ? $vendor->name : 'No Vendor';
                                            } elseif ($groupByWarehouse) {
                                                $warehouse = ($groupKey !== 'no_warehouse') ? \App\Models\Warehouse::find($groupKey) : null;
                                                $groupName = $warehouse ? $warehouse->name : 'No Warehouse';
                                            }
                                        @endphp
                                        {{-- Group Header --}}
                                        @php
                                            $groupTotal = $groupTotals[$groupKey] ?? [
                                                'totalQty' => 0,
                                                'totalSalePrice' => 0,
                                                'totalPurchasePrice' => 0,
                                                'totalExpense' => 0,
                                                'profitLoss' => 0,
                                            ];
                                        @endphp
                                        <tr class=" font-weight-bold">
                                            <td colspan="{{ $colspan }}" style="background-color: #e9ecef; padding: 10px;">
                                                <div class="justify-content-between align-items-center flex-wrap">
                                                    <div>
                                                        @if($groupByProduct)
                                                            <strong>{{ __('Product') }}: {{ $groupName }}</strong>
                                                        @elseif($groupBySubBrand)
                                                            <strong>{{ __('Sub Brand') }}: {{ $groupName }}</strong>
                                                        @elseif($groupBySalesman)
                                                            <strong>{{ __('Salesman') }}: {{ $groupName }}</strong>
                                                        @elseif($groupByPurchaseMan)
                                                            <strong>{{ __('Purchase Man') }}: {{ $groupName }}</strong>
                                                        @elseif($groupByCustomer)
                                                            <strong>{{ __('Customer') }}: {{ $groupName }}</strong>
                                                        @elseif($groupByVendor)
                                                            <strong>{{ __('Vendor') }}: {{ $groupName }}</strong>
                                                        @elseif($groupByWarehouse)
                                                            <strong>{{ __('Warehouse') }}: {{ $groupName }}</strong>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex gap-3 flex-wrap">
                                                        <span><strong>{{ __('Total Qty') }}:</strong> {{ number_format($groupTotal['totalQty'], 2) }}</span>
                                                        <span><strong>{{ __('Total Sell Price') }}:</strong> {{ \Auth::user()->priceFormat($groupTotal['totalSalePrice']) }}</span>
                                                        @can('view_all_stock_columns')
                                                        <span><strong>{{ __('Total Purchase Price') }}:</strong> {{ \Auth::user()->priceFormat($groupTotal['totalPurchasePrice']) }}</span>
                                                        <span><strong>{{ __('Total Expense') }}:</strong> {{ \Auth::user()->priceFormat($groupTotal['totalExpense']) }}</span>
                                                        <span class="{{ $groupTotal['profitLoss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                            <strong>{{ __('Profit/Loss') }}:</strong> {{ \Auth::user()->priceFormat($groupTotal['profitLoss']) }}
                                                        </span>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        {{-- Products within group --}}
                                        @foreach ($groupProducts as $productId => $productData)
                                            @php
                                                $product = $productData['product'];
                                                $productFullName = ($product && $product->brand ? $product->brand->name .'/' : '') . 
                                                                   ($product && $product->subBrand ? $product->subBrand->name .'/' : '') . 
                                                                   ($product ? $product->name : 'N/A');
                                                $collapseId = 'product_' . $groupKey . '_' . $productId;
                                                
                                                // Calculate product totals
                                                $productTotalQty = $productData['totalQty'];
                                                $productTotalSalePrice = $productData['totalSalePrice'];
                                                $productTotalPurchasePrice = 0;
                                                $productTotalExpense = 0;
                                                $processedSubProductsForProduct = [];
                                                
                                                foreach ($productData['subProducts'] as $row) {
                                                    $productTotalPurchasePrice += (($row->purchase_price ?? 0) * ($row->quantity ?? 0));
                                                    
                                                    $subProductId = $row->sub_product_id;
                                                    $sp = $subProductsData[$subProductId] ?? null;
                                                    
                                                    if ($sp && $sp->productService && !isset($processedSubProductsForProduct[$subProductId])) {
                                                        $category = $sp->productService->category;
                                                        $purchaseAccountId = $category ? $category->purchase_account_id : null;
                                                        
                                                        $directExpenseAmount = 0;
                                                        if ($subProductId && $purchaseAccountId) {
                                                            $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $subProductId)
                                                                ->where('chart_account_id', $purchaseAccountId)
                                                                ->whereHas('directExpense', function ($query) {
                                                                    $query->where('created_by', \Auth::user()->creatorId());
                                                                })
                                                                ->sum('amount');
                                                        }
                                                        
                                                        $carAccessoryAmount = 0;
                                                        if ($subProductId) {
                                                            $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($subProductId) {
                                                                $query->where('car_id', $subProductId)
                                                                    ->orWhere('accessory_id', $subProductId);
                                                            })
                                                            ->whereHas('request', function ($query) {
                                                                $query->where('created_by', \Auth::user()->creatorId());
                                                            })
                                                            ->sum('sell_price');
                                                        }
                                                        
                                                        $productTotalExpense += ($directExpenseAmount + $carAccessoryAmount);
                                                        $processedSubProductsForProduct[$subProductId] = true;
                                                    }
                                                }
                                                
                                                $productProfitLoss = $productTotalSalePrice - $productTotalPurchasePrice - $productTotalExpense;
                                            @endphp
                                            {{-- Product Header Row --}}
                                            <tr  style="background-color: #d1ecf1;">
                                                <td colspan="{{ $colspan }}" style="padding: 10px;">
                                                    <div class="justify-content-between align-items-center flex-wrap">
                                                        <div>
                                                            <strong style="font-size: 1.1em;">{{ $productFullName }}</strong>
                                                        </div>
                                                        <div class="d-flex gap-3 flex-wrap">
                                                            <span><strong>{{ __('Qty') }}:</strong> {{ number_format($productTotalQty, 2) }}</span>
                                                            <span><strong>{{ __('Total Sell Price') }}:</strong> {{ \Auth::user()->priceFormat($productTotalSalePrice) }}</span>
                                                            @can('view_all_stock_columns')
                                                            <span><strong>{{ __('Total Purchase Price') }}:</strong> {{ \Auth::user()->priceFormat($productTotalPurchasePrice) }}</span>
                                                            <span><strong>{{ __('Total Expense') }}:</strong> {{ \Auth::user()->priceFormat($productTotalExpense) }}</span>
                                                            <span class="{{ $productProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <strong>{{ __('Profit/Loss') }}:</strong> {{ \Auth::user()->priceFormat($productProfitLoss) }}
                                                            </span>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            {{-- Sub-products Table Header --}}
                                            <tr style="background-color: #f8f9fa;">
                                                @if($hasGrouping)
                                                    <th style="background-color: #f8f9fa;"></th>
                                                @endif
                                                <th style="background-color: #f8f9fa;">{{ __('ID') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Product') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('SKU') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Product No') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Sale Price') }}</th>
                                                @can('view_all_stock_columns')
                                                <th style="background-color: #f8f9fa;">{{ __('Purchase Price') }}</th>
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Quantity') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Purchase Status') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Book Status') }}</th>
                                                @can('view_all_stock_columns')
                                                <th style="background-color: #f8f9fa;">{{ __('Bill') }}</th>
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Invoice') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('POS') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Location') }}</th>
                                                @can('view_all_stock_columns')
                                                @foreach ($customFields as $customField)
                                                    <th style="background-color: #f8f9fa;">{{ __($customField->name) }}</th>
                                                @endforeach
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Custom Fields') }}</th>
                                            </tr>
                                            {{-- Sub-products Rows --}}
                                            @foreach ($productData['subProducts'] as $row)
                                                @php
                                                    $subProduct = $row->subProduct ?? null;
                                                    $subProductId = $row->sub_product_id;
                                                    $productService = $subProduct ? $subProduct->productService : null;
                                                @endphp
                                                <tr style="background-color: #fff;">
                                                    @if($hasGrouping)
                                                        <td></td>
                                                    @endif
                                                    <td>
                                                        <a href="{{ route('sub-product.expenses', $subProductId) }}" class="btn btn-outline-primary btn-sm">
                                                            {{ $subProductId }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $productFullName }}</td>
                                                    <td>{{ $row->sku ?? ($productService ? $productService->sku : '-') }}</td>
                                                    <td>{{ $row->product_no }}</td>
                                                    <td>{{ \Auth::user()->priceFormat($row->price) }}</td>
                                                    @can('view_all_stock_columns')
                                                    <td>{{ \Auth::user()->priceFormat($row->purchase_price ?? 0) }}</td>
                                                    @endcan
                                                    <td>{{ $row->quantity }}</td>
                                                    <td>
                                                        @php
                                                            $flag = $row->flag ?? 0;
                                                            $flagLabels = [
                                                                0 => 'Pending',
                                                                1 => 'Purchased',
                                                                2 => 'Cancelled',
                                                                3 => 'Consignment'
                                                            ];
                                                        @endphp
                                                        {{ $flagLabels[$flag] ?? 'Unknown' }}
                                                    </td>
                                                    <td>
                                                        @php
                                                            $booked = $row->booked ?? 0;
                                                            $invoiceId = $row->invoice_id ?? null;
                                                            $posId = $row->pos_id ?? null;
                                                            $invoice = $invoiceId ? \App\Models\Invoice::where('id', $invoiceId)->first() : null;
                                                        @endphp
                                                        @if ($booked == 0)
                                                            Free
                                                        @elseif ($booked == 1 && $invoiceId != null && $invoice && $invoice->type == 'rent')
                                                            Rented
                                                        @elseif ($booked == 1 && $invoiceId != null && $invoice && $invoice->type == 'regular')
                                                            Booked
                                                        @elseif($booked == 2 && $invoiceId == null)
                                                            Sold
                                                        @elseif(($booked == 2 && $invoice && $invoice->type == 'regular') || ($booked == 1 && $posId != null))
                                                            Sold
                                                        @elseif($booked == 2 && $invoice && $invoice->type == 'rent')
                                                            Rented
                                                        @else
                                                            Delivered
                                                        @endif
                                                    </td>
                                                    @can('view_all_stock_columns')
                                                    <td>
                                                        @if ($row->bill_id != null)
                                                            @php $bill = $subProduct ? $subProduct->bill : null; @endphp
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($row->bill_id)) }}" class="btn btn-outline-primary">
                                                                {{ $bill ? Auth::user()->billNumberFormat($bill->bill_id) : '#' . $row->bill_id }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    @endcan
                                                    <td>
                                                        @if ($invoiceId != null)
                                                            @php
                                                                $invoice = \App\Models\Invoice::where('id', $invoiceId)->first();
                                                            @endphp
                                                            <a href="{{ route('invoice.show', \Crypt::encrypt($invoiceId)) }}" class="btn btn-outline-primary">
                                                                {{ $invoice ? Auth::user()->invoiceNumberFormat($invoice->invoice_id) : '#' . $invoiceId }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $pos = $subProduct && $posId ? $subProduct->pos : null;
                                                            $displayPosId = $pos ? $pos->pos_id : $posId;
                                                        @endphp
                                                        @if ($posId != null)
                                                            <a href="{{ route('pos.show', \Crypt::encrypt($posId)) }}" class="btn btn-outline-primary">
                                                                {{ '#' . __('POS') . sprintf('%05d', $displayPosId) }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $warehouse = $subProduct ? $subProduct->warehouse : null;
                                                        @endphp
                                                        {{ $warehouse && $warehouse->country 
                                                            ? $warehouse->name.'/'.$warehouse->country->name 
                                                            : ($row->warehouse_name ?? '') }}
                                                    </td>
                                                    @can('view_all_stock_columns')
                                                    @foreach ($customFields as $customField)
                                                        <td>{{ $customFieldValues[$subProductId][$customField->id] ?? '-' }}</td>
                                                    @endforeach
                                                    @endcan
                                                    <td>
                                                        @php
                                                            $values = $customFieldValues[$subProductId] ?? [];
                                                        @endphp
                                                        @if(!empty($values))
                                                            <ul class="mb-0" style="padding-left:16px">
                                                                @foreach($values as $fieldId => $val)
                                                                    @php $field = $customFields->firstWhere('id', $fieldId); @endphp
                                                                    @if($field && $val !== null && $val !== '')
                                                                        <li><strong>{{ $field->name }}:</strong> {{ $val }}</li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @else
                                    {{-- No grouping - show products with sub-product tables --}}
                                    @if(isset($groupedByCriteria['all']))
                                        @foreach ($groupedByCriteria['all'] as $productId => $productData)
                                            @php
                                                $product = $productData['product'];
                                                $productFullName = ($product && $product->brand ? $product->brand->name .'/' : '') . 
                                                                   ($product && $product->subBrand ? $product->subBrand->name .'/' : '') . 
                                                                   ($product ? $product->name : 'N/A');
                                                
                                                // Calculate product totals
                                                $productTotalQty = $productData['totalQty'];
                                                $productTotalSalePrice = $productData['totalSalePrice'];
                                                $productTotalPurchasePrice = 0;
                                                $productTotalExpense = 0;
                                                $processedSubProductsForProduct = [];
                                                
                                                foreach ($productData['subProducts'] as $row) {
                                                    $productTotalPurchasePrice += (($row->purchase_price ?? 0) * ($row->quantity ?? 0));
                                                    
                                                    $subProductId = $row->sub_product_id;
                                                    $sp = $subProductsData[$subProductId] ?? null;
                                                    
                                                    if ($sp && $sp->productService && !isset($processedSubProductsForProduct[$subProductId])) {
                                                        $category = $sp->productService->category;
                                                        $purchaseAccountId = $category ? $category->purchase_account_id : null;
                                                        
                                                        $directExpenseAmount = 0;
                                                        if ($subProductId && $purchaseAccountId) {
                                                            $directExpenseAmount = \App\Models\DirectExpenseItem::where('sub_product_id', $subProductId)
                                                                ->where('chart_account_id', $purchaseAccountId)
                                                                ->whereHas('directExpense', function ($query) {
                                                                    $query->where('created_by', \Auth::user()->creatorId());
                                                                })
                                                                ->sum('amount');
                                                        }
                                                        
                                                        $carAccessoryAmount = 0;
                                                        if ($subProductId) {
                                                            $carAccessoryAmount = \App\Models\CarAccessoryRequestItem::where(function ($query) use ($subProductId) {
                                                                $query->where('car_id', $subProductId)
                                                                    ->orWhere('accessory_id', $subProductId);
                                                            })
                                                            ->whereHas('request', function ($query) {
                                                                $query->where('created_by', \Auth::user()->creatorId());
                                                            })
                                                            ->sum('sell_price');
                                                        }
                                                        
                                                        $productTotalExpense += ($directExpenseAmount + $carAccessoryAmount);
                                                        $processedSubProductsForProduct[$subProductId] = true;
                                                    }
                                                }
                                                
                                                $productProfitLoss = $productTotalSalePrice - $productTotalPurchasePrice - $productTotalExpense;
                                            @endphp
                                            {{-- Product Header Row --}}
                                            <tr  style="background-color: #d1ecf1;">
                                                <td colspan="{{ $colspan }}" style="padding: 10px;">
                                                    <div class=" justify-content-between align-items-center flex-wrap">
                                                        <div>
                                                            <strong style="font-size: 1.1em;">{{ $productFullName }}</strong>
                                                        </div>
                                                        <div class="d-flex gap-3 flex-wrap">
                                                            <span><strong>{{ __('Qty') }}:</strong> {{ number_format($productTotalQty, 2) }}</span>
                                                            <span><strong>{{ __('Total Sell Price') }}:</strong> {{ \Auth::user()->priceFormat($productTotalSalePrice) }}</span>
                                                            @can('view_all_stock_columns')
                                                            <span><strong>{{ __('Total Purchase Price') }}:</strong> {{ \Auth::user()->priceFormat($productTotalPurchasePrice) }}</span>
                                                            <span><strong>{{ __('Total Expense') }}:</strong> {{ \Auth::user()->priceFormat($productTotalExpense) }}</span>
                                                            <span class="{{ $productProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                                                <strong>{{ __('Profit/Loss') }}:</strong> {{ \Auth::user()->priceFormat($productProfitLoss) }}
                                                            </span>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            {{-- Sub-products Table Header --}}
                                            <tr style="background-color: #f8f9fa;">
                                                <th style="background-color: #f8f9fa;">{{ __('ID') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Product') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('SKU') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Product No') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Sale Price') }}</th>
                                                @can('view_all_stock_columns')
                                                <th style="background-color: #f8f9fa;">{{ __('Purchase Price') }}</th>
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Quantity') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Purchase Status') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Book Status') }}</th>
                                                @can('view_all_stock_columns')
                                                <th style="background-color: #f8f9fa;">{{ __('Bill') }}</th>
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Invoice') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('POS') }}</th>
                                                <th style="background-color: #f8f9fa;">{{ __('Location') }}</th>
                                                @can('view_all_stock_columns')
                                                @foreach ($customFields as $customField)
                                                    <th style="background-color: #f8f9fa;">{{ __($customField->name) }}</th>
                                                @endforeach
                                                @endcan
                                                <th style="background-color: #f8f9fa;">{{ __('Custom Fields') }}</th>
                                            </tr>
                                            {{-- Sub-products Rows --}}
                                            @foreach ($productData['subProducts'] as $row)
                                                @php
                                                    $subProduct = $row->subProduct ?? null;
                                                    $subProductId = $row->sub_product_id;
                                                    $productService = $subProduct ? $subProduct->productService : null;
                                                @endphp
                                                <tr style="background-color: #fff;">
                                                    <td>
                                                        <a href="{{ route('sub-product.expenses', $subProductId) }}" class="btn btn-outline-primary btn-sm">
                                                            {{ $subProductId }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $productFullName }}</td>
                                                    <td>{{ $row->sku ?? ($productService ? $productService->sku : '-') }}</td>
                                                    <td>{{ $row->product_no }}</td>
                                                    <td>{{ \Auth::user()->priceFormat($row->price) }}</td>
                                                    @can('view_all_stock_columns')
                                                    <td>{{ \Auth::user()->priceFormat($row->purchase_price ?? 0) }}</td>
                                                    @endcan
                                                    <td>{{ $row->quantity }}</td>
                                                    <td>
                                                        @php
                                                            $flag = $row->flag ?? 0;
                                                            $flagLabels = [
                                                                0 => 'Pending',
                                                                1 => 'Purchased',
                                                                2 => 'Cancelled',
                                                                3 => 'Consignment'
                                                            ];
                                                        @endphp
                                                        {{ $flagLabels[$flag] ?? 'Unknown' }}
                                                    </td>
                                                    <td>
                                                        @php
                                                            $booked = $row->booked ?? 0;
                                                            $invoiceId = $row->invoice_id ?? null;
                                                            $posId = $row->pos_id ?? null;
                                                            $invoice = $invoiceId ? \App\Models\Invoice::where('id', $invoiceId)->first() : null;
                                                        @endphp
                                                        @if ($booked == 0)
                                                            Free
                                                        @elseif ($booked == 1 && $invoiceId != null && $invoice && $invoice->type == 'rent')
                                                            Rented
                                                        @elseif ($booked == 1 && $invoiceId != null && $invoice && $invoice->type == 'regular')
                                                            Booked
                                                        @elseif($booked == 2 && $invoiceId == null)
                                                            Sold
                                                        @elseif(($booked == 2 && $invoice && $invoice->type == 'regular') || ($booked == 1 && $posId != null))
                                                            Sold
                                                        @elseif($booked == 2 && $invoice && $invoice->type == 'rent')
                                                            Rented
                                                        @else
                                                            Delivered
                                                        @endif
                                                    </td>
                                                    @can('view_all_stock_columns')
                                                    <td>
                                                        @if ($row->bill_id != null)
                                                            @php $bill = $subProduct ? $subProduct->bill : null; @endphp
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($row->bill_id)) }}" class="btn btn-outline-primary">
                                                                {{ $bill ? Auth::user()->billNumberFormat($bill->bill_id) : '#' . $row->bill_id }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    @endcan
                                                    <td>
                                                        @if ($invoiceId != null)
                                                            @php
                                                                $invoice = \App\Models\Invoice::where('id', $invoiceId)->first();
                                                            @endphp
                                                            <a href="{{ route('invoice.show', \Crypt::encrypt($invoiceId)) }}" class="btn btn-outline-primary">
                                                                {{ $invoice ? Auth::user()->invoiceNumberFormat($invoice->invoice_id) : '#' . $invoiceId }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $pos = $subProduct && $posId ? $subProduct->pos : null;
                                                            $displayPosId = $pos ? $pos->pos_id : $posId;
                                                        @endphp
                                                        @if ($posId != null)
                                                            <a href="{{ route('pos.show', \Crypt::encrypt($posId)) }}" class="btn btn-outline-primary">
                                                                {{ '#' . __('POS') . sprintf('%05d', $displayPosId) }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $warehouse = $subProduct ? $subProduct->warehouse : null;
                                                        @endphp
                                                        {{ $warehouse && $warehouse->country 
                                                            ? $warehouse->name.'/'.$warehouse->country->name 
                                                            : ($row->warehouse_name ?? '') }}
                                                    </td>
                                                    @can('view_all_stock_columns')
                                                    @foreach ($customFields as $customField)
                                                        <td>{{ $customFieldValues[$subProductId][$customField->id] ?? '-' }}</td>
                                                    @endforeach
                                                    @endcan
                                                    <td>
                                                        @php
                                                            $values = $customFieldValues[$subProductId] ?? [];
                                                        @endphp
                                                        @if(!empty($values))
                                                            <ul class="mb-0" style="padding-left:16px">
                                                                @foreach($values as $fieldId => $val)
                                                                    @php $field = $customFields->firstWhere('id', $fieldId); @endphp
                                                                    @if($field && $val !== null && $val !== '')
                                                                        <li><strong>{{ $field->name }}:</strong> {{ $val }}</li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endif
                                @endif
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $subProducts->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script-page')
<script>
    $(document).ready(function() {
        // Make grouping checkboxes mutually exclusive
        $('.group-by-checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                // Uncheck all other grouping checkboxes
                $('.group-by-checkbox').not(this).prop('checked', false);
            }
        });

        // Handle collapse icon rotation
        $('#groupByOptions').on('show.bs.collapse', function () {
            $('#groupByToggleIcon').removeClass('ti-chevron-down').addClass('ti-chevron-up');
        });

        $('#groupByOptions').on('hide.bs.collapse', function () {
            $('#groupByToggleIcon').removeClass('ti-chevron-up').addClass('ti-chevron-down');
        });

        // Set initial icon state
        if ($('#groupByOptions').hasClass('show')) {
            $('#groupByToggleIcon').removeClass('ti-chevron-down').addClass('ti-chevron-up');
        }
    });
</script>
@endpush

