@extends('layouts.admin')

@section('page-title')
    {{ __('Sales Return Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.return.index') }}">{{ __('Sales Return') }}</a></li>
    <li class="breadcrumb-item">{{ __('Details') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('sales.return.ledger', $salesReturn->id) }}" class="btn btn-sm btn-primary me-2" target="_blank"
            data-bs-toggle="tooltip" title="{{ __('View Accounting') }}">
            <i class="ti ti-file-text"></i>
        </a>
        <a href="{{ route('sales.return.index') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-arrow-left"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>{{ __('Return #') }}:</strong> {{ $salesReturn->id }}</div>
                        <div class="col-md-3">
                            <strong>{{ __('Invoice') }}:</strong>
                            @if ($salesReturn->invoice)
                                {{ \Auth::user()->invoiceNumberFormat($salesReturn->invoice->invoice_id) }}
                            @else
                                -
                            @endif
                        </div>
                        <div class="col-md-3"><strong>{{ __('Customer') }}:</strong> {{ optional($salesReturn->customer)->name ?? '-' }}</div>
                        <div class="col-md-3"><strong>{{ __('Return Date') }}:</strong> {{ \Auth::user()->dateFormat($salesReturn->return_date) }}</div>
                    </div>

                    <div class="mb-3">
                        <strong>{{ __('Notes') }}:</strong>
                        <div class="text-muted">{{ $salesReturn->notes ?: '-' }}</div>
                    </div>

                    <div class="table-responsive">
                        @php $grandTotal = 0; @endphp
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Product No') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Unit Price') }}</th>
                                    <th>{{ __('Line Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesReturn->items as $item)
                                    @php
                                        $lineTotal = (float) $item->unit_price * (float) $item->quantity;
                                        $grandTotal += $lineTotal;
                                    @endphp
                                    <tr>
                                        <td>{{ optional($item->product)->name ?? '-' }}</td>
                                        <td>{{ optional($item->product)->sku ?? '-' }}</td>
                                        <td>{{ optional($item->subProduct)->product_no ?? '-' }}</td>
                                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                        <td>{{ \Auth::user()->priceFormat((float) $item->unit_price) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($lineTotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">{{ __('Total') }}</th>
                                    <th>{{ \Auth::user()->priceFormat($grandTotal) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
