@extends('layouts.admin')
@section('page-title')
    {{ __('Manage ASNs') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('ASN') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('manage bill')
            <a href="{{ route('asn.export', request()->query()) }}" class="btn btn-sm btn-success ms-2" data-bs-toggle="tooltip"
                title="{{ __('Export to Excel') }}" data-original-title="{{ __('Export to Excel') }}">
                <i class="ti ti-file-export"></i>
            </a>
        @endcan
        @can('create bill')
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
                data-url="{{ route('asn.file.import') }}" data-ajax-popup="true"
                data-title="{{ __('Import ASN Excel file') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-file-import"></i>
            </a>
            <a href="#" data-size="lg" data-bs-toggle="tooltip" title="{{ __('Import items only') }}"
                data-url="{{ route('asn.file.import.items-only') }}" data-ajax-popup="true"
                data-title="{{ __('Import ASN (Items-only)') }}" class="btn btn-sm btn-warning ms-2">
                <i class="ti ti-file-import"></i> {{ __('Import (Items only)') }}
            </a>
            {{-- <a href="{{ route('asn.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a> --}}
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
                        @if (session('asn_import_error_report_token'))
                            <div class="mt-2">
                                <a href="{{ route('asn.import.download-errors', ['token' => session('asn_import_error_report_token')]) }}" class="btn btn-sm btn-outline-danger" target="_blank" rel="noopener noreferrer">
                                    <i class="ti ti-download"></i> {{ __('Download error report (Excel)') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('asn.index') }}" method="GET" id="frm_submit">
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
                                                <label for="asn_date" class="form-label">{{ __('ASN Date') }}</label>
                                                <input type="date" name="asn_date"
                                                    value="{{ isset($_GET['asn_date']) ? $_GET['asn_date'] : '' }}"
                                                    id="asn_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="asn_no" class="form-label">{{ __('ASN No') }}</label>
                                                <input type="text" name="asn_no"
                                                    value="{{ isset($_GET['asn_no']) ? $_GET['asn_no'] : '' }}"
                                                    id="asn_no" class="form-control" placeholder="{{ __('Search ASN No') }}">
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
                                            <a href="{{ route('asn.index') }}" class="btn btn-sm btn-danger"
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
                                    <th>{{ __('ASN No') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th>{{ __('Supplier Code') }}</th>
                                    <th>{{ __('Supplier Inv No') }}</th>
                                    <th>{{ __('ASN Date') }}</th>
                                    <th>{{ __('Container No') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Created By') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="15%">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($asns as $asn)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('asn.show', $asn->id) }}"
                                                class="btn btn-outline-primary">{{ \Auth::user()->asnNumberFormat($asn->asn_no) }}</a>
                                        </td>
                                        <td>{{ $asn->supplier_name ?? ($asn->supplier->name ?? '-') }}</td>
                                        <td>
                                            @php
                                                $supplierCode = '-';
                                                if ($asn->supplier && $asn->supplier->supplier_code) {
                                                    $supplierCode = $asn->supplier->supplier_code;
                                                } elseif ($asn->supplier_name) {
                                                    $vendor = \App\Models\Vender::where('created_by', \Auth::user()->creatorId())
                                                        ->where('name', $asn->supplier_name)
                                                        ->first();
                                                    if ($vendor && $vendor->supplier_code) {
                                                        $supplierCode = $vendor->supplier_code;
                                                    } elseif ($asn->supplier_code) {
                                                        $supplierCode = $asn->supplier_code;
                                                    }
                                                } elseif ($asn->supplier_code) {
                                                    $supplierCode = $asn->supplier_code;
                                                }
                                            @endphp
                                            {{ $supplierCode }}
                                        </td>
                                        <td>{{ $asn->supplier_inv_no ?? '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($asn->asn_date) }}</td>
                                        <td>{{ $asn->container_no ?? '-' }}</td>
                                        <td>{{ $asn->items->whereNull('split_from_asn_item_id')->count() }}</td>
                                        <td>
                                            <span class="{{ $asn->getStatusBadgeClass() }}">{{ $asn->status_label }}</span>
                                        </td>
                                        <td>
                                            @if($asn->bill_id)
                                                @php $encBillId = \Illuminate\Support\Facades\Crypt::encrypt($asn->bill_id); @endphp
                                                <a href="{{ route('bill.show', $encBillId) }}" class="badge bg-success">{{ __('View Bill') }}</a>
                                            @else
                                                <span class="badge bg-secondary">{{ __('No Bill') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ Auth::user()->priceFormat($asn->getTotalPrice()) }}</td>
                                        <td>{{ $asn->creator?->name ?? '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($asn->created_at) }}</td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('asn.show', $asn->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('manage bill')
                                                        <div class="action-btn bg-success ms-2">
                                                            <a href="{{ route('asn.single.export', $asn->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Export to Excel') }}">
                                                                <i class="ti ti-file-export text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('create bill')
                                                        @if(!$asn->bill_id)
                                                            @if($asn->status === 'fully_received' || $asn->status === 'manually_received')
                                                                <div class="action-btn bg-success ms-2">
                                                                    <a href="{{ route('asn.show', $asn->id) }}"
                                                                        class="mx-3 btn btn-sm align-items-center"
                                                                        data-bs-toggle="tooltip" title="{{ __('Convert to Bill') }}">
                                                                        <i class="ti ti-file-invoice text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @else
                                                                <div class="action-btn bg-secondary ms-2">
                                                                    <span class="mx-3 btn btn-sm align-items-center"
                                                                        data-bs-toggle="tooltip" title="{{ __('ASN must be fully received before converting to Bill. Current status: :status', ['status' => $asn->status_label ?? $asn->status ?? 'created']) }}">
                                                                        <i class="ti ti-file-invoice text-white"></i>
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="action-btn bg-info ms-2">
                                                                <a href="{{ route('bill.show', \Illuminate\Support\Facades\Crypt::encrypt($asn->bill_id)) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('View Bill') }}">
                                                                    <i class="ti ti-file-invoice text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="{{ route('asn.edit', $asn->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                        @if($asn->status !== 'created')
                                                            <div class="action-btn bg-success ms-2">
                                                                <a href="{{ route('asn.grn', $asn->id) }}"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('GRN') }}">
                                                                    <i class="ti ti-clipboard-check text-white"></i>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-secondary ms-2">
                                                                <a href="javascript:void(0)"
                                                                    class="mx-3 btn btn-sm align-items-center disabled"
                                                                    data-bs-toggle="tooltip" title="{{ __('GRN is only available when ASN status is not Open') }}">
                                                                    <i class="ti ti-clipboard-check text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    @endcan
                                                    @can('delete bill')
                                                        @if($asn->status === 'created')
                                                            <div class="action-btn bg-danger ms-2">
                                                                <form id="delete-form-{{ $asn->id }}" action="{{ route('asn.destroy', $asn->id) }}" method="POST" style="display: inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        data-confirm="Are you sure? This action can not be undone."
                                                                        data-confirm-yes="document.getElementById('delete-form-{{ $asn->id }}').submit();">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <div class="action-btn bg-secondary ms-2">
                                                                <a href="javascript:void(0)"
                                                                    class="mx-3 btn btn-sm align-items-center disabled"
                                                                    data-bs-toggle="tooltip" title="{{ __('Cannot delete ASN that is not in Created status') }}">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endif
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

