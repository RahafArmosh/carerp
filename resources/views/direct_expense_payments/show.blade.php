@extends('layouts.admin')
@section('page-title')
    {{ __('Direct Expense Payment') }} #{{ $payment->id }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expense_payments.index') }}">{{ __('Direct Expense Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Direct Expense Payment Details') }}</li>
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
                                    <th>{{ __('Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        @if ($payment->status == 0)
                                            <span class="badge bg-primary">{{ __('Draft') }}</span>
                                        @elseif($payment->status == 2)
                                            <span class="badge bg-success">{{ __('Received') }}</span>
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
                            <h5>{{ __('Direct Expense Information') }}</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('Expense Number') }}</th>
                                    <td>
                                        <a href="{{ route('direct_expenses.show', $payment->direct_expense_id) }}" target="_blank">
                                            {{ Auth::user()->expenseNumberFormat($payment->directExpense->expense_number ?? '') }}
                                        </a>
                                    </td>
                                </tr>
                                @if($payment->directExpense && $payment->directExpense->expense_date)
                                <tr>
                                    <th>{{ __('Expense Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($payment->directExpense->expense_date) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>{{ __('Vendor') }}</th>
                                    <td>{{ optional($payment->vendor)->name ?? '-' }}</td>
                                </tr>
                                @if($payment->directExpense)
                                <tr>
                                    <th>{{ __('Payment Status') }}</th>
                                    <td>
                                        @if($payment->directExpense->payment_status == 0)
                                            <span class="badge bg-warning">{{ __('Unpaid') }}</span>
                                        @elseif($payment->directExpense->payment_status == 2)
                                            <span class="badge bg-info">{{ __('Partially Paid') }}</span>
                                        @elseif($payment->directExpense->payment_status == 4)
                                            <span class="badge bg-success">{{ __('Paid') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($payment->directExpense->currency)
                                <tr>
                                    <th>{{ __('Currency') }}</th>
                                    <td>{{ $payment->directExpense->currency->code }} - {{ $payment->directExpense->currency->name }}</td>
                                </tr>
                                @endif
                                @if($payment->directExpense->exchange_rate > 0)
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($payment->directExpense->exchange_rate, 4) }}</td>
                                </tr>
                                @endif
                                @if(!empty($taxes) && count($taxes) > 0)
                                <tr>
                                    <th>{{ __('Tax') }}</th>
                                    <td>
                                        @foreach($taxes as $tax)
                                            <span class="badge bg-info">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endif
                                @php
                                    $totalAmount = $payment->directExpense->getTotalAmount();
                                    $totalTaxAmount = $payment->directExpense->getTotalTaxAmount();
                                    $totalWithTax = $totalAmount + $totalTaxAmount;
                                    $totalPaid = $payment->directExpense->getTotalPaid();
                                    $dueAmount = $totalWithTax - $totalPaid;
                                @endphp
                                <tr>
                                    <th>{{ __('Subtotal') }}</th>
                                    <td>{{ Auth::user()->priceFormat($totalAmount) }}</td>
                                </tr>
                                @if($totalTaxAmount > 0)
                                <tr>
                                    <th>{{ __('Tax Amount') }}</th>
                                    <td>{{ Auth::user()->priceFormat($totalTaxAmount) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>{{ __('Total Amount') }}</th>
                                    <td class="fw-bold text-primary">
                                        {{ Auth::user()->priceFormat($totalWithTax) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Total Paid') }}</th>
                                    <td>{{ Auth::user()->priceFormat($totalPaid) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Due Amount') }}</th>
                                    <td class="fw-bold {{ $dueAmount > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ Auth::user()->priceFormat($dueAmount) }}
                                    </td>
                                </tr>
                                @endif
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
                            <a href="{{ asset('uploads/direct_expense_payment/' . $payment->add_receipt) }}" target="_blank"
                                class="btn btn-sm btn-info">
                                <i class="ti ti-download"></i> {{ __('View Receipt') }}
                            </a>
                        </div>
                    @endif
                    <div class="d-flex gap-2">
                        @if ($payment->status == 0)
                            <form action="{{ route('direct_expense_payments.send', $payment->id) }}" method="POST"
                                onsubmit="return confirm('{{ __('Mark as paid?') }}')">
                                @csrf
                                <button type="submit" class="btn btn-primary">{{ __('Mark as Paid') }}</button>
                            </form>
                        @endif
                        <a href="{{ route('direct_expense_payments.index') }}" class="btn btn-secondary">
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

