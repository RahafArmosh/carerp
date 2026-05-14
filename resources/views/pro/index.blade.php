@extends('layouts.admin')
@section('page-title')
    {{ __('Manage PROs') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('PRO') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create bill')
            {{-- <a href="{{ route('pro.export', request()->all()) }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-file-export"></i>
            </a> --}}
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
                data-url="{{ route('pro.file.import') }}" data-ajax-popup="true"
                data-title="{{ __('Import PRO Excel file') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-file-import"></i>
            </a>
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import & create missing stock') }}"
                data-url="{{ route('pro.file.import.create-subproducts') }}" data-ajax-popup="true"
                data-title="{{ __('Import PRO (Create missing stock)') }}" class="btn btn-sm btn-success">
                <i class="ti ti-file-import"></i> {{ __('Import (Create stock)') }}
            </a>
            <a href="#" data-size="lg" data-bs-toggle="tooltip" title="{{ __('Import items only') }}"
                data-url="{{ route('pro.file.import.items-only') }}" data-ajax-popup="true"
                data-title="{{ __('Import PRO (Items-only)') }}" class="btn btn-sm btn-warning">
                <i class="ti ti-file-import"></i> {{ __('Import (Items only)') }}
            </a>
            <a href="{{ route('pro.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('pro.index') }}" method="GET" id="frm_submit">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
                                                <select id="supplier_id" name="supplier_id" class="form-control select2">
                                                    <option value="">Select Supplier</option>
                                                    @foreach ($suppliers as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['supplier_id']) && $_GET['supplier_id'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="po_date" class="form-label">{{ __('PO Date') }}</label>
                                                <input type="date" name="po_date"
                                                    value="{{ isset($_GET['po_date']) ? $_GET['po_date'] : '' }}"
                                                    id="po_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="pro_no" class="form-label">{{ __('PRO No') }}</label>
                                                <input type="text" name="pro_no"
                                                    value="{{ isset($_GET['pro_no']) ? $_GET['pro_no'] : '' }}"
                                                    id="pro_no" class="form-control" placeholder="{{ __('Search PRO No') }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                                <select id="status" name="status" class="form-control select2">
                                                    <option value="">{{ __('All') }}</option>
                                                    <option value="open" {{ isset($_GET['status']) && $_GET['status'] == 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                                                    <option value="delivered" {{ isset($_GET['status']) && $_GET['status'] == 'delivered' ? 'selected' : '' }}>{{ __('Delivered') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div class="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('frm_submit').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('pro.index') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off"></i></span>
                                            </a>
                                        </div>
                                    </div>
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
                                    <th>{{ __('PRO No') }}</th>
                                    <th>{{ __('Advance SO') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th>{{ __('Supplier Code') }}</th>
                                    <th>{{ __('PO Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Supplier Proforma No') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th>{{ __('Total Amount') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pros as $pro)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('pro.show', $pro->id) }}"
                                                class="btn btn-outline-primary">{{ \Auth::user()->proNumberFormat($pro->pro_no) }}</a>
                                        </td>
                                        <td>
                                            @if($pro->advanceSaleOrder)
                                                <a href="{{ route('advance-saleorder.show', \Crypt::encrypt($pro->advanceSaleOrder->id)) }}" class="btn btn-outline-primary btn-sm">
                                                    {{ \Auth::user()->saleOrderNumberFormat($pro->advanceSaleOrder->advance_sale_order_no) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $pro->supplier_name ?? ($pro->supplier->name ?? '-') }}</td>
                                        <td>
                                            @php
                                                $supplierCode = '-';
                                                if ($pro->supplier_code) {
                                                    $supplierCode = $pro->supplier_code;
                                                } elseif ($pro->supplier && $pro->supplier->supplier_code) {
                                                    $supplierCode = $pro->supplier->supplier_code;
                                                } elseif ($pro->supplier_name) {
                                                    $vendor = \App\Models\Vender::where('created_by', \Auth::user()->creatorId())
                                                        ->where('name', $pro->supplier_name)
                                                        ->first();
                                                    if ($vendor && $vendor->supplier_code) {
                                                        $supplierCode = $vendor->supplier_code;
                                                    }
                                                }
                                            @endphp
                                            {{ $supplierCode }}
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($pro->po_date) }}</td>
                                        <td>
                                            <span class="{{ $pro->getStatusBadgeClass() }}">
                                                {{ ucfirst($pro->status ?? 'open') }}
                                            </span>
                                        </td>
                                        <td>{{ $pro->supplier_proforma_no ?? '-' }}</td>
                                        <td>{{ $pro->items->count() }}</td>
                                        <td>{{ Auth::user()->priceFormat($pro->getTotalAmount()) }}</td>
                                        <td>{{ Auth::user()->dateFormat($pro->created_at) }}</td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('pro.show', $pro->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="{{ route('pro.edit', $pro->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form id="delete-form-{{ $pro->id }}" action="{{ route('pro.destroy', $pro->id) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-confirm="Are you sure? This action can not be undone."
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $pro->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                </span>
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

