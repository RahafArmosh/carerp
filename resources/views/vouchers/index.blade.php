@extends('layouts.admin')

@section('page-title')
    {{ __('Vouchers') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Vouchers') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('vouchers.create') }}" data-size="lg"
            data-ajax-popup="true" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Vouchers') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
        
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
                                    <th>{{ __('VID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Source') }}</th>
                                    <th>{{ __('Valid Until') }}</th>
                                    <th>{{ __('Active') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vouchers as $voucher)
                                    <tr>
                                        <td>{{ $voucher->id ?? '-' }}</td>                                        
                                        <td>{{ $voucher->customer->name ?? ($voucher->customer_id ?? '-') }}</td>
                                        <td>{{ $voucher->amount ?? '-' }}</td>
                                        <td>
                                            @if($voucher->posRefund)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="ti ti-arrow-back-up"></i> {{ __('From Refund') }}
                                                </span>
                                                @if($voucher->posRefund->pos)
                                                    <br><small class="text-muted">
                                                        POS: {{ \Auth::user()->posNumberFormat($voucher->posRefund->pos->pos_id) }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="badge bg-info">{{ __('Manual') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $voucher->valid_until ? $voucher->valid_until : '-' }}</td>
                                        <td>
                                            @if ($voucher->active)
                                                <span class="badge bg-success">{{ __('Yes') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $voucher->created_at->format('Y-m-d') }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('vouchers.edit', $voucher->id) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Vouchers') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('vouchers.print', $voucher->id) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Print') }}"
                                                        data-title="{{ __('Print voucher') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form
                                                        action="{{ route('vouchers.destroy', $voucher->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                            class="btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
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

                        <div class="mt-3">
                            {{ $vouchers->links() }} {{-- Pagination --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
