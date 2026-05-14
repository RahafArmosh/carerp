@extends('layouts.admin')
@section('page-title')
    {{ __('Advance Sale Order Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('advance-saleorder.index') }}">{{ __('Advance Sale Orders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('advance-saleorder.edit', \Crypt::encrypt($advanceSaleOrder->id)) }}" class="btn btn-sm btn-primary">
            <i class="ti ti-pencil"></i> {{ __('Edit') }}
        </a>
        <form action="{{ route('advance-saleorder.destroy', \Crypt::encrypt($advanceSaleOrder->id)) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('{{ __('Are you sure you want to delete this advance sale order?') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">
                <i class="ti ti-trash"></i> {{ __('Delete') }}
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Advance Sale Order Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Advance SO No') }}</th>
                                    <td>{{ \Auth::user()->saleOrderNumberFormat($advanceSaleOrder->advance_sale_order_no) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ $advanceSaleOrder->customer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer TRN No') }}</th>
                                    <td>{{ $advanceSaleOrder->customer->customer_trn_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sales Order Date') }}</th>
                                    <td>{{ \Auth::user()->dateFormat($advanceSaleOrder->sales_order_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td><span class="badge bg-secondary">{{ strtoupper($advanceSaleOrder->status ?? '') }}</span></td>
                                </tr>
                                <tr>
                                    <th>{{ __('Linked Sale Orders') }}</th>
                                    <td>
                                        @forelse($advanceSaleOrder->saleOrders as $saleOrder)
                                            <a href="{{ route('saleorder.show', \Crypt::encrypt($saleOrder->id)) }}" class="btn btn-outline-primary btn-sm mb-1 me-1">
                                                {{ \Auth::user()->saleOrderNumberFormat($saleOrder->sale_order_no) }}
                                            </a>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Currency') }}</th>
                                    <td>{{ $advanceSaleOrder->currency->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Tax') }}</th>
                                    <td>
                                        @if($advanceSaleOrder->tax_id)
                                            @php
                                                $taxIds = explode(',', $advanceSaleOrder->tax_id);
                                                $taxNames = [];
                                                foreach ($taxIds as $taxId) {
                                                    $tax = $taxes->firstWhere('id', $taxId);
                                                    if ($tax) {
                                                        $taxNames[] = $tax->name . ' (' . $tax->rate . '%)';
                                                    }
                                                }
                                            @endphp
                                            {{ !empty($taxNames) ? implode(', ', $taxNames) : '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($advanceSaleOrder->exchange_rate ?? 1.0, 6) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created By') }}</th>
                                    <td>{{ $advanceSaleOrder->creator->name ?? '-' }}</td>
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
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('REQ QTY') }}</th>
                                    <th>{{ __('CONVERTED QTY') }}</th>
                                    <th>{{ __('Unit Price') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advanceSaleOrder->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->part_no }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td>{{ number_format($item->req_qty, 2) }}</td>
                                        <td>{{ number_format($item->converted_qty ?? 0, 2) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($item->unit_price) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No items found') }}</td>
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
