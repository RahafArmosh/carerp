@extends('layouts.admin')
@section('page-title')
    {{ __('Stock Overview') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Stock Overview') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('stock.overview.export', request()->all()) }}" class="btn btn-sm btn-success me-2" data-bs-toggle="tooltip"
            title="{{ __('Export to Excel') }}">
            <i class="ti ti-file-export"></i> {{ __('Export') }}
        </a>
        <a href="{{ route('subproduct.stock_report') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('View Full Stock Report') }}">
            <i class="ti ti-file-report"></i> {{ __('View Full Report') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('stock.overview') }}" method="GET" id="stock_overview_form">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="q" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Product / SKU / Brand') }}">
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
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('stock.overview') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Stock Overview') }}</h5>
                    <small class="text-muted">{{ __('Stock data grouped by product') }}</small>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Sell Qty') }}</th>
                                    <th>{{ __('Total Quantity') }}</th>
                                    <th>{{ __('Free Quantity') }}</th>
                                    <th>{{ __('Booked Quantity') }}</th>
                                    <th>{{ __('Sub Products') }}</th>
                                    <th>{{ __('Avg Sale Price') }}</th>
                                    <th>{{ __('Avg Purchase Price') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockOverview as $stock)
                                    <tr>
                                        <td>
                                            @php
                                                $parts = array_filter([
                                                    $stock->category_name ?? null,
                                                    // $stock->brand_name ?? null,
                                                    $stock->sub_brand_name ?? null,
                                                    $stock->product_name ?? null
                                                ]);
                                                $productDisplay = !empty($parts) ? implode('/', $parts) : ($stock->product_name ?? '-');
                                                // Add SKU if available
                                                if (!empty($stock->sku)) {
                                                    $productDisplay = $stock->sku . ' - ' . $productDisplay;
                                                }
                                                echo $productDisplay;
                                            @endphp
                                        </td>
                                        <td>{{ $stock->sku ?? '-' }}</td>
                                        <td>{{ $stock->brand_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ number_format($stock->sell_qty ?? 0, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ number_format($stock->total_quantity, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ number_format($stock->free_quantity, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ number_format($stock->booked_quantity, 2) }}</span>
                                        </td>
                                        <td>{{ $stock->sub_product_count }}</td>
                                        <td>{{ \Auth::user()->priceFormat($stock->avg_sale_price ?? 0) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($stock->avg_purchase_price ?? 0) }}</td>
                                        <td>
                                            <a href="{{ route('subProducts', $stock->product_id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" 
                                               title="{{ __('View Sub Products') }}">
                                                <i class="ti ti-eye"></i> {{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11">
                                            <div class="text-center">
                                                <h6>{{ __('No stock data available') }}</h6>
                                            </div>
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

