@extends('layouts.admin')
@section('page-title')
    {{ __('Proposal Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proposal.index') }}">{{ __('Proposal') }}</a></li>
    <li class="breadcrumb-item">{{ __('Proposal Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2-container').css({
                'width': '100%', // Set the width to match your form's width
                'border': '1px solid #ccc', // Example border style
                'border-radius': '4px', // Example border-radius
                'box-shadow': 'none', // Example box-shadow
            });
            // Set default currency symbol on page load
            $('.currency-symbol').text('{{ \Auth::user()->currencySymbol() }}');

            $('#currency_id').change(function() {
                var currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}'; // Default

                if (currencyId === '') {
                    $('.currency-symbol').text(symbol);
                    $('#exchange_rate_div').hide();
                    $('#exchange_rate').val('');
                } else {
                    // Fetch symbol from backend
                    fetch('/get-exchange-rate/' + currencyId)
                        .then(response => response.json())
                        .then(data => {
                            $('.currency-symbol').text(data.symbol || data.code || symbol);
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
    <script>
        var selector = "body";
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
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
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


            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);
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

                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    // $('.pro_description').text(item.product.description);

                    var taxes = '';
                    var tax = [];

                    var totalItemTaxRate = 0;
                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge  badge bg-primary mt-1 mr-2">' + item.taxes[i]
                                .name + ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }

                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (item.product.sale_price *
                        1));

                    $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    $(el.parent().parent().find('.taxes')).html(taxes);
                    $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);
                    $(el.parent().parent().find('.amount')).html(item.totalAmount);


                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value);
                    }

                    var totalItemTaxPrice = 0;
                    var itemTaxPriceInput = $('.itemTaxPrice');
                    for (var j = 0; j < itemTaxPriceInput.length; j++) {
                        totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                        $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount) +
                            parseFloat(itemTaxPriceInput[j].value));
                    }



                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                        totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    }

                    $('.subTotal').html(totalItemPrice.toFixed(2));
                    $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                        totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));

                },
            });
        });


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
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

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
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));


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
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }
    </script>

    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
    <script>
        $('#choices-multiple1').on('change', function() {
            // Get selected tax rates
            var selectedValues = $(this).val();
            var taxData = <?php echo json_encode($fullTax); ?>;
            TotalTax = 0;

            // Your logic to calculate total tax amount based on selected rates


            for (let i = 0; i < selectedValues.length; i++) {
                for (let j = 0; j < taxData.length; j++) {
                    if (taxData[j].id === parseInt(selectedValues[i])) {
                        TotalTax += parseInt(taxData[j].rate);
                        vatType = taxData[j].type || 'add';
                    }
                }
            }

            var totalAmountValue = $('.totalAmount').text();

            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            var TotalAmount = 0;
            if (vatType === 'add') {
                VATAmount = TotalTax;
                TotalAmount = (existingSubTotal - existingDiscount) + (existingSubTotal - existingDiscount) * (
                    parseInt(TotalTax) / 100);
            } else if (vatType === 'subtract') {
                VATAmount = (existingSubTotal * (parseInt(TotalTax) / 100)) / (1 + (parseInt(TotalTax) / 100));
                TotalAmount = (existingSubTotal - existingDiscount) - VATAmount;
            }


            // $('.totalAmount').text(totalAmount.toFixed(2));
            $('.totalAmount').html(TotalAmount.toFixed(2))
            $('.tax_val').text(parseInt(TotalTax));
            $('.totalTax').html(parseInt(VATAmount));

        });
    </script>
@endpush
@section('content')
    <div class="row">
        <form action="{{ url('proposal') }}" method="POST" class="w-100">
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="customer-box">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                                        @foreach ($customers as $id => $customer)
                                            <option value="{{ $id }}"
                                                {{ $customerId == $id ? 'selected' : '' }}>
                                                {{ $customer }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>

                                <div id="customer_detail" class="d-none">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                            <div class="form-icon-user">
                                                <input type="date" id="issue_date" name="issue_date" class="form-control"
                                                    required>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                            <select name="category_id" id="category_id" class="form-control select"
                                                required>
                                                <option value="" disabled selected>{{ __('Select Category') }}
                                                </option>
                                                <!-- Add options dynamically based on $category -->
                                                @foreach ($category as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="proposal_number"
                                                class="form-label">{{ __('Proposal Number') }}</label>
                                            <div class="form-icon-user">
                                                <input type="text" class="form-control" value="{{ $proposal_number }}"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_account_id" class="form-label">{{ __('Bank') }}</label>
                                            <select name="bank_account_id" class="form-control select2"
                                                data-placeholder="{{ __('Select Bank') }}">
                                                <option value=""></option>
                                                @foreach ($accounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-check custom-checkbox mt-4"> --}}
                                    {{-- <input class="form-check-input" type="checkbox" name="discount_apply"
                                            id="discount_apply"> --}}
                                    {{-- <label class="form-check-label " for="discount_apply">{{__('Discount
                                            Apply')}}</label> --}}
                                    {{-- </div> --}}
                                    {{-- </div> --}}


                                    {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-group"> --}}
                                    {{-- {{Form::label('sku',__('SKU')) }} --}}
                                    {{-- {!!Form::text('sku', null,array('class' =>
                                        'form-control','required'=>'required')) !!} --}}
                                    {{-- </div> --}}
                                    {{-- </div> --}}
                                    @if (!$customFields->isEmpty())
                                        <div class="col-md-6">
                                            <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                @include('customFields.formBuilder')
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="descriptionProposal"
                                                class="form-label">{{ __('Description') }}</label>
                                            <textarea name="descriptionProposal" id="descriptionProposal" class="form-control" rows="3"
                                                placeholder="{{ __('Enter proposal description') }}">{{ old('descriptionProposal') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            <select name="currency_id" id="currency_id" class="form-control select2">
                                                @foreach ($currency as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="exchange_rate_div" style="display: none;">
                                        <div class="form-group">
                                            <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" name="exchange_rate" id="exchange_rate"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </div>
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
                        <div class="d-flex align-items-center float-end me-2">
                            <a href="#" data-repeater-create="" class="btn btn-primary mb-2"
                                data-bs-toggle="modal" data-target="#add-bank">
                                <i class="ti ti-plus"></i> {{ __('Add item') }}
                            </a>
                        </div>
                        <div class="card-body mt-3">
                            <div class="table-responsive">
                                <table class="table mb-0" data-repeater-list="items">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Items') }}</th>
                                            <th>{{ __('Quantity') }}</th>
                                            <th>{{ __('Price') }} </th>
                                            <th>{{ __('Discount') }}</th>
                                            <th>{{ __('Tax') }} (%)</th>

                                            <th class="text-end">{{ __('Amount') }} <br>
                                                <small
                                                    class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="ui-sortable" data-repeater-item>
                                        <tr>
                                            <td width="25%" class="form-group">
                                                <select name="item" class="form-control select2 item"
                                                    data-url="{{ route('proposal.product') }}" required>
                                                    @foreach ($product_services as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>

                                            </td>
                                            <td>
                                                <div class="form-group price-input input-group search-form">
                                                    <input type="text" name="quantity" class="form-control quantity"
                                                        required placeholder="{{ __('Qty') }}" required>
                                                    <span class="unit input-group-text bg-transparent"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group price-input input-group search-form">
                                                    <input type="text" name="price" class="form-control price"
                                                        required placeholder="{{ __('Price') }}" required>
                                                    <span class="input-group-text bg-transparent">
                                                        <span
                                                            class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group price-input input-group search-form">
                                                    <input type="text" name="discount" class="form-control discount"
                                                        required placeholder="{{ __('Discount') }}" required>
                                                    <span class="input-group-text bg-transparent">
                                                        <span
                                                            class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="taxes"></div>
                                                        <input type="hidden" name="tax" class="form-control tax">
                                                        <input type="hidden" name="itemTaxPrice"
                                                            class="form-control itemTaxPrice">
                                                        <input type="hidden" name="itemTaxRate"
                                                            class="form-control itemTaxRate">

                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-end amount">
                                                0.00
                                            </td>
                                            <td>
                                                <a href="#" class="ti ti-trash text-white text-danger"
                                                    data-repeater-delete></a>
                                            </td>
                                        </tr>
                                        @if (isset($proposalItemCustomFields) && !$proposalItemCustomFields->isEmpty())
                                            <tr class="proposal-item-custom-fields-row">
                                                <td colspan="7">
                                                    <div class="mt-1">
                                                        @include('proposal.partials.proposalItemCustomFieldsInputs', [
                                                            'fields' => $proposalItemCustomFields,
                                                        'inputNamePrefix' => 'proposal_item_custom_fields_',
                                                        ])
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <textarea name="description" class="form-control pro_description" rows="1"
                                                        placeholder="{{ __('Description') }}"></textarea>

                                                </div>
                                            </td>
                                            <td colspan="5"></td>
                                        </tr>
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
                                                    <select name="tax_id[]" id="choices-multiple1" required
                                                        class="form-control select2 custom-select" multiple>
                                                        @foreach ($fullTax as $value)
                                                            <option value="{{ $value->id }}">{{ $value->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="text-end tax_val">0.00</td>
                                        </tr>
                                        <tr class="border-none">
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td></td>
                                            <td><strong>{{ __('Sub Total') }}
                                                    ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                            <td class="text-end subTotal">0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td></td>
                                            <td><strong>{{ __('Discount') }}
                                                    ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                            <td class="text-end totalDiscount">0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td></td>
                                            <td><strong>{{ __('Tax') }}
                                                    ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                            <td class="text-end totalTax">0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td>&nbsp;</td>
                                            <td class="blue-text border-none"><strong>{{ __('Total Amount') }}
                                                    ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                            <td class="text-end totalAmount blue-text border-none"></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="button" value="{{ __('Cancel') }}"
                        onclick="location.href = '{{ route('proposal.index') }}';" class="btn btn-light">
                    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
                </div>
        </form>
    </div>
    </div>
@endsection
