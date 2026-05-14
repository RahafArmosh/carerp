@extends('layouts.admin')
@section('page-title')
    {{ __('PRO Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pro.index') }}">{{ __('PRO') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('edit bill')
            <a href="{{ route('pro.edit', $pro->id) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i> {{ __('Edit') }}
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('PRO Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('PRO No') }}</th>
                                    <td>{{ \Auth::user()->proNumberFormat($pro->pro_no) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Advance SO') }}</th>
                                    <td>
                                        @if($pro->advanceSaleOrder)
                                            <a href="{{ route('advance-saleorder.show', \Crypt::encrypt($pro->advanceSaleOrder->id)) }}" class="btn btn-outline-primary btn-sm">
                                                {{ \Auth::user()->saleOrderNumberFormat($pro->advanceSaleOrder->advance_sale_order_no) }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Name') }}</th>
                                    <td>{{ $pro->supplier_name ?? ($pro->supplier->name ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Code') }}</th>
                                    <td>
                                        @php
                                            $supplierCode = '-';
                                            if ($pro->supplier_code) {
                                                $supplierCode = $pro->supplier_code;
                                            } elseif ($pro->supplier && $pro->supplier->supplier_code) {
                                                $supplierCode = $pro->supplier->supplier_code;
                                            } elseif ($pro->supplier_name) {
                                                $vendor = \App\Models\Vender::where('created_by', \Auth::user()->creatorId())
                                                    ->where('name', $pro->supplier_name)
                                                    ->first();
                                                if ($vendor && $vendor->supplier_code) {
                                                    $supplierCode = $vendor->supplier_code;
                                                }
                                            }
                                        @endphp
                                        {{ $supplierCode }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('PO Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($pro->po_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        <span class="{{ $pro->getStatusBadgeClass() }}">
                                            {{ ucfirst($pro->status ?? 'open') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Supplier Proforma No') }}</th>
                                    <td>{{ $pro->supplier_proforma_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Proforma Date') }}</th>
                                    <td>{{ $pro->supplier_proforma_date ? Auth::user()->dateFormat($pro->supplier_proforma_date) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Our Order Ref') }}</th>
                                    <td>{{ $pro->our_order_ref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Ref') }}</th>
                                    <td>{{ $pro->supplier_ref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ETA Date') }}</th>
                                    <td>{{ $pro->eta_date ? Auth::user()->dateFormat($pro->eta_date) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Currency') }}</th>
                                    <td>
                                        @if($pro->currency)
                                            {{ $pro->currency->name }} ({{ $pro->currency->code }})
                                        @else
                                            {{ Auth::user()->currencySymbol() }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($pro->exchange_rate ?? 1.0, 6) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>{{ __('Items') }}</h5>
                </div>
                <div class="card-body">
                    @php
                        // Get currency symbol once for all price formatting
                        $currencySymbol = $pro->currency ? $pro->currency->symbol : Auth::user()->currencySymbol();
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Order Qty') }}</th>
                                    <th>{{ __('Supplied Qty') }}</th>
                                    <th>{{ __('Remaining Qty') }}</th>
                                    <th>{{ __('Unit Price') }}</th>
                                    <th>{{ __('Total Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pro->items as $item)
                                    <tr>
                                        <td>{{ $item->part_no ?? '-' }}</td>
                                        <td>
                                            @php
                                                $product = $item->matchedProduct ?? null;
                                                $displayText = $item->description ?? '-';
                                                
                                                if ($product) {
                                                    $parts = [];
                                                    
                                                    // Add category
                                                    if ($product->category && $product->category->name) {
                                                        $parts[] = $product->category->name;
                                                    }
                                                    
                                                    // Add brand
                                                    if ($product->brand && $product->brand->name) {
                                                        $parts[] = $product->brand->name;
                                                    }
                                                    
                                                    // Add sub-brand
                                                    if ($product->subBrand && $product->subBrand->name) {
                                                        $parts[] = $product->subBrand->name;
                                                    }
                                                    
                                                    // Add product name
                                                    if ($product->name) {
                                                        $parts[] = $product->name;
                                                    }
                                                    
                                                    if (!empty($parts)) {
                                                        $displayText = implode(' / ', $parts);
                                                    }
                                                }
                                            @endphp
                                            {{ $displayText }}
                                        </td>
                                        <td>{{ $item->order_qty }}</td>
                                        <td>{{ $item->supplied_qty }}</td>
                                        <td>{{ $item->remaining_qty }}</td>
                                        <td>{{ Auth::user()->priceFormatCurr($item->unit_price, $currencySymbol) }}</td>
                                        <td>{{ Auth::user()->priceFormatCurr($item->total_amount, $currencySymbol) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-end">{{ __('Total') }}</th>
                                    <th>{{ Auth::user()->priceFormatCurr($pro->getTotalAmount(), $currencySymbol) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="{{ route('pro.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
            </div>
        </div>
    </div>
@endsection

