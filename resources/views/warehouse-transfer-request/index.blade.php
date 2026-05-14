@extends('layouts.admin')
@section('page-title')
    {{ __('Warehouse Transfer Requests') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Transfer Requests') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @if (\Auth::user()->type == 'company' || \Auth::user()->can('create transfer'))
        <a href="#" data-url="{{ route('warehouse-transfer.file.import') }}" data-size="lg"
           data-bs-toggle="tooltip" title="{{ __('Import from Excel') }}"
           data-title="{{ __('Import Warehouse Transfer from Excel') }}"
           data-ajax-popup="true"
           class="btn btn-sm btn-primary me-2">
            <i class="ti ti-file-import"></i> {{ __('Import') }}
        </a>
        <a href="{{ route('warehoustrans.create') }}" data-size="lg"
           data-bs-toggle="tooltip" title="{{ __('Create') }}"
           data-title="{{ __('Create Warehouse Transfer Request') }}"
           class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Request Number') }}</th>
                                    <th>{{ __('From Warehouse') }}</th>
                                    <th>{{ __('To Warehouse') }}</th>
                                    <th>{{ __('Items Count') }}</th>
                                    <th>{{ __('Request Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created By') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    <tr class="font-style">
                                        <td>
                                            <a href="{{ route('warehouse-transfer-request.show', $request->id) }}" class="btn btn-outline-primary btn-sm">
                                                {{ $request->request_number }}
                                            </a>
                                        </td>
                                        <td>{{ optional($request->fromWarehouse)->name }}</td>
                                        <td>{{ optional($request->toWarehouse)->name }}</td>
                                        <td>{{ $request->transfers->count() }}</td>
                                        <td>{{ Auth::user()->dateFormat($request->request_date) }}</td>
                                        <td>
                                            @if($request->status == 'draft')
                                                <span class="badge bg-secondary">{{ __('Draft') }}</span>
                                            @elseif($request->status == 'pending')
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @elseif($request->status == 'approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                            @elseif($request->status == 'rejected')
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @elseif($request->status == 'cancelled')
                                                <span class="badge bg-dark">{{ __('Cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($request->creator)->name }}</td>
                                        <td class="Action">
                                            <a href="{{ route('warehouse-transfer-request.show', $request->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip" 
                                               title="{{ __('View') }}">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            @if(($request->status == 'draft' || $request->status == 'pending') && (\Auth::user()->type == 'company' || \Auth::user()->can('delete transfer')))
                                                <div class="action-btn bg-danger ms-2 d-inline-block">
                                                    <form action="{{ route('warehouse-transfer-request.destroy', $request->id) }}"
                                                          method="POST"
                                                          id="delete-request-form-{{ $request->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#"
                                                           class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                           data-bs-toggle="tooltip"
                                                           title="{{ __('Delete') }}"
                                                           onclick="confirmDelete('{{ __('Are You Sure?') }}', 'delete-request-form-{{ $request->id }}')">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

