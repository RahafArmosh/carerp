@extends('layouts.admin')
@section('page-title')
    {{ __('Create Direct Expense Payment') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expense_payments.index') }}">{{ __('Direct Expense Payments') }}</a></li>
    <li class="breadcrumb-item"></li>{{ __('Create Direct Expense Payment') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('direct_expense_payments.store', $directExpense->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
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
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-select select2">
                                    <option value="">{{ __('Default (Base Currency)') }}</option>
                                    @isset($currencies)
                                        @foreach ($currencies as $cur)
                                            <option value="{{ $cur->id }}" {{ (string)($directExpense->currency_id ?? '') === (string)$cur->id ? 'selected' : '' }}>
                                                {{ $cur->code }} - {{ $cur->name }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.000001" min="0" name="currency_rate" id="currency_rate" class="form-control" value="{{ $directExpense->exchange_rate ?? '' }}" placeholder="{{ __('If currency selected') }}">
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
                                    $dueWithTax = max($totalWithTax - $paidSoFar, 0);
                                @endphp
                                <input type="number" name="amount" step="0.01" min="0" max="{{ $dueWithTax }}" class="form-control"
                                    value="{{ $dueWithTax }}" required>
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
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('Reference') }}</label>
                                <input type="text" name="reference" class="form-control"
                                    placeholder="{{ __('Payment reference number') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="{{ __('Payment description') }}"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('Receipt') }}</label>
                                <input type="file" name="add_receipt" class="form-control"
                                    accept="image/*,.pdf">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('direct_expenses.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

