@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Create') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
@endsection
@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $themeColor = !empty($setting['color']) ? $setting['color'] : 'theme-3';

    // Map theme colors to actual color values
    $themeColors = [
        'theme-1' => ['primary' => '#0CAF60', 'secondary' => '#0A8F4D'],
        'theme-2' => ['primary' => '#6FD943', 'secondary' => '#5BC02A'],
        'theme-3' => ['primary' => '#584ED2', 'secondary' => '#4538B8'],
        'theme-4' => ['primary' => '#145388', 'secondary' => '#0F3F6A'],
        'theme-5' => ['primary' => '#3b82f6', 'secondary' => '#2563eb'],
    ];

    $primaryColor = $themeColors[$themeColor]['primary'] ?? '#3b82f6';
    $secondaryColor = $themeColors[$themeColor]['secondary'] ?? '#2563eb';

    // Convert hex to rgba for shadows
    function hexToRgba($hex, $alpha = 0.1)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r, $g, $b, $alpha)";
    }

    $primaryRgba = hexToRgba($primaryColor, 0.1);
@endphp
@push('script-page')
    <style>
        /* Radio Toggle Group Styling */
        .radio-toggle-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .radio-toggle-group input[type="radio"] {
            display: none;
        }

        .radio-toggle-group label {
            padding: 10px 25px;
            border: 2px solid #007bff;
            border-radius: 25px;
            /* color: #007bff; */
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
        }

        .radio-toggle-group input[type="radio"]:checked+label {
            background: linear-gradient(141.55deg, {{ $primaryColor }} 3.46%, {{ $primaryColor }} 99.86%), {{ $primaryColor }} !important;
            color: #fff;
        }

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
            background: {{ $primaryColor }};
            opacity: 0.8;
        }

        .option-input:checked {
            background: linear-gradient(141.55deg, {{ $primaryColor }} 3.46%, {{ $primaryColor }} 99.86%), {{ $primaryColor }} !important;
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

        .warehouse-quantities-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #e3e8ef;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }

        .warehouse-quantities-section h6 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .warehouse-quantities-section h6::before {
            content: '📦';
            font-size: 1.1rem;
        }

        .warehouse-quantities-section table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
            min-width: 600px;
        }

        .warehouse-quantities-section thead th {
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 8px 10px;
            text-align: left;
            border: none;
            white-space: nowrap;
        }

        .warehouse-quantities-section thead th:first-child {
            border-top-left-radius: 6px;
        }

        .warehouse-quantities-section thead th:last-child {
            border-top-right-radius: 6px;
        }

        .warehouse-quantities-section tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e9ecef;
        }

        .warehouse-quantities-section tbody tr:hover {
            background-color: #f8f9fa;
        }

        .warehouse-quantities-section tbody tr:last-child {
            border-bottom: none;
        }

        .warehouse-quantities-section tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .warehouse-quantities-section tbody td:nth-child(3) {
            white-space: normal;
            max-width: 200px;
        }

        .warehouse-quantities-section .tag {
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .warehouse-quantities-section .tag strong {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
        }

        .warehouse-quantities-section .warehouse-quantity {
            border: 1.5px solid #e9ecef;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 0.8rem;
            width: 70px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .warehouse-quantities-section .warehouse-quantity:focus {
            border-color: {{ $primaryColor }};
            background: #ffffff;
            box-shadow: 0 0 0 2px {{ $primaryRgba }};
            outline: none;
        }

        .warehouse-quantities-section .warehouse-quantity:hover {
            border-color: #ced4da;
            background: #ffffff;
        }

        .warehouse-quantities-section .badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
        }

        .warehouse-quantities-section .alert {
            border-radius: 8px;
            padding: 12px 15px;
            border: none;
            box-shadow: 0 1px 4px rgba(255, 193, 7, 0.15);
            font-size: 0.85rem;
        }

        .Autocustom-fields-container {
            width: 100% !important;
            max-width: 100%;
            overflow-x: auto;
        }

        .Autocustom-fields-container .warehouse-quantities-section {
            width: 100%;
            max-width: 70%;
            box-sizing: border-box;
        }

        .Autocustom-fields-container .warehouse-quantities-section .table-responsive {
            width: 100%;
            max-width: 100%;
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
            function initSelect2() {
                $('.item').select2({
                    width: '100%',
                    placeholder: 'Search Account...',
                    allowClear: true
                });
            }

            // Initialize on page load
            initSelect2();

            // Reinitialize when new row is added dynamically
            $(document).on('click', '[data-repeater-create]', function() {
                setTimeout(() => {
                    initSelect2();
                }, 100);
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
        let VATAmount = 0;
        let productId = 0;
        var vatType = 'add';
        var site_vat_calculation = '{{ $setting['site_vat_calculation'] }}';
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
                },
                // hide: function(deleteElement) {
                //     if (confirm('Are you sure you want to delete this element?')) {
                //         $(this).slideUp(deleteElement);
                //         $(this).remove();

                //         var inputs = $(".amount");
                //         var subTotal = 0;
                //         for (var i = 0; i < inputs.length; i++) {
                //             subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                //         }
                //         $('.subTotal').html(subTotal.toFixed(2));
                //         $('.totalAmount').html(subTotal.toFixed(2));
                //     }
                // },
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
            var subProductSection = $('.sub-product-section').eq(index);
            var subProductCheckboxes = $('.sub-product-checkboxes').eq(index);
            var autoCustomFieldsContainer = $('.Autocustom-fields-container').eq(index);
            var itemRow = $(this).closest('tr');
            // Get the correct index from the name attribute of the item select
            var itemNameAttr = $(this).attr('name'); // e.g., "items[0][item]"
            var rowIndex = itemNameAttr.match(/\d+/)[0]; // Extracts the index number from the name attribute
            console.log("Row Index:", itemRow);

            // Clear previous warehouse quantities and sub-product results
            $('.warehouse-quantities-section').remove();
            subProductCheckboxes.empty();
            subProductSection.hide();

            $.ajax({
                url: "{{ route('get-item-category', ['productId' => ':productId']) }}".replace(
                    ':productId',
                    iteams_id),
                type: 'GET',
                success: function(data) {
                    if (data.status === 'success') {
                        var categoryType = data.category_type;
                        $('.sub-product-section').attr('data-category-type', categoryType);
                        subProductSection.find('#autoSelect').prop('checked', true);
                        subProductSection.find('#autoSelect').parent().show();

                        toggleSubProductSection(false);
                        autoCustomFieldsContainer.show();
                        subProductSection.find('#manualSelect').parent().show();

                    } else {
                        console.log('Error: ' + data.message);
                    }
                },
                error: function() {
                    console.log('Error fetching item category.');
                }
            });
            if (iteams_id) {
                // $.ajax({
                //     url: '/get-custom-fields-inv',
                //     type: 'GET',
                //     data: {
                //         product_id: iteams_id
                //     },
                //     success: function(response) {
                //         var customFieldsHtml = '';

                //         // Start row
                //         customFieldsHtml += `<div class="row" style="padding-top: 20px;">`;
                //         console.log(response);

                //         // Loop through the custom fields and generate the corresponding input elements
                //         response.customFields.forEach(function(field) {
                //             console.log(
                //                 `Creating field for item index ${rowIndex}: ${field.name}`);

                //             // Create a column for each field
                //             customFieldsHtml +=
                //                 `<div class="col-md-4">`; // Adjust the col-md-* to change the width

                //             customFieldsHtml +=
                //                 `<div class="form-group" style="margin-bottom: 10px;">`;

                //             if (field.type === 'text') {
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <input type="text" class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]" value=""/>
            // `;
                //             } else if (field.type === 'email') {
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <input type="email" class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]" value=""/>
            // `;
                //             } else if (field.type === 'number') {
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <input type="number" class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]" value=""/>
            // `;
                //             } else if (field.type === 'date') {
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <input type="date" class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]" value=""/>
            // `;
                //             } else if (field.type === 'textarea') {
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <textarea class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]"></textarea>
            // `;
                //             } else if (field.type === 'dropdown') {
                //                 var options = JSON.parse(field.options || '[]');
                //                 customFieldsHtml += `
            //     <label>${field.name}</label>
            //     <select class="form-control" name="items[${rowIndex}][custom_fields][${field.id}]">
            // `;
                //                 options.forEach(function(option) {
                //                     customFieldsHtml +=
                //                         `<option value="${option}">${option}</option>`;
                //                 });
                //                 customFieldsHtml += `</select>`;
                //             }

                //             customFieldsHtml += `</div>`; // End form-group
                //             customFieldsHtml += `</div>`; // End col-md-4
                //         });

                //         customFieldsHtml += `</div>`; // End row

                //         // Append the custom fields HTML to the custom-fields-container
                //         autoCustomFieldsContainer.html(customFieldsHtml);
                //         console.log(customFieldsHtml);
                //     },
                //     error: function(xhr, status, error) {
                //         console.error('Error fetching custom fields:', error);
                //     }
                // });

                // First, get warehouse quantities for this product
                $.ajax({
                    url: "{{ route('get-sub-product-quantities-by-warehouse', ['productId' => ':productId']) }}"
                        .replace(
                            ':productId', iteams_id),
                    type: 'GET',
                    success: function(warehouseData) {
                        // Check if response is an error or has no data
                        if (warehouseData.status === 'error' || !warehouseData.data || warehouseData
                            .data.length === 0) {
                            // Show no stock message
                            var noStockHtml = '<div class="warehouse-quantities-section">';
                            noStockHtml +=
                                '<div class="alert alert-warning d-flex align-items-center" role="alert" style="border-radius: 8px; padding: 12px 15px;">';
                            noStockHtml +=
                                '<i class="ti ti-alert-triangle me-2" style="font-size: 1.2rem;"></i>';
                            noStockHtml += '<div>';
                            noStockHtml +=
                                '<strong style="font-size: 0.85rem;">No Stock Available</strong>';
                            var errorMsg = warehouseData.message ||
                                'This product is not available in any warehouse.';
                            noStockHtml += '<p class="mb-0 mt-1" style="font-size: 0.8rem;">' +
                                errorMsg + '</p>';
                            noStockHtml += '</div>';
                            noStockHtml += '</div>';
                            noStockHtml += '</div>';
                            autoCustomFieldsContainer.html(noStockHtml);
                            return;
                        }

                        // Replace the warehouse quantities section with this updated version
                        if (warehouseData.status === 'success' && warehouseData.data.length > 0) {
                            let warehouseHtml = '<div class="warehouse-quantities-section">';
                            warehouseHtml += '<h6>Available Quantities by Warehouse Location</h6>';
                            warehouseHtml +=
                                '<div class="table-responsive" style="max-width: 100%; overflow-x: auto;">';
                            warehouseHtml += '<table class="table mb-0">';
                            warehouseHtml += '<thead>';
                            warehouseHtml += '<tr>';
                            warehouseHtml += '<th>Warehouse</th>';
                            warehouseHtml += '<th>Available</th>';
                            warehouseHtml += '<th>Custom Fields</th>';
                            warehouseHtml += '<th>Quantity to Reserve</th>';
                            warehouseHtml += '</tr>';
                            warehouseHtml += '</thead>';
                            warehouseHtml += '<tbody>';

                            warehouseData.data.forEach(function(item, index) {
                                // Build combination tags
                                let combinationTags = '';
                                if (item.combination && Object.keys(item.combination).length >
                                    0) {
                                    Object.entries(item.combination).forEach(([key, value]) => {
                                        if (value && value !== 'N/A' && value !==
                                            'n/a') {
                                            combinationTags +=
                                                `<span class="tag"><strong>${key}:</strong> ${value}</span>`;
                                        }
                                    });
                                }
                                if (!combinationTags) {
                                    combinationTags =
                                        '<span class="text-muted" style="font-size: 0.8rem;">-</span>';
                                }

                                warehouseHtml += `
                                    <tr>
                                        <td>
                                            <span>📍 ${item.country || 'Warehouse'}</span>
                                        </td>
                                        <td>
                                            <span class="badge">${item.quantity}</span>
                                        </td>
                                        <td>
                                            ${combinationTags}
                                        </td>
                                        <td>
                                            <input type="number" 
                                                class="form-control warehouse-quantity" 
                                                name="items[${rowIndex}][warehouse_quantities][${item.warehouse_id}][quantity]"
                                                data-warehouse-id="${item.warehouse_id}"
                                                data-combination='${JSON.stringify(item.combination || {})}'
                                                max="${item.quantity}"
                                                min="0"
                                                value="0"
                                                placeholder="0">
                                            <input type="hidden" 
                                                name="items[${rowIndex}][warehouse_quantities][${item.warehouse_id}][combination]"
                                                value='${JSON.stringify(item.combination || {})}'>
                                            <input type="hidden" 
                                                name="items[${rowIndex}][warehouse_quantities][${item.warehouse_id}][warehouse_id]"
                                                value="${item.warehouse_id}">
                                        </td>
                                    </tr>
                                `;
                            });

                            warehouseHtml += '</tbody>';
                            warehouseHtml += '</table>';
                            warehouseHtml += '</div>';
                            warehouseHtml += '</div>';

                            autoCustomFieldsContainer.html(warehouseHtml);
                        } else {
                            // Show no stock message with better styling
                            var noStockHtml = '<div class="warehouse-quantities-section">';
                            noStockHtml +=
                                '<div class="alert alert-warning d-flex align-items-center" role="alert" style="border-radius: 8px; padding: 12px 15px;">';
                            noStockHtml +=
                                '<i class="ti ti-alert-triangle me-2" style="font-size: 1.2rem;"></i>';
                            noStockHtml += '<div>';
                            noStockHtml +=
                                '<strong style="font-size: 0.85rem;">No Stock Available</strong>';
                            noStockHtml +=
                                '<p class="mb-0 mt-1" style="font-size: 0.8rem;">This product is not available in any warehouse.</p>';
                            noStockHtml += '</div>';
                            noStockHtml += '</div>';
                            noStockHtml += '</div>';
                            autoCustomFieldsContainer.html(noStockHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show no stock message on AJAX error
                        var noStockHtml = '<div class="warehouse-quantities-section">';
                        noStockHtml +=
                            '<div class="alert alert-warning d-flex align-items-center" role="alert" style="border-radius: 8px; padding: 12px 15px;">';
                        noStockHtml +=
                            '<i class="ti ti-alert-triangle me-2" style="font-size: 1.2rem;"></i>';
                        noStockHtml += '<div>';
                        noStockHtml +=
                            '<strong style="font-size: 0.85rem;">No Stock Available</strong>';
                        var errorMsg = 'This product is not available in any warehouse.';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            // If response is not JSON, use default message
                        }
                        noStockHtml += '<p class="mb-0 mt-1" style="font-size: 0.8rem;">' + errorMsg +
                            '</p>';
                        noStockHtml += '</div>';
                        noStockHtml += '</div>';
                        noStockHtml += '</div>';
                        autoCustomFieldsContainer.html(noStockHtml);
                    }
                });

                // Now get the regular sub-products
                $.ajax({
                    url: "{{ route('get-sub-products', ['productId' => ':productId']) }}"
                        .replace(
                            ':productId', iteams_id),
                    type: 'GET',
                    success: function(data) {
                        var subProducts = data.subProducts;
                        var customFieldsData = data.customFieldsData;
                        var tableHtml = '<table class="sub-product-table">';
                        tableHtml += '<thead>';
                        tableHtml += '<tr>';
                        tableHtml +=
                            '<th><input type="checkbox" class="select-all"></th>'; // Select All Checkbox
                        tableHtml +=
                            '<th>ID<br><input type="text" id="filter-id" class="filter-input"></th>';
                        tableHtml +=
                            '<th>Name<br><input type="text" id="filter-name" class="filter-input"></th>';
                        tableHtml +=
                            '<th>Number<br><input type="text" id="filter-number" class="filter-input"></th>';
                        tableHtml +=
                            '<th>Custom Fields<br><input type="text" id="filter-custom-fields" class="filter-input"></th>';
                        tableHtml += '</tr>';
                        tableHtml += '</thead>';
                        tableHtml += '<tbody>';

                        subProducts.forEach(function(subProduct, index) {
                            var rowId = 'subProductRow_' + index;
                            tableHtml += '<tr id="' + rowId + '">';
                            tableHtml +=
                                '<td><input type="checkbox" class="sub-product-checkbox" name="subProducts[]" value="' +
                                subProduct.id + '"></td>';
                            tableHtml += '<td>' + subProduct.id + '</td>';
                            tableHtml += '<td>' + subProduct.product_service
                                .name + '</td>';
                            tableHtml += '<td>' + subProduct.product_no +
                                '</td>';

                            // Display custom fields for this sub-product
                            var customFieldsHtml =
                                '<div class="custom-fields-container">';
                            var fields = customFieldsData[subProduct.id] || {};

                            for (var fieldName in fields) {
                                customFieldsHtml +=
                                    '<div class="custom-field"><strong>' +
                                    fieldName + ':</strong> ' + fields[
                                        fieldName] + '</div>';
                            }

                            customFieldsHtml += '</div>';
                            tableHtml += '<td>' + customFieldsHtml +
                                '</td>';
                            tableHtml += '</tr>';
                        });

                        tableHtml += '</tbody></table>';

                        subProductCheckboxes.html(tableHtml);
                        subProductSection.show();

                        // **🔄 Fix "Select All" Checkbox**
                        $('.select-all').on('change', function() {
                            var table = $(this).closest(
                                '.sub-product-table');
                            table.find('.sub-product-checkbox').prop(
                                'checked', $(this).prop(
                                    'checked'));
                        });

                        $('.sub-product-checkbox').on('change', function() {
                            var table = $(this).closest(
                                '.sub-product-table');
                            var allChecked = table.find(
                                    '.sub-product-checkbox').length ===
                                table.find('.sub-product-checkbox:checked')
                                .length;
                            table.find('.select-all').prop('checked',
                                allChecked);
                        });

                        $(document).on('keyup', '.filter-input', function() {
                            var table = $(this).closest('.sub-product-section').find(
                                '.sub-product-table');

                            function tokenize(val) {
                                return (val || '')
                                    .toLowerCase()
                                    .split(' ')
                                    .map(function(s) {
                                        return s.trim();
                                    })
                                    .filter(function(s) {
                                        return s.length > 0;
                                    });
                            }

                            function matchesAny(text, tokens) {
                                if (tokens.length === 0) return true;
                                for (var i = 0; i < tokens.length; i++) {
                                    if (text.indexOf(tokens[i]) > -1) return true;
                                }
                                return false;
                            }

                            var idTokens = tokenize(table.find('#filter-id').val());
                            var nameTokens = tokenize(table.find('#filter-name').val());
                            var numberTokens = tokenize(table.find('#filter-number').val());
                            var customFieldTokens = tokenize(table.find('#filter-custom-fields')
                                .val());

                            table.find('tbody tr').each(function() {
                                var idText = $(this).find('td:nth-child(2)').text()
                                    .toLowerCase();
                                var nameText = $(this).find('td:nth-child(3)').text()
                                    .toLowerCase();
                                var numberText = $(this).find('td:nth-child(4)').text()
                                    .toLowerCase();

                                var customFieldsText = '';
                                $(this).find('td:nth-child(5) .custom-fields-container')
                                    .each(function() {
                                        $(this).find('.custom-field').each(
                                            function() {
                                                customFieldsText += $(this)
                                                    .text().toLowerCase() + ' ';
                                            });
                                    });

                                var show =
                                    matchesAny(idText, idTokens) &&
                                    matchesAny(nameText, nameTokens) &&
                                    matchesAny(numberText, numberTokens) &&
                                    matchesAny(customFieldsText, customFieldTokens);

                                $(this).toggle(show);
                            });
                        });

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
                    console.log('items: ', item)
                    console.log(el.parent().parent().find('.quantity'))
                    $(el.parent().parent().find('.quantity')).val(1);
                    // console.log("Extracted Type:", type);
                    let currentUrl = window.location.pathname;

                    // Extract type from URL (assuming "/invoice/{type}")
                    let invoice_type = currentUrl.split('/')[3]; // Assuming '/invoice/type' format

                    let issueDate = new Date(document.getElementById("issue_date").value);
                    let dueDate = new Date(document.getElementById("due_date").value);
                    let dayes = (dueDate - issueDate) / (1000 * 60 * 60 * 24);

                    var price = parseFloat(item.product.sale_price);
                    el.closest('tr').find('.price').val(price);

                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    var old_tax_price = $(".totalTax").val();
                    totalItemTaxRate = parseInt(TotalTax);
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) * parseFloat((price * 1));
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);
                    var amount = invoice_type === "rent" ?
                        price * (dayes + 1) :
                        price * 1; // Quantity is 1 by default
                    el.closest('tr').find('.amount').html(amount.toFixed(2));

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    var Qty = $('.quantity');

                    for (var j = 0; j < priceInput.length; j++) {
                        if (invoice_type == "rent") {
                            totalItemPrice += parseFloat(priceInput[j].value) * Qty[j].value * (dayes +
                                1)
                        } else {
                            totalItemPrice += parseFloat(priceInput[j].value) * Qty[j].value
                        }

                    }
                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                        var rowQty = parseFloat(Qty[k].value) || 0;
                        var rowDiscount = parseFloat(itemDiscountPriceInput[k].value) || 0;
                        totalItemDiscountPrice += rowDiscount * rowQty;
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
        $(document).on('change', '.warehouse-quantity', function() {
            var row = $(this).closest('[data-repeater-item]');
            var quantityInput = row.find('.quantity');

            // Calculate total quantity from all warehouse inputs
            var totalQuantity = 0;
            row.find('.warehouse-quantity').each(function() {
                totalQuantity += parseInt($(this).val()) || 0;
            });

            // Update the main quantity field
            quantityInput.val(totalQuantity);
            quantityInput.trigger('keyup');

            // Update the hidden sub-products field with warehouse data
            var warehouseData = [];
            row.find('.warehouse-quantity').each(function() {
                var quantity = parseInt($(this).val()) || 0;
                if (quantity > 0) {
                    warehouseData.push({
                        warehouse_id: $(this).data('warehouse-id'),
                        combination: $(this).data('combination'),
                        quantity: quantity
                    });
                }
            });

            row.find('.sub-products').val(JSON.stringify(warehouseData));
        });

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;
            // Extract type from URL (assuming "/invoice/{type}")
            let currentUrl = window.location.pathname;
            let invoice_type = currentUrl.split('/')[3]; // Assuming '/invoice/type' format

            let issueDate = new Date(document.getElementById("issue_date").value);
            let dueDate = new Date(document.getElementById("due_date").value);
            let dayes = (dueDate - issueDate) / (1000 * 60 * 60 * 24);

            ///////////////////////////
            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = 0
            if (invoice_type == "rent") {
                var totalItemPrice = (quantity * price * (dayes + 1)) - (discount * quantity);
            } else {
                var totalItemPrice = (quantity * price) - (discount * quantity);
            }
            // var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = parseInt(TotalTax); //$(el.find('.itemTaxRate')).val();
            // totalItemTaxRate = parseInt(totalItemTaxRate)  + parseInt(TotalTax);
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            // $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(amount));
            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                if (invoice_type == "rent") {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value) * (
                        dayes + 1));
                } else {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
                }
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }
            var sumAmount = totalItemPrice;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            var TotalAmount = 0;
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            if (vatType === 'add') {
                VATAmount = (subTotal - existingDiscount) * (totalItemTaxRate / 100);
                TotalAmount = (subTotal - existingDiscount) + ((subTotal - existingDiscount) * (
                    parseInt(TotalTax) / 100));
            } else if (vatType === 'subtract') {
                VATAmount = (subTotal * (parseInt(TotalTax) / 100)) / (1 + (parseInt(TotalTax) / 100));
                TotalAmount = (subTotal - existingDiscount) - VATAmount;
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html((VATAmount).toFixed(2));
            var totalAmountValue = $('.totalAmount').text();
            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));

            $('.totalAmount').html(parseFloat(TotalAmount).toFixed(2));

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();

            // Extract type from URL (assuming "/invoice/{type}")
            let currentUrl = window.location.pathname;
            let invoice_type = currentUrl.split('/')[3]; // Assuming '/invoice/type' format

            let issueDate = new Date(document.getElementById("issue_date").value);
            let dueDate = new Date(document.getElementById("due_date").value);
            let dayes = (dueDate - issueDate) / (1000 * 60 * 60 * 24);

            ///////////////////////////

            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = 0;
            if (invoice_type == "rent") {
                var totalItemPrice = (quantity * price * (dayes + 1)) - (discount * quantity);
            } else {
                var totalItemPrice = (quantity * price) - (discount * quantity);
            }


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
                if (invoice_type == "rent") {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value) * (
                        dayes + 1));
                } else {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
                }
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }
            var TotalAmount = 0;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            if (vatType === 'add') {
                VATAmount = (totalItemPrice - existingDiscount) * (totalItemTaxRate / 100);
                TotalAmount = (subTotal - existingDiscount) + ((subTotal - existingDiscount) * (
                    totalItemTaxRate / 100));
            } else if (vatType === 'subtract') {
                VATAmount = (totalItemPrice * (parseInt(TotalTax) / 100)) / (1 + (parseInt(TotalTax) / 100));
                TotalAmount = (subTotal - existingDiscount) - VATAmount;
            }
            $('.subTotal').html(totalItemPrice.toFixed(2));
            // $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalTax').html((VATAmount).toFixed(2));
            $('.totalAmount').html((TotalAmount).toFixed(2));


        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();

            // Extract type from URL (assuming "/invoice/{type}")
            let currentUrl = window.location.pathname;
            let invoice_type = currentUrl.split('/')[3]; // Assuming '/invoice/type' format

            let issueDate = new Date(document.getElementById("issue_date").value);
            let dueDate = new Date(document.getElementById("due_date").value);
            let dayes = (dueDate - issueDate) / (1000 * 60 * 60 * 24);

            ///////////////////////////
            if (invoice_type == "rent") {
                var totalItemPrice = (quantity * price * (dayes + 1)) - (discount * quantity);
            } else {
                var totalItemPrice = (quantity * price) - (discount * quantity);
            }



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
                if (invoice_type == "rent") {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value) * (
                        dayes + 1));
                } else {
                    totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
                }

            }
            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                var rowQty = parseFloat(inputs_quantity[k].value) || 0;
                var rowDiscount = parseFloat(itemDiscountPriceInput[k].value) || 0;
                totalItemDiscountPrice += rowDiscount * rowQty;
            }

            var TotalAmount = 0;
            $('.subTotal').html(totalItemPrice.toFixed(2));
            if (vatType === 'add') {
                VATAmount = (totalItemPrice - totalItemDiscountPrice) * parseFloat((totalItemTaxRate / 100));
                TotalAmount = parseFloat(subTotal) + ((totalItemPrice - totalItemDiscountPrice) * parseFloat((
                    totalItemTaxRate / 100)));
            } else if (vatType === 'subtract') {
                VATAmount = (subTotal * (parseInt(TotalTax) / 100)) / (1 + (parseInt(TotalTax) / 100));
                TotalAmount = parseFloat(subTotal) + (totalItemPrice - totalItemDiscountPrice) - VATAmount;
            }
            $('.totalTax').html((VATAmount)
                .toFixed(2));

            $('.totalAmount').html((TotalAmount).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })
        // $(document).on('change', '.auto-select, .manual-select', function() {
        //     let $container = $(this).closest('.sub-product-section');
        //     let isManual = $(this).hasClass('manual-select');

        //     // Toggle only inside this item's container
        //     $container.find('.subProductSection').toggle(isManual);
        //     $container.find('.Autocustom-fields-container').toggle(!isManual);
        // });

        function toggleSubProductSection(show) {
            // var subProductSection = document.getElementById('subProductSection');
            // subProductSection.style.display = show ? 'block' : 'none';
            var radio = $(event.target);
            var repeaterItem = radio.closest('[data-repeater-item]');
            var subProductSection = repeaterItem.find('.subProductSection');
            var quantityInput = repeaterItem.find('.quantity');
            var autoCustomFieldsContainer = repeaterItem.find('.Autocustom-fields-container');
            subProductSection.css('display', show ? 'block' : 'none');

            if (!show) {
                quantityInput.removeAttr('disabled');
                quantityInput.val(1);
                autoCustomFieldsContainer.css('display', 'block')
            } else {
                quantityInput.attr('disabled', 'disabled');
                quantityInput.val('');
                autoCustomFieldsContainer.css('display', 'none');
            }
        }
        $(document).on('change', '.sub-product-section .sub-product-table input[type="checkbox"]', function() {
            // Find the parent sub-product section
            var subProductSection = $(this).closest('.sub-product-section');

            // Find the parent row of the sub-product section
            var row = subProductSection.closest('tr');

            // Calculate the total quantity based on the selected checkboxes in the current row
            var totalQuantity = subProductSection.find('.sub-product-table input[type="checkbox"]:checked')
                .not('.select-all')
                .length;


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
            console.log(totalQuantity);
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
                        vatType = taxData[j].type || 'add';
                    }
                }
            }

            var totalAmountValue = $('.totalAmount').text();

            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            var existingDiscount = parseFloat($('.totalDiscount').text()) || 0;
            var TotalAmount = 0;
            var newSubTotal = existingSubTotal;
            if (vatType === 'add') {
                VATAmount = TotalTax;
                TotalAmount = (existingSubTotal - existingDiscount) + (existingSubTotal - existingDiscount) * (
                    parseInt(TotalTax) / 100);
            } else if (vatType === 'subtract') {
                // Calculate VAT to remove from a tax-inclusive subtotal, then subtract it from Sub Total and include in Total
                VATAmount = ((existingSubTotal - existingDiscount) * (parseInt(TotalTax) / 100)) / (1 + (parseInt(
                    TotalTax) / 100));
                newSubTotal = (existingSubTotal - VATAmount);
                TotalAmount = ((existingSubTotal - existingDiscount) - VATAmount) + VATAmount;
            }


            // $('.totalAmount').text(totalAmount.toFixed(2));
            $('.totalAmount').html(TotalAmount.toFixed(2))
            if (vatType === 'subtract') {
                $('.subTotal').html(newSubTotal.toFixed(2));
            }
            $('.tax_val').text(parseInt(TotalTax));
            $('.totalTax').html((parseFloat(VATAmount) || 0).toFixed(2));

        });
    </script>
    <script>
        $(document).ready(function() {
            const exchangeRateDiv = document.getElementById('exchange_rate_div');

            // Set default currency symbol on page load
            $('.currency-symbol').text('{{ \Auth::user()->currencySymbol() }}');

            $('#currency_id').change(function() {
                var currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}'; // Default

                if (currencyId === '') {
                    // Default selected (empty value)
                    $('.currency-symbol').text(symbol);
                    $('#exchange_rate_div').hide();
                    $('#exchange_rate').val('');
                } else {
                    // Fetch symbol and exchange rate from backend
                    fetch('/get-exchange-rate/' + currencyId)
                        .then(response => response.json())
                        .then(data => {
                            $('.currency-symbol').text(data.symbol || data.code || symbol);
                            $('#exchange_rate_div').show();
                            $('#exchange_rate').val(data.exchange_rate);
                        })
                        .catch(() => {
                            $('.currency-symbol').text(symbol);
                        });
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function updateTotalAmount() {
                let total = 0;
                document.querySelectorAll('.accountAmountInput').forEach(input => {
                    let amount = parseFloat(input.value) || 0;
                    total += amount;
                });
                document.querySelectorAll('.totalAmountAccount').forEach(el => el.textContent = total.toFixed(2));
                document.querySelector('input[name="totalAmountAccount"]').value = total.toFixed(2);
            }

            // Event listener for amount input changes
            document.addEventListener('input', function(event) {
                if (event.target.classList.contains('accountAmountInput')) {
                    updateTotalAmount();
                }
            });

            // Handle new row addition
            document.querySelector('[data-repeater-create]').addEventListener('click', function() {
                setTimeout(() => {
                    document.querySelectorAll('.accountAmountInput').forEach(input => {
                        input.addEventListener('input', updateTotalAmount);
                    });
                }, 500); // Delay to ensure the new row is fully added
            });

            // Initial calculation in case of pre-filled data
            updateTotalAmount();
        });
    </script>
@endpush
@section('content')
    <div class="row">
        <div class="container mt-4">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ url('invoice') }}" class="w-100" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Left side: Customer and Driver -->
                            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                <!-- Customer -->
                                <div class="form-group" id="customer-box">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" id="customer" class="form-control select2"
                                        data-url="{{ route('invoice.customer') }}" required>
                                        @foreach ($customers as $id => $customer)
                                            <option value="{{ $id }}">{{ $customer }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="customer_detail" class="d-none"></div>
                                @if (request()->is('*rent*'))
                                    <!-- Driver -->
                                    <div class="form-group mt-3" id="driver-box">
                                        <label for="driver_id" class="form-label">{{ __('Driver') }}</label>
                                        <select name="driver_id" id="driver" class="form-control select2"
                                            data-url="{{ route('invoice.customer') }}" required>
                                            @foreach ($customers as $id => $customer)
                                                <option value="{{ $id }}">{{ $customer }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="driver_detail" class="d-none"></div>
                                @endif
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                            <div class="form-icon-user">
                                                <input type="date" name="issue_date" id="issue_date" class="form-control"
                                                    required>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date" class="form-label">{{ __('Due Date') }}</label>

                                            <div class="form-icon-user">
                                                <input type="date" name="due_date" id="due_date" class="form-control"
                                                    required>


                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="invoice_number"
                                                class="form-label">{{ __('Invoice Number') }}</label>

                                            <div class="form-icon-user">
                                                <input type="text" class="form-control" name="invoice_number"
                                                    value="{{ $invoice_number }}" readonly>
                                                <input type="hidden" class="form-control" name="invoice_numberNo"
                                                    value="{{ $invoice_numberNo }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                            <select name="category_id" class="form-control select2" required>
                                                @foreach ($category as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ref_number" class="form-label">{{ __('Ref Number') }}</label>
                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" name="ref_number" id="ref_number"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salesman_id" class="form-label">{{ __('SalesMan') }}</label>
                                            <select name="salesman_id" id="salesman_id" class="form-control select2">
                                                @foreach ($users as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            <select name="currency_id" id="currency_id" class="form-control select2"
                                                data-placeholder="{{ __('Select Currency') }}">
                                                <option value=""></option>
                                                @foreach ($currency as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="exchange_rate_div" style="display: none;">
                                        <div class="form-group">
                                            <label for="exchange_rate"
                                                class="form-label">{{ __('Exchange Rate') }}</label>
                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" name="exchange_rate" id="exchange_rate"
                                                    class="form-control">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_account_id" class="form-label">{{ __('Bank') }}</label>
                                            <select name="bank_account_id" id="bank_account_id"
                                                class="form-control select2">
                                                @foreach ($accounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="discount_account_id"
                                                class="form-label">{{ __('Discount Account') }}</label>
                                            <select name="discount_account_id" id="discount_account_id"
                                                class="form-control select2">
                                                @foreach ($chartAccounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="document">Document:</label>
                                        <input type="file" class="form-control" id="documents" name="documents[]"
                                            multiple>
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
                            <div
                                class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary"
                                        data-bs-toggle="modal" data-target="#add-bank">
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
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Price') }} </th>
                                        <th>{{ __('Discount') }}</th>
                                        <th class="text-end">{{ __('Amount') }} <br><small
                                                class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>

                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>

                                        <td width="25%">
                                            <select name="item" class="form-group form-control select2 item"
                                                data-url="{{ route('invoice.product') }}" required>
                                                @foreach ($product_services as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>



                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="number" name="quantity" class="form-control quantity"
                                                    required="required" placeholder="{{ __('Qty') }}" disabled>

                                                <span class="unit input-group-text bg-transparent"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="number" name="price" class="form-control price"
                                                    required="required" placeholder="{{ __('Price') }}"
                                                    step="0.01">

                                                <span class="input-group-text bg-transparent">
                                                    <span
                                                        class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="number" name="discount" class="form-control discount"
                                                    required="required" placeholder="{{ __('Discount') }}"
                                                    step="0.01">

                                                <span class="input-group-text bg-transparent">
                                                    <span
                                                        class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                                </span>
                                            </div>
                                        </td>

                                        <td class="d-none">
                                            <div class="form-group">
                                                <div class="input-group colorpickerinput">
                                                    <div class="taxes"></div>
                                                    <input type="hidden" name="tax"
                                                        class="form-control tax text-dark" value="">
                                                    <input type="hidden" name="itemTaxPrice"
                                                        class="form-control itemTaxPrice" value="">
                                                    <input type="hidden" name="itemTaxRate"
                                                        class="form-control itemTaxRate" value="">
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
                                        <td colspan="6">
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
                                                        class="sub-products" value="">
                                                </div>
                                                <div class="Autocustom-fields-container" style="width: 50%;"></div>
                                            </div>
                                        </td>
                                        <td class="d-none">
                                            <div class="form-group">
                                                <textarea name="description" class="form-control pro_description" rows="2"
                                                    placeholder="{{ __('Description') }}"></textarea>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>
                                            <div class="form-group col-md-10">
                                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                                <select name="tax_id[]" id="choices-multiple1"
                                                    class="form-control select2 custom-select" multiple>
                                                    @foreach ($fullTax as $value)
                                                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td class="text-end tax_val">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Sub Total') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Discount') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalDiscount">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Tax') }} (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalTax">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>
                                        <td class="text-end totalAmount blue-text"></td>

                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Expenses') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div
                                class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary"
                                        data-bs-toggle="modal" data-target="#add-bank">
                                        <i class="ti ti-plus"></i> {{ __('Add Item') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table mb-0" data-repeater-list="itemsAccount" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th width="20%">{{ __('Items') }}</th>
                                        <th>{{ __('') }}</th>
                                        <th>{{ __('') }} </th>
                                        <th>{{ __('') }}</th>

                                        <th class="text-end">{{ __('Amount') }}
                                            <br><small class="text-danger font-bold">{{ __('after discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>
                                        <td class="form-group">
                                            <select name="chart_account_id" class="form-control  select2">
                                                @foreach ($chartAccounts as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>

                                        </td>
                                        <td class="form-group">
                                            <div class="input-group ">
                                                <input type="text" name="amount"
                                                    class="form-control accountAmountInput"
                                                    placeholder="{{ __('Amount') }}">
                                                <span
                                                    class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                        </td>
                                        <td colspan="2" class="form-group">
                                            <textarea name="description" class="form-control pro_description" rows="1"
                                                placeholder="{{ __('Description') }}"></textarea>
                                        </td>


                                    </tr>

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                (<span
                                                    class="currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>)</strong>
                                        </td>

                                        <td class="blue-text text-end totalAmountAccount">0.00</td>
                                        <input type="hidden" name="totalAmountAccount" class="form-control totalAmount">

                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('invoice.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
            </div>
        </form>

    </div>
@endsection
