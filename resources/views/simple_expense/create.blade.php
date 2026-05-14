@extends('layouts.admin')
@section('page-title')
    {{ __('Create Service Bill') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Service Bill') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>

    <script>
        var selector = "body";
        let TotalTax = 0;
        if ($(selector + " .repeater").length) {
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
            
            // Header tax calculation
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
    </script>
    <script>
        $(document).ready(function() {
            const exchangeRateDiv = document.getElementById('exchange_rate_div');
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
        <form action="{{ route('simple-expense.store') }}" method="POST" class="w-100" enctype="multipart/form-data">
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="vender-box">
                                    <label for="vender_id" class="form-label">{{ __('Payee') }}</label>
                                    <select name="vender_id" class="form-control select2" id="vender" required>
                                        @foreach ($venders as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expense_date" class="form-label">{{ __('Expense Date') }}</label>
                                    <input type="date" name="expense_date" class="form-control" required>
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
                                    <select name="category_id" required class="form-control select2" id="category_id">
                                        @foreach ($category as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="header_tax" class="form-label">{{ __('Tax') }}</label>
                                    <select id="header_tax" name="tax_id[]" class="form-control select2" multiple>
                                        @foreach ($fullTax as $value)
                                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_id" class="form-label">{{ __('Account') }}</label>
                                    <select name="account_id" class="form-control select2" id="account_id">
                                        @foreach ($accounts as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                                    <input type="file" name="attachment" id="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <small class="text-muted">{{ __('Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Accounts') }}</h5>
                <div class="card repeater">
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

            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('simple-expense.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
@endsection

