@extends('layouts.admin')

@section('page-title', __('Edit Pricing List'))

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('pricing-lists.index') }}">{{ __('Pricing Lists') }}</a></li>
<li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')

<form action="{{ route('pricing-lists.update', $pricingList->id) }}" method="POST">
@csrf
@method('PUT')

<div class="card">
    <div class="card-body">
        <div class="row">

            {{-- Pricing Type --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Pricing Type') }}</label>
                <select name="pricing_list_type_id" class="form-control" required>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}"
                            {{ $pricingList->pricing_list_type_id == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Product --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Product / Part') }}</label>
                <select name="product_service_id" class="form-control select2" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ $pricingList->product_service_id == $product->id ? 'selected' : '' }}>
                            {{ $product->sku }} - {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Warehouse --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Warehouse') }}</label>
                <select name="warehouse_id" class="form-control" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ $pricingList->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->code ?? $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Price --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Price') }}</label>
                <input type="number"
                       name="current_price"
                       step="0.01"
                       min="0"
                       class="form-control"
                       value="{{ $pricingList->current_price }}"
                       required>
            </div>

        </div>
    </div>
</div>

<div class="mt-3 text-end">
    <a href="{{ route('pricing-lists.index') }}" class="btn btn-secondary">
        {{ __('Cancel') }}
    </a>
    <button class="btn btn-primary">
        {{ __('Update Price') }}
    </button>
</div>

</form>
@endsection
