@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Direct Expenses') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Search') }}</li>
@endsection
@section('content')
<div class="row">
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    
    <!-- First Section: Expense Header Form (Same as Expense Create) -->
    <form id="expense-store" action="{{ route('direct_expenses.store') }}" method="POST" class="w-100" enctype="multipart/form-data">
        @csrf
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="vender-box">
                                <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                <select name="vendor_id" class="form-control select2" id="vender_id" required>
                                    <option value="">{{ __('Select Vendor') }}</option>
                                    @isset($vendors)
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ (string)request('vendor_id') === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expense_date" class="form-label">{{ __('Expense Date') }}<span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ request('expense_date', now()->toDateString()) }}" required="required">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_date" class="form-label">{{ __('Payment Date') }}<span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ request('payment_date', now()->toDateString()) }}" required="required">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control select">
                                    <option value="">{{ __('Default') }}</option>
                                    @isset($currencies)
                                    @foreach($currencies as $cur)
                                        <option value="{{ $cur->id }}" {{ (string)request('currency_id') === (string)$cur->id ? 'selected' : '' }}>{{ $cur->code }} - {{ $cur->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6" id="exchange_rate_div" style="display: none;">
                            <div class="form-group">
                                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                <div class="form-icon-user">
                                    <span><i class="ti ti-joint"></i></span>
                                    <input type="text" name="exchange_rate" id="exchange_rate" value="{{ request('exchange_rate') }}" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                <select name="category_id" class="form-control select2" id="category_id">
                                    <option value="">{{ __('None') }}</option>
                                    @isset($categories)
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string)request('category_id') === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                                <select name="chart_account_id" class="form-control select2" id="chart_account_id">
                                    <option value="">{{ __('None') }}</option>
                                    @isset($accounts)
                                    @foreach($accounts as $a)
                                        <option value="{{ $a->id }}" {{ (string)request('chart_account_id') === (string)$a->id ? 'selected' : '' }}>{{ $a->code }} - {{ $a->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="header_tax" class="form-label">{{ __('Tax') }}</label>
                                <select id="header_tax" name="tax_id[]" class="form-control select2" multiple required>
                                    @php $selectedTaxes = (array)request('tax_id', []); @endphp
                                    @isset($taxes)
                                    @foreach($taxes as $t)
                                        <option value="{{ $t->id }}" {{ in_array((string)$t->id, array_map('strval', $selectedTaxes), true) ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="bank_account_field">
                            <div class="form-group">
                                <label for="payment_account_id" class="form-label">{{ __('Payment Account') }}</label>
                                <select name="payment_account_id" id="payment_account_id" class="form-control select2">
                                    <option value="">{{ __('Select Account') }}</option>
                                    @php
                                        $bankAccounts = \App\Models\BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                                            ->where('created_by', Auth::user()->creatorId())
                                            ->get()
                                            ->pluck('name', 'id');
                                    @endphp
                                    @foreach($bankAccounts as $id => $name)
                                        <option value="{{ $id }}" {{ (string)request('payment_account_id') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Required if not creating without payment') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="no_payment" name="no_payment" {{ request('no_payment') ? 'checked' : '' }}>
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
    </form>
</div>

<!-- Second Section: Search Form and Results -->
<div class="row mt-4">
    <div class="col-12">
        <form id="direct-expense-search" action="{{ route('direct_expenses.doSearch') }}" method="POST" class="mb-3">
            @csrf
            <input type="hidden" name="vendor_id" value="{{ request('vendor_id') }}">
            <input type="hidden" name="expense_date" value="{{ request('expense_date') }}">
            <input type="hidden" name="payment_date" value="{{ request('payment_date') }}">
            <input type="hidden" name="currency_id" value="{{ request('currency_id') }}">
            <input type="hidden" name="exchange_rate" value="{{ request('exchange_rate') }}">
            <input type="hidden" name="category_id" value="{{ request('category_id') }}">
            <input type="hidden" name="chart_account_id" value="{{ request('chart_account_id') }}">
            <input type="hidden" name="payment_account_id" value="{{ request('payment_account_id') }}">
            <input type="hidden" name="no_payment" value="{{ request('no_payment') ? 1 : '' }}">
            <div id="header_tax_hidden"></div>
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Search Cars') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label>{{ __('Vendor') }}</label>
                            <select name="vender_id" class="form-control select2">
                                <option value="">{{ __('All') }}</option>
                                @isset($vendors)
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ (string)request('vender_id') === (string)$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>{{ __('Invoice') }}</label>
                            <select name="invoice_id" class="form-control select2">
                                <option value="">{{ __('All') }}</option>
                                @isset($invoices)
                                @foreach($invoices as $inv)
                                    <option value="{{ $inv->id }}" {{ (string)request('invoice_id') === (string)$inv->id ? 'selected' : '' }}>{{ Auth::user()->invoiceNumberFormat($inv->invoice_id) }}</option>
                                @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>{{ __('Bill') }}</label>
                            <select name="bill_id" class="form-control select2">
                                <option value="">{{ __('All') }}</option>
                                @isset($bills)
                                @foreach($bills as $bill)
                                    <option value="{{ $bill->id }}" {{ (string)request('bill_id') === (string)$bill->id ? 'selected' : '' }}>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                                @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>{{ __('VINs (multi)') }}</label>
                            <input type="text" name="vins" class="form-control" placeholder="{{ __('Enter VINs, separated by space') }}" value="{{ request('vins') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" class="form-control select2">
                                <option value="">{{ __('All') }}</option>
                                @isset($warehouses)
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" {{ (string)request('warehouse_id') === (string)$w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>{{ __('Customer') }}</label>
                            <select name="customer_id" class="form-control select2">
                                <option value="">{{ __('All') }}</option>
                                @isset($customers)
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ (string)request('customer_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 align-self-end">
                            <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                            <button type="reset" class="btn btn-secondary" onclick="resetSearchForm()">{{ __('Reset') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Section -->
@isset($cars)
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">{{ __('Cars') }}</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>{{ __('VIN') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($cars as $car)
                            <tr>
                                <td><input type="checkbox" name="sub_product_ids[]" value="{{ $car->id }}" form="expense-store"></td>
                                <td>{{ $car->product_no }}</td>
                                <td>{{ $car->product_name }}</td>
                                <td>{{ $car->quantity ?? 0 }}</td>
                                <td>{{ $car->warehouse_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">{{ __('No results') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">{{ __('Expense Details') }}</div>
            <div class="card-body">
                <div class="mb-3">
                    <label>{{ __('Amount') }}</label>
                    <input type="number" name="amount" step="0.01" min="0" class="form-control" form="expense-store" required>
                </div>
                <div class="mb-3">
                    <label>{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Optional') }}" form="expense-store"></textarea>
                </div>
                <div class="d-grid">
                    <button class="btn btn-success" type="submit" form="expense-store">{{ __('Save Direct Expense') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endisset

@push('script-page')
<script>
    $(document).ready(function() {
        const exchangeRateDiv = document.getElementById('exchange_rate_div');

        $('#currency_id').change(function() {
            var currencyId = $(this).val();

            if (currencyId === '') {
                $('#exchange_rate_div').hide();
                $('#exchange_rate').val('');
            } else {
                fetch('/get-exchange-rate/' + currencyId)
                    .then(response => response.json())
                    .then(data => {
                        $('#exchange_rate_div').show();
                        $('#exchange_rate').val(data.exchange_rate || '');
                    })
                    .catch(() => {
                        $('#exchange_rate_div').show();
                    });
            }
        });

        // Toggle payment account field based on no_payment checkbox
        $('#no_payment').change(function() {
            if ($(this).is(':checked')) {
                $('#bank_account_field').hide();
                $('#payment_account_id').prop('required', false);
            } else {
                $('#bank_account_field').show();
                $('#payment_account_id').prop('required', true);
            }
        }).trigger('change');

        // Copy header fields into hidden inputs on search submit so values persist
        $('#direct-expense-search').on('submit', function() {
            $('input[name="vendor_id"]').val($('#vender_id').val());
            $('input[name="expense_date"]').val($('#expense_date').val());
            $('input[name="payment_date"]').val($('#payment_date').val());
            $('input[name="currency_id"]').val($('#currency_id').val());
            $('input[name="exchange_rate"]').val($('#exchange_rate').val());
            $('input[name="category_id"]').val($('#category_id').val());
            $('input[name="chart_account_id"]').val($('#chart_account_id').val());
            $('input[name="payment_account_id"]').val($('#payment_account_id').val());
            $('input[name="no_payment"]').val($('#no_payment').is(':checked') ? 1 : '');
            // tax array
            const taxHidden = $('#header_tax_hidden').empty();
            ($('#header_tax').val() || []).forEach(function(tid){
                taxHidden.append($('<input>').attr({type:'hidden', name:'tax_id[]', value: tid}));
            });
        });
    });

    document.getElementById('select-all')?.addEventListener('change', function(e){
        document.querySelectorAll('input[name="sub_product_ids[]"]').forEach(cb => cb.checked = e.target.checked);
    });

    // Reset search form function
    function resetSearchForm() {
        // Reset the search form
        document.getElementById('direct-expense-search').reset();
        
        // Reset the header form fields
        $('#vender_id').val('').trigger('change');
        $('#expense_date').val('');
        $('#payment_date').val('');
        $('#currency_id').val('').trigger('change');
        $('#exchange_rate_div').hide();
        $('#exchange_rate').val('');
        $('#category_id').val('').trigger('change');
        $('#chart_account_id').val('').trigger('change');
        $('#payment_account_id').val('').trigger('change');
        $('#no_payment').prop('checked', false).trigger('change');
        $('#header_tax').val(null).trigger('change');
        
        // Redirect to search page without query parameters to clear results
        window.location.href = '{{ route("direct_expenses.search") }}';
    }
</script>
@endpush
@endsection
