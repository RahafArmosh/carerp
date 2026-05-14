@extends('layouts.admin')

@section('page-title')
    {{ __('Quotation') }} {{ $quotation->quotation_no }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('quotations.index') }}">{{ __('Quotations') }}</a></li>
    <li class="breadcrumb-item">{{ $quotation->quotation_no }}</li>
@endsection


@section('action-btn')
        <div class="float-end action-btn  ms-2"> 
            <a class="action-btn bg-success" href="{{ route('quotations.showexport', $quotation->id) }}" title="Export Excel"> 
                <i class="ti ti-file-export text-white"></i> </a> 
        </div>
        <div class="float-end action-btn bg-danger ms-2"> 
            <a class="btn btn-sm btn-danger" href="{{ route('quotations.export.pdf', $quotation->id) }}"  title="Export PDF"> 
                <i class="ti ti-file-export text-white"></i> </a> 
        </div>
        <div class="float-end action-btn bg-info ms-2"> 
            {{-- <a href="{{ route('quotations.convert_to_sale_order', $quotation->id) }}" title="Convert to SO"> 
                <i class="ti ti-refresh text-white"></i> </a>  --}}
            <a href="#" data-size="lg" data-url="{{ route('quotations.quotation2saleorder', $quotation->id) }}" data-ajax-popup="true"
                    data-bs-toggle="tooltip" title="{{ __('Convert') }}" data-title="{{ __('Convert to SO ') }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-refresh text-white"></i>
            </a>
        </div>

@endsection

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('Quotation No') }}</strong><br>
                        {{ $quotation->quotation_no }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Quotation Date') }}</strong><br>
                        {{ Auth::user()->dateFormat($quotation->quotation_date) }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Customer') }}</strong><br>
                        {{ $quotation->customer?->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Warehouse') }}</strong><br>
                        {{ $quotation->warehouse?->name ?? '-' }}
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <strong>{{ __('Delivery Location') }}</strong><br>
                        {{ $quotation->delivery_location ?? '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('Price Group') }}</strong><br>
                        {{ $quotation->price_group->name ?? '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('Currency') }}</strong><br>
                        {{ __('AED') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-border-style">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Item') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Requested Qty') }}</th>
                        <th>{{ __('Available Qty') }}</th>
                        <th>{{ __('Unit Price') }}</th>
                        <th>{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>

                @foreach ($quotation->items->where('parent_id', null) as $item)
                @if ($item->form_state != 'out of system')
                    <tr class="table-primary">
                        <td>
                            <strong>{{ $item->productService->name }}</strong>
                        </td>
                        
                        <td>{{ $item->productService->sku }}</td>
                        <td>{{ $item->re_quantity }}</td>
                        <td>{{ $item->av_quantity }}</td>
                        <td>{{ \Auth::user()->priceFormat($item->unit_price) }}</td>
                        <td>{{ \Auth::user()->priceFormat($item->total_price) }}</td>
                    </tr>
                @else
                    <tr class="table-danger">
                        <td>
                            <span class="badge bg-danger ms-2">{{ $item->partnumber }} {{ __('Not in Our System') }} </span>
                        </td>
                        <td>{{ $item->partnumber }}</td>
                        <td>{{ $item->re_quantity }}</td>
                        <td>{{ $item->av_quantity }}</td>
                        <td>{{ \Auth::user()->priceFormat($item->unit_price) }}</td>
                        <td>{{ \Auth::user()->priceFormat($item->total_price) }}</td>
                    </tr>
                @endif
                    
                    @foreach ($quotation->items->where('parent_id', $item->id) as $alt)
                        <tr class="table-light">
                            <td class="ps-4">
                                ↳ {{ $alt->productService->name }}
                                <span class="badge bg-warning ms-2">{{ __('Alternative') }}</span>
                            </td>
                            <td>{{ $alt->productService->sku }}</td>
                            <td>{{ $alt->re_quantity }}</td>
                            <td>{{ $alt->av_quantity }}</td>
                            <td>{{ Auth::user()->priceFormat($alt->unit_price) }}</td>
                            <td>{{ Auth::user()->priceFormat($alt->total_price) }}</td>
                        </tr>
                    @endforeach
                @endforeach

                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4 offset-md-8">
        <table class="table">
            <tr>
                <th>{{ __('Subtotal') }}</th>
                <td>{{ Auth::user()->priceFormat($quotation->subtotal) }}</td>
            </tr>
            <tr>
                <th>{{ __('Tax') }}</th>
                <td>{{ Auth::user()->priceFormat($quotation->tax_amount) }}</td>
            </tr>
            <tr>
                <th>{{ __('Total') }}</th>
                <td>
                    <strong>{{ Auth::user()->priceFormat($quotation->total) }}</strong>
                </td>
            </tr>
        </table>
    </div>
</div>

@endsection
