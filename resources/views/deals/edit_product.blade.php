<form action="{{ route('deals.products.update1', [$deal->id, $product->id]) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">{{ __('Product') }}</label>
        <input type="text" class="form-control" value="{{ optional($product->product)->category->name .'/'.optional($product->product)->brand->name.'/'.optional($product->product)->subBrand->name.'/' .optional($product->product)->name .'/'.optional($product->product)->sku }}" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Quantity') }}</label>
        <input type="number" name="quantity" class="form-control" value="{{ $product->quantity }}" min="1" required>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Price') }}</label>
        <input type="number" name="price" class="form-control" 
            value="{{ $product->currency_id ? ($product->exchange_price ?? $product->price) : $product->price }}" 
            min="0" step="0.01" required>
        <small class="text-muted">{{ __('Enter price in selected currency') }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Currency') }}</label>
        <select name="currency_id" id="edit_currency_id" class="form-control select2">
            <option value="">{{ __('Select Currency') }}</option>
            @foreach ($currencies as $id => $currency)
                @php
                    $currencyModel = \App\Models\Currency::find($id);
                    $exchangeRate = $currencyModel ? $currencyModel->exchange_rate : 1;
                @endphp
                <option value="{{ $id }}" 
                    data-rate="{{ $exchangeRate }}"
                    {{ $product->currency_id == $id ? 'selected' : '' }}>{{ $currency }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="exchange_rate_div" style="{{ $product->currency_id ? '' : 'display: none;' }}">
        <label class="form-label">{{ __('Exchange Rate') }}</label>
        <input type="number" name="exchange_rate" id="edit_exchange_rate" class="form-control" 
            value="{{ $product->exchange_rate ?? ($product->currency ? $product->currency->exchange_rate : 1) }}" 
            min="0" step="0.0001">
    </div>

    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
</form>

<script>
    $(document).ready(function() {
        $('#edit_currency_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var exchangeRate = selectedOption.data('rate') || 1;
            
            if ($(this).val()) {
                $('#exchange_rate_div').show();
                $('#edit_exchange_rate').val(exchangeRate);
            } else {
                $('#exchange_rate_div').hide();
                $('#edit_exchange_rate').val('');
            }
        });
        
        // Initialize select2
        $('.select2').select2({
            dropdownParent: $("#commonModal"),
            width: "100%"
        });
    });
</script>
