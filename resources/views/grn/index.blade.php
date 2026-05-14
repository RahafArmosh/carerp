@extends('layouts.admin')
@section('page-title')
    {{ __('Manage GRNs') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('GRN') }}</li>
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
                        <form action="{{ route('grn.index') }}" method="GET" id="frm_submit">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        @if($users)
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="assigned_to" class="form-label">{{ __('Assigned User') }}</label>
                                                <select id="assigned_to" name="assigned_to" class="form-control select2">
                                                    <option value="">{{ __('All Users') }}</option>
                                                    @foreach ($users as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['assigned_to']) && $_GET['assigned_to'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        @if($suppliers)
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
                                                <select id="supplier_id" name="supplier_id" class="form-control select2">
                                                    <option value="">{{ __('All Suppliers') }}</option>
                                                    @foreach ($suppliers as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['supplier_id']) && $_GET['supplier_id'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                                <select id="status" name="status" class="form-control select2">
                                                    <option value="">{{ __('All Statuses') }}</option>
                                                    <option value="draft" {{ isset($_GET['status']) && $_GET['status'] == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                                    <option value="received" {{ isset($_GET['status']) && $_GET['status'] == 'received' ? 'selected' : '' }}>{{ __('Partially Received') }}</option>
                                                    <option value="manually_received" {{ isset($_GET['status']) && $_GET['status'] == 'manually_received' ? 'selected' : '' }}>{{ __('Manually Received') }}</option>
                                                    <option value="completed" {{ isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                                                    <option value="cancelled" {{ isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                                <input type="date" name="start_date"
                                                    value="{{ isset($_GET['start_date']) ? $_GET['start_date'] : '' }}"
                                                    id="start_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                                <input type="date" name="end_date"
                                                    value="{{ isset($_GET['end_date']) ? $_GET['end_date'] : '' }}"
                                                    id="end_date" class="form-control">
                                            </div>
                                        </div>
                                        @if($asns)
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="asn_id" class="form-label">{{ __('ASN') }}</label>
                                                <select id="asn_id" name="asn_id" class="form-control select2">
                                                    <option value="">{{ __('All ASNs') }}</option>
                                                    @foreach ($asns as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['asn_id']) && $_GET['asn_id'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="supplier_inv_no" class="form-label">{{ __('Supplier Inv No') }}</label>
                                                <input type="text" id="supplier_inv_no" name="supplier_inv_no" class="form-control"
                                                       value="{{ request('supplier_inv_no') }}" placeholder="{{ __('Search by Supplier Invoice') }}">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="btn-box">
                                                <label for="box_no" class="form-label">{{ __('Box No') }}</label>
                                                <select id="box_no" name="box_no" class="form-control select2">
                                                    <option value="">{{ __('All Box Numbers') }}</option>
                                                    @foreach($boxNos ?? [] as $boxNo => $label)
                                                        <option value="{{ $boxNo }}" {{ request('box_no') == $boxNo ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
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
                                            <a href="{{ route('grn.index') }}" class="btn btn-sm btn-danger"
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

    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('GRN No') }}</th>
                                    <th>{{ __('ASN No') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th>{{ __('Supplier Inv No') }}</th>
                                    <th>{{ __('GRN Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Bill No') }}</th>
                                    <th>{{ __('Assigned To') }}</th>
                                    <th>{{ __('Total Qty') }}</th>
                                    <th>{{ __('Received Qty') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grns as $grn)
                                    <tr>
                                        <td>
                                            <a href="{{ route('grn.show', Crypt::encrypt($grn->id)) }}" class="btn btn-outline-primary btn-sm">
                                                GRN{{ str_pad($grn->grn_no, 5, '0', STR_PAD_LEFT) }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($grn->asn)
                                                <a href="{{ route('asn.show', $grn->asn->id) }}" class="btn btn-outline-info btn-sm">
                                                    {{ Auth::user()->asnNumberFormat($grn->asn->asn_no) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $grn->supplier_name ?? ($grn->supplier ? $grn->supplier->name : '-') }}</td>
                                        <td>{{ $grn->asn?->supplier_inv_no ?? '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($grn->grn_date) }}</td>
                                        <td>
                                            <span class="{{ $grn->getStatusBadgeClass() }}">
                                                {{ $grn->getStatusLabelAttribute() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($grn->bill_id && $grn->bill)
                                                <a href="{{ route('bill.show', Crypt::encrypt($grn->bill_id)) }}" class="btn btn-outline-success btn-sm">
                                                    {{ Auth::user()->billNumberFormat($grn->bill->bill_id) }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $grn->assignedUser ? $grn->assignedUser->name : '-' }}</td>
                                        <td>{{ number_format((float)($grn->total_qty ?? 0), 2) }}</td>
                                        <td>{{ number_format((float)($grn->total_received_qty ?? 0), 2) }}</td>
                                        <td>{{ Auth::user()->priceFormat((float)($grn->total_amount ?? 0)) }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('grn.show', Crypt::encrypt($grn->id)) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                @if($grn->bill_id)
                                                    <a href="{{ route('bill.show', Crypt::encrypt($grn->bill_id)) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="{{ __('View Bill') }}">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $grns->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

