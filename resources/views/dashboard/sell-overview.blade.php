@extends('layouts.admin')
@section('page-title')
    {{ __('Sell Overview') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sell Overview') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('sell.overview.export', request()->all()) }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip"
            title="{{ __('Export to Excel') }}">
            <i class="ti ti-file-export"></i> {{ __('Export to Excel') }}
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
                        <form action="{{ route('sell.overview') }}" method="GET" id="sell_overview_form">
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
                                    <label for="date_from" class="form-label">{{ __('Date From') }}</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">{{ __('Date To') }}</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('sell.overview') }}" class="btn btn-danger">{{ __('Reset') }}</a>
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
                    <h5 class="mb-0">{{ __('Sell Overview') }}</h5>
                    <small class="text-muted">{{ __('Products sold from POS and Invoices grouped by product') }} — {{ __('defaults to the current month') }}</small>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Available Qty') }}</th>
                                    <th>{{ __('POS Sell Qty') }}</th>
                                    <th>{{ __('Invoice Sell Qty') }}</th>
                                    <th>{{ __('Total Sell Qty') }}</th>
                                    <th>{{ __('Sub Products Sold') }}</th>
                                    <th>{{ __('POS Count') }}</th>
                                    <th>{{ __('Invoice Count') }}</th>
                                    <th>{{ __('Avg POS Price') }}</th>
                                    <th>{{ __('Avg Invoice Price') }}</th>
                                    <th>{{ __('Avg Cost') }}</th>
                                    <th>{{ __('Total Sell') }}</th>
                                    <th>{{ __('Total Cost') }}</th>
                                    <th>{{ __('Total Sell Refund') }}</th>
                                    <th>{{ __('Total Cost Refund') }}</th>
                                    <th>{{ __('Net Sell') }}</th>
                                    <th>{{ __('Net Cost') }}</th>
                                    <th>{{ __('Profit/Loss') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalCostPerQty = 0;
                                    $totalSellAmount = 0;
                                @endphp
                                @forelse($sellOverview as $sell)
                                    @php
                                        $avgCost = $sell->avg_cost ?? 0;
                                        $posCount = $sell->pos_sell_qty ?? 0;
                                        $invoiceCount = $sell->invoice_sell_qty ?? 0;
                                        $totalCount = $posCount + $invoiceCount;
                                        $costPerQty = $avgCost * $totalCount;
                                        $totalSell = $sell->total_sell ?? 0;
                                        $rowSellRefund = $sell->total_sell_refund ?? 0;
                                        $rowCostRefund = $sell->total_cost_refund ?? 0;
                                        $rowNetSell = $sell->net_sell ?? ($totalSell - $rowSellRefund);
                                        $rowNetCost = $sell->net_cost ?? ($costPerQty - $rowCostRefund);
                                        
                                        // Accumulate totals
                                        $totalCostPerQty += $costPerQty;
                                        $totalSellAmount += $totalSell;
                                    @endphp
                                    <tr>
                                        <td>
                                            @php
                                                $parts = array_filter([
                                                    $sell->category_name ?? null,
                                                    $sell->sub_brand_name ?? null,
                                                    $sell->product_name ?? null
                                                ]);
                                                echo !empty($parts) ? implode('/', $parts) : ($sell->product_name ?? '-');
                                            @endphp
                                        </td>
                                        <td>{{ $sell->sku ?? '-' }}</td>
                                        <td>{{ $sell->brand_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ number_format($sell->available_qty ?? 0, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ number_format($sell->pos_sell_qty, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ number_format($sell->invoice_sell_qty, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ number_format($sell->total_sell_qty, 2) }}</span>
                                        </td>
                                        <td>{{ $sell->total_sub_product_count }}</td>
                                        <td>{{ $sell->pos_count }}</td>
                                        <td>{{ $sell->invoice_count }}</td>
                                        <td>{{ \Auth::user()->priceFormat($sell->avg_pos_price ?? 0) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($sell->avg_invoice_price ?? 0) }}</td>
                                        <td>
                                            @if(isset($sell->avg_cost) && $sell->avg_cost > 0)
                                                <span class="badge bg-secondary">{{ \Auth::user()->priceFormat($sell->avg_cost) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ \Auth::user()->priceFormat($totalSell) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">{{ \Auth::user()->priceFormat($costPerQty) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ \Auth::user()->priceFormat($rowSellRefund) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ \Auth::user()->priceFormat($rowCostRefund) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ \Auth::user()->priceFormat($rowNetSell) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">{{ \Auth::user()->priceFormat($rowNetCost) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $profit = $rowNetSell - $rowNetCost;
                                                $profitClass = $profit >= 0 ? 'bg-success' : 'bg-danger';
                                            @endphp
                                            <span class="badge {{ $profitClass }}">{{ \Auth::user()->priceFormat($profit) }}</span>
                                        </td>
                                        <td>
                                            <a href="#" 
                                               class="btn btn-sm btn-outline-primary show-sold-details" 
                                               data-product-id="{{ $sell->product_id }}"
                                               data-bs-toggle="tooltip" 
                                               title="{{ __('View Sold Sub Products') }}">
                                                <i class="ti ti-eye"></i> {{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="21">
                                            <div class="text-center">
                                                <h6>{{ __('No sales data available') }}</h6>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Summary Footer - appears below pagination -->
                    <div class="row mt-3 mb-2" style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end align-items-center gap-4">
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Total Cost Per Qty') }}:</strong>
                                    <span class="badge bg-danger ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($totalCostPerQty) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Total Sell') }}:</strong>
                                    <span class="badge bg-success ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($totalSellAmount) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Total Sell Refund') }}:</strong>
                                    <span class="badge bg-warning ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($refundTotals['total_sell_refund'] ?? 0) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Total Cost Refund') }}:</strong>
                                    <span class="badge bg-secondary ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($refundTotals['total_cost_refund'] ?? 0) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    @php
                                        $netSellAmount = $totalSellAmount - ($refundTotals['total_sell_refund'] ?? 0);
                                        $netCostAmount = $totalCostPerQty - ($refundTotals['total_cost_refund'] ?? 0);
                                        $totalProfit = $netSellAmount - $netCostAmount;
                                        $totalProfitClass = $totalProfit >= 0 ? 'bg-success' : 'bg-danger';
                                    @endphp
                                    <strong class="text-muted">{{ __('Net Sell') }}:</strong>
                                    <span class="badge bg-success ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($netSellAmount) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Net Cost') }}:</strong>
                                    <span class="badge bg-danger ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($netCostAmount) }}</strong>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong class="text-muted">{{ __('Profit/Loss') }}:</strong>
                                    <span class="badge {{ $totalProfitClass }} ms-2" style="font-size: 1em; padding: 8px 12px;">
                                        <strong>{{ \Auth::user()->priceFormat($totalProfit) }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for showing sold sub-products -->
    <div class="modal fade" id="soldDetailsModal" tabindex="-1" role="dialog" aria-labelledby="soldDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="soldDetailsModalLabel">{{ __('Sold Sub Products Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="soldDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $(document).ready(function() {
        // Handle click on "View" button to show sold sub-products
        $(document).on('click', '.show-sold-details', function(e) {
            e.preventDefault();
            var productId = $(this).data('product-id');
            var modal = $('#soldDetailsModal');
            
            // Show modal
            modal.modal('show');
            
            // Reset content and show loading
            $('#soldDetailsContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">{{ __("Loading...") }}</span></div></div>');
            
            // Load sold sub-products details
            $.ajax({
                url: '{{ route("sell.overview.details") }}',
                method: 'GET',
                data: {
                    product_id: productId,
                    @if(request('warehouse_id'))
                    warehouse_id: {{ request('warehouse_id') }},
                    @endif
                    @if(request('date_from'))
                    date_from: '{{ request('date_from') }}',
                    @endif
                    @if(request('date_to'))
                    date_to: '{{ request('date_to') }}',
                    @endif
                },
                success: function(response) {
                    if (response.html) {
                        $('#soldDetailsContent').html(response.html);
                    } else {
                        $('#soldDetailsContent').html('<div class="alert alert-warning">{{ __("No data available") }}</div>');
                    }
                },
                error: function(xhr) {
                    var errorMsg = '{{ __("Error loading details") }}';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseText) {
                        try {
                            var errorData = JSON.parse(xhr.responseText);
                            if (errorData.error) {
                                errorMsg = errorData.error;
                            }
                        } catch(e) {
                            // Keep default error message
                        }
                    }
                    $('#soldDetailsContent').html('<div class="alert alert-danger">' + errorMsg + '</div>');
                }
            });
        });
    });
</script>
@endpush
