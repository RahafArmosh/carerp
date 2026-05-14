@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Edit') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Edit') }}</li>
@endsection
@php
    $products = session('productsQTY', []); // Retrieve the session data or an empty array if it doesn't exist
@endphp
<style>

</style>
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        var selector = "body";
        $(document).on('change', '#vender', function() {
            $('#vender_detail').removeClass('d-none');
            $('#vender_detail').addClass('d-block');
            $('#vender-box').removeClass('d-block');
            $('#vender-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    if (data != '') {
                        $('#vender_detail').html(data);
                    } else {
                        $('#vender-box').removeClass('d-none');
                        $('#vender-box').addClass('d-block');
                        $('#vender_detail').removeClass('d-block');
                        $('#vender_detail').addClass('d-none');
                    }
                },

            });
        });
        $(document).on('click', '#remove', function() {
            $('#vender-box').removeClass('d-none');
            $('#vender-box').addClass('d-block');
            $('#vender_detail').removeClass('d-block');
            $('#vender_detail').addClass('d-none');
        });
    </script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Set default currency symbol on page load
            var selectedCurrencyId = $('#currency_id').val();
            
            // If currency is selected (including default AED), fetch exchange rate on page load
            if (selectedCurrencyId && selectedCurrencyId !== '') {
                $('#exchange_rate_div').show();
                
                fetch(`/get-exchange-rate/${selectedCurrencyId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Only update if exchange_rate is empty or not set
                        if (!$('#exchange_rate').val() || $('#exchange_rate').val() === '') {
                            $('#exchange_rate').val(data.exchange_rate);
                        }
                        // Update all currency symbols
                        var symbol = '{{ \Auth::user()->currencySymbol() }}';
                        $('.currency-symbol').text(data.symbol || data.code || symbol);
                    })
                    .catch(error => {
                        console.error('Error fetching exchange rate:', error);
                    });
            }

            $('#currency_id').on('change', function() {
                const currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}'; // Default

                if (currencyId !== '') {
                    $('#exchange_rate_div').show();

                    fetch(`/get-exchange-rate/${currencyId}`)
                        .then(response => response.json())
                        .then(data => {
                            $('#exchange_rate').val(data.exchange_rate);
                            // Update all currency symbols
                            $('.currency-symbol').text(data.symbol || data.code ||
                                symbol);
                        })
                        .catch(error => {
                            console.error('Error fetching exchange rate:', error);
                        });
                } else {
                    $('#exchange_rate_div').hide();
                    $('#exchange_rate').val('');
                    // Reset to default currency symbol
                    $('.currency-symbol').text(symbol);
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('input[name="items[0][chassis_no]"]').on('paste', function(e) {
                // Prevent default paste behavior
                e.preventDefault();

                // Get pasted data
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var rows = pastedData.split('\n');

                // Update the first input field with the first value after a short delay
                setTimeout(function() {
                    var firstRowValue = rows[0].split('\t')[0]; // Assuming tab-separated data
                    $('input[name="items[0][chassis_no]"]').val(firstRowValue);

                    // Update the rest of the rows
                    for (var i = 1; i < rows.length; i++) {
                        var cols = rows[i].split('\t');
                        if (cols.length > 0) {
                            $('input[name="items[' + i + '][chassis_no]"]').val(cols[0]);
                        }
                    }
                }, 50); // Delay added to ensure proper handling after the paste event
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Use a wildcard selector to detect paste on any product_no input field
            $('input[name^="items"][name$="[product_no]"]').on('paste', function(e) {
                // Prevent default paste behavior
                e.preventDefault();

                // Get the index of the row where the paste occurred
                var currentRowIndex = $(this).attr('name').match(/\d+/)[
                    0]; // Extract row index (e.g., items[0] -> 0)

                // Get pasted data
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var rows = pastedData.split('\n');

                // Update the current input field with the first pasted value after a short delay
                setTimeout(function() {
                    // Update the field where the paste occurred
                    $('input[name="items[' + currentRowIndex + '][product_no]"]').val(rows[0]);

                    // Loop through the remaining rows and update the respective product_no fields
                    for (var i = 1; i < rows.length; i++) {
                        var nextRowIndex = parseInt(currentRowIndex) + i;
                        var value = rows[i];
                        $('input[name="items[' + nextRowIndex + '][product_no]"]').val(value);
                    }
                }, 50); // Delay added to ensure proper handling after the paste event
            });
        });

        // $(document).ready(function() {
        //     // Listen for the paste event on any text-type custom field
        //     $(document).on('paste', '.custom-field-text', function(event) {
        //         event.preventDefault(); // Prevent the default paste behavior

        //         var clipboardData = event.originalEvent.clipboardData || window.clipboardData;
        //         var pastedData = clipboardData.getData('Text'); // Get the pasted text

        //         // Split pasted data into an array (assuming new lines separate values)
        //         var pastedValues = pastedData.split('\n').map(function(item) {
        //             return item.trim();
        //         }).filter(function(item) {
        //             return item.length > 0;
        //         });

        //         var fieldId = $(this).data('field-id'); // Get field ID of the pasted column

        //         // Get all input fields with the same fieldId across rows
        //         var relatedFields = $('.custom-field-text[data-field-id="' + fieldId + '"]');

        //         // Populate values vertically for the same field type in different rows
        //         relatedFields.each(function(index) {
        //             if (pastedValues[index]) {
        //                 $(this).val(pastedValues[index]);
        //             }
        //         });
        //     });
        // });
        $(document).ready(function() {
            // Listen for the paste event on ANY custom field input type
            $(document).on('paste', '.custom-field-input', function(event) {
                event.preventDefault(); // Stop default paste

                var clipboardData = event.originalEvent.clipboardData || window.clipboardData;
                var pastedData = clipboardData.getData('Text');

                // Split into rows (handling Windows/Mac line endings)
                var pastedValues = pastedData.split(/\r?\n/);

                var fieldId = $(this).data('field-id'); // Column identifier

                // Get all fields in same column across rows
                var relatedFields = $('.custom-field-input[data-field-id="' + fieldId + '"]');

                relatedFields.each(function(index) {
                    if (pastedValues[index] !== undefined) {
                        $(this).val(pastedValues[index].trim());
                    }
                });
            });
        });
    </script>
    <script>
        function recalculateTotals() {
            let subtotal = 0;
            let totalDiscount = 0;
            let taxRate = {{ $bill->tax->rate ?? 0 }};

            $('tbody tr').each(function() {
                let qty = parseFloat($(this).find('input[name*="[qty]"]').val()) || 0;
                let price = parseFloat($(this).find('input[name*="[purchase_price]"]').val()) || 0;
                let discount = parseFloat($(this).find('input[name*="[discount]"]').val()) || 0;

                let rowTotal = qty * price;
                subtotal += rowTotal;
                totalDiscount += discount;
            });

            let tax = (subtotal - totalDiscount) * taxRate / 100;
            let total = subtotal + tax - totalDiscount;

            $('.subTotal').text(subtotal.toFixed(2));
            $('.tax_val, .totalTax').text(tax.toFixed(2));
            $('.totalDiscount').text(totalDiscount.toFixed(2));
            $('.totalAmount').text(total.toFixed(2));
        }

        // Update qty change to recalculate properly
        $(document).on('input', 'input[name*="[qty]"]', function() {
            let qtyInput = $(this);
            let row = qtyInput.closest('tr');

            // Get the original price
            let originalPrice = parseFloat(qtyInput.data('purchase-price')) || 0;

            // Set price back
            let priceInput = row.find('input[name*="[purchase_price]"]');
            priceInput.val(originalPrice.toFixed(2)).trigger('input');

            recalculateTotals();
        });

        // Recalculate on any relevant input change
        $(document).on('input change', 'input[name*="[qty]"], input[name*="[purchase_price]"], input[name*="[discount]"]',
            function() {
                recalculateTotals();
            });


        // After adding a new item row
        $(document).on('click', '.add-item', function() {
            // Your logic to add a new row
            recalculateTotals();
        });

        // After removing an item row
        $(document).on('click', '.remove-item', function() {
            $(this).closest('tr').remove();
            recalculateTotals();
        });
    </script>
    <script>
        $('#get_avg_rate_btn').on('click', function() {
            const billId = $('#bill_id').val(); // Make sure the bill ID is in a hidden input or accessible

            if (!billId) {
                alert('Bill ID not found.');
                return;
            }

            $.ajax({
                url: `/bills/${billId}/calculate-average-rate`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content') // CSRF token for security
                },
                success: function(data) {
                    if (data.success) {
                        $('#exchange_rate').val(data.new_rate);
                        Swal.fire({
                            title: 'Success',
                            text: 'New average rate calculated successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Refreshes the page after clicking "OK"
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Error calculating rate.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });

                    }
                },
                error: function() {
                    alert('An error occurred while calculating the rate.');
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[readonly-select]');
            selects.forEach(function(select) {
                select.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    this.blur(); // prevent dropdown from opening
                });
            });
        });
    </script>
@endpush
@section('content')
    <div class="row">
        <form action="{{ route('bill.update', $bill->id) }}" method="POST" class="w-100" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <input type="hidden" id="bill_id" value="{{ $bill->id }}">

            {{-- Bill Info Section --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="vender-box">
                                    <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                    <select name="vender_id" id="vender" class="form-control select2"
                                        data-url="{{ route('bill.vender') }}" required="required">
                                        @foreach ($venders as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ $bill->vender_id == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="vender_detail" class="d-none">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bill_date" class="form-label">{{ __('Bill Date') }}</label>
                                            <input type="date" id="bill_date" name="bill_date" class="form-control"
                                                required="required" value="{{ $bill->bill_date }}">
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                                            <input type="date" id="due_date" name="due_date" class="form-control"
                                                required="required" value="{{ $bill->due_date }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                            <select id="category_id" name="category_id" class="form-control select">
                                                @foreach ($category as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if (old('category_id', $bill->category_id) == $key) selected @endif>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                            <select name="warehouse_id" id="warehouse_id" class="form-control select">
                                                @foreach ($warehouse as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if (old('warehouse_id', $bill->warehouse_id) == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="order_number" class="form-label">{{ __('Order Number') }}</label>
                                            <input type="number" id="order_number" name="order_number" class="form-control"
                                                value="{{ $bill->order_number }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salesman_id" class="form-label">{{ __('SalesMan') }}</label>
                                            <select name="salesman_id" id="salesman_id" class="form-control select">
                                                @foreach ($users as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if ($key === $bill->salesman_id) selected @endif>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            @php
                                                // Find AED currency ID and rate as default
                                                $aedCurrency = \App\Models\Currency::where('code', 'AED')->first();
                                                $aedCurrencyId = $aedCurrency ? $aedCurrency->id : null;
                                                $aedExchangeRate = $aedCurrency ? ($aedCurrency->exchange_rate ?? $aedCurrency->rate ?? 1) : 1;
                                                
                                                // Use bill currency_id if set, otherwise default to AED (or first currency if AED not found)
                                                $firstCurrencyId = is_array($currency) ? array_key_first($currency) : ($currency->keys()->first() ?? null);
                                                $selectedCurrencyId = $bill->currency_id ?? ($aedCurrencyId ?? $firstCurrencyId);
                                                
                                                // Determine exchange rate: use bill's rate if currency_id exists, otherwise use AED's rate
                                                $defaultExchangeRate = $bill->currency_id 
                                                    ? ($bill->exchange_rate ?? ($bill->currency ? ($bill->currency->exchange_rate ?? $bill->currency->rate ?? '') : ''))
                                                    : ($aedExchangeRate ?? '');
                                            @endphp
                                            <select name="currency_id" id="currency_id" class="form-control select" 
                                                @if ($bill->payment_status != 0) readonly-select @endif>
                                                @foreach ($currency as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if ($selectedCurrencyId == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="exchange_rate_div">
                                        <div class="form-group">
                                            <label for="exchange_rate"
                                                class="form-label">{{ __('Exchange Rate') }}</label>

                                            <div class="input-group">
                                                <span class="input-group-text"><i class="ti ti-joint"></i></span>
                                                <input type="text" name="exchange_rate" id="exchange_rate"
                                                    value="{{ old('exchange_rate', $defaultExchangeRate) }}"
                                                    required class="form-control">
                                                @if ($bill->status == 0)
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        id="get_avg_rate_btn" title="Get Average Rate">
                                                        <i class="ti ti-refresh"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-10">
                                        <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                        <select id="choices-multiple1" name="tax_id[]" class="form-control" required>
                                            <option value="" disabled selected>Select Tax</option>
                                            @foreach ($fullTax as $value)
                                                <option value="{{ $value->id }}" data-rate="{{ $value->rate }}"
                                                    @if ($bill->tax_id == $value->id) selected @endif>
                                                    {{ $value->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="document">Document:</label>
                                        <input type="file" class="form-control" id="documents" name="documents[]"
                                            multiple>
                                    </div>
                                    {{-- @if (!$customFieldsbill->isEmpty())
                                        <div class="col-md-6">
                                            <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                @include('customFields.formBuilder')
                                            </div>
                                        </div>
                                    @endif --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product & Services Section (moved from second form) --}}
            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Product & Services') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div
                                class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-size="lg"
                                        data-url="{{ route('sub-product-bill.create', $bill->id) }}"
                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                        title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table mb-0" data-repeater-list="items" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Chassis No') }}</th>
                                        <th>{{ __('QTY') }}</th>
                                        <th>{{ __('Sale Price') }}</th>
                                        <th>{{ __('Purchase Price') }}</th>
                                        <th>{{ __('Discount') }}</th>
                                        @php
                                            $categoryId = $subProducts->isNotEmpty() && $subProducts->first() && $subProducts->first()->productService 
                                                ? $subProducts->first()->productService->category_id 
                                                : null;
                                            $customFields = collect();
                                            if ($categoryId) {
                                                $customFields = \App\Models\CustomField::where(
                                                    'created_by',
                                                    \Auth::user()->creatorId(),
                                                )
                                                    ->where('module', 'sub-product')
                                                    ->forCategory($categoryId)
                                                    ->get();
                                            }
                                        @endphp
                                        @foreach ($customFields as $customField)
                                            <th>{{ __($customField->name) }}</th>
                                        @endforeach
                                        <th>{{ __('Images') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    @foreach ($subProducts as $index => $subProduct)
                                        <tr>
                                            <td class="" width="25%">
                                                <select name="items[{{ $index }}][product_id]"
                                                    class="form-control select" required disabled>
                                                    @foreach ($product_services as $productId => $productName)
                                                        <option value="{{ $subProduct->productService->id }}"
                                                            @if ($productId === $subProduct->productService->id) selected @endif>
                                                            {{ $productName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][product_no]"
                                                    class="form-control" required value="{{ $subProduct->chassis_no }}">
                                            </td>
                                            <td>
                                                @if ($subProduct->productService->category->type == 'Qty product')
                                                    <input type="number" name="items[{{ $index }}][qty]"
                                                        class="form-control" required step="0.01"
                                                        value="{{ $subProduct->quantity }}"
                                                        data-purchase-price="{{ $subProduct->billProducts()->first()->exchange_price ?: $subProduct->billProducts()->first()->price }}">
                                                @else
                                                    <input type="number" name="items[{{ $index }}][qty]"
                                                        class="form-control" required step="0.01" value="1"
                                                        readonly>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][sale_price]"
                                                    class="form-control" required step="0.01"
                                                    value="{{ $subProduct->sale_price }}">
                                            </td>
                                            <td>
                                                @php
                                                    $billProduct = $subProduct->billProducts()->first();
                                                    $purchasePrice = $billProduct
                                                        ? $billProduct->exchange_price ?? $billProduct->price
                                                        : '';
                                                @endphp
                                                <input type="number" name="items[{{ $index }}][purchase_price]"
                                                    class="form-control price" required step="0.01"
                                                    value="{{ $purchasePrice }}"
                                                    id="purchase_price_{{ $index }}">
                                            </td>
                                            <td>
                                                @php
                                                    $billProduct = $subProduct->billProducts()->first();
                                                    $discount = $billProduct
                                                        ? ($billProduct->exchange_discount ?? $billProduct->discount ?? 0)
                                                        : 0;
                                                @endphp
                                                <input type="number" name="items[{{ $index }}][discount]"
                                                    class="form-control price" required step="0.01"
                                                    value="{{ $discount }}"
                                                    id="purchase_price_{{ $index }}">
                                            </td>
                                            @php
                                                $customFieldValues = \App\Models\CustomFieldValue::where(
                                                    'record_id',
                                                    $subProduct->id,
                                                )->pluck('value', 'field_id');
                                            @endphp
                                            @foreach ($customFields as $customField)
                                                <td>
                                                    @php
                                                        $value = isset($customFieldValues[$customField->id])
                                                            ? $customFieldValues[$customField->id]
                                                            : '';
                                                    @endphp
                                                    <div class="form-group">
                                                        @if ($customField->type == 'text')
                                                            <input type="text"
                                                                class="form-control custom-field-input custom-field-text w-100"
                                                                name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                data-field-id="{{ $customField->id }}"
                                                                value="{{ $value }}" style="min-width:100px;">
                                                        @elseif($customField->type == 'email')
                                                            <input type="email"
                                                                name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                class="form-control custom-field-input custom-field-email w-100"
                                                                data-field-id="{{ $customField->id }}"
                                                                value="{{ $value }}" style="min-width:100px;">
                                                        @elseif($customField->type == 'number')
                                                            <input type="number"
                                                                name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                class="form-control custom-field-input custom-field-number w-100"
                                                                data-field-id="{{ $customField->id }}"
                                                                value="{{ $value }}" style="min-width:100px;">
                                                        @elseif($customField->type == 'date')
                                                            <input type="date"
                                                                name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                class="form-control custom-field-input custom-field-date w-100"
                                                                data-field-id="{{ $customField->id }}"
                                                                value="{{ $value }}" style="min-width:100px;">
                                                        @elseif($customField->type == 'textarea')
                                                            <textarea name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                class="form-control custom-field-input custom-field-textarea w-100" data-field-id="{{ $customField->id }}"
                                                                style="min-width:100px;">{{ $value }}</textarea>
                                                        @elseif($customField->type == 'dropdown')
                                                            @php
                                                                $options = json_decode($customField->options, true);
                                                            @endphp
                                                            <select id="customField-{{ $customField->id }}"
                                                                name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                class="form-control w-100" style="min-width:100px;">
                                                                @foreach ($options as $option)
                                                                    <option value="{{ $option }}"
                                                                        {{ $value == $option ? 'selected' : '' }}>
                                                                        {{ $option }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td style="min-width: 140px;">
                                                @if ($subProduct->relationLoaded('images') && $subProduct->images->isNotEmpty())
                                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                                        @foreach ($subProduct->images->take(4) as $img)
                                                            <img src="{{ $img->url() }}" alt=""
                                                                class="rounded border"
                                                                style="width:36px;height:36px;object-fit:cover;">
                                                        @endforeach
                                                        @if ($subProduct->images->count() > 4)
                                                            <span class="text-muted small align-self-center">+{{ $subProduct->images->count() - 4 }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                <input type="file"
                                                    name="items[{{ $index }}][sub_product_images][]"
                                                    class="form-control form-control-sm"
                                                    accept="image/*" multiple>
                                            </td>
                                            <td class="Action">
                                                {{-- <div class="action-btn bg-danger ms-2">
                                                    <form
                                                        action="{{ route('sub-product-bill.delete', ['id' => $subProduct->id, 'bill_id' => $bill->id]) }}"
                                                        method="POST" id="{{ $subProduct->id }}">
                                                        @csrf
                                                        <button type="button"
                                                            onclick="confirmDelete('{{ $subProduct->id }}')"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                </div> --}}
                                            </td>
                                        </tr>
                                        <input type="hidden" id="sub_product_id"
                                            name="items[{{ $index }}][sub_product_id]"
                                            value="{{ $subProduct->id }}">
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Sub Total') }} (<span
                                                    class="currency-symbol">{{ $currency_symbol }}</span>)</strong>
                                        </td>
                                        <td class="text-end subTotal">{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Discount') }} (<span
                                                    class="currency-symbol">{{ $currency_symbol }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalDiscount">{{ number_format($totalDiscount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Tax') }} (<span
                                                    class="currency-symbol">{{ $currency_symbol }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalTax">{{ number_format($totalTax, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end blue-text">
                                            <strong>{{ __('Total Amount') }} (<span
                                                    class="currency-symbol">{{ $currency_symbol }}</span>)</strong>
                                        </td>
                                        <td class="blue-text text-end totalAmount">{{ number_format($totalAmount, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div class="d-flex justify-content-center mt-3">
                                {{ $subProducts->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('bill.index') }}';" class="btn btn-light me-3">
                <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
@endsection
