@extends('layouts.admin')

@section('page-title')
    {{ __('Edit Combo Offer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('combo_offers.index') }}">{{ __('Combo Offers') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
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

    <div class="row">
        <div class="col-xl-12">
            <form method="POST" action="{{ route('combo_offers.update', $comboOffer->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">

                        {{-- Warehouse --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" class="form-control select" required>
                                <option value="" disabled>{{ __('Select Warehouse') }}</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" 
                                        {{ $comboOffer->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Brand --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Brand') }}</label>
                            <select name="brand_id" id="brand_id" class="form-control select">
                                <option value="">{{ __('All Brands') }}</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" 
                                        {{ $comboOffer->brand_id == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Model (sub_brands) --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Model') }}</label>
                            <select name="sub_brand_id" id="sub_brand_id" class="form-control select">
                                <option value="">{{ __('All Models') }}</option>
                                @foreach ($subBrands as $subBrand)
                                    <option value="{{ $subBrand->id }}" 
                                        data-brand-id="{{ $subBrand->brand_id }}"
                                        {{ $comboOffer->sub_brand_id == $subBrand->id ? 'selected' : '' }}>
                                        {{ $subBrand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Products (Multi-select) --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Products') }} <span class="text-danger">*</span></label>
                            <select name="product_ids[]" id="product_ids" class="form-control" multiple required style="min-height: 150px;">
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" 
                                        data-brand-id="{{ $product->brand_id }}" 
                                        data-sub-brand-id="{{ $product->sub_brand_id }}"
                                        {{ $comboOffer->products->contains($product->id) ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">{{ __('Hold Ctrl/Cmd to select multiple products') }}</small>
                        </div>

                        {{-- Buy Quantity --}}
                        <div class="form-group col-md-6">
                            <label class="form-label">{{ __('Buy Quantity') }}</label>
                            <input type="number" name="buy_quantity" class="form-control" min="1"
                                   value="{{ old('buy_quantity', $comboOffer->buy_quantity) }}">
                        </div>

                        {{-- Type --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Combo Type') }}</label>
                            <select name="type" class="form-control select" required id="combo_type">
                                <option value="" disabled>{{ __('Select Type') }}</option>
                                <option value="bogo" {{ $comboOffer->type == 'bogo' ? 'selected' : '' }}>
                                    {{ __('Buy X Get Y') }}
                                </option>
                                <option value="tiered_pricing" {{ $comboOffer->type == 'tiered_pricing' ? 'selected' : '' }}>
                                    {{ __('Tiered Pricing') }}
                                </option>
                            </select>
                        </div>

                        {{-- Get Quantity (for BOGO) --}}
                        <div class="form-group col-md-6 bogo-fields d-none">
                            <label class="form-label">{{ __('Get Quantity') }}</label>
                            <input type="number" name="get_quantity" class="form-control" min="0"
                                   value="{{ old('get_quantity', $comboOffer->get_quantity) }}">
                        </div>

                        {{-- Tiered Pricing (for Tiered) --}}
                        <div class="form-group col-md-12 tiered-fields d-none">
                            <label class="form-label">{{ __('Tiered Price') }}</label>
                            <input type="number" step="0.01" name="tiered_price" class="form-control" 
                                   value="{{ old('tiered_price', $comboOffer->tiered_price) }}"
                                   placeholder="{{ __('Enter Tiered Price') }}">
                        </div>

                        {{-- Valid Until --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Valid Until') }}</label>
                            <input type="date" name="valid_until" class="form-control" 
                                   value="{{ old('valid_until', $comboOffer->valid_until ? $comboOffer->valid_until->format('Y-m-d') : '') }}">
                        </div>

                        {{-- Active --}}
                        <div class="form-group col-md-12">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="active" id="active"
                                    {{ $comboOffer->active ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">{{ __('Active') }}</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <a href="{{ route('combo_offers.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
    
    @if(isset($logs))
        @include('partials.pos_logs', ['logs' => $logs])
    @endif
@endsection

@push('script-page')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const comboType = document.getElementById('combo_type');
        const bogoFields = document.querySelectorAll('.bogo-fields');
        const tieredFields = document.querySelectorAll('.tiered-fields');
        const brandSelect = document.getElementById('brand_id');
        const subBrandSelect = document.getElementById('sub_brand_id');
        const productSelect = document.getElementById('product_ids');

        function toggleFields() {
            const type = comboType.value;
            bogoFields.forEach(f => f.classList.toggle('d-none', type !== 'bogo'));
            tieredFields.forEach(f => f.classList.toggle('d-none', type !== 'tiered_pricing'));
        }

        function filterSubBrands(brandId) {
            subBrandSelect.querySelectorAll('option').forEach(option => {
                if (option.value === '') {
                    return;
                }

                let show = true;

                if (brandId) {
                    const directBrandId = option.dataset.brandId || '';
                    show = directBrandId == brandId;
                }

                option.style.display = show ? '' : 'none';
            });

            if (brandId && subBrandSelect.value) {
                const selectedOption = subBrandSelect.options[subBrandSelect.selectedIndex];
                const directBrandId = selectedOption.dataset.brandId || '';

                if (directBrandId != brandId) {
                    subBrandSelect.value = '';
                }
            }

            filterProducts();
        }

        // Filter products by brand and sub-brand
        function filterProducts() {
            const brandId = brandSelect.value;
            const subBrandId = subBrandSelect.value;
            
            productSelect.querySelectorAll('option').forEach(option => {
                const optionBrandId = option.dataset.brandId || '';
                const optionSubBrandId = option.dataset.subBrandId || '';
                
                let show = true;
                
                if (brandId && optionBrandId != brandId) {
                    show = false;
                }
                
                if (subBrandId && optionSubBrandId != subBrandId) {
                    show = false;
                }
                
                option.style.display = show ? '' : 'none';
            });
        }

        comboType.addEventListener('change', toggleFields);
        brandSelect.addEventListener('change', function() {
            filterSubBrands(this.value);
        });
        subBrandSelect.addEventListener('change', filterProducts);

        // Trigger on page load with existing values
        toggleFields();
        filterSubBrands(brandSelect.value);
        filterProducts();
    });
</script>
@endpush
