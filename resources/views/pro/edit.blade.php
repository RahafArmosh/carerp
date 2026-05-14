@extends('layouts.admin')
@section('page-title')
    {{ __('Edit PRO') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pro.index') }}">{{ __('PRO') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('pro.update', $pro->id) }}" method="POST" id="pro-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pro_no" class="form-label">{{ __('PRO No') }}</label>
                                    <input type="text" class="form-control" value="{{ \Auth::user()->proNumberFormat($pro->pro_no) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id" class="form-label">{{ __('Supplier Name') }}</label>
                                    <select name="supplier_id" id="supplier_id" class="form-control select2" onchange="loadSupplierData()">
                                        <option value="">{{ __('Select Supplier') }}</option>
                                        @foreach($suppliers as $id => $name)
                                            <option value="{{ $id }}" {{ $pro->supplier_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_code" class="form-label">{{ __('Supplier Code') }}</label>
                                    <input type="text" name="supplier_code" id="supplier_code" class="form-control" value="{{ $pro->supplier_code }}" placeholder="{{ __('Auto-filled from supplier') }}">
                                </div>
                            </div> --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="po_date" class="form-label">{{ __('PO Date') }}</label>
                                    <input type="date" name="po_date" id="po_date" class="form-control" value="{{ $pro->po_date ? (\Carbon\Carbon::parse($pro->po_date)->format('Y-m-d')) : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_proforma_no" class="form-label">{{ __('Supplier Proforma No') }}</label>
                                    <input type="text" name="supplier_proforma_no" id="supplier_proforma_no" class="form-control" value="{{ $pro->supplier_proforma_no }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_proforma_date" class="form-label">{{ __('Supplier Proforma Date') }}</label>
                                    <input type="date" name="supplier_proforma_date" id="supplier_proforma_date" class="form-control" value="{{ $pro->supplier_proforma_date ? \Carbon\Carbon::parse($pro->supplier_proforma_date)->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="our_order_ref" class="form-label">{{ __('Our Order Ref') }}</label>
                                    <input type="text" name="our_order_ref" id="our_order_ref" class="form-control" value="{{ $pro->our_order_ref }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_ref" class="form-label">{{ __('Supplier Ref') }}</label>
                                    <input type="text" name="supplier_ref" id="supplier_ref" class="form-control" value="{{ $pro->supplier_ref }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eta_date" class="form-label">{{ __('ETA Date') }}</label>
                                    <input type="date" name="eta_date" id="eta_date" class="form-control" value="{{ $pro->eta_date ? \Carbon\Carbon::parse($pro->eta_date)->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="open" {{ ($pro->status ?? 'open') == 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                                        <option value="delivered" {{ ($pro->status ?? 'open') == 'delivered' ? 'selected' : '' }}>{{ __('Delivered') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control select2" onchange="updateExchangeRate()">
                                        <option value="">{{ __('Select Currency') }}</option>
                                        @foreach($currencies as $id => $name)
                                            <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}" {{ $pro->currency_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                    <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.000001" min="0" value="{{ $pro->exchange_rate ?? 1.0 }}" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>{{ __('Items') }}</h5>
                        <div id="items-container">
                            @foreach($pro->items as $index => $item)
                                <div class="item-row mb-3 border p-3">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Part No') }}</label>
                                            <input type="text" name="items[{{ $index }}][part_no]" class="form-control" value="{{ $item->part_no }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('Product') }}</label>
                                            <select name="items[{{ $index }}][product_id]" class="form-control select2">
                                                <option value="">{{ __('Select Product') }}</option>
                                                @foreach($products as $id => $name)
                                                    <option value="{{ $id }}" {{ $item->product_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('Description') }}</label>
                                            <input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item->description }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('Order Qty') }}</label>
                                            <input type="number" name="items[{{ $index }}][order_qty]" class="form-control" step="0.01" min="0" value="{{ $item->order_qty }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('Supplied Qty') }}</label>
                                            <input type="number" name="items[{{ $index }}][supplied_qty]" class="form-control" step="0.01" min="0" value="{{ $item->supplied_qty }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('Unit Price') }}</label>
                                            <input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit-price" step="0.01" min="0" value="{{ $item->unit_price }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('Total') }}</label>
                                            <input type="text" class="form-control item-total" readonly value="{{ number_format($item->total_amount, 2) }}">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-sm remove-item" {{ $index == 0 ? 'style="display:none;"' : '' }}>
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="add-item">{{ __('Add Item') }}</button>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <a href="{{ route('pro.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
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
    let itemIndex = {{ $pro->items->count() }};
    
    $(document).ready(function() {
        $('.select2').select2();
        $('.item-row').each(function(index) {
            calculateItemTotal(index);
        });
        
        $('#add-item').click(function() {
            const newRow = $('.item-row').first().clone();
            newRow.find('input, select').val('');
            newRow.find('input[name]').each(function() {
                const name = $(this).attr('name').replace(/\[\d+\]/, '[' + itemIndex + ']');
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

