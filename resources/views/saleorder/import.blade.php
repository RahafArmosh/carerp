@extends('layouts.admin')
@section('page-title')
    {{ __('Import Sale Order') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('saleorder.index') }}">{{ __('Sale Orders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Sale Order') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Import Sale Order from Excel') }}</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('saleorder.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="file" class="form-label">{{ __('Download sample sale order Excel file') }}</label>
                                <a href="{{ route('saleorder.sample') }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="ti ti-download"></i> {{__('Download Sample')}}
                                </a>
                                <small class="d-block text-muted mt-1">{{ __('Download the sample file to see the required format') }}</small>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h6>{{ __('Sale Order Import Guide') }}</h6>
                                    <p class="mb-2">{{ __('This import will create a new sale order with items.') }}</p>
                                    <hr>
                                    <p class="mb-1"><strong>{{ __('Required Information in Excel File:') }}</strong></p>
                                    <ul class="mb-0">
                                        <li><strong>Customer Information:</strong> Customer Name only (must match an existing customer). Customer Code and TRN are taken from the customer master.</li>
                                        <li><strong>Sales Order Date:</strong> Date of the sale order (defaults to today if not provided)</li>
                                        <li><strong>Sales Order Number:</strong> Optional (will be auto-generated if not provided)</li>
                                        <li><strong>TAX:</strong> Rate only (e.g. 5 or 5% to use 5% VAT). Optional.</li>
                                    </ul>
                                    <p class="mb-1 mt-2"><strong>{{ __('Required Columns in Table:') }}</strong></p>
                                    <ul class="mb-0">
                                        <li><strong>PART NO</strong> - Part number (required)</li>
                                        <li><strong>REQ QTY</strong> - Required quantity (required)</li>
                                        <li><strong>UNIT PRICE</strong> - Unit price (optional)</li>
                                        <li><strong>TOTAL</strong> - Total amount (optional)</li>
                                    </ul>
                                    <p class="mb-0 mt-2"><small class="text-muted"><i class="ti ti-info-circle"></i> {{ __('Note: STOCK QTY, PACKED QTY, and DISCREPANCY columns are not required. If PACKED QTY is not provided, it will default to REQ QTY.') }}</small></p>
                                    <p class="mb-0 mt-2"><small class="text-info"><i class="ti ti-info-circle"></i> {{ __('The system will try to match PART NO with existing sub-products. If not found, the item will still be created but without product linkage.') }}</small></p>
                                    <hr>
                                    <div class="alert alert-warning mb-0 mt-2">
                                        <strong><i class="ti ti-alert-triangle"></i> {{ __('Important:') }}</strong>
                                        <ul class="mb-0 mt-1">
                                            <li>{{ __('Do NOT use VLOOKUP, HLOOKUP, or any Excel formulas in your import file.') }}</li>
                                            <li>{{ __('All values must be actual data, not formulas. Copy and paste as values before importing.') }}</li>
                                            <li>{{ __('Formulas will cause import errors and data inconsistencies.') }}</li>
                                            <li>{{ __('Customer must exist in the system before importing.') }}</li>
                                        </ul>
                                    </div>
                                </div>
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
    // Display selected file name
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>
@endpush
