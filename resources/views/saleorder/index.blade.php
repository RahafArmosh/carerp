@extends('layouts.admin')
@section('page-title')
    {{ __('Sale Orders') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sale Orders') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create sale order')
            <a href="{{ route('saleorder.import') }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip"
                title="{{ __('Import Sale Order') }}">
                <i class="ti ti-file-import"></i> {{ __('Import') }}
            </a>
            <a href="{{ route('saleorder.import.items-only') }}" class="btn btn-sm btn-warning ms-2" data-bs-toggle="tooltip"
                title="{{ __('Import Sale Order (Items only)') }}">
                <i class="ti ti-file-import"></i> {{ __('Import (Items only)') }}
            </a>
        @endcan
    </div>
@endsection

@push('css-page')
@endpush
@push('script-page')
@endpush
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('saleorder.index') }}" id="frm_submit">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                    <div class="btn-box">
                                        <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                        <select name="customer" id="customer" class="form-control select">
                                            @foreach ($customer as $id => $name)
                                                <option value="{{ $id }}"
                                                    {{ isset($_GET['customer']) && $_GET['customer'] == $id ? 'selected' : '' }}>
                                                    {{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                    <div class="btn-box">
                                        <label for="sales_order_date" class="form-label">{{ __('Date') }}</label>
                                        <input type="text" name="sales_order_date" id="sales_order_date"
                                            value="{{ isset($_GET['sales_order_date']) ? $_GET['sales_order_date'] : null }}"
                                            class="form-control month-btn" id="pc-daterangepicker-1">
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 ">
                                    <div class="btn-box">
                                        <label for="status" class="form-label">{{ __('Status') }}</label>
                                        <select name="status" id="status" class="form-control select">
                                            <option value="" selected>{{ __('Select Status') }}</option>
                                            @foreach ($statuses as $key => $value)
                                                <option value="{{ $key }}"
                                                    {{ isset($_GET['status']) && $_GET['status'] == $key ? 'selected' : '' }}>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-auto float-end ms-2 mt-4">
                                    <a href="#" class="btn btn-sm btn-primary"
                                        onclick="document.getElementById('frm_submit').submit(); return false;"
                                        data-bs-toggle="tooltip" data-original-title="{{ __('apply') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </a>
                                    <a href="{{ route('saleorder.index') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-off text-white "></i></span>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Sale Order No') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Sales Order Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    @if (Gate::check('create sale order'))
                                        <th width="10%">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($saleOrders as $saleOrder)
                                    <tr class="font-style">
                                        <td class="Id">
                                            @can('create sale order')
                                                <a href="{{ route('saleorder.show', \Crypt::encrypt($saleOrder->id)) }}" class="btn btn-outline-primary">
                                                    {{ Auth::user()->saleOrderNumberFormat($saleOrder->sale_order_no) }}
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-outline-primary">
                                                    {{ Auth::user()->saleOrderNumberFormat($saleOrder->sale_order_no) }}
                                                </a>
                                            @endcan
                                        </td>
                                        <td>{{ !empty($saleOrder->customer) ? $saleOrder->customer->name : '' }}</td>
                                        <td>{{ Auth::user()->dateFormat($saleOrder->sales_order_date) }}</td>
                                        <td>
                                            @if ($saleOrder->status == 'draft')
                                                <span class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __('CREATED') }}</span>
                                            @elseif($saleOrder->status == 'picking')
                                                <span class="status_badge badge bg-info p-2 px-3 rounded">{{ __('PICKING IN PROGRESS') }}</span>
                                            @elseif($saleOrder->status == 'packing_in_progress')
                                                <span class="status_badge badge bg-info p-2 px-3 rounded">{{ __('PACKING IN PROGRESS') }}</span>
                                            @elseif($saleOrder->status == 'packed')
                                                <span class="status_badge badge bg-primary p-2 px-3 rounded">{{ __('PACKED') }}</span>
                                            @elseif(in_array($saleOrder->status, ['invoiced', 'converted']))
                                                <span class="status_badge badge bg-success p-2 px-3 rounded">{{ __('INVOICED') }}</span>
                                            @elseif($saleOrder->status == 'shipped')
                                                <span class="status_badge badge bg-warning p-2 px-3 rounded">{{ __('SHIPPED') }}</span>
                                            @else
                                                <span class="status_badge badge bg-secondary p-2 px-3 rounded">{{ strtoupper($saleOrder->status ?? '') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ !empty($saleOrder->currency) ? $saleOrder->currency->name : '-' }}</td>
                                        <td>{{ $saleOrder->items->count() }}</td>
                                        @if (Gate::check('create sale order'))
                                            <td class="Action">
                                                @can('create sale order')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('saleorder.show', \Crypt::encrypt($saleOrder->id)) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('create sale order')
                                                    @if ($saleOrder->status != 'converted' && !$saleOrder->pickList)
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="{{ route('saleorder.edit', \Crypt::encrypt($saleOrder->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endcan
                                                @can('create sale order')
                                                    @if (!$saleOrder->isConverted())
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form action="{{ route('saleorder.destroy', \Crypt::encrypt($saleOrder->id)) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this sale order? This will unbook all sub-products and delete all related pick/packing lists.') }}')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                @endcan
                                                @if ($saleOrder->isConverted())
                                                    @can('show invoice')
                                                        <div class="action-btn bg-success ms-2">
                                                            <a href="{{ route('invoice.show', \Crypt::encrypt($saleOrder->invoice_id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('View Invoice') }}">
                                                                <i class="ti ti-file-invoice text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                @endif
                                            </td>
                                        @endif
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
