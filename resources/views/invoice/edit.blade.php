@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Edit') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice Edit') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Set default currency symbol on page load

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
        $('#get_avg_rate_btn').on('click', function() {
            const invoiceId = invoice_id // Make sure the bill ID is in a hidden input or accessible

            if (!invoiceId) {
                alert('Invoice ID not found.');
                return;
            }

            $.ajax({
                url: `/invoices/${invoiceId}/calculate-average-rate`,
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
        $(document).ready(function() {
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            var totalAmount = $('.subTotal').val();

            $('.totalTax').html((parseInt(totalAmount) * (parseInt(taxValue) / 100)).toFixed(2));
            $('.totalAmount').html((parseInt(totalAmount) + (parseInt(totalAmount) * (parseInt(taxValue) / 100)))
                .toFixed(2));


        });

        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: true,
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
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }
                },
                hide: function(deleteElement) {


                    $(this).slideUp(deleteElement);
                    $(this).remove();
                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }
                    $('.subTotal').html(subTotal.toFixed(2));
                    var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
                    $('.totalAmount').html(subTotal + (subTotal * (taxValue / 100)).toFixed(2));

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
                for (var i = 0; i < value.length; i++) {
                    var tr = $('#sortable-table .id[value="' + value[i].id + '"]').parent();
                    tr.find('.item').val(value[i].product_id);
                    changeItem(tr.find('.item'));
                }
            }

        }

        $(document).on('change', '#customer', function() {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
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
                        $('#customer_detail').html(data);
                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }
                },

            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.item', function() {
            changeItem($(this));
        });

        var invoice_id = '{{ $invoice->id }}';

        function changeItem(element) {

            var iteams_id = element.val();
            var url = element.data('url');
            var el = element;
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
                    var item = JSON.parse(data);

                    $.ajax({
                        url: '{{ route('invoice.items') }}',
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': jQuery('#token').val()
                        },
                        data: {
                            'invoice_id': invoice_id,
                            'product_id': iteams_id,
                        },
                        cache: false,
                        success: function(data) {
                            var invoiceItems = JSON.parse(data);
                            if (invoiceItems != null) {

                                var amount = (invoiceItems.price * invoiceItems.quantity);

                                $(el.parent().parent().find('.quantity')).val(invoiceItems
                                    .quantity);
                                $(el.parent().parent().find('.price')).val(invoiceItems.price);
                                $(el.parent().parent().find('.discount')).val(invoiceItems
                                    .discount);
                                $(el.parent().parent().parent().find('.pro_description')).val(
                                    invoiceItems.description);
                                // $('.pro_description').text(invoiceItems.description);

                            } else {

                                $(el.parent().parent().find('.quantity')).val(1);
                                $(el.parent().parent().find('.price')).val(item.product.sale_price);
                                $(el.parent().parent().find('.discount')).val(0);
                                // $(el.parent().parent().find('.pro_description')).val(item.product.description);
                                $(el.parent().parent().parent().find('.pro_description')).val(item
                                    .product.description);
                                // $('.pro_description').text(item.product.description);

                            }

                            var taxes = '';
                            var tax = [];

                            var totalItemTaxRate = 0;
                            for (var i = 0; i < item.taxes.length; i++) {
                                taxes +=
                                    '<span class="badge bg-primary p-2 px-3 rounded mt-1 mr-1">' +
                                    item.taxes[i].name + ' ' + '(' + item.taxes[i].rate + '%)' +
                                    '</span>';
                                tax.push(item.taxes[i].id);
                                totalItemTaxRate += parseFloat(item.taxes[i].rate);
                            }

                            var discount = $(el.parent().parent().find('.discount')).val();


                            if (invoiceItems != null) {
                                var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) *
                                    parseFloat((invoiceItems.price * invoiceItems.quantity) -
                                        discount);
                            } else {
                                var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) *
                                    parseFloat((item.product.sale_price * 1) - discount);
                            }

                            $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(
                                2));
                            $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate
                                .toFixed(2));
                            $(el.parent().parent().find('.taxes')).html(taxes);
                            $(el.parent().parent().find('.tax')).val(tax);
                            $(el.parent().parent().find('.unit')).html(item.unit);
                            // $(el.parent().parent().find('.discount')).val(item.discount);


                            var inputs = $(".amount");
                            var subTotal = 0;
                            for (var i = 0; i < inputs.length; i++) {
                                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                            }

                            var totalItemPrice = 0;
                            var inputs_quantity = $(".quantity");

                            var priceInput = $('.price');
                            for (var j = 0; j < priceInput.length; j++) {
                                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(
                                    inputs_quantity[j].value));
                            }


                            var totalItemTaxPrice = 0;
                            var itemTaxPriceInput = $('.itemTaxPrice');
                            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                                if (invoiceItems != null) {
                                    $(el.parent().parent().find('.amount')).html(parseFloat(
                                        amount) + parseFloat(itemTaxPrice) - parseFloat(
                                        discount));
                                } else {
                                    $(el.parent().parent().find('.amount')).html(parseFloat(item
                                        .totalAmount) + parseFloat(itemTaxPrice));
                                }

                            }

                            var totalItemDiscountPrice = 0;
                            var itemDiscountPriceInput = $('.discount');

                            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k]
                                    .value);
                            }


                            $('.subTotal').html(totalItemPrice.toFixed(2));
                            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
                            $('.totalTax').html((totalItemPrice * (taxValue / 100)).toFixed(2));
                            $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                                totalItemDiscountPrice) + parseFloat(totalItemPrice * (
                                taxValue / 100))).toFixed(2));
                            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));

                        }
                    });


                },
            });
        }

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            // $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            $('.totalTax').html((totalItemPrice * (taxValue / 100)).toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal) + (subTotal * (taxValue / 100))).toFixed(2));

        })

        $(document).on('keyup change', '.price', function() {

            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }


            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);

            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            // $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            $('.totalTax').html((totalItemPrice * (taxValue / 100)).toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal) + (totalItemPrice * (taxValue / 100))).toFixed(2));

        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var price = $(el.find('.price')).val();

            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);



            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));


            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            // $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            var taxValue = <?php echo json_encode($totalTaxPrice); ?>;
            $('.totalTax').html((totalItemPrice * (taxValue / 100)).toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));


        })

        $(document).on('click', '[data-repeater-create]', function() {
            $('.item :selected').each(function() {
                var id = $(this).val();
                $(".item option[value=" + id + "]").prop("disabled", true);
            });
        })

        $(document).on('click', '[data-repeater-delete]', function() {
            // $('.delete_item').click(function () {
            if (confirm('Are you sure you want to delete this element?')) {
                var el = $(this).parent().parent();
                var id = $(el.find('.id')).val();
                var amount = $(el.find('.amount')).html();

                $.ajax({
                    url: '{{ route('invoice.product.destroy') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': jQuery('#token').val()
                    },
                    data: {
                        'id': id,
                        'amount': amount,
                    },
                    cache: false,
                    success: function(data) {

                    },
                });

            }
        });
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
@endpush

@section('content')
    {{--    @dd($invoice) --}}
    <div class="row">
        <form method="POST" action="{{ route('invoice.update', $invoice->id) }}" class="w-100" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="customer-box">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select id="customer_id" name="customer_id" class="form-control select"
                                        required="required" id="customer" data-url="{{ route('invoice.customer') }}">
                                        @foreach ($customers as $id => $customer)
                                            <option value="{{ $id }}"
                                                @if ($id === $invoice->customer_id) selected @endif>{{ $customer }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="customer_detail" class="d-none">
                                </div>
                                @if ($invoice->driver_id)
                                    <div class="form-group mt-3" id="driver-box">
                                        <label for="driver_id" class="form-label">{{ __('Driver') }}</label>
                                        <select name="driver_id" id="driver_id" class="form-control select">
                                            <option value="">{{ __('Select Driver') }}</option>
                                            @foreach ($customers as $id => $customer)
                                                <option value="{{ $id }}"
                                                    @if ($id == $invoice->driver_id) selected @endif>{{ $customer }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                            <div class="form-icon-user">
                                                <input type="date" id="issue_date" name="issue_date" class="form-control"
                                                    required="required" value="{{ $invoice->issue_date }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                                            <div class="form-icon-user">
                                                <input type="date" id="due_date" name="due_date" class="form-control"
                                                    required="required" value="{{ $invoice->due_date }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number"
                                                class="form-label">{{ __('Invoice Number') }}</label>

                                            <div class="form-icon-user">
                                                <input type="text" class="form-control"
                                                    value="{{ \Auth::user()->invoiceNumberFormat($invoice->id) }}"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                        <select id="category_id" name="category_id" class="form-control select"
                                            required="required">
                                            @foreach ($category as $key => $value)
                                                <option value="{{ $key }}"
                                                    @if ($key === $invoice->category_id) selected @endif>{{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ref_number" class="form-label">{{ __('Ref Number') }}</label>
                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" id="ref_number" name="ref_number" class="form-control"
                                                    value="{{ $invoice->ref_number }}">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salesman_id" class="form-label">{{ __('SalesMan') }}</label>
                                            <select name="salesman_id" id="salesman_id" class="form-control select">
                                                @foreach ($users as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if ($key === $invoice->salesman_id) selected @endif>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            <select name="currency_id" id="currency_id" class="form-control select"
                                                @if ($invoice->payment_status != 0) readonly-select @endif>
                                                @foreach ($currency as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if ($invoice->currency_id == $key) selected @endif>
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
                                                    value="{{ $invoice->exchange_rate ?? ($invoice->currency ? $invoice->currency->rate : '') }}"
                                                    class="form-control">
                                                @if ($invoice->status == 0)
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
                                        <select id="choices-multiple1" name="tax_id[]" class="form-control" required
                                            data-placeholder="{{ __('Select Tax') }}">
                                            <option value=""></option>
                                            @foreach ($fullTax as $value)
                                                <option value="{{ $value->id }}" data-rate="{{ $value->rate }}"
                                                    @if ($invoice->tax_id == $value->id) selected @endif>
                                                    {{ $value->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="discount_account_id"
                                                class="form-label">{{ __('Discount Account') }}</label>
                                            <select name="discount_account_id" id="discount_account_id"
                                                class="form-control select">
                                                @foreach ($chartAccounts as $key => $value)
                                                    <option value="{{ $key }}"
                                                        @if ($key === $invoice->discount_account_id) selected @endif>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="document">Document:</label>
                                        <input type="file" class="form-control" id="documents" name="documents[]"
                                            multiple>
                                    </div>
                                    {{--                                <div class="col-md-6"> --}}
                                    {{--                                    <div class="form-check custom-checkbox mt-4"> --}}
                                    {{--                                        <input class="form-check-input" type="checkbox" name="discount_apply" id="discount_apply" {{$invoice->discount_apply==1?'checked':''}}> --}}
                                    {{--                                        <label class="form-check-label" for="discount_apply">{{__('Discount Apply')}}</label> --}}
                                    {{--                                    </div> --}}
                                    {{--                                </div> --}}

                                    {{--                                <div class="col-md-6"> --}}
                                    {{--                                    <div class="form-group"> --}}
                                    {{--                                        {{Form::label('sku',__('SKU')) }} --}}
                                    {{--                                        {!!Form::text('sku', null,array('class' => 'form-control','required'=>'required')) !!} --}}
                                    {{--                                    </div> --}}
                                    {{--                                </div> --}}
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

            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('invoice.index') }}';" class="btn btn-light me-3">
                <input type="submit" value="{{ __('Update Products') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
@endsection
