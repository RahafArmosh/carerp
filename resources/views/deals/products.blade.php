<form action="{{ route('deals.products.update', $deal->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body repeater">
        <div id="product-list" data-repeater-list="products">
            <div class="product-row row mb-3" data-repeater-item>
                <div class="col-md-3">
                    <label>Product</label>
                    <select name="id" class="form-control select2 product-select" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $id => $product)
                            <option value="{{ $id }}">{{ $product }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Price</label>
                    <input type="number" name="price" class="form-control product-price" min="0" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <label>Currency</label>
                    <select name="currency_id" class="form-control select2 product-currency" data-placeholder="{{ __('Select Currency') }}">
                        <option value="">{{ __('Select Currency') }}</option>
                        @foreach ($currencies as $id => $currency)
                            <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Exchange Rate</label>
                    <input type="number" name="exchange_rate" class="form-control product-exchange-rate" min="0" step="0.0001" placeholder="Auto">
                </div>
                <div class="col-md-2">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" data-repeater-delete class="btn btn-danger remove-row">×</button>
                </div>
            </div>
        </div>

        <div class="mb-3 text-end">
            <button type="button" id="add-product-row" data-repeater-create class="btn btn-outline-primary">+ Add
                Product</button>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>



<script>
    var selector = "body";

    if ($(selector + " .repeater").length) {
        var $repeater = $(selector + ' .repeater').repeater({
            initEmpty: false,
            defaultValues: {
                'status': 1
            },
            show: function() {
                $(this).slideDown();
            },
            function(setIndexes) {
                $dragAndDrop.on('drop', setIndexes);
            },
            isFirstItemUndeletable: true
        });
        var value = $(selector +
            " .repeater").attr('data-value');
        if (typeof value != 'undefined' && value.length != 0) {
            value = JSON.parse(value);
            $repeater.setList(value);
            console.log(value);
        }
    }
    // Reinitialize select2 for all elements
    $(".select2").select2({
        dropdownParent: $("#commonModal"),
        width: "100%",
        matcher: function(params, data) {
            // If no search term, return all options
            if (!params.term || params.term.trim() === "") {
                return data;
            }

            // Split search query into keywords
            const keywords = params.term.toLowerCase().split(" ");
            const text = data.text.toLowerCase();

            // Check if ALL keywords exist in the option's text (order ignored)
            const isMatch = keywords.every((keyword) => text.includes(keyword));

            return isMatch ? data : null;
        },
    });
    // Reinitialize Select2 when a new repeater row is added
    $(document).on("click", "[data-repeater-create]", function() {
        setTimeout(function() {
            $(".select2").select2({
                dropdownParent: $("#commonModal"),
                width: "100%",
                matcher: function(params, data) {
                    // If no search term, return all options
                    if (!params.term || params.term.trim() === "") {
                        return data;
                    }

                    // Split search query into keywords
                    const keywords = params.term.toLowerCase().split(" ");
                    const text = data.text.toLowerCase();

                    // Check if ALL keywords exist in the option's text (order ignored)
                    const isMatch = keywords.every((keyword) => text.includes(keyword));

                    return isMatch ? data : null;
                },
            });
            
            // Attach currency change handler to new rows
            attachCurrencyHandlers();
        }, 100);
    });
    
    // Handle currency change to auto-populate exchange rate
    function attachCurrencyHandlers() {
        $(document).off('change', '.product-currency').on('change', '.product-currency', function() {
            var $row = $(this).closest('.product-row');
            var $exchangeRateInput = $row.find('.product-exchange-rate');
            var selectedOption = $(this).find('option:selected');
            var exchangeRate = selectedOption.data('rate') || 1;
            
            if ($(this).val()) {
                $exchangeRateInput.val(exchangeRate);
            } else {
                $exchangeRateInput.val('');
            }
        });
    }
    
    // Initialize currency handlers on page load
    attachCurrencyHandlers();
</script>
