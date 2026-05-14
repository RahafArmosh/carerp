@extends('layouts.admin')
@section('page-title')
    {{ __('Item Master Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('report.product.stock.report') }}">{{ __('Stock Reports') }}</a></li>
    <li class="breadcrumb-item">{{ __('Item Master') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('report.item.master') }}" method="GET" id="item_master_filter">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="q" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Part No / Product Name / SKU') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                    <select name="category_id" class="form-control select2">
                                        <option value="">{{ __('All Categories') }}</option>
                                        @foreach ($categories as $id => $cat)
                                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="product_id" class="form-label">{{ __('Product') }}</label>
                                    <select name="product_id" class="form-control select2">
                                        <option value="">{{ __('All Products') }}</option>
                                        @foreach ($products as $id => $prod)
                                            <option value="{{ $id }}" {{ request('product_id') == $id ? 'selected' : '' }}>{{ $prod }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" class="form-control select2">
                                        <option value="">{{ __('All Warehouses') }}</option>
                                        @foreach ($warehouses as $id => $wh)
                                            <option value="{{ $id }}" {{ request('warehouse_id') == $id ? 'selected' : '' }}>{{ $wh }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter"></i> {{ __('Filter') }}
                                        </button>
                                        <a href="{{ route('report.item.master') }}" class="btn btn-secondary">
                                            <i class="ti ti-refresh"></i> {{ __('Reset') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Product Name') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Stock Qty') }}</th>
                                    <th>{{ __('Reserved Qty') }}</th>
                                    <th>{{ __('Free Qty') }}</th>
                                    <th>{{ __('AV Cost') }}</th>
                                    <th>{{ __('Sell Price') }}</th>
                                    <th>{{ __('Sell Price with VAT') }}</th>
                                    <th>{{ __('Sold Price') }}</th>
                                    @foreach ($customFields as $customField)
                                        <th>{{ $customField->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reportData as $item)
                                    <tr>
                                        <td>
                                            <a href="{{ route('stock_movements.index', ['product_id' => $item['product_id']]) }}" 
                                               class="btn btn-outline-primary btn-sm" 
                                               data-bs-toggle="tooltip" 
                                               title="{{ __('View Stock Movement for this Product') }}">
                                                {{ $item['part_no'] }}
                                            </a>
                                        </td>
                                        <td>{{ $item['product_name'] }}</td>
                                        <td>{{ $item['sku'] }}</td>
                                        <td><strong>{{ number_format($item['stock_qty'], 2) }}</strong></td>
                                        <td><strong>{{ number_format($item['reserved_qty'], 2) }}</strong></td>
                                        <td><strong>{{ number_format($item['free_qty'], 2) }}</strong></td>
                                        <td>{{ \Auth::user()->priceFormat($item['avg_cost']) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($item['sell_price']) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($item['sell_price_with_vat']) }}</td>
                                        <td>
                                            @if($item['sold_price'])
                                                <span class="badge bg-{{ $item['sold_source'] == 'Invoice' ? 'info' : 'success' }}">
                                                    {{ \Auth::user()->priceFormat($item['sold_price']) }} 
                                                    <small>({{ $item['sold_source'] }})</small>
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @foreach ($customFields as $customField)
                                            <td>
                                                {{ $customFieldValues[$item['sub_product_id']][$customField->id] ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 10 + count($customFields) }}" class="text-center text-dark">
                                            <p>{{ __('No Data Found') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

