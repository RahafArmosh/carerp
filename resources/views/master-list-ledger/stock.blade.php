@extends('layouts.admin')

@section('page-title')
{{ __('Warehouse Stock') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('master-ledger.index') }}">{{ __('Warehouses') }}</a></li>
<li class="breadcrumb-item">{{ __('Stock') }}</li>
@endsection

@section('content')

{{-- Search / Filter --}}
<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <input 
                type="text"
                name="search"
                class="form-control me-2"
                placeholder="{{ __('Search SKU or Product') }}"
                value="{{ request('search') }}"
            >
            @if($warehouseId)
                <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
            @endif
            <button class="btn btn-primary"><i class="ti ti-search"></i> {{ __('Search') }}</button>
            <a href="{{ route('master-ledger.stock', ['warehouse_id' => $warehouseId]) }}" class="btn btn-secondary ms-2">{{ __('Reset') }}</a>
        </form>
    </div>
</div>

<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-body table-border-style">

<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>{{ __('SKU') }}</th>
            <th>{{ __('DESCRIPTION') }}</th>
            <th>{{ __('STOCK QTY') }}</th>

            <th>{{ __('Booked') }}</th>
            <th>{{ __('Free') }}</th>
            <th>{{ __('ON ORDER QTY') }}</th>
            <th>{{ __('AV COST') }}</th>
                     {{-- Add Sale Price columns dynamically for each pricing type --}}
            @foreach($priceTypes as $type)
                <th>{{ $type->name }} SP</th>
            @endforeach
            <th>{{ __('Sold') }}</th>

            <th>{{ __('Category') }}</th>
            <th>{{ __('Brand') }}</th>
            <th>{{ __('Purchase Price') }}</th>

   
            <th>{{ __('Custom Fields') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach($products as $product)
            @php
                $sub = $product->subProducts->first(); // only first sub-product
            @endphp
            <tr>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td>
                    <a href="#" class="badge bg-info" dactive>
                                {{ $product->free_qty + $product->booked_qty }}
                    </a>
                </td>

                <td>
                    <a href="{{ route('master-ledger.records', ['product' => $product->id, 'movement' => 'booked', 'warehouse_id' => $warehouseId]) }}" class="badge bg-warning">
                        {{ $product->booked_qty ?? 0 }}
                    </a>
                </td>
                
                <td>
                    <a href="{{ route('master-ledger.records', ['product' => $product->id, 'movement' => 'free', 'warehouse_id' => $warehouseId]) }}" class="badge bg-success">
                        {{ $product->free_qty ?? 0 }}
                    </a>
                </td>
                
                <td>
                    <a href="{{ route('master-ledger.onorder_details', ['product' => $product->id, 'warehouse_id' => $warehouseId]) }}" 
                    class="badge bg-danger">
                        {{ $product->remainingOrderedQuantity() ?? 0 }}
                    </a>
                </td>
                
                <td>
                    {{$product->avg_cost}}
                </td>
                
                {{-- Sale Price for each type --}}
                @foreach($priceTypes as $type)
                    @php
                        $price = $product->pricingLists
                            ->where('pricing_list_type_id', $type->id)
                            ->first()?->current_price ?? '-';
                    @endphp
                    <td>{{ $price }}</td>
                @endforeach
                
                <td>
                    <a href="{{ route('master-ledger.records', ['product' => $product->id, 'movement' => 'sold', 'warehouse_id' => $warehouseId]) }}" class="badge bg-danger">
                        {{ $product->sold_qty ?? 0 }}
                    </a>
                </td>
                
                
                <td>{{ $product->category->name ?? '-' }}</td>
                <td>{{ $product->brand->name ?? '-' }}</td>
                <td>{{ $sub->purchase_price ?? $product->purchase_price }}</td>

                
                <td>
                    @if($sub)
                        @foreach($sub->customFieldValues as $field)
                            @if($field->value)
                                <strong>{{ $field->customField->name }}:</strong> {{ $field->value }}<br>
                            @endif
                        @endforeach
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $products->links() }}
</div>

</div>
</div>
</div>
</div>
@endsection