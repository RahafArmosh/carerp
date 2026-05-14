@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Packing Lists') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Packing Lists') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('packinglist.index') }}" method="GET" id="packinglist_filter_form">
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
                                <div class="col-md-3">
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="form-control select2">
                                        @foreach ($statusOptions as $key => $value)
                                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto float-end ms-2 mt-4">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('packinglist.index') }}" class="btn btn-danger">{{ __('Reset') }}</a>
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
                                    <th>{{ __('Packing List No') }}</th>
                                    <th>{{ __('Sale Order') }}</th>
                                    <th>{{ __('Pick List') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Packed By') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Total Items') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packingLists as $packingList)
                                    <tr>
                                        <td>
                                            <a href="{{ route('packinglist.show', \Crypt::encrypt($packingList->id)) }}" class="btn btn-outline-primary">
                                                {{ \Auth::user()->packingListNumberFormat($packingList->packing_list_no) }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($packingList->saleOrder)
                                                <a href="{{ route('saleorder.show', \Crypt::encrypt($packingList->sale_order_id)) }}" class="text-primary">
                                                    {{ \Auth::user()->saleOrderNumberFormat($packingList->saleOrder->sale_order_no) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($packingList->pickList)
                                                <a href="{{ route('picklist.show', \Crypt::encrypt($packingList->pick_list_id)) }}" class="text-info">
                                                    #{{ $packingList->pickList->id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $packingList->customer->name ?? '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($packingList->packing_list_date) }}</td>
                                        <td>{{ $packingList->packer->name ?? '-' }}</td>
                                        <td>
                                            @php
                                                $statusClass = '';
                                                switch ($packingList->status) {
                                                    case 'draft':
                                                        $statusClass = 'bg-secondary';
                                                        break;
                                                    case 'packed':
                                                        $statusClass = 'bg-info';
                                                        break;
                                                    case 'shipped':
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                    case 'delivered':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                        break;
                                                }
                                            @endphp
                                            <span class="badge {{ $statusClass }} p-2 px-3 rounded">{{ ucfirst($packingList->status) }}</span>
                                        </td>
                                        <td>{{ $packingList->items->count() }}</td>
                                        <td>
                                            @can('create sale order')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('packinglist.show', \Crypt::encrypt($packingList->id)) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                                @if($packingList->status === 'draft')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{ route('packinglist.edit', \Crypt::encrypt($packingList->id)) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('No Packing Lists found.') }}</td>
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
