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
        @can('create refund')
            <a href="#" data-url="{{ route('refund.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" data-size="lg"
                data-title="{{ __('Create New Refund') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
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
                        <form action="{{ route('refund.index') }}" method="GET" id="payment_form">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="date" class="form-label">{{ __('Date') }}</label>
                                                <input type="date" name="date"
                                                    value="{{ isset($_GET['date']) ? $_GET['date'] : '' }}"
                                                    class="form-control month-btn" id="pc-daterangepicker-1">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="account" class="form-label">{{ __('Account') }}</label>
                                                <select name="account" id="account" class="form-control select"
                                                    id="choices-multiple">
                                                    <?php
                                                    foreach ($account as $key => $value) {
                                                        $selected = isset($_GET['account']) && $_GET['account'] == $key ? 'selected' : '';
                                                        echo "<option value='$key' $selected>$value</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="vender" class="form-label">{{ __('Vendor') }}</label>
                                                <select name="vender" id="vender" class="form-control select"
                                                    id="choices-multiple1">
                                                    <?php
                                                    foreach ($vender as $key => $value) {
                                                        $selected = isset($_GET['vender']) && $_GET['vender'] == $key ? 'selected' : '';
                                                        echo "<option value='$key' $selected>$value</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="category" class="form-label">{{ __('Category') }}</label>
                                                <select name="category" id="category" class="form-control select"
                                                    id="choices-multiple2">
                                                    <?php
                                                    foreach ($category as $key => $value) {
                                                        $selected = isset($_GET['category']) && $_GET['category'] == $key ? 'selected' : '';
                                                        echo "<option value='$key' $selected>$value</option>";
                                                    }
                                                    ?>
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
                                            <a href="{{ route('productservice.index') }}" class="btn btn-sm btn-danger"
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
                                    <th>{{ __(key: 'ID') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Amount Currency') }}</th>
                                    <th>{{ __('Amount In Bill Currency') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    {{--                                <th> {{__('Chart Of Account')}}</th> --}}
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Refund Receipt') }}</th>
                                    <th> {{ __('Bill') }}</th>
                                    @if (Gate::check('edit refund') || Gate::check('delete refund'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $refundpath = \App\Models\Utility::get_file('uploads/refund');
                                @endphp

                                @foreach ($refunds as $refund)
                                    <tr class="font-style">
                                        @php
                                            $bill = \App\Models\Bill::find($refund->bill_id);
                                            $currencySymbol = $bill && $bill->currency ? $bill->currency->symbol : Auth::user()->currencySymbol();
                                        @endphp
                                        <td>{{\Auth::user()->paymentNumberRefundFormat($refund->id)}}</td>
                                        <td>{{ Auth::user()->dateFormat($refund->date) }}</td>
                                        <td>{{ $refund->currency ? Auth::user()->priceFormatCurr($refund->amount / $refund->currency_rate,$refund->currency->symbol) : Auth::user()->priceFormat($refund->amount) }}</td>
                                        <td>{{ $refund->currency ?  $refund->currency->name : \Auth::user()->currencySymbol()}}</td>
                                        <td>{{ Auth::user()->priceFormat($refund->amount)}}</td>
                                        <td>{{ $refund->amount_in_currency
                                            ? Auth::user()->priceFormatCurr($refund->amount_in_currency, $currencySymbol)
                                            : '-' }}</td>
                                        <td>{{ !empty($refund->bankAccount) ? $refund->bankAccount->bank_name . ' ' . $refund->bankAccount->holder_name : '' }}
                                        </td>
                                        {{--                                    <td>{{ !empty($refund->chartAccount)?$refund->chartAccount->name :'-' }}</td> --}}
                                        <td>{{ !empty($refund->vender) ? $refund->vender->name : '-' }}</td>
                                        <td>{{ !empty($refund->category) ? $refund->category->name : '-' }}</td>
                                        <td>{{ !empty($refund->reference) ? $refund->reference : '-' }}</td>
                                        <td>{{ !empty($refund->description) ? $refund->description : '-' }}</td>
                                        <td>
                                            @if (!empty($refund->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ URL::to('/') . '/uploads/customer_payment'  . '/' . $refund->add_receipt }}" download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ URL::to('/') . '/uploads/customer_payment'  . '/' . $refund->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>
                                            @if (!empty($refund->bill_id))
                                                <a href="{{ route('bill.show', \Crypt::encrypt($refund->bill_id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($refund->bill->bill_id) }}</a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="action">
                                                @can('edit refund')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('refund.edit', ['refund' => $refund->id]) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Refund') }}"
                                                            data-size="lg" data-bs-toggle="tooltip"
                                                            title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete refund')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST" action="{{ route('refund.destroy', $refund->id) }}" id="delete-form-{{ $refund->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip"
                                                            data-original-title="{{ __('Delete') }}"
                                                            title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $refund->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                    </div>
                                                @endcan
                                                <div class="action-btn bg-info ms-2">
                                                    {{-- <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('printvendorrefundpayment', ['payment' => $refund->id]) }}"
                                                        data-ajax-popup="true" data-title="{{ __('Print Refund') }}"
                                                        data-size="lg" data-bs-toggle="tooltip"
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Print') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a> --}}
                                                    <a href="{{ route('printvendorrefundpayment', ['payment' => $refund->id]) }}"
                                                        class="mx-3 btn btn-sm align-items-center" target="_blank"
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Print Refund') }}"
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
