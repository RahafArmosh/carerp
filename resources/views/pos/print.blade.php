@extends('layouts.admin')
@section('page-title')
    {{ __('POS Barcode Print') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pos.barcode') }}">{{ __('POS Product Barcode') }}</a></li>
    <li class="breadcrumb-item">{{ __('POS Barcode Print') }}</li>
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
@endpush

@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select').select2();
            
            // Load categories when warehouse is selected
            var warehouseId = $('#warehouse_id').val();
            if (warehouseId) {
                loadCategories(warehouseId);
            }
            
            // Auto-focus barcode search input on page load
            setTimeout(function() {
                $('#barcode_search_direct').focus();
            }, 300);
        });

        // Step 1: Load categories when warehouse changes
        $(document).on('change', '#warehouse_id', function() {
            var warehouseId = $(this).val();
            resetFilters();
            if (warehouseId && warehouseId !== '') {
                loadCategories(warehouseId);
            }
        });

        // Step 2: Load brands when category changes
        $(document).on('change', '#category_id', function() {
            var categoryId = $(this).val();
            var warehouseId = $('#warehouse_id').val();
            
            // Reset downstream selects
            $('#brand_id').val('').trigger('change').prop('disabled', true);
            $('#product_id').val('').trigger('change').prop('disabled', true);
            $('#sub_product_id').val('').trigger('change').prop('disabled', true);
            $('#custom_fields_container').hide();
            $('#product_info_container').hide();
            
            if (categoryId && warehouseId) {
                loadBrands(warehouseId, categoryId);
            } else {
                $('#brand_id').empty().append('<option value="">{{ __('Please select a category first') }}</option>').prop('disabled', true);
            }
        });

        // Step 3: Load products when brand changes
        $(document).on('change', '#brand_id', function() {
            var brandId = $(this).val();
            var categoryId = $('#category_id').val();
            var warehouseId = $('#warehouse_id').val();
            
            // Reset downstream selects
            $('#product_id').val('').trigger('change').prop('disabled', true);
            $('#sub_product_id').val('').trigger('change').prop('disabled', true);
            $('#custom_fields_container').hide();
            $('#product_info_container').hide();
            
            if (categoryId && brandId && warehouseId) {
                loadProducts(warehouseId, categoryId, brandId);
            } else {
                $('#product_id').empty().append('<option value="">{{ __('Please select a brand first') }}</option>').prop('disabled', true);
            }
        });

        // Step 4: Load sub-products when product changes
        $(document).on('change', '#product_id', function() {
            var productId = $(this).val();
            var warehouseId = $('#warehouse_id').val();
            
            // Reset sub-product select
            $('#sub_product_id').val('').trigger('change').prop('disabled', true);
            $('#custom_fields_container').hide();
            $('#product_info_container').hide();
            
            if (productId && warehouseId) {
                loadSubProducts(warehouseId, productId);
            }
        });

        // Handle sub-product selection
        $(document).on('change', '#sub_product_id', function() {
            var productNo = $(this).val();
            var selectedValue = $(this).val();
            
            // Get product data from stored map
            var productData = null;
            if (selectedValue && window.productDataMap && window.productDataMap[selectedValue]) {
                productData = window.productDataMap[selectedValue];
            }
            
            if (productNo) {
                getCustomFields(productNo);
                
                // Display product information if data is available
                if (productData) {
                    displayProductInfo(productData);
                }
            } else {
                $('#custom_fields_container').hide();
                $('#custom_fields_checkboxes').empty();
                $('#product_info_container').hide();
            }
        });

        function resetFilters() {
            $('#category_id').val('').trigger('change').prop('disabled', true);
            $('#brand_id').val('').trigger('change').prop('disabled', true);
            $('#product_id').val('').trigger('change').prop('disabled', true);
            $('#sub_product_id').val('').trigger('change').prop('disabled', true);
            $('#custom_fields_container').hide();
            $('#product_info_container').hide();
        }

        function loadCategories(warehouseId, callback) {
            $('#category_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-categories') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#category_id').empty().prop('disabled', false);
                    $('#category_id').append('<option value="">{{ __('Select Category') }}</option>');
                    
                    if (data.categories && data.categories.length > 0) {
                        $.each(data.categories, function(index, category) {
                            $('#category_id').append(`<option value="${category.id}">${category.name}</option>`);
                        });
                    } else {
                        $('#category_id').append('<option value="">{{ __('No categories found') }}</option>');
                    }
                    $('#category_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#category_id').empty().prop('disabled', false);
                    $('#category_id').append('<option value="">{{ __('Error loading categories') }}</option>').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }

        function loadBrands(warehouseId, categoryId, callback) {
            $('#brand_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-brands') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    category_id: categoryId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#brand_id').empty().prop('disabled', false);
                    $('#brand_id').append('<option value="">{{ __('Select Brand') }}</option>');
                    
                    if (data.brands && data.brands.length > 0) {
                        $.each(data.brands, function(index, brand) {
                            $('#brand_id').append(`<option value="${brand.id}">${brand.name}</option>`);
                        });
                    } else {
                        $('#brand_id').append('<option value="">{{ __('No brands found for this category') }}</option>');
                    }
                    $('#brand_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#brand_id').empty().prop('disabled', false);
                    $('#brand_id').append('<option value="">{{ __('Error loading brands') }}</option>').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }

        function loadProducts(warehouseId, categoryId, brandId, callback) {
            $('#product_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-products') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    category_id: categoryId,
                    brand_id: brandId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#product_id').empty().prop('disabled', false);
                    $('#product_id').append('<option value="">{{ __('Select Product') }}</option>');
                    
                    if (data.products && data.products.length > 0) {
                        $.each(data.products, function(index, product) {
                            $('#product_id').append(`<option value="${product.id}">${product.name} ${product.sku ? '(' + product.sku + ')' : ''}</option>`);
                        });
                    } else {
                        $('#product_id').append('<option value="">{{ __('No products found for this category and brand') }}</option>');
                    }
                    $('#product_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#product_id').empty().prop('disabled', false);
                    $('#product_id').append('<option value="">{{ __('Error loading products') }}</option>').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }

        function loadSubProducts(warehouseId, productId, callback) {
            $('#sub_product_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-sub-products') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    product_id: productId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#sub_product_id').empty().prop('disabled', false);
                    $('#sub_product_id').append('<option value="">{{ __('Select Sub-Product') }}</option>');
                    
                    // Store product data map
                    window.productDataMap = {};
                    
                    if (data && data.length > 0) {
                        $.each(data, function(key, value) {
                            var displayText = (value.name || '').trim();
                            
                            // Remove custom fields separator if present
                            if (displayText.indexOf('|') !== -1) {
                                displayText = displayText.split('|')[0].trim();
                            }
                            
                            // Add product_no
                            if (value.product_no) {
                                displayText += ' [' + value.product_no + ']';
                            }
                            
                            // Add custom fields if available
                            if (value.custom_fields && value.custom_fields.trim() !== '') {
                                // Remove trailing ' | ' if present
                                var customFieldsStr = value.custom_fields.trim();
                                if (customFieldsStr.endsWith(' | ')) {
                                    customFieldsStr = customFieldsStr.slice(0, -3);
                                }
                                displayText += ' | ' + customFieldsStr;
                            }
                            
                            displayText = $('<div>').text(displayText).html();
                            
                            var $option = $('<option></option>')
                                .attr('value', value.id)
                                .text(displayText);
                            
                            $('#sub_product_id').append($option);
                            
                            // Store product data
                            window.productDataMap[value.id] = value;
                        });
                    } else {
                        $('#sub_product_id').append('<option value="">{{ __('No sub-products found') }}</option>');
                    }
                    
                    // Reinitialize Choices if needed
                    if ($('#sub_product_id').data('choices')) {
                        $('#sub_product_id').data('choices').destroy();
                    }
                    
                    var multipleCancelButton = new Choices('#sub_product_id', {
                        removeItemButton: true,
                        searchEnabled: true,
                        shouldSort: false
                    });
                    
                    $('#sub_product_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#sub_product_id').empty().prop('disabled', false);
                    $('#sub_product_id').append('<option value="">{{ __('Error loading sub-products') }}</option>').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
        
        function displayProductInfo(productData) {
            if (!productData) return;
            
            // Display product number
            if (productData.product_no) {
                $('#display_product_no').text(productData.product_no);
            }
            
            // Display quantity
            var quantity = productData.total_quantity || 0;
            $('#display_quantity').text(quantity);
            
            // Get prices from product data (already calculated in controller)
            var priceWithoutVat = parseFloat(productData.price_without_vat) || parseFloat(productData.sale_price) || 0;
            var priceWithVat = parseFloat(productData.price_with_vat) || parseFloat(productData.sale_price) || 0;
            
            // Format and display prices
            $('#display_price_without_vat').text(formatPrice(priceWithoutVat));
            $('#display_price_with_vat').text(formatPrice(priceWithVat));
            
            // Show product info container
            $('#product_info_container').show();
        }
        
        function formatPrice(price) {
            return parseFloat(price).toFixed(2) + ' {{ \Auth::user()->currencySymbol() }}';
        }

        function getProduct(bid) {

            $.ajax({
                url: '{{ route('pos.getproduct') }}',
                type: 'POST',
                data: {
                    "warehouse_id": bid,
                    "_token": "{{ csrf_token() }}",
                },

                success: function(data) {
                    console.log(data);
                    $('#product_id').empty();
                    $('#product_id').append('<option value="">{{ __('Select Product') }}</option>');

                    $.each(data, function(key, value) {
                        console.log(value);
                        // Display full name with product_no in dropdown (clean text only, no custom fields in display)
                        // Ensure name is clean - just the hierarchy path, no custom fields
                        var displayText = (value.name || '').trim();
                        
                        // Remove any custom fields that might have been appended (safety check)
                        if (displayText.indexOf('|') !== -1) {
                            // If custom fields separator found, take only the part before it
                            displayText = displayText.split('|')[0].trim();
                        }
                        
                        if (value.product_no) {
                            displayText += ' [' + value.product_no + ']';
                        }
                        
                        // Escape HTML to prevent rendering issues
                        displayText = $('<div>').text(displayText).html();
                        
                        // Create option element properly - use plain text, no data attributes
                        var $option = $('<option></option>')
                            .attr('value', value.id)
                            .text(displayText);
                        
                        $('#product_id').append($option);
                    });
                    
                    // Store product data for later use (before initializing Choices)
                    window.productDataMap = {};
                    $.each(data, function(key, value) {
                        window.productDataMap[value.id] = value;
                    });
                    
                    // Reinitialize Choices after clearing and repopulating
                    if ($('#product_id').data('choices')) {
                        $('#product_id').data('choices').destroy();
                    }
                    
                    var multipleCancelButton = new Choices('#product_id', {
                        removeItemButton: true,
                        searchEnabled: true,
                        shouldSort: false
                    });

                }

            });
        }

        // Direct barcode search functionality (NEW - searches without filters)
        var barcodeSearchTimeout;
        var barcodeSearchProcessing = false;
        
        // Handle Enter key or button click
        $('#barcode_search_direct').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                searchBarcodeDirect();
            }
        });
        
        $('#barcode_search_btn').on('click', function() {
            searchBarcodeDirect();
        });
        
        // Auto-search on input (debounced)
        $('#barcode_search_direct').on('input', function() {
            var barcode = $(this).val().trim();
            
            clearTimeout(barcodeSearchTimeout);
            
            if (barcode.length >= 3) {
                barcodeSearchTimeout = setTimeout(function() {
                    searchBarcodeDirect();
                }, 800);
            } else if (barcode.length === 0) {
                // Clear status message
                $('#barcode_search_status').hide();
            }
        });
        
        function searchBarcodeDirect() {
            var barcode = $('#barcode_search_direct').val().trim();
            
            if (!barcode || barcode.length < 3) {
                showBarcodeStatus('warning', '{{ __("Please enter at least 3 characters") }}');
                return;
            }
            
            if (barcodeSearchProcessing) {
                return; // Prevent multiple simultaneous searches
            }
            
            barcodeSearchProcessing = true;
            var warehouseId = $('#warehouse_id').val();
            
            // Show loading state
            $('#barcode_search_btn').prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Searching...") }}');
            showBarcodeStatus('info', '{{ __("Searching for barcode/SKU...") }}');
            
            $.ajax({
                url: '{{ route("pos.barcode.search-direct") }}',
                type: 'POST',
                data: {
                    barcode: barcode,
                    warehouse_id: warehouseId || null,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    if (data.success && data.sub_product) {
                        // Auto-populate all filters
                        autoPopulateFilters(data);
                        showBarcodeStatus('success', '{{ __("Product found! All fields have been auto-filled.") }}');
                    } else {
                        showBarcodeStatus('danger', data.error || '{{ __("Product not found") }}');
                    }
                },
                error: function(xhr) {
                    var errorMsg = '{{ __("Error searching barcode/SKU") }}';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    showBarcodeStatus('danger', errorMsg);
                },
                complete: function() {
                    barcodeSearchProcessing = false;
                    $('#barcode_search_btn').prop('disabled', false).html('<i class="ti ti-search"></i> {{ __("Search") }}');
                }
            });
        }
        
        function autoPopulateFilters(data) {
            // Set warehouse if not already set
            if (data.warehouse && data.warehouse.id && !$('#warehouse_id').val()) {
                $('#warehouse_id').val(data.warehouse.id).trigger('change');
            }
            
            // Wait a bit for warehouse change to process, then set other filters
            setTimeout(function() {
                // Set category
                if (data.category && data.category.id) {
                    // First load categories if needed
                    if ($('#category_id').find('option[value="' + data.category.id + '"]').length === 0) {
                        loadCategories(data.warehouse.id, function() {
                            $('#category_id').val(data.category.id).trigger('change');
                            setTimeout(function() {
                                setBrandAndProduct(data);
                            }, 500);
                        });
                    } else {
                        $('#category_id').val(data.category.id).trigger('change');
                        setTimeout(function() {
                            setBrandAndProduct(data);
                        }, 500);
                    }
                } else {
                    setBrandAndProduct(data);
                }
            }, 300);
        }
        
        function setBrandAndProduct(data) {
            // Set brand
            if (data.brand && data.brand.id) {
                if ($('#brand_id').find('option[value="' + data.brand.id + '"]').length === 0) {
                    var categoryId = $('#category_id').val();
                    var warehouseId = $('#warehouse_id').val();
                    if (categoryId && warehouseId) {
                        loadBrands(warehouseId, categoryId, function() {
                            $('#brand_id').val(data.brand.id).trigger('change');
                            setTimeout(function() {
                                setProductAndSubProduct(data);
                            }, 500);
                        });
                    }
                } else {
                    $('#brand_id').val(data.brand.id).trigger('change');
                    setTimeout(function() {
                        setProductAndSubProduct(data);
                    }, 500);
                }
            } else {
                setProductAndSubProduct(data);
            }
        }
        
        function setProductAndSubProduct(data) {
            // Set product
            if (data.product && data.product.id) {
                if ($('#product_id').find('option[value="' + data.product.id + '"]').length === 0) {
                    var categoryId = $('#category_id').val();
                    var brandId = $('#brand_id').val();
                    var warehouseId = $('#warehouse_id').val();
                    if (categoryId && brandId && warehouseId) {
                        loadProducts(warehouseId, categoryId, brandId, function() {
                            $('#product_id').val(data.product.id).trigger('change');
                            setTimeout(function() {
                                setSubProduct(data);
                            }, 500);
                        });
                    }
                } else {
                    $('#product_id').val(data.product.id).trigger('change');
                    setTimeout(function() {
                        setSubProduct(data);
                    }, 500);
                }
            } else {
                setSubProduct(data);
            }
        }
        
        function setSubProduct(data) {
            // Set sub-product
            if (data.sub_product && data.sub_product.id) {
                if ($('#sub_product_id').find('option[value="' + data.sub_product.id + '"]').length === 0) {
                    var productId = $('#product_id').val();
                    var warehouseId = $('#warehouse_id').val();
                    if (productId && warehouseId) {
                        loadSubProducts(warehouseId, productId, function() {
                            $('#sub_product_id').val(data.sub_product.id).trigger('change');
                            displayProductInfoFromBarcode(data);
                        });
                    }
                } else {
                    $('#sub_product_id').val(data.sub_product.id).trigger('change');
                    displayProductInfoFromBarcode(data);
                }
            } else {
                displayProductInfoFromBarcode(data);
            }
        }
        
        function displayProductInfoFromBarcode(data) {
            if (data.sub_product) {
                // Display product info
                if (data.sub_product.product_no) {
                    $('#display_product_no').text(data.sub_product.product_no);
                }
                if (data.sub_product.total_quantity !== undefined) {
                    $('#display_quantity').text(data.sub_product.total_quantity);
                }
                if (data.sub_product.price_without_vat !== undefined) {
                    $('#display_price_without_vat').text(formatPrice(data.sub_product.price_without_vat));
                }
                if (data.sub_product.price_with_vat !== undefined) {
                    $('#display_price_with_vat').text(formatPrice(data.sub_product.price_with_vat));
                }
                $('#product_info_container').show();
                
                // Load and display custom fields
                if (data.custom_fields && data.custom_fields.length > 0) {
                    $('#custom_fields_checkboxes').empty();
                    $.each(data.custom_fields, function(key, field) {
                        var checkboxHtml = '<div class="col-md-3 mb-2">' +
                            '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" name="custom_fields[]" value="' + field.id + '" id="custom_field_' + field.id + '" checked>' +
                            '<label class="form-check-label" for="custom_field_' + field.id + '">' +
                            field.name + (field.value ? ' (' + field.value + ')' : '') +
                            '</label>' +
                            '</div>' +
                            '</div>';
                        $('#custom_fields_checkboxes').append(checkboxHtml);
                    });
                    $('#custom_fields_container').show();
                } else {
                    // Try to load custom fields via existing route
                    if (data.sub_product.product_no) {
                        getCustomFields(data.sub_product.product_no);
                    }
                }
            }
        }
        
        function showBarcodeStatus(type, message) {
            var alertClass = 'alert-' + type;
            $('#barcode_search_status').removeClass('alert-success alert-danger alert-warning alert-info')
                .addClass(alertClass)
                .show();
            $('#barcode_search_message').text(message);
            
            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $('#barcode_search_status').fadeOut();
                }, 3000);
            }
        }
        
        // Update loadCategories to accept callback
        function loadCategories(warehouseId, callback) {
            $('#category_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-categories') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#category_id').empty().prop('disabled', false);
                    $('#category_id').append('<option value="">{{ __('Select Category') }}</option>');
                    
                    if (data.categories && data.categories.length > 0) {
                        $.each(data.categories, function(index, category) {
                            $('#category_id').append(`<option value="${category.id}">${category.name}</option>`);
                        });
                    } else {
                        $('#category_id').append('<option value="">{{ __('No categories found') }}</option>');
                    }
                    $('#category_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#category_id').empty().prop('disabled', false);
                    $('#category_id').append('<option value="">{{ __('Error loading categories') }}</option>').trigger('change');
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
        
        // Update loadBrands to accept callback
        function loadBrands(warehouseId, categoryId, callback) {
            $('#brand_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-brands') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    category_id: categoryId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#brand_id').empty().prop('disabled', false);
                    $('#brand_id').append('<option value="">{{ __('Select Brand') }}</option>');
                    
                    if (data.brands && data.brands.length > 0) {
                        $.each(data.brands, function(index, brand) {
                            $('#brand_id').append(`<option value="${brand.id}">${brand.name}</option>`);
                        });
                    } else {
                        $('#brand_id').append('<option value="">{{ __('No brands found for this category') }}</option>');
                    }
                    $('#brand_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#brand_id').empty().prop('disabled', false);
                    $('#brand_id').append('<option value="">{{ __('Error loading brands') }}</option>').trigger('change');
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
        
        // Update loadProducts to accept callback
        function loadProducts(warehouseId, categoryId, brandId, callback) {
            $('#product_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-products') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    category_id: categoryId,
                    brand_id: brandId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#product_id').empty().prop('disabled', false);
                    $('#product_id').append('<option value="">{{ __('Select Product') }}</option>');
                    
                    if (data.products && data.products.length > 0) {
                        $.each(data.products, function(index, product) {
                            $('#product_id').append(`<option value="${product.id}">${product.name} ${product.sku ? '(' + product.sku + ')' : ''}</option>`);
                        });
                    } else {
                        $('#product_id').append('<option value="">{{ __('No products found for this category and brand') }}</option>');
                    }
                    $('#product_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#product_id').empty().prop('disabled', false);
                    $('#product_id').append('<option value="">{{ __('Error loading products') }}</option>').trigger('change');
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
        
        // Update loadSubProducts to accept callback
        function loadSubProducts(warehouseId, productId, callback) {
            $('#sub_product_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
            
            $.ajax({
                url: '{{ route('pos.barcode.get-sub-products') }}',
                type: 'POST',
                data: {
                    warehouse_id: warehouseId,
                    product_id: productId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#sub_product_id').empty().prop('disabled', false);
                    $('#sub_product_id').append('<option value="">{{ __('Select Sub-Product') }}</option>');
                    
                    // Store product data map
                    window.productDataMap = {};
                    
                    if (data && data.length > 0) {
                        $.each(data, function(key, value) {
                            var displayText = (value.name || '').trim();
                            
                            // Remove custom fields separator if present
                            if (displayText.indexOf('|') !== -1) {
                                displayText = displayText.split('|')[0].trim();
                            }
                            
                            // Add product_no
                            if (value.product_no) {
                                displayText += ' [' + value.product_no + ']';
                            }
                            
                            // Add custom fields if available
                            if (value.custom_fields && value.custom_fields.trim() !== '') {
                                // Remove trailing ' | ' if present
                                var customFieldsStr = value.custom_fields.trim();
                                if (customFieldsStr.endsWith(' | ')) {
                                    customFieldsStr = customFieldsStr.slice(0, -3);
                                }
                                displayText += ' | ' + customFieldsStr;
                            }
                            
                            displayText = $('<div>').text(displayText).html();
                            
                            var $option = $('<option></option>')
                                .attr('value', value.id)
                                .text(displayText);
                            
                            $('#sub_product_id').append($option);
                            
                            // Store product data
                            window.productDataMap[value.id] = value;
                        });
                    } else {
                        $('#sub_product_id').append('<option value="">{{ __('No sub-products found') }}</option>');
                    }
                    
                    // Reinitialize Choices if needed
                    if ($('#sub_product_id').data('choices')) {
                        $('#sub_product_id').data('choices').destroy();
                    }
                    
                    var multipleCancelButton = new Choices('#sub_product_id', {
                        removeItemButton: true,
                        searchEnabled: true,
                        shouldSort: false
                    });
                    
                    $('#sub_product_id').trigger('change');
                    
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                },
                error: function() {
                    $('#sub_product_id').empty().prop('disabled', false);
                    $('#sub_product_id').append('<option value="">{{ __('Error loading sub-products') }}</option>').trigger('change');
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }
        
        // Old barcode search functionality (kept for backward compatibility)
        var barcodeSearchTimeoutOld;
        $('#barcode_search').on('input', function() {
            var barcode = $(this).val().trim();
            
            clearTimeout(barcodeSearchTimeoutOld);
            
            if (barcode.length >= 3) {
                barcodeSearchTimeoutOld = setTimeout(function() {
                    searchByBarcode(barcode);
                }, 500);
            } else if (barcode.length === 0) {
                // Clear selection if barcode is cleared
                $('#product_id').val('').trigger('change');
            }
        });
        
        function searchByBarcode(barcode) {
            // Search through product options
            var found = false;
            $('#product_id option').each(function() {
                var $option = $(this);
                var productData = $option.data('product-data');
                
                if (productData && productData.parent_product && productData.parent_product.sku) {
                    if (productData.parent_product.sku.toLowerCase().includes(barcode.toLowerCase())) {
                        $('#product_id').val($option.val()).trigger('change');
                        found = true;
                        return false; // Break loop
                    }
                }
            });
            
            if (!found) {
                // Also search by product_no
                $('#product_id option').each(function() {
                    var $option = $(this);
                    var productData = $option.data('product-data');
                    
                    if (productData && productData.product_no) {
                        if (productData.product_no.toString().includes(barcode)) {
                            $('#product_id').val($option.val()).trigger('change');
                            found = true;
                            return false; // Break loop
                        }
                    }
                });
            }
            
            if (!found) {
                console.log('Barcode not found:', barcode);
            }
        }
        
        function getCustomFields(productNo) {
            $.ajax({
                url: '{{ route('pos.getcustomfields') }}',
                type: 'POST',
                data: {
                    "product_no": productNo,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#custom_fields_checkboxes').empty();
                    
                    if (data && data.length > 0) {
                        $.each(data, function(key, field) {
                            var checkboxHtml = '<div class="col-md-3 mb-2">' +
                                '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" name="custom_fields[]" value="' + field.id + '" id="custom_field_' + field.id + '">' +
                                '<label class="form-check-label" for="custom_field_' + field.id + '">' +
                                field.name + (field.value ? ' (' + field.value + ')' : '') +
                                '</label>' +
                                '</div>' +
                                '</div>';
                            $('#custom_fields_checkboxes').append(checkboxHtml);
                        });
                        $('#custom_fields_container').show();
                    } else {
                        $('#custom_fields_container').hide();
                    }
                    
                    // Also ensure product info is displayed if product is selected
                    var selectedProductId = $('#product_id').val();
                    if (selectedProductId && window.productDataMap && window.productDataMap[selectedProductId]) {
                        displayProductInfo(window.productDataMap[selectedProductId]);
                    }
                },
                error: function() {
                    $('#custom_fields_container').hide();
                    $('#custom_fields_checkboxes').empty();
                }
            });
        }
    </script>
    <script>
        function copyToClipboard(element) {
            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            // document.addEventListener('copy', function (e) {
            //     e.clipboardData.setData('text/plain', copyText);
            //     e.preventDefault();
            // }, true);
            // document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>
    <script>
        var filename = $('#filesname').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.1,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 300,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: [60, 30], // Width: 60mm, Height: 30mm
                    orientation: 'landscape'
                }
            };
            html2pdf().set(opt).from(element).save();

        }
    </script>
@endpush


@section('action-btn')
    <div class="float-end">
        <a href="{{ route('pos.barcode') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Back') }}">
            <i class="ti ti-arrow-left text-white"></i>
        </a>
    </div>
@endsection


@section('content')
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('pos.receipt') }}" method="POST">
                        @csrf
                        <div class="row" id="printableArea">
                            {{-- Direct Barcode Search (Quick Access) --}}
                            <div class="col-md-12 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label for="barcode_search_direct" class="form-label">
                                                    <strong>{{ __('Quick Search by Barcode/SKU') }}</strong>
                                                    <small class="text-muted d-block">{{ __('Enter barcode or SKU to auto-fill all fields') }}</small>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           id="barcode_search_direct" 
                                                           class="form-control" 
                                                           placeholder="{{ __('Scan or type barcode/SKU here...') }}" 
                                                           autocomplete="off"
                                                           autofocus>
                                                    <button type="button" 
                                                            id="barcode_search_btn" 
                                                            class="btn btn-primary">
                                                        <i class="ti ti-search"></i> {{ __('Search') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div id="barcode_search_status" class="alert" style="display: none; margin-bottom: 0;">
                                                    <span id="barcode_search_message"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control select" required>
                                        @foreach ($warehouses as $warehouseId => $warehouseName)
                                            <option value="{{ $warehouseId }}">{{ $warehouseName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{-- Step 1: Category Filter (Required) --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category_id" class="form-label">
                                        {{ __('Step 1: Category') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="category_id" id="category_id" class="form-control select" required disabled>
                                        <option value="">{{ __('Please select a warehouse first') }}</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Step 2: Brand Filter (Required) --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="brand_id" class="form-label">
                                        {{ __('Step 2: Brand') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="brand_id" id="brand_id" class="form-control select" required disabled>
                                        <option value="">{{ __('Please select a category first') }}</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Step 3: Product Filter (Required) --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_id" class="form-label">
                                        {{ __('Step 3: Product') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="product_id" id="product_id" class="form-control select" required disabled>
                                        <option value="">{{ __('Please select a brand first') }}</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Step 4: Sub-Product Filter (Required) --}}
                            <div class="col-md-3">
                                <div class="form-group" id="sub_product_div">
                                    <label for="sub_product_id" class="form-label">
                                        {{ __('Step 4: Sub-Product') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="barcode_search" class="form-control mb-2" placeholder="{{ __('Search by Barcode') }}" autocomplete="off" style="display: none;">
                                    <select name="product_id" id="sub_product_id" class="form-control select" required disabled>
                                        <option value="">{{ __('Please select a product first') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="quantity" class="form-label">{{ __('Quantity') }}</label><span
                                    class="text-danger">*</span>
                                <input type="text" name="quantity" id="quantity" class="form-control" required>
                            </div>
                            <div class="col-md-12" id="custom_fields_container" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Custom Fields') }}</label>
                                    <div id="custom_fields_checkboxes" class="row">
                                        <!-- Custom field checkboxes will be populated here -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12" id="product_info_container" style="display: none; margin-top: 15px;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ __('Product Information') }}</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>{{ __('Product No') }}:</strong> <span id="display_product_no"></span></p>
                                                <p class="mb-1"><strong>{{ __('Available Quantity') }}:</strong> <span id="display_quantity"></span></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>{{ __('Price Without VAT') }}:</strong> <span id="display_price_without_vat"></span></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>{{ __('Price With VAT') }}:</strong> <span id="display_price_with_vat"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="discount" class="form-label">{{ __('Discount') }}</label>
                                <select name="discount" id="discount" class="form-control select" >
                                    <option value="0">NO</option>
                                    <option value="10">10%</option>
                                    <option value="20">20%</option>
                                    <option value="30">30%</option>
                                    <option value="40">40%</option>
                                    <option value="50">50%</option>
                                    <option value="60">60%</option>
                                    <option value="70">70%</option>
                                    <option value="80">80%</option>
                                    <option value="90">90%</option>
                                    <option value="100">100%</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                    <label class="form-check-label" for="labels">
                                        {{ __('Add Labels') }}
                                    </label>
                                    <input class="form-check-input" type="checkbox" name="labels" value="labels" id="labels">
                                    
                            </div>
                        </div>
                        <div class="col-md-6 pt-4">
                            <button type="submit" class="btn btn-sm btn-primary btn-icon">{{ __('Print') }}</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

