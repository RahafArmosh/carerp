@extends('layouts.admin')

@section('page-title', __('Create Pricing List'))

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('pricing-lists.index') }}">{{ __('Pricing Lists') }}</a></li>
<li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')

<form action="{{ route('pricing-lists.store') }}" method="POST">
@csrf
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row">

            {{-- Pricing Type --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Pricing Type') }}</label>
                <select name="pricing_list_type_id" class="form-control" required>
                    <option value="">{{ __('Select Type') }}</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Product --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Product / Part') }}</label>
                <select name="product_service_id" class="form-control select2" required>
                    <option value="">{{ __('Select Product') }}</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->sku }} - {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Warehouse --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('Warehouse') }}</label>
                <select name="warehouse_id" class="form-control" required>
                    <option value="">{{ __('Select Warehouse') }}</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">
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
        {{ __('Save Price') }}
    </button>
</div>

</form>
@endsection
