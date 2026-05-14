@extends('layouts.admin')
@section('page-title')
    {{ __('Pick List Status Logs') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('picklist.index') }}">{{ __('Pick Lists') }}</a></li>
    <li class="breadcrumb-item">{{ __('Status Logs') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('picklist.show', \Crypt::encrypt($pickList->id)) }}" class="btn btn-sm btn-primary">
            <i class="ti ti-arrow-left"></i> {{ __('Back to Pick List') }}
        </a>
    </div>
@endsection

@section('content')
    @php
        $statusLabelMap = [
            'draft' => __('Draft'),
            'under_picking' => __('Under Picking'),
            'partially_picked' => __('Partially Picked'),
            'picking_completed' => __('Picking Completed'),
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>{{ __('Pick List ID:') }}</strong> #{{ $pickList->id }}</div>
                        <div class="col-md-4">
                            <strong>{{ __('Sale Order:') }}</strong>
                            {{ \Auth::user()->saleOrderNumberFormat($pickList->saleOrder->sale_order_no ?? 0) }}
                        </div>
                        <div class="col-md-4"><strong>{{ __('Customer:') }}</strong> {{ $pickList->customer->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Status Change History') }}</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Changed At') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('From Status') }}</th>
                                    <th>{{ __('To Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statusLogs as $log)
                                    <tr>
                                        <td>{{ \Auth::user()->dateFormat($log->changed_at ?? $log->created_at) }}</td>
                                        <td>{{ $log->user->name ?? __('System') }}</td>
                                        <td>{{ $statusLabelMap[$log->old_status] ?? ucfirst(str_replace('_', ' ', $log->old_status ?? '-')) }}</td>
                                        <td>{{ $statusLabelMap[$log->new_status] ?? ucfirst(str_replace('_', ' ', $log->new_status ?? '-')) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">{{ __('No status changes logged yet.') }}</td>
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
