@extends('layouts.admin')
@section('page-title')
    {{ __('Expense Edit') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expense.index') }}">{{ __('Expense') }}</a></li>
    <li class="breadcrumb-item">{{ __('Expense Edit') }}</li>
@endsection

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false, // Don't initialize with empty items
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
                    // Initialize changeItem for new items
                    $(this).find('.item').on('change', function() {
                        changeItem($(this));
                    });
                    // Ensure new items start with 0.00
                    $(this).find('.amount').html('0.00');
                    $(this).find('.accountamount').html('0.00');
                    $(this).find('.accountAmountInput').val('');
                    $(this).find('.itemTaxPrice').val('0.00');
                    calculateTotals();
                },
                hide: function(deleteElement) {
                    $(this).slideUp(deleteElement);
                    $(this).remove();
                    calculateTotals();
                },
                ready: function(setIndexes) {
                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: false
            });
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
            changeItem($(this));
        });

        var bill_id = '{{ $expense->id }}';

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
                    'product_id': iteams_id,
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);

                    $.ajax({
                        url: '{{ route('expense.items') }}',
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': jQuery('#token').val()
                        },
                        data: {
                            'bill_id': bill_id,
                            'product_id': iteams_id,
                        },
                        cache: false,
                        success: function(data) {
                            var billItems = JSON.parse(data);

                            if (billItems != null) {
                                var amount = (billItems.price * billItems.quantity);
                                $(el.parent().parent().parent().find('.quantity')).val(billItems
                                    .quantity);
                                $(el.parent().parent().parent().find('.price')).val(billItems
                                    .price);
                                $(el.parent().parent().parent().find('.discount')).val(billItems
                                    .discount);
                                $(el.parent().parent().parent().parent().find('.pro_description'))
                                    .val(billItems.description);
                            } else {
                                $(el.parent().parent().parent().find('.quantity')).val(1);
                                $(el.parent().parent().parent().find('.price')).val(item.product
                                    .purchase_price);
                                $(el.parent().parent().parent().find('.discount')).val(0);
                                $(el.parent().parent().parent().parent().find('.pro_description'))
                                    .val(item.product.description);
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

                            var discount = $(el.parent().parent().parent().find('.discount')).val();

                            if (billItems != null) {
                                var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) *
                                    parseFloat((billItems.price * billItems.quantity) - discount);
                            } else {
                                var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) *
                                    parseFloat((item.product.purchase_price * 1) - discount);
                            }

                            $(el.parent().parent().parent().find('.itemTaxPrice')).val(itemTaxPrice
                                .toFixed(2));
                            $(el.parent().parent().parent().find('.itemTaxRate')).val(
                                totalItemTaxRate.toFixed(2));
                            $(el.parent().parent().parent().find('.taxes')).html(taxes);
                            $(el.parent().parent().parent().find('.tax')).val(tax);
                            $(el.parent().parent().parent().find('.unit')).html(item.unit);

                            calculateTotals();
                        }
                    });
                },
            });
        }

        function calculateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            let totalAmount = 0;
            var taxData = @json($fullTax);

            // Handle all rows (both existing and new items)
            document.querySelectorAll('tbody tr').forEach(function(row) {
                const amountInput = row.querySelector('.accountAmountInput');
                const taxSelect = row.querySelector('.tax-dropdown-class');
                const itemTotalElement = row.querySelector('.accountamount');

                if (amountInput && taxSelect && itemTotalElement) {
                    // Get amount and tax rate
                    const amount = parseFloat(amountInput.value) || 0;
                    var taxRate = 0;

                    if (taxSelect.value) {
                        for (let j = 0; j < taxData.length; j++) {
                            if (taxData[j].id === parseInt(taxSelect.value)) {
                                taxRate = parseInt(taxData[j].rate);
                                break;
                            }
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
                }
            });

            // Calculate final total
            totalAmount = subtotal + totalTax;

            // Update totals in the footer
            document.querySelector('.subTotal').innerText = subtotal.toFixed(2);
            document.querySelector('.totalTax').innerText = totalTax.toFixed(2);
            document.querySelector('.totalAmount').textContent = totalAmount.toFixed(2);
            document.querySelector('input.totalAmount').value = totalAmount.toFixed(2); // Update hidden input
        }

        // Handle tax dropdown changes for all items (existing and new)
        $(document).on('change', '.tax-dropdown-class', function() {
            var $row = $(this).closest('tr');
            var selectedValues = $(this).val();
            var taxData = @json($fullTax);
            var TotalTax = 0;
            var amount = parseFloat($row.find('.accountAmountInput').val()) || 0;

            if (selectedValues && selectedValues.length > 0) {
                for (let i = 0; i < selectedValues.length; i++) {
                    for (let j = 0; j < taxData.length; j++) {
                        if (taxData[j].id === parseInt(selectedValues[i])) {
                            TotalTax += parseInt(taxData[j].rate);
                        }
                    }
                }
            }

            var totalItemTaxPrice = (TotalTax / 100) * amount;
            $row.find('.itemTaxPrice').val(totalItemTaxPrice.toFixed(2));
            $row.find('.accountamount').html((amount + totalItemTaxPrice).toFixed(2));

            // Update the amount field in the first row (product row) if it exists
            var $firstRow = $row.prev('tr');
            if ($firstRow.length > 0) {
                $firstRow.find('.amount').html((amount + totalItemTaxPrice).toFixed(2));
            }

            // Update totals
            calculateTotals();
        });

        // Handle amount input changes for all items (existing and new)
        $(document).on('input', '.accountAmountInput', function() {
            var $row = $(this).closest('tr');
            var amount = parseFloat($(this).val()) || 0;
            var taxSelect = $row.find('.tax-dropdown-class');
            var selectedValues = taxSelect.val();
            var taxData = @json($fullTax);
            var TotalTax = 0;

            if (selectedValues && selectedValues.length > 0) {
                for (let i = 0; i < selectedValues.length; i++) {
                    for (let j = 0; j < taxData.length; j++) {
                        if (taxData[j].id === parseInt(selectedValues[i])) {
                            TotalTax += parseInt(taxData[j].rate);
                        }
                    }
                }
            }

            var totalItemTaxPrice = (TotalTax / 100) * amount;
            $row.find('.itemTaxPrice').val(totalItemTaxPrice.toFixed(2));
            $row.find('.accountamount').html((amount + totalItemTaxPrice).toFixed(2));

            // Update the amount field in the first row (product row) if it exists
            var $firstRow = $row.prev('tr');
            if ($firstRow.length > 0) {
                $firstRow.find('.amount').html((amount + totalItemTaxPrice).toFixed(2));
            }

            // Update totals
            calculateTotals();
        });

        // Remove the old event handlers and replace with the new unified approach
        $(document).on('keyup change', '.quantity, .price, .discount, .accountAmount', function() {
            calculateTotals();
        });

        // Initialize calculations on page load
        $(document).ready(function() {
            // Small delay to ensure all elements are loaded
            setTimeout(function() {
                calculateTotals();
            }, 100);
        });

        $(document).on('click', '[data-repeater-delete]', function() {
            // $('.delete_item').click(function () {
            if (confirm('Are you sure you want to delete this element?')) {
                var el = $(this).parent().parent();
                var id = $(el.find('.id')).val();
                var amount = $(el.find('.amount')).html();
                var account_id = $(el.find('.account_id')).val();

                $.ajax({
                    url: '{{ route('expense.product.destroy') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': jQuery('#token').val()
                    },
                    data: {
                        'id': id,
                        'amount': amount,
                        'account_id': account_id,

                    },
                    cache: false,
                    success: function(data) {
                        show_toastr('success', 'Product Successfully Deleted', 'success');
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        show_toastr('error', data.error, 'error')
                    }

                });

            }
        });

        $('.accountAmount').trigger('keyup');
</script>

<script>
    $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
</script>


{{-- start for user select --}}
<script>
    // $(document).ready(function() {
        //     $('input[name=type]:first').prop('checked',true);
        // });




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
        })
</script>

{{-- end for user select --}}
@endpush
@section('content')
<div class="row">

    <form method="POST" action="{{ route('expense.update', $expense->id) }}" class="w-100">
        @csrf
        @method('PUT')
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="vender-box">
                                <label for="vender_id" class="form-label">{{ __('Payee') }}</label>
                                <select name="vender_id" class="form-control select" id="vender"
                                    data-url="{{ route('expense.vender') }}" required>
                                    @foreach ($venders as $key => $value)
                                    <option value="{{ $key }}" @if ($expense->vender_id == $key) selected @endif>{{
                                        $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="vender_detail" class="d-none">
                            </div>
                            <input type="hidden" name="type" value="vendor">
                        </div>

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date" class="form-label">{{ __('Payment Date') }}</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control" required
                                            value="{{ $expense->bill_date }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                        <select name="currency_id" id="currency_id" class="form-control select">
                                            @foreach ($currency as $key => $value)
                                                <option value="{{ $key }}"
                                                    @if ($expense->currency_id == $key) selected @endif>{{ $value }}
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
                                                value="{{ $expense->exchange_rate }}" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                        <select name="category_id" id="category_id" class="form-control select">
                                            @foreach ($category as $key => $value)
                                                <option value="{{ $key }}"
                                                    @if ($expense->category_id == $key) selected @endif>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id" class="form-label">{{ __('Account') }}</label>
                                        <select name="account_id" id="account_id" class="form-control select" required>
                                            @foreach ($bank_Account as $key => $account)
                                                <option value="{{ $key }}"
                                                    @if (isset($bankAccount) && $bankAccount->id == $key) selected @endif>
                                                    {{ __($account) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_number" class="form-label">{{ __('Expense Number') }}</label>
                                        <input type="text" class="form-control" value="{{ $expense->bill_id }}"
                                            readonly>
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
            <div class="card repeater" data-value='{!! json_encode($items) !!}'>
                <div class="item-section py-2">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                            <div class="all-button-box me-2">
                                <a href="#" data-repeater-create="" class="btn btn-primary" data-bs-toggle="modal"
                                    data-target="#add-bank">
                                    <i class="ti ti-plus"></i> {{ __('Add item') }}
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
                                    <th>{{ __('Discount') }}</th>
                                    <th>{{ __('Tax') }} (%)</th>
                                    <th class="text-end">{{ __('Amount') }}
                                        <br><small class="text-danger font-bold">{{ __('after tax & discount')
                                            }}</small>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="ui-sortable" data-repeater-item>
                                <tr>
                                    <input type="hidden" name="id" class="form-control id">
                                    <input type="hidden" name="account_id" class="form-control account_id">
                                    <td width="25%" class="form-group">
                                        <select name="items" class="form-control select item"
                                            data-url="{{ route('expense.product') }}">
                                            @foreach ($product_services as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="quantity" class="form-control quantity"
                                                placeholder="{{ __('Qty') }}">
                                            <span class="unit input-group-text bg-transparent"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="price" class="form-control price"
                                                placeholder="{{ __('Price') }}">
                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="discount" class="form-control discount"
                                                placeholder="{{ __('Discount') }}">
                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
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
                                        @can('delete bill product')
                                        <a href="#"
                                            class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 bs-pass-para"
                                            data-repeater-delete></a>
                                        @endcan
                                    </td>
                                </tr>
                                <tr>
                                    <td class="form-group">
                                        <select name="chart_account_id" class="form-control select js-searchBox">
                                            @foreach ($chartAccounts as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group">
                                        <div class="input-group ">
                                            <input type="text" name="amount" class="form-control accountAmount"
                                                placeholder="{{ __('Amount') }}" value="{{ old('amount') }}">
                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>

                                    <td colspan="2" class="form-group">
                                        <textarea name="description" class="form-control pro_description" rows="1"
                                            placeholder="{{ __('Description') }}">{{ old('description') }}</textarea>
                                    </td>
                                    <td></td>
                                    <td class="text-end accountamount">
                                        0.00
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end subTotal">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Discount') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end totalDiscount">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Tax') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end totalTax">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td class="blue-text"><strong>{{ __('Total Amount') }}
                                            ({{ \Auth::user()->currencySymbol() }})</strong></td>

                                    <td class="blue-text text-end totalAmount">0.00</td>
                                    <input type="hidden" name="totalAmount" class="form-control totalAmount"
                                        value="{{ old('totalAmount') }}">

                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" onclick="location.href = '{{ route('expense.index') }}';"
                class="btn btn-light me-3">
            <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
        </div>
    </form>
</div>
@endsection