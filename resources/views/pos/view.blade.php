@extends('layouts.admin')
@section('page-title')
    {{__('POS Detail')}}
@endsection
@push('script-page')
    <script>
        $(document).on('click', '#shipping', function () {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: {
                    'is_display': is_display,
                },
                success: function (data) {
                    // console.log(data);
                }
            });
        });

        // Direct Print to Epson TM-M30
        $(document).on('click', '#directPrintBtn', function() {
            var $btn = $(this);
            var originalText = $btn.html();
            
            // Disable button and show loading
            $btn.prop('disabled', true);
            $btn.html('<i class="ti ti-loader"></i> {{ __("Printing...") }}');
            
            // Get printer settings
            var printerIp = '{{ $settings["pos_printer_ip"] ?? "10.255.254.17" }}';
            var printerPort = {{ $settings["pos_printer_port"] ?? 9100 }};
            
            if (!printerIp || printerIp === '') {
                alert('{{ __("Printer not configured. Please set pos_printer_ip in system settings.") }}');
                $btn.prop('disabled', false);
                $btn.html(originalText);
                return false;
            }
            
            // Prepare sales data from POS items
            var salesData = [];
            @foreach($iteams as $item)
                @php
                    $product = null;
                    $productName = '';
                    $categoryName = '';
                    $brandName = '';
                    $subBrandName = '';
                    $fullProductName = '';
                    
                    // Try to get product from product relationship
                    if($item->product) {
                        $product = $item->product;
                        $productName = $product->name ?? '';
                    }
                    
                    // If product relationship doesn't work, try via sub_product
                    if(!$product && $item->sub_product && $item->sub_product->productService) {
                        $product = $item->sub_product->productService;
                        $productName = $product->name ?? '';
                    }
                    
                    // If still empty, try direct query
                    if(!$product && $item->product_id) {
                        $product = \App\Models\ProductService::with(['category', 'brand', 'subBrand'])->find($item->product_id);
                        if($product) {
                            $productName = $product->name ?? '';
                        }
                    }
                    
                    // Load relationships if not already loaded
                    if($product) {
                        if(!$product->relationLoaded('category')) {
                            $product->load('category');
                        }
                        if(!$product->relationLoaded('brand')) {
                            $product->load('brand');
                        }
                        if(!$product->relationLoaded('subBrand')) {
                            $product->load('subBrand');
                        }
                        
                        // Get category name (raw - will be escaped by json_encode for JavaScript)
                        if($product->category) {
                            $categoryName = $product->category->name ?? '';
                        }
                        
                        // Get brand name (raw - will be escaped by json_encode for JavaScript)
                        if($product->brand) {
                            $brandName = $product->brand->name ?? '';
                        }
                        
                        // Get sub brand name (raw - will be escaped by json_encode for JavaScript)
                        if($product->subBrand) {
                            $subBrandName = $product->subBrand->name ?? '';
                        }
                        
                        // Build full product name hierarchy (raw values - json_encode will handle escaping)
                        $nameParts = [];
                        if(!empty($categoryName)) {
                            $nameParts[] = $categoryName;
                        }
                        if(!empty($brandName)) {
                            $nameParts[] = $brandName;
                        }
                        if(!empty($subBrandName)) {
                            $nameParts[] = $subBrandName;
                        }
                        if(!empty($productName)) {
                            $nameParts[] = $productName;
                        }
                        $fullProductName = implode(' → ', $nameParts);
                    } else {
                        $fullProductName = $productName;
                    }
                    
                    // Determine base price - use combo_price if combo exists
                    // combo_price can be 0.00 for free items in BOGO combos
                    $basePrice = $item->price;
                    if ($item->compo_id != 0 && $item->compo_id != '0' && $item->combo_price !== null) {
                        $basePrice = $item->combo_price;
                    }
                    
                    // Calculate tax for this item
                    $itemTaxPrice = 0;
                    $itemTaxRate = 0;
                    if(!empty($pos->tax_id)) {
                        $taxes = App\Models\Utility::tax($pos->tax_id);
                        foreach($taxes as $tax) {
                            if($tax && $tax->rate && $tax->name) {
                                $priceAfterDiscount = $basePrice - ($basePrice * ($item->discount / 100));
                                $taxPrice = App\Models\Utility::taxRate($tax->rate, $priceAfterDiscount, $item->quantity);
                                $itemTaxPrice += $taxPrice;
                                $itemTaxRate += $tax->rate;
                            }
                        }
                    }
                    
                    $itemSubtotal = ($basePrice - ($basePrice * ($item->discount / 100))) * $item->quantity;
                    
                    // Generate combo text description if combo exists
                    $compoText = null;
                    if ($item->compo_id != 0 && $item->compo_id != '0') {
                        $combo = \App\Models\ComboOffer::find($item->compo_id);
                        if ($combo) {
                            if ($combo->type == 'bogo') {
                                $compoText = 'buy: ' . $combo->buy_quantity . '| get: ' . $combo->get_quantity;
                            } else {
                                $compoText = 'buy: ' . $combo->buy_quantity . '| for: ' . $combo->tiered_price;
                            }
                        }
                    }
                @endphp
                salesData.push({
                    id: {{ $item->product_id }},
                    name: {!! json_encode($fullProductName, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
                    quantity: {{ $item->quantity }},
                    price: {{ $basePrice }},
                    subtotal: {{ $itemSubtotal }},
                    discount: {{ $item->discount ?? 0 }},
                    tax: {{ $itemTaxRate }},
                    tax_amount: {{ $itemTaxPrice }},
                    compo_id: {{ $item->compo_id ?? 0 }},
                    compo_text: @if(!empty($compoText)){!! json_encode($compoText, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}@else null @endif,
                    combo_price: @if($item->compo_id != 0 && $item->combo_price != null){{ $item->combo_price }}@else null @endif
                });
            @endforeach
            
            if (salesData.length === 0) {
                alert('{{ __("No items found to print.") }}');
                $btn.prop('disabled', false);
                $btn.html(originalText);
                return false;
            }
            
            // Prepare vouchers data
            var vouchersData = {};
            @if(!empty($vouchers) && count($vouchers) > 0)
                @foreach($vouchers as $voucherId => $voucherData)
                    vouchersData['{{ $voucherId }}'] = {
                        amount: {{ $voucherData['amount'] ?? 0 }}
                    };
                @endforeach
            @endif
            
            // Prepare payment methods data
            var paymentMethodsData = [];
            @if(isset($paymentMethods) && !empty($paymentMethods))
                @foreach($paymentMethods as $pm)
                    paymentMethodsData.push({
                        id: {{ $pm['id'] }},
                        name: '{{ addslashes($pm['name']) }}',
                        amount: {{ $pm['amount'] }}
                    });
                @endforeach
            @endif
            
            // Calculate totals
            // Calculate subtotal from sales data
            var subtotal = 0;
            salesData.forEach(function(item) {
                subtotal += parseFloat(item.subtotal) || 0;
            });
            
            var discount = {{ isset($posPayment) && $posPayment ? ($posPayment->discount ?? 0) : 0 }};
            var taxAmount = {{ $totalTax ?? 0 }};
            var total = subtotal + taxAmount - discount;
            var voucherTotal = 0;
            @if(!empty($vouchers) && count($vouchers) > 0)
                @foreach($vouchers as $voucherId => $voucherData)
                    voucherTotal += {{ $voucherData['amount'] ?? 0 }};
                @endforeach
            @endif
            // Calculate customerPay from totals (subtotal + tax - discount)
            // This is the amount the customer needs to pay before vouchers
            var customerPay = total;
            var customerReturn = Math.max(0, customerPay - (total - voucherTotal));
            
            // Prepare form data
            var formData = {
                vc_name: {{ $pos->customer_id }},
                warehouse_name: {{ $pos->warehouse_id }},
                discount: discount,
                payments: customerPay,
                printer_ip: printerIp,
                printer_port: printerPort,
                sales_data: salesData,
                vouchers: vouchersData,
                tax_id: @if($pos->tax_id){{ $pos->tax_id }}@else null @endif,
                customer_return: customerReturn,
                payment_methods: paymentMethodsData,
                pos_id: '{{ $pos->pos_id }}'
            };
            
            // Send AJAX request
            $.ajax({
                url: '{{ route("pos.printview.direct") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify(formData),
                timeout: 15000,
                success: function(response) {
                    if (response.success) {
                        show_toastr('success', response.message || '{{ __("Print job queued successfully!") }}', 'success');
                        if (response.queue_info) {
                            console.log('Queue info:', response.queue_info);
                        }
                    } else {
                        show_toastr('error', response.message || '{{ __("Failed to queue print job") }}', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = '{{ __("Error sending print job") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (status === 'timeout') {
                        errorMsg = '{{ __("Request timeout. Please check your connection.") }}';
                    }
                    show_toastr('error', errorMsg, 'error');
                    console.error('Print error:', xhr.responseJSON || error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            });
        });

        // Function to open printview in new window (like vouchers do)
        function openPrintView() {
            // Get POS data
            var customerId = {{ $pos->customer_id ?? 'null' }};
            var warehouseId = {{ $pos->warehouse_id ?? 'null' }};
            var discount = {{ $pos->discount ?? 0 }};
            var taxId = @if($pos->tax_id){{ $pos->tax_id }}@else null @endif;
            
            // Use totalPay from controller (already calculated)
            var customerPay = {{ $totalPay ?? 0 }};
            
            // Construct URL with necessary parameters
            var printUrl = '{{ route("pos.printview") }}';
            var params = new URLSearchParams();
            
            if (customerId) {
                params.append('vc_name', customerId);
            }
            if (warehouseId) {
                params.append('warehouse_name', warehouseId);
            }
            if (customerPay) {
                params.append('payments', customerPay);
            }
            if (discount) {
                params.append('discount', discount);
            }
            if (taxId) {
                params.append('tax_id', taxId);
            }
            if ('{{ $pos->pos_id }}') {
                params.append('pos_id', '{{ $pos->pos_id }}');
            }
            
            // Build full URL
            var fullUrl = printUrl + '?' + params.toString();
            
            console.log('Opening printview URL:', fullUrl);
            
            // Open in new window - it will auto-print on load (like vouchers)
            window.open(fullUrl, '_blank');
            
            return false;
        }
    </script>
@endpush

@php
    $settings = Utility::settings();
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('pos.report')}}">{{__('POS Summary')}}</a></li>
    <li class="breadcrumb-item">{{ AUth::user()->posNumberFormat($pos->pos_id) }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('pos.pdf', Crypt::encrypt($pos->id))}}" class="btn btn-primary" target="_blank">{{__('Download')}}</a>
        @if (\Auth::user()->type == 'company')
            <a href="{{ route('pos.ledger', $pos->id) }}" target="_blank" class="btn btn-primary ms-2">{{ __('Show Accounting') }}</a>
        @endif
            <button id="printLikeVoucherBtn" type="button" class="btn btn-secondary ms-2" onclick="openPrintView(); return false;">
                <i class="ti ti-printer"></i> {{ __('Print Receipt (Browser)') }}
            </button>
            <button id="directPrintBtn" type="button" class="btn btn-success ms-2">
                <i class="ti ti-printer"></i> {{ __('Print Direct to Epson TM-M30') }}
            </button>
        
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mt-2">
                        <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                            <h4>{{__('POS')}}</h4>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                            <h4 class="invoice-number">{{ Auth::user()->posNumberFormat($pos->pos_id) }}</h4>
                        </div>
                        <div class="col-12">
                            <hr>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-5">
                            <small class="font-style">
                                <strong>{{__('Billed To')}} :</strong><br>
                                @if(!empty($customer->name))
                                    {{!empty($customer->name)?$customer->name:''}}<br>
                                    {{!empty($customer->billing_name)?$customer->billing_name:''}}<br>
                                    {{!empty($customer->billing_address)?$customer->billing_address:''}}<br>
                                    {{!empty($customer->billing_city)?$customer->billing_city:'' .', '}}<br>
                                    {{!empty($customer->billing_state)?$customer->billing_state:'' . ', '}},
                                    {{!empty($customer->billing_zip)?$customer->billing_zip:''}}<br>
                                    {{!empty($customer->billing_country)?$customer->billing_country:''}}<br>
                                    {{!empty($customer->billing_phone)?$customer->billing_phone:''}}<br>
                                    @if($settings['vat_gst_number_switch'] == 'on')
                                    <strong>{{__('Tax Number ')}} : </strong>{{!empty($customer->tax_number)?$customer->tax_number:''}}
                                    @endif
                                @else
                                    -
                                @endif
                            </small>
                        </div>
                        <div class="col-4">
                            @if(App\Models\Utility::getValByName('shipping_display')=='on')
                                <small>
                                    <strong>{{__('Shipped To')}} :</strong><br>
                                        @if(!empty($customer->shipping_name))
                                        {{!empty($customer->shipping_name)?$customer->shipping_name:''}}<br>
                                        {{!empty($customer->shipping_address)?$customer->shipping_address:''}}<br>
                                        {{!empty($customer->shipping_city)?$customer->shipping_city:'' . ', '}}<br>
                                        {{!empty($customer->shipping_state)?$customer->shipping_state:'' .', '}},
                                        {{!empty($customer->shipping_zip)?$customer->shipping_zip:''}}<br>
                                        {{!empty($customer->shipping_country)?$customer->shipping_country:''}}<br>
                                        {{!empty($customer->shipping_phone)?$customer->shipping_phone:''}}<br>
                                    @else
                                    -
                                    @endif
                                </small>
                            @endif
                        </div>
                        <div class="col-3">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="me-4">
                                    <small>
                                        <strong>{{__('Issue Date')}} :</strong>
                                        {{\Auth::user()->dateFormat($pos->pos_date)}}<br><br>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!empty($customer))
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="mb-2">{{ __('Customer Details') }}</h6>
                                <div class="row">
                                    <div class="col-md-4"><strong>{{ __('Name') }}:</strong> {{ data_get($customer, 'name', '-') }}</div>
                                    <div class="col-md-4"><strong>{{ __('Email') }}:</strong> {{ data_get($customer, 'email', '-') ?: '-' }}</div>
                                    <div class="col-md-4"><strong>{{ __('Phone') }}:</strong> {{ data_get($customer, 'contact', data_get($customer, 'phone', '-')) ?: '-' }}</div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-md-4"><strong>{{ __('Tax Number') }}:</strong> {{ data_get($customer, 'tax_number', '-') ?: '-' }}</div>
                                    <div class="col-md-4"><strong>{{ __('Billing Name') }}:</strong> {{ data_get($customer, 'billing_name', '-') ?: '-' }}</div>
                                    <div class="col-md-4"><strong>{{ __('Shipping Name') }}:</strong> {{ data_get($customer, 'shipping_name', '-') ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <style>
                                .pos-items-table td:nth-child(2) {
                                    white-space: normal !important;
                                    word-wrap: break-word;
                                    max-width: 300px;
                                    min-width: 200px;
                                }
                                .pos-items-table td:nth-child(2) * {
                                    white-space: normal !important;
                                    word-wrap: break-word;
                                }
                            </style>
                            <div class="table-responsive mt-3">
                                <table class="table pos-items-table">
                                    <thead>
                                    <tr>
                                        <th class="text-dark" >#</th>
                                        <th class="text-dark">{{__('Items')}}</th>
                                        <th class="text-dark">{{__('Quantity')}}</th>
                                        <th class="text-dark">{{__('Refunded')}}</th>
                                        <th class="text-dark">{{__('Remaining')}}</th>
                                        <th class="text-dark">{{__('Status')}}</th>
                                        <th class="text-dark">{{__('Price')}}</th>
                                        <th class="text-dark">{{__('Discount')}}</th>
                                        <th class="text-dark">{{__('Price After')}}</th>
                                        <th class="text-dark">{{__('Tax')}}</th>
                                        <th class="text-dark">{{__('Tax Amount')}}</th>
                                        <th class="text-dark">{{__('Total')}}</th>
                                    </tr>
                                    </thead>
                                    @php
                                        $totalQuantity=0;
                                        $totalRate=0;
                                        $totalTaxPrice=0;
                                        $totalTax=0;
                                        $totalDiscount=0;
                                        $subTotal=0;
                                        $taxesData=[];
                                    @endphp
                                    @foreach($iteams as $key =>$iteam)
                                        @if(!empty($iteam->tax))
                                            @php
                                                $taxes=App\Models\Utility::tax($pos->tax_id);
                                                $totalQuantity+=$iteam->quantity;
                                                $totalRate+=$iteam->price;
                                                $totalDiscount+=$iteam->discount;
                                                foreach($taxes as $taxe){
                                                    if ($taxe && $taxe->rate && $taxe->name) {
                                                        $taxDataPrice=App\Models\Utility::taxRate($taxe->rate,$iteam->price,$iteam->quantity);
                                                        if (array_key_exists($taxe->name,$taxesData))
                                                        {
                                                            $taxesData[$taxe->name] = $taxesData[$taxe->name]+$taxDataPrice;
                                                        }
                                                        else
                                                        {
                                                            $taxesData[$taxe->name] = $taxDataPrice;
                                                        }
                                                    }
                                                }
                                            @endphp
                                        @endif
                                        <tr>
                                            <td>{{$key+1}}</td>
                                            <td style="white-space: normal; word-wrap: break-word; max-width: 300px;">
                                                @php
                                                    $product = null;
                                                    $productName = '';
                                                    $productNo = '';
                                                    $categoryName = '';
                                                    $brandName = '';
                                                    $subBrandName = '';
                                                    
                                                    // Try to get product from product relationship
                                                    if($iteam->product) {
                                                        $product = $iteam->product;
                                                        $productName = $product->name ?? '';
                                                    }
                                                    
                                                    // If product relationship doesn't work, try via sub_product
                                                    if(!$product && $iteam->sub_product && $iteam->sub_product->productService) {
                                                        $product = $iteam->sub_product->productService;
                                                        $productName = $product->name ?? '';
                                                    }
                                                    
                                                    // If still empty, try direct query
                                                    if(!$product && $iteam->product_id) {
                                                        $product = \App\Models\ProductService::with(['category', 'brand', 'subBrand'])->find($iteam->product_id);
                                                        if($product) {
                                                            $productName = $product->name ?? '';
                                                        }
                                                    }
                                                    
                                                    // Load relationships if not already loaded
                                                    if($product) {
                                                        if(!$product->relationLoaded('category')) {
                                                            $product->load('category');
                                                        }
                                                        if(!$product->relationLoaded('brand')) {
                                                            $product->load('brand');
                                                        }
                                                        if(!$product->relationLoaded('subBrand')) {
                                                            $product->load('subBrand');
                                                        }
                                                        
                                                        // Get category name (will be escaped by e() helper in blade)
                                                        if($product->category) {
                                                            $categoryName = $product->category->name ?? '';
                                                        }
                                                        
                                                        // Get brand name (will be escaped by e() helper in blade)
                                                        if($product->brand) {
                                                            $brandName = $product->brand->name ?? '';
                                                        }
                                                        
                                                        // Get sub brand name (will be escaped by e() helper in blade)
                                                        if($product->subBrand) {
                                                            $subBrandName = $product->subBrand->name ?? '';
                                                        }
                                                    }
                                                    
                                                    // Get product number from sub_product if available
                                                    if($iteam->sub_product && $iteam->sub_product->product_no) {
                                                        $productNo = $iteam->sub_product->product_no;
                                                    }
                                                    // If sub_product_id is null, try to find a sub_product for this product in the same warehouse
                                                    elseif(empty($productNo) && $iteam->product_id && $pos->warehouse_id) {
                                                        $subProduct = \App\Models\SubProduct::where('product_id', $iteam->product_id)
                                                            ->where('warehouse_id', $pos->warehouse_id)
                                                            ->where('pos_id', $pos->id)
                                                            ->first();
                                                        if($subProduct && $subProduct->chassis_no) {
                                                            $productNo = $subProduct->chassis_no;
                                                        }
                                                    }
                                                    
                                                    // Get custom field values
                                                    $customFieldsData = [];
                                                    if($product) {
                                                        $customFieldsData = \App\Models\CustomField::getData($product, 'product');
                                                    }
                                                @endphp
                                                
                                                <div style="white-space: normal; word-wrap: break-word; word-break: break-word;">
                                                    @if($product)
                                                        {{-- Display full hierarchy: Category → Brand → Sub Brand → Product Name --}}
                                                        <div class="product-hierarchy mb-2">
                                                            @if(!empty($categoryName))
                                                                <span class="fw-bold text-primary">{{ $categoryName }}</span>
                                                                @if(!empty($brandName) || !empty($subBrandName) || !empty($productName))
                                                                    <span class="mx-1">→</span>
                                                                @endif
                                                            @endif
                                                            
                                                            @if(!empty($brandName))
                                                                <span class="fw-semibold text-info">{{ $brandName }}</span>
                                                                @if(!empty($subBrandName) || !empty($productName))
                                                                    <span class="mx-1">→</span>
                                                                @endif
                                                            @endif
                                                            
                                                            @if(!empty($subBrandName))
                                                                <span class="fw-semibold text-success">{{ $subBrandName }}</span>
                                                                @if(!empty($productName))
                                                                    <span class="mx-1">→</span>
                                                                @endif
                                                            @endif
                                                            
                                                            @if(!empty($productName))
                                                                <span class="fw-bold">{{ $productName }}</span>
                                                            @endif
                                                            
                                                            @if(!empty($productNo))
                                                                <span class="text-muted ms-1">({{ $productNo }})</span>
                                                            @endif
                                                        </div>
                                                        
                                                        {{-- Display custom field values --}}
                                                        @if($customFieldsData && $customFieldsData->count() > 0)
                                                            <div class="custom-fields mt-2 pt-2 border-top">
                                                                @php
                                                                    // Get custom field names for display
                                                                    $customFields = \App\Models\CustomField::where('module', 'product')
                                                                        ->where('created_by', \Auth::user()->creatorId())
                                                                        ->get()
                                                                        ->keyBy('id');
                                                                @endphp
                                                                @foreach($customFieldsData as $fieldId => $fieldValue)
                                                                    @php
                                                                        $customField = $customFields->get($fieldId);
                                                                        $fieldName = $customField ? $customField->name : 'Field #' . $fieldId;
                                                                        // Handle array values (multi-select dropdowns)
                                                                        if(is_array($fieldValue)) {
                                                                            $fieldValue = implode(', ', $fieldValue);
                                                                        }
                                                                    @endphp
                                                                    @if(!empty($fieldValue))
                                                                        <div class="custom-field-item mb-1">
                                                                            <small class="text-muted">
                                                                                <strong>{{ $fieldName }}:</strong> 
                                                                                <span class="text-dark">{{ $fieldValue }}</span>
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @else
                                                        {{-- Fallback if product not found --}}
                                                        @if(!empty($productName))
                                                            <div>{{ $productName }}</div>
                                                        @else
                                                            <div>{{ $iteam->product_id ? 'Product ID: ' . $iteam->product_id : '-' }}</div>
                                                        @endif
                                                        @if(!empty($productNo))
                                                            <span class="text-muted">({{ $productNo }})</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{$iteam->quantity}}</td>
                                            <td>
                                                @php
                                                    // Get total refunded quantity for this pos_product
                                                    $totalRefundedQty = \App\Models\PosProductsRefund::where('pos_products_id', $iteam->id)
                                                        ->sum('quantity');
                                                @endphp
                                                @if($totalRefundedQty > 0)
                                                    <span class="text-danger">{{ $totalRefundedQty }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    // Calculate remaining quantity: original quantity - total refunded
                                                    $remainingQty = $iteam->quantity - $totalRefundedQty;
                                                @endphp
                                                @if($remainingQty > 0)
                                                    <span class="text-success">{{ $remainingQty }}</span>
                                                @else
                                                    <span class="text-danger">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    // Get status from pos_product or calculate based on remaining quantity
                                                    $itemStatus = $iteam->status ?? 'active';
                                                    if ($remainingQty <= 0) {
                                                        $itemStatus = 'refunded';
                                                    } elseif ($totalRefundedQty > 0) {
                                                        $itemStatus = 'partial_refund';
                                                    }
                                                @endphp
                                                @if($itemStatus == 'refunded')
                                                    <span class="badge bg-danger">{{ __('Refunded') }}</span>
                                                @elseif($itemStatus == 'partial_refund')
                                                    <span class="badge bg-warning">{{ __('Partial Refund') }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    // Replace price with combo_price if combo exists
                                                    // combo_price can be 0.00 for free items in BOGO combos
                                                    $displayPrice = $iteam->price;
                                                    if ($iteam->compo_id != 0 && $iteam->compo_id != '0' && $iteam->combo_price !== null) {
                                                        $displayPrice = $iteam->combo_price;
                                                    }
                                                @endphp
                                                {{\Auth::user()->priceFormat($displayPrice)}}
                                            </td>
                                            <td>{{ $iteam->discount }} %</td>
                                            <td>
                                                @php
                                                    // Calculate price after discount
                                                    // If combo exists, use combo_price as base, otherwise use regular price
                                                    $basePrice = $displayPrice;
                                                    $priceAfterDiscount = $basePrice - ($basePrice * ($iteam->discount / 100));
                                                @endphp
                                                {{ \Auth::user()->priceFormat($priceAfterDiscount) }}
                                            </td>
                                            <td>
                                                @php
                                                    $itemTaxPrice = 0;
                                                    $itemTaxRate = 0;
                                                    // Determine base price for tax calculation
                                                    // Use combo_price if combo exists (can be 0.00 for free items in BOGO)
                                                    $taxBasePrice = $iteam->price;
                                                    if ($iteam->compo_id != 0 && $iteam->compo_id != '0' && $iteam->combo_price !== null) {
                                                        $taxBasePrice = $iteam->combo_price;
                                                    }
                                                    $priceAfterDiscountForTax = $taxBasePrice - ($taxBasePrice * ($iteam->discount / 100));
                                                @endphp
                                                @if(!empty($pos->tax_id))
                                                    <table>
                                                        @foreach(App\Models\Utility::tax($pos->tax_id) as $tax)
                                                            @if($tax && $tax->rate && $tax->name)
                                                                @php
                                                                    $taxPrice=App\Models\Utility::taxRate($tax->rate, $priceAfterDiscountForTax, $iteam->quantity);
                                                                    $itemTaxPrice+=$taxPrice;
                                                                    $itemTaxRate+=$tax->rate;
                                                                @endphp
                                                                <tr>
                                                                    <span class="badge bg-primary">{{$tax->name .' ('.$tax->rate .'%)'}}</span> <br>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </table>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{\Auth::user()->priceFormat($itemTaxPrice)}}</td>
                                            @php
                                                // Calculate item total using combo_price if available
                                                $itemTotal = ($priceAfterDiscountForTax * $iteam->quantity);
                                                $itemTotalWithTax = $itemTotal + $itemTaxPrice;
                                                $subTotal += $itemTotal;
                                                $totalTax += $itemTaxPrice;
                                            @endphp
                                            <td>{{\Auth::user()->priceFormat($itemTotalWithTax)}}</td>
                                        </tr>
                                    @endforeach

                                    <tr>
                                        <td><b>{{__(' Sub Total')}}</b></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{\Auth::user()->priceFormat($subTotal)}}</td>
                                    </tr>
                                    @if($totalTax > 0)
                                    <tr>
                                        <td><b>{{__('Tax')}}</b></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{\Auth::user()->priceFormat($totalTax)}}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><b>{{__('Discount')}}</b></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{isset($posPayment) && $posPayment ? \Auth::user()->priceFormat($posPayment->discount ?? 0):0}}</td>
                                    </tr>
                                    <tr class="pos-header">
                                        <td><b>{{__('Total')}}</b></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{\Auth::user()->priceFormat($subTotal + $totalTax - (isset($posPayment) && $posPayment ? ($posPayment->discount ?? 0) : 0))}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    @if(!empty($vouchers) && count($vouchers) > 0)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3">{{__('Vouchers Used')}}</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-dark">{{__('Voucher ID')}}</th>
                                            <th class="text-dark">{{__('Amount')}}</th>
                                            <th class="text-dark">{{__('Validity')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vouchers as $voucherId => $voucherData)
                                            @php
                                                $voucherDetails = isset($vouchersWithDetails[$voucherId]) 
                                                    ? $vouchersWithDetails[$voucherId] 
                                                    : null;
                                                $validUntil = $voucherDetails && isset($voucherDetails['valid_until']) && $voucherDetails['valid_until'] 
                                                    ? \Carbon\Carbon::parse($voucherDetails['valid_until']) 
                                                    : null;
                                                $isActive = $voucherDetails && isset($voucherDetails['active']) ? $voucherDetails['active'] : true;
                                                $isExpiredByDate = $validUntil ? $validUntil->isPast() : false;
                                                $isExpired = !$isActive || $isExpiredByDate;
                                                $isExpiringSoon = $validUntil && $validUntil->isFuture() && $validUntil->diffInDays(now()) <= 7 && $isActive;
                                            @endphp
                                            <tr>
                                                <td>{{ $voucherId }}</td>
                                                <td>{{ \Auth::user()->priceFormat($voucherData['amount']) }}</td>
                                                <td>
                                                    @if($validUntil)
                                                        <span class="badge {{ $isExpired ? 'bg-danger' : ($isExpiringSoon ? 'bg-warning' : 'bg-success') }}" 
                                                              title="{{ $isExpired ? __('Expired') : ($isExpiringSoon ? __('Expiring Soon') : __('Valid')) }}">
                                                            {{ \Auth::user()->dateFormat($validUntil) }}
                                                        </span>
                                                        @if($isExpired)
                                                            <small class="text-danger d-block">
                                                                @if(!$isActive)
                                                                    {{ __('Voucher Expired') }}
                                                                @elseif($isExpiredByDate)
                                                                    {{ __('Expired') }}
                                                                @else
                                                                    {{ __('Expired') }}
                                                                @endif
                                                            </small>
                                                        @elseif($isExpiringSoon)
                                                            <small class="text-warning d-block">{{ __('Expiring Soon') }}</small>
                                                        @endif
                                                    @else
                                                        @if(!$isActive)
                                                            <span class="badge bg-danger" title="{{ __('Voucher is not active') }}">
                                                                {{ __('Not Active') }}
                                                            </span>
                                                            <small class="text-danger d-block">{{ __('Voucher is not active') }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($posPayment) && $posPayment)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-5">{{__('Payment Information')}}</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-dark">{{__('Payment Date')}}</th>
                                            <th class="text-dark">{{__('POS Amount')}}</th>
                                            <th class="text-dark">{{__('Total Customer Payment')}}</th>
                                            <th class="text-dark">{{__('Return/Change')}}</th>
                                            <th class="text-dark">{{__('Discount')}}</th>
                                            @if($posPayment->voucher_id && $posPayment->voucher)
                                            <th class="text-dark">{{__('Voucher Amount')}}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Get POS amount (bill total) - this is the bill amount
                                            $posAmount = $posPayment->amount ?? ($subTotal + $totalTax - ($posPayment->discount ?? 0));
                                            
                                            // Get total customer payment (what customer actually paid)
                                            // If total_user_payment exists, use it; otherwise calculate from payment methods
                                            $totalCustomerPayment = null;
                                            if($posPayment->total_user_payment !== null && $posPayment->total_user_payment > 0) {
                                                $totalCustomerPayment = $posPayment->total_user_payment;
                                            } else {
                                                // Fallback: calculate from payment methods if total_user_payment is not set (old records)
                                                $totalCustomerPayment = 0;
                                                if(isset($paymentMethods) && !empty($paymentMethods)) {
                                                    foreach($paymentMethods as $pm) {
                                                        $totalCustomerPayment += $pm['amount'] ?? 0;
                                                    }
                                                }
                                                // If no payment methods found, use POS amount as fallback
                                                if($totalCustomerPayment == 0) {
                                                    $totalCustomerPayment = $posAmount;
                                                }
                                            }
                                            
                                            // Calculate return/change amount
                                            $returnAmount = max(0, $totalCustomerPayment - $posAmount);
                                        @endphp
                                        <tr>
                                            <td>{{ \Auth::user()->dateFormat($posPayment->date) }}</td>
                                            <td class="fw-bold">{{ \Auth::user()->priceFormat($posAmount) }}</td>
                                            <td class="fw-bold text-primary">{{ \Auth::user()->priceFormat($totalCustomerPayment) }}</td>
                                            <td class="fw-bold {{ $returnAmount > 0 ? 'text-success' : 'text-muted' }}">
                                                {{ \Auth::user()->priceFormat($returnAmount) }}
                                                @if($returnAmount > 0)
                                                    <small class="d-block text-muted">({{ __('Change') }})</small>
                                                @endif
                                            </td>
                                            <td>{{ \Auth::user()->priceFormat($posPayment->discount ?? 0) }}</td>
                                            @if($posPayment->voucher_id && $posPayment->voucher)
                                            <td>
                                                {{ \Auth::user()->priceFormat($posPayment->voucher->amount ?? 0) }}
                                            </td>
                                            @endif
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            @if(isset($paymentMethods) && !empty($paymentMethods))
                            <div class="mt-4">
                                <h6 class="mb-5">{{__('Payment Methods')}}</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-dark">{{__('Payment Method')}}</th>
                                                <th class="text-dark">{{__('Amount')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paymentMethods as $pm)
                                            <tr>
                                                <td>{{ $pm['name'] }}</td>
                                                <td>{{ \Auth::user()->priceFormat($pm['amount']) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @if(isset($logs))
        @include('partials.pos_logs', ['logs' => $logs])
    @endif
@endsection
