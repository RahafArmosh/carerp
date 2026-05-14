@extends('layouts.admin')
@section('page-title')
    {{ __('Create PRO') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pro.index') }}">{{ __('PRO') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('pro.store') }}" method="POST" id="pro-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pro_no" class="form-label">{{ __('PRO No') }}</label>
                                    <input type="text" class="form-control" value="{{ $pro_number_formatted ?? \Auth::user()->proNumberFormat($pro_number) }}" readonly>
                                    <small class="text-muted">{{ __('Auto-generated') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id" class="form-label">{{ __('Supplier Name') }}</label>
                                    <select name="supplier_id" id="supplier_id" class="form-control select2" onchange="loadSupplierData()">
                                        <option value="">{{ __('Select Supplier') }}</option>
                                        @foreach($suppliers as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_code" class="form-label">{{ __('Supplier Code') }}</label>
                                    <input type="text" name="supplier_code" id="supplier_code" class="form-control" placeholder="{{ __('Auto-filled from supplier') }}">
                                </div>
                            </div> --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="po_date" class="form-label">{{ __('PO Date') }}</label>
                                    <input type="date" name="po_date" id="po_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_proforma_no" class="form-label">{{ __('Supplier Proforma No') }}</label>
                                    <input type="text" name="supplier_proforma_no" id="supplier_proforma_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_proforma_date" class="form-label">{{ __('Supplier Proforma Date') }}</label>
                                    <input type="date" name="supplier_proforma_date" id="supplier_proforma_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="our_order_ref" class="form-label">{{ __('Our Order Ref') }}</label>
                                    <input type="text" name="our_order_ref" id="our_order_ref" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_ref" class="form-label">{{ __('Supplier Ref') }}</label>
                                    <input type="text" name="supplier_ref" id="supplier_ref" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eta_date" class="form-label">{{ __('ETA Date') }}</label>
                                    <input type="date" name="eta_date" id="eta_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="open" selected>{{ __('Open') }}</option>
                                        <option value="delivered">{{ __('Delivered') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control select2" onchange="updateExchangeRate()">
                                        <option value="">{{ __('Select Currency') }}</option>
                                        @foreach($currencies as $id => $name)
                                            <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                    <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.000001" min="0" value="1.0" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>{{ __('Items') }}</h5>
                        <div id="items-container">
                            <div class="item-row mb-3 border p-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Part No') }}</label>
                                        <input type="text" name="items[0][part_no]" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Product') }}</label>
                                        <select name="items[0][product_id]" class="form-control select2">
                                            <option value="">{{ __('Select Product') }}</option>
                                            @foreach($products as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Description') }}</label>
                                        <input type="text" name="items[0][description]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Order Qty') }}</label>
                                        <input type="number" name="items[0][order_qty]" class="form-control" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Unit Price') }}</label>
                                        <input type="number" name="items[0][unit_price]" class="form-control unit-price" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Total') }}</label>
                                        <input type="text" class="form-control item-total" readonly value="0.00">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm remove-item" style="display:none;">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-item">{{ __('Add Item') }}</button>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <a href="{{ route('pro.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    let itemIndex = 1;
    
    $(document).ready(function() {
        $('.select2').select2();
        calculateItemTotal(0);
        
        $('#add-item').click(function() {
            const newRow = $('.item-row').first().clone();
            newRow.find('input, select').val('');
            newRow.find('input[name]').each(function() {
                const name = $(this).attr('name').replace('[0]', '[' + itemIndex + ']');
                $(this).attr('name', name);
            });
            newRow.find('.remove-item').show();
            newRow.find('.item-total').val('0.00');
            $('#items-container').append(newRow);
            newRow.find('.select2').select2();
            itemIndex++;
        });
        
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.item-row').remove();
        });
        
        $(document).on('input', '.unit-price, input[name*="[order_qty]"]', function() {
            const row = $(this).closest('.item-row');
            const index = row.index();
            calculateItemTotal(index);
        });
    });
    
    function calculateItemTotal(index) {
        const row = $('.item-row').eq(index);
        const qty = parseFloat(row.find('input[name*="[order_qty]"]').val()) || 0;
        const price = parseFloat(row.find('.unit-price').val()) || 0;
        const total = qty * price;
        row.find('.item-total').val(total.toFixed(2));
    }
    
    function loadSupplierData() {
        const supplierId = $('#supplier_id').val();
        if (supplierId) {
            // Auto-fill supplier code if available
            // You can add AJAX call here to fetch supplier details
        }
    }
    
    function updateExchangeRate() {
        const currencySelect = $('#currency_id');
        const exchangeRateInput = $('#exchange_rate');
        const selectedOption = currencySelect.find('option:selected');
        const rate = selectedOption.data('rate');
        
        if (rate && rate > 0) {
            exchangeRateInput.val(rate);
        }
    }
    
    // Initialize exchange rate on page load
    $(document).ready(function() {
        updateExchangeRate();
    });
</script>
@endpush

