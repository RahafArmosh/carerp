@extends('layouts.admin')
@section('page-title')
{{ __('Invoice Create') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('rentinvoice.index') }}">{{ __('Invoice') }}</a></li>
<li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
@endsection
@push('script-page')
<style>
    .custom-select {
        width: 200px;
        height: 70px;
        line-height: 70px;
        padding: 10px;
    }

    .sub-product-table {
        width: 50%;
        margin: 0 auto;
        /* padding-top: 20px ; */
    }

    .option-input {
        -webkit-appearance: none;
        -moz-appearance: none;
        -ms-appearance: none;
        -o-appearance: none;
        appearance: none;
        position: relative;
        top: 13.33333px;
        right: 0;
        bottom: 0;
        left: 0;
        height: 40px;
        width: 40px;
        transition: all 0.15s ease-out 0s;
        background: #cbd1d8;
        border: none;
        color: #fff;
        cursor: pointer;
        display: inline-block;
        margin-right: 0.5rem;
        outline: none;
        position: relative;
        z-index: 1000;
    }

    .option-input:hover {
        background: #9faab7;
    }

    .option-input:checked {
        background: linear-gradient(141.55deg, #C0A145 3.46%, #C0A145 99.86%), #C0A145 !important;
    }

    .option-input:checked::before {
        width: 40px;
        height: 40px;
        display: flex;
        content: '\f00c';
        font-size: 25px;
        font-weight: bold;
        position: absolute;
        align-items: center;
        justify-content: center;
        font-family: 'Font Awesome 5 Free';
    }

    .option-input.radio {
        border-radius: 50%;
    }

    .option-input.radio::after {
        border-radius: 50%;
    }

    @keyframes click-wave {
        0% {
            height: 40px;
            width: 40px;
            opacity: 0.35;
            position: relative;
        }

        100% {
            height: 200px;
            width: 200px;
            margin-left: -80px;
            margin-top: -80px;
            opacity: 0;
        }
    }
</style>
<!-- Include Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
<script>
    $(document).ready(function() {
            $('.select2.item').select2({
                placeholder: 'Search for a product...',
                allowClear: true, // Add an option to clear the selection
                theme: 'classic',
                width: '100%',
            });
        });
        $('.select2-container').css({
            'width': '100%', // Set the width to match your form's width
            'border': '1px solid #ccc', // Example border style
            'border-radius': '4px', // Example border-radius
            'box-shadow': 'none', // Example box-shadow
        });
</script>
<script>
    var selector = "body";
        let TotalTax = 0;
        let productId = 0;
        // Function to get color name from ID
        function getColorName(colorId, colorsArray) {
            for (var i = 0; i < colorsArray.length; i++) {
                if (colorsArray[i].id === colorId) {
                    return colorsArray[i].name;
                }
            }
            return 'Unknown Color';
        }

        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }

                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
                },
                ready: function(setIndexes) {

                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
                console.log(value);
            }

        }

        $(document).on('change', '#customer', function() {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    if (data != '') {
                        $('#customer_detail').html(data);
                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }

                },

            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.item', function() {

            var iteams_id = $(this).val();
            productId = iteams_id;
            var url = $(this).data('url');
            var el = $(this);
            var index = $('.item').index(this);
            var colors = @json($allColor);
            var subProductSection = $('.sub-product-section').eq(index);
            var subProductCheckboxes = $('.sub-product-checkboxes').eq(index);
            if (index == 0) {
                $('#colorTh').show();
                $('#colorThIN').show();
                $('#countryTh').show();
            }


            var colorTd = $('.colorTd').eq(index);
            var colorTdIn = $('.colorTdIn').eq(index);
            var countryTd = $('.countryTd').eq(index);

            colorTd.show();
            colorTdIn.show();
            countryTd.show();
            $(this).closest('tr').find('.colorSelect').val(null).trigger('change');
            $(this).closest('tr').find('.colorSelectIn').val(null).trigger('change');
            $(this).closest('tr').find('.countrySelect').val(null).trigger('change');
            if (iteams_id) {
                $.ajax({
                    url: "{{ route('get-sub-products', ['productId' => ':productId']) }}".replace(
                        ':productId', iteams_id),
                    type: 'GET',
                    success: function(data) {
                        var subProducts = data.subProducts;
                        // Update the sub-product checkboxes dynamically
                        var tableHtml = '<table class="sub-product-table">';
                        tableHtml +=
                            '<thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Number</th><th>Exterior Color</th><th>Interior Color</th><th>Location</th></tr></thead>';
                        tableHtml += '<tbody>';

                        subProducts.forEach(function(subProduct) {
                            var rowId = 'subProductRow_' + index;
                            var exteriorColor = getColorName(subProduct.exterior_color_id,
                                colors);
                            var interiorColor = getColorName(subProduct.interior_color_id,
                                colors);
                            var countryName = subProduct.country_name;
                            tableHtml += '<tr id="' + rowId + '">';
                            tableHtml +=
                                '<td><input type="checkbox" name="subProducts[]" value="' +
                                subProduct.id + '"></td>';
                            tableHtml += '<td>' + subProduct.id + '</td>';
                            tableHtml += '<td>' + subProduct.chassis_no + '</td>';
                            tableHtml += '<td>' + subProduct.engine_no + '</td>';
                            tableHtml += '<td>' + exteriorColor + '</td>';
                            tableHtml += '<td>' + interiorColor + '</td>';
                            tableHtml += '<td>' + countryName + '</td>';
                            tableHtml += '</tr>';
                        });

                        tableHtml += '</tbody></table>';

                        subProductCheckboxes.html(tableHtml);
                        subProductSection.show();
                    },
                    error: function() {
                        console.log('Error fetching sub-products.');
                    }
                });
            } else {
                // If no product is selected, hide the sub-product section
                subProductSection.hide();
            }
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);
                    console.log(el.parent().parent().find('.quantity'))
                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    // $('.pro_description').text(item.product.description);

                    // var taxes = '';
                    // var tax = [];

                    // var totalItemTaxRate = 0;

                    // if (item.taxes == 0) {
                    //     taxes += '-';
                    // } else {
                    //     for (var i = 0; i < item.taxes.length; i++) {
                    //         taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name + ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                    //         tax.push(item.taxes[i].id);
                    //         totalItemTaxRate += parseFloat(item.taxes[i].rate);
                    //     }
                    // }
                    var old_tax_price = $(".totalTax").val();
                    totalItemTaxRate = parseInt(TotalTax);
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) * parseFloat((item.product
                        .sale_price * 1));
                    // $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    // $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    // $(el.parent().parent().find('.taxes')).html(taxes);
                    // $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);
                    $(el.parent().parent().find('.amount')).html(item.totalAmount);

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    var Qty = $('.quantity');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value) * Qty[j].value;

                    }

                    // var totalItemTaxPrice = 0;
                    // var itemTaxPriceInput = $('.itemTaxPrice');
                    // for (var j = 0; j < itemTaxPriceInput.length; j++) {
                    //     totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                    //     $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount)+parseFloat(itemTaxPriceInput[j].value));
                    // }

                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                        totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    }

                    $('.subTotal').html(totalItemPrice.toFixed(2));
                    $('.totalTax').html(((totalItemPrice - parseFloat(totalItemDiscountPrice)) * (
                        totalItemTaxRate / 100)).toFixed(2));

                    var totalAmountValue = $('.totalAmount').text();
                    totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (
                        parseInt(TotalTax) / 100));
                    var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
                    var TotalAmount = (totalItemPrice - parseFloat(totalItemDiscountPrice)) + ((
                        totalItemPrice - parseFloat(totalItemDiscountPrice)) * (parseInt(
                        TotalTax) / 100));

                    $('.totalAmount').html((TotalAmount).toFixed(2));


                },
            });
        });
        $(document).on('change', 'tbody.ui-sortable .colorSelect', function() {
            var colorId = $(this).val(); // Get the selected color ID
            var index = $('.item').index(this);
            var colorIdInG = $(this).closest('tr').find('.colorSelectIn').val();
            var countryId = $(this).closest('tr').find('.countrySelect').val();
            var subProductSection = $('.sub-product-section').eq(index);
            var subProductCheckboxes = $('.sub-product-checkboxes').eq(index);
            var itemValue = $(this).closest('tr').find('.item').val();
            var colors = @json($allColor);
            if (!colorIdInG && !countryId) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorId).replace(':colorIdIn', -1)
                    .replace(':country', -1);
            } else if (colorIdInG && !countryId) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorId).replace(':colorIdIn',
                    colorIdInG).replace(':country', -1);
            } else if (!colorIdInG && countryId) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorId).replace(':colorIdIn', -1)
                    .replace(':country', countryId);
            } else {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorId).replace(':colorIdIn',
                    colorIdInG).replace(':country', countryId);
            }
            console.log(url);
            if (colorId) {
                $.ajax({
                    url: url, // Update the route as needed
                    type: 'GET',
                    success: function(data) {
                        var subProducts = data.subProducts;
                        // Update the sub-product checkboxes dynamically
                        var tableHtml = '<table class="sub-product-table">';
                        tableHtml +=
                            '<thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Number</th><th>Exterior Color</th><th>Interior Color</th><th>Location</th></tr></thead>';
                        tableHtml += '<tbody>';

                        subProducts.forEach(function(subProduct) {
                            var rowId = 'subProductRow_' + index;
                            var exteriorColor = getColorName(subProduct.exterior_color_id,
                                colors);
                            var interiorColor = getColorName(subProduct.interior_color_id,
                                colors);
                            var countryName = subProduct.country_name;
                            tableHtml += '<tr id="' + rowId + '">';
                            tableHtml +=
                                '<td><input type="checkbox" name="subProducts[]" value="' +
                                subProduct.id + '"></td>';
                            tableHtml += '<td>' + subProduct.id + '</td>';
                            tableHtml += '<td>' + subProduct.chassis_no + '</td>';
                            tableHtml += '<td>' + subProduct.engine_no + '</td>';
                            tableHtml += '<td>' + exteriorColor + '</td>';
                            tableHtml += '<td>' + interiorColor + '</td>';
                            tableHtml += '<td>' + countryName + '</td>';
                            tableHtml += '</tr>';
                        });

                        tableHtml += '</tbody></table>';
                        subProductCheckboxes.html('');
                        subProductCheckboxes.html(tableHtml);
                        subProductSection.show();
                    },
                    error: function() {
                        console.log('Error fetching sub-products.');
                    }
                });
            }
        });


        // Event listener for the color select dropdown change
        $(document).on('change', 'tbody.ui-sortable .colorSelectIn', function() {
            var colorId = $(this).val(); // Get the selected color ID
            var index = $('.item').index(this);
            var colorIdG = $(this).closest('tr').find('.colorSelect').val();
            var countryId = $(this).closest('tr').find('.countrySelect').val();
            var subProductSection = $('.sub-product-section').eq(index);
            var subProductCheckboxes = $('.sub-product-checkboxes').eq(index);
            var itemValue = $(this).closest('tr').find('.item').val();
            var colors = @json($allColor);
            if (!colorIdG && !countryId) {

                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', -1).replace(':colorIdIn', colorId)
                    .replace(':country', -1);
            } else if (!colorIdG && countryId) {

                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', -1).replace(':colorIdIn', colorId)
                    .replace(':country', countryId);
            } else if (colorIdG && countryId) {

                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorIdG).replace(':colorIdIn',
                    colorId).replace(':country', countryId);
            } else {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorIdG).replace(':colorIdIn',
                    colorId).replace(':country', -1);

            }
            console.log(url);
            if (colorId) {
                $.ajax({
                    url: url, // Update the route as needed
                    type: 'GET',
                    success: function(data) {
                        var subProducts = data.subProducts;
                        // Update the sub-product checkboxes dynamically
                        var tableHtml = '<table class="sub-product-table">';
                        tableHtml +=
                            '<thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Number</th><th>Exterior Color</th><th>Interior Color</th><th>Location</th></tr></thead>';
                        tableHtml += '<tbody>';

                        subProducts.forEach(function(subProduct) {
                            var rowId = 'subProductRow_' + index;
                            var exteriorColor = getColorName(subProduct.exterior_color_id,
                                colors);
                            var interiorColor = getColorName(subProduct.interior_color_id,
                                colors);
                            var countryName = subProduct.country_name;
                            tableHtml += '<tr id="' + rowId + '">';
                            tableHtml +=
                                '<td><input type="checkbox" name="subProducts[]" value="' +
                                subProduct.id + '"></td>';
                            tableHtml += '<td>' + subProduct.id + '</td>';
                            tableHtml += '<td>' + subProduct.chassis_no + '</td>';
                            tableHtml += '<td>' + subProduct.engine_no + '</td>';
                            tableHtml += '<td>' + exteriorColor + '</td>';
                            tableHtml += '<td>' + interiorColor + '</td>';
                            tableHtml += '<td>' + countryName + '</td>';
                            tableHtml += '</tr>';
                        });

                        tableHtml += '</tbody></table>';
                        subProductCheckboxes.html('');
                        subProductCheckboxes.html(tableHtml);
                        subProductSection.show();
                    },
                    error: function() {
                        console.log('Error fetching sub-products.');
                    }
                });
            }
        });

        $(document).on('change', 'tbody.ui-sortable .countrySelect', function() {
            var countryId = $(this).val(); // Get the selected color ID
            var index = $('.item').index(this);
            var colorIdInG = $(this).closest('tr').find('.colorSelectIn').val();
            var colorIdG = $(this).closest('tr').find('.colorSelect').val();
            var subProductSection = $('.sub-product-section').eq(index);
            var subProductCheckboxes = $('.sub-product-checkboxes').eq(index);
            var itemValue = $(this).closest('tr').find('.item').val();
            var colors = @json($allColor);
            if (!colorIdInG && !colorIdG) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', -1).replace(':colorIdIn', -1)
                    .replace(':country', countryId);
            } else if (!colorIdInG && colorIdG) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorIdG).replace(':colorIdIn', -1)
                    .replace(':country', countryId);
            } else if (colorIdInG && !colorIdG) {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', -1).replace(':colorIdIn', colorIdInG)
                    .replace(':country', countryId);
            } else {
                var url =
                    "{{ route('get-sub-products', ['productId' => ':productId', 'colorId' => ':colorId', 'colorIdIn' => ':colorIdIn', 'country' => ':country']) }}";
                url = url.replace(':productId', itemValue).replace(':colorId', colorIdG).replace(':colorIdIn',
                    colorIdInG).replace(':country', countryId);
            }
            console.log(url);
            if (countryId) {
                $.ajax({
                    url: url, // Update the route as needed
                    type: 'GET',
                    success: function(data) {
                        var subProducts = data.subProducts;
                        // Update the sub-product checkboxes dynamically
                        var tableHtml = '<table class="sub-product-table">';
                        tableHtml +=
                            '<thead><tr><th>Select</th><th>ID</th><th>Name</th><th>Number</th><th>Exterior Color</th><th>Interior Color</th><th>Location</th></tr></thead>';
                        tableHtml += '<tbody>';

                        subProducts.forEach(function(subProduct) {
                            var rowId = 'subProductRow_' + index;
                            var exteriorColor = getColorName(subProduct.exterior_color_id,
                                colors);
                            var interiorColor = getColorName(subProduct.interior_color_id,
                                colors);
                            var countryName = subProduct.country_name;
                            tableHtml += '<tr id="' + rowId + '">';
                            tableHtml +=
                                '<td><input type="checkbox" name="subProducts[]" value="' +
                                subProduct.id + '"></td>';
                            tableHtml += '<td>' + subProduct.id + '</td>';
                            tableHtml += '<td>' + subProduct.chassis_no + '</td>';
                            tableHtml += '<td>' + subProduct.engine_no + '</td>';
                            tableHtml += '<td>' + exteriorColor + '</td>';
                            tableHtml += '<td>' + interiorColor + '</td>';
                            tableHtml += '<td>' + countryName + '</td>';
                            tableHtml += '</tr>';
                        });

                        tableHtml += '</tbody></table>';
                        subProductCheckboxes.html('');
                        subProductCheckboxes.html(tableHtml);
                        subProductSection.show();
                    },
                    error: function() {
                        console.log('Error fetching sub-products.');
                    }
                });
            }
        });

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = parseInt(TotalTax); //$(el.find('.itemTaxRate')).val();
            // totalItemTaxRate = parseInt(totalItemTaxRate)  + parseInt(TotalTax);
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            // $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(amount));

            // var totalItemTaxPrice = 0;
            // var itemTaxPriceInput = $('.itemTaxPrice');
            // for (var j = 0; j < itemTaxPriceInput.length; j++) {
            //     totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            // }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }
            var sumAmount = totalItemPrice;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(((sumAmount - existingDiscount) * (totalItemTaxRate / 100)).toFixed(2));
            var totalAmountValue = $('.totalAmount').text();
            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            var TotalAmount = (existingSubTotal - existingDiscount) + ((existingSubTotal - existingDiscount) * (
                parseInt(TotalTax) / 100));

            $('.totalAmount').html((TotalAmount).toFixed(2));
            // $('.totalAmount').html((parseFloat(subTotal)+totalAccount).toFixed(2));

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = parseInt(TotalTax); //$(el.find('.itemTaxRate')).val();
            // totalItemTaxRate = parseInt(totalItemTaxRate) + parseInt(TotalTax);
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            // $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            $('.totalTax').html(((totalItemPrice - existingDiscount) * (totalItemTaxRate / 100)).toFixed(2));
            $('.totalAmount').html(((subTotal - existingDiscount) + ((subTotal - existingDiscount) * (
                totalItemTaxRate / 100))).toFixed(2));


        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = parseInt(TotalTax); //$(el.find('.itemTaxRate')).val();
            // totalItemTaxRate = parseInt(totalItemTaxRate) + parseInt(TotalTax);
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(amount));

            var totalItemTaxPrice = parseInt(TotalTax);
            // var itemTaxPriceInput = $('.itemTaxPrice');
            // for (var j = 0; j < itemTaxPriceInput.length; j++) {
            //     totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            // }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }


            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(((totalItemPrice - totalItemDiscountPrice) * parseFloat((totalItemTaxRate / 100)))
                .toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal) + ((totalItemPrice - totalItemDiscountPrice) * parseFloat((
                totalItemTaxRate / 100)))).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }

        //     $(document).on('change', '.sub-product-checkboxes input[type="checkbox"]', function () {
        //     // Calculate the total quantity based on the selected checkboxes
        //     var totalQuantity  = 0 ;
        //     totalQuantity = $('.sub-product-checkboxes input[type="checkbox"]:checked').length;
        //     console.log("totalQuantity" + totalQuantity);
        //     // Assuming you want to update the quantity input value
        //     // Get the closest quantity input field in the same row
        //     var quantityInput = $(this).closest('td').find('.quantity');

        //     // Update the quantity input value
        //     quantityInput.val(totalQuantity);
        //     quantityInput.trigger('keyup');
        // });
        //     var autoSelectCheckbox = document.getElementById('autoSelectCheckbox');
        // var subProductCheckboxes = document.querySelector('.sub-product-checkboxes');
        //     autoSelectCheckbox.addEventListener('change', function() {
        //     if (autoSelectCheckbox.checked) {
        //         // If Auto Select is checked, hide the checkboxes and activate quantity
        //         subProductCheckboxes.style.display = 'none';
        //         document.querySelector('.quantity').removeAttribute('disabled');
        //         // Add logic to activate quantity as needed
        //     } else {
        //         // If Auto Select is unchecked, show the checkboxes
        //         subProductCheckboxes.style.display = 'block';
        //         // Add logic to deactivate quantity as needed
        //     }
        // });
        function toggleSubProductSection(show) {
            // var subProductSection = document.getElementById('subProductSection');
            // subProductSection.style.display = show ? 'block' : 'none';
            var radio = $(event.target);
            var repeaterItem = radio.closest('[data-repeater-item]');
            var subProductSection = repeaterItem.find('.subProductSection');
            var quantityInput = repeaterItem.find('.quantity');
            subProductSection.css('display', show ? 'block' : 'none');

            if (!show) {
                quantityInput.removeAttr('disabled');
            } else {
                quantityInput.attr('disabled', 'disabled');
                quantityInput.val('');
            }
        }
        $(document).on('change', '.sub-product-section .sub-product-table input[type="checkbox"]', function() {
            // Find the parent sub-product section
            var subProductSection = $(this).closest('.sub-product-section');

            // Find the parent row of the sub-product section
            var row = subProductSection.closest('tr');

            // Calculate the total quantity based on the selected checkboxes in the current row
            var totalQuantity = subProductSection.find('.sub-product-table input[type="checkbox"]:checked').length;

            // Update the corresponding hidden input value
            var hiddenInput = row.find('.sub-products');
            hiddenInput.val(subProductSection.find('.sub-product-table input[type="checkbox"]:checked').map(
                function() {
                    return $(this).val();
                }).get());

            // Get the previous row
            var prevRow = row.prev('tr');

            // Get the quantity input in the first row of the current repeater item
            var quantityInput = row.closest('[data-repeater-item]').find('.quantity');

            // Update the quantity input value
            quantityInput.val(totalQuantity);
            quantityInput.trigger('keyup');

        });
</script>
<script>
    $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
</script>
<script>
    $('#choices-multiple1').on('change', function() {
            // Get selected tax rates
            var selectedValues = $(this).val();
            var taxData = <?php echo json_encode($fullTax); ?>;
            TotalTax = 0;

            // Your logic to calculate total tax amount based on selected rates


            for (let i = 0; i < selectedValues.length; i++) {
                for (let j = 0; j < taxData.length; j++) {
                    if (taxData[j].id === parseInt(selectedValues[i])) {
                        TotalTax += parseInt(taxData[j].rate);
                    }
                }
            }

            var totalAmountValue = $('.totalAmount').text();
            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            var TotalAmount = (existingSubTotal - existingDiscount) + ((existingSubTotal - existingDiscount) * (
                parseInt(TotalTax) / 100));

            // $('.totalAmount').text(totalAmount.toFixed(2));
            $('.totalAmount').html(TotalAmount.toFixed(2))
            $('.tax_val').text(parseInt(TotalTax));
            $('.totalTax').html((existingSubTotal - existingDiscount) * (parseInt(TotalTax) / 100));

        });
</script>
<script>
    $(document).ready(function() {
            $('#currency_id').change(function() {
                if ($(this).val() !== '') {
                    $('#exchange_rate_div').show();
                } else {
                    $('#exchange_rate_div').hide();
                }
                const currencyId = $(this).val();
                fetch(`/get-exchange-rate/${currencyId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Display the exchange rate div
                        exchangeRateDiv.style.display = 'block';
                        // Set the exchange rate input value
                        document.getElementById('exchange_rate').value = data.exchange_rate;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });


        const currencySelect = document.getElementById('currency_id');
        const exchangeRateDiv = document.getElementById('exchange_rate_div');
        currencySelect.addEventListener('change', function() {
            // Get the selected currency id
            const currencyId = this.value;

            // Make an AJAX request to fetch the exchange rate based on the selected currency
            fetch(`/get-exchange-rate/${currencyId}`)
                .then(response => response.json())
                .then(data => {
                    // Set the exchange rate input value
                    document.getElementById('exchange_rate').value = data.exchange_rate;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
</script>
@endpush
@section('content')
<div class="row">
    <form action="rentinvoice" class="w-100" enctype="multipart/form-data">
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group" id="customer-box">
                                <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                <select name="customer_id" class="form-control select" id="customer"
                                    data-url="{{ route('invoice.customer') }}" required>
                                    @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $customer->id == $customerId ? 'selected' :
                                        '' }}>{{ $customer->name }}
                                    </option>
                                    @endforeach
                                </select>

                            </div>

                            <div id="customer_detail" class="d-none">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                        <div class="form-icon-user">
                                            <input type="date" name="issue_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                                        <div class="form-icon-user">
                                            <input type="date" name="due_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>






                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="invoice_number" class="form-label">{{ __('Invoice Number')
                                            }}</label>

                                        <div class="form-icon-user">
                                            <input type="text" class="form-control" name="invoice_number"
                                                value="{{ $invoice_number }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                        <select name="category_id" class="form-control select" required>
                                            @foreach ($category as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_number" class="form-label">{{ __('Ref Number') }}</label>
                                        <div class="form-icon-user">
                                            <span><i class="ti ti-joint"></i></span>
                                            <input type="text" name="ref_number" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                        <select name="currency_id" class="form-control select">
                                            @foreach ($currency as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6" id="exchange_rate_div" style="display: none;">
                                    <div class="form-group">
                                        <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                        <div class="form-icon-user">
                                            <span><i class="ti ti-joint"></i></span>
                                            <input type="text" name="exchange_rate" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="document">Document:</label>
                                    <input type="file" class="form-control" id="documents" name="documents[]" multiple>
                                </div>
                                {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-check custom-checkbox mt-4"> --}}
                                        {{-- <input class="form-check-input" type="checkbox" name="discount_apply"
                                            id="discount_apply"> --}}
                                        {{-- <label class="form-check-label " for="discount_apply">{{__('Discount
                                            Apply')}}</label> --}}
                                        {{-- </div> --}}
                                    {{-- </div> --}}
                                {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-group"> --}}
                                        {{-- {{Form::label('sku',__('SKU')) }} --}}
                                        {{-- {!!Form::text('sku', null,array('class' =>
                                        'form-control','required'=>'required')) !!} --}}
                                        {{-- </div> --}}
                                    {{-- </div> --}}
                                @if (!$customFields->isEmpty())
                                <div class="col-md-6">
                                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                        @include('customFields.formBuilder')
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <h5 class=" d-inline-block mb-4">{{ __('Product & Services') }}</h5>
            <div class="card repeater">
                <div class="item-section py-2">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                            <div class="all-button-box me-2">
                                <a href="#" data-repeater-create="" class="btn btn-primary" data-bs-toggle="modal"
                                    data-target="#add-bank">
                                    <i class="ti ti-plus"></i> {{ __('Add item') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body table-border-style mt-2">
                    <div class="table-responsive">
                        <table class="table  mb-0 table-custom-style" data-repeater-list="items" id="sortable-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Items') }}</th>
                                    <th id="colorTh" style="display: none;">{{ __('Exterior Color') }}</th>
                                    <th id="colorThIN" style="display: none;">{{ __('Interior Color') }}</th>
                                    <th id="countryTh" style="display: none;">{{ __('Location') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Price') }} </th>
                                    <th>{{ __('Discount') }}</th>
                                    <th></th>
                                    <th class="text-end">{{ __('Amount') }} <br><small
                                            class="text-danger font-weight-bold">{{ __('after tax & discount')
                                            }}</small>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody class="ui-sortable" data-repeater-item>
                                <tr>

                                    <td width="25%" class="form-group">
                                        <select name="item" class="form-control select2 item"
                                            data-url="{{ route('invoice.product') }}" required>
                                            @foreach ($product_services as $product_service)
                                            <option value="{{ $product_service->id }}">
                                                {{ $product_service->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 colorTd" style="display: none;">
                                        <select name="color_id" class="form-control select colorSelect">
                                            @foreach ($colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 colorTdIn" style="display: none;">
                                        <select name="color_id_in" class="form-control select colorSelectIn">
                                            @foreach ($colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 countryTd" style="display: none;">
                                        <select name="country_id" class="form-control select countrySelect">
                                            @foreach ($countries as $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="quantity" class="form-control quantity" required
                                                placeholder="{{ __('Qty') }}">
                                            <span class="unit input-group-text bg-transparent"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="price" class="form-control price" required
                                                placeholder="{{ __('Price') }}" required>
                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="discount" class="form-control discount" required
                                                placeholder="{{ __('Discount') }}">
                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>

                                    <td width="25%" class="form-group">
                                        <select name="item" class="form-control select2 item"
                                            data-url="{{ route('invoice.product') }}" required>
                                            @foreach ($product_services as $product_service)
                                            <option value="{{ $product_service->id }}">
                                                {{ $product_service->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 colorTd" style="display: none;">
                                        <select name="color_id" class="form-control select colorSelect">
                                            @foreach ($colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 colorTdIn" style="display: none;">
                                        <select name="color_id_in" class="form-control select colorSelectIn">
                                            @foreach ($colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="form-group pt-0 countryTd" style="display: none;">
                                        <select name="country_id" class="form-control select countrySelect">
                                            @foreach ($countries as $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="quantity" class="form-control quantity" required
                                                placeholder="{{ __('Qty') }}">

                                            <span class="unit input-group-text bg-transparent"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="price" class="form-control price" required
                                                placeholder="{{ __('Price') }}" required>

                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group price-input input-group search-form">
                                            <input type="text" name="discount" class="form-control discount" required
                                                placeholder="{{ __('Discount') }}">

                                            <span class="input-group-text bg-transparent">{{
                                                \Auth::user()->currencySymbol() }}</span>
                                        </div>
                                    </td>



                                    <td>
                                        <div class="form-group">
                                            <div class="input-group colorpickerinput">
                                                <div class="taxes"></div>
                                                <input type="hidden" name="tax" class="form-control tax text-dark">
                                                <input type="hidden" name="itemTaxPrice"
                                                    class="form-control itemTaxPrice">
                                                <input type="hidden" name="itemTaxRate"
                                                    class="form-control itemTaxRate">
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-end amount">0.00</td>
                                    <td>
                                        <a href="#"
                                            class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 bs-pass-para"
                                            data-repeater-delete></a>
                                    </td>
                                </tr>
                                <tr>
                                    <!-- Add custom fields dynamically -->
                                    @foreach ($customFieldsProducts as $customField)
                                    @if ($customField->type == 'text')
                                    <td>

                                        <div class="form-group">
                                            <label for="customFieldP-{{ $customField->id }}" class="form-label">{{
                                                __($customField->name) }}</label>
                                            <div class="input-group">
                                                <input type="text" name="customFieldP[{{ $customField->id }}]"
                                                    class="form-control">
                                            </div>
                                        </div>

                                    </td>
                                    @elseif($customField->type == 'email')
                                    <td>
                                        <div class="form-group">
                                            <label for="customFieldP-{{ $customField->id }}" class="form-label">{{
                                                __($customField->name) }}</label>
                                            <div class="input-group">
                                                <input type="email" name="customFieldP[{{ $customField->id }}]"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </td>
                                    @elseif($customField->type == 'number')
                                    <td>
                                        <div class="form-group">
                                            <label for="customFieldP-{{ $customField->id }}" class="form-label">{{
                                                __($customField->name) }}</label>
                                            <div class="input-group">
                                                <input type="number" name="customFieldP[{{ $customField->id }}]"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </td>
                                    @elseif($customField->type == 'date')
                                    <td>
                                        <div class="form-group">
                                            <label for="customFieldP-{{ $customField->id }}" class="form-label">{{
                                                __($customField->name) }}</label>
                                            <div class="input-group">
                                                <input type="date" name="customFieldP[{{ $customField->id }}]"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </td>
                                    @elseif($customField->type == 'textarea')
                                    <td>
                                        <div class="form-group">
                                            <label for="customFieldP-{{ $customField->id }}" class="form-label">{{
                                                __($customField->name) }}</label>
                                            <div class="input-group">
                                                <textarea name="customFieldP[{{ $customField->id }}]"
                                                    class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                <tr>
                                    <td colspan="7">
                                        <div class="sub-product-section"
                                            style="display: none;  width: 100%; text-align: center;">
                                            <div
                                                style="display: flex; justify-content: space-between;width: 40%;margin: 0 auto;padding-bottom: 10px">
                                                <div>
                                                    <input type="radio" id="autoSelect" name="selectType" checked
                                                        onclick="toggleSubProductSection(false)" value="auto"
                                                        class="option-input radio">
                                                    <label for="autoSelect">Auto Select</label>
                                                </div>
                                                <div>
                                                    <input type="radio" id="manualSelect" name="selectType"
                                                        onclick="toggleSubProductSection(true)" value="manual"
                                                        class="option-input radio">
                                                    <label for="manualSelect">Manual Select</label>
                                                </div>
                                            </div>

                                            <div style="display: none;" class="subProductSection">
                                                <div class="sub-product-checkboxes  width: 100%; text-align: center;">
                                                </div>
                                                <input type="hidden" name="subProducts[][selected]"
                                                    class="sub-products">

                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group d-none">
                                            <textarea name="description" class="form-control pro_description" rows="2"
                                                placeholder="{{ __('Description') }}"></textarea>

                                        </div>
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td>
                                        <div class="form-group col-md-6">
                                            <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                            <select name="tax_id[]" class="form-control select2 custom-select"
                                                id="choices-multiple1" multiple>
                                                @foreach ($tax as $tax)
                                                <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-end tax_val">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end subTotal">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Discount') }}
                                            ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end totalDiscount">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td><strong>{{ __('Tax') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                    </td>
                                    <td class="text-end totalTax">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td class="blue-text"><strong>{{ __('Total Amount') }}
                                            ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                    <td class="text-end totalAmount blue-text"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" onclick="location.href = '{{ route('invoice.index') }}';"
                class="btn btn-light">
            <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
        </div>
    </form>

</div>
@endsection