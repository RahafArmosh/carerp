<form method="POST" action="{{ route('sub-product-invoice.store', ['id' => $id]) }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="product_id" class="form-label">{{ __('Product') }}<span class="text-danger">*</span></label>
                <select name="product_id" class="form-control select" required id="product-select">
                    @foreach ($product_services as $productId => $productName)
                        <option value="{{ $productId }}">{{ $productName }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sub-product-section" style="display: none;">
                <div id="sub-product-checkboxes"></div>
                <input type="hidden" name="subProducts[][selected]" class="sub-products" value="">
            </div>

            <div class="form-group price-input input-group search-form">
                <input type="text" name="quantity" class="form-control quantity" required
                    placeholder="{{ __('Qty') }}">
                <span class="unit input-group-text bg-transparent"></span>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>

<script>
    $(document).ready(function() {
        // Add event listener to product select box
        $('#product-select').on('change', function() {
            var productId = $(this).val();

            // Make AJAX request to get sub-products for the selected product
            $.ajax({
                url: "{{ route('get-sub-products', ['productId' => ':productId']) }}".replace(
                    ':productId', productId),
                type: 'GET',
                data: {
                    productId: productId
                },
                success: function(data) {
                    var subProducts = data.subProducts;
                    var checkboxesHtml = '';

                    // Generate HTML for sub-products
                    subProducts.forEach(function(subProduct) {
                        checkboxesHtml +=
                            '<label class="form-label"><input type="checkbox" name="subProducts[]" value="' +
                            subProduct.id + '"> ' + subProduct.name +
                            '</label><br>';
                    });

                    // Update the sub-product div
                    $('#sub-product-checkboxes').html(checkboxesHtml);
                    $('#sub-product-section').show();
                },
                error: function() {
                    console.log('Error fetching sub-products.');
                }
            });
        });
    });

    // Add event listener to sub-product checkboxes
    $('#sub-product-section').on('change', 'input[type="checkbox"]', function() {
        // Calculate the total quantity based on the selected checkboxes in the current product section
        var totalQuantity = $('#sub-product-section input[type="checkbox"]:checked').length;

        // Update the quantity input value
        $('.quantity').val(totalQuantity);
    });
</script>
