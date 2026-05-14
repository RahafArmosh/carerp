@extends('layouts.admin')

@section('page-title')
    {{ __('Add Combo Offer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('combo_offers.index') }}">{{ __('Combo Offers') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add') }}</li>
@endsection

@section('content')
@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('combo_offers.store') }}" class="card shadow-sm p-4">
            @csrf
            <h4 class="mb-4">{{ __('Add Combo Offer') }}</h4>

            {{-- Warehouse --}}
            <div class="mb-3 position-relative">
                <label class="form-label">{{ __('Warehouse') }}</label>
                <input type="text" class="form-control dropdown-search" placeholder="{{ __('Search Warehouse...') }}">
                <ul class="dropdown-list list-group position-absolute w-100" style="z-index: 1000; max-height:200px; overflow-y:auto; display:none;">
                    @foreach ($warehouses as $warehouse)
                        <li class="list-group-item list-group-item-action" data-value="{{ $warehouse->id }}">{{ $warehouse->name }}</li>
                    @endforeach
                </ul>
                <input type="hidden" name="warehouse_id">
            </div>

            {{-- Brand --}}
            <div class="mb-3 position-relative">
                <label class="form-label">{{ __('Brand') }}</label>
                <input type="text" class="form-control dropdown-search" id="brand_search" placeholder="{{ __('Search Brand...') }}">
                <ul class="dropdown-list list-group position-absolute w-100" style="z-index: 1000; max-height:200px; overflow-y:auto; display:none;">
                    <li class="list-group-item list-group-item-action" data-value="">{{ __('All Brands') }}</li>
                    @foreach ($brands as $brand)
                        <li class="list-group-item list-group-item-action" data-value="{{ $brand->id }}">{{ $brand->name }}</li>
                    @endforeach
                </ul>
                <input type="hidden" name="brand_id" id="brand_id">
            </div>

            {{-- Sub Brand --}}
            <div class="mb-3 position-relative">
                <label class="form-label">{{ __('Sub Brand') }}</label>
                <input type="text" class="form-control dropdown-search" id="sub_brand_search" placeholder="{{ __('Search Sub Brand...') }}">
                <ul class="dropdown-list list-group position-absolute w-100" style="z-index: 1000; max-height:200px; overflow-y:auto; display:none;" id="sub_brand_list">
                    <li class="list-group-item list-group-item-action" data-value="">{{ __('All Sub Brands') }}</li>
                </ul>
                <input type="hidden" name="sub_brand_id" id="sub_brand_id">
            </div>

            {{-- Products (Multi-select, lazy-loaded) --}}
            <div class="mb-3">
                <label class="form-label">{{ __('Products') }} <span class="text-danger">*</span></label>
                <select name="product_ids[]" id="product_ids" class="form-control" multiple required style="min-height: 150px;">
                    {{-- Options are loaded dynamically via AJAX based on warehouse / brand / sub-brand --}}
                </select>
                <small class="form-text text-muted">{{ __('Search and select multiple products') }}</small>
            </div>

            {{-- Buy Quantity --}}
            <div class="mb-3">
                <label class="form-label">{{ __('Buy Quantity') }}</label>
                <input type="number" name="buy_quantity" class="form-control" min="1">
            </div>

            {{-- Combo Type --}}
            <div class="mb-3 position-relative">
                <label class="form-label">{{ __('Combo Type') }}</label>
                <input type="text" class="form-control dropdown-search" placeholder="{{ __('Search Type...') }}">
                <ul class="dropdown-list list-group position-absolute w-100" style="z-index: 1000; display:none;">
                    <li class="list-group-item list-group-item-action" data-value="bogo">{{ __('Buy X Get Y') }}</li>
                    <li class="list-group-item list-group-item-action" data-value="tiered_pricing">{{ __('Tiered Pricing') }}</li>
                </ul>
                <input type="hidden" name="type" id="combo_type">
            </div>

            {{-- Bogo / Tiered --}}
            <div class="mb-3 bogo-fields d-none">
                <label class="form-label">{{ __('Get Quantity') }}</label>
                <input type="number" name="get_quantity" class="form-control" min="0">
            </div>

            <div class="mb-3 tiered-fields d-none">
                <label class="form-label">{{ __('Tiered Price') }}</label>
                <input type="number" step="0.01" name="tiered_price" class="form-control" placeholder="{{ __('Enter Tiered Price') }}">
            </div>

            {{-- Valid Until --}}
            <div class="mb-3">
                <label class="form-label">{{ __('Valid Until') }}</label>
                <input type="date" name="valid_until" class="form-control">
            </div>

            {{-- Active --}}
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="active" id="active" checked>
                <label class="form-check-label" for="active">{{ __('Active') }}</label>
            </div>

            <div class="d-flex justify-content-end">
                <input type="button" value="{{ __('Cancel') }}" class="btn btn-light me-2" data-bs-dismiss="modal">
                <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
</div>
@endsection

@push('script-page')
<!-- Include Select2 CSS (already in admin layout, but ensuring it's available) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<!-- Include jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bogoFields = document.querySelectorAll('.bogo-fields');
    const tieredFields = document.querySelectorAll('.tiered-fields');
    const productSelect = document.getElementById('product_ids');
    const brandIdInput = document.getElementById('brand_id');
    const subBrandIdInput = document.getElementById('sub_brand_id');
    const subBrandSearch = document.getElementById('sub_brand_search');
    const subBrandList = document.getElementById('sub_brand_list');
    const brandSearch = document.getElementById('brand_search');
    const brandList = brandSearch.parentElement.querySelector('.dropdown-list');

    function toggleFields(type) {
        bogoFields.forEach(f => f.classList.toggle('d-none', type !== 'bogo'));
        tieredFields.forEach(f => f.classList.toggle('d-none', type !== 'tiered_pricing'));
    }

    // Filter brands by warehouse
    function filterBrandsByWarehouse() {
        const warehouseId = document.querySelector('input[name="warehouse_id"]').value;
        
        if (!warehouseId) {
            // If no warehouse selected, show all brands
            brandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('All Brands') }}</li>';
            @foreach ($brands as $brand)
                const brandLi{{ $brand->id }} = document.createElement('li');
                brandLi{{ $brand->id }}.className = 'list-group-item list-group-item-action';
                brandLi{{ $brand->id }}.dataset.value = '{{ $brand->id }}';
                brandLi{{ $brand->id }}.textContent = '{{ $brand->name }}';
                brandList.appendChild(brandLi{{ $brand->id }});
            @endforeach
            return;
        }

        // Fetch brands from API filtered by warehouse
        brandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('Loading...') }}</li>';
        brandSearch.disabled = true;

        fetch('{{ route("combo_offers.get-sub-brands") }}?warehouse_id=' + warehouseId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Brands API response:', data);
            brandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('All Brands') }}</li>';
            
            if (data.success && data.brands && data.brands.length > 0) {
                data.brands.forEach(brand => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action';
                    li.dataset.value = brand.id;
                    li.textContent = brand.name;
                    brandList.appendChild(li);
                });
            }
            
            brandSearch.disabled = false;
            // Reset brand selection
            brandIdInput.value = '';
            brandSearch.value = '';
            // Also reset sub-brand and filter products
            subBrandIdInput.value = '';
            subBrandSearch.value = '';
            
            // Always populate sub-brands from the API response (even if empty array)
            if (data.success && data.sub_brands !== undefined) {
                console.log('Populating sub-brands from brands API response:', data.sub_brands ? data.sub_brands.length : 0);
                subBrandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('All Sub Brands') }}</li>';
                
                if (Array.isArray(data.sub_brands) && data.sub_brands.length > 0) {
                    data.sub_brands.forEach(subBrand => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.dataset.value = subBrand.id;
                        li.textContent = subBrand.name;
                        subBrandList.appendChild(li);
                    });
                }
                subBrandSearch.disabled = false;
            } else {
                console.log('No sub_brands in response, fetching separately');
                // If no sub-brands in response, fetch them separately
                filterSubBrands('');
            }
        })
        .catch(error => {
            console.error('Error fetching brands:', error);
            brandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('Error loading brands') }}</li>';
            brandSearch.disabled = false;
        });
    }

    // Filter sub-brands by warehouse and brand (always via API for performance)
    function filterSubBrands(brandId) {
        const warehouseId = document.querySelector('input[name="warehouse_id"]').value;

        // If warehouse is not selected, clear and disable sub-brand controls
        if (!warehouseId) {
            subBrandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('All Sub Brands') }}</li>';
            subBrandSearch.disabled = true;
            subBrandIdInput.value = '';
            subBrandSearch.value = '';
            // No warehouse, so also clear products
            filterProducts();
            return;
        }

        // Fetch sub-brands from API filtered by warehouse and brand
        subBrandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('Loading...') }}</li>';
        subBrandSearch.disabled = true;

        fetch('{{ route("combo_offers.get-sub-brands") }}?warehouse_id=' + warehouseId + (brandId ? '&brand_id=' + brandId : ''), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Sub-brands API response:', data);
            subBrandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('All Sub Brands') }}</li>';
            
            if (data.success && data.sub_brands && Array.isArray(data.sub_brands) && data.sub_brands.length > 0) {
                console.log('Found', data.sub_brands.length, 'sub-brands');
                data.sub_brands.forEach(subBrand => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action';
                    li.dataset.value = subBrand.id;
                    li.textContent = subBrand.name;
                    subBrandList.appendChild(li);
                });
            } else {
                console.log('No sub-brands found for warehouse:', warehouseId, 'brand:', brandId);
            }
            
            subBrandSearch.disabled = false;
            // Reset sub-brand selection
            subBrandIdInput.value = '';
            subBrandSearch.value = '';
            filterProducts();
        })
        .catch(error => {
            console.error('Error fetching sub-brands:', error);
            subBrandList.innerHTML = '<li class="list-group-item list-group-item-action" data-value="">{{ __('Error loading sub-brands') }}</li>';
            subBrandSearch.disabled = false;
        });
    }

    // Load products by warehouse / brand / sub-brand via AJAX
    function filterProducts() {
        const warehouseId = document.querySelector('input[name="warehouse_id"]').value;
        const brandId = brandIdInput.value;
        const subBrandId = subBrandIdInput.value;

        if (!warehouseId) {
            // No warehouse selected: clear list
            productSelect.innerHTML = '';
            if ($('#product_ids').hasClass('select2-hidden-accessible')) {
                $('#product_ids').trigger('change.select2');
            }
            return;
        }

        const params = new URLSearchParams();
        params.append('warehouse_id', warehouseId);
        if (brandId) params.append('brand_id', brandId);
        if (subBrandId) params.append('sub_brand_id', subBrandId);

        fetch('{{ route("combo_offers.get-products") }}?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error loading products for combo:', data.error || data.message);
                return;
            }

            // Preserve any already-selected product IDs so user selections are not lost
            const selectedValues = Array.from(productSelect.selectedOptions).map(o => o.value);

            productSelect.innerHTML = '';
            (data.products || []).forEach(prod => {
                const opt = document.createElement('option');
                opt.value = prod.id;
                opt.dataset.brandId = prod.brand_id || '';
                opt.dataset.subBrandId = prod.sub_brand_id || '';
                opt.dataset.sku = prod.sku || '';
                opt.textContent = prod.name + (prod.sku ? ' (SKU: ' + prod.sku + ')' : '');

                if (selectedValues.includes(String(prod.id))) {
                    opt.selected = true;
                }

                productSelect.appendChild(opt);
            });

            if ($('#product_ids').hasClass('select2-hidden-accessible')) {
                $('#product_ids').trigger('change.select2');
            }
        })
        .catch(error => {
            console.error('Error fetching products for combo:', error);
        });
    }

    // Generic dropdown search
    document.querySelectorAll('.dropdown-search').forEach(input => {
        const container = input.parentElement;
        const list = container.querySelector('.dropdown-list');
        const hiddenInput = container.querySelector('input[type="hidden"]');

        input.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            let hasVisible = false;
            list.querySelectorAll('li').forEach(li => {
                if(li.textContent.toLowerCase().includes(val)) {
                    li.style.display = '';
                    hasVisible = true;
                } else {
                    li.style.display = 'none';
                }
            });
            list.style.display = hasVisible ? 'block' : 'none';
        });

        input.addEventListener('focus', () => list.style.display = 'block');

        // Use event delegation to handle clicks on dynamically added elements
        list.addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (!li) return;
            
            input.value = li.textContent;
            hiddenInput.value = li.dataset.value;
            list.style.display = 'none';

            // Special handling for Combo Type
            if (hiddenInput.name === 'type') {
                toggleFields(li.dataset.value);
            }
            
            // Handle brand selection
            if (hiddenInput.name === 'brand_id') {
                filterSubBrands(li.dataset.value);
            }
            
            // Handle sub-brand selection
            if (hiddenInput.name === 'sub_brand_id') {
                filterProducts();
            }
            
            // Handle warehouse selection
            if (hiddenInput.name === 'warehouse_id') {
                // Fetch brands and sub-brands based on selected warehouse
                filterBrandsByWarehouse();
                // Filter sub-brands and products based on selected warehouse and brand
                const brandId = brandIdInput.value;
                filterSubBrands(brandId);
            }
        });

        document.addEventListener('click', function(e){
            if(!container.contains(e.target)) list.style.display = 'none';
        });
    });

    // Initialize brands and sub-brands lists
    // Only filter if warehouse is already selected
    const initialWarehouseId = document.querySelector('input[name="warehouse_id"]').value;
    if (initialWarehouseId) {
        filterBrandsByWarehouse();
    }
    
    // Initialize Select2 for product select with search functionality
    $(document).ready(function() {
        $('#product_ids').select2({
            width: '100%',
            placeholder: '{{ __('Search and select products...') }}',
            allowClear: false,
            closeOnSelect: false,
            language: {
                noResults: function() {
                    return '{{ __('No products found') }}';
                },
                searching: function() {
                    return '{{ __('Searching...') }}';
                }
            }
        });
    });
});
</script>
@endpush
