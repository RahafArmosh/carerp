@extends('layouts.admin')

@section('page-title')
    {{ __('Create New Quotation') }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Create New Quotation') }}</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('quotations.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- BASIC INFORMATION --}}
                    <h6 class="mb-3 text-primary">{{ __('Quotation Information') }}</h6>

                    <div class="row">
                        {{-- Quotation Date (DEFAULT TODAY) --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Quotation Date') }}</label>
                            <input
                                type="date"
                                name="quotation_date"
                                class="form-control"
                                value="{{ date('Y-m-d') }}"
                                required
                            >
                        </div>

                        {{-- Customer (SEARCHABLE) --}}
                        <div class="col-md-8 mb-3">
                            <label class="form-label">{{ __('Customer') }}</label>
                            <select name="customer_id" id="customer_id"
                                    class="form-control searchable"
                                    required>
                                <option value="">{{ __('Search customer...') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- DELIVERY --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Delivery Location') }}</label>
                        <input type="text"
                               name="delivery_location"
                               class="form-control"
                               placeholder="{{ __('Customer site / address') }}">
                    </div>

                    {{-- SETTINGS --}}
                    <h6 class="mt-4 mb-3 text-primary">{{ __('Quotation Settings') }}</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" class="form-control" required>
                                <option value="">{{ __('Select Warehouse') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Price Group') }}</label>
                            <select name="price_group" class="form-control">
                                <option value="">{{ __('Default') }}</option>
                                @foreach($priceListTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Tax') }}</label>
                            <select name="tax_id" class="form-control">
                                <option value="">{{ __('Select Tax') }}</option>
                            @foreach(\App\Models\Tax::where('created_by', \Auth::user()->creatorId())->get() as $tax)
                                    <option value="{{ $tax->id }}">
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- ITEM INPUT METHOD --}}
                    <h6 class="mt-4 mb-3 text-primary">{{ __('Quotation Items') }}</h6>

                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="item_input_method" value="manual" checked>
                            <label class="form-check-label">
                                {{ __('Add Item by Item') }}
                            </label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio"
                                   name="item_input_method" value="upload">
                            <label class="form-check-label">
                                {{ __('Upload XLSX') }}
                            </label>
                        </div>
                    </div>

                    {{-- MANUAL ITEMS --}}
                    <div id="manual-items-container">
                        <div id="items-container"></div>

                        <button type="button" id="add-item"
                                class="btn btn-outline-secondary">
                            {{ __('Add Item') }}
                        </button>
                    </div>

                    {{-- XLSX UPLOAD --}}
                    <div id="upload-items-container" style="display:none;">
                        
                        <small>
                            <a href="{{ route('quotations.createexport') }}"
                            class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-download"></i> {{ __('Download Template') }}
                            </a>
                        </small>
                        <br>
                        <br>
                        
                        <input type="file" name="items_file"
                               class="form-control" accept=".xlsx,.xls">
                        
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary">
                        {{ __('Create Quotation') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
let itemIndex = 1;

// Toggle manual / upload
document.querySelectorAll('input[name="item_input_method"]').forEach(function(el){
    el.addEventListener('change', function() {
        if(this.value === 'manual') {
            document.getElementById('manual-items-container').style.display = '';
            document.getElementById('upload-items-container').style.display = 'none';
        } else {
            document.getElementById('manual-items-container').style.display = 'none';
            document.getElementById('upload-items-container').style.display = '';
        }
    });
});

// Add more manual items
document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const html = `<div class="item-row mb-3 row">
        <div class="col-md-5">
            <select name="items[${itemIndex}][product_service_id]" class="form-control" required>
                <option value="">{{ __('Select Product') }}</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="items[${itemIndex}][re_quantity]" value="1" min="1" class="form-control" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-danger remove-item">{{ __('Remove') }}</button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    itemIndex++;
});

// Remove item row
document.addEventListener('click', function(e) {
    if(e.target && e.target.classList.contains('remove-item')) {
        e.target.closest('.item-row').remove();
    }
});
</script>
@endpush
