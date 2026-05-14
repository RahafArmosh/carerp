@extends('layouts.admin')

@section('page-title')
    {{ __('Payment Methods') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payment Methods') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('payment-methods.create') }}" data-size="lg"
            data-ajax-popup="true" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Payment Method') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
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
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Bank Account') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paymentMethods as $method)
                                    <tr>
                                        <td>{{ $method->name }}</td>
                                        <td>{{ $method->warehouse->name ?? '-' }}</td>
                                        <td>{{ $method->bankAccount->holder_name ?? '-' }}</td>
                                        <td>{{ $method->created_at }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('payment-methods.edit', $method->id) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Payment Method') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn {{ ($paymentCounts[$method->id] ?? 0) > 0 ? 'bg-secondary' : 'bg-danger' }} ms-2">
                                                    @if (($paymentCounts[$method->id] ?? 0) > 0)
                                                        <button type="button"
                                                            class="btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('Cannot delete: :count payment(s) associated', ['count' => $paymentCounts[$method->id]]) }}"
                                                            disabled>
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    @else
                                                        <form id="delete-form-{{ $method->id }}"
                                                            action="{{ route('payment-methods.destroy', $method->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-3">
                            {{ $paymentMethods->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
