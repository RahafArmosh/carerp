@extends('layouts.admin')
@section('page-title')
    {{ __('Vendor Statement Summary') }}
@endsection
@push('script-page')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateHiddenInput(inputId, hiddenId, datalistId) {
                let input = document.getElementById(inputId);
                let hiddenInput = document.getElementById(hiddenId);
                let options = document.querySelectorAll(`#${datalistId} option`);

                input.addEventListener('input', function() {
                    let selectedOption = [...options].find(option => option.value === input.value);
                    hiddenInput.value = selectedOption ? selectedOption.getAttribute('data-id') : '';
                });
            }

            updateHiddenInput('account_name', 'account_id', 'accounts');
            updateHiddenInput('vendor_name', 'vendor_id', 'vendors');
        });
    </script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A4'
                }
            };
            html2pdf().set(opt).from(element).save();
        }

        $(document).ready(function() {
            var filename = $('#filename').val();
            $('#report-dataTable').DataTable({
                dom: 'lBfrtip',
                buttons: [{
                        extend: 'excel',
                        title: filename
                    },
                    {
                        extend: 'pdf',
                        title: filename
                    }, {
                        extend: 'csv',
                        title: filename
                    }
                ]
            });
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Vendor Statement Summary') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">
        {{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
        {{--            <i class="ti ti-filter"></i> --}}
        {{--        </a> --}}

        <a href="{{ route('accountstatement.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()"data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>

    </div>
@endsection


@section('content')

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('report.vendor.statement') }}" method="GET" id="report_account">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">

                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="start_month" class="form-label">{{ __('Start Month') }}</label>
                                                <input type="month" id="start_month" name="start_month"
                                                    value="{{ isset($_GET['start_month']) ? $_GET['start_month'] : date('Y-m', strtotime('-5 month')) }}"
                                                    class="month-btn form-control">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="end_month" class="form-label">{{ __('End Month') }}</label>
                                                <input type="month" id="end_month" name="end_month"
                                                    value="{{ isset($_GET['end_month']) ? $_GET['end_month'] : date('Y-m') }}"
                                                    class="month-btn form-control">
                                            </div>
                                        </div>

                                        <!-- Account Selection -->
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="account" class="form-label">{{ __('Account') }}</label>
                                                <select class="form-control select2" name="account" id="account"
                                                    style="width: 100%;">
                                                    <option value="">{{ __('Select Account') }}</option>
                                                    @foreach ($account as $accountId => $accountLabel)
                                                        <option value="{{ $accountId }}"
                                                            {{ isset($_GET['account']) && $_GET['account'] == $accountId ? 'selected' : '' }}>
                                                            {{ $accountLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Vendor Selection -->
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="vendor_name" class="form-label">{{ __('Vendor') }}</label>
                                                <select class="form-control select2" name="vendor" id="vendor"
                                                    style="width: 100%;">
                                                    <option value="">{{ __('Select Vendor') }}</option>
                                                    @foreach ($vendor as $vendorId => $vendorLabel)
                                                        <option value="{{ $vendorId }}"
                                                            {{ isset($_GET['vendor']) && $_GET['vendor'] == $vendorId ? 'selected' : '' }}>
                                                            {{ $vendorLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="row">
                                        <div class="col-auto mt-4">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('report_account').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('report.vendor.statement') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off"></i></span>
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

        <div id="printableArea">
            <div class="row mt-3">
                <div class="col">
                    <input type="hidden"
                        value="{{ __('Vendor Statement') . ' ' . $filter['vendor'] . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
                        id="filename">
                    <div class="card mb-4 p-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                        <h6 class="report-text mb-0">{{ __('Vendor Statement Summary') }}</h6>
                    </div>
                </div>
                @if ($filter['account'] != __('All'))
                    <div class="col">
                        <div class="card mb-4 p-4">
                            <h7 class="report-text gray-text mb-0">{{ __('Account') }} :</h7>
                            <h6 class="report-text mb-0">{{ $filter['account'] }}</h6>
                        </div>
                    </div>
                @endif
                @if ($filter['vendor'] != __('All'))
                    <div class="col">
                        <div class="card mb-4 p-4">
                            <h7 class="report-text gray-text mb-0">{{ __('Vendor') }} :</h7>
                            <h6 class="report-text mb-0">
                                {{ \App\Models\Vender::where('id', $filter['vendor'])->first() != null ? \App\Models\Vender::where('id', $filter['vendor'])->first()->name : '' }}
                            </h6>
                        </div>
                    </div>
                @endif
                <div class="col">
                    <div class="card mb-4 p-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}
                        </h6>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4 p-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Previous Balance') }} :</h7>
                        <h6 class="report-text mb-0">{{ Auth::user()->priceFormat($previousBalance) }}</h6>
                    </div>
                </div>
            </div>

            {{-- @if (!empty($reportData['general_ledger']))
            <div class="row">
                @foreach ($reportData['general_ledger'] as $account)
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="card p-4 mb-4">
                            @if ($account->holder_name == 'Cash')
                                <h7 class="report-text gray-text mb-0">{{$account->holder_name}}</h7>
                            @elseif(empty($account->holder_name))
                                <h7 class="report-text gray-text mb-0">{{__('Stripe / Paypal')}}</h7>
                            @else
                                <h7 class="report-text gray-text mb-0">{{$account->holder_name.' - '.$account->bank_name}}</h7>
                            @endif
                            <h6 class="report-text mb-0">{{\Auth::user()->priceFormat($account->total)}}</h6>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="datatable table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Update Date') }}</th>
                                        <th>{{ __('VID') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Account') }}</th>
                                        <th>{{ __('Debit') }}</th>
                                        <th>{{ __('Credit') }}</th>
                                        <th>{{ __('Balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!empty($reportData['general_ledger']))
                                        @php
                                            $totalDebit = $previousBalance;
                                            $totalCredit = 0;
                                            $totalBalance = 0;
                                        @endphp
                                        @foreach ($reportData['general_ledger'] as $general_ledger)
                                            @php
                                                $totalDebit += $general_ledger->total_debit;
                                                $totalCredit += $general_ledger->total_credit;
                                            @endphp
                                            <tr class="font-style">
                                                <td>{{ Auth::user()->dateFormat($general_ledger->send_date) }}</td>
                                                <td>{{ Auth::user()->dateFormat($general_ledger->updated_at) }}</td>
                                                <td>{{ $general_ledger->vid }}</td>
                                                @if ($general_ledger->debit > 0)
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_debit) }}</td>
                                                @else
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_credit) }}</td>
                                                @endif
                                                <td>
                                                    @if ($general_ledger->reference == 'Bill' || $general_ledger->reference == 'Bill Account')
                                                        @php
                                                            $bill = \App\Models\Bill::find($general_ledger->ref_id);
                                                        @endphp
                                                        @if ($bill)
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                class="btn btn-outline-primary">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                                        @else
                                                            {{ Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $general_ledger->ref_id)->first()->bill_id) }}
                                                        @endif
                                                    @elseif (
                                                        $general_ledger->reference == 'Expense' ||
                                                            $general_ledger->reference == 'Expense Account' ||
                                                            $general_ledger->reference == 'Delete Expense')
                                                        @php
                                                            $expense = App\Models\Bill::where(
                                                                'id',
                                                                $general_ledger->ref_id,
                                                            )
                                                                ->where('type', 'Expense')
                                                                ->first();
                                                        @endphp
                                                        @if ($expense)
                                                            <a href="{{ route('expense.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? $expense->bill_id }}</a>
                                                        @else
                                                            <a href="{{ route('expense.index') }}"
                                                                class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? App\Models\Bill::withTrashed()->where('id', $general_ledger->ref_id)->first()->bill_id }}</a>
                                                        @endif
                                                    @elseif (\App\Models\SimpleExpense::referenceIsExpenseLine($general_ledger->reference))
                                                        @php
                                                            $simpleExpense = App\Models\SimpleExpense::withTrashed()->where('id', $general_ledger->ref_id)->first();
                                                        @endphp
                                                        @if ($simpleExpense)
                                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? $simpleExpense->expense_id }}</a>
                                                        @else
                                                            <a href="{{ route('simple-expense.index') }}"
                                                                class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? __('Service Bill Not Found') }}</a>
                                                        @endif
                                                    @elseif ($general_ledger->reference == 'Delete Item From Bill')
                                                        @php
                                                            $bill = App\Models\Bill::withTrashed()->where('id', $general_ledger->ref_id)->first();
                                                        @endphp
                                                        @if($bill && $general_ledger->deleted_qty && $general_ledger->sub_product_id)
                                                            <a href="{{ route('bill.showItemdelete', [\Crypt::encrypt($general_ledger->sub_product_id), $general_ledger->deleted_qty, $general_ledger->ref_id]) }}"
                                                                class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? 'Delete Item From Bill  ' . Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                                        @else
                                                            <span class="text-muted">Delete Item From Bill</span>
                                                        @endif
                                                    @elseif ($general_ledger->reference == 'Expense Payment')
                                                        <a href="{{ route('payment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ 'Expense Payment' . $general_ledger->ref_number ?? App\Models\Bill::withTrashed()->where('id', $general_ledger->ref_id)->first()->bill_id }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete Expense Payment')
                                                        <a href="{{ route('payment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ 'Delete Expense Payment' . $general_ledger->ref_number ?? App\Models\Bill::withTrashed()->where('id', $general_ledger->ref_id)->first()->bill_id }}</a>
                                                    @elseif (\App\Models\SimpleExpense::referenceIsPaymentLine($general_ledger->reference))
                                                        <a href="{{ route('payment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? \App\Models\Payment::formatLabelForId($general_ledger->payment_id) }}</a>
                                                    @elseif ($general_ledger->reference == 'Payment' || $general_ledger->reference == 'Bill Payment')
                                                        <a href="{{ route('payment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? \App\Models\Payment::formatLabelForId($general_ledger->payment_id) }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete Payment' || $general_ledger->reference == 'Delete Bill Payment')
                                                        <a href="{{ route('payment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ 'Delete Payment' . ($general_ledger->ref_number ?? \App\Models\Payment::formatLabelForId($general_ledger->payment_id)) }}</a>
                                                    @elseif (
                                                        $general_ledger->reference === 'Bill Refund' ||
                                                            $general_ledger->reference === 'Vendor Refund' ||
                                                            $general_ledger->reference === 'Delete Bill Refund' ||
                                                            $general_ledger->reference === 'Delete Vendor Refund')
                                                        @php
                                                            $isVendorPayment = $general_ledger->ref_id == -1;
                                                            $bill = !$isVendorPayment
                                                                ? \App\Models\Bill::withTrashed()->find(
                                                                    $general_ledger->ref_id,
                                                                )
                                                                : null;
                                                        @endphp

                                                        @if (!$isVendorPayment && $bill)
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                class="btn btn-outline-primary">
                                                                {{ $general_ledger->ref_number ?? 'Bill Refund ' . Auth::user()->billNumberFormat($bill->bill_id) }}
                                                            </a>
                                                        @else
                                                            <a href="{{ route('refund.index', \Crypt::encrypt($general_ledger->id)) }}"
                                                                class="btn btn-outline-primary">
                                                                {{ $general_ledger->ref_number ?? $general_ledger->type }}
                                                            </a>
                                                        @endif
                                                    @elseif ($general_ledger->reference == 'Debit Note' || $general_ledger->reference == 'Delete Debit Note')
                                                        <a href="{{ route('debit.note') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'opening balance')
                                                        <a href="{{ route('chart-of-account.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Direct Expense Reversal')
                                                        <a href="{{ route('direct_expenses.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a> 
                                                    @elseif ($general_ledger->reference == 'Direct Expense')
                                                        <a href="{{ route('direct_expenses.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Direct Expense Payment Reversal')
                                                        <a href="{{ route('direct_expense_payments.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Direct Expense Payment')
                                                        <a href="{{ route('direct_expense_payments.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Direct Expense Payment Reversal')
                                                        <a href="{{ route('direct_expense_payments.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @else
                                                        <span class="text-muted">No reference</span>
                                                    @endif
                                                </td>
                                                <td>{{ \App\Models\Vender::where('id', $general_ledger->user_id)->first()->name }}
                                                </td>
                                                <td>{{ \App\Models\ChartOfAccount::where('id', $general_ledger->account)->first()->name }}
                                                </td>
                                                <td>{{ Auth::user()->priceFormat($general_ledger->total_debit) }}</td>
                                                <td>{{ Auth::user()->priceFormat($general_ledger->total_credit) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($totalDebit - $totalCredit) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td><b>{{ __('Total') }}</b></td>
                                            <td><b>{{ \Auth::user()->priceFormat($totalDebit) }}</b></td>
                                            <td><b>{{ \Auth::user()->priceFormat($totalCredit) }}</b></td>
                                            <td>{{ \Auth::user()->priceFormat($totalDebit - $totalCredit) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
