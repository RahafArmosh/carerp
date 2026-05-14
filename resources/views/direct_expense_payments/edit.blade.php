@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Direct Expense Payment') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expense_payments.index') }}">{{ __('Direct Expense Payments') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Direct Expense Payment') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('direct_expense_payments.update', $payment->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Direct Expense') }}</label>
                                <div class="form-control" readonly>
                                    {{ Auth::user()->expenseNumberFormat($directExpense->expense_number) }} - {{ optional($directExpense->vendor)->name ?? 'N/A' }}
                                </div>
                                <small class="text-muted">
                                    {{ __('Total Amount') }}: {{ Auth::user()->priceFormat($directExpense->getTotalAmount()) }}
                                    @if($directExpense->getTotalPaid() > 0)
                                        | {{ __('Paid') }}: {{ Auth::user()->priceFormat($directExpense->getTotalPaid()) }}
                                        | {{ __('Due') }}: {{ Auth::user()->priceFormat($directExpense->getDueAmount()) }}
                                    @endif
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Vendor') }}</label>
                                <div class="form-control" readonly>
                                    {{ optional($directExpense->vendor)->name ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="{{ $payment->date }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-select select2">
                                    <option value="">{{ __('Default (Base Currency)') }}</option>
                                    @isset($currencies)
                                        @foreach ($currencies as $cur)
                                            <option value="{{ $cur->id }}" {{ (string)($payment->currency_id ?? '') === (string)$cur->id ? 'selected' : '' }}>
                                                {{ $cur->code }} - {{ $cur->name }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.000001" min="0" name="currency_rate" id="currency_rate" class="form-control" value="{{ $payment->currency_rate ?? '' }}" placeholder="{{ __('If currency selected') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Amount (including tax)') }} <span class="text-danger">*</span></label>
                                @php
                                    $totalAmount = $directExpense->getTotalAmount();
                                    $taxRateSum = 0;
                                    if (!empty($directExpense->tax_id)) {
                                        $taxIds = method_exists($directExpense, 'getTaxIds') ? $directExpense->getTaxIds() : explode(',', (string) $directExpense->tax_id);
                                        foreach ($taxIds as $tid) {
                                            $taxModel = \App\Models\Tax::find($tid);
                                            if ($taxModel) {
                                                $taxRateSum += (float) $taxModel->rate;
                                            }
                                        }
                                    }
                                    $taxAmount = ($taxRateSum > 0 && $totalAmount > 0) ? ($totalAmount * $taxRateSum / 100) : 0;
                                    $totalWithTax = $totalAmount + $taxAmount;
                                    $paidSoFar = method_exists($directExpense, 'getTotalPaid') ? $directExpense->getTotalPaid() : 0;
                                    // Exclude current payment amount from paid total for max calculation
                                    $paidExcludingCurrent = max(0, $paidSoFar - $payment->amount);
                                    $dueWithTax = max($totalWithTax - $paidExcludingCurrent, 0);
                                    // Use currency_amount if exists, otherwise use amount
                                    $displayAmount = $payment->currency_amount ?? $payment->amount;
                                @endphp
                                <input type="number" name="amount" step="0.01" min="0" max="{{ $dueWithTax }}" class="form-control"
                                    value="{{ $displayAmount }}" required>
                                <small class="text-muted d-block">
                                    {{ __('Items Total') }}: {{ Auth::user()->priceFormat($totalAmount) }}
                                </small>
                                <small class="text-muted d-block">
                                    {{ __('Tax') }} ({{ number_format($taxRateSum, 2) }}%): {{ Auth::user()->priceFormat($taxAmount) }}
                                </small>
                                <small class="text-muted d-block">
                                    {{ __('Maximum (including tax)') }}: {{ Auth::user()->priceFormat($dueWithTax) }}
                                </small>
                                <small class="text-muted d-block">
                                    {{ __('If currency is selected, this amount is in that currency and will be converted to AED for storage.') }}
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Account') }} <span class="text-danger">*</span></label>
                                <select name="account_id" class="form-select select2" required>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" {{ $payment->account_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Reference') }}</label>
                                <input type="text" name="reference" class="form-control"
                                    value="{{ $payment->reference }}"
                                    placeholder="{{ __('Payment reference number') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="{{ __('Payment description') }}">{{ $payment->description }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Receipt') }}</label>
                                @if($payment->add_receipt)
                                    <div class="mb-2">
                                        <a href="{{ asset('uploads/direct_expense_payment/' . $payment->add_receipt) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="ti ti-file"></i> {{ __('View Current Receipt') }}
                                        </a>
                                    </div>
                                @endif
                                <input type="file" name="add_receipt" class="form-control"
                                    accept="image/*,.pdf">
                                <small class="text-muted">{{ __('Leave empty to keep current receipt, or upload a new one to replace it.') }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                            <a href="{{ route('direct_expense_payments.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
