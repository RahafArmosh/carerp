@extends('layouts.admin')

@section('page-title')
    {{ __('Warehouse Transfer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Transfer') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('warehoustrans.create') }}" data-size="lg"
           data-bs-toggle="tooltip" title="{{ __('Create') }}"
           data-title="{{ __('Create Warehouse Transfer') }}"
           class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
<form method="POST" action="{{ route('warehouse-transfer.store') }}" id="warehouse-transfer-form">
    @csrf
    <div class="modal-body">
        <div class="row">
            {{-- From Warehouse --}}
            <div class="form-group col-md-6">
                <label class="form-label" for="from_warehouse">
                    {{ __('From Warehouse') }} <span class="text-danger">*</span>
                </label>
                <select class="form-control select" name="from_warehouse" id="warehouse_id" required>
                    <option value="">{{ __('Select Warehouse') }}</option>
                    @foreach ($from_warehouses as $id => $warehouse)
                        <option value="{{ $id }}">{{ $warehouse }}</option>
                    @endforeach
                </select>
            </div>

            {{-- To Warehouse --}}
            <div class="form-group col-md-6">
                <label class="form-label" for="to_warehouse">
                    {{ __('To Warehouse') }} <span class="text-danger">*</span>
                </label>
                <select class="form-control select" name="to_warehouse" required>
                    @foreach ($to_warehouses as $id => $warehouse)
                        <option value="{{ $id }}">{{ $warehouse }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category Filter (Required) --}}
            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Select Category') }} <span class="text-danger">*</span></label>
                <select class="form-control select" name="category_id" id="category_id" required disabled>
                    <option value="">{{ __('Please select a warehouse first') }}</option>
                </select>
            </div>

            {{-- Sub-Products Display --}}
            <div class="form-group col-md-12" id="sub_products_div" style="display: none;">
                <label class="form-label">{{ __('Select Sub-Products') }}</label>
                <div id="sub_products_list" class="border p-3 rounded" style="max-height: 400px; overflow-y: auto;">
                    <p class="text-muted">{{ __('Select a category to see sub-products') }}</p>
                </div>
            </div>

            {{-- Dynamic Quantities (Hidden, populated from sub-products) --}}
            <div class="form-group col-md-12" id="qty_div" style="display: none;">
                <label class="form-label">{{ __('Quantities') }}</label>
                <div id="quantity_fields"></div>
            </div>

            {{-- Transfer Date --}}
            <div class="form-group col-lg-6">
                <label class="form-label" for="date">{{ __('Date') }}</label>
                <input type="date" class="form-control w-100 mt-2" name="date" required>
            </div>

            {{-- Notes --}}
            <div class="form-group col-md-12">
                <label class="form-label" for="notes">{{ __('Notes') }}</label>
                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="{{ __('Optional notes about this transfer request') }}"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" onclick="window.history.back();">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
@endsection

@push('script-page')
<script>
    $(document).ready(function() {
        // Initialize selects
        $('.select').select2();

        // Enable category dropdown if warehouse is already selected
        let w_id = $('#warehouse_id').val();
        if (w_id && w_id !== '') {
            loadCategories(w_id);
        }
    });

    // Load categories when warehouse changes
    $(document).on('change', '#warehouse_id', function() {
        let warehouseId = $(this).val();
        
        // Reset filters
        resetFilters();
        
        if (warehouseId && warehouseId !== '') {
            loadCategories(warehouseId);
            
            // Update to_warehouse options
            updateToWarehouseOptions(warehouseId);
        } else {
            $('#category_id').prop('disabled', true);
        }
    });

    // Load sub-products when category changes
    $(document).on('change', '#category_id', function() {
        let categoryId = $(this).val();
        let warehouseId = $('#warehouse_id').val();
        
        // Reset sub-products display
        $('#sub_products_div').hide();
        $('#qty_div').hide();
        $('#quantity_fields').empty();
        
        // Load sub-products directly if category is selected
        if (categoryId && categoryId !== '' && warehouseId) {
            loadSubProductsByCategory(warehouseId, categoryId);
        }
    });

    function resetFilters() {
        $('#category_id').val('').trigger('change').prop('disabled', true);
        $('#sub_products_div').hide();
        $('#qty_div').hide();
        $('#quantity_fields').empty();
    }

    function loadCategories(warehouseId) {
        $('#category_id').prop('disabled', true).empty().append('<option value="">{{ __('Loading...') }}</option>').trigger('change');
        
        $.ajax({
            url: '{{ route('warehouse-transfer.get-categories') }}',
            type: 'POST',
            data: {
                warehouse_id: warehouseId,
                _token: "{{ csrf_token() }}"
            },
            success: function(data) {
                $('#category_id').empty().prop('disabled', false);
                $('#category_id').append('<option value="">{{ __('All Categories') }}</option>');
                
                if (data.categories && data.categories.length > 0) {
                    $.each(data.categories, function(index, category) {
                        $('#category_id').append(`<option value="${category.id}">${category.name}</option>`);
                    });
                } else {
                    $('#category_id').append('<option value="">{{ __('No categories found') }}</option>');
                }
                $('#category_id').trigger('change');
            },
            error: function() {
                $('#category_id').empty().prop('disabled', false);
                $('#category_id').append('<option value="">{{ __('Error loading categories') }}</option>').trigger('change');
            }
        });
    }

    function loadSubProductsByCategory(warehouseId, categoryId) {
        if (!categoryId || !warehouseId) {
            return; // Don't load if category or warehouse not selected
        }
        
        $('#sub_products_list').html('<p class="text-muted">{{ __('Loading sub-products...') }}</p>');
        $('#sub_products_div').show();
        
        $.ajax({
            url: '{{ route('warehouse-transfer.get-sub-products-by-category') }}',
            type: 'POST',
            data: {
                warehouse_id: warehouseId,
                category_id: categoryId,
                _token: "{{ csrf_token() }}"
            },
            success: function(data) {
                $('#sub_products_list').empty();
                $('#quantity_fields').empty();
                
                if (data.sub_products && data.sub_products.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                    html += '<thead><tr><th>{{ __('Product No') }}</th><th>{{ __('Product Name') }}</th><th>{{ __('Stock') }}</th><th>{{ __('Sale Price') }}</th><th>{{ __('Select') }}</th></tr></thead><tbody>';
                    
                    $.each(data.sub_products, function(index, subProduct) {
                        html += `<tr>
                            <td>${subProduct.product_no}</td>
                            <td>${subProduct.product_name}</td>
                            <td>${subProduct.total_quantity}</td>
                            <td>${subProduct.sale_price}</td>
                            <td>
                                <input type="checkbox" class="sub-product-checkbox" 
                                       data-product-no="${subProduct.product_no}" 
                                       data-product-name="${subProduct.product_name}"
                                       data-quantity="${subProduct.total_quantity}">
                            </td>
                        </tr>`;
                        
                        // Add hidden quantity field (will be shown when checked)
                        $('#quantity_fields').append(`
                            <div class="mb-2 quantity-field" data-product-no="${subProduct.product_no}" style="display: none;">
                                <label class="form-label">${subProduct.product_name} (Product #${subProduct.product_no}) - Available: ${subProduct.total_quantity}</label>
                                <input type="number" class="form-control quantity-input" 
                                       name="quantities[${subProduct.product_no}]" 
                                       value="1" 
                                       min="1" 
                                       max="${subProduct.total_quantity}"
                                       data-product-no="${subProduct.product_no}">
                            </div>
                        `);
                    });
                    
                    html += '</tbody></table></div>';
                    $('#sub_products_list').html(html);
                } else {
                    $('#sub_products_list').html('<p class="text-muted">{{ __('No sub-products found for this category') }}</p>');
                }
            },
            error: function() {
                $('#sub_products_list').html('<p class="text-danger">{{ __('Error loading sub-products') }}</p>');
            }
        });
    }

    // Handle sub-product checkbox changes
    $(document).on('change', '.sub-product-checkbox', function() {
        let productNo = $(this).data('product-no');
        let quantityField = $(`.quantity-field[data-product-no="${productNo}"]`);
        let quantityInput = quantityField.find('input');
        
        if ($(this).is(':checked')) {
            quantityField.show();
            quantityInput.prop('required', true);
            // Restore the name attribute
            quantityInput.attr('name', `quantities[${productNo}]`);
            // Set default value to 1 if it's 0 or empty
            if (!quantityInput.val() || quantityInput.val() == '0') {
                quantityInput.val(1);
            }
        } else {
            quantityField.hide();
            quantityInput.val('').prop('required', false);
            quantityInput.removeAttr('name'); // Remove name so it's not submitted
        }
        
        // Show/hide quantity div if any checkboxes are checked
        if ($('.sub-product-checkbox:checked').length > 0) {
            $('#qty_div').show();
        } else {
            $('#qty_div').hide();
        }
    });

    // Form submission validation
    $('#warehouse-transfer-form').on('submit', function(e) {
        let hasValidQuantities = false;
        let errorMessages = [];
        
        // Ensure all checked checkboxes have their quantity inputs properly named and validated
        $('.sub-product-checkbox:checked').each(function() {
            let productNo = $(this).data('product-no');
            let quantityInput = $(`.quantity-input[data-product-no="${productNo}"]`);
            let quantity = parseInt(quantityInput.val()) || 0;
            
            // Ensure the input has a name attribute
            quantityInput.attr('name', `quantities[${productNo}]`);
            
            if (quantity > 0) {
                hasValidQuantities = true;
            } else {
                errorMessages.push(`Product #${productNo}: Quantity must be greater than 0`);
            }
        });
        
        // Remove name attributes from unchecked quantity inputs
        $('.sub-product-checkbox:not(:checked)').each(function() {
            let productNo = $(this).data('product-no');
            $(`.quantity-input[data-product-no="${productNo}"]`).removeAttr('name');
        });
        
        if (!hasValidQuantities) {
            e.preventDefault();
            alert('{{ __("Please select at least one product and enter a quantity greater than 0.") }}');
            return false;
        }
        
        // Validate all checked products have valid quantities
        if (errorMessages.length > 0) {
            e.preventDefault();
            alert('{{ __("Please fix the following errors:") }}\n' + errorMessages.join('\n'));
            return false;
        }
    });

    function updateToWarehouseOptions(excludeWarehouseId) {
        $.ajax({
            url: '{{ route('warehouse-transfer.getproduct') }}',
            type: 'POST',
            data: {
                warehouse_id: excludeWarehouseId,
                _token: "{{ csrf_token() }}"
            },
            success: function(data) {
                $('select[name=to_warehouse]').empty();
                if (data.to_warehouses) {
                    $.each(data.to_warehouses, function(key, value) {
                        $('select[name=to_warehouse]').append(`<option value="${key}">${value}</option>`);
                    });
                }
            }
        });
    }
</script>
@endpush
