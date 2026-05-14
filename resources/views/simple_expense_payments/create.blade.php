@extends('layouts.admin')
@section('page-title')
    {{ __('Create Service Bill Payment') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense-payments.index') }}">{{ __('Service Bill Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Service Bill Payment') }}</li>
@endsection
@push('script-page')
<script>
    $(document).ready(function() {
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
                        $('#exchange_rate').val(data.exchange_rate);
                    })
                    .catch(() => {
                        $('#exchange_rate_div').show();
                    });
            }
        });
    });
</script>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('simple-expense-payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Service Bill') }} <span class="text-danger">*</span></label>
                                <select name="expense_id" id="expense_id" class="form-control select2" required>
                                    @foreach ($expenses as $id => $name)
                                        <option value="{{ $id }}" @if(isset($selectedExpenseId) && $selectedExpenseId == $id) selected @endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control select2">
                                    @foreach ($currencies as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="exchange_rate_div" style="display: none;">
                                <label>{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.000001" min="0" name="currency_rate" id="exchange_rate" class="form-control" placeholder="{{ __('Auto-filled if currency selected') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control" required>
                                <small class="text-muted">{{ __('If currency is selected, this amount is in that currency and will be converted to AED for storage.') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Account') }} <span class="text-danger">*</span></label>
                                <select name="account_id" class="form-control select2" required>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Reference') }}</label>
                                <input type="text" name="reference" class="form-control" placeholder="{{ __('Payment reference number') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Payment description') }}"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Receipt') }}</label>
                                <input type="file" name="add_receipt" class="form-control" accept="image/*,.pdf">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('simple-expense-payments.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

