@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Proposals') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Proposal') }}</li>
@endsection

@section('action-btn')
    <div class="float-end d-flex flex-wrap gap-1 justify-content-end">
        @can('create sale order')
            <a href="{{ route('saleorder.import') }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip"
                title="{{ __('Import Sale Order') }}">
                {{ __('Import Sale Order') }}
            </a>
        @endcan

        <a href="{{ route('proposal.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            {{ __('Export') }}
        </a>

        @can('create proposal')
            <a href="{{ route('proposal.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                {{ __('Create') }}
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
                        <form method="GET" action="{{ route('proposal.index') }}" id="frm_submit">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                    <div class="btn-box">
                                        <label for="issue_date" class="form-label">{{ __('Date') }}</label>
                                        <input type="text" name="issue_date" id="issue_date"
                                            value="{{ isset($_GET['issue_date']) ? $_GET['issue_date'] : null }}"
                                            class="form-control month-btn" id="pc-daterangepicker-1">

                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 ">
                                    <div class="btn-box">
                                        <label for="status" class="form-label">{{ __('Status') }}</label>
                                        <select name="status" id="status" class="form-control select">
                                            <option value="" selected>Select Status</option>
                                            @foreach ($status as $key => $value)
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
                                        {{ __('Apply') }}
                                    </a>
                                    <a href="{{ route('proposal.index') }}" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                        {{ __('Reset') }}
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
                                    <th> {{ __('Proposal') }}</th>
                                    {{--                                @if (!\Auth::guard('customer')->check()) --}}
                                    {{--                                    <th> {{__('Customer')}}</th> --}}
                                    {{--                                @endif --}}
                                    <th> {{ __('Customer') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Issue Date') }}</th>
                                    <th> {{ __('Status') }}</th>
                                    @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                    {{-- <th>
                                    <td class="barcode">
                                        {!! DNS1D::getBarcodeHTML($invoice->sku, "C128",1.4,22) !!}
                                        <p class="pid">{{$invoice->sku}}</p>
                                    </td>
                                </th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($proposals as $proposal)
                                    <tr class="font-style">
                                        <td class="Id">
                                            <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                class="btn btn-outline-primary">{{ \Auth::user()->proposalNumberFormat($proposal->proposal_id) }}
                                            </a>
                                        </td>
                                        <td>{{ !empty($proposal->customer) ? $proposal->customer->name : '' }}</td>
                                        <td>{{ !empty($proposal->category) ? $proposal->category->name : '' }}</td>
                                        <td>{{ \Auth::user()->dateFormat($proposal->issue_date) }}</td>
                                        <td>
                                            @if ($proposal->status == 0)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 1)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 2)
                                                <span
                                                    class="status_badge badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 3)
                                                <span
                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @elseif($proposal->status == 4)
                                                <span
                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Proposal::$statues[$proposal->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                            <td class="Action">
                                                @if ($proposal->is_convert == 0)
                                                    @can('convert invoice')
                                                        <div class="action-btn bg-warning ms-2">
                                                            <form id="proposal-form-{{ $proposal->id }}" method="get"
                                                                action="{{ route('proposal.convert', $proposal->id) }}">
                                                                @csrf
                                                                <a href="#"
                                                                    class="mx-2 px-2 btn btn-sm align-items-center text-white bs-pass-para"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ __('Convert Invoice') }}"
                                                                    data-confirm="{{ __('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back') }}"
                                                                    data-confirm-yes="document.getElementById('proposal-form-{{ $proposal->id }}').submit();">
                                                                    {{ __('Convert Invoice') }}
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                @else
                                                    @can('show invoice')
                                                        <div class="action-btn bg-warning ms-2">
                                                            <a href="{{ route('invoice.show', \Crypt::encrypt($proposal->converted_invoice_id)) }}"
                                                                class="mx-2 px-2 btn btn-sm align-items-center text-white"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('Already convert to Invoice') }}"
                                                                data-original-title="{{ __('Already convert to Invoice') }}">
                                                                {{ __('Invoice') }}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                @endif
                                                @can('duplicate proposal')
                                                    <div class="action-btn bg-success ms-2">
                                                        <form id="duplicate-form-{{ $proposal->id }}" method="GET"
                                                            action="{{ route('proposal.duplicate', $proposal->id) }}">
                                                            @csrf
                                                            <a href="#"
                                                                class="mx-2 px-2 btn btn-sm align-items-center text-white bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Duplicate') }}"
                                                                data-confirm="{{ __('You want to confirm duplicate this invoice. Press Yes to continue or Cancel to go back') }}"
                                                                data-confirm-yes="document.getElementById('duplicate-form-{{ $proposal->id }}').submit();">
                                                                {{ __('Duplicate') }}
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endcan
                                                @can('show proposal')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('proposal.show', \Crypt::encrypt($proposal->id)) }}"
                                                            class="mx-2 px-2 btn btn-sm align-items-center text-white"
                                                            data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                            data-original-title="{{ __('Detail') }}">
                                                            {{ __('View') }}
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('edit proposal')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('proposal.edit', \Crypt::encrypt($proposal->id)) }}"
                                                            class="mx-2 px-2 btn btn-sm align-items-center text-white"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            {{ __('Edit') }}
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('delete proposal')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form id="delete-form-{{ $proposal->id }}" method="POST"
                                                            action="{{ route('proposal.destroy', $proposal->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#"
                                                                class="mx-2 px-2 btn btn-sm align-items-center text-white bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $proposal->id }}').submit();">
                                                                {{ __('Delete') }}
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endcan
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
