@extends('layouts.admin')
@section('page-title')
    {{ __('Pick Lists') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Pick Lists') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        {{-- Add any action buttons here if needed --}}
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('picklist.index') }}" method="GET" id="picklist_filter_form">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" id="customer_id" class="form-control select2">
                                        @foreach ($customers as $id => $name)
                                            <option value="{{ $id }}" {{ request('customer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
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
                                <div class="col-auto float-end ms-2 mt-4">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('picklist.index') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Pick List ID') }}</th>
                                    <th>{{ __('Sale Order') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Pick List Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Assigned To') }}</th>
                                    <th>{{ __('Assign Status') }}</th>
                                    <th>{{ __('Picked By') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pickLists as $pickList)
                                    <tr>
                                        <td> <a href="{{ route('picklist.edit', \Crypt::encrypt($pickList->id)) }}"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="tooltip" title="{{ __('Edit Picking') }}">
                                            #{{ $pickList->id }}
                                        </a></td>
                                        <td>
                                            <a href="{{ route('saleorder.show', \Crypt::encrypt($pickList->sales_order_id)) }}" class="btn btn-outline-primary btn-sm">
                                                {{ \Auth::user()->saleOrderNumberFormat($pickList->saleOrder->sale_order_no ?? 0) }}
                                            </a>
                                        </td>
                                        <td>{{ $pickList->customer->name ?? '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($pickList->pick_list_date) }}</td>
                                        <td>
                                            @php
                                                $status = $pickList->status ?? 'draft';
                                                $statusLabelMap = [
                                                    'draft' => __('Draft'),
                                                    'under_picking' => __('Under Picking'),
                                                    'partially_picked' => __('Partially Picked'),
                                                    'picking_completed' => __('Picking Completed'),
                                                ];
                                                $statusColorMap = [
                                                    'draft' => 'bg-secondary',
                                                    'under_picking' => 'bg-info',
                                                    'partially_picked' => 'bg-warning',
                                                    'picking_completed' => 'bg-success',
                                                ];
                                                $statusLabel = $statusLabelMap[$status] ?? ucfirst($status);
                                                $statusColor = $statusColorMap[$status] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $statusColor }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>{{ $pickList->assignedUser->name ?? '-' }}</td>
                                        <td>
                                            @if($pickList->assigned_to)
                                                <span class="badge bg-success">{{ __('Assigned') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Not Assigned') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $pickList->picker->name ?? '-' }}</td>
                                        <td>{{ $pickList->items->count() }}</td>
                                        <td>
                                            @can('create sale order')
                                                <div class="d-flex">
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('picklist.show', \Crypt::encrypt($pickList->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('View / Assign') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn bg-dark ms-2">
                                                        <a href="{{ route('picklist.status-logs', \Crypt::encrypt($pickList->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Status Logs') }}">
                                                            <i class="ti ti-history text-white"></i>
                                                        </a>
                                                    </div>
                                                    @if (!$pickList->packingList && $pickList->assigned_to)
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('picklist.edit', \Crypt::encrypt($pickList->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit Picking') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                    @endif
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">{{ __('No Pick Lists found.') }}</td>
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

@push('script-page')
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@endpush
