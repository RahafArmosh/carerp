@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Manage Employee-Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employeepayment.index') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ $employee['name'] }}</li>
@endsection
@push('script-page')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box vendor_card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Employee Info') }}</h5>
                    <p class="card-text mb-0">{{ $employee->name }}</p>
                    <p class="card-text mb-0">{{ $employee->email }}</p>
                    <p class="card-text mb-0">{{ $employee->contact }}</p>
                    <p class="card-text mb-0">{{ $employee['chart_account_id'] != 0 ? \App\Models\ChartOfAccount::where('id', $employee['chart_account_id'])->first()->name :'-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class=" d-inline-block mb-5">{{ __('Bills') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Bill Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($employee->EmployeeBill($employee->id) as $bill)
                                    <tr class="font-style">
                                        <td class="Id">
                                            <a href="{{ route('expense.show', \Crypt::encrypt($bill->id)) }}"
                                                class="btn btn-outline-primary">{{ $bill->bill_id }}
                                            </a>
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                        <td>
                                            @if ($bill->due_date < date('Y-m-d'))
                                                <p class="text-danger"> {{ \Auth::user()->dateFormat($bill->due_date) }}
                                                </p>
                                            @else
                                                {{ \Auth::user()->dateFormat($bill->due_date) }}
                                            @endif
                                        </td>
                                        <td>
                                         @if($bill->type === "Bill")
                                            {{ \Auth::user()->priceFormat($bill->getDue()) }}
                                        @else
                                            {{ \Auth::user()->priceFormat(0) }}
                                        @endif
                                        </td>
                                        <td>
                                            @if ($bill->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$bill->status]) }}</span>
                                            @endif
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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Payment') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th> {{ __('Status') }}</th>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    <th> {{ __('Bill') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employeePyment as $payment)
                                    <tr>
                                        {{--                                        <td>{{ AUth::user()->posNumberFormat($posPayment->pos_id) }}</td> --}}

                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
                                        </td>
                                        <td>
                                            @if ($payment->status == 0)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Payment::$statues[$payment->status]) }}</span>
                                            @elseif($payment->status == 2)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Payment::$statues[$payment->status]) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ !empty($payment->vender) ? $payment->vender->name : '-' }}</td>
                                        <td>{{ !empty($payment->category) ? $payment->category->name : '-' }}</td>
                                        <td>{{ !empty($payment->reference) ? $payment->reference : '-' }}</td>
                                        <td>{{ !empty($payment->description) ? $payment->description : '-' }}</td>
                                        <td>
                                            @if (!empty($payment->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ asset('uploads/payment') . '/' . $payment->add_receipt }}"
                                                    download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ asset('uploads/payment') . '/' . $payment->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>
                                            @if (!empty($payment->bill_id))
                                                @if(\App\Models\Bill::where('id',$payment->bill_id)->first()->type == 'Bill')
                                                <a href="{{ route('bill.show', \Crypt::encrypt($payment->bill_id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($payment->bill_id) }}</a>
                                                @else
                                                <a href="{{ route('bill.show', \Crypt::encrypt($payment->bill_id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->expenseNumberFormat($payment->bill_id) }}</a>
                                                @endif
                                            @else
                                                -
                                            @endif

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
