@extends('layouts.admin')

@section('page-title')
    {{ __('Warehouse Price Rules') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Price Rules') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-url="{{ route('pricelist.create') }}" data-size="lg"
           data-ajax-popup="true" data-bs-toggle="tooltip"
           title="{{ __('Create') }}" data-title="{{ __('Add Price Rule') }}"
           class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Add Warehouse Price Rule') }}</h5>
                <!-- Import from Excel button (sub product based) -->
                <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import from Excel (Sub Product No)') }}"
                   data-url="{{ route('price-rules.form') }}" data-ajax-popup="true"
                   data-title="{{ __('Import Price Rules Template') }}" class="btn btn-sm btn-success">
                    <i class="ti ti-file-import"></i> {{ __('Import from Excel') }}
                </a>
            </div>

            <form method="POST" action="{{ route('pricelist.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">

                        <!-- Warehouse -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-control select2" required>
                                <option value="">{{ __('Select Warehouse') }}</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Apply To -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Apply To') }}</label>
                            <select name="apply_to" id="apply_to" class="form-control select2" required>
                                <option value="">{{ __('Select Type') }}</option>
                                <option value="product">{{ __('Product') }}</option>
                                <option value="category">{{ __('Category') }}</option>
                                <option value="brand">{{ __('Brand') }}</option>
                                <option value="sub_brand">{{ __('Sub Brand') }}</option>
                            </select>
                        </div>

                        <!-- Base Price -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Base Price') }}</label>
                            <select name="base_price_source" id="base_price_source" class="form-control select2" required>
                                <option value="">{{ __('Select Base Price') }}</option>
                                <option value="sale">{{ __('Sale Price') }}</option>
                                <option value="purchase">{{ __('Purchase Price') }}</option>
                            </select>
                        </div>

                        <!-- Target -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Target') }}</label>
                            <select name="target_id" id="target_id" class="form-control select2" required>
                                <option value="">{{ __('Select Target') }}</option>
                            </select>
                        </div>

                        <!-- Price Mode -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Price Mode') }}</label>
                            <select name="price_mode" id="price_mode" class="form-control select2" required>
                                <option value="">{{ __('Select Price Mode') }}</option>
                                <option value="fixed">{{ __('Fixed Price') }}</option>
                                <option value="discount">{{ __('Discount %') }}</option>
                                <option value="formula">{{ __('Formula') }}</option>
                            </select>
                        </div>

                        <!-- Value -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Value') }}</label>
                            <input type="number" step="0.01" name="value" class="form-control" required>
                            <small class="form-text text-muted">
                                {{ __('For discount, use percent. For formula, enter multiplier.') }}
                            </small>
                        </div>

                        <!-- Apply .99 -->
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="apply_99" id="apply_99">
                                <label class="form-check-label" for="apply_99">
                                    {{ __('Apply .99 (e.g. 10 → 9.99)') }}
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer text-end">
                    <a href="javascript:history.back()" class="btn btn-light">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<!-- Select2 CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Select2 for all selects
    $('.select2').select2({
        theme: 'default',
        width: '100%',
        allowClear: true,
        placeholder: function() {
            return $(this).data('placeholder') || '{{ __("Select an option") }}';
        }
    });

    // Load targets dynamically when Apply To changes
    const applyToSelect = $('#apply_to');
    const targetSelect = $('#target_id');

    applyToSelect.on('change', function () {
        const type = $(this).val();
        
        if (!type) {
            targetSelect.empty().append('<option value="">{{ __("Select Target") }}</option>');
            targetSelect.val('').trigger('change');
            return;
        }
        
        // Destroy Select2 temporarily to update options
        targetSelect.select2('destroy');
        targetSelect.empty().append('<option value="">{{ __("Loading...") }}</option>');
        targetSelect.prop('disabled', true);

        fetch(`/pricelist/targets/${type}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                targetSelect.empty().append('<option value="">{{ __("Select Target") }}</option>');
                if (data && Array.isArray(data)) {
                    data.forEach(item => {
                        targetSelect.append($('<option></option>')
                            .attr('value', item.id)
                            .text(item.name));
                    });
                }
                targetSelect.prop('disabled', false);
                
                // Reinitialize Select2
                targetSelect.select2({
                    theme: 'default',
                    width: '100%',
                    allowClear: true,
                    placeholder: '{{ __("Select Target") }}'
                });
            })
            .catch(err => {
                console.error('Fetch error:', err);
                targetSelect.empty().append('<option value="">{{ __("Error loading options") }}</option>');
                targetSelect.prop('disabled', false);
                
                // Reinitialize Select2 even on error
                targetSelect.select2({
                    theme: 'default',
                    width: '100%',
                    allowClear: true,
                    placeholder: '{{ __("Select Target") }}'
                });
            });
    });

});
</script>
@endpush
