@extends('layouts.admin')
@section('page-title')
    {{ __('Stock Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Stock Report') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('subproduct.stock_report') }}" method="GET" id="stock_report_form">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="q" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="q" id="search_barcode" value="{{ request('q') }}" class="form-control"
                                        placeholder="{{ __('VIN / Product / SKU / Chassis No') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="vins" class="form-label">{{ __('Chassis No (paste from Excel)') }}</label>
                                    <textarea name="vins" class="form-control font-monospace" rows="2" placeholder="{{ __('Paste barcodes: one per line, or comma/tab separated') }}">{{ request('vins') }}</textarea>
                                    <small class="text-muted">{{ __('Paste a column from Excel to filter by chassis no.') }}</small>
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
                                    <label for="brand_id" class="form-label">{{ __('Brand') }}</label>
                                    <select name="brand_id" class="form-control select2">
                                        <option value="">{{ __('All Brands') }}</option>
                                        @if(isset($brands) && !empty($brands))
                                            @foreach ($brands as $id => $brand)
                                                <option value="{{ $id }}" {{ request('brand_id') == $id ? 'selected' : '' }}>{{ $brand }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="sub_brand_id" class="form-label">{{ __('Model') }}</label>
                                    <select name="sub_brand_id" class="form-control select2">
                                        <option value="">{{ __('All Models') }}</option>
                                        @if(isset($subBrands) && !empty($subBrands))
                                            @foreach ($subBrands as $id => $subBrand)
                                                <option value="{{ $id }}" {{ request('sub_brand_id') == $id ? 'selected' : '' }}>{{ $subBrand }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                {{-- @if(isset($isCompany) && $isCompany) --}}
                                <div class="col-md-3">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" class="form-control select2">
                                        <option value="">{{ __('All Warehouses') }}</option>
                                        @foreach ($warehouses as  $wh)
                                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- @endif --}}
                                @if(isset($isCompany) && $isCompany && $bills->isNotEmpty())
                                <div class="col-md-3">
                                    <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                                    <select name="bill_id" class="form-control select2">
                                        <option value="">{{ __('All Bills') }}</option>
                                        @foreach ($bills as  $bill)
                                            <option value="{{ $bill->id }}" {{ request('bill_id') == $bill->id ? 'selected' : '' }}>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                @if(isset($isCompany) && $isCompany && $invoices->isNotEmpty())
                                <div class="col-md-3">
                                    <label for="invoice_id" class="form-label">{{ __('Invoice') }}</label>
                                    <select name="invoice_id" class="form-control select2">
                                        <option value="">{{ __('All Invoices') }}</option>
                                        @foreach ($invoices as  $invoice)
                                            <option value="{{ $invoice->id }}" {{ request('invoice_id') == $invoice->id ? 'selected' : '' }}>{{ Auth::user()->invoiceNumberFormat( $invoice->invoice_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                @if(isset($asns) && $asns->isNotEmpty() && ((isset($isCompany) && $isCompany) || Auth::user()->hasRole('Warehouse')))
                                <div class="col-md-3">
                                    <label for="asn_id" class="form-label">{{ __('ASN') }}</label>
                                    <select name="asn_id" class="form-control select2">
                                        <option value="">{{ __('All ASNs') }}</option>
                                        @foreach ($asns as $asn)
                                            <option value="{{ $asn->id }}" {{ request('asn_id') == $asn->id ? 'selected' : '' }}>
                                                {{ Auth::user()->asnNumberFormat($asn->asn_no) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" class="form-control select2">
                                        <option value="">{{ __('All Customers') }}</option>
                                        @foreach ($customers as  $cust)
                                            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(isset($isCompany) && $isCompany && $vendors->isNotEmpty())
                                <div class="col-md-3">
                                    <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                    <select name="vender_id" class="form-control select2">
                                        <option value="">{{ __('All Vendors') }}</option>
                                        @foreach ($vendors as  $vend)
                                            <option value="{{ $vend->id }}" {{ request('vender_id') == $vend->id ? 'selected' : '' }}>{{ $vend->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <label for="purchase_status" class="form-label">{{ __('Purchase Status') }}</label>
                                    <select name="purchase_status" class="form-control select2">
                                        <option value="">{{ __('All Purchase Status') }}</option>
                                        <option value="0" {{ request('purchase_status') == '0' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                        <option value="1" {{ request('purchase_status') == '1' ? 'selected' : '' }}>{{ __('Purchased') }}</option>
                                        <option value="2" {{ request('purchase_status') == '2' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                        <option value="3" {{ request('purchase_status') == '3' ? 'selected' : '' }}>{{ __('Inventory') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="book_status" class="form-label">{{ __('Car Book Status') }}</label>
                                    <select name="book_status" class="form-control select2">
                                        <option value="">{{ __('All Book Status') }}</option>
                                        <option value="free" {{ request('book_status') == 'free' ? 'selected' : '' }}>{{ __('Free') }}</option>
                                        <option value="booked" {{ request('book_status') == 'booked' ? 'selected' : '' }}>{{ __('Booked') }}</option>
                                        <option value="rented" {{ request('book_status') == 'rented' ? 'selected' : '' }}>{{ __('Rented') }}</option>
                                        <option value="sold" {{ request('book_status') == 'sold' ? 'selected' : '' }}>{{ __('Sold') }}</option>
                                        <option value="delivered" {{ request('book_status') == 'delivered' ? 'selected' : '' }}>{{ __('Delivered') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label d-block">{{ __('Quantity Filter') }}</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="show_zero_qty" id="show_zero_qty" value="1" class="form-check-input"
                                            {{ request('show_zero_qty') ? 'checked' : '' }}>
                                        <label for="show_zero_qty" class="form-check-label">
                                            {{ __('Show items with zero quantity') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('subproduct.stock_report') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                    <a href="{{ route('subproduct.stock_report.export') . '?' . http_build_query(request()->all()) }}" class="btn btn-success">{{ __('Export to Excel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Warehouse & Custom Fields Modal (company / warehouse users only) --}}
    @if(($isCompany ?? false) || \Auth::user()->warehouses()->exists())
    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-edit-location" method="POST" action="#">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="editLocationModalLabel">{{ __('Edit Warehouse & Bin Location') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="location_modal_sale_price" class="form-label">{{ __('Sale Price') }}</label>
                            <input type="number" step="0.01" min="0" name="sale_price" id="location_modal_sale_price" class="form-control" value="">
                        </div>
                        <div class="mb-3">
                            <label for="location_modal_warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" id="location_modal_warehouse_id" class="form-control select">
                                <option value="">{{ __('Select Warehouse') }}</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Edit all custom fields for this sub-product --}}
                        @foreach ($customFields as $customField)
                            <div class="mb-3">
                                <label for="location_modal_cf_{{ $customField->id }}" class="form-label">{{ __($customField->name) }}</label>
                                @if ($customField->type == 'text' || $customField->type == 'email' || $customField->type == 'number')
                                    <input type="{{ $customField->type }}" id="location_modal_cf_{{ $customField->id }}"
                                        name="customField[{{ $customField->id }}]" class="form-control" value="">
                                @elseif($customField->type == 'date')
                                    <input type="date" id="location_modal_cf_{{ $customField->id }}"
                                        name="customField[{{ $customField->id }}]" class="form-control" value="">
                                @elseif($customField->type == 'textarea')
                                    <textarea id="location_modal_cf_{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control" rows="2"></textarea>
                                @elseif($customField->type == 'dropdown')
                                    @php $options = json_decode($customField->options, true); @endphp
                                    <select id="location_modal_cf_{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">
                                        <option value="">{{ __('Select') }}</option>
                                        @if(!empty($options))
                                            @foreach ($options as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                @else
                                    <input type="text" id="location_modal_cf_{{ $customField->id }}"
                                        name="customField[{{ $customField->id }}]" class="form-control" value="">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Separate Import Section - Only for Company/Admin --}}
    @if(isset($isCompany) && $isCompany)
    <div class="row mt-3">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">{{ __('Update Sub-Products from Excel') }}</h6>
                    <form action="{{ route('subproduct.stock_report.import') }}" method="POST" enctype="multipart/form-data" id="import_form">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label for="import_file" class="form-label">{{ __('Excel File') }}</label>
                                <input type="file" name="file" id="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">
                                    {{ __('Required column: ID') }}<br>
                                    {{ __('Updatable columns: Chassis No, Brand, Model, Sale Price, Purchase Price, Warehouse ID/Name (Location), and Custom Fields (each as separate column).') }}<br>
                                    {{ __('You can update Sale Price, Brand, and Model: download the template, edit these columns, and re-upload.') }}<br>
                                    <strong class="text-warning">{{ __('Note: Quantity updates are disabled for security reasons.') }}</strong>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-info" id="import_submit_btn">
                                    <i class="ti ti-upload"></i> <span id="import_btn_text">{{ __('Import & Update') }}</span>
                                </button>
                                <a href="{{ route('subproduct.stock_report.export') . '?' . http_build_query(request()->all()) }}" class="btn btn-outline-success">
                                    <i class="ti ti-download"></i> {{ __('Download Template') }}
                                </a>
                            </div>
                        </div>
                        <div id="import_progress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                                    {{ __('Processing import... This may take several minutes for large files.') }}
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">{{ __('Please do not close this page or navigate away.') }}</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Chassis No') }}</th>
                                    <th>{{ __('Request No') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    <th>{{ __('Sale Price with VAT') }}</th>
                                    @if(isset($isCompany) && $isCompany)
                                    @can('view_all_stock_columns')
                                    <th>{{ __('Purchase Price') }}</th>
                                    <th>{{ __('Avg Cost') }}</th>
                                    @endcan
                                    @endif
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Purchase Status') }}</th>
                                    <th>{{ __('Book Status') }}</th>
                                    @if(isset($isCompany) && $isCompany)
                                    @can('view_all_stock_columns')
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('ASN') }}</th>
                                    @endcan
                                    @endif
                                    @if(isset($isCompany) && $isCompany)
                                    <th>{{ __('Invoice') }}</th>
                                    @endif
                                    {{-- <th>{{ __('Created By') }}</th> --}}
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Location') }}</th>
                                    {{-- @can('view_all_stock_columns') --}}
                                    @foreach ($customFields as $customField)
                                        <th>{{ __($customField->name) }}</th>
                                    @endforeach
                                    <th>{{ __('Note') }}</th>
                                    <th>{{ __('Media') }}</th>
                                    {{-- @endcan --}}
                                    @if(($isCompany ?? false) || \Auth::user()->warehouses()->exists())
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                    {{-- <th>{{ __('Custom Fields') }}</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subProducts as $productService)
                                    <tr class="font-style">
                                        <td>
                                            <a href="{{ route('sub-product.expenses', $productService->id) }}" class="btn btn-outline-primary btn-sm">
                                                {{ $productService->id }}
                                            </a>
                                        </td>
                                        <td>{{ optional(optional($productService->productService)->brand)->name ?? '-' }}/{{ optional(optional($productService->productService)->subBrand)->name ?? '-' }}/{{ optional($productService->productService)->name ?? '-' }}</td>
                                        <td>{{ optional($productService->productService)->sku ?? '-' }}</td>
                                        <td>{{ $productService->product_no }}</td>
                                        <td>
                                            @php
                                                $requests = $carAccessoryRequests[$productService->id] ?? [];
                                            @endphp
                                            @if(!empty($requests))
                                                @foreach($requests as $request)
                                                    <a href="{{ route('car_accessories.show', $request['id']) }}" class="badge bg-info text-decoration-none" target="_blank">{{ $request['request_no'] }}</a>@if(!$loop->last) <br> @endif
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($productService->sale_price) }}</td>
                                        <td>
                                            @php
                                                // Calculate price with VAT
                                                // Sub-product sale_price is base price from Excel (no VAT)
                                                $baseSalePrice = $productService->sale_price ?? 0;
                                                $priceWithVat = $baseSalePrice;
                                                
                                                // Get VAT rate from parent product's tax_id
                                                if ($productService->productService && !empty($productService->productService->tax_id)) {
                                                    // Get tax rates (can be comma-separated)
                                                    $taxIds = explode(',', $productService->productService->tax_id);
                                                    $totalVatRate = 0;
                                                    
                                                    foreach ($taxIds as $taxId) {
                                                        $taxId = trim($taxId);
                                                        if (!empty($taxId)) {
                                                            $tax = \App\Models\Tax::find($taxId);
                                                            if ($tax) {
                                                                $totalVatRate += (float) $tax->rate;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Calculate price with VAT: basePrice * (1 + VAT/100)
                                                    if ($totalVatRate > 0) {
                                                        $priceWithVat = $baseSalePrice * (1 + ($totalVatRate / 100));
                                                    }
                                                }
                                            @endphp
                                            {{ \Auth::user()->priceFormat($priceWithVat) }}
                                        </td>
                                        @if(isset($isCompany) && $isCompany)
                                        @can('view_all_stock_columns')
                                        <td>{{ \Auth::user()->priceFormat($productService->purchase_price) }}</td>
                                        <td>{{ \Auth::user()->priceFormat(optional($productService->productService)->avg_cost ?? 0) }}</td>
                                        @endcan
                                        @endif
                                        <td>{{ $productService->quantity }}</td>
                                        <td>
                                            @php
                                                $flag = $productService->flag ?? 0;
                                                $flagLabels = [
                                                    0 => 'Pending',
                                                    1 => 'Purchased',
                                                    2 => 'Cancelled',
                                                    3 => 'Inventory'
                                                ];
                                            @endphp
                                            {{ $flagLabels[$flag] ?? 'Unknown' }}
                                        </td>
                                        <td>
                                            @if ($productService->booked == 0)
                                                Free
                                            @elseif ($productService->booked == 1 && $productService->invoice_id != null && optional($productService->invoice)->type == 'rent')
                                                Rented
                                            @elseif ($productService->booked == 1 && $productService->invoice_id != null && optional($productService->invoice)->type == 'regular')
                                                Booked
                                            @elseif($productService->booked == 2 && $productService->invoice_id == null)
                                                Sold
                                            @elseif(($productService->booked == 2 && optional($productService->invoice)->type == 'regular') || ($productService->booked == 1 && $productService->pos_id != null))
                                                Sold
                                            @elseif($productService->booked == 2 && optional($productService->invoice)->type == 'rent')
                                                Rented
                                            @else
                                                Delivered
                                            @endif
                                        </td>
                                        @if(isset($isCompany) && $isCompany)
                                        @can('view_all_stock_columns')
                                        <td>
                                            @if ($productService->bill_id != null)
                                                <a href="{{ route('bill.show', \Crypt::encrypt($productService->bill_id)) }}" class="btn btn-outline-primary">
                                                    {{ Auth::user()->billNumberFormat(optional($productService->bill)->bill_id) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($productService->asn_id != null && $productService->asn)
                                                <a href="{{ route('asn.show', $productService->asn->id) }}" class="btn btn-outline-primary">
                                                    {{ Auth::user()->asnNumberFormat($productService->asn->asn_no) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endcan
                                        @endif
                                        @if(isset($isCompany) && $isCompany)
                                        <td>
                                            @if ($productService->invoice_id != null)
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($productService->invoice_id)) }}" class="btn btn-outline-primary">
                                                    {{ Auth::user()->invoiceNumberFormat(optional($productService->invoice)->invoice_id) }}
                                                </a>
                                            @elseif($productService->pos_id != null)
                                                @php
                                                    $pos = $productService->pos;
                                                    $displayPosId = $pos ? $pos->pos_id : $productService->pos_id;
                                                @endphp
                                                <a href="{{ route('pos.show', \Crypt::encrypt($productService->pos_id)) }}" class="btn btn-outline-primary">
                                                    {{ '#' . __('POS') . sprintf('%05d', $displayPosId) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                        {{-- <td>
                                            @php
                                                $user = \App\Models\User::find($productService->created_by);
                                            @endphp
                                            {{ $user ? $user->name : '-' }}
                                        </td> --}}
                                        <td>
                                            {{ $productService->warehouse ? $productService->warehouse->name : '-' }}
                                        </td>
                                        <td>
                                            {{ $productService->warehouse && $productService->warehouse->country 
                                                ? $productService->warehouse->name.'/'.$productService->warehouse->country->name 
                                                : '' }}
                                        </td>
                                        {{-- @can('view_all_stock_columns') --}}
                                        @foreach ($customFields as $customField)
                                            <td>{{ $customFieldValues[$productService->id][$customField->id] ?? '-' }}</td>
                                        @endforeach
                                        <td>
                                            @if(!empty($productService->note))
                                                <span class="badge bg-info" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $productService->note }}">
                                                    {{ Str::limit($productService->note, 30) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Media') }}">
                                                <a href="#"
                                                    class="btn btn-secondary"
                                                    data-size="lg"
                                                    data-url="{{ route('subproduct.stock_report.gallery', $productService->id) }}"
                                                    data-ajax-popup="true"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('View item images') }}"
                                                    data-title="{{ __('Sub product images') }}">
                                                    <i class="ti ti-photo"></i>
                                                </a>
                                                <a href="{{ route('subproduct.stock_report.brochure.pdf', $productService->id) }}"
                                                    class="btn btn-success"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('Download sub product brochure PDF') }}"
                                                    target="_blank" rel="noopener">
                                                    <i class="ti ti-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                        {{-- @endcan --}}
                                        @if(($isCompany ?? false) || \Auth::user()->warehouses()->exists())
                                            <td class="text-center">
                                                @can('edit sub-products')
                                                    <button type="button"
                                                       class="btn btn-sm btn-primary btn-edit-location"
                                                       data-url="{{ route('sub-product.update-location', $productService->id) }}"
                                                       data-sale-price="{{ $productService->sale_price ?? '' }}"
                                                       data-warehouse-id="{{ $productService->warehouse_id ?? '' }}"
                                                       @foreach($customFields as $lf) data-cf-{{ $lf->id }}="{{ e($customFieldValues[$productService->id][$lf->id] ?? '') }}" @endforeach
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#editLocationModal"
                                                       title="{{ __('Edit Warehouse & Bin Location') }}">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        @endif
                                        {{-- <td>
                                            @php
                                                $values = $customFieldValues[$productService->id] ?? [];
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
                                        </td>--}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        {{-- Total Stock Quantity Summary --}}
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card" style="background-color: #f8f9fa; border-top: 2px solid #dee2e6;">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 text-end">
                                                <strong style="font-size: 1.1em;">{{ __('Total Stock Quantity') }}:</strong>
                                            </div>
                                            <div class="col-md-6">
                                                <strong class="text-primary" style="font-size: 1.3em;">{{ number_format($totalStockQuantity ?? 0) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
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
        // Handle import form submission via AJAX to prevent GET redirect issues
        $('#import_form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(this);
            const submitBtn = $('#import_submit_btn');
            const btnText = $('#import_btn_text');
            const progressDiv = $('#import_progress');
            const fileInput = $('#import_file');
            
            // Validate file is selected
            if (!fileInput[0].files || !fileInput[0].files[0]) {
                if (typeof show_toastr !== 'undefined') {
                    show_toastr('error', '{{ __("Please select a file to import") }}');
                } else {
                    alert('{{ __("Please select a file to import") }}');
                }
                return false;
            }
            
            // Disable submit button and show progress
            submitBtn.prop('disabled', true);
            btnText.text('{{ __("Processing...") }}');
            progressDiv.show();
            
            // Submit via AJAX
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 1800000, // 30 minutes timeout for large imports
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    // Handle progress if needed
                    return xhr;
                },
                success: function(response) {
                    // Check if response contains HTML (redirect response) or JSON
                    if (typeof response === 'string' && response.includes('<!DOCTYPE')) {
                        // It's an HTML redirect response, extract the message from it
                        // Or just show a generic success message
                        if (typeof show_toastr !== 'undefined') {
                            show_toastr('success', '{{ __("Import completed successfully!") }}');
                        } else {
                            alert('{{ __("Import completed successfully!") }}');
                        }
                        // Reload page to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // JSON response
                        if (response.success) {
                            if (typeof show_toastr !== 'undefined') {
                                show_toastr('success', response.message || '{{ __("Import completed successfully!") }}');
                            } else {
                                alert(response.message || '{{ __("Import completed successfully!") }}');
                            }
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            if (typeof show_toastr !== 'undefined') {
                                show_toastr('error', response.message || '{{ __("Import failed") }}');
                            } else {
                                alert(response.message || '{{ __("Import failed") }}');
                            }
                            submitBtn.prop('disabled', false);
                            btnText.text('{{ __("Import & Update") }}');
                            progressDiv.hide();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = '{{ __("Import failed") }}';
                    
                    if (status === 'timeout') {
                        errorMessage = '{{ __("Import timed out. The file may be too large. Please try again or contact support.") }}';
                    } else if (xhr.responseJSON) {
                        // Handle JSON error response
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            // Handle validation errors
                            const errors = [];
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                if (Array.isArray(value)) {
                                    errors.push(value.join(', '));
                                } else {
                                    errors.push(value);
                                }
                            });
                            errorMessage = errors.join('; ');
                        }
                    } else if (xhr.responseText) {
                        // Try to extract error message from HTML response
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(xhr.responseText, 'text/html');
                        const errorElement = doc.querySelector('.alert-danger, .error, [role="alert"]');
                        if (errorElement) {
                            errorMessage = errorElement.textContent.trim();
                        }
                    }
                    
                    if (typeof show_toastr !== 'undefined') {
                        show_toastr('error', errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                    
                    submitBtn.prop('disabled', false);
                    btnText.text('{{ __("Import & Update") }}');
                    progressDiv.hide();
                }
            });
            
            return false;
        });
    });

    // Edit Warehouse & Bin Location modal: fill form from button data and set action
    $('#editLocationModal').on('show.bs.modal', function(e) {
        var btn = e.relatedTarget;
        if (!btn || !$(btn).hasClass('btn-edit-location')) return;
        var form = document.getElementById('form-edit-location');
        if (!form) return;
        form.action = $(btn).data('url');
        $('#location_modal_sale_price').val($(btn).data('sale-price') || '');
        $('#location_modal_warehouse_id').val($(btn).data('warehouse-id') || '');
        $('form#form-edit-location [id^="location_modal_cf_"]').each(function() {
            var fieldId = this.id.replace('location_modal_cf_', '');
            var val = $(btn).data('cf-' + fieldId);
            $(this).val(val !== undefined && val !== null ? val : '');
        });
    });
</script>
@endpush
