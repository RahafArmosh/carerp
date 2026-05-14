@extends('layouts.admin')
@section('page-title')
    {{__('Manage Product Stock')}}
@endsection
@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Pos Product Stock')}}</li>
@endsection
@section('action-btn')
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-4">
            <form action="{{ route('pos.productsStock') }}" method="GET">
                <label for="warehouse" class="form-label">{{ __('Select Warehouse') }}</label>
                <select name="warehouse" id="warehouse" class="form-control select2" onchange="this.form.submit()">
                    <option value="">{{ __('-- All Warehouses --') }}</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" 
                            {{ request('warehouse') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Warehouse Name') }}</th>
                                    <th>{{ __('Sku') }}</th>
                                    <th>{{ __('Current Quantity') }}</th>
                                    <th>{{ __('Warehouse Price') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    <th>{{ __('QR code ') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grouped as $item)
                                    @if($item && (is_array($item) || is_object($item)))
                                        @php
                                            // Convert to array if it's an object
                                            $itemArray = is_array($item) ? $item : (array)$item;
                                            $productNo = isset($itemArray['product_no']) ? trim((string)$itemArray['product_no']) : null;
                                        @endphp
                                        <tr class="font-style">
                                            <td>{{ $itemArray['name'] ?? '-' }}</td>
                                            <td>{{ $itemArray['warehouse_name'] ?? '-' }}</td>
                                            
                                            <td>{{ $itemArray['product_no'] ?? '-' }}</td>
                                            <td>{{ $itemArray['total_quantity'] ?? '0' }}</td>
                                            <td>{{ $itemArray['sale_price'] ?? '0.00' }}</td>
                                            <td>{{ $itemArray['price'] ?? '0.00' }}</td>
                                            <td>
                                                @if(!empty($productNo) && is_string($productNo) && strlen($productNo) > 0)
                                                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($productNo, 'C128') }}" alt="barcode" />
                                                @else
                                                    <span class="text-muted">No barcode</span>
                                                @endif
                                            </td>
                                            
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
