@extends('layouts.admin')
@section('page-title')
    {{ __('Service Bill Payment') }} #{{ $payment->id }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense-payments.index') }}">{{ __('Service Bill Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Service Bill Payment Details') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>{{ __('Payment Information') }}</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('Payment ID') }}</th>
                                    <td>#{{ $payment->id }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Amount (AED)') }}</th>
                                    <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                </tr>
                                @if($payment->currency_id && $payment->amount_in_currency)
                                <tr>
                                    <th>{{ __('Currency Amount') }}</th>
                                    <td>{{ number_format($payment->amount_in_currency, 2) }} {{ optional($payment->currency)->code ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($payment->currency_rate, 6) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        @if ($payment->status == 0)
                                            <span class="badge bg-primary">{{ __('Draft') }}</span>
                                        @elseif($payment->status == 2)
                                            <span class="badge bg-success">{{ __('Paid') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <td>{{ optional($payment->bankAccount)->holder_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Reference') }}</th>
                                    <td>{{ $payment->reference ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>{{ __('Service Bill Information') }}</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('Service Bill No.') }}</th>
                                    <td>
                                        @if($payment->expense)
                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($payment->expense_id)) }}" class="btn btn-outline-primary btn-sm">
                                                {{ $payment->expense->expense_id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Vendor') }}</th>
                                    <td>{{ optional($payment->expense->vender)->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Expense Date') }}</th>
                                    <td>{{ $payment->expense ? Auth::user()->dateFormat($payment->expense->expense_date) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Total Amount') }}</th>
                                    <td>{{ $payment->expense ? Auth::user()->priceFormat($payment->expense->getTotal()) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Paid Amount') }}</th>
                                    <td>{{ $payment->expense ? Auth::user()->priceFormat($payment->expense->payments()->where('status', 2)->sum('amount')) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Due Amount') }}</th>
                                    <td>{{ $payment->expense ? Auth::user()->priceFormat($payment->expense->getExpenseDue()) : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if ($payment->description)
                        <div class="mb-3">
                            <h6>{{ __('Description') }}</h6>
                            <p>{{ $payment->description }}</p>
                        </div>
                    @endif
                    @if ($payment->add_receipt)
                        <div class="mb-3">
                            <h6>{{ __('Receipt') }}</h6>
                            <a href="{{ asset('uploads/simple_expense_payment/' . $payment->add_receipt) }}" target="_blank"
                                class="btn btn-sm btn-info">
                                <i class="ti ti-download"></i> {{ __('View Receipt') }}
                            </a>
                        </div>
                    @endif
                    <div class="d-flex gap-2">
                        @if ($payment->status == 0)
                            @can('manage payment')
                                <form action="{{ route('simple-expense-payments.send', \Crypt::encrypt($payment->id)) }}" method="POST"
                                    onsubmit="return confirm('{{ __('Mark as paid?') }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">{{ __('Mark as Paid') }}</button>
                                </form>
                            @endcan
                            @can('edit payment')
                                <a href="{{ route('simple-expense-payments.edit', \Crypt::encrypt($payment->id)) }}" class="btn btn-primary">{{ __('Edit') }}</a>
                            @endcan
                        @endif
                        <a href="{{ route('simple-expense-payments.index') }}" class="btn btn-secondary">
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

