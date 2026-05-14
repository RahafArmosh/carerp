@extends('layouts.admin')
@section('page-title')
    {{ __('Import Sale Order (Items only)') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('saleorder.index') }}">{{ __('Sale Orders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Items only') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Import Sale Order - Items only file') }}</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('saleorder.import.items-only.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required>
                                    <option value="">{{ __('Select Customer') }}</option>
                                    @foreach($customers as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sales_order_date" class="form-label">{{ __('Sales Order Date') }}</label>
                                <input type="date" name="sales_order_date" id="sales_order_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="currency_id" class="form-label">{{ __('Currency ID') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control select2">
                                    <option value="">{{ __('Select Currency') }}</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate ?? 1 }}">
                                            {{ $currency->id }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.0001" min="0" name="exchange_rate" id="exchange_rate" class="form-control" value="1">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                <select name="tax_id" id="tax_id" class="form-control select2">
                                    <option value="">{{ __('Default Tax') }}</option>
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="file" class="form-label">{{ __('Download sample Sale Order items-only file') }}</label>
                                <a href="{{ route('saleorder.sample.items-only') }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="ti ti-download"></i> {{ __('Download Items-Only Sample') }}
                                </a>
                                <small class="d-block text-muted mt-1">{{ __('Sample columns: PART NO, DESCRIPTION, REQ QTY, UNIT PRICE') }}</small>
                            </div>

                            <div class="col-md-12">
                                <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
                                <div class="choose-file form-group">
                                    <label for="file" class="form-label">
                                        <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.xls,.csv">
                                    </label>
                                    <p class="upload_file"></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
                            <a href="{{ route('saleorder.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $('.select2').select2();

    $('#currency_id').on('change', function() {
        const selectedRate = parseFloat($(this).find(':selected').data('rate'));
        if (!isNaN(selectedRate)) {
            $('#exchange_rate').val(selectedRate);
        }
    });

    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>
@endpush
