@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Service Bill Payment') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense-payments.index') }}">{{ __('Service Bill Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Service Bill Payment') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

    <script>
        var selector = "body";
        let TotalTax = 0;
        if ($(selector + " .repeater").length) {
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
            } else {
                value = [];
            }
            
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    if ($('.select2').length) {
                        $(this).find('.select2').select2();
                    }
                    updateTotals();
                },
                hide: function(deleteElement) {
                    updateTotals();
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
                    if (value.length > 0) {
                        $repeater.setList(value);
                    }
                },
                isFirstItemUndeletable: true
            });
        }

        function updateTotals() {
            let subTotal = 0;
            $('.account-amount-input').each(function() {
                const amount = parseFloat($(this).val()) || 0;
                subTotal += amount;
            });
            
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

        $(document).on('input', '.account-amount-input', function() {
            updateTotals();
        });

        $(document).on('change', '#header_tax', function() {
            updateTotals();
        });

        // Initial calculation on page load
        $(document).ready(function() {
            updateTotals();
        });
    </script>
    <script>
        $(document).ready(function() {
            $('.currency-symbol').text('{{ \Auth::user()->currencySymbol() }}');

            $('#currency_id').change(function() {
                var currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}';

                if (currencyId === '') {
                    $('.currency-symbol').text(symbol);
                    $('#exchange_rate_div').hide();
                    $('#exchange_rate').val('');
                } else {
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
            
            @if($payment->currency_id)
            $('#currency_id').trigger('change');
            @endif
        });
    </script>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('simple-expense-payments.update', \Crypt::encrypt($payment->id)) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Service Bill') }} <span class="text-danger">*</span></label>
                                <select name="expense_id" id="expense_id" class="form-control select2" required>
                                    @foreach ($expenses as $id => $name)
                                        <option value="{{ $id }}" @if($payment->expense_id == $id) selected @endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ $payment->date }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control select2">
                                    @foreach ($currencies as $id => $name)
                                        <option value="{{ $id }}" @if($payment->currency_id == $id) selected @endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="exchange_rate_div" style="display: {{ $payment->currency_id ? 'block' : 'none' }};">
                                <label>{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.000001" min="0" name="currency_rate" id="exchange_rate" class="form-control" value="{{ $payment->currency_rate }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control" value="{{ $payment->amount_in_currency ?? $payment->amount }}" required>
                                <small class="text-muted">{{ __('If currency is selected, this amount is in that currency and will be converted to AED for storage.') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Account') }} <span class="text-danger">*</span></label>
                                <select name="account_id" class="form-control select2" required>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @if($payment->account_id == $id) selected @endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Reference') }}</label>
                                <input type="text" name="reference" class="form-control" value="{{ $payment->reference }}" placeholder="{{ __('Payment reference number') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Payment description') }}">{{ $payment->description }}</textarea>
                            </div>
                            @if($payment->add_receipt)
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Current Receipt') }}</label>
                                <div>
                                    <a href="{{ asset('uploads/simple_expense_payment/' . $payment->add_receipt) }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="ti ti-download"></i> {{ __('View Current Receipt') }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Receipt') }}</label>
                                <input type="file" name="add_receipt" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">{{ __('Leave empty to keep current receipt') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($expense)
            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Service Bill Accounts') }}</h5>
                <div class="card repeater" data-value='{!! json_encode($expenseAccounts) !!}'>
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary">
                                        <i class="ti ti-plus"></i> {{ __('Add Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table mb-0" data-repeater-list="accounts" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th width="30%">{{ __('Account') }}</th>
                                        <th width="25%">{{ __('Amount') }}</th>
                                        <th width="35%">{{ __('Description') }}</th>
                                        <th width="10%"></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>
                                        <td class="form-group">
                                            <select name="chart_account_id" class="form-control select2" required>
                                                @foreach ($chartAccounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="form-group">
                                            <div class="input-group">
                                                <input type="number" name="amount" step="0.01" min="0.01"
                                                    class="form-control account-amount-input" placeholder="{{ __('Amount') }}" required>
                                                <span class="input-group-text bg-transparent">
                                                    <span class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="form-group">
                                            <textarea name="description" class="form-control" rows="1"
                                                placeholder="{{ __('Description') }}"></textarea>
                                        </td>
                                        <td>
                                            @can('delete proposal product')
                                                <a href="#"
                                                    class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 bs-pass-para"
                                                    data-repeater-delete></a>
                                            @endcan
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Sub Total') }} (<span class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong></td>
                                        <td class="text-end subTotal">0.00</td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Tax') }} (<span class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong></td>
                                        <td class="text-end totalTax">0.00</td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }} (<span class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong></td>
                                        <td class="blue-text text-end totalAmount">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                            <a href="{{ route('simple-expense-payments.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

