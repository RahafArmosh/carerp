@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Create') }}</li>
@endsection
@push('script-page')
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        $(document).ready(function() {
            function initSelect2() {
                $('.item').select2({
                    width: '100%',
                    placeholder: 'Search Account...',
                    allowClear: true
                });
            }

            // Initialize on page load
            initSelect2();

            // Reinitialize when new row is added dynamically
            $(document).on('click', '[data-repeater-create]', function() {
                setTimeout(() => {
                    initSelect2();
                }, 100);
            });


        });
        var selector = "body";
        let TotalTax = 0;
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }

                    // for item SearchBox ( this function is  custom Js )
                    JsSearchBox();

                    // Update currency symbol in the new repeated item
                    var currentSymbol = $('.currency-symbol').first().text();
                    $(this).find('.currency-symbol').text(currentSymbol);

                    // Initialize Select2 for the new item select
                    $(this).find('.item').select2({
                        width: '100%',
                        placeholder: 'Search Item...',
                        allowClear: true
                    });

                    // Set default values for new row
                    $(this).find('.quantity').val(1);
                    $(this).find('.price').val('');
                    $(this).find('.discount').val(0);
                    $(this).find('.amount').text('0.00');

                    // Recalculate totals
                    calculateTotal();
                },
                hide: function(deleteElement) {
                    const swalWithBootstrapButtons = Swal.mixin({
                        customClass: {
                            confirmButton: "btn btn-success",
                            cancelButton: "btn btn-danger",
                        },
                        buttonsStyling: false,
                    });
                    swalWithBootstrapButtons
                        .fire({
                            title: "Are you sure?",
                            text: "This action can not be undone. Do you want to continue?",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Yes",
                            cancelButtonText: "No",
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $(this).slideUp(deleteElement);
                                $(this).remove();
                                calculateTotal();
                            }
                        });
                },
                ready: function(setIndexes) {
                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
            }

            // Ensure custom fields render for existing rows (including the first) on load
            setTimeout(function initCustomFieldsForExistingRows() {
                $('select.item').each(function() {
                    var selectEl = $(this);
                    var selectedVal = selectEl.val();
                    if (selectedVal) {
                        var itemNameAttr = selectEl.attr('name') || '';
                        var match = itemNameAttr.match(/\d+/);
                        var rowIndex = match ? match[0] : 0;
                        var $itemRow = selectEl.closest('tr');
                        loadCustomFields(selectedVal, $itemRow, rowIndex);
                    }
                });
            }, 1000);
        }

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

        $(document).on('change', '.item', function() {
            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);
            var $itemRow = $(this).closest('tr');
            var itemNameAttr = $(this).attr('name');
            var rowIndex = itemNameAttr.match(/\d+/)[0];

            // Load custom fields for this item
            loadCustomFields(iteams_id, $itemRow, rowIndex);

            if (!iteams_id) {
                // Clear fields if no item selected
                $itemRow.find('.quantity').val('');
                $itemRow.find('.price').val('');
                $itemRow.find('.discount').val('');
                $itemRow.find('.amount').text('0.00');
                $itemRow.find('.unit').html('');
                calculateTotal();
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    try {
                        var item = JSON.parse(data);

                        // Set default values in the current row
                        $itemRow.find('.quantity').val(1);
                        $itemRow.find('.price').val(item.product.purchase_price);
                        $itemRow.find('.discount').val(0);
                        $itemRow.find('.unit').html(item.unit || '');

                        // Calculate and set amount for this row
                        var qty = parseFloat($itemRow.find('.quantity').val()) || 0;
                        var price = parseFloat($itemRow.find('.price').val()) || 0;
                        var discount = parseFloat($itemRow.find('.discount').val()) || 0;
                        var amount = (qty * price) - (qty * discount);
                        $itemRow.find('.amount').text(amount.toFixed(2));

                        // Recalculate all totals
                        calculateTotal();

                    } catch (e) {
                        console.error('Error parsing item data:', e);
                        // Clear fields on error
                        $itemRow.find('.quantity').val('');
                        $itemRow.find('.price').val('');
                        $itemRow.find('.discount').val('');
                        $itemRow.find('.amount').text('0.00');
                        $itemRow.find('.unit').html('');
                        calculateTotal();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    // Clear fields on error
                    $itemRow.find('.quantity').val('');
                    $itemRow.find('.price').val('');
                    $itemRow.find('.discount').val('');
                    $itemRow.find('.amount').text('0.00');
                    $itemRow.find('.unit').html('');
                    calculateTotal();
                }
            });
        });

        function calculateRowTotal(row) {
            let qty = parseFloat(row.find('.quantity').val()) || 0;
            let price = parseFloat(row.find('.price').val()) || 0;
            let discount = parseFloat(row.find('.discount').val()) || 0;

            let baseAmount = (qty * price) - (qty * discount);
            baseAmount = baseAmount > 0 ? baseAmount : 0;

            row.find('.amount').text(baseAmount.toFixed(2));
            return baseAmount;
        }

        function calculateTotal() {
            let subTotal = 0;

            // Calculate subtotal from all item rows
            $('[data-repeater-item]').each(function() {
                let row = $(this);
                subTotal += calculateRowTotal(row);
            });

            // Get selected tax rates and calculate tax
            let totalTax = 0;
            let taxRates = [];
            $('#choices-multiple1 option:selected').each(function() {
                let rate = parseFloat($(this).data('rate')) || 0;
                if (rate > 0) {
                    taxRates.push(rate);
                    totalTax += subTotal * (rate / 100);
                }
            });

            // Update tax display
            if (taxRates.length > 0) {
                $('.tax_val').text(taxRates.join('%, ') + '%');
            } else {
                $('.tax_val').text('0.00');
            }

            // Update totals
            $('.subTotal').text(subTotal.toFixed(2));
            $('.totalTax').text(totalTax.toFixed(2));
            $('.totalAmount').text((subTotal + totalTax).toFixed(2));
        }

        // Trigger calculation on item change
        $(document).on('input', '.quantity, .price, .discount', function() {
            calculateTotal();
        });

        $(document).on('change', '#choices-multiple1', function() {
            calculateTotal();
        });
        $(document).on('click', '[data-repeater-delete]', function(e) {
            e.preventDefault();
            // Recalculate totals
            calculateTotal();
        });

        // Normalize any stray custom field inputs before form submit
        $(document).on('submit', 'form[action$="bill"]', function() {
            $('input[name^="items["], select[name^="items["], textarea[name^="items["]').each(function() {
                var n = $(this).attr('name');
                // Match items[INDEX][FIELDID] (numeric FIELDID) and rewrite
                var m = n && n.match(/^items\[(\d+)\]\[(\d+)\]$/);
                if (m) {
                    $(this).attr('name', 'items[' + m[1] + '][custom_fields][' + m[2] + ']');
                }
            });
        });

        function loadCustomFields(iteams_id, $itemRow, rowIndex) {
            $.ajax({
                url: '/get-custom-fields',
                type: 'GET',
                data: {
                    product_id: iteams_id
                },
                success: function(response) {
                    // Remove old custom field row if it exists (to prevent duplication)
                    var $customFieldsRow = $itemRow.next('.custom-fields-row');
                    $itemRow.next('tr.custom-fields-row').remove();

                    // Only proceed if there are custom fields
                    if (!response.customFields || response.customFields.length === 0) {
                        return;
                    }

                    // Build custom fields HTML
                    let customFieldsHtml = `
                            <tr class="custom-fields-row bg-light border-top">
                                <td colspan="7">
                                    <div class="row g-3 py-3 custom-fields-container">
                        `;

                    response.customFields.forEach(field => {
                        let fieldName = field.name.replace(/\s+/g, '_');
                        let fieldID = field.id;
                        let inputField = '';

                        switch (field.type) {
                            case 'text':
                            case 'email':
                            case 'number':
                            case 'date':
                                inputField = `<input type="${field.type}" class="form-control"
                                        name="items[${rowIndex}][custom_fields][${fieldID}]">`;
                                break;
                            case 'textarea':
                                inputField = `<textarea class="form-control"
                                        name="items[${rowIndex}][custom_fields][${fieldID}]"></textarea>`;
                                break;
                            case 'dropdown':
                                const options = JSON.parse(field.options || '[]');
                                inputField = `<select class="form-control select2"
                                        name="items[${rowIndex}][custom_fields][${fieldID}]">
                                        ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                                    </select>`;
                                break;
                        }

                        customFieldsHtml += `
                                <div class="col-md-4">
                                    <div class="form-group d-flex flex-column">
                                        <label class="form-label fw-bold">${field.name}</label>
                                        ${inputField}
                                    </div>
                                </div>
                            `;
                    });

                    customFieldsHtml += `
                                    </div>
                                </td>
                            </tr>
                        `;

                    // Add custom fields row after current item row
                    $itemRow.after(customFieldsHtml);

                    // Initialize Select2 for newly added dropdowns
                    $itemRow.next('.custom-fields-row').find('.select2').select2();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching custom fields:', error);
                }
            });
        }

        var vendorId = '{{ $vendorId }}';
        if (vendorId > 0) {
            $('#vender').val(vendorId).change();
        }
    </script>
    <script>
        $(document).ready(function() {
            const exchangeRateDiv = document.getElementById('exchange_rate_div');

            // Set default currency symbol on page load
            $('.currency-symbol').text('{{ \Auth::user()->currencySymbol() }}');

            $('#currency_id').change(function() {
                var currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}'; // Default

                if (currencyId === '') {
                    // Default selected (empty value)
                    $('.currency-symbol').text(symbol);
                    $('#exchange_rate_div').hide();
                    $('#exchange_rate').val('');
                } else {
                    // Fetch symbol from backend
                    fetch('/get-exchange-rate/' + currencyId)
                        .then(response => response.json())
                        .then(data => {
                            $('.currency-symbol').text(data.symbol || data.code);
                            $('#exchange_rate_div').show();
                            $('#exchange_rate').val(data.exchange_rate);
                        })
                        .catch(() => {
                            $('.currency-symbol').text(symbol);
                        });
                }
            });
        });
    </script>
@endpush
@section('content')
    <div class="row">
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <form method="POST" action="{{ url('bill') }}" class="w-100" enctype="multipart/form-data">
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="vender-box">
                                    <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                    <select name="vender_id" id="vender" class="form-control select select2"
                                        data-url="{{ route('bill.vender') }}" required>
                                        @foreach ($venders as $key => $value)
                                            <option value="{{ $key }}"
                                                @if (old('vender_id') == $key || $key == $vendorId) selected @endif>
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
                                            <input type="date" name="bill_date" id="bill_date" class="form-control"
                                                value="{{ old('bill_date') }}" required>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                                            <input type="date" name="due_date" id="due_date" class="form-control"
                                                value="{{ old('due_date') }}" required>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bill_number" class="form-label">{{ __('Bill Number') }}</label>
                                            <input type="text" class="form-control" name="bill_number"
                                                value="{{ old('bill_number', $bill_number) }}" readonly>
                                            <input type="hidden" class="form-control" name="bill_numberNo"
                                                value="{{ $bill_numberNo }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                            <select name="category_id" id="category_id" class="form-control select2"
                                                data-placeholder="{{ __('Select Category') }}">
                                                <option value=""></option>
                                                @foreach ($category as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if (old('category_id') == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="order_number"
                                                class="form-label">{{ __('Reference Number') }}</label>
                                            <input type="text" name="order_number" id="order_number" class="form-control"
                                                value="{{ old('order_number') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                            <select name="warehouse_id" id="warehouse_id" class="form-control select2"
                                                required data-placeholder="{{ __('Select Warehouse') }}">
                                                <option value=""></option>
                                                @foreach ($warehouse as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if (old('warehouse_id') == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salesman_id" class="form-label">{{ __('purchase Man') }}</label>
                                            <select name="salesman_id" id="salesman_id" class="form-control select2"
                                                data-placeholder="{{ __('Select User') }}">
                                                <option value=""></option>
                                                @foreach ($users as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if (old('salesman_id') == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            <select name="currency_id" id="currency_id" class="form-control select2"
                                                data-placeholder="{{ __('Select Currency') }}" required>
                                                @foreach ($currency as $key => $value)
                                                    <option value=""></option>
                                                    <option value="{{ $key }}"
                                                        @if (old('currency_id') == $key) selected @endif>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="exchange_rate_div" style="display: none;">
                                        <div class="form-group">
                                            <label for="exchange_rate"
                                                class="form-label">{{ __('Exchange Rate') }}</label>

                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" name="exchange_rate" id="exchange_rate"
                                                    value="{{ old('exchange_rate') }}" class="form-control">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="document">Document:</label>
                                        <input type="file" class="form-control" id="documents" name="documents[]"
                                            multiple>
                                    </div>
                                    @if (!$customFields->isEmpty())
                                        <div class="col-md-6">
                                            <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                @include('customFields.formBuilder')
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Product & Services') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div
                                class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary"
                                        data-bs-toggle="modal" data-target="#add-bank">
                                        <i class="ti ti-plus"></i> {{ __('Add Item') }}
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
                                        <th width="20%">{{ __('Items') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Price') }} </th>
                                        <th>WareHousePrice </th>
                                        <th>{{ __('Discount') }} </th>
                                        <th class="text-end">{{ __('Amount') }}
                                            <br><small
                                                class="text-danger font-bold">{{ __('after tax & discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody data-repeater-item class="ui-sortable">
                                    <tr>
                                        <td width="25%" class="form-group">
                                            <select name="items[0][item]" class="form-control select2 item"
                                                data-url="{{ route('bill.product') }}">
                                                @foreach ($product_services as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="items[0][quantity]"
                                                    class="form-control quantity" placeholder="{{ __('Qty') }}">
                                                <span class="unit input-group-text bg-transparent"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="items[0][price]" class="form-control price"
                                                    placeholder="{{ __('Price') }}">
                                                <span class="input-group-text bg-transparent">
                                                    <span
                                                        class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="items[0][WareHousePrice]"
                                                    class="form-control" placeholder="{{ __('optional') }}">
                                                <span class="input-group-text bg-transparent">
                                                    <span class="currency-symbol">
                                                        {{ \Auth::user()->currencySymbol() }}
                                                    </span>
                                                </span>
                                            </div>

                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="items[0][discount]"
                                                    class="form-control discount" placeholder="{{ __('Discount') }}">
                                                <span class="input-group-text bg-transparent">
                                                    <span
                                                        class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-end amount">
                                            0.00
                                        </td>
                                        <td>
                                            @can('delete proposal product')
                                                <a href="#"
                                                    class="ti ti-trash text-white repeater-action-btn bg-danger ms-2"
                                                    data-repeater-delete></a>
                                            @endcan
                                        </td>
                                    </tr>
                                    {{-- <tr class="custom-fields-row">
                                    <td colspan="7">
                                        <div class="custom-fields-container"></div>
                                    </td>
                                </tr> --}}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>
                                            <div class="form-group col-md-10">
                                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                                <select id="choices-multiple1" name="tax_id[]" class="form-control"
                                                    required>
                                                    <option value="" disabled selected>Select Tax</option>
                                                    @foreach ($fullTax as $value)
                                                        <option value="{{ $value->id }}"
                                                            data-rate="{{ $value->rate }}">{{ $value->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </td>
                                        <td class="text-end tax_val">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Sub Total') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Tax') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalTax">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="blue-text text-end totalAmount">0.00</td>


                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal-footer">
                <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
            </div>
        </form>
    </div>
@endsection
