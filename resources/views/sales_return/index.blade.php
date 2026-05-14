@extends('layouts.admin')

@section('page-title')
    {{ __('Sales Return') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sales Return') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create invoice')
            <a href="{{ route('sales.return.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create Manual') }}">
                <i class="ti ti-plus"></i>
            </a>
            <a href="{{ route('sales.return.create.import') }}" class="btn btn-sm btn-info ms-1" data-bs-toggle="tooltip"
                title="{{ __('Create by Import') }}">
                <i class="ti ti-file-import"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Return Date') }}</th>
                                    <th>{{ __('Send Date') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    <th>{{ __('Total Qty') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesReturns as $salesReturn)
                                    <tr>
                                        <td>{{ $salesReturn->id }}</td>
                                        <td>
                                            @if ($salesReturn->invoice)
                                                {{ \Auth::user()->invoiceNumberFormat($salesReturn->invoice->invoice_id) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ optional($salesReturn->customer)->name ?? '-' }}</td>
                                        <td>{{ \Auth::user()->dateFormat($salesReturn->return_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($salesReturn->return_date) }}</td>
                                        <td>{{ $salesReturn->items->count() }}</td>
                                        <td>{{ number_format((float) $salesReturn->items->sum('quantity'), 2) }}</td>
                                        <td>{{ $salesReturn->notes ?? '-' }}</td>
                                        <td>
                                            <div class="action-btn bg-info ms-2">
                                                <a href="{{ route('sales.return.show', $salesReturn->id) }}"
                                                    class="mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Show') }}">
                                                    <i class="ti ti-eye text-white"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
