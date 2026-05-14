@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Refunds') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Refund') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create customer refund')
            <a href="#" data-url="{{ route('customerrefund.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip"
                data-size="lg" data-title="{{ __('Create New Refund') }}" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i>{{ __('Create') }}
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('customerrefund.index') }}" method="GET" id="payment_form">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="date" class="form-label">Date</label>
                                                <input type="date" name="date" id="pc-daterangepicker-1"
                                                    class="form-control month-btn"
                                                    value="{{ isset($_GET['date']) ? $_GET['date'] : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="account" class="form-label">Account</label>
                                                <select name="account" id="choices-multiple" class="form-control select">
                                                    @foreach ($account as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['account']) && $_GET['account'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="customer" class="form-label">Customer</label>
                                                <select name="customer" id="choices-multiple1" class="form-control select">
                                                    @foreach ($customer as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['customer']) && $_GET['customer'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="category" class="form-label">Category</label>
                                                <select name="category" id="choices-multiple2" class="form-control select">
                                                    @foreach ($category as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['category']) && $_GET['category'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div clas="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('payment_form').submit(); return false;"
                                                data-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('customerrefund.index') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    {{--                                <th> {{__('Chart Of Account')}}</th> --}}
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    <th> {{ __('Invoice') }}</th>
                                    @if (Gate::check('edit payment') || Gate::check('delete payment'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentpath = \App\Models\Utility::get_file('uploads/payment');
                                @endphp

                                @foreach ($payments as $payment)
                                    <tr class="font-style">
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
                                        </td>
                                        {{--                                    <td>{{ !empty($payment->chartAccount)?$payment->chartAccount->name :'-' }}</td> --}}
                                        <td>{{ !empty($payment->customer) ? $payment->customer->name : '-' }}</td>
                                        <td>{{ !empty($payment->category) ? $payment->category->name : '-' }}</td>
                                        <td>{{ !empty($payment->reference) ? $payment->reference : '-' }}</td>
                                        <td>{{ !empty($payment->description) ? $payment->description : '-' }}</td>
                                        <td>
                                            @if (!empty($payment->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ URL::to('/') . '/uploads/customer_payment'  . '/' . $payment->add_receipt }}" download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ URL::to('/') . '/uploads/customer_payment'  . '/' . $payment->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>
                                            @if (!empty($payment->invoice_id))
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($payment->invoice_id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->invoiceNumberFormat($payment->invoice->invoice_id) }}</a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="action">
                                                @can('edit customer refund')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('customerrefund.edit', ['customerrefund' => $payment->id]) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Refund') }}"
                                                            data-size="lg" data-bs-toggle="tooltip"
                                                            title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete customer refund')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST" action="{{ route('customerrefund.destroy', $payment->id) }}" id="delete-form-{{ $payment->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip"
                                                            data-original-title="{{ __('Delete') }}"
                                                            title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $payment->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                    </div>
                                                @endcan
                                                <div class="action-btn bg-info ms-2">
                                                    {{-- <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('printrefundpayment', ['payment' => $payment->id]) }}"
                                                        data-ajax-popup="true" data-title="{{ __('Refund Payment') }}"
                                                        data-size="lg" data-bs-toggle="tooltip"
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Print') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a> --}}

                                                    <a href="{{ route('printrefundpayment', ['payment' => $payment->id]) }}"
                                                        class="mx-3 btn btn-sm align-items-center" target="_blank"
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Refund Payment') }}"
                                                        >
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a>
                                                </div>
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
