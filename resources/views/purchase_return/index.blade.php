@extends('layouts.admin')

@section('page-title')
    {{ __('Purchase Return') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Purchase Return') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create purchase')
            <a href="{{ route('purchase.return.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create Manual') }}">
                <i class="ti ti-plus me-1"></i>{{ __('Create Manual') }}
            </a>
            <a href="{{ route('purchase.return.create.import') }}" class="btn btn-sm btn-info ms-1" data-bs-toggle="tooltip"
                title="{{ __('Create by Import') }}">
                <i class="ti ti-file-import me-1"></i>{{ __('Create by Import') }}
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
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Return Date') }}</th>
                                    <th>{{ __('Send Date') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    <th>{{ __('Total Qty') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseReturns as $purchaseReturn)
                                    <tr>
                                        <td>{{ $purchaseReturn->id }}</td>
                                        <td>
                                            @if ($purchaseReturn->bill)
                                                {{ \Auth::user()->billNumberFormat($purchaseReturn->bill->bill_id) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ optional($purchaseReturn->vender)->name ?? '-' }}</td>
                                        <td>{{ \Auth::user()->dateFormat($purchaseReturn->return_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($purchaseReturn->return_date) }}</td>
                                        <td>{{ $purchaseReturn->items->count() }}</td>
                                        <td>{{ number_format((float) $purchaseReturn->items->sum('quantity'), 2) }}</td>
                                        <td>{{ $purchaseReturn->notes ?? '-' }}</td>
                                        <td>
                                            <div class="action-btn bg-info ms-2">
                                                <a href="{{ route('purchase.return.show', $purchaseReturn->id) }}"
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
