@extends('layouts.admin')
@section('page-title')
    {{ __('Service Bill Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item">{{ $expense->expense_id }}</li>
@endsection

@php
    $settings = Utility::settings();
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-between align-items-center mb-3">
                        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                            <div class="all-button-box">
                                <a href="{{ route('simple-expense.edit', \Crypt::encrypt($expense->id)) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-pencil"></i> {{ __('Edit') }}
                                </a>
                            </div>
                            <div class="all-button-box mx-2">
                                <a href="{{ route('simple-expense.ledger', $expense->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="ti ti-file-invoice"></i> {{ __('Show Accounting') }}
                                </a>
                            </div>
                            @php
                                $due = $expense->getExpenseDue();
                                $total = $expense->getTotal();
                            @endphp
                            @if($due > 0)
                                <div class="all-button-box mx-2">
                                    <a href="{{ route('simple-expense-payments.create') }}?expense_id={{ $expense->id }}" class="btn btn-sm btn-success">
                                        <i class="ti ti-plus"></i> {{ __('Add Payment') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{ __('Service Bill') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ $expense->expense_id }}</h4>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($user->name))
                                            {{ !empty($user->name) ? $user->name : '' }}<br>
                                            {{ !empty($user->billing_address) ? $user->billing_address : '' }}<br>
                                            {{ !empty($user->billing_city) ? $user->billing_city : '' . ', ' }}<br>
                                            {{ !empty($user->billing_state) ? $user->billing_state : '' . ', ' }},
                                            {{ !empty($user->billing_zip) ? $user->billing_zip : '' }}<br>
                                            {{ !empty($user->billing_country) ? $user->billing_country : '' }}<br>
                                            {{ !empty($user->billing_phone) ? $user->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($user->tax_number) ? $user->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Expense Date') }} :</strong><br>
                                        {{ \Auth::user()->dateFormat($expense->expense_date) }}<br><br>
                                    </small>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} : </strong><br>
                                        <span class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\SimpleExpense::$statues[$expense->status]) }}</span>
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Payment Status') }} :</strong><br>
                                        @php
                                            $due = $expense->getExpenseDue();
                                            $total = $expense->getTotal();
                                        @endphp
                                        @if($due <= 0)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __('Paid') }}</span>
                                        @elseif($due < $total)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __('Partially Paid') }}</span>
                                        @else
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __('Unpaid') }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Currency') }} :</strong><br>
                                        @if ($expense->currency_id != null)
                                            <span class="badge bg-info p-2 px-3 rounded">{{ $expense->currency->name }}</span>
                                        @else
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ $settings['site_currency'] }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Attachment') }} :</strong><br>
                                        @if(!empty($expense->attachment))
                                            <a href="{{ asset('storage/uploads/simple_expenses/' . $expense->attachment) }}" target="_blank">
                                                <i class="ti ti-file"></i> {{ $expense->attachment }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expense->accounts as $account)
                                    <tr>
                                        <td>{{ !empty($account->chartAccount) ? $account->chartAccount->name : '-' }}</td>
                                        <td>{{ $account->description }}</td>
                                        <td class="text-end">{{ \Auth::user()->priceFormat($account->price) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($expense->getAccountTotal()) }}</td>
                                </tr>
                                @php
                                    $taxRate = 0;
                                    if ($expense->tax_id) {
                                        $taxIds = explode(',', $expense->tax_id);
                                        foreach ($taxIds as $taxId) {
                                            $tax = \App\Models\Tax::find($taxId);
                                            if ($tax) {
                                                $taxRate += $tax->rate;
                                            }
                                        }
                                    }
                                    $taxAmount = $expense->getAccountTotal() * ($taxRate / 100);
                                @endphp
                                @if($taxAmount > 0)
                                <tr>
                                    <td colspan="2" class="text-end"><b>{{ __('Tax') }}</b></td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($taxAmount) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td colspan="2" class="text-end blue-text"><b>{{ __('Total') }}</b></td>
                                    <td class="text-end blue-text">{{ \Auth::user()->priceFormat($expense->getTotal()) }}</td>
                                </tr>
                                @php
                                    $paidAmount = \App\Models\SimpleExpensePayment::where('expense_id', $expense->id)->sum('amount');
                                @endphp
                                <tr>
                                    <td colspan="2" class="text-end"><b>{{ __('Paid') }}</b></td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($paidAmount) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

