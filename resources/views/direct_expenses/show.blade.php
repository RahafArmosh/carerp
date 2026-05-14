@extends('layouts.admin')
@section('page-title')
    {{ __('Direct Expense Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Direct Expense Details') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto">
                        <h5 class="mb-0">{{ __('Direct Expense') }}: {{ Auth::user()->expenseNumberFormat($directExpense->expense_number) }}</h5>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('direct_expenses.index') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left"></i> {{ __('Back') }}
                        </a>
                        {{-- @can('edit bill')
                        <a href="{{ route('direct_expenses.edit', $directExpense->id) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-pencil"></i> {{ __('Edit') }}
                        </a>
                        @endcan --}}
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Header Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">{{ __('Expense Information') }}</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold">{{ __('Expense Number') }}:</td>
                                <td>{{ Auth::user()->expenseNumberFormat($directExpense->expense_number) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('Expense Date') }}:</td>
                                <td>{{ $directExpense->expense_date ? Auth::user()->dateFormat($directExpense->expense_date) : Auth::user()->dateFormat($directExpense->created_at) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('Vendor') }}:</td>
                                <td>{{ optional($directExpense->vendor)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('Payment Status') }}:</td>
                                <td>
                                    @if($directExpense->payment_status == 0)
                                        <span class="badge bg-warning">{{ __('Unpaid') }}</span>
                                    @elseif($directExpense->payment_status == 2)
                                        <span class="badge bg-info">{{ __('Partially Paid') }}</span>
                                    @elseif($directExpense->payment_status == 4)
                                        <span class="badge bg-success">{{ __('Paid') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('Created Date') }}:</td>
                                <td>{{ Auth::user()->dateFormat($directExpense->created_at) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('Attachment') }}:</td>
                                <td>
                                    @if(!empty($directExpense->attachment))
                                        <a href="{{ asset('storage/uploads/direct_expenses/' . $directExpense->attachment) }}" target="_blank">
                                            <i class="ti ti-file"></i> {{ $directExpense->attachment }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">{{ __('Financial Information') }}</h6>
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold">{{ __('Currency') }}:</td>
                                <td>
                                    @if($directExpense->currency)
                                        {{ $directExpense->currency->code }} - {{ $directExpense->currency->name }}
                                    @else
                                        {{ Auth::user()->currencySymbol() }}
                                    @endif
                                </td>
                            </tr>
                            @if($directExpense->exchange_rate > 0)
                            <tr>
                                <td class="fw-bold">{{ __('Exchange Rate') }}:</td>
                                <td>{{ number_format($directExpense->exchange_rate, 4) }}</td>
                            </tr>
                            @endif
                            @if(!empty($taxes) && count($taxes) > 0)
                            <tr>
                                <td class="fw-bold">{{ __('Tax') }}:</td>
                                <td>
                                    @foreach($taxes as $tax)
                                        <span class="badge bg-info">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                    @endforeach
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="fw-bold">{{ __('Total Amount') }}:</td>
                                <td class="h5 text-primary">
                                    {{ number_format($directExpense->getTotalAmount() + $directExpense->getTotalTaxAmount(), 2) }} {{ Auth::user()->currencySymbol() }}
                                </td>
                            </tr>
                            @if($directExpense->currency && $directExpense->exchange_rate > 0)
                            <tr>
                                <td class="fw-bold">{{ __('Total (Currency)') }}:</td>
                                <td>
                                    {{ number_format($directExpense->getTotalCurrencyAmount() + $directExpense->getTotalTaxAmountCurrency(), 2) }} {{ $directExpense->currency->code }}
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mt-4">
                    <h6 class="mb-3">{{ __('Expense Items') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('VIN') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    @if($directExpense->currency && $directExpense->exchange_rate > 0)
                                    <th>{{ __('Currency Amount') }}</th>
                                    @endif
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($directExpense->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ optional($item->subProduct)->product_no ?? '-' }}</td>
                                        <td>{{ optional(optional($item->subProduct)->productService)->name ?? '-' }}</td>
                                        <td>{{ optional(optional($item->subProduct)->warehouse)->name ?? '-' }}</td>
                                        <td>{{ $item->qty ?? 1 }}</td>
                                        <td>{{ number_format($item->amount, 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                        @if($directExpense->currency && $directExpense->exchange_rate > 0)
                                        <td>
                                            @if($item->currency_amount)
                                                {{ number_format($item->currency_amount, 2) }} {{ $directExpense->currency->code }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                        <td>
                                            @if($item->chartAccount)
                                                {{ $item->chartAccount->code }} - {{ $item->chartAccount->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $directExpense->currency && $directExpense->exchange_rate > 0 ? '9' : '8' }}" class="text-center">{{ __('No items found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="{{ $directExpense->currency && $directExpense->exchange_rate > 0 ? '5' : '5' }}" class="text-end fw-bold">{{ __('Total') }}:</td>
                                    <td class="fw-bold">{{ number_format($directExpense->getTotalAmount() , 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                    @if($directExpense->currency && $directExpense->exchange_rate > 0)
                                    <td class="fw-bold">{{ number_format($directExpense->getTotalCurrencyAmount() , 2) }} {{ $directExpense->currency->code }}</td>
                                    @endif
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Payment Information -->
                @if($directExpense->payments && $directExpense->payments->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3">{{ __('Payments') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($directExpense->payments as $payment)
                                    <tr>
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ number_format($payment->amount, 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                        <td>{{ $payment->reference ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-end fw-bold">{{ __('Total Paid') }}:</td>
                                    <td class="fw-bold">{{ number_format($directExpense->getTotalPaid(), 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                    <td></td>
                                </tr>
                                {{-- <tr>
                                    <td class="text-end fw-bold">{{ __('Due Amount') }}:</td>
                                    <td class="fw-bold text-danger">{{ number_format($directExpense->getDueAmount(), 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                    <td></td>
                                </tr> --}}
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="mt-4 d-flex align-items-center gap-2">
                    @if($directExpense->payment_status != 4)
                    <a href="{{ route('direct_expense_payments.create', $directExpense->id) }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> {{ __('Add Payment') }}
                    </a>
                    @endif
                    @if(\Auth::user()->can('ledger report'))
                    <a href="{{ route('direct_expenses.ledger', $directExpense->id) }}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="ti ti-file-invoice"></i> {{ __('Show Accounting') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

