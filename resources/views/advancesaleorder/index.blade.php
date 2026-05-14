@extends('layouts.admin')
@section('page-title')
    {{ __('Advance Sale Orders') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Advance Sale Orders') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @if (Gate::check('create sale order') || Gate::check('create advance sale order'))
            <a href="{{ route('advance-saleorder.import') }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip"
                title="{{ __('Import Advance Sale Order') }}">
                <i class="ti ti-file-import"></i> {{ __('Import') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('advance-saleorder.index') }}" id="frm_submit">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                    <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer" id="customer" class="form-control select">
                                        @foreach ($customer as $id => $name)
                                            <option value="{{ $id }}" {{ request('customer') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                    <label for="sales_order_date" class="form-label">{{ __('Date') }}</label>
                                    <input type="text" name="sales_order_date" id="sales_order_date"
                                        value="{{ request('sales_order_date') }}" class="form-control month-btn">
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="form-control select">
                                        <option value="">{{ __('Select Status') }}</option>
                                        @foreach ($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto float-end ms-2 mt-4">
                                    <a href="#" class="btn btn-sm btn-primary"
                                        onclick="document.getElementById('frm_submit').submit(); return false;">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </a>
                                    <a href="{{ route('advance-saleorder.index') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-off text-white"></i></span>
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
                                    <th>{{ __('Advance SO No') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Sales Order Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    <th width="10%">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($advanceSaleOrders as $order)
                                    <tr class="font-style">
                                        <td>
                                            <a href="{{ route('advance-saleorder.show', \Crypt::encrypt($order->id)) }}" class="btn btn-outline-primary">
                                                {{ \Auth::user()->saleOrderNumberFormat($order->advance_sale_order_no) }}
                                            </a>
                                        </td>
                                        <td>{{ $order->customer->name ?? '' }}</td>
                                        <td>{{ \Auth::user()->dateFormat($order->sales_order_date) }}</td>
                                        <td>
                                            <span class="badge bg-secondary p-2 px-3 rounded">{{ strtoupper($order->status ?? '') }}</span>
                                        </td>
                                        <td>{{ $order->currency->name ?? '-' }}</td>
                                        <td>{{ $order->items->count() }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('advance-saleorder.show', \Crypt::encrypt($order->id)) }}" class="mx-3 btn btn-sm align-items-center" title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{ route('advance-saleorder.edit', \Crypt::encrypt($order->id)) }}" class="mx-3 btn btn-sm align-items-center" title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form action="{{ route('advance-saleorder.destroy', \Crypt::encrypt($order->id)) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this advance sale order?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="mx-3 btn btn-sm align-items-center" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
