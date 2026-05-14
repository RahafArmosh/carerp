<form method="POST" action="{{ route('sub-product-invoice.store', ['id' => $id]) }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">

        <div class="row">
            <div class="form-group  col-md-6">
                <label for="product_id" class="form-label">{{ __('Product') }}<span class="text-danger">*</span></label>
                <select name="product_id" class="form-control select2" required id="product-select">
                    @foreach ($product_services as $productId => $productName)
                        <option value="{{ $productId }}">{{ $productName }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sub-product-section" style="display: none;">
                <div id="sub-product-checkboxes"></div>
                {{-- {{ Form::hidden('subProducts[][selected]', '', array('class' => 'sub-products')) }} --}}
            </div>

            <div class="form-group price-input input-group search-form d-none">
                <input type="text" name="quantity" class="form-control quantity" required="required"
                    placeholder="{{ __('Qty') }}">
                <span class="unit input-group-text bg-transparent"></span>
            </div>

            {{-- @if (!$customFields->isEmpty())
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif --}}

            {{-- <input type="hidden" id="product_id" name="product_id" value="{{ $id }}"> --}}

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
                    var customFieldsData = data.customFieldsData;

                    // Initialize table HTML
                    var checkboxesHtml =
                        '<table class="sub-product-table" style="border-collapse: collapse; border: 1px solid #ddd;">';
                    checkboxesHtml += '<thead><tr>';
                    checkboxesHtml +=
                        '<th style="text-align: center;width: 20%;border: 1px solid #ddd;">Select</th>';
                    checkboxesHtml +=
                        '<th style="text-align: center;width: 20%;border: 1px solid #ddd;">ID<br><input type="text" id="filter-id" class="filter-input" style="width: 100%;"></th>';
                    checkboxesHtml +=
                        '<th style="text-align: center;width: 20%;border: 1px solid #ddd;">Name<br><input type="text" id="filter-name" class="filter-input" style="width: 100%;"></th>';
                    checkboxesHtml +=
                        '<th style="text-align: center;width: 20%;border: 1px solid #ddd;">Number<br><input type="text" id="filter-number" class="filter-input" style="width: 100%;"></th>';
                    checkboxesHtml +=
                        '<th style="text-align: center;width: 20%;border: 1px solid #ddd;">Custom Fields<br><input type="text" id="filter-custom-fields" class="filter-input" style="width: 100%;"></th>';
                    checkboxesHtml += '</tr></thead>';
                    checkboxesHtml += '<tbody>';

                    // Loop through each sub-product and create rows
                    subProducts.forEach(function(subProduct) {
                        checkboxesHtml += '<tr>';
                        checkboxesHtml +=
                            '<td style="text-align: center;border: 1px solid #ddd;"><input type="checkbox" name="subProducts[]" value="' +
                            subProduct.id + '"></td>';
                        checkboxesHtml +=
                            '<td style="text-align: center;border: 1px solid #ddd;">' +
                            subProduct.id + '</td>';
                        checkboxesHtml +=
                            '<td style="text-align: center;border: 1px solid #ddd;">' +
                            subProduct.product_service.name + '</td>';
                        checkboxesHtml +=
                            '<td style="text-align: center;border: 1px solid #ddd;">' +
                            subProduct.product_no + '</td>';

                        // Generate custom fields HTML
                        var customFieldsHtml =
                            '<div class="custom-fields-container">';
                        var fields = customFieldsData[subProduct.id] || {};

                        for (var fieldName in fields) {
                            customFieldsHtml +=
                                '<div class="custom-field"><strong>' + fieldName +
                                ':</strong> ' + fields[fieldName] + '</div>';
                        }

                        customFieldsHtml += '</div>';
                        checkboxesHtml +=
                            '<td style="text-align: center;border: 1px solid #ddd;">' +
                            customFieldsHtml + '</td>';
                        checkboxesHtml += '</tr>';
                    });

                    checkboxesHtml += '</tbody></table>';

                    // Update the sub-product div
                    $('#sub-product-checkboxes').html(checkboxesHtml);
                    $('#sub-product-section').show();

                    // Add filtering functionality
                    $('.filter-input').on('keyup', function() {
                        var idFilter = $('#filter-id').val().toLowerCase();
                        var nameFilter = $('#filter-name').val().toLowerCase();
                        var numberFilter = $('#filter-number').val().toLowerCase();
                        var customFieldsFilter = $('#filter-custom-fields').val()
                            .toLowerCase();

                        $('.sub-product-table tbody tr').filter(function() {
                            var idText = $(this).find('td:nth-child(2)')
                                .text().toLowerCase();
                            var nameText = $(this).find('td:nth-child(3)')
                                .text().toLowerCase();
                            var numberText = $(this).find('td:nth-child(4)')
                                .text().toLowerCase();

                            // Extract and concatenate all custom field values into a single string
                            var customFieldsText = '';
                            $(this).find(
                                'td:nth-child(5) .custom-fields-container'
                                ).each(function() {
                                $(this).find('.custom-field').each(
                                    function() {
                                        customFieldsText += $(
                                                this).text()
                                            .toLowerCase() +
                                            ' ';
                                    });
                            });

                            // Apply the filters
                            $(this).toggle(
                                idText.indexOf(idFilter) > -1 &&
                                nameText.indexOf(nameFilter) > -1 &&
                                numberText.indexOf(numberFilter) > -1 &&
                                customFieldsText.indexOf(
                                    customFieldsFilter) > -1
                            );
                        });
                    });

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
