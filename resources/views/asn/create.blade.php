@extends('layouts.admin')
@section('page-title')
    {{ __('Create ASN') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('asn.index') }}">{{ __('ASN') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('asn.store') }}" method="POST" id="asn-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="asn_no" class="form-label">{{ __('ASN No') }}</label>
                                    <input type="text" class="form-control" value="{{ $asn_number_formatted ?? \Auth::user()->asnNumberFormat($asn_number) }}" readonly>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_code" class="form-label">{{ __('Supplier Code') }}</label>
                                    <input type="text" name="supplier_code" id="supplier_code" class="form-control" placeholder="{{ __('Auto-filled from supplier') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="asn_date" class="form-label">{{ __('ASN Date') }}</label>
                                    <input type="date" name="asn_date" id="asn_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_inv_no" class="form-label">{{ __('Supplier Inv No') }}</label>
                                    <input type="text" name="supplier_inv_no" id="supplier_inv_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="container_no" class="form-label">{{ __('Container No') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="container_no" id="container_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dec_no" class="form-label">{{ __('DEC NO') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="dec_no" id="dec_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="boe_number" class="form-label">{{ __('BOE Number') }}</label>
                                    <input type="text" name="boe_number" id="boe_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dec_date" class="form-label">{{ __('DEC DATE') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="dec_date" id="dec_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }} <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control select2" required>
                                        <option value="">{{ __('Select Warehouse') }}</option>
                                        @foreach($warehouses as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control select2" onchange="updateExchangeRate()">
                                        <option value="">{{ __('Select Currency') }}</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate ?? 1 }}">{{ $currency->name }}</option>
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
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Box No') }}</label>
                                        <input type="text" name="items[0][box_no]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Supplier PO No') }}</label>
                                        <input type="text" name="items[0][supplier_po_no]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Our PRO No') }}</label>
                                        <select name="items[0][our_pro_id]" class="form-control select2">
                                            <option value="">{{ __('Select PRO') }}</option>
                                            @foreach($pros as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Order Ref') }}</label>
                                        <input type="text" name="items[0][order_ref]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Part No') }}</label>
                                        <input type="text" name="items[0][part_no]" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Description') }}</label>
                                        <input type="text" name="items[0][description]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('QTY') }}</label>
                                        <input type="number" name="items[0][qty]" class="form-control qty-input" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Received QTY') }}</label>
                                        <input type="number" name="items[0][received_qty]" class="form-control received-qty-input" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Discrepancy') }}</label>
                                        <input type="text" class="form-control discrepancy-input" readonly value="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Unit Price') }}</label>
                                        <input type="number" name="items[0][unit_price]" class="form-control unit-price" step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Total Price') }}</label>
                                        <input type="text" class="form-control total-price" readonly value="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Unit Weight') }}</label>
                                        <input type="number" name="items[0][unit_weight]" class="form-control unit-weight" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Total Weight') }}</label>
                                        <input type="text" class="form-control total-weight" readonly value="0.00">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('HS Code') }}</label>
                                        <input type="text" name="items[0][hs_code]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Container NO') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][container_no]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('DEC NO') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][dec_no]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('DEC DATE') }} <span class="text-danger">*</span></label>
                                        <input type="date" name="items[0][dec_date]" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Origin') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][origin]" class="form-control">
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
                                    <a href="{{ route('asn.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
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
        calculateItemTotals(0);
        
        $('#add-item').click(function() {
            const newRow = $('.item-row').first().clone();
            newRow.find('input, select').val('');
            newRow.find('input[name]').each(function() {
                const name = $(this).attr('name').replace('[0]', '[' + itemIndex + ']');
                $(this).attr('name', name);
            });
            newRow.find('.remove-item').show();
            newRow.find('.discrepancy-input, .total-price, .total-weight').val('0.00');
            $('#items-container').append(newRow);
            newRow.find('.select2').select2();
            itemIndex++;
        });
        
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.item-row').remove();
        });
        
        $(document).on('input', '.qty-input, .received-qty-input, .unit-price, .unit-weight', function() {
            const row = $(this).closest('.item-row');
            const index = row.index();
            calculateItemTotals(index);
        });

        $('#asn-form').on('submit', function(e) {
            let invalid = false;
            $('#asn-form .item-row').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                const received = parseFloat($(this).find('.received-qty-input').val()) || 0;
                if (received > qty + 1e-9) {
                    invalid = true;
                    return false;
                }
            });
            if (invalid) {
                e.preventDefault();
                alert(@json(__('Received quantity cannot exceed ordered quantity (QTY) on a line.')));
            }
        });
    });
    
    function calculateItemTotals(index) {
        const row = $('.item-row').eq(index);
        if (!row.length) {
            return;
        }
        const qty = parseFloat(row.find('.qty-input').val()) || 0;
        const receivedInput = row.find('.received-qty-input');
        if (qty >= 0) {
            receivedInput.attr('max', qty);
        } else {
            receivedInput.removeAttr('max');
        }
        let receivedQty = parseFloat(receivedInput.val()) || 0;
        if (receivedQty > qty) {
            receivedQty = qty;
            receivedInput.val(receivedQty.toFixed(2));
        }
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const unitWeight = parseFloat(row.find('.unit-weight').val()) || 0;
        
        // Calculate discrepancy
        const discrepancy = receivedQty - qty;
        row.find('.discrepancy-input').val(discrepancy.toFixed(2));
        
        // Calculate total price
        const totalPrice = receivedQty * unitPrice;
        row.find('.total-price').val(totalPrice.toFixed(2));
        
        // Calculate total weight
        const totalWeight = receivedQty * unitWeight;
        row.find('.total-weight').val(totalWeight.toFixed(2));
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

