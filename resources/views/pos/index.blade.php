@php
    // $logo=asset(Storage::url('uploads/logo/'));
    $logo = \App\Models\Utility::get_file('uploads/logo');
    $company_favicon = Utility::getValByName('company_favicon');
    $SITE_RTL = Utility::getValByName('SITE_RTL');
    $setting = \App\Models\Utility::colorset();
    $color = 'theme-3';
    if (!empty($setting['color'])) {
        $color = $setting['color'];
    }
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
@endphp
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>
        {{ !empty($companySettings['header_text']) ? $companySettings['header_text']->value : config('app.name', 'Orbix') }}
        - {{ __('POS') }}</title>

    <link rel="icon"
        href="{{ URL::to('/') . '/' . 'storage/uploads/logo' . '/' . (isset($companySettings['company_favicon']) && !empty($companySettings['company_favicon']) ? $companySettings['company_favicon']->value : 'favicon.png') }}"
        type="image" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}" id="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <!--bootstrap switch-->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap-switch-button.min.css') }}">

    <!-- vendor css -->
    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}" id="main-style-link">

    <style>
        .bg-color {
            @if ($color == 'theme-1')
                background: linear-gradient(141.55deg, rgba(81, 69, 157, 0) 3.46%, rgba(255, 58, 110, 0.6) 99.86%), #51459d;
            @elseif($color == 'theme-2')
                background: linear-gradient(141.55deg, rgba(81, 69, 157, 0) 3.46%, #4ebbd3 99.86%), #1f3996;
            @elseif($color == 'theme-3')
                background: linear-gradient(141.55deg, #6fd943 3.46%, #6fd943 99.86%), #6fd943;
            @elseif($color == 'theme-4')
                background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #685ee5 99.86%), #584ed2;
            @endif
        }

        .carttable-scroll {
            height: calc(150vh - 115px);
        }
        #users_search span{
            padding: 0  3px !important;
        }
        .customer-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .customer-search-results .customer-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .customer-search-results .customer-item:hover {
            background-color: #f8f9fa;
        }
        .customer-search-results .customer-item.selected {
            background-color: #e7f3ff;
        }
        .customer-search-results .customer-item .customer-name {
            font-weight: 600;
            color: #333;
        }
        .customer-search-results .customer-item .customer-details {
            font-size: 0.875rem;
            color: #666;
            margin-top: 4px;
        }
        .customer-search-results .no-results {
            padding: 15px;
            text-align: center;
            color: #999;
        }
        .warehouse-select-container {
            display: flex;
            align-items: stretch;
            gap: 8px;
            width: 100%;
        }
        .warehouse-select-container .select2-container {
            flex: 1;
            min-width: 0;
            width: 100% !important;
        }
        .warehouse-select-container .select2-container .select2-selection {
            height: 38px;
            display: flex;
            align-items: center;
        }
        .warehouse-select-container #load-warehouse-products {
            white-space: nowrap;
            flex-shrink: 0;
            height: 38px;
            padding: 0.375rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .warehouse-select-container select {
            width: 100%;
        }
        #reload-pos-btn {
            transition: transform 0.3s ease, opacity 0.3s ease;
            cursor: pointer;
        }
        #reload-pos-btn:hover {
            opacity: 0.8;
            transform: scale(1.1);
        }
        #reload-pos-btn:active {
            transform: rotate(180deg) scale(1.1);
        }
        #reload-pos-btn i {
            display: inline-block;
            transition: transform 0.3s ease;
        }
        #reload-pos-btn:hover i {
            transform: rotate(90deg);
        }
        #reload-pos-btn i.ti-loader {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        /* Optimized product listing styles - no images for better performance */
        .toacart {
            cursor: pointer;
            border: 1px solid #e0e0e0;
            transition: all 0.2s ease;
        }
        .toacart:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0,123,255,0.2);
            transform: translateY(-2px);
        }
        .toacart:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,123,255,0.1);
        }
        #product-listing {
            min-height: 200px;
        }
        /* Optimize scrolling for product listing */
        .product-body-nop {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        /* Smooth scrolling */
        .product-body-nop {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
    </style>

    @stack('css-page')
</head>

<body class="{{ $color }}">
    <div class="container-fluid px-2">
        <?php $lastsegment = request()->segment(count(request()->segments())); ?>
        <div class="row">
            <div class="col-12">
                <div class="mt-2 pos-top-bar bg-color d-flex justify-content-between bg-primary">
                    <div class="d-flex align-items-center">
                        <span class="text-white">{{ __('POS') }}</span>
                        <span class="text-white ms-3">|</span>
                        <span class="text-white ms-3">{{ __('User') }}: {{ Auth::user()->name }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('dashboard') }}" class="text-white me-3" title="{{ __('Dashboard') }}">
                            <i class="ti ti-home" style="font-size: 20px;"></i>
                        </a>
                        <a href="{{ route('pos.report') }}" target="_blank" class="text-white me-3" title="{{ __('POS Report') }}">
                            <i class="ti ti-shopping-cart" style="font-size: 20px;"></i>
                        </a>
                        <a href="{{ route('pos.stock-report') }}" target="_blank" class="text-white me-3" title="{{ __('Stock Report') }}">
                            <i class="ti ti-package" style="font-size: 20px;"></i>
                        </a>
                        <a href="javascript:void(0);" class="text-white me-3" title="{{ __('Reload POS & Empty Cart') }}" 
                           id="reload-pos-btn">
                            <i class="ti ti-refresh" style="font-size: 20px;"></i>
                        </a>
                        <a href="{{ route('logout') }}" class="text-white" title="{{ __('Logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ti ti-logout" style="font-size: 20px;"></i>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-2 row">
            <div class="col-lg-5">
                <div class="sop-card card">
                    <div class="card-header p-2">
                        <div class="search-bar-left d-flex">
                            <form class="mr-2" onsubmit="return false;">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    </div>
                                    <input id="searchproduct" type="text" data-url="{{ route('search.products') }}"
                                        placeholder="{{ __('Search Product') }}"
                                        class="form-control pr-4 rounded-right">
                                </div>
                            </form>
                            <form class="mr-2" onsubmit="return false;">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ti ti-barcode"></i></span>
                                    </div>
                                    <input id="searchbarcode_manual" type="text" data-url="{{ route('search.barcode') }}"
                                        placeholder="{{ __('Type Barcode to Search') }}"
                                        class="form-control pr-4 rounded-right">
                                </div>
                            </form>
                            <form onsubmit="return false;">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ti ti-scan"></i></span>
                                    </div>
                                    <input id="searchbarcode" type="text" data-url="{{ route('search.barcode') }}"
                                        placeholder="{{ __('Scan Barcode (Auto-Add)') }}"
                                        class="form-control pr-4 rounded-right" autofocus>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="right-content">
                            <div class="button-list b-bottom catgory-pad">
                                <div class="form-row m-0" id="categories-listing">
                                </div>
                            </div>
                            <div class="product-body-nop">
                                <div id="product-listing-loading" class="text-center p-4" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">{{ __('Loading products...') }}</span>
                                    </div>
                                    <p class="mt-2 text-muted">{{ __('Loading products...') }}</p>
                                </div>
                                <div class="form-row" id="product-listing">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 ps-lg-0">
                <div class="card m-0">
                    <div class="card-header p-2">
                        <div class="row" style="justify-content: center;align-items: center;" >
                            <div id="users_search" class="form-group col-md-6" style="margin: 0; position: relative;">
                                <div class="input-group">
                                    <input type="text" 
                                           id="customer_search" 
                                           class="form-control" 
                                           placeholder="{{ __('Search customer by name, email, or number...') }}"
                                           autocomplete="off"
                                           required>
                                           <a href="#" data-size="lg" data-url="{{ route('customer.create') }}?from=pos" data-ajax-popup="true"
                                                data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Customer') }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="ti ti-plus"></i>
                                           </a>
                                </div>
                                <input type="hidden" name="customer_id" id="customer_id" required>
                                <input type="hidden" name="vc_name_hidden" id="vc_name_hidden">
                                <div id="customer_search_results" class="customer-search-results" style="display: none;"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="warehouse-select-container">
                                    <select name="warehouse_id" id="warehouse" class="form-control select2 warehouse_select"
                                        required>
                                        @foreach ($warehouses as $warehouseId => $warehouseName)
                                            <option value="{{ $warehouseId }}" {{ $loop->first ? 'selected' : '' }}>{{ $warehouseName }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="load-warehouse-products" class="btn btn-primary" 
                                        title="{{ __('Load Products from Selected Warehouse') }}">
                                        <i class="ti ti-refresh"></i> {{ __('Load') }}
                                    </button>
                                </div>
                                <input type="hidden" name="warehouse_name_hidden" id="warehouse_name_hidden">
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user_id" class="form-label">{{ __('Cashier') }}</label>
                                    <select name="user_id" id="user_id" class="form-control select2" required>
                                        @foreach ($users as $userId => $userName)
                                            <option value="{{ $userId }}" {{ $userId == Auth::user()->id ? 'selected' : '' }}>{{ $userName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body carttable cart-product-list carttable-scroll" id="carthtml">
                        @php 
                            $total = 0 ;
                            $total_vouchers = 0;
                        @endphp
                        <div class="table-responsive" style="height: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="text-left">{{ __('Name') }}</th>
                                        <th class="text-center">{{ __('QTY') }}</th>
                                        {{-- <th>{{ __('Tax') }}</th> --}}
                                        <th class="text-center">{{ __('Price') }}</th>
                                        <th class="text-center">{{ __('Discount%') }}</th>
                                        <th class="text-center">{{ __('Sub Total') }}</th>
                                        <th class="text-center">{{ __('Delete') }}</th>
                                        <th class="text-center">{{ __('combo') }}</th>
                                    </tr>
                                </thead>
                                @if (session('vouchers') && !empty(session('vouchers')) && count(session('vouchers')) > 0)
                                    @foreach (session('vouchers') as $id => $details)
                                                @php
                                                    $total_vouchers += $details['amount'];
                                                @endphp
                                    @endforeach
                                @endif
                                <tbody id="tbody">
                                    @if (session($lastsegment) && !empty(session($lastsegment)) && count(session($lastsegment)) > 0)
                                        @foreach (session($lastsegment) as $id => $details)
                                            @php
                                                // Get warehouse ID - try from request first, then from first warehouse
                                                $warehouseId = request('warehouse_id');
                                                if (!$warehouseId && $warehouses) {
                                                    // Handle both collection and array
                                                    $warehousesArray = is_array($warehouses) ? $warehouses : $warehouses->toArray();
                                                    if (count($warehousesArray) > 0) {
                                                        $warehouseId = array_key_first($warehousesArray);
                                                    }
                                                }
                                                
                                                // Get sub-product by product_no (which is $id)
                                                // Try with warehouse_id first, then without if not found
                                                $subProduct = null;
                                                if ($warehouseId) {
                                                    $subProduct = \App\Models\SubProduct::where('chassis_no', $id)
                                                        ->where('warehouse_id', $warehouseId)
                                                        ->latest()
                                                        ->first();
                                                }
                                                
                                                // Fallback: try without warehouse_id if not found
                                                if (!$subProduct) {
                                                    $subProduct = \App\Models\SubProduct::where('chassis_no', $id)
                                                        ->latest()
                                                        ->first();
                                                }
                                                
                                                // Get parent product
                                                $product = null;
                                                if ($subProduct) {
                                                    $product = $subProduct->productService;
                                                } else {
                                                    // Fallback: try to find product by ID from details
                                                    $product = \App\Models\ProductService::find($details['id'] ?? null);
                                                }
                                                
                                                $image_url =
                                                    !empty($product) && isset($product->pro_image)
                                                        ? $product->pro_image
                                                        : 'uploads/pro_image/';
                                                $total += $details['subtotal'];
                                                
                                                // Always use sale price from sub-product (sale_price from Excel - no VAT)
                                                // This is the main selling price without VAT
                                                $basePrice = 0;
                                                if ($subProduct && isset($subProduct->sale_price)) {
                                                    $basePrice = (float) $subProduct->sale_price;
                                                } elseif (isset($details['price'])) {
                                                    // Fallback: use price from cart details if sub-product not found
                                                    $basePrice = (float) $details['price'];
                                                }
                                            @endphp
                                            <tr data-product-id="{{ $id }}"
                                                id="product-id-{{ $id }}">
                                                <td class="cart-images">
                                                    <img alt="Image placeholder"
                                                        src="{{ URL::to('/') . '/' . 'storage/uploads/pro_image' . '/' . $image_url }}"
                                                        class="card-image avatar rounded-circle-sale shadow hover-shadow-lg">
                                                </td>
                                                <td class="name">{{ $details['name'] }}</td>
                                                <td>
                                                    <span class="quantity buttons_added">
                                                        
                                                        <input type="button" value="-" class="minus">
                                                        <input type="number" step="1" min="1"
                                                            
                                                            max="{{ $details['originalquantity'] }}"
                                                            name="quantity" title="{{ __('Quantity') }}"
                                                            class="input-number" data-url="{{ url('update-cart/') }}"
                                                            data-id="{{$id}}" size="4"
                                                            value="{{ $details['quantity'] }}">
                                                        <input type="button" value="+" class="plus"> 
                                                    </span>
                                                </td>
                                                
                                                <td class="price text-right" data-base-price="{{ $basePrice }}">
                                                    {{-- Main price: Sale price from sub-product (without VAT) --}}
                                                    <div class="fw-bold base-price">{{ Auth::user()->priceFormat($basePrice) }}</div>
                                                    {{-- Informational: Price with VAT (calculated based on selected tax) --}}
                                                    <div class="price-with-vat text-muted small mt-1" style="font-size: 0.75rem; display: none;">
                                                        <i class="ti ti-info-circle" style="font-size: 0.7rem;"></i> 
                                                        <span class="vat-price-amount"></span>
                                                        <span class="text-muted">{{ __('(incl. VAT)') }}</span>
                                                    </div>
                                                </td>
                                                
                                                <td>
                                                    <span class="quantity buttons_added">
                                                        <input type="number" step="1" min="0" max="100" name="discount" title="{{ __('Discount') }}" class="input-number"  data-id="{{$id}}" size="4" value="{{ $details['discount'] }}">
                                                    </span>
                                                </td>

                                                <td class="col-sm-3 mt-2">
                                                    <span id="{{$id}}"
                                                        class="subtotal">{{ Auth::user()->priceFormat($details['subtotal']) }}</span>
                                                </td>
                                                <td class="col-sm-2 mt-2">
                                                    <a href="#" class="action-btn bg-danger bs-pass-para-pos"
                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="delete-form-{{ $id }}"
                                                        title="{{ __('Delete') }}" data-id="{{ $id }}">
                                                        <i class="ti ti-trash text-white mx-3 btn btn-sm"
                                                            title="{{ __('Delete') }}"></i>
                                                    </a>
                                                    <form method="POST" action="{{ url('remove-from-cart') }}"
                                                        id="delete-form-{{ $id }}">
                                                        @csrf <!-- Add CSRF token for Laravel -->
                                                        @method('DELETE') <!-- Specify the HTTP method -->

                                                        <input type="hidden" name="session_key"
                                                            value="{{ $lastsegment }}">
                                                        <input type="hidden" name="id"
                                                            value="{{ $id }}">
                                                    </form>
                                                </td>
                                                <td class="combo">
                                                    
                                                        @if ( $details['compo_id'] != 0 )
                                                            <span class="badge bg-success">{{ $details['compo_id'] }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ __('No combo') }}</span>
                                                        @endif
                                                    
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="text-center no-found">
                                            <td colspan="7">{{ __('No Data Found.!') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-center">{{ __('Voucher ID') }}</th>
                                        <th class="text-center">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="vouchers_tbody">
                                    @if (session('vouchers') && !empty(session('vouchers')) && count(session('vouchers')) > 0)
                                        @foreach (session('vouchers') as $id => $details)
                                            <tr data-voucher-id="{{ $id }}"
                                                id="voucher-id-{{ $id }}">
                                               
                                                <td class="name text-center">{{ $id }}</td>
                                                <td class="amount text-center vamou">
                                                    {{ Auth::user()->priceFormat($details['amount']) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="text-center no-found">
                                            <td colspan="7">{{ __('No vouchers Found.!') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="total-section mt-3">
                            
                            <div class="sub-total">
                                
                                <div class="d-flex text-end justify-content-end">
                                    <h6 class="mb-0 text-dark">{{ __('Sub Total') }} :</h6>
                                    <h6 class="mb-0 text-dark subtotal_price" id="displaytotal">
                                        @if ($total_vouchers)
                                            
                                            {{ Auth::user()->priceFormat($total) }}
                                        
                                        @else
                                            {{ Auth::user()->priceFormat($total) }}
                                        @endif
                                        
                                    </h6>
                                </div>
                                </br>

                                <div id="users_search" class="form-group col-md-6">
                                    <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                    <select name="tax_id" id="choices-multiple1"
                                        class="form-control select2 custom-select">
                                        @php
                                            // Get first warehouse's tax_id if available
                                            $firstWarehouseId = null;
                                            $firstWarehouseTaxId = null;
                                            if (!empty($warehouses) && count($warehouses) > 0) {
                                                $warehousesArray = is_array($warehouses) ? $warehouses : $warehouses->toArray();
                                                if (count($warehousesArray) > 0) {
                                                    $firstWarehouseId = array_key_first($warehousesArray);
                                                    $firstWarehouseTaxId = $warehousesWithTax[$firstWarehouseId] ?? null;
                                                }
                                            }
                                            // Determine default tax: warehouse tax if available, otherwise first tax
                                            $defaultTaxId = $firstWarehouseTaxId ?? null;
                                            if (!$defaultTaxId && !empty($tax)) {
                                                $taxArray = is_array($tax) ? $tax : $tax->toArray();
                                                foreach ($taxArray as $key => $value) {
                                                    if ($key != '') {
                                                        $defaultTaxId = $key;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @foreach ($tax as $key => $value)
                                            @if($key != '' && $key == $defaultTaxId)
                                                <option value="{{ $key }}" selected>{{ $value }}</option>
                                            @else
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="tax_hidden" id="tax_hidden" value="{{ $defaultTaxId ?? '' }}">
                                </div>
                                </br>
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <div class="d-flex text-end justify-content-end align-items-center">
                                            <span
                                                class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                                            <input type="number" name="voucher" id="voucher"
                                                class="form-control voucher" required
                                                placeholder="{{ __('Voucher') }}">
                                            <input type="hidden" name="voucher_hidden" id="voucher_hidden">
                                        </div>
                                    </div>
                                </div>
                                

                                <div class="d-flex text-end justify-content-end" style="margin-right: 14px;">
                                    <h6 class="mb-0 text-dark">{{ __('Tax') }} :</h6>
                                    <h6 class="mb-0 text-dark tax_val" id="tax_val">0.00</h6>
                                </div>
                                </br>
                                <div class="col-6" style="max-width: 100% !important;width: 100%;">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <h6 class="">{{ __('Total') }} :</h6>
                                        <h6 class="totalamount">
                                            @if ($total_vouchers)
                                            
                                                {{ Auth::user()->priceFormat($total-$total_vouchers) }}
                                            
                                            @else
                                                {{ Auth::user()->priceFormat($total) }}
                                            @endif
                                        </h6>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between pt-3" id="btn-pur">
                                    <button type="button" class="btn btn-primary rounded" id="pay-button" data-ajax-popup="true"
                                        data-size="xl" data-align="centered" data-url="{{ route('pos.create') }}"
                                        data-title="{{ __('POS Invoice') }}"
                                        @if (session($lastsegment) && !empty(session($lastsegment)) && count(session($lastsegment)) > 0) @else disabled="disabled" @endif>
                                        {{ __('PAY') }}
                                    </button>
                                    <div class="tab-content btn-empty text-end">
                                        <a href="#" class="btn btn-danger bs-pass-para-pos rounded m-0"
                                            data-toggle="tooltip" data-original-title="{{ __('Empty Cart') }}"
                                            data-confirm="{{ __('Are You Sure?') }}"
                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                            data-confirm-yes="delete-form-emptycart">{{ __('Empty Cart') }}
                                        </a>
                                        <form method="post" action="{{ url('empty-cart') }}"
                                            id="delete-form-emptycart">
                                            @csrf <!-- Add CSRF token for Laravel -->
                                            <input type="hidden" name="session_key" value="{{ $lastsegment }}"
                                                id="empty_cart">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>


    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
    </div>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white  fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"> </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

</body>
    <!-- Required Js -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/dash.js') }}"></script>
    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap-switch-button.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>

   
    <!-- Apex Chart -->
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>
    
    <!-- Select2 CSS and JS for POS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script src="{{ asset('js/jscolor.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>

    @if ($message = Session::get('success'))
        <script>
            show_toastr('success', '{!! $message !!}');
        </script>
    @endif
    @if ($message = Session::get('error'))
        <script>
            show_toastr('error', '{!! $message !!}');
        </script>
    @endif
    @stack('script-page')

    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>

    <script>
        // Performance optimization: Enable debug mode only in development
        var DEBUG_MODE = false; // Set to true for debugging
        
        // Performance: Cache frequently used selectors
        var $cache = {};
        function getCached(selector) {
            if (!$cache[selector]) {
                $cache[selector] = $(selector);
            }
            return $cache[selector];
        }
        
        // Performance: Log function that only runs in debug mode
        function debugLog() {
            if (DEBUG_MODE && console && console.log) {
                console.log.apply(console, arguments);
            }
        }
        
        let TotalTax = 0;
        let VATAmount = 0;
        let productId = 0;
        var vatType = '';
        var site_vat_calculation = '{{ $setting['site_vat_calculation'] }}';
        
        // Performance: Store active AJAX requests for cancellation
        var activeAjaxRequests = {
            productSearch: null,
            barcodeSearch: null,
            customerSearch: null,
            cartUpdate: null,
            discountUpdate: null
        };
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Function to clear everything on page refresh
        function clearEverythingOnRefresh() {
            debugLog('Clearing everything on page refresh...');
            
            var session_key = $("#empty_cart").val();
            
            // Clear all sessions via AJAX
            if (session_key) {
                $.ajax({
                    type: 'POST',
                    url: '{{ route('warehouse-empty-cart') }}',
                    data: {
                        'session_key': session_key,
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    async: false, // Make synchronous to ensure it completes before page continues
                    success: function(data) {
                        debugLog('All sessions cleared on page refresh');
                    },
                    error: function(err) {
                        if (DEBUG_MODE) console.error('Error clearing sessions on refresh:', err);
                    }
                });
            }
            
            // Clear cart display
            $("#tbody").empty();
            $("#tbody").html(
                '<tr class="text-center no-found"><td colspan="7">{{ __('No Data Found.!') }}</td></tr>'
            );
            
            // Clear vouchers table
            $("#vouchers_tbody").empty();
            $("#vouchers_tbody").html(
                '<tr class="text-center no-found"><td colspan="7">{{ __('No vouchers Found.!') }}</td></tr>'
            );
            
            // Reset totals
            $('#displaytotal').text('0.00');
            $('.totalamount').text('0.00');
            $('.tax_val').text('0.00');
            
            // Clear discount
            $('.discount').val('');
            $("#discount_hidden").val('');
            
            // Clear voucher input
            $('#voucher').val('');
            $('#voucher_hidden').val('');
            
            // Reset tax based on warehouse tax_id, or first option as fallback (destroy Select2 first to prevent duplicates)
            var taxSelect = $('#choices-multiple1');
            
            // Destroy Select2 if already initialized
            if (taxSelect.hasClass('select2-hidden-accessible')) {
                try {
                    taxSelect.select2('destroy');
                } catch(e) {
                    if (DEBUG_MODE) console.warn('Error destroying tax Select2:', e);
                }
            }
            
            // Remove ALL duplicate options (keep only unique values by both value and text)
            var seenOptions = {};
            var optionsToKeep = [];
            
            taxSelect.find('option').each(function() {
                var $option = $(this);
                var value = $option.val();
                var text = $option.text().trim();
                var key = value + '|' + text;
                
                // Skip empty placeholder option
                if (value === '' || value === null) {
                    optionsToKeep.push($option[0].outerHTML);
                    return;
                }
                
                // Only keep first occurrence of each unique value+text combination
                if (!seenOptions[key]) {
                    seenOptions[key] = true;
                    optionsToKeep.push($option[0].outerHTML);
                }
            });
            
            // Clear and rebuild options
            taxSelect.empty();
            optionsToKeep.forEach(function(html) {
                taxSelect.append(html);
            });
            
            // Try to use warehouse tax first
            var warehousesWithTax = @json($warehousesWithTax ?? []);
            var currentWarehouseId = $('#warehouse').val();
            var warehouseTaxId = warehousesWithTax[currentWarehouseId];
            var selectedTaxId = null;
            
            if (warehouseTaxId) {
                var taxOption = taxSelect.find('option[value="' + warehouseTaxId + '"]');
                if (taxOption.length > 0) {
                    selectedTaxId = warehouseTaxId;
                }
            }
            
            // Fallback to first tax option if warehouse has no tax or tax not found
            if (!selectedTaxId) {
                var firstTaxOption = taxSelect.find('option:not([value=""]):first');
                if (firstTaxOption.length > 0) {
                    selectedTaxId = firstTaxOption.val();
                }
            }
            
            if (selectedTaxId) {
                taxSelect.val(selectedTaxId);
                $("#tax_hidden").val(selectedTaxId);
            } else {
                taxSelect.val(null);
                $("#tax_hidden").val('');
            }
            
            // Reinitialize Select2 after cleaning
            if (typeof $.fn.select2 !== 'undefined') {
                taxSelect.select2({
                    theme: 'default',
                    width: '100%',
                    allowClear: false
                });
            }
            
            // Reset TotalTax and vatType
            TotalTax = 0;
            vatType = '';
            
            // Clear customer selection (will be reloaded by loadFirstCustomer)
            $('#customer_search').val('');
            $('#customer_id').val('');
            $('#vc_name_hidden').val('');
            
            // Disable pay button
            $('#btn-pur button').attr('disabled', 'disabled');
            $('.btn-empty button').removeClass('btn-clear-cart');
            
            debugLog('Everything cleared successfully');
        }

        $(document).ready(function() {
            debugLog('=== Document Ready ===');
            debugLog('POS page ready - checking Select2 availability');
            debugLog('jQuery version:', $.fn.jquery);
            debugLog('Select2 available:', typeof $.fn.select2 !== 'undefined');
            
            // Clear everything on page refresh/load
            clearEverythingOnRefresh();
            
            // Initialize tax values after clearing (clearEverythingOnRefresh already sets the tax dropdown)
            setTimeout(function() {
                var warehousesWithTax = @json($warehousesWithTax ?? []);
                var firstWarehouseId = $('#warehouse').val();
                var taxSelect = $('#choices-multiple1');
                var selectedTaxId = taxSelect.val();
                
                if (selectedTaxId && firstWarehouseId) {
                    // Initialize tax values from the selected tax
                    var taxData = <?php echo json_encode($fullTax); ?>;
                    var taxIdInt = parseInt(selectedTaxId);
                    if (!isNaN(taxIdInt)) {
                        for (let j = 0; j < taxData.length; j++) {
                            if (taxData[j].id === taxIdInt) {
                                TotalTax = parseInt(taxData[j].rate) || 0;
                                vatType = taxData[j].type || '';
                                debugLog('Tax initialized on page load - ID:', taxIdInt, 'Rate:', TotalTax, 'Type:', vatType, 'From warehouse:', warehousesWithTax[firstWarehouseId] == taxIdInt ? 'yes' : 'no');
                                break;
                            }
                        }
                    }
                }
            }, 500);
            
            // Auto-focus barcode search input
            var $barcodeInput = $('#searchbarcode');
            
            // Function to focus barcode input
            function focusBarcodeInput() {
                if ($barcodeInput.length > 0) {
                    setTimeout(function() {
                        $barcodeInput.focus();
                        debugLog('Barcode input focused');
                    }, 100);
                }
            }
            
            // Focus barcode input on page load
            focusBarcodeInput();
            
            // Prevent form submission on Enter key globally (to prevent page refresh)
            $(document).on('keydown', 'form', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    // Allow Enter in textareas
                    if ($(e.target).is('textarea')) {
                        return true;
                    }
                    // Prevent form submission for all other inputs
                    e.preventDefault();
                    return false;
                }
            });
            
            // Refocus after product is added to cart
            $(document).on('ajaxSuccess', function(event, xhr, settings) {
                // Check if it's a cart add/update operation
                if (settings.url && (settings.url.includes('add-to-cart') || settings.url.includes('update-cart'))) {
                    setTimeout(function() {
                        focusBarcodeInput();
                    }, 300);
                }
            });
            
            // Refocus after barcode is processed and input is cleared
            // This is handled in searchBarcodeAndAdd function where input is cleared
            
            // Refocus when clicking outside but not on other inputs/modals
            $(document).on('click', function(e) {
                // Don't refocus if clicking on:
                // - The barcode input itself
                // - Manual barcode search input
                // - Customer search input
                // - Product search input
                // - Modal elements
                // - Select2 dropdowns
                // - Buttons
                var $target = $(e.target);
                if (!$target.closest('#searchbarcode, #searchbarcode_manual, #customer_search, #searchproduct, .modal, .select2-container, button, .btn, input[type="button"], .toacart').length) {
                    // Only refocus if no other input is focused
                    if (!$(':focus').is('input, textarea, select')) {
                        setTimeout(function() {
                            focusBarcodeInput();
                        }, 200);
                    }
                }
            });
            
            // Refocus after modal closes and reinitialize Select2 dropdowns
            $('#commonModal').on('hidden.bs.modal', function() {
                // Reinitialize Select2 dropdowns (warehouse and tax) after modal closes
                // Modal interactions can break Select2 event handlers
                setTimeout(function() {
                    if (typeof window.reinitializeSelect2 === 'function') {
                        debugLog('Modal closed - reinitializing Select2 dropdowns...');
                        window.reinitializeSelect2();
                    }
                }, 200);
                
                // Refocus barcode input
                setTimeout(function() {
                    focusBarcodeInput();
                }, 300);
            });
            
            // Initialize customer search with simple autocomplete
            var customerSearchInput = $('#customer_search');
            var customerSearchResults = $('#customer_search_results');
            var customerIdInput = $('#customer_id');
            var customerNameHidden = $('#vc_name_hidden');
            var searchTimeout;
            var selectedCustomerIndex = -1;
            var currentResults = [];
            var defaultCustomer = null; // Store first customer for restoration
            var previousCustomerId = null; // Track previous customer ID to detect changes

            if (customerSearchInput.length === 0) {
                if (DEBUG_MODE) console.error('Customer search input not found!');
            } else {
                debugLog('Initializing customer search...');
                
                // Function to clear vouchers when customer changes
                function clearVouchersOnCustomerChange() {
                    // Check if there are any vouchers to clear
                    var hasVouchers = $("#vouchers_tbody tr:not(.no-found)").length > 0;
                    if (!hasVouchers) {
                        // Check if vouchers exist in session by checking the table content
                        var vouchersTableHtml = $("#vouchers_tbody").html();
                        hasVouchers = vouchersTableHtml && vouchersTableHtml.indexOf('No vouchers Found') === -1 && vouchersTableHtml.trim() !== '';
                    }
                    
                    if (!hasVouchers) {
                        debugLog('No vouchers to clear');
                        return;
                    }
                    
                    // Clear vouchers from session via AJAX
                    $.ajax({
                        type: 'POST',
                        url: '{{ route("vouchers.clear") }}',
                        data: {
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            debugLog('Vouchers cleared from session');
                            
                            // Clear vouchers table in UI
                            $("#vouchers_tbody").empty();
                            $("#vouchers_tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __("No vouchers Found.!") }}</td></tr>'
                            );
                            
                            // Clear voucher input
                            $('#voucher').val('');
                            $('#voucher_hidden').val('');
                            
                            // Recalculate totals (vouchers are now cleared)
                            updateCartTotals();
                            
                            // Show notification
                            if (typeof show_toastr !== 'undefined') {
                                show_toastr('info', '{{ __("Vouchers cleared due to customer change") }}', 'info');
                            }
                        },
                        error: function(err) {
                            if (DEBUG_MODE) console.error('Error clearing vouchers:', err);
                            // Still clear UI even if AJAX fails
                            $("#vouchers_tbody").empty();
                            $("#vouchers_tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __("No vouchers Found.!") }}</td></tr>'
                            );
                            $('#voucher').val('');
                            $('#voucher_hidden').val('');
                            updateCartTotals();
                        }
                    });
                }

                // Function to search customers
                function searchCustomers(searchTerm) {
                    if (!searchTerm || searchTerm.trim() === '') {
                        customerSearchResults.hide().empty();
                        return;
                    }

                    // Performance: Cancel previous request if still pending
                    if (activeAjaxRequests.customerSearch) {
                        activeAjaxRequests.customerSearch.abort();
                    }

                    activeAjaxRequests.customerSearch = $.ajax({
                        url: '{{ route("customer.search") }}',
                        type: 'GET',
                        dataType: 'json',
                        data: { q: searchTerm },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(data) {
                            currentResults = data.results || [];
                            displaySearchResults(currentResults);
                            activeAjaxRequests.customerSearch = null;
                        },
                        error: function(xhr, status, error) {
                            if (status !== 'abort') {
                                if (DEBUG_MODE) console.error('Customer search error:', error);
                                customerSearchResults.html('<div class="no-results">{{ __('Error searching customers') }}</div>').show();
                            }
                            activeAjaxRequests.customerSearch = null;
                        }
                    });
                }

                // Function to display search results
                function displaySearchResults(results) {
                    customerSearchResults.empty();
                    selectedCustomerIndex = -1;

                    if (results.length === 0) {
                        customerSearchResults.html('<div class="no-results">{{ __('No customers found') }}</div>').show();
                        return;
                    }

                    results.forEach(function(customer, index) {
                        var item = $('<div class="customer-item" data-index="' + index + '" data-id="' + customer.id + '">' +
                            '<div class="customer-name">' + customer.text + '</div>' +
                            '</div>');
                        customerSearchResults.append(item);
                    });

                    customerSearchResults.show();
                }

                // Handle input typing
                customerSearchInput.on('input', function() {
                    var searchTerm = $(this).val().trim();
                    var currentCustomerId = customerIdInput.val();
                    
                    // Clear previous timeout
                    clearTimeout(searchTimeout);
                    
                    // Clear customer selection when user starts typing/clearing
                    if (searchTerm === '') {
                        customerSearchResults.hide().empty();
                        
                        // If customer was cleared and there were vouchers, clear them
                        if (currentCustomerId && previousCustomerId !== null && currentCustomerId === previousCustomerId) {
                            var hasVouchers = $("#vouchers_tbody tr:not(.no-found)").length > 0 || 
                                             ($("#vouchers_tbody").html() && $("#vouchers_tbody").html().indexOf('No vouchers Found') === -1);
                            if (hasVouchers) {
                                clearVouchersOnCustomerChange();
                            }
                            previousCustomerId = null;
                        }
                        
                        // Clear customer selection to allow new search
                        customerIdInput.val('');
                        customerNameHidden.val('');
                        currentResults = [];
                        return;
                    }

                    // Debounce search - wait 300ms after user stops typing
                    searchTimeout = setTimeout(function() {
                        searchCustomers(searchTerm);
                    }, 300);
                });

                // Handle keyboard navigation
                customerSearchInput.on('keydown', function(e) {
                    if (!customerSearchResults.is(':visible') || currentResults.length === 0) {
                        return;
                    }

                    var items = customerSearchResults.find('.customer-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedCustomerIndex = Math.min(selectedCustomerIndex + 1, items.length - 1);
                        items.removeClass('selected').eq(selectedCustomerIndex).addClass('selected');
                        items.eq(selectedCustomerIndex)[0].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedCustomerIndex = Math.max(selectedCustomerIndex - 1, -1);
                        items.removeClass('selected');
                        if (selectedCustomerIndex >= 0) {
                            items.eq(selectedCustomerIndex).addClass('selected');
                            items.eq(selectedCustomerIndex)[0].scrollIntoView({ block: 'nearest' });
                        }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (selectedCustomerIndex >= 0 && selectedCustomerIndex < items.length) {
                            items.eq(selectedCustomerIndex).click();
                        }
                    } else if (e.key === 'Escape') {
                        customerSearchResults.hide();
                        selectedCustomerIndex = -1;
                    }
                });

                // Handle customer selection
                customerSearchResults.on('click', '.customer-item', function() {
                    var customerId = $(this).data('id');
                    var customer = currentResults.find(function(c) { return c.id == customerId; });
                    
                    if (customer) {
                        // Get current customer ID before updating
                        var currentCustomerId = customerIdInput.val();
                        
                        // Check if customer has actually changed (not just re-selected same customer)
                        var customerChanged = previousCustomerId !== null && 
                                             previousCustomerId !== customerId && 
                                             currentCustomerId !== customerId;
                        
                        // Update customer fields
                        customerSearchInput.val(customer.text);
                        customerIdInput.val(customer.id);
                        customerNameHidden.val(customer.id);
                        customerSearchResults.hide();
                        selectedCustomerIndex = -1;
                        
                        debugLog('Customer selected:', customer, 'Previous:', previousCustomerId, 'Current:', currentCustomerId, 'Changed:', customerChanged);
                        
                        // Clear vouchers if customer changed (and there were vouchers)
                        if (customerChanged) {
                            var hasVouchers = $("#vouchers_tbody tr:not(.no-found)").length > 0 || 
                                             ($("#vouchers_tbody").html() && $("#vouchers_tbody").html().indexOf('No vouchers Found') === -1);
                            if (hasVouchers) {
                                clearVouchersOnCustomerChange();
                            }
                        }
                        
                        // Update previous customer ID
                        previousCustomerId = customerId;
                    }
                });

                // Hide results when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#users_search').length) {
                        customerSearchResults.hide();
                        
                        // Restore default customer only if input is empty and no customer selected
                        var searchTerm = customerSearchInput.val().trim();
                        if (searchTerm === '' && !customerIdInput.val()) {
                            if (defaultCustomer) {
                                var oldCustomerId = customerIdInput.val();
                                customerSearchInput.val(defaultCustomer.text);
                                customerIdInput.val(defaultCustomer.id);
                                customerNameHidden.val(defaultCustomer.id);
                                
                                // Clear vouchers if customer was changed
                                if (oldCustomerId && oldCustomerId !== defaultCustomer.id && previousCustomerId !== null) {
                                    var hasVouchers = $("#vouchers_tbody tr:not(.no-found)").length > 0 || 
                                                     ($("#vouchers_tbody").html().indexOf('No vouchers Found') === -1);
                                    if (hasVouchers) {
                                        clearVouchersOnCustomerChange();
                                    }
                                }
                                previousCustomerId = defaultCustomer.id;
                            } else {
                                loadFirstCustomer();
                            }
                        }
                    }
                });

                // Focus search input when clicking on it - allow clearing
                customerSearchInput.on('focus', function() {
                    var searchTerm = $(this).val().trim();
                    if (searchTerm && currentResults.length > 0) {
                        customerSearchResults.show();
                    }
                    // Allow user to select all text for easy clearing
                    $(this).select();
                });

                debugLog('Customer search initialized successfully');
                
                // Load and set first customer as default
                loadFirstCustomer();
                
                // Set initial previous customer ID after first customer is loaded
                setTimeout(function() {
                    previousCustomerId = customerIdInput.val();
                }, 500);
            }
            
            // Cashiers are now loaded from the controller and displayed in the dropdown
            // No AJAX needed - they're already available in the $users variable
            
            // Function to load and set first customer as default
            function loadFirstCustomer() {
                // Only load if no customer is already selected
                if (customerIdInput.val() && customerIdInput.val() !== '') {
                    debugLog('Customer already selected, skipping first customer load');
                    // Set previous customer ID to current value
                    previousCustomerId = customerIdInput.val();
                    return;
                }
                
                $.ajax({
                    url: '{{ route("customer.search") }}',
                    type: 'GET',
                    dataType: 'json',
                    data: { q: '' }, // Empty search to get all customers (limited to 50)
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(data) {
                        var customers = data.results || [];
                        if (customers.length > 0) {
                            // Get first customer
                            var firstCustomer = customers[0];
                            
                            // Store as default for restoration (use outer scope variable)
                            defaultCustomer = firstCustomer;
                            
                            // Set customer in input and hidden fields
                            customerSearchInput.val(firstCustomer.text);
                            customerIdInput.val(firstCustomer.id);
                            customerNameHidden.val(firstCustomer.id);
                            
                            // Set previous customer ID to track changes
                            previousCustomerId = firstCustomer.id;
                            
                            debugLog('First customer set as default:', firstCustomer);
                        } else {
                            debugLog('No customers available to set as default');
                        }
                    },
                    error: function(xhr, status, error) {
                        if (DEBUG_MODE) console.error('Error loading first customer:', error);
                    }
                });
            }

            // Initialize other select2 dropdowns (destroy first to prevent duplicates)
            if (typeof $.fn.select2 !== 'undefined') {
                debugLog('Initializing other Select2 dropdowns...');
                $('.select2').not('.customer_select').each(function() {
                    var $select = $(this);
                    var selectId = $select.attr('id');
                    
                    // Destroy Select2 if already initialized to prevent duplicates
                    if ($select.hasClass('select2-hidden-accessible')) {
                        try {
                            $select.select2('destroy');
                        } catch(e) {
                            if (DEBUG_MODE) console.warn('Error destroying Select2 for', selectId, e);
                        }
                    }
                    
                    // Remove duplicate options before initializing (especially for tax dropdown)
                    if (selectId === 'choices-multiple1') {
                        // Completely rebuild options to remove all duplicates
                        var seenOptions = {};
                        var optionsToKeep = [];
                        
                        $select.find('option').each(function() {
                            var $option = $(this);
                            var value = $option.val();
                            var text = $option.text().trim();
                            var key = value + '|' + text;
                            
                            // Keep placeholder option
                            if (value === '' || value === null) {
                                optionsToKeep.push({
                                    value: value || '',
                                    text: text || 'Select Tax',
                                    selected: $option.prop('selected')
                                });
                                return;
                            }
                            
                            // Only keep first occurrence of each unique value+text combination
                            if (!seenOptions[key]) {
                                seenOptions[key] = true;
                                optionsToKeep.push({
                                    value: value,
                                    text: text,
                                    selected: $option.prop('selected')
                                });
                            }
                        });
                        
                        // Clear and rebuild
                        $select.empty();
                        optionsToKeep.forEach(function(opt) {
                            var $newOption = $('<option></option>')
                                .attr('value', opt.value)
                                .text(opt.text);
                            if (opt.selected) {
                                $newOption.prop('selected', true);
                            }
                            $select.append($newOption);
                        });
                        
                        debugLog('Tax dropdown cleaned - kept', optionsToKeep.length, 'unique options');
                    }
                    
                    // Initialize Select2
                    $select.select2({
                        theme: 'default',
                        width: '100%',
                        allowClear: false
                    });
                    
                    // For tax dropdown, add event listener to prevent duplicate rendering
                    if (selectId === 'choices-multiple1') {
                        // Clean on open to prevent duplicates in dropdown
                        $select.on('select2:open', function() {
                            var $select2 = $(this);
                            setTimeout(function() {
                                // Check Select2 results container for duplicates
                                var $results = $('.select2-results__option');
                                var seenTexts = {};
                                $results.each(function() {
                                    var $result = $(this);
                                    var text = $result.text().trim();
                                    if (seenTexts[text]) {
                                        $result.remove();
                                    } else if (text && text !== 'Select Tax') {
                                        seenTexts[text] = true;
                                    }
                                });
                            }, 10);
                        });
                    }
                });
                debugLog('All Select2 dropdowns initialized');
            }
            
            // Fix warehouse select2 width within container
            $('#warehouse').on('select2:open', function() {
                var $container = $(this).closest('.warehouse-select-container');
                if ($container.length) {
                    var select2Container = $(this).data('select2').$container;
                    select2Container.css({
                        'width': '100%',
                        'max-width': '100%'
                    });
                }
            });
            
            // Function to reinitialize Select2 dropdowns (callable from modal)
            window.reinitializeSelect2 = function() {
                debugLog('Reinitializing Select2 dropdowns...');
                if (typeof $.fn.select2 !== 'undefined') {
                    // Always reinitialize warehouse select after modal close
                    // Modal interactions can break Select2 event handlers even if it appears initialized
                    var $warehouse = $('#warehouse');
                    if ($warehouse.length > 0) {
                        try {
                            // Check if options exist
                            var optionCount = $warehouse.find('option').length;
                            if (optionCount === 0) {
                                debugLog('Warehouse select has no options - cannot reinitialize');
                                return;
                            }
                            
                            // Preserve selected value
                            var selectedValue = $warehouse.val();
                            
                            // Always destroy and reinitialize to ensure event handlers are fresh
                            // This fixes issues where Select2 appears initialized but doesn't respond to clicks
                            if ($warehouse.hasClass('select2-hidden-accessible')) {
                                try {
                                    $warehouse.select2('destroy');
                                    debugLog('Destroyed existing Select2 instance');
                                } catch(e) {
                                    if (DEBUG_MODE) console.warn('Error destroying warehouse Select2:', e);
                                    // Continue anyway - might be partially broken
                                }
                            }
                            
                            // Small delay to ensure destroy is complete
                            setTimeout(function() {
                                try {
                                    // Reinitialize
                                    $warehouse.select2({
                                        theme: 'default',
                                        width: '100%',
                                        allowClear: false
                                    });
                                    
                                    // Restore selected value if it existed
                                    if (selectedValue) {
                                        $warehouse.val(selectedValue).trigger('change');
                                    }
                                    
                                    debugLog('Warehouse Select2 reinitialized with', optionCount, 'options');
                                    
                                    // Reattach the width fix handler
                                    $warehouse.off('select2:open').on('select2:open', function() {
                                        var $container = $(this).closest('.warehouse-select-container');
                                        if ($container.length) {
                                            var select2Container = $(this).data('select2').$container;
                                            select2Container.css({
                                                'width': '100%',
                                                'max-width': '100%'
                                            });
                                        }
                                    });
                                    
                                    // Reattach the change handler
                                    $warehouse.off('change').on('change', function(){
                                        var ware_id = $(this).val();
                                        debugLog('Warehouse changed to:', ware_id);
                                        
                                        if (!ware_id) {
                                            if (DEBUG_MODE) console.warn('No warehouse selected');
                                            // Cashiers are company-wide, so no need to clear dropdown
                                            return;
                                        }
                                        
                                        $("#warehouse_name_hidden").val(ware_id);
                                        
                                        // Get URL from search product input
                                        var productSearchUrl = $('#searchproduct').data('url');
                                        
                                        // Refresh product listing with new warehouse
                                        if (productSearchUrl) {
                                            debugLog('Loading products for warehouse:', ware_id);
                                            if (typeof searchProducts === 'function') {
                                                searchProducts(productSearchUrl, '', '0', ware_id);
                                            }
                                        } else {
                                            if (DEBUG_MODE) console.error('Product search URL not found');
                                        }
                                    });
                                    
                                } catch(e) {
                                    if (DEBUG_MODE) console.warn('Error reinitializing warehouse Select2:', e);
                                }
                            }, 50);
                            
                        } catch(e) {
                            if (DEBUG_MODE) console.warn('Error in warehouse Select2 reinitialization:', e);
                        }
                    } else {
                        debugLog('Warehouse select element not found');
                    }
                    
                    // Force reinitialize tax dropdown (choices-multiple1) after modal close
                    // Similar to warehouse, modal interactions can break it even if it appears initialized
                    var $taxSelect = $('#choices-multiple1');
                    if ($taxSelect.length > 0) {
                        try {
                            var taxOptionCount = $taxSelect.find('option').length;
                            if (taxOptionCount > 0) {
                                var taxSelectedValue = $taxSelect.val();
                                
                                // Always destroy and reinitialize tax dropdown after modal close
                                if ($taxSelect.hasClass('select2-hidden-accessible')) {
                                    try {
                                        $taxSelect.select2('destroy');
                                        debugLog('Destroyed tax Select2 instance');
                                    } catch(e) {
                                        if (DEBUG_MODE) console.warn('Error destroying tax Select2:', e);
                                    }
                                }
                                
                                // Small delay to ensure destroy is complete
                                setTimeout(function() {
                                    try {
                                        $taxSelect.select2({
                                            theme: 'default',
                                            width: '100%',
                                            allowClear: false
                                        });
                                        
                                        // Restore selected value if it existed
                                        if (taxSelectedValue) {
                                            $taxSelect.val(taxSelectedValue).trigger('change');
                                        }
                                        
                                        debugLog('Tax Select2 reinitialized with', taxOptionCount, 'options');
                                    } catch(e) {
                                        if (DEBUG_MODE) console.warn('Error reinitializing tax Select2:', e);
                                    }
                                }, 50);
                            }
                        } catch(e) {
                            if (DEBUG_MODE) console.warn('Error in tax Select2 reinitialization:', e);
                        }
                    }
                    
                    // Reinitialize other Select2 dropdowns (if any)
                    $('.select2').not('.customer_select').each(function() {
                        var $select = $(this);
                        var selectId = $select.attr('id');
                        
                        // Skip warehouse and tax as we already handled them
                        if (selectId === 'warehouse' || selectId === 'choices-multiple1') {
                            return;
                        }
                        
                        // Check if options exist
                        var hasOptions = $select.find('option').length > 0;
                        if (!hasOptions) {
                            return; // Skip if no options
                        }
                        
                        // Preserve selected value
                        var selectedValue = $select.val();
                        
                        // Skip if already initialized and working
                        if ($select.hasClass('select2-hidden-accessible')) {
                            try {
                                // Test if Select2 is working
                                var select2Data = $select.data('select2');
                                if (select2Data && select2Data.$container && select2Data.$container.length > 0) {
                                    // Check if container is visible or exists
                                    if (select2Data.$container.is(':visible') || select2Data.$container.length > 0) {
                                        return; // Already working, skip
                                    }
                                }
                            } catch(e) {
                                // Select2 might be broken, reinitialize
                            }
                        }
                        
                        // Destroy if already initialized
                        if ($select.hasClass('select2-hidden-accessible')) {
                            try {
                                $select.select2('destroy');
                            } catch(e) {
                                if (DEBUG_MODE) console.warn('Error destroying Select2 for', selectId, e);
                            }
                        }
                        
                        // Reinitialize
                        try {
                            $select.select2({
                                theme: 'default',
                                width: '100%',
                                allowClear: false
                            });
                            
                            // Restore selected value if it existed
                            if (selectedValue) {
                                $select.val(selectedValue).trigger('change');
                            }
                            
                            debugLog('Select2 reinitialized for:', selectId);
                        } catch(e) {
                            if (DEBUG_MODE) console.warn('Error reinitializing Select2 for', selectId, e);
                        }
                    });
                }
            };

            // Define session_key globally
            var session_key = $("#empty_cart").val();
            
            // Centralized function to calculate and update totals
            function updateCartTotals() {
                var sum = 0.0;
                var voucher_amount = 0.0;
                
                // Calculate sum from all cart items (only from cart table rows, exclude header/no-found rows)
                $('#tbody tr[data-product-id] .subtotal').each(function() {
                    var $subtotal = $(this);
                    // Skip if it's in a header or no-found row
                    if ($subtotal.closest('.no-found').length > 0 || $subtotal.closest('tr').hasClass('no-found')) {
                        return;
                    }
                    // Skip if parent row doesn't have data-product-id (not a cart item)
                    if ($subtotal.closest('tr[data-product-id]').length === 0) {
                        return;
                    }
                    var subtotalText = $subtotal.text().trim();
                    // Remove all non-numeric characters except decimal point and minus sign
                    var cleanedText = subtotalText.replace(/[^\d.-]/g, '');
                    var subtotalValue = parseFloat(cleanedText) || 0;
                    if (!isNaN(subtotalValue) && subtotalValue >= 0) { // Changed > 0 to >= 0 to include zero values
                        sum += subtotalValue;
                        debugLog('Adding subtotal:', subtotalValue, 'from text:', subtotalText, 'cleaned:', cleanedText);
                    } else {
                        debugLog('Skipping invalid subtotal:', subtotalText, 'parsed:', subtotalValue);
                    }
                });
                
                debugLog('Total subtotal calculated:', sum);
                
                // Calculate total vouchers
                $('#vouchers_tbody .vamou').each(function() {
                    var $voucher = $(this);
                    // Skip if it's in a no-found row
                    if ($voucher.closest('.no-found').length > 0) {
                        return;
                    }
                    var voucherText = $voucher.text().trim();
                    var cleanedText = voucherText.replace(/[^\d.-]/g, '');
                    var voucherValue = parseFloat(cleanedText) || 0;
                    if (!isNaN(voucherValue) && voucherValue > 0) {
                        voucher_amount += voucherValue;
                    }
                });
                
                // Round subtotal to 2 decimal places to avoid precision issues
                sum = Math.round(sum * 100) / 100;
                
                // Update subtotal display (sum of all items, no tax, no vouchers)
                $('#displaytotal').text(addCommas(sum.toFixed(2)));
                
                // Calculate tax BEFORE voucher deduction
                var totalAmountValue = 0;
                var taxAmount = 0;
                
                // Ensure TotalTax is a number
                var taxRate = parseFloat(TotalTax) || 0;
                
                if (vatType === 'add' && taxRate > 0) {
                    // Tax is added on top of subtotal
                    taxAmount = sum * (taxRate / 100);
                    // Round tax amount to 2 decimal places (normal rounding)
                    taxAmount = Math.round(taxAmount * 100) / 100;
                    // Calculate subtotal + tax, then apply voucher AFTER tax
                    totalAmountValue = sum + taxAmount - voucher_amount;
                } else if (vatType && taxRate > 0) {
                    // Tax is included in subtotal, extract it for display
                    taxAmount = (sum * (taxRate / 100)) / (1 + (taxRate / 100));
                    // Round tax amount to 2 decimal places (normal rounding)
                    taxAmount = Math.round(taxAmount * 100) / 100;
                    // Since tax is included in sum, apply voucher to the full sum (which includes tax)
                    totalAmountValue = sum - voucher_amount;
                } else {
                    // No tax - just subtract voucher from subtotal
                    totalAmountValue = sum - voucher_amount;
                }
                
                // Ensure total is not negative
                if (totalAmountValue < 0) {
                    totalAmountValue = 0;
                }
                
                // Ensure tax amount is not negative
                if (taxAmount < 0) {
                    taxAmount = 0;
                }
                
                // Update total display - keep exact 2-decimal amount (no rounding to whole number)
                var normalizedTotal = Math.round(totalAmountValue * 100) / 100;
                $('.totalamount').text(addCommas(normalizedTotal.toFixed(2)));
                
                // Update tax display (show tax amount, not rate)
                if (taxAmount > 0) {
                    $('.tax_val').text(addCommas(taxAmount.toFixed(2)));
                } else {
                    $('.tax_val').text('0.00');
                }
                
                debugLog('Totals updated - Subtotal:', sum, 'Tax Rate:', taxRate, 'Tax Amount:', taxAmount, 'Vouchers:', voucher_amount, 'Total:', totalAmountValue);
                
                return {
                    subtotal: sum,
                    tax: taxAmount,
                    vouchers: voucher_amount,
                    total: totalAmountValue
                };
            }
            
            // Update hidden fields when customer is selected
            // If no customer is set, loadFirstCustomer will set the first one
            var customerId = $('#customer_id').val();
            if (customerId) {
                $("#vc_name_hidden").val(customerId);
            } else {
                // If no customer selected, the loadFirstCustomer function will set it
                // This ensures customer is never empty
            }
            $("#warehouse_name_hidden").val($('.warehouse_select').val());
            $("#discount_hidden").val($('.discount').val());
            
            // Cashiers are loaded from controller - no need to load via AJAX
            
            // Initialize tax select - select first non-empty option by default
            // First, ensure no duplicates exist (clean again after Select2 initialization)
            var taxSelect = $('#choices-multiple1');
            
            // Clean duplicates one more time after Select2 might have been initialized
            if (taxSelect.hasClass('select2-hidden-accessible')) {
                try {
                    taxSelect.select2('destroy');
                } catch(e) {
                    if (DEBUG_MODE) console.warn('Error destroying tax Select2:', e);
                }
            }
            
            // Remove duplicates one final time
            var seenOptions = {};
            var optionsToKeep = [];
            taxSelect.find('option').each(function() {
                var $option = $(this);
                var value = $option.val();
                var text = $option.text().trim();
                var key = value + '|' + text;
                
                if (value === '' || value === null) {
                    optionsToKeep.push({
                        value: value || '',
                        text: text || 'Select Tax',
                        selected: $option.prop('selected')
                    });
                    return;
                }
                
                if (!seenOptions[key]) {
                    seenOptions[key] = true;
                    optionsToKeep.push({
                        value: value,
                        text: text,
                        selected: $option.prop('selected')
                    });
                }
            });
            
            // Rebuild if we found duplicates
            if (optionsToKeep.length < taxSelect.find('option').length) {
                taxSelect.empty();
                optionsToKeep.forEach(function(opt) {
                    var $newOption = $('<option></option>')
                        .attr('value', opt.value)
                        .text(opt.text);
                    if (opt.selected) {
                        $newOption.prop('selected', true);
                    }
                    taxSelect.append($newOption);
                });
                debugLog('Tax dropdown cleaned - removed duplicates, kept', optionsToKeep.length, 'options');
            }
            
            var initialTaxValue = taxSelect.val();
            
            // If no value selected or empty, select the first actual tax option (skip "Select Tax" placeholder)
            if (!initialTaxValue || initialTaxValue === '' || initialTaxValue === null) {
                var firstTaxOption = taxSelect.find('option:not([value=""]):first');
                if (firstTaxOption.length > 0) {
                    initialTaxValue = firstTaxOption.val();
                    taxSelect.val(initialTaxValue);
                }
            }
            
            if (initialTaxValue && initialTaxValue !== '' && initialTaxValue !== null) {
                $("#tax_hidden").val(initialTaxValue);
                // Initialize tax values from the selected option
                var taxData = <?php echo json_encode($fullTax); ?>;
                var selectedTaxId = parseInt(initialTaxValue);
                if (!isNaN(selectedTaxId)) {
                    for (let j = 0; j < taxData.length; j++) {
                        if (taxData[j].id === selectedTaxId) {
                            TotalTax = parseInt(taxData[j].rate) || 0;
                            vatType = taxData[j].type || '';
                            debugLog('Initial tax loaded - ID:', selectedTaxId, 'Rate:', TotalTax, 'Type:', vatType);
                            break;
                        }
                    }
                }
                // Trigger change event to calculate tax and update totals
                // Use a small delay to ensure Select2 is initialized
                setTimeout(function() {
                    // Only trigger change if updateCartTotals is defined
                    if (typeof updateCartTotals === 'function') {
                        // Trigger change on tax select - this will call updateCartTotals via the change handler
                        taxSelect.trigger('change');
                        // Also call updatePriceWithVatForAllItems after a short delay to ensure DOM is ready
                        setTimeout(function() {
                            if (typeof updatePriceWithVatForAllItems === 'function') {
                                updatePriceWithVatForAllItems();
                            }
                        }, 300);
                    } else {
                        console.warn('updateCartTotals not defined yet, skipping tax change trigger');
                    }
                }, 200);
            } else {
                $("#tax_hidden").val('');
                TotalTax = 0;
                vatType = '';
                // Reset tax display if no tax selected
                $('.tax_val').text('0.00');
                // Update totals without tax - check if function exists
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals();
                }
            }


            $(function() {
                getProductCategories();
                
                // Initialize totals on page load if cart has items
                setTimeout(function() {
                    if ($('.subtotal').length > 0) {
                        updateCartTotals();
                        // Update price with VAT for all items after tax is initialized (with delay to ensure tax is set)
                        setTimeout(function() {
                            updatePriceWithVatForAllItems();
                        }, 800);
                    }
                }, 500);
            });

            // Load products on page load with selected warehouse
            if ($('#searchproduct').length > 0) {
                var url = $('#searchproduct').data('url');
                var ware_id = $("#warehouse").val() || '0';
                debugLog('Initial load - Warehouse ID:', ware_id);
                searchProducts(url, '', '0', ware_id);
            }
            if ($('#searchbarcode').length > 2) {
                var url = $('#searchbarcode').data('url');
                var ware_id = $("#warehouse").val() || '0';
                searchBarcode(url, '', '0', ware_id);
            }


            // Combined handler for warehouse change
            $('#warehouse, .warehouse_select').on('change', function(){
                var ware_id = $(this).val();
                debugLog('Warehouse changed to:', ware_id);
                
                if (!ware_id) {
                    if (DEBUG_MODE) console.warn('No warehouse selected');
                    // Cashiers are company-wide, so no need to clear dropdown
                    return;
                }
                
                // Cashiers are loaded from controller - no need to load via AJAX
                
                $("#warehouse_name_hidden").val(ware_id);
                
                // Update tax based on warehouse tax_id
                var warehousesWithTax = @json($warehousesWithTax ?? []);
                var warehouseTaxId = warehousesWithTax[ware_id];
                
                if (warehouseTaxId) {
                    var taxSelect = $('#choices-multiple1');
                    var taxOption = taxSelect.find('option[value="' + warehouseTaxId + '"]');
                    
                    if (taxOption.length > 0) {
                        // Warehouse has a tax - select it
                        taxSelect.val(warehouseTaxId).trigger('change');
                        $("#tax_hidden").val(warehouseTaxId);
                        
                        // Initialize tax values from the selected tax
                        var taxData = <?php echo json_encode($fullTax); ?>;
                        var selectedTaxId = parseInt(warehouseTaxId);
                        if (!isNaN(selectedTaxId)) {
                            for (let j = 0; j < taxData.length; j++) {
                                if (taxData[j].id === selectedTaxId) {
                                    TotalTax = parseInt(taxData[j].rate) || 0;
                                    vatType = taxData[j].type || '';
                                    debugLog('Tax updated from warehouse - ID:', selectedTaxId, 'Rate:', TotalTax, 'Type:', vatType);
                                    break;
                                }
                            }
                        }
                    } else {
                        debugLog('Warehouse tax ID not found in tax options:', warehouseTaxId);
                    }
                } else {
                    debugLog('Warehouse has no tax_id, keeping current tax selection');
                }
                
                // Get URL from search product input
                var productSearchUrl = $('#searchproduct').data('url');
                
                // Refresh product listing with new warehouse
                if (productSearchUrl) {
                    debugLog('Loading products for warehouse:', ware_id);
                    searchProducts(productSearchUrl, '', '0', ware_id);
                } else {
                    if (DEBUG_MODE) console.error('Product search URL not found');
                }
                
                // Clear all sessions and cart when warehouse changes
                var session_key = $("#empty_cart").val();
                if (session_key) {
                    // Clear cart and all sessions via warehouse-empty-cart route (now clears everything)
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('warehouse-empty-cart') }}',
                        data: {
                            'session_key': session_key,
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            debugLog('All sessions cleared on warehouse change');
                            
                            // Clear cart display
                            $("#tbody").empty();
                            $("#tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __('No Data Found.!') }}</td></tr>'
                            );
                            
                            // Clear vouchers table
                            $("#vouchers_tbody").empty();
                            $("#vouchers_tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __('No vouchers Found.!') }}</td></tr>'
                            );
                            
                            // Reset totals
                            $('#displaytotal').text('0.00');
                            $('.totalamount').text('0.00');
                            $('.tax_val').text('0.00');
                            
                            // Select tax based on warehouse tax_id, or first VAT option as fallback
                            var taxSelect = $('#choices-multiple1');
                            var warehousesWithTax = @json($warehousesWithTax ?? []);
                            var warehouseTaxId = warehousesWithTax[ware_id];
                            var selectedTaxId = null;
                            
                            // Try to use warehouse tax first
                            if (warehouseTaxId) {
                                var taxOption = taxSelect.find('option[value="' + warehouseTaxId + '"]');
                                if (taxOption.length > 0) {
                                    selectedTaxId = warehouseTaxId;
                                    taxSelect.val(warehouseTaxId);
                                    $("#tax_hidden").val(warehouseTaxId);
                                }
                            }
                            
                            // Fallback to first tax option if warehouse has no tax or tax not found
                            if (!selectedTaxId) {
                                var firstTaxOption = taxSelect.find('option:not([value=""]):first');
                                if (firstTaxOption.length > 0) {
                                    selectedTaxId = firstTaxOption.val();
                                    taxSelect.val(selectedTaxId);
                                    $("#tax_hidden").val(selectedTaxId);
                                }
                            }
                            
                            // Initialize tax values
                            if (selectedTaxId) {
                                var taxData = <?php echo json_encode($fullTax); ?>;
                                var taxIdInt = parseInt(selectedTaxId);
                                if (!isNaN(taxIdInt)) {
                                    for (let j = 0; j < taxData.length; j++) {
                                        if (taxData[j].id === taxIdInt) {
                                            TotalTax = parseInt(taxData[j].rate) || 0;
                                            vatType = taxData[j].type || '';
                                            debugLog('Tax selected on warehouse change - ID:', taxIdInt, 'Rate:', TotalTax, 'Type:', vatType, 'From warehouse:', warehouseTaxId ? 'yes' : 'no');
                                            break;
                                        }
                                    }
                                }
                                
                                // Trigger change event to update totals with tax
                                setTimeout(function() {
                                    taxSelect.trigger('change');
                                }, 200);
                            } else {
                                // No tax options available, clear selection
                                taxSelect.val(null).trigger('change');
                                TotalTax = 0;
                                vatType = '';
                                $("#tax_hidden").val('');
                            }
                            
                            // Clear discount
                            $('.discount').val('');
                            $("#discount_hidden").val('');
                            
                            // Disable pay button
                            $('#btn-pur button').attr('disabled', 'disabled');
                            $('.btn-empty button').removeClass('btn-clear-cart');
                            
                            // Show notification
                            if (typeof show_toastr !== 'undefined') {
                                show_toastr('info', '{{ __('Cart and sessions cleared due to warehouse change') }}', 'info');
                            }
                        },
                        error: function(err) {
                            if (DEBUG_MODE) console.error('Error clearing cart and sessions:', err);
                            // Still clear UI even if AJAX fails
                            $("#tbody").empty();
                            $("#tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __('No Data Found.!') }}</td></tr>'
                            );
                            $("#vouchers_tbody").empty();
                            $("#vouchers_tbody").html(
                                '<tr class="text-center no-found"><td colspan="7">{{ __('No vouchers Found.!') }}</td></tr>'
                            );
                            $('#displaytotal').text('0.00');
                            $('.totalamount').text('0.00');
                            $('.tax_val').text('0.00');
                            
                            // Select tax based on warehouse tax_id, or first VAT option as fallback (even on error)
                            var taxSelect = $('#choices-multiple1');
                            var warehousesWithTax = @json($warehousesWithTax ?? []);
                            var warehouseTaxId = warehousesWithTax[ware_id];
                            var selectedTaxId = null;
                            
                            // Try to use warehouse tax first
                            if (warehouseTaxId) {
                                var taxOption = taxSelect.find('option[value="' + warehouseTaxId + '"]');
                                if (taxOption.length > 0) {
                                    selectedTaxId = warehouseTaxId;
                                    taxSelect.val(warehouseTaxId);
                                    $("#tax_hidden").val(warehouseTaxId);
                                }
                            }
                            
                            // Fallback to first tax option if warehouse has no tax or tax not found
                            if (!selectedTaxId) {
                                var firstTaxOption = taxSelect.find('option:not([value=""]):first');
                                if (firstTaxOption.length > 0) {
                                    selectedTaxId = firstTaxOption.val();
                                    taxSelect.val(selectedTaxId);
                                    $("#tax_hidden").val(selectedTaxId);
                                }
                            }
                            
                            // Initialize tax values
                            if (selectedTaxId) {
                                var taxData = <?php echo json_encode($fullTax); ?>;
                                var taxIdInt = parseInt(selectedTaxId);
                                if (!isNaN(taxIdInt)) {
                                    for (let j = 0; j < taxData.length; j++) {
                                        if (taxData[j].id === taxIdInt) {
                                            TotalTax = parseInt(taxData[j].rate) || 0;
                                            vatType = taxData[j].type || '';
                                            debugLog('Tax selected on warehouse change (error case) - ID:', taxIdInt, 'Rate:', TotalTax, 'Type:', vatType, 'From warehouse:', warehouseTaxId ? 'yes' : 'no');
                                            break;
                                        }
                                    }
                                }
                                
                                // Trigger change event to update totals with tax
                                setTimeout(function() {
                                    taxSelect.trigger('change');
                                }, 200);
                            } else {
                                // No tax options available, clear selection
                                taxSelect.val(null).trigger('change');
                                TotalTax = 0;
                                vatType = '';
                                $("#tax_hidden").val('');
                            }
                            
                            $('.discount').val('');
                            $("#discount_hidden").val('');
                            $('#btn-pur button').attr('disabled', 'disabled');
                            $('.btn-empty button').removeClass('btn-clear-cart');
                        }
                    });
                } else {
                    // If no session key, still clear UI elements
                    $("#tbody").empty();
                    $("#tbody").html(
                        '<tr class="text-center no-found"><td colspan="7">{{ __('No Data Found.!') }}</td></tr>'
                    );
                    $("#vouchers_tbody").empty();
                    $("#vouchers_tbody").html(
                        '<tr class="text-center no-found"><td colspan="7">{{ __('No vouchers Found.!') }}</td></tr>'
                    );
                    $('#displaytotal').text('0.00');
                    $('.totalamount').text('0.00');
                    $('.tax_val').text('0.00');
                    
                    // Select tax based on warehouse tax_id, or first VAT option as fallback
                    var taxSelect = $('#choices-multiple1');
                    var warehousesWithTax = @json($warehousesWithTax ?? []);
                    var warehouseTaxId = warehousesWithTax[ware_id];
                    var selectedTaxId = null;
                    
                    // Try to use warehouse tax first
                    if (warehouseTaxId) {
                        var taxOption = taxSelect.find('option[value="' + warehouseTaxId + '"]');
                        if (taxOption.length > 0) {
                            selectedTaxId = warehouseTaxId;
                            taxSelect.val(warehouseTaxId);
                            $("#tax_hidden").val(warehouseTaxId);
                        }
                    }
                    
                    // Fallback to first tax option if warehouse has no tax or tax not found
                    if (!selectedTaxId) {
                        var firstTaxOption = taxSelect.find('option:not([value=""]):first');
                        if (firstTaxOption.length > 0) {
                            selectedTaxId = firstTaxOption.val();
                            taxSelect.val(selectedTaxId);
                            $("#tax_hidden").val(selectedTaxId);
                        }
                    }
                    
                    // Initialize tax values
                    if (selectedTaxId) {
                        var taxData = <?php echo json_encode($fullTax); ?>;
                        var taxIdInt = parseInt(selectedTaxId);
                        if (!isNaN(taxIdInt)) {
                            for (let j = 0; j < taxData.length; j++) {
                                if (taxData[j].id === taxIdInt) {
                                    TotalTax = parseInt(taxData[j].rate) || 0;
                                    vatType = taxData[j].type || '';
                                    debugLog('Tax selected on warehouse change (no session key) - ID:', taxIdInt, 'Rate:', TotalTax, 'Type:', vatType, 'From warehouse:', warehouseTaxId ? 'yes' : 'no');
                                    break;
                                }
                            }
                        }
                        
                        // Trigger change event to update totals with tax
                        setTimeout(function() {
                            taxSelect.trigger('change');
                        }, 200);
                    } else {
                        // No tax options available, clear selection
                        taxSelect.val(null).trigger('change');
                        TotalTax = 0;
                        vatType = '';
                        $("#tax_hidden").val('');
                    }
                    $('.discount').val('');
                    $("#discount_hidden").val('');
                    $('#btn-pur button').attr('disabled', 'disabled');
                    $('.btn-empty button').removeClass('btn-clear-cart');
                }
            });
            
            // Customer change handler is now handled in Select2 initialization above
            
            // Button to load products from selected warehouse
            $('#load-warehouse-products').on('click', function() {
                var ware_id = $('#warehouse').val();
                
                if (!ware_id) {
                    show_toastr('error', '{{ __('Please select a warehouse first') }}', 'error');
                    return;
                }
                
                // Update hidden field
                $("#warehouse_name_hidden").val(ware_id);
                
                // Get URL from search product input
                var productSearchUrl = $('#searchproduct').data('url');
                
                // Show loading state
                $(this).prop('disabled', true);
                $(this).html('<i class="ti ti-loader"></i> {{ __('Loading...') }}');
                
                // Refresh product listing with selected warehouse
                if (productSearchUrl) {
                    searchProducts(productSearchUrl, '', '0', ware_id);
                }
                
                // Update stock quantities for all items currently in cart
                updateCartStockQuantities(ware_id);
                
                // Reset button after a short delay
                var $btn = $(this);
                setTimeout(function() {
                    $btn.prop('disabled', false);
                    $btn.html('<i class="ti ti-refresh"></i> {{ __('Load') }}');
                }, 1000);
                
                show_toastr('success', '{{ __('Products loaded from selected warehouse') }}', 'success');
            });
            
            // Performance: Optimized function to update stock quantities for all items in cart
            // Uses Promise.all to batch requests instead of sequential AJAX calls
            function updateCartStockQuantities(warehouseId) {
                var $rows = $('#tbody tr[data-product-id]');
                if ($rows.length === 0 || !warehouseId) {
                    return;
                }
                
                // Collect all product IDs and their corresponding elements
                var stockRequests = [];
                var productData = [];
                
                $rows.each(function() {
                    var $row = $(this);
                    var productId = $row.data('product-id');
                    var $quantityInput = $row.find('input[name="quantity"]');
                    
                    if (productId && warehouseId) {
                        productData.push({
                            productId: productId,
                            $row: $row,
                            $quantityInput: $quantityInput
                        });
                        
                        // Create AJAX promise for each product
                        stockRequests.push(
                            $.ajax({
                                url: 'get_free_product_in_warehouse/' + warehouseId + '/' + productId,
                                method: 'GET',
                                timeout: 5000 // 5 second timeout per request
                            })
                        );
                    }
                });
                
                // Performance: Execute all requests in parallel using Promise.all
                if (stockRequests.length > 0) {
                    Promise.all(stockRequests).then(function(responses) {
                        responses.forEach(function(response, index) {
                            var data = productData[index];
                            if (!data) return;
                            
                            var maxQuantity = parseInt(response) || 0;
                            var $quantityInput = data.$quantityInput;
                            var $row = data.$row;
                            
                            // Update max attribute
                            $quantityInput.attr('max', maxQuantity);
                            
                            // Get current quantity
                            var currentQuantity = parseInt($quantityInput.val()) || 0;
                            
                            // If current quantity exceeds new max, set to max
                            if (currentQuantity > maxQuantity) {
                                $quantityInput.val(maxQuantity);
                                // Trigger quantity change to update subtotal
                                $quantityInput.trigger('change');
                            }
                            
                            // Enable/disable plus button based on stock
                            var plusBtn = $row.find('.plus');
                            if (currentQuantity >= maxQuantity || maxQuantity <= 0) {
                                plusBtn.prop('disabled', true);
                            } else {
                                plusBtn.prop('disabled', false);
                            }
                        });
                    }).catch(function(error) {
                        if (DEBUG_MODE) console.error('Error fetching stock quantities:', error);
                    });
                }
            }

            $(document).on('click', '#clearinput', function(e) {
                var IDs = [];
                $(this).closest('div').find("input").each(function() {
                    IDs.push('#' + this.id);
                });
                $(IDs.toString()).val('');
            });
            // Performance: Add debouncing to product search
            var productSearchTimeout;
            $(document).on('keyup', 'input#searchproduct', function() {
                var $input = $(this);
                var url = $input.data('url');
                var value = this.value;
                var cat = $('.cat-active').children().data('cat-id');
                var ware_id = $("#warehouse").val() || '0';
                
                // Clear previous timeout
                clearTimeout(productSearchTimeout);
                
                // Debounce search - wait 400ms after user stops typing
                productSearchTimeout = setTimeout(function() {
                    searchProducts(url, value, cat, ware_id);
                }, 400);
            });
            
            // Manual barcode search (for typing, not scanning) - just displays results, doesn't auto-add
            var manualBarcodeSearchTimeout;
            $(document).on('keyup', 'input#searchbarcode_manual', function() {
                var $input = $(this);
                var url = $input.data('url');
                var value = this.value.trim();
                var cat = $('.cat-active').children().data('cat-id');
                var ware_id = $("#warehouse").val() || '0';
                
                // Clear previous timeout
                clearTimeout(manualBarcodeSearchTimeout);
                
                // If empty, clear results
                if (!value || value === '') {
                    // Optionally show all products or clear
                    var productSearchUrl = $('#searchproduct').data('url');
                    if (productSearchUrl) {
                        searchProducts(productSearchUrl, '', cat, ware_id);
                    }
                    return;
                }
                
                // Debounce search - wait 400ms after user stops typing
                manualBarcodeSearchTimeout = setTimeout(function() {
                    debugLog('Manual barcode search:', value);
                    searchBarcode(url, value, cat, ware_id);
                }, 400);
            });
            
            // Handle Enter key for manual barcode search
            $(document).on('keydown', 'input#searchbarcode_manual', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    var $input = $(this);
                    var url = $input.data('url');
                    var value = $input.val().trim();
                    var cat = $('.cat-active').children().data('cat-id');
                    var ware_id = $("#warehouse").val() || '0';
                    
                    if (value && value.length > 0) {
                        debugLog('Enter pressed - Manual barcode search:', value);
                        searchBarcode(url, value, cat, ware_id);
                    }
                    return false;
                }
            });
            
            // Track barcode input for auto-add functionality
            var barcodeTimeout;
            var lastBarcodeValue = '';
            var barcodeProcessing = false; // Flag to prevent multiple simultaneous requests
            var barcodeEnterPressed = false; // Track if Enter was pressed
            var barcodeInputTimer = null; // Timer to wait for full barcode paste
            
            // Prevent form submission on Enter key
            $(document).on('keydown', 'input#searchbarcode', function(e) {
                // Prevent form submission and page refresh
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    barcodeEnterPressed = true;
                    
                    // Clear any existing timeout
                    clearTimeout(barcodeTimeout);
                    clearTimeout(barcodeInputTimer);
                    
                    // Wait a bit longer to ensure all characters from scanner are captured
                    // Barcode scanners paste very quickly, but we need to wait for the full value
                    var $input = $(this);
                    barcodeInputTimer = setTimeout(function() {
                        var value = $input.val().trim();
                        if (value && value.length > 0 && !barcodeProcessing) {
                            var url = $input.data('url');
                            var cat = $('.cat-active').children().data('cat-id');
                            var ware_id = $("#warehouse").val();
                            
                            debugLog('Enter pressed - Processing barcode:', value);
                            searchBarcodeAndAdd(url, value, cat, ware_id, $input);
                        }
                        barcodeEnterPressed = false;
                    }, 150); // Wait 150ms after Enter to capture full barcode
                    
                    return false;
                }
            });
            
            $(document).on('keyup', 'input#searchbarcode', function(e) {
                // Skip if Enter was just pressed (handled by keydown)
                if (barcodeEnterPressed) {
                    return;
                }
                
                var url = $(this).data('url');
                var value = this.value.trim();
                var cat = $('.cat-active').children().data('cat-id');
                var ware_id = $("#warehouse").val();
                
                // Prevent processing if already processing or if value is empty
                if (barcodeProcessing || !value) {
                    return;
                }
                
                // Clear previous timeout
                clearTimeout(barcodeTimeout);
                
                // For barcode scanners that don't send Enter, wait for input to stop
                // Use longer timeout to ensure full barcode is captured
                barcodeTimeout = setTimeout(function() {
                    // Only auto-add if value is not empty, has changed, and not already processing
                    if (value && value.length > 0 && value !== lastBarcodeValue && !barcodeProcessing && !barcodeEnterPressed) {
                        debugLog('Auto-processing barcode (no Enter):', value);
                        searchBarcodeAndAdd(url, value, cat, ware_id, $('#searchbarcode'));
                    }
                }, 300); // Increased timeout to ensure full barcode is captured
            });
            
            // Handle paste event for barcode scanners that paste the entire code at once
            $(document).on('paste', 'input#searchbarcode', function(e) {
                var $input = $(this);
                
                // Clear any existing timeouts
                clearTimeout(barcodeTimeout);
                clearTimeout(barcodeInputTimer);
                
                // Wait for paste to complete, then process
                setTimeout(function() {
                    var value = $input.val().trim();
                    if (value && value.length > 0 && !barcodeProcessing) {
                        var url = $input.data('url');
                        var cat = $('.cat-active').children().data('cat-id');
                        var ware_id = $("#warehouse").val();
                        
                        debugLog('Paste detected - Processing barcode:', value);
                        searchBarcodeAndAdd(url, value, cat, ware_id, $input);
                    }
                }, 100); // Small delay to ensure paste is complete
            });


            function searchProducts(url, value, cat_id, war_id) {
                // Always get current warehouse ID if not provided or is '0'
                if (!war_id || war_id === '0' || war_id === '') {
                    war_id = $("#warehouse").val() || '0';
                }
                
                debugLog('searchProducts called with:', {
                    'search': value,
                    'cat_id': cat_id,
                    'war_id': war_id,
                    'session_key': session_key
                });
                
                // Performance: Cancel previous product search request
                if (activeAjaxRequests.productSearch) {
                    activeAjaxRequests.productSearch.abort();
                }
                
                // Show loading indicator
                $('#product-listing-loading').show();
                $('#product-listing').hide();
                
                activeAjaxRequests.productSearch = $.ajax({
                    type: 'GET',
                    url: url,
                    data: {
                        'search': value,
                        'cat_id': cat_id,
                        'war_id': war_id,
                        'session_key': session_key
                    },
                    success: function(data) {
                        debugLog('Products loaded successfully for warehouse:', war_id);
                        
                        // Hide loading indicator
                        $('#product-listing-loading').hide();
                        
                        // Performance: Use requestAnimationFrame for smoother DOM updates
                        requestAnimationFrame(function() {
                            var $listing = $('#product-listing');
                            // Clear existing content efficiently
                            $listing.empty();
                            // Use document fragment for better performance with many products
                            var $temp = $('<div>').html(data);
                            $listing.append($temp.contents());
                            $listing.show();
                        });
                        
                        activeAjaxRequests.productSearch = null;
                    },
                    error: function(xhr, status, error) {
                        // Hide loading indicator on error
                        $('#product-listing-loading').hide();
                        $('#product-listing').show();
                        
                        if (status !== 'abort') {
                            if (DEBUG_MODE) {
                                console.error('Error loading products:', error);
                                console.error('Response:', xhr.responseText);
                            }
                        }
                        activeAjaxRequests.productSearch = null;
                    }
                });
            }

            function searchBarcode(url, value, cat_id, war_id = '0') {
                // Performance: Cancel previous barcode search request
                if (activeAjaxRequests.barcodeSearch) {
                    activeAjaxRequests.barcodeSearch.abort();
                }
                
                activeAjaxRequests.barcodeSearch = $.ajax({
                    type: 'GET',
                    url: url,
                    data: {
                        'search': value,
                        'cat_id': cat_id,
                        'war_id': war_id,
                        'session_key': session_key
                    },
                    success: function(data) {
                        debugLog('Barcode search result:', data);
                        // Performance: Use requestAnimationFrame for smoother DOM updates
                        requestAnimationFrame(function() {
                            var $listing = $('#product-listing');
                            $listing.empty();
                            var $temp = $('<div>').html(data);
                            $listing.append($temp.contents());
                        });
                        activeAjaxRequests.barcodeSearch = null;
                    },
                    error: function(xhr, status, error) {
                        if (status !== 'abort' && DEBUG_MODE) {
                            console.error('Barcode search error:', error);
                        }
                        activeAjaxRequests.barcodeSearch = null;
                    }
                });
            }
            
            // Function to search barcode and automatically add to cart
            function searchBarcodeAndAdd(url, value, cat_id, war_id, $input) {
                if (!value || value.trim() === '') {
                    return;
                }
                
                // Prevent multiple simultaneous requests
                if (barcodeProcessing) {
                    debugLog('Barcode processing already in progress, skipping...');
                    return;
                }
                
                // Set processing flag
                barcodeProcessing = true;
                
                // Update last barcode value to prevent duplicate adds
                lastBarcodeValue = value;
                
                // Show loading indicator (optional - can be removed if not needed)
                var originalPlaceholder = $input.attr('placeholder');
                $input.attr('placeholder', '{{ __("Processing...") }}').prop('disabled', true);
                
                // Cancel any previous barcode search request
                if (activeAjaxRequests.barcodeSearch) {
                    activeAjaxRequests.barcodeSearch.abort();
                }
                
                activeAjaxRequests.barcodeSearch = $.ajax({
                    type: 'GET',
                    url: url,
                    data: {
                        'search': value,
                        'cat_id': cat_id || '0',
                        'war_id': war_id || '0',
                        'session_key': session_key
                    },
                    timeout: 30000, // Increased to 30 seconds for large datasets
                    cache: false, // Prevent caching issues
                    success: function(data) {
                        // Reset AJAX request tracking
                        activeAjaxRequests.barcodeSearch = null;
                        
                        // Use a temporary container for faster DOM parsing
                        var $tempContainer = $('<div>').html(data);
                        
                        // Find the first product with .toacart button (more efficient)
                        var $firstProduct = $tempContainer.find('.toacart').first();
                        
                        if ($firstProduct.length > 0) {
                            // Get the add to cart URL
                            var addToCartUrl = $firstProduct.data('url');
                            
                            if (addToCartUrl) {
                                // Performance: Skip updating product listing for barcode auto-add (not needed)
                                // Only update if user explicitly searches, not for auto-add
                                // $('#product-listing').html(data); // Commented out for performance
                                
                                // Clear the barcode input immediately
                                $input.val('').prop('disabled', false).attr('placeholder', originalPlaceholder);
                                lastBarcodeValue = '';
                                
                                // Refocus barcode input after clearing
                                setTimeout(function() {
                                    $input.focus();
                                }, 100);
                                
                                // Cancel any previous cart add request
                                if (activeAjaxRequests.cartUpdate) {
                                    activeAjaxRequests.cartUpdate.abort();
                                }
                                
                                // Trigger add to cart immediately
                                activeAjaxRequests.cartUpdate = $.ajax({
                                    url: addToCartUrl,
                                    timeout: 15000, // Increased to 15 seconds
                                    cache: false,
                                    success: function(cartData) {
                                        // Reset processing flag and AJAX tracking
                                        barcodeProcessing = false;
                                        activeAjaxRequests.cartUpdate = null;
                                        var sum = 0;
                                        if (cartData.code == '200') {
                                            debugLog('Barcode scanned - Product added:', cartData);
                                            
                                            if ('carttotal' in cartData) {
                                                // Update existing items or add new ones
                                                $.each(cartData.carttotal, function(key, value) {
                                                    var $existingRow = $('#product-id-' + value.id);
                                                    if ($existingRow.length > 0) {
                                                        // Product exists, update quantity and subtotal
                                                        $existingRow.find('input[name="quantity"]').val(value.quantity);
                                                        $existingRow.find('.subtotal').text(addCommas(value.subtotal));
                                                        // Preserve price with VAT display if it exists
                                                        // The price with VAT is already in the HTML structure
                                                    } else {
                                                        // New product, check if we have cart HTML for it
                                                        if (cartData.carthtml && cartData.carthtml.indexOf('product-id-' + value.id) !== -1) {
                                                            $('#tbody').append(cartData.carthtml);
                                                            $('.no-found').addClass('d-none');
                                                        }
                                                    }
                                                });
                                                
                                                // Update totals using centralized function
                                                updateCartTotals();
                                            } else {
                                                // First product in cart or single product response
                                                if (cartData.carthtml) {
                                                    // Check if product already exists
                                                    var productId = cartData.product.id;
                                                    var $existingRow = $('#product-id-' + productId);
                                                    
                                                    if ($existingRow.length > 0) {
                                                        // Update existing product - preserve price with VAT display
                                                        $existingRow.find('input[name="quantity"]').val(cartData.product.quantity);
                                                        $existingRow.find('.subtotal').text(addCommas(cartData.product.subtotal));
                                                        // Price with VAT is already in the HTML structure, no need to update it
                                                    } else {
                                                        // Add new product - HTML already includes price with VAT
                                                        $('#tbody').append(cartData.carthtml);
                                                        $('.no-found').addClass('d-none');
                                                    }
                                                    
                                                    // Update totals after DOM is updated
                                                    setTimeout(function() {
                                                        updateCartTotals();
                                                        // Update price with VAT for all items (with delay to ensure DOM is ready)
                                                        setTimeout(function() {
                                                            updatePriceWithVatForAllItems();
                                                        }, 200);
                                                    }, 100);
                                                }
                                            }
                                            
                                            let compo_id = cartData.product.compo_id;
                                            let badgeHtml = '';
                                            
                                            if (compo_id != 0) {
                                                badgeHtml = '<span class="badge bg-success">' + compo_id + '</span>';
                                            } else {
                                                badgeHtml = '<span class="badge bg-secondary">No combo</span>';
                                            }
                                            
                                            $('.carttable #product-id-' + cartData.product.id + ' .combo').html(badgeHtml);
                                            $('.carttable #product-id-' + cartData.product.id + ' input[name="quantity"]').val(cartData.product.quantity);
                                            $('#btn-pur button').removeAttr('disabled');
                                            $('.btn-empty button').addClass('btn-clear-cart');
                                            
                                            // Show success message
                                            if (cartData.success) {
                                                show_toastr('success', cartData.success, 'success');
                                            }
                                            
                                            // Check for multi-product combo offers after adding product
                                            if (cartData.carttotal) {
                                                var cartDataForCombo = {
                                                    carttotal: cartData.carttotal
                                                };
                                                setTimeout(function() {
                                                    checkAndApplyMultiProductCombos(cartDataForCombo);
                                                }, 300);
                                            }
                                            
                                            // Refocus barcode input after successful addition
                                            setTimeout(function() {
                                                $input.focus();
                                            }, 200);
                                        }
                                    },
                                    error: function(errorData) {
                                        // Reset processing flag and AJAX tracking
                                        barcodeProcessing = false;
                                        activeAjaxRequests.cartUpdate = null;
                                        $input.prop('disabled', false).attr('placeholder', originalPlaceholder);
                                        
                                        // Handle timeout specifically
                                        if (errorData.status === 0 || errorData.statusText === 'timeout') {
                                            show_toastr('error', '{{ __("Request timed out. Please try again.") }}', 'error');
                                        } else {
                                            errorData = errorData.responseJSON || {};
                                            if (errorData.error) {
                                                show_toastr('error', errorData.error, 'error');
                                            } else {
                                                show_toastr('error', '{{ __("Error adding product to cart") }}', 'error');
                                            }
                                        }
                                        $input.val('');
                                        lastBarcodeValue = '';
                                        
                                        // Refocus barcode input after error
                                        setTimeout(function() {
                                            $input.focus();
                                        }, 200);
                                    }
                                });
                            } else {
                                // Reset processing flag if URL not found
                                barcodeProcessing = false;
                                $input.prop('disabled', false).attr('placeholder', originalPlaceholder);
                                
                                // Refocus barcode input
                                setTimeout(function() {
                                    $input.focus();
                                }, 200);
                            }
                        } else {
                            // No product found - reset processing flag
                            barcodeProcessing = false;
                            activeAjaxRequests.barcodeSearch = null;
                            $input.prop('disabled', false).attr('placeholder', originalPlaceholder);
                            show_toastr('error', '{{ __("Product not found for barcode") }}: ' + value, 'error');
                            $input.val('');
                            lastBarcodeValue = '';
                            
                            // Refocus barcode input after product not found
                            setTimeout(function() {
                                $input.focus();
                            }, 200);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset processing flag and AJAX tracking on error
                        barcodeProcessing = false;
                        activeAjaxRequests.barcodeSearch = null;
                        $input.prop('disabled', false).attr('placeholder', originalPlaceholder);
                        
                        if (status !== 'abort') {
                            if (DEBUG_MODE) console.error('Barcode search error:', error, 'Status:', status);
                            var errorMsg = '{{ __("Error searching barcode") }}';
                            
                            // Handle different error types
                            if (status === 'timeout') {
                                errorMsg = '{{ __("Barcode search timed out. The database may be large. Please try a more specific barcode or contact support.") }}';
                            } else if (status === 'error' && xhr.status === 0) {
                                errorMsg = '{{ __("Network error. Please check your connection and try again.") }}';
                            } else if (xhr.status >= 500) {
                                errorMsg = '{{ __("Server error. Please try again later.") }}';
                            } else if (xhr.status === 404) {
                                errorMsg = '{{ __("Search endpoint not found. Please refresh the page.") }}';
                            }
                            
                            show_toastr('error', errorMsg, 'error');
                        }
                        $input.val('');
                        lastBarcodeValue = '';
                        
                        // Refocus barcode input after error
                        setTimeout(function() {
                            $input.focus();
                        }, 200);
                    }
                });
            }

            function getProductCategories() {
                $.ajax({
                    type: 'GET',
                    url: '{{ route('product.categories') }}',
                    success: function(data) {
                        // console.log(data);
                        $('#categories-listing').html(data);
                    }
                });
            }

            // Function to check and apply multi-product combo offers
            function checkAndApplyMultiProductCombos(cartData) {
                if (!cartData || !cartData.carttotal) {
                    return;
                }
                
                var warehouseId = $('#warehouse').val();
                if (!warehouseId) {
                    return;
                }
                
                // Get session key from empty_cart input or default to 'pos'
                var sessionKey = $("#empty_cart").val() || 'pos';
                
                // Get all product IDs from cart
                var cartProductIds = [];
                $.each(cartData.carttotal, function(key, item) {
                    // Extract product ID from product_no (P_num format)
                    // We need to get the actual product_service_id from the product_no
                    var productNo = item.id || key;
                    cartProductIds.push(productNo);
                });
                
                if (cartProductIds.length < 2) {
                    // Need at least 2 products for multi-product combo
                    return;
                }
                
                debugLog('Checking for multi-product combos:', {
                    'product_ids': cartProductIds,
                    'warehouse_id': warehouseId,
                    'cart_data': cartData.carttotal,
                    'session_key': sessionKey
                });
                
                // Check for multi-product combo offers via AJAX
                $.ajax({
                    url: '{{ route("combo_offers.check-multi-product") }}',
                    method: 'POST',
                    data: {
                        product_ids: cartProductIds,
                        warehouse_id: warehouseId,
                        cart_data: cartData.carttotal,
                        session_key: sessionKey,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        debugLog('Multi-product combo check response:', response);
                        if (response.success && response.applicable_combos && response.applicable_combos.length > 0) {
                            debugLog('Multi-product combo offers found:', response.applicable_combos);
                            
                            var productsUpdated = 0;
                            
                            // Apply combo offers to ALL matching products
                            $.each(response.applicable_combos, function(index, comboData) {
                                debugLog('Processing combo:', comboData.combo_id, 'with', Object.keys(comboData.products).length, 'products');
                                
                                $.each(comboData.products, function(pid, productData) {
                                    var $row = $('#product-id-' + productData.product_no);
                                    if ($row.length > 0) {
                                        // Update combo badge - only show if combo_id is not 0
                                        // Products that exceed buy_quantity limit should not show combo badge
                                        var badgeHtml = '';
                                        if (productData.combo_id && productData.combo_id != 0) {
                                            badgeHtml = '<span class="badge bg-success">' + productData.combo_id + '</span>';
                                        } else {
                                            badgeHtml = '<span class="badge bg-secondary">{{ __("No combo") }}</span>';
                                        }
                                        $row.find('.combo').html(badgeHtml);
                                        
                                        // Update subtotal if provided
                                        if (productData.subtotal !== undefined) {
                                            var formattedSubtotal = addCommas(parseFloat(productData.subtotal).toFixed(2));
                                            $row.find('.subtotal').text(formattedSubtotal);
                                        }
                                        
                                        if (productData.combo_id && productData.combo_id != 0) {
                                            productsUpdated++;
                                            debugLog('Applied combo', productData.combo_id, 'to product', productData.product_no, '- Subtotal:', productData.subtotal);
                                        } else {
                                            debugLog('Combo cleared for product', productData.product_no, '- exceeds buy_quantity limit');
                                        }
                                    } else {
                                        debugLog('Warning: Row not found for product', productData.product_no);
                                    }
                                });
                            });
                            
                            debugLog('Total products updated with combo:', productsUpdated);
                            
                            // Update totals after applying combos
                            setTimeout(function() {
                                updateCartTotals();
                            }, 100);
                        } else if (response.success && response.updated_cart) {
                            // Even if no combos found, update cart if provided
                            debugLog('No combos found, but updated cart provided');
                        }
                    },
                    error: function(xhr, status, error) {
                        debugLog('Error checking multi-product combos:', {
                            'status': status,
                            'error': error,
                            'response': xhr.responseText
                        });
                        if (DEBUG_MODE) console.error('Error checking multi-product combos:', error, xhr.responseText);
                    }
                });
            }

            $(document).on('click', '.toacart', function() {
                // alert('hey');
                
                $.ajax({
                    url: $(this).data('url'),

                    success: function(data) {
                        var sum = 0;
                        if (data.code == '200') {
                            
                            debugLog('Add to cart data:', data);
                            
                            let compo_id = data.product.compo_id;

                            let badgeHtml = '';

                            if (compo_id != 0) {
                                badgeHtml = '<span class="badge bg-success">' + compo_id + '</span>';
                            } else {
                                badgeHtml = '<span class="badge bg-secondary">No combo</span>';
                            }
                            
                            $('.carttable #product-id-' + data.product.id + ' .combo').html(badgeHtml);
                            
                            // Check for multi-product combo offers after adding product
                            if (data.carttotal || data.cart) {
                                var cartData = {
                                    carttotal: data.carttotal || data.cart
                                };
                                setTimeout(function() {
                                    checkAndApplyMultiProductCombos(cartData);
                                }, 300);
                            }
                            
                            // Append cart HTML (already includes price with VAT from server) - only if it's a new product
                            // CRITICAL FIX: Only append if product doesn't already exist in cart
                            if (data.carthtml) {
                                var productId = data.product.id;
                                var $existingRow = $('#product-id-' + productId);
                                
                                // Only append if product doesn't exist in cart
                                if ($existingRow.length === 0) {
                                    $('#tbody').append(data.carthtml);
                                    debugLog('Appended new product to cart:', productId);
                                } else {
                                    debugLog('Product already exists in cart, skipping append:', productId);
                                }
                            }
                            if (data.compo_html) {
                                $('#combo_tbody').append(data.compo_html);
                            }
                            $('.no-found').addClass('d-none');
                            
                            if ('carttotal' in data) {
                                debugLog('=== CARTTOTAL EXISTS - Updating existing product(s) ===');
                                debugLog('Cart total data:', data.carttotal);
                                debugLog('Number of items in cart:', Object.keys(data.carttotal).length);
                                
                                // When carttotal exists, it means we're updating an existing product (incrementing quantity)
                                // Update ALL items in cart to ensure consistency
                                var itemsUpdated = 0;
                                $.each(data.carttotal, function(key, value) {
                                    var productId = value.id || key;
                                    var $row = $('#product-id-' + productId);
                                    
                                    debugLog('Processing cart item - Key:', key, 'Product ID:', productId, 'Row found:', $row.length > 0);
                                    
                                    if ($row.length) {
                                        // Update quantity
                                        var $qtyInput = $row.find('input[name="quantity"]');
                                        if ($qtyInput.length) {
                                            $qtyInput.val(value.quantity);
                                            debugLog('Updated quantity for product', productId, 'to', value.quantity);
                                        }
                                        
                                        // Update combo badge if combo_id exists
                                        if (value.compo_id !== undefined && value.compo_id != 0) {
                                            var badgeHtml = '<span class="badge bg-success">' + value.compo_id + '</span>';
                                            $row.find('.combo').html(badgeHtml);
                                            debugLog('Updated combo badge for product', productId, 'to combo', value.compo_id);
                                        }
                                        
                                        // Update subtotal - ensure proper formatting
                                        var subtotalNum = parseFloat(value.subtotal) || 0;
                                        var formattedSubtotal = addCommas(subtotalNum.toFixed(2));
                                        var $subtotalElement = $row.find('.subtotal');
                                        
                                        if ($subtotalElement.length) {
                                            var oldSubtotal = $subtotalElement.text().trim();
                                            $subtotalElement.text(formattedSubtotal);
                                            debugLog('Updated subtotal for product', productId, '- Old:', oldSubtotal, 'New:', formattedSubtotal, 'Raw:', subtotalNum);
                                            itemsUpdated++;
                                        } else {
                                            debugLog('Warning: Subtotal element not found for product', productId);
                                        }
                                    } else {
                                        debugLog('Warning: Row not found for product', productId, '- carttotal might be out of sync');
                                    }
                                });
                                
                                debugLog('Total items updated:', itemsUpdated, 'out of', Object.keys(data.carttotal).length);
                                
                                // Force DOM update and wait before calculating totals
                                // Use requestAnimationFrame to ensure DOM is fully updated
                                requestAnimationFrame(function() {
                                    setTimeout(function() {
                                        debugLog('=== Calling updateCartTotals() after carttotal update ===');
                                        var totals = updateCartTotals();
                                        debugLog('Totals calculation result:', totals);
                                        // Update price with VAT for all items (with additional delay)
                                        setTimeout(function() {
                                            updatePriceWithVatForAllItems();
                                        }, 200);
                                    }, 150); // Increased delay to ensure DOM is ready
                                });
                                
                                $('.discount').val('');
                            } else {
                                debugLog('=== NO CARTTOTAL - New product added ===');
                                // New product added (first item or different product)
                                // Update the specific product row if it exists (shouldn't for new products)
                                var $existingRow = $('.carttable #product-id-' + data.product.id);
                                if ($existingRow.length > 0) {
                                    var subtotalNum = parseFloat(data.product.subtotal) || 0;
                                    var formattedSubtotal = addCommas(subtotalNum.toFixed(2));
                                    $existingRow.find('input[name="quantity"]').val(data.product.quantity);
                                    $existingRow.find('.subtotal').text(formattedSubtotal);
                                    debugLog('Updated existing row for new product', data.product.id, 'Subtotal:', formattedSubtotal);
                                }
                                
                                // Update totals after DOM is updated
                                requestAnimationFrame(function() {
                                    setTimeout(function() {
                                        debugLog('=== Calling updateCartTotals() after new product ===');
                                        var totals = updateCartTotals();
                                        debugLog('Totals calculation result:', totals);
                                        // Update price with VAT for all items (with additional delay)
                                        setTimeout(function() {
                                            updatePriceWithVatForAllItems();
                                        }, 300);
                                    }, 200); // Increased delay for new products
                                });
                            }
                            
                            $('#btn-pur button').removeAttr('disabled');
                            $('.btn-empty button').addClass('btn-clear-cart');
                            
                            // Refocus barcode input after adding product
                            setTimeout(function() {
                                $('#searchbarcode').focus();
                            }, 200);
                        }
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        show_toastr('{{ __('Error') }}', data.error, 'error');
                        
                        // Refocus barcode input after error
                        setTimeout(function() {
                            $('#searchbarcode').focus();
                        }, 200);
                    }
                });
            });

            $(document).on('change keyup', '#carthtml input[name="quantity"]', function(e) {
                e.preventDefault();
                var ele = $(this);
                var tr = ele.closest('tr'); // Find the closest <tr> element
                var productId = tr.data('product-id'); // Retrieve the value of data-product-id attribute
                var sum = 0;
                var quantity = ele.closest('span').find('input[name="quantity"]').val();
                var discount = $('.discount').val();
                var max = parseInt(ele.attr('max')); // Get the maximum value attribute from the input
                var plusBtn = ele.closest('.quantity').find(
                    '.plus'); // Find the plus button within the same .quantity container
                var warehouseId = $('#warehouse').val();
                var quantityInput = $('input[name="quantity"]');
                
                // Performance: Only fetch stock if needed (debounce this too)
                var stockCheckTimeout;
                clearTimeout(stockCheckTimeout);
                stockCheckTimeout = setTimeout(function() {
                    $.ajax({
                        url: 'get_free_product_in_warehouse/' + warehouseId + '/' + productId,
                        method: 'GET',
                        success: function(response) {
                            debugLog('Stock check - Warehouse:', warehouseId, 'Product:', productId, 'Max:', response);
                            var maxQuantity = parseInt(response) || 0;
                            // Set the max attribute of the input field
                            quantityInput.attr('max', maxQuantity);
                        },
                        error: function(xhr, status, error) {
                            if (DEBUG_MODE) console.error('Error fetching max value:', error);
                        }
                    });
                }, 300); // Debounce stock check
                
                debugLog('Quantity change - Product ID:', ele.attr("data-id"));
                // Check if the quantity exceeds the maximum value or the product is out of stock
                if (quantity >= max || max <= 0) {
                    plusBtn.prop('disabled', true); // Disable the plus button
                } else {
                    plusBtn.prop('disabled', false); // Enable the plus button
                }
                // console.log(quantity)
                if (quantity != null && quantity != 0) {
                    $.ajax({
                        url: ele.data('url'),
                        method: "patch",
                        data: {
                            id: ele.attr("data-id"),
                            warehouse: warehouseId,
                            quantity: quantity,
                            discount: discount,
                            session_key: session_key
                        },
                        success: function(data) {
                            debugLog('Cart update response:', data);

                            if (data.code == '200') {
                                let voucher_amount = 0.0;
                                if (quantity == 0) {
                                    ele.closest(".row").hide(250, function() {
                                        ele.closest(".row").remove();
                                    });
                                    if (ele.closest(".row").is(":last-child")) {
                                        $('#btn-pur button').attr('disabled', 'disabled');
                                        $('.btn-empty button').removeClass('btn-clear-cart');
                                    }
                                }

                                // Update subtotals for all items
                                $.each(data.product, function(key, value) {
                                    $('#product-id-' + value.id + ' .subtotal').text(
                                        addCommas(value.subtotal));
                                });
                                
                                // Update totals using centralized function
                                updateCartTotals();

                                let compo_id = data.prod.compo_id;
                                let badgeHtml = '';
                                if (compo_id != 0) {
                                    badgeHtml = '<span class="badge bg-success">' + compo_id + '</span>';
                                } else {
                                    badgeHtml = '<span class="badge bg-secondary">No combo</span>';
                                }
                                
                                $('.carttable #product-id-' + data.prod.id + ' .combo').html(badgeHtml);
                                
                                // Check for multi-product combo offers after quantity change
                                if (data.product) {
                                    // Get current cart from session or build from DOM
                                    var currentCart = {};
                                    $('#tbody tr[data-product-id]').each(function() {
                                        var $row = $(this);
                                        var productId = $row.data('product-id');
                                        var quantity = parseInt($row.find('input[name="quantity"]').val()) || 0;
                                        var price = parseFloat($row.find('.price').text().replace(/[^\d.-]/g, '')) || 0;
                                        var subtotal = parseFloat($row.find('.subtotal').text().replace(/[^\d.-]/g, '')) || 0;
                                        
                                        currentCart[productId] = {
                                            id: productId,
                                            quantity: quantity,
                                            price: price,
                                            subtotal: subtotal
                                        };
                                    });
                                    
                                    if (Object.keys(currentCart).length >= 2) {
                                        var cartDataForCombo = {
                                            carttotal: currentCart
                                        };
                                        setTimeout(function() {
                                            checkAndApplyMultiProductCombos(cartDataForCombo);
                                        }, 300);
                                    }
                                }
                            }
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            show_toastr('{{ __('Error') }}', data.error, 'error');
                        }
                    });
                }
            });

            // Intercept bs-pass-para-pos clicks for cart items to use AJAX instead of form submission
            $(document).on("click", ".bs-pass-para-pos", function (e) {
                var $button = $(this);
                var formId = $button.data("confirm-yes");
                var $form = $('#' + formId);
                
                // Check if this is a cart removal form (has remove-from-cart action)
                if ($form.length && $form.attr('action') && $form.attr('action').includes('remove-from-cart')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var productId = $button.data("id");
                    var sessionKey = $form.find('input[name="session_key"]').val();
                    
                    const swalWithBootstrapButtons = Swal.mixin({
                        customClass: {
                            confirmButton: "btn btn-success",
                            cancelButton: "btn btn-danger",
                        },
                        buttonsStyling: false,
                    });
                    
                    swalWithBootstrapButtons
                        .fire({
                            title: "{{ __('Are you sure?') }}",
                            text: "{{ __('This action can not be undone. Do you want to continue?') }}",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "{{ __('Yes') }}",
                            cancelButtonText: "{{ __('No') }}",
                            reverseButtons: true,
                        })
                        .then((result) => {
                            if (result.isConfirmed) {
                                // Find the row to remove (tr element with data-product-id)
                                var $row = $button.closest('tr[data-product-id="' + productId + '"]');
                                
                                if ($row.length === 0) {
                                    // Fallback: try to find by product-id class
                                    $row = $('#product-id-' + productId);
                                }
                                
                                // Hide row with animation
                                $row.fadeOut(250, function() {
                                    // Make AJAX call instead of form submission
                                    $.ajax({
                                        url: $form.attr('action'),
                                        method: "DELETE",
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json'
                                        },
                                        data: {
                                            id: productId,
                                            session_key: sessionKey,
                                            _token: $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function(data) {
                                            // Remove row from DOM
                                            $row.remove();
                                            
                                            // Check if cart is empty
                                            var remainingRows = $('#tbody tr[data-product-id]').length;
                                            if (remainingRows === 0) {
                                                $('#tbody').html('<tr class="text-center no-found"><td colspan="9">{{ __("No Data Found.!") }}</td></tr>');
                                                $('#btn-pur button').attr('disabled', 'disabled');
                                                $('.btn-empty button').removeClass('btn-clear-cart');
                                            }
                                            
                                            // Update subtotals for remaining items if response contains product data
                                            if (data && data.code == '200' && data.product) {
                                                $.each(data.product, function(key, value) {
                                                    var $productRow = $('#product-id-' + key);
                                                    if ($productRow.length) {
                                                        var subtotalNum = parseFloat(value.subtotal) || 0;
                                                        var formattedSubtotal = addCommas(subtotalNum.toFixed(2));
                                                        $productRow.find('.subtotal').text(formattedSubtotal);
                                                    }
                                                });
                                            }
                                            
                                            // Update totals using centralized function
                                            updateCartTotals();
                                            
                                            if (data && data.success) {
                                                show_toastr('success', data.success, 'success');
                                            } else {
                                                show_toastr('success', '{{ __("Product removed from cart") }}', 'success');
                                            }
                                            
                                            // Refocus barcode input
                                            setTimeout(function() {
                                                $('#searchbarcode').focus();
                                            }, 200);
                                        },
                                        error: function(xhr) {
                                            // Show row again if error
                                            $row.show();
                                            
                                            var errorData = xhr.responseJSON || {};
                                            var errorMsg = errorData.error || '{{ __("Error removing product from cart") }}';
                                            show_toastr('error', errorMsg, 'error');
                                        }
                                    });
                                });
                            }
                        });
                    
                    return false; // Prevent default handler from running
                }
                // If not a cart removal, let the default handler run (form submission)
            });

            $(document).on('click', '.remove-from-cart', function(e) {
                e.preventDefault();

                var ele = $(this);
                var sum = 0;

                if (confirm('{{ __('Are you sure?') }}')) {
                    ele.closest(".row").hide(250, function() {
                        ele.closest(".row").parent().parent().remove();
                    });
                    if (ele.closest(".row").is(":last-child")) {
                        $('#btn-pur button').attr('disabled', 'disabled');
                        $('.btn-empty button').removeClass('btn-clear-cart');
                    }
                    $.ajax({
                        url: ele.data('url'),
                        method: "DELETE",
                        data: {
                            id: ele.attr("data-id"),
                            // session_key: session_key
                        },
                        success: function(data) {
                            if (data.code == '200') {

                                // Update subtotals for all items
                                $.each(data.product, function(key, value) {
                                    $('#product-id-' + value.id + ' .subtotal').text(addCommas(value.subtotal));
                                });
                                
                                // Update totals using centralized function
                                updateCartTotals();
                                show_toastr('success', data.success, 'success')
                            }
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            show_toastr('{{ __('Error') }}', data.error, 'error');
                        }
                    });
                }
            });

            $(document).on('click', '.btn-clear-cart', function(e) {
                e.preventDefault();

                if (confirm('{{ __('Remove all items from cart?') }}')) {

                    $.ajax({
                        url: $(this).data('url'),
                        data: {
                            session_key: session_key
                        },
                        success: function(data) {
                            location.reload();
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            show_toastr('{{ __('Error') }}', data.error, 'error');
                        }
                    });
                }
            });
            // Performance: Debounce discount updates
            var discountUpdateTimeout;
            $(document).on('change keyup', '#carthtml input[name="discount"]', function (e) {
                e.preventDefault();

                var ele = $(this);
                var tr = ele.closest('tr');
                var productId = ele.data('id');
                var newDiscount = parseFloat(ele.val());

                if (isNaN(newDiscount) || newDiscount < 0) {
                    newDiscount = 0;
                    ele.val(newDiscount);
                }
                if (newDiscount > 100) {
                    newDiscount = 100;
                    ele.val(newDiscount);
                }

                // Clear previous timeout
                clearTimeout(discountUpdateTimeout);
                
                // Debounce discount update - wait 500ms after user stops typing
                discountUpdateTimeout = setTimeout(function() {
                    // Cancel previous discount update request
                    if (activeAjaxRequests.discountUpdate) {
                        activeAjaxRequests.discountUpdate.abort();
                    }
                    
                    // Send discount to backend
                    activeAjaxRequests.discountUpdate = $.ajax({
                        url: '/update-discount',
                        method: 'POST',
                        data: {
                            product_id: productId,
                            item_discount: newDiscount,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (cart) {
                            debugLog('Discount update response:', cart);
                            
                            // Performance: Cache selectors
                            var $displayTotal = $('#displaytotal');
                            var $totalAmount = $('.totalamount');
                            
                            if (cart[productId]) {
                                const formattedSubtotal = cart[productId]['subtotal'];
                                tr.find('.subtotal').text(addCommas(formattedSubtotal));
                            }
                            
                            // Update totals using centralized function
                            updateCartTotals();
                            activeAjaxRequests.discountUpdate = null;
                        },
                        error: function (err) {
                            if (err.status !== 'abort') {
                                if (DEBUG_MODE) console.error("Error updating discount:", err);
                            }
                            activeAjaxRequests.discountUpdate = null;
                        }
                    });
                }, 500);
            });
            $(document).on('click', '.btn-done-payment', function(e) {
                e.preventDefault();
                var ele = $(this);

                $.ajax({
                    url: ele.data('url'),

                    method: 'GET',
                    data: {
                        customer_id: $('#customer_id').val() || $('#vc_name_hidden').val(), // Use customer_id from hidden field
                        vc_name: $('#vc_name_hidden').val(), // Keep for backward compatibility
                        warehouse_name: $('#warehouse_name_hidden').val(),
                        discount: $('#discount_hidden').val(),
                        tax_id: $('#tax_hidden').val(),
                    },
                    beforeSend: function() {
                        ele.remove();
                    },
                    success: function(data) {
                        if (data.code == 200) {
                            show_toastr('success', data.success, 'success');
                            
                            // Close the modal after successful payment
                            var $modal = $('#commonModal');
                            if ($modal.length) {
                                // Try Bootstrap 5 method first
                                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                    var modalInstance = bootstrap.Modal.getInstance($modal[0]);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    } else {
                                        // If no instance exists, create one and hide
                                        var modal = new bootstrap.Modal($modal[0]);
                                        modal.hide();
                                    }
                                } else {
                                    // Fallback to Bootstrap 4/jQuery method
                                    $modal.modal('hide');
                                }
                                // Also remove backdrop if it exists
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                $('body').css('padding-right', '');
                            }
                            
                            // Empty cart after successful payment
                            var session_key = $("#empty_cart").val();
                            $.ajax({
                                url: '{{ url("empty-cart") }}',
                                method: 'POST',
                                data: {
                                    session_key: session_key,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(emptyData) {
                                    debugLog('Cart emptied successfully');
                                    
                                    // Performance: Batch DOM updates
                                    var $tbody = $("#tbody");
                                    var $vouchersTbody = $("#vouchers_tbody");
                                    
                                    // Clear cart display
                                    $tbody.empty().html('<tr class="text-center no-found"><td colspan="7">{{ __("No Data Found.!") }}</td></tr>');
                                    
                                    // Reset totals - cache selectors
                                    $('#displaytotal').text('0.00');
                                    $('.totalamount').text('0.00');
                                    $('.tax_val').text('0.00');
                                    
                                    // Disable pay button
                                    $('#btn-pur button').attr('disabled', 'disabled');
                                    $('.btn-empty button').removeClass('btn-clear-cart');
                                    
                                    // Clear vouchers table
                                    $vouchersTbody.empty().html('<tr class="text-center no-found"><td colspan="7">{{ __("No vouchers Found.!") }}</td></tr>');
                                },
                                error: function(err) {
                                    if (DEBUG_MODE) console.error('Error emptying cart:', err);
                                }
                            });
                        }
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        show_toastr('{{ __('Error') }}', data.error, 'error');
                    }

                });

            });

            $(document).on('click', '.category-select', function(e) {
                var cat = $(this).data('cat-id');
                var white = 'text-white';
                var dark = 'text-dark';
                $('.category-select').parent().removeClass('cat-active');
                $('.category-select').find('.card-title').removeClass('text-white').addClass('text-dark');
                $('.category-select').find('.card-title').parent().removeClass('text-white').addClass(
                    'text-dark');
                $(this).find('.card-title').removeClass('text-dark').addClass('text-white');
                $(this).find('.card-title').parent().removeClass('text-dark').addClass('text-white');
                $(this).parent().addClass('cat-active');
                var url = '{{ route('search.products') }}'
                var warehouse_id = $('#warehouse').val();
                searchProducts(url, '', cat, warehouse_id);
            });

            $(document).on('keyup', '.discount', function() {

                var discount = $('.discount').val();
                var tax = $('#choices-multiple1').val();
                
                $("#discount_hidden").val(discount);
                $.ajax({
                    url: "{{ route('cartdiscount') }}",
                    method: 'POST',
                    data: {
                        discount: discount,
                        tax:tax,
                    },
                    success: function(data) {
                        debugLog('Cart discount response:', data);
                        $('.totalamount').text(data.total);
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        show_toastr('{{ __('Error') }}', data.error, 'error');
                    }
                });
                
                // // var total_subtotals = 
                // // {{-- var price = {{$total}} --}}
                // // {{--    var total_amount = price-discount; --}}
                // var totalAmount = document.querySelector('.totalamount').textContent.trim();
                // console.log(totalAmount);
                // console.log(discount);
                // {{--    $('.totalamount').text(totalAmount); --}}
            })
            
            // Validate customer selection before PAY button click
            $(document).on('click', '#pay-button, button[data-ajax-popup="true"][data-url*="pos.create"]', function(e) {
                var customerId = $('#customer_id').val();
                var vcName = $('#vc_name_hidden').val();
                
                // Check if customer is selected
                if (!customerId && !vcName) {
                    e.preventDefault();
                    e.stopPropagation();
                    show_toastr('error', '{{ __("Please select a customer before proceeding to payment.") }}', 'error');
                    $('#customer_search').focus();
                    return false;
                }
                
                // Ensure customer_id is set from vc_name_hidden if customer_id is empty
                if (!customerId && vcName) {
                    $('#customer_id').val(vcName);
                }
                
                // Ensure vc_name_hidden is set from customer_id if vc_name_hidden is empty
                if (customerId && !vcName) {
                    $('#vc_name_hidden').val(customerId);
                }
                
                // Update the URL to include user_id (cashier) if dropdown exists
                var $button = $(this);
                var originalUrl = $button.data('url') || $button.attr('href');
                if (originalUrl && $('#user_id').length) {
                    var userId = $('#user_id').val();
                    var separator = originalUrl.indexOf('?') !== -1 ? '&' : '?';
                    var newUrl = originalUrl + separator + 'user_id=' + encodeURIComponent(userId);
                    $button.data('url', newUrl);
                    if ($button.attr('href')) {
                        $button.attr('href', newUrl);
                    }
                }
                
                debugLog('PAY button clicked - Customer ID:', $('#customer_id').val(), 'VC Name:', $('#vc_name_hidden').val(), 'User ID:', $('#user_id').val());
            });

            // Handle reload button click - empty cart then reload
            $('#reload-pos-btn').on('click', function(e) {
                e.preventDefault();
                
                // Show loading state
                var $btn = $(this);
                var $icon = $btn.find('i');
                $icon.addClass('ti-loader').removeClass('ti-refresh');
                $btn.css('pointer-events', 'none');
                
                // Empty cart first, then reload
                $.ajax({
                    url: '{{ url('empty-cart') }}',
                    method: 'POST',
                    data: {
                        session_key: session_key,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        // Reload page after cart is emptied
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        // Even if there's an error, reload the page
                        if (DEBUG_MODE) console.error('Error emptying cart:', error);
                        location.reload();
                    }
                });
            });

        });
    </script>
    <script>
        var site_currency_symbol_position = '{{ \App\Models\Utility::getValByName('site_currency_symbol_position') }}';
        var site_currency_symbol = '{{ \App\Models\Utility::getValByName('site_currency_symbol') }}';
    </script>
    <script>
        function addCommas(nStr) {
            nStr += '';
            var x = nStr.split('.');
            var x1 = x[0];
            var x2 = x.length > 1 ? '.' + x[1] : '';
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(x1)) {
                x1 = x1.replace(rgx, '$1' + ',' + '$2');
            }
            return x1 + x2;
        }


        // Clean tax dropdown before attaching change handler
        var $taxSelect = $('#choices-multiple1');
        if ($taxSelect.length > 0) {
            // Destroy Select2 if initialized
            if ($taxSelect.hasClass('select2-hidden-accessible')) {
                try {
                    $taxSelect.select2('destroy');
                } catch(e) {
                    if (DEBUG_MODE) console.warn('Error destroying tax Select2 before cleanup:', e);
                }
            }
            
            // Remove all duplicates by rebuilding options
            var seenTaxOptions = {};
            var uniqueOptions = [];
            $taxSelect.find('option').each(function() {
                var $opt = $(this);
                var val = $opt.val();
                var txt = $opt.text().trim();
                var key = (val || '') + '|' + txt;
                
                if (!seenTaxOptions[key]) {
                    seenTaxOptions[key] = true;
                    uniqueOptions.push({
                        value: val || '',
                        text: txt || 'Select Tax',
                        selected: $opt.prop('selected')
                    });
                }
            });
            
            // Rebuild if duplicates found
            if (uniqueOptions.length < $taxSelect.find('option').length) {
                var currentVal = $taxSelect.val();
                $taxSelect.empty();
                uniqueOptions.forEach(function(opt) {
                    var $newOpt = $('<option></option>').attr('value', opt.value).text(opt.text);
                    if (opt.selected || (opt.value && opt.value === currentVal)) {
                        $newOpt.prop('selected', true);
                    }
                    $taxSelect.append($newOpt);
                });
                debugLog('Tax dropdown cleaned - removed duplicates, kept', uniqueOptions.length, 'unique options');
            }
            
            // Reinitialize Select2 if it was destroyed
            if (typeof $.fn.select2 !== 'undefined' && !$taxSelect.hasClass('select2-hidden-accessible')) {
                $taxSelect.select2({
                    theme: 'default',
                    width: '100%',
                    allowClear: false
                });
            }
        }
        
        $('#choices-multiple1').on('change', function() {
            // Get selected tax rates
            var selectedValues = $(this).val();
            var taxData = <?php echo json_encode($fullTax); ?>;
            
            // Reset TotalTax and vatType before calculating
            TotalTax = 0;
            vatType = '';
            
            // Validate selectedValues
            if (!selectedValues || selectedValues === '' || selectedValues === null) {
                // No tax selected, set defaults
                $("#tax_hidden").val('');
                TotalTax = 0;
                vatType = '';
                // Update totals using centralized function
                updateCartTotals();
                return;
            }
            
            // Your logic to calculate total tax amount based on selected rates
            $("#tax_hidden").val(selectedValues);
            
            // Save tax_id to session for POS save (optional - form submission will also work)
            // The form has name="tax_id" so it will be submitted, but we also save to session as backup
            try {
                sessionStorage.setItem('pos_tax_id', selectedValues);
                debugLog('Tax saved to sessionStorage:', selectedValues);
            } catch(e) {
                if (DEBUG_MODE) console.warn('Failed to save tax to sessionStorage:', e);
            }
            
            // Parse selectedValues to integer for comparison
            var selectedTaxId = parseInt(selectedValues);
            if (isNaN(selectedTaxId)) {
                console.error('Invalid tax ID:', selectedValues);
                return;
            }
         
            for (let j = 0; j < taxData.length; j++) {
                if (taxData[j].id === selectedTaxId) {
                    TotalTax += parseInt(taxData[j].rate) || 0;
                    vatType = taxData[j].type || '';
                }
            }

            debugLog('Tax selected:', selectedValues, 'TotalTax:', TotalTax, 'vatType:', vatType);

            // Update totals using centralized function (handles tax calculation correctly)
            updateCartTotals();
            
            // Update price with VAT display for all items
            updatePriceWithVatForAllItems();
        });
        
        // Function to update price with VAT display for all cart items based on selected tax
        function updatePriceWithVatForAllItems() {
            var taxRate = parseFloat(TotalTax) || 0;
            var vatTypeValue = vatType || '';
            
            debugLog('updatePriceWithVatForAllItems called - Tax Rate:', taxRate, 'VAT Type:', vatTypeValue);
            
            // Get currency symbol and position for formatting
            var currencySymbol = typeof site_currency_symbol !== 'undefined' ? site_currency_symbol : '';
            var symbolPosition = typeof site_currency_symbol_position !== 'undefined' ? site_currency_symbol_position : 'pre';
            
            // Find all price cells - try multiple selectors to catch all cases
            var $priceCells = $('.price[data-base-price]');
            if ($priceCells.length === 0) {
                // Fallback: try to find price cells by class and extract base price from text
                $priceCells = $('td.price.text-right');
            }
            
            debugLog('Found', $priceCells.length, 'price cells to update');
            
            // Loop through all price cells
            $priceCells.each(function() {
                var $priceCell = $(this);
                var basePrice = 0;
                
                // Try to get base price from data attribute first
                var dataBasePrice = $priceCell.data('base-price');
                if (dataBasePrice !== undefined && dataBasePrice !== null) {
                    basePrice = parseFloat(dataBasePrice) || 0;
                } else {
                    // Fallback: extract from the displayed price text
                    var $basePriceDiv = $priceCell.find('.base-price');
                    if ($basePriceDiv.length === 0) {
                        $basePriceDiv = $priceCell.find('.fw-bold');
                    }
                    if ($basePriceDiv.length > 0) {
                        var priceText = $basePriceDiv.text().trim();
                        // Remove currency symbol and commas, extract number
                        priceText = priceText.replace(/[^\d.-]/g, '');
                        basePrice = parseFloat(priceText) || 0;
                    }
                }
                
                var $vatPriceDiv = $priceCell.find('.price-with-vat');
                var $vatPriceAmount = $priceCell.find('.vat-price-amount');
                
                // If VAT div doesn't exist, create it
                if ($vatPriceDiv.length === 0) {
                    $vatPriceDiv = $('<div class="price-with-vat text-muted small mt-1" style="font-size: 0.75rem; display: none;">' +
                        '<i class="ti ti-info-circle" style="font-size: 0.7rem;"></i> ' +
                        '<span class="vat-price-amount"></span> ' +
                        '<span class="text-muted">{{ __("(incl. VAT)") }}</span>' +
                        '</div>');
                    $priceCell.append($vatPriceDiv);
                    $vatPriceAmount = $vatPriceDiv.find('.vat-price-amount');
                }
                
                debugLog('Processing price cell - Base Price:', basePrice, 'Tax Rate:', taxRate);
                
                if (basePrice > 0 && taxRate > 0) {
                    // Calculate price with VAT
                    var priceWithVat = basePrice * (1 + (taxRate / 100));
                    
                    // Format the price
                    var formattedPrice = addCommas(priceWithVat.toFixed(2));
                    if (symbolPosition === 'pre') {
                        formattedPrice = currencySymbol + formattedPrice;
                    } else {
                        formattedPrice = formattedPrice + currencySymbol;
                    }
                    
                    // Update and show the VAT price
                    $vatPriceAmount.text(formattedPrice);
                    $vatPriceDiv.show();
                    
                    debugLog('Updated VAT price for item - Base:', basePrice, 'With VAT:', priceWithVat, 'Formatted:', formattedPrice);
                } else {
                    // Hide VAT price if no tax or invalid base price
                    $vatPriceDiv.hide();
                    debugLog('Hiding VAT price - Base Price:', basePrice, 'Tax Rate:', taxRate);
                }
            });
        }
        $('#voucher').on('change', function() {
            var voucherValue = $(this).val();
            $('#voucher_hidden').val(voucherValue);
            
            // Get customer ID from the correct field
            var customerId = $('#customer_id').val() || $('#vc_name_hidden').val();
            
            // Validate customer ID
            if (!customerId || customerId === '') {
                show_toastr('error', '{{ __("Please select a customer first") }}', 'error');
                $('#voucher').val('');
                return;
            }
            
            // Parse customer ID as integer
            var customerIdInt = parseInt(customerId);
            if (isNaN(customerIdInt)) {
                show_toastr('error', '{{ __("Invalid customer ID") }}', 'error');
                $('#voucher').val('');
                return;
            }
            
            // console.log(price);
            $.ajax({
                    url: "{{ route('vouchers.check') }}",
                    method: 'POST',
                    data: {
                        customer_id: customerIdInt, 
                        voucher_id: $('#voucher').val(),   
                        tax_id:$('#choices-multiple1').val(),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        debugLog('Voucher check response:', data);
                        let vouchers = data.all_vouchers;
                        let total_vouchers = 0.0;
                        let sum = 0.0;
                         $.each(data.carttotal, function(key, value) {
                            sum += value.subtotal;
                        });
                       
                        $('#vouchers_tbody').empty();
                        if (Object.keys(vouchers).length > 0) {
                            $.each(vouchers, function (id, details) {
                                let newRow = `
                                    <tr data-voucher-id="${id}" id="voucher-id-${id}">
                                        <td class="name text-center">${id}</td>
                                        <td class="amount text-center vamou">${details.amount}</td>
                                    </tr>
                                `;
                                $('#vouchers_tbody').append(newRow);
                                total_vouchers += details.amount;
                            });
                            // Update totals using centralized function (handles tax and vouchers correctly)
                            updateCartTotals();                            
                        } else {
                            // 4️⃣ If none, add "No vouchers Found" row
                            $('#vouchers_tbody').append(`
                                <tr class="text-center no-found">
                                    <td colspan="7">{{ __('No vouchers Found.!') }}</td>
                                </tr>
                            `);
                        }
                    },
                    error: function(xhr) {
                        var data = xhr.responseJSON || {};
                        var errorMessage = data.error || '{{ __("An error occurred while checking the voucher") }}';
                        var errorType = data.error_type || 'unknown';
                        
                        // Clear voucher input
                        $('#voucher').val('');
                        $('#voucher_hidden').val('');
                        
                        // Show clear error message with different styling for expired vouchers
                        if (errorType === 'expired') {
                            // Show a more prominent error for expired vouchers
                            var expiryDateFormatted = data.expiry_date_formatted || data.expiry_date || '';
                            var expiryDateText = expiryDateFormatted ? ' ({{ __("Expired on") }}: ' + expiryDateFormatted + ')' : '';
                            
                            // Show toastr error with red styling
                            show_toastr('{{ __("Voucher Expired") }}', errorMessage + expiryDateText, 'error');
                            
                            // Show SweetAlert dialog for expired vouchers to make it more visible
                            setTimeout(function() {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '{{ __("⚠️ VOUCHER EXPIRED ⚠️") }}',
                                        html: '<p style="font-size: 16px; margin-bottom: 10px;">' + errorMessage + expiryDateText + '</p><p style="color: #666;">{{ __("Please use a valid voucher.") }}</p>',
                                        confirmButtonText: '{{ __("OK") }}',
                                        confirmButtonColor: '#dc3545',
                                        allowOutsideClick: true,
                                        allowEscapeKey: true
                                    });
                                } else {
                                    // Fallback to regular alert if SweetAlert is not available
                                    alert('{{ __("⚠️ VOUCHER EXPIRED ⚠️") }}\n\n' + errorMessage + expiryDateText + '\n\n{{ __("Please use a valid voucher.") }}');
                                }
                            }, 500);
                        } else {
                            // Regular error for other cases
                            show_toastr('{{ __("Error") }}', errorMessage, 'error');
                        }
                        
                        debugLog('Voucher check error:', errorType, errorMessage);
                    }
            });
            debugLog('Voucher changed:', voucherValue);
        });
    </script>



</html>
