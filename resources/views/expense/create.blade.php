@extends('layouts.admin')
@section('page-title')
    {{ __('Expense Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expense.index') }}">{{ __('Expense') }}</a></li>
    <li class="breadcrumb-item">{{ __('Expense Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

    <script>
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
                },
                hide: function(deleteElement) {
                    updateTotals()
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
                                updateTotals();
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

        }

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
                    // console.log(item)

                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.purchase_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);

                    // var taxes = '';
                    // var tax = [];

                    // var totalItemTaxRate = 0;
                    // if (item.taxes == 0) {
                    //     taxes += '-';
                    // } else {
                    //     for (var i = 0; i < item.taxes.length; i++) {
                    //         taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name +
                    //             ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                    //         tax.push(item.taxes[i].id);
                    //         totalItemTaxRate += parseFloat(item.taxes[i].rate);
                    //     }
                    // }
                    // var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (item.product
                    //     .purchase_price * 1));

                    // $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    // $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    // $(el.parent().parent().find('.taxes')).html(taxes);
                    // $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);



                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }


                    var accountinputs = $(".accountamount");
                    var accountSubTotal = 0;
                    for (var i = 0; i < accountinputs.length; i++) {
                        var currentInputValue = parseFloat(accountinputs[i].innerHTML);
                        if (!isNaN(currentInputValue)) {
                            accountSubTotal += currentInputValue;
                        }
                    }



                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value);

                    }

                    // var totalItemTaxPrice = 0;
                    // var itemTaxPriceInput = $('.itemTaxPrice');
                    // for (var j = 0; j < itemTaxPriceInput.length; j++) {
                    //     totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                    $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount));
                    // }

                    // var totalItemDiscountPrice = 0;
                    // var itemDiscountPriceInput = $('.discount');
                    // for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                    //     totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    // }


                    // $('.subTotal').html((totalItemPrice + accountSubTotal).toFixed(2));
                    // // $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    // $('.totalAmount').html(parseFloat(totalItemPrice) - parseFloat(
                    //     totalItemDiscountPrice));


                    // var totalAmount = parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice);
                    // $('.totalAmount').val(totalAmount.toFixed(2));




                },
            });
        });

        var id = '{{ $Id }}';
        if (id > 0) {
            $('#vender').val(id).change();
        }
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>

    {{-- start for user select --}}
    <script>

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
        // removed per-line tax calculation; using header tax

        function updateTotals() {
            let subTotal = 0;
            $('.table tbody tr').each(function() {
                subTotal += parseFloat($(this).find('.accountamount').text()) || 0;
            });
            // header tax calculation
            const selectedHeaderTax = $('#header_tax').val() || [];
            const taxData = <?php echo json_encode($fullTax); ?>;
            let totalTaxRate = 0;
            selectedHeaderTax.forEach(function(tid){
                for(let j=0;j<taxData.length;j++){
                    if (taxData[j].id === parseInt(tid)) totalTaxRate += parseFloat(taxData[j].rate);
                }
            });
            const totalTax = subTotal * (totalTaxRate/100);
            $('.subTotal').text(subTotal.toFixed(2));
            $('.totalTax').text(totalTax.toFixed(2));
            $('.totalAmount').text((subTotal + totalTax).toFixed(2));
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function calculateTotals() {
                let subtotal = 0;
                let totalTax = 0;
                let totalAmount = 0;
                var taxData = <?php echo json_encode($fullTax); ?>;
                document.querySelectorAll('tbody[data-repeater-item]').forEach(function(row) {
                    const amountInput = row.querySelector('.accountAmountInput');
                    const taxSelect = row.querySelector('.tax-dropdown-class');
                    const itemTotalElement = row.querySelector('.accountamount');

                    // Get amount and tax rate
                    const amount = parseFloat(amountInput.value) || 0;
                    var taxRate = 0;
                    for (let j = 0; j < taxData.length; j++) {
                        if (taxData[j].id === parseInt(taxSelect.value)) {
                            taxRate = parseInt(taxData[j].rate);
                        }
                    }
                    // Calculate item total with tax
                    const taxAmount = (amount * taxRate) / 100;

                    const itemTotal = amount + taxAmount;

                    // Display item total in the row
                    itemTotalElement.innerText = itemTotal.toFixed(2);

                    // Add to subtotal and total tax
                    subtotal += amount;
                    totalTax += taxAmount;
                });

                // Calculate final total
                totalAmount = subtotal + totalTax;

                // Update totals in the footer
                document.querySelector('.subTotal').innerText = subtotal.toFixed(2);
                document.querySelector('.totalTax').innerText = totalTax.toFixed(2);
                document.querySelector('.totalAmount').textContent = totalAmount.toFixed(2);
                document.querySelector('input.totalAmount').value = totalAmount.toFixed(2); // Update hidden input
            }

            // Add event listeners for changes on amount, tax, quantity, and discount fields
            document.addEventListener('input', function(event) {
                if (
                    event.target.classList.contains('accountAmountInput') ||
                    event.target.classList.contains('tax-dropdown-class') ||
                    event.target.classList.contains('price') ||
                    event.target.classList.contains('quantity') ||
                    event.target.classList.contains('discount')
                ) {
                    calculateTotals();
                }
            });

            // Initial calculation to set totals
            calculateTotals();
        });
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
        <form action="{{ url('expense') }}" method="POST" class="w-100">
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="col">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="vender-box">
                                        <label for="vender_id" class="form-label">{{ __('Payee') }}</label>
                                        <select name="vender_id" class="form-control select2" id="vender"
                                            data-url="{{ route('expense.vender') }}" required>
                                            @foreach ($venders as $key => $value)
                                                <option value="{{ $key }}" {{ $key == $Id ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="vender_detail" class="d-none">
                                    </div>
                                </div>
                                <input type="hidden" name="type" value="vendor">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_date" class="form-label">{{ __('Payment Date') }}</label>
                                        <input type="date" name="payment_date" class="form-control" required="required">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                        <select name="currency_id" id="currency_id" class="form-control select">
                                            @foreach ($currency as $key => $value)
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
                                        <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>

                                        <div class="form-icon-user">
                                            <span><i class="ti ti-joint"></i></span>
                                            <input type="text" name="exchange_rate" id="exchange_rate"
                                                value="{{ old('exchange_rate') }}" class="form-control">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                        <select name="category_id" required class="form-control select2"
                                            id="category_id">
                                            @foreach ($category as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id" class="form-label">{{ __('Account') }}</label>
                                        <select name="account_id" class="form-control select2" id="account_id"
                                            required="required">
                                            @foreach ($accounts as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="header_tax" class="form-label">{{ __('Tax') }}</label>
                                        <select id="header_tax" name="tax_id[]" class="form-control select2" multiple required>
                                            @foreach ($fullTax as $value)
                                                <option value="{{ $value->id }}">{{ $value->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" value="1" id="no_payment" name="no_payment">
                                        <label class="form-check-label" for="no_payment">
                                            {{ __('Create without payment') }}
                                        </label>
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
                                        <th>{{ __('') }}</th>
                                        <th>{{ __('') }} </th>
                                        <th>{{ __('') }}</th>
                                        <th class="text-end">{{ __('Amount') }}
                                            <br><small
                                                class="text-danger font-bold">{{ __('after tax & discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>
                                        <td width="25%" class="form-group">
                                            <select name="item" class="form-group form-control select2 item"
                                                data-url="{{ route('expense.product') }}">
                                                @foreach ($product_services as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td colspan="4"></td>
                                        <td class="text-end amount">
                                            0.00
                                        </td>
                                        <td>
                                            @can('delete proposal product')
                                                <a href="#"
                                                    class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 bs-pass-para"
                                                    data-repeater-delete></a>
                                            @endcan
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="form-group">
                                            <select name="chart_account_id" class="form-control select2">
                                                @foreach ($chartAccounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>

                                        </td>
                                        <td class="form-group">
                                            <div class="input-group ">
                                                <input type="text" name="amount"
                                                    class="form-control accountAmountInput"
                                                    placeholder="{{ __('Amount') }}">
                                                <span class="input-group-text bg-transparent"><span
                                                        class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span></span>
                                            </div>
                                        </td>
                                        <td colspan="2" class="form-group">
                                            <textarea name="description" class="form-control pro_description" rows="1"
                                                placeholder="{{ __('Description') }}"></textarea>
                                        </td>
                                        
                                        <td class="text-end accountamount">
                                            0.00
                                        </td>
                                        <td></td>
                                    </tr>

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Sub Total') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Tax') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalTax">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>

                                        <td class="blue-text text-end totalAmount">0.00</td>
                                        <input type="hidden" name="totalAmount" class="form-control totalAmount">

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
                    onclick="location.href = '{{ route('expense.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
            </div>
        </form>
    </div>
@endsection
