@extends('layouts.admin')
@section('page-title')
{{ __('General Ledger') }}
@endsection
<style>
    datalist {
        width: 100%;
        background-color: white;
    }
</style>
<script>
    function exportGledger() {
        let startDate = document.getElementById('start_date').value;
        let endDate = document.getElementById('end_date').value;
        let account = document.getElementById('account').value;

        let url = "{{ route('Gledger.export') }}" +
            '?start_date=' + encodeURIComponent(startDate) +
            '&end_date=' + encodeURIComponent(endDate) +
            '&account=' + encodeURIComponent(account);

        window.location.href = url;
    }
</script>
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item">{{ __('General Ledger') }}</li>
@endsection
@push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
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
                format: 'A2'
            }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>
<script>
    function submitForm() {
        document.getElementById('report_Gledger').submit();
    }
</script>
@endpush

@section('action-btn')
<div class="float-end">
    {{-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
    {{-- <i class="ti ti-filter"></i> --}}
    {{-- </a> --}}
    <a href="#" onclick="exportGledger()" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
        title="{{ __('Export') }}">
        <i class="ti ti-file-export"></i>
    </a>
    <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
        title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
        <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
    </a>

</div>
@endsection

@php
$accounts = $accounts->prepend('Select ', 0);
@endphp
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="mt-2" id="multiCollapseExample1">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('report.Gledger') }}" method="GET" id="report_Gledger">

                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                            <input type="date" id="start_date" name="start_date"
                                                value="{{ $filter['startDateRange'] }}" class="month-btn form-control">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                            <input type="date" id="end_date" name="end_date"
                                                value="{{ $filter['endDateRange'] }}" class="month-btn form-control">
                                        </div>
                                    </div>



                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="account" class="form-label">{{ __('Account') }}</label>
                                            <select id="account" name="account_id" class="form-control select2">
                                                @foreach ($accounts as $accountId => $accountName)
                                                <option value="{{ $accountId }}"
                                                    {{ isset($_GET['account']) && $_GET['account'] == $accountId ? 'selected' : '' }}>
                                                    {{ $accountName }}
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
                                            onclick="submitForm(); return false;" data-bs-toggle="tooltip"
                                            title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('report.Gledger') }}" class="btn btn-sm btn-danger"
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
        <div class="row mb-4">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="datatable table">
                                <thead>
                                    <tr>
                                        {{-- <th> {{ __('ID') }}</th> --}}
                                        <th> {{ __('VID') }}</th>
                                        <th> {{ __('Account Name') }}</th>
                                        <th> {{ __('Account Number') }}</th>
                                        <th> {{ __('Transaction Type') }}</th>
                                        <th> {{ __('Reference') }}</th>
                                        <th> {{ __('Reference') }}</th>
                                        <th> {{ __('User') }}</th>
                                        <th> {{ __('Debit') }}</th>
                                        <th> {{ __('Credit') }}</th>
                                        <th> {{ __('Date') }}</th>
                                        <th> {{ __('Create Date') }}</th>
                                        <th> {{ __('Update Date') }}</th>
                                        {{-- <th> {{ __('Balance') }}</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @php

                                    $accountArrays = [];
                                    foreach ($chart_accounts as $key => $account) {
                                    $chartDatas = App\Models\Utility::getAccountData(
                                    $account['id'],
                                    $filter['startDateRange'],
                                    $filter['endDateRange'],
                                    );

                                    $chartDatas = $chartDatas->toArray();
                                    $accountArrays[] = $chartDatas;
                                    }
                                    @endphp
                                    @foreach ($generalLedgerData as $data)
                                    <tr>
                                        {{-- <td>{{ $data->id }}</td> --}}
                                        <td>{{ $data->vid }}</td>
                                        <td>{{ \App\Models\ChartOfAccount::where('id', $data->account)->first()->name }}
                                        </td>
                                        <td>{{ \App\Models\ChartOfAccount::where('id', $data->account)->first()->code }}
                                        </td>
                                        <td>
                                            @if ($data->reference === 'Bill')
                                            @php
                                            $bill = App\Models\Bill::find($data->ref_id);
                                            @endphp
                                            @if ($bill)
                                            @if ($data->ref_number)
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number }}
                                            </a>
                                            @else
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ Auth::user()->billNumberFormat($bill->bill_id) }}
                                            </a>
                                            @endif
                                            @else
                                            <span
                                                class="text-danger">{{ $data->ref_number ?? Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->find($data->ref_id)->bill_id) }}</span>
                                            @endif
                                            @elseif ($data->reference === 'Journal Entries' || $data->reference === 'Delete Journal Entries' || $data->reference === 'Reverse Journal Entries')
                                            @php
                                            $journal = \App\Models\JournalEntry::withTrashed()->find(
                                            $data->ref_id,
                                            );
                                            @endphp

                                            @if ($journal && $journal->deleted_at === null)
                                            <a href="{{ route('journal-entry.show', $journal->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? Auth::user()->journalNumberFormat($journal->journal_id) }}
                                            </a>
                                            @elseif ($journal && $journal->deleted_at != null)
                                            <a href="{{ route('journal-entry.showdelete', $journal->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? Auth::user()->journalNumberFormat($journal->journal_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">Journal Not Found</span>
                                            @endif
                                            @elseif ($data->reference === 'Payment' || $data->reference == 'Bill Payment' )
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? \App\Models\Payment::formatLabelForId($data->payment_id) }}
                                            </a>
                                            @elseif ( $data->reference === 'Delete Payment' || $data->reference == 'Delete Bill Payment')
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ 'Delete Payment ' . ($data->ref_number ?? \App\Models\Payment::formatLabelForId($data->payment_id)) }}
                                            </a>
                                            @elseif($data->reference === 'Delete Bill')
                                            <a href="{{ route('bill.showdelete', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? 'Bill delete ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif($data->reference === 'Delete Item From Bill')
                                            <a href="{{ route('bill.showItemdelete', [\Crypt::encrypt($data->sub_product_id), $data->deleted_qty, $data->ref_id]) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? 'Delete Item From Bill  ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif($data->reference === 'Debit Note' || $data->reference === 'Delete Debit Note')
                                            <a href="{{ route('debit.note') }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? $data->type }}</a>
                                            @elseif ($data->reference === 'Bill Refund' ||$data->reference === 'Vendor Refund' ||$data->reference === 'Delete Bill Refund' ||$data->reference === 'Delete Vendor Refund')
                                            @php
                                            $isVendorPayment = $data->ref_id == -1;
                                            $bill = !$isVendorPayment
                                            ? \App\Models\Bill::withTrashed()->find($data->ref_id)
                                            : null;
                                            @endphp

                                            @if (!$isVendorPayment && $bill)
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? 'Bill Refund ' . Auth::user()->billNumberFormat($bill->bill_id) }}
                                            </a>
                                            @else
                                            <a href="{{ route('refund.index', \Crypt::encrypt($data->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? $data->type }}
                                            </a>
                                            @endif
                                            @elseif(stripos((string) $data->reference, 'Purchase Return') !== false)
                                            <a href="{{ route('purchase.return.index') }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? $data->type }}
                                            </a>
                                            @elseif(
                                                stripos((string) $data->reference, 'Sales Return') !== false ||
                                                stripos((string) $data->type, 'Sales Return') !== false
                                            )
                                            @php
                                                $salesReturn = \App\Models\SalesReturn::withTrashed()->with('invoice')->find($data->ref_id);
                                                $salesReturnInvoice = $salesReturn && $salesReturn->invoice
                                                    ? $salesReturn->invoice
                                                    : ($salesReturn ? \App\Models\Invoice::withTrashed()->find($salesReturn->invoice_id) : null);
                                                $salesReturnRef = $data->ref_number;

                                                if (empty($salesReturnRef) || strtoupper(trim((string) $salesReturnRef)) === 'N/A') {
                                                    $salesReturnRef = $salesReturnInvoice
                                                        ? \Auth::user()->invoiceNumberFormat($salesReturnInvoice->invoice_id)
                                                        : ('Sales Return #' . $data->ref_id);
                                                }
                                            @endphp
                                            @if ($salesReturn)
                                            <a href="{{ route('sales.return.show', $salesReturn->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ $salesReturnRef }}
                                            </a>
                                            @else
                                            <a href="{{ route('sales.return.index') }}"
                                                class="btn btn-outline-primary">
                                                {{ $salesReturnRef }}
                                            </a>
                                            @endif
                                            @elseif($data->reference == 'Delete Bank Transfer')
                                            {{ $data->ref_number ?? $data->type }}
                                            @elseif($data->reference == 'Bank Transfer')
                                            <a href="{{ route('bank-transfer.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? $data->type }}</a>
                                            @elseif ($data->reference == 'Expense' || $data->reference == 'Delete Expense')
                                            @php
                                            $expense = App\Models\Bill::withTrashed()->where('id', $data->ref_id)->where('type', 'Expense')->first();
                                            @endphp
                                            @if ($expense)
                                            <a href="{{ route('expense.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? $expense->bill_id }}</a>
                                            @else
                                            <a href="{{ route('expense.index') }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? $expense->bill_id }}</a>
                                            @endif
                                            @elseif ($data->reference == 'Expense Payment')
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Expense Payment ' . ($data->ref_number ?? \App\Models\Payment::formatLabelForId($data->payment_id)) }}</a>
                                            @elseif ( $data->reference == 'Delete Expense Payment')
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? \App\Models\Payment::formatLabelForId($data->payment_id) }}</a>
                                            @elseif (\App\Models\SimpleExpense::referenceIsExpenseLine($data->reference))
                                            @php
                                            $simpleExpense = App\Models\SimpleExpense::withTrashed()->where('id', $data->ref_id)->first();
                                            @endphp
                                            @if ($simpleExpense)
                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? $simpleExpense->expense_id }}</a>
                                            @else
                                            <a href="{{ route('simple-expense.index') }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? __('Service Bill Not Found') }}</a>
                                            @endif
                                            @elseif (\App\Models\SimpleExpense::referenceIsPaymentLine($data->reference))
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? \App\Models\Payment::formatLabelForId($data->payment_id) }}</a>
                                            @elseif($data->reference === 'Delete Invoice')
                                            <a href="{{ route('invoice.showdelete', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->ref_number ?? 'Invoice delete ' . Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</a>
                                            @elseif ($data->reference === 'Invoice')
                                            @php
                                            $invoice = App\Models\Invoice::find($data->ref_id);
                                            @endphp

                                            @if ($invoice)
                                            @if ($data->ref_number)
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number }}
                                            </a>
                                            @else
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                            </a>
                                            @endif
                                            @else
                                            <span
                                                class="text-danger">{{ Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</span>
                                            @endif
                                            @elseif ($data->reference === 'Customer Payment' || $data->reference == 'Invoice Payment' )
                                            <a href="{{ route('customerpayment.index', \Crypt::encrypt($data->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($data->payment_id) }}
                                            </a>
                                            @elseif ( $data->reference === 'Delete Customer Payment' || $data->reference == 'Delete Invoice Payment')
                                            <a href="{{ route('customerpayment.index', \Crypt::encrypt($data->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ 'Delete Customer Payment ' . ($data->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($data->payment_id)) }}
                                            </a>
                                            @elseif ($data->reference == 'Invoice Delete Product')
                                            <a href="{{ route('invoice.showItemdelete', [\Crypt::encrypt($data->sub_product_id), $data->deleted_qty, $data->ref_id]) }}"
                                                class="btn btn-outline-primary">{{ 'Delete Product from ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</a>
                                            @elseif ($data->reference == 'Revenue' || $data->reference == 'Delete Revenue')
                                            <a href="{{ route('revenue.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif ($data->reference == 'Customer Refund' || $data->reference == 'Delete Customer Refund')
                                            <a href="{{ route('customerrefunds.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif ($data->reference == 'Credit Note' || $data->reference == 'Delete Credit Note')
                                            <a href="{{ route('credit.note') }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif ($data->reference == 'opening balance')
                                            <a href="{{ route('chart-of-account.index') }}"
                                                class="btn btn-outline-primary">{{ $data->reference }}</a>
                                            @elseif ($data->reference == 'Assign Car Accessory')
                                                @php
                                                    $carAccessoryRequest = \App\Models\CarAccessoryRequest::find($data->ref_id);
                                                @endphp
                                                @if($carAccessoryRequest)
                                                    <a href="{{ route('car_accessories.show', $carAccessoryRequest->id) }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @else
                                                    <a href="{{ route('car_accessories.index') }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @endif
                                             @elseif ($data->reference == 'Delete - Assign Car Accessory')
                                            <a href="{{ route('car_accessories.index') }}"
                                                class="btn btn-outline-primary">{{ $data->reference }}</a>
                                            @elseif ($data->reference == 'Direct Expense')
                                                @php
                                                    $directExpense = \App\Models\DirectExpense::find($data->ref_id);
                                                @endphp
                                                @if($directExpense)
                                                    <a href="{{ route('direct_expenses.show', $directExpense->id) }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @else
                                                    <a href="{{ route('direct_expenses.index') }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @endif
                                            @elseif ($data->reference == 'Direct Expense Reversal')
                                                @php
                                                    $directExpenseReversal = \App\Models\DirectExpense::find($data->ref_id);
                                                @endphp
                                                @if($directExpenseReversal)
                                                    <a href="{{ route('direct_expenses.show', $directExpenseReversal->id) }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @else
                                                    <a href="{{ route('direct_expenses.index') }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @endif
                                            @elseif ($data->reference == 'Direct Expense Item Reversal')
                                                @php
                                                    $directExpenseItemReversal = \App\Models\DirectExpense::find($data->ref_id);
                                                @endphp
                                                @if($directExpenseItemReversal)
                                                    <a href="{{ route('direct_expenses.show', $directExpenseItemReversal->id) }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @else
                                                    <a href="{{ route('direct_expenses.index') }}"
                                                        class="btn btn-outline-primary">{{ $data->reference }}</a>
                                                @endif
                                            @elseif ($data->reference == 'Direct Expense Payment')
                                            <a href="{{ route('direct_expense_payments.index') }}"
                                                class="btn btn-outline-primary">{{ $data->reference }}</a>
                                            @elseif ($data->reference == 'Direct Expense Payment Reversal')
                                            <a href="{{ route('direct_expense_payments.index') }}"
                                                class="btn btn-outline-primary">{{ $data->reference }}</a>
                                            @elseif ($data->reference == 'POS' || $data->reference == 'Delete POS')
                                            @php
                                            $pos = \App\Models\Pos::find($data->ref_id);
                                            @endphp
                                            @if ($pos)
                                            <a href="{{ route('pos.show', \Crypt::encrypt($pos->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? \Auth::user()->posNumberFormat($pos->pos_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ '#' . __('POS') . sprintf('%05d', $data->ref_id) }}</span>
                                            @endif
                                            @elseif ($data->reference == 'POS_payment')
                                            @php
                                            $pos = \App\Models\Pos::find($data->ref_id);
                                            @endphp
                                            @if ($pos)
                                            <a href="{{ route('pos.show', \Crypt::encrypt($pos->id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment' }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ '#' . __('POS') . sprintf('%05d', $data->ref_id) . ' Payment' }}</span>
                                            @endif
                                            @elseif (strpos($data->reference, 'Stock Count') !== false)
                                            @php
                                            $warehouse = \App\Models\warehouse::find($data->ref_id);
                                            @endphp
                                            @if ($warehouse)
                                            <a href="{{ route('warehouse.stock-count', $warehouse->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->ref_number ?? $data->reference }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->ref_number ?? $data->reference }}</span>
                                            @endif
                                            @else
                                            <span class="text-muted">No reference</span>
                                            @endif
                                        </td>
                                        <td class="Id">
                                            @if (stripos((string) $data->reference, 'Sales Return') !== false)
                                            @php
                                                $salesReturn = \App\Models\SalesReturn::withTrashed()->with('invoice')->find($data->ref_id);
                                                $salesReturnInvoice = $salesReturn && $salesReturn->invoice
                                                    ? $salesReturn->invoice
                                                    : ($salesReturn ? \App\Models\Invoice::withTrashed()->find($salesReturn->invoice_id) : null);
                                                $salesReturnRef = $data->ref_number;
                                                if (empty($salesReturnRef) || strtoupper(trim((string) $salesReturnRef)) === 'N/A') {
                                                    $salesReturnRef = $salesReturnInvoice
                                                        ? \Auth::user()->invoiceNumberFormat($salesReturnInvoice->invoice_id)
                                                        : ('Sales Return #' . $data->ref_id);
                                                }
                                            @endphp
                                            @if ($salesReturn)
                                            <a href="{{ route('sales.return.show', $salesReturn->id) }}"
                                                class="btn btn-outline-primary">{{ $salesReturnRef }}</a>
                                            @else
                                            <a href="{{ route('sales.return.index') }}"
                                                class="btn btn-outline-primary">{{ $salesReturnRef }}</a>
                                            @endif
                                            @elseif (stripos((string) $data->reference, 'Purchase Return') !== false)
                                            @php
                                                $purchaseReturn = \App\Models\PurchaseReturn::withTrashed()->with('bill')->find($data->ref_id);
                                                $purchaseReturnBill = $purchaseReturn && $purchaseReturn->bill
                                                    ? $purchaseReturn->bill
                                                    : ($purchaseReturn ? \App\Models\Bill::withTrashed()->find($purchaseReturn->bill_id) : null);
                                                $purchaseReturnRef = $data->ref_number;
                                                if (empty($purchaseReturnRef) || strtoupper(trim((string) $purchaseReturnRef)) === 'N/A') {
                                                    $purchaseReturnRef = $purchaseReturnBill
                                                        ? \Auth::user()->billNumberFormat($purchaseReturnBill->bill_id)
                                                        : ('Purchase Return #' . $data->ref_id);
                                                }
                                            @endphp
                                            @if ($purchaseReturn)
                                            <a href="{{ route('purchase.return.index') }}"
                                                class="btn btn-outline-primary">{{ $purchaseReturnRef }}</a>
                                            @else
                                            <a href="{{ route('purchase.return.index') }}"
                                                class="btn btn-outline-primary">{{ $purchaseReturnRef }}</a>
                                            @endif
                                            @elseif (strstr($data->type, 'Bill Payment') !== false)
                                            @php
                                            $bill = App\Models\Bill::withTrashed()
                                            ->where('id', $data->ref_id)
                                            ->first();
                                            @endphp
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ 'Bill Payment  ' . Auth::user()->billNumberFormat($bill->bill_id) }}
                                            </a>
                                            @elseif (strstr($data->type, 'BILL') !== false &&
                                            strstr($data->type, 'Bill delete #BILL') === false &&
                                            strstr($data->type, 'Delete Item From Bill') === false &&
                                            strstr($data->type, 'Bill Payment') === false &&
                                            strstr($data->type, 'Bill delete Payment') === false &&
                                            strstr($data->type, 'Bill Refund Payment') === false &&
                                            strstr($data->type, 'Debit Note for') === false)
                                            @php
                                            $bill = App\Models\Bill::withTrashed()
                                            ->where('id', $data->ref_id)
                                            ->first();
                                            @endphp

                                            @if ($bill)
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ Auth::user()->billNumberFormat($bill->bill_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">Bill Not Found</span>
                                            @endif
                                            @elseif(strstr($data->type, 'Credit Note for') !== false || strstr($data->type, 'Delete Credit Note for') !== false)
                                            <a href="{{ route('credit.note') }}"
                                                class="btn btn-outline-primary">
                                                {{ $data->type }}
                                            </a>
                                            @elseif(strstr($data->type, 'Invoice Payment') !== false)
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ 'Invoice Payment ' . Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}
                                            </a>
                                            @elseif(strstr($data->type, 'Invoice delete') !== false &&
                                            strstr($data->type, 'Invoice Delete Product') === false &&
                                            strstr($data->type, 'Invoice delete Payment #INVO') === false)
                                            <a href="{{ route('invoice.showdelete', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Invoice delete ' . Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</a>
                                            @elseif(strstr($data->type, 'Invoice Delete Product') !== false)
                                            <a href="{{ route('invoice.showItemdelete', [\Crypt::encrypt($data->sub_product_id), $data->deleted_qty, $data->ref_id]) }}"
                                                class="btn btn-outline-primary">{{ 'Delete Product from ' . Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</a>
                                            @elseif(strstr($data->type, 'INVO') !== false &&
                                            strstr($data->type, 'Invoice delete') === false &&
                                            strstr($data->type, 'Invoice Delete Product') === false &&
                                            strstr($data->type, 'Credit Note for') === false &&
                                            strstr($data->type, 'Invoice delete') === false &&
                                            strstr($data->type, 'Invoice delete Payment') === false &&
                                            strstr($data->type, 'Invoice Payment') === false)
                                            <a href="{{ route('invoice.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">@php
                                                $invoice = App\Models\Invoice::withTrashed()
                                                ->where('id', $data->ref_id)
                                                ->first();
                                                @endphp

                                                @if ($invoice)
                                                {{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                                @else
                                                N/A {{-- or any fallback message --}}
                                                @endif
                                            </a>
                                            @elseif(strstr($data->type, 'POS') !== false)
                                            <a href="{{ route('pos.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'JUR') !== false)
                                            @php
                                            $journal = \App\Models\JournalEntry::find($data->ref_id);
                                            @endphp

                                            @if ($journal)
                                            <a href="{{ route('journal-entry.show', $journal->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ Auth::user()->journalNumberFormat($journal->journal_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }} </span>
                                            @endif
                                            @elseif(strstr($data->type, 'Bill delete #BILL') !== false && strstr($data->type, 'Bill delete Payment') === false)
                                            <a href="{{ route('bill.showdelete', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Bill delete ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'Delete Item From Bill') !== false)
                                            <a href="{{ route('bill.showItemdelete', [\Crypt::encrypt($data->sub_product_id), $data->deleted_qty, $data->ref_id]) }}"
                                                class="btn btn-outline-primary">{{ 'Delete Item From Bill  ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'Delete Debit Note For') !== false)
                                            <a href="{{ route('debit.note') }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Debit Note For') !== false)
                                            <a href="{{ route('debit.note') }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Bank Transfer') !== false)
                                            <a href="{{ route('bank-transfer.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Vendor Payment') !== false)
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Bill Payment') !== false)
                                            <a href="{{ route('bill.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'Bill delete Payment') !== false && strstr($data->type, 'Bill delete #BILL') === false)
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Bill delete Payment ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'Delete Payment ') !== false)
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Delete Vendor Payment  ' . \App\Models\Payment::formatLabelForId($data->payment_id) }}</a>
                                            @elseif(strstr($data->type, 'Customer Payment') !== false)
                                            <a href="{{ route('customerpayment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Customer Refund Payment') !== false)
                                            <a href="{{ route('customerrefunds.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Vendor Refund Payment') !== false)
                                            <a href="{{ route('refunds.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Bill Refund Payment') !== false)
                                            <a href="{{ route('refunds.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Bill Refund Payment ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'delete Bill Refund Payment') !== false)
                                            <a href="{{ route('refunds.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'delete Bill Refund Payment ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $data->ref_id)->first()->bill_id) }}</a>
                                            @elseif(strstr($data->type, 'RV') !== false)
                                            <a href="{{ route('revenue.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'EXP') !== false && !\App\Models\SimpleExpense::glTypeLooksLikeServiceBillExpenseId($data->type))
                                            @php
                                            $expense = App\Models\Bill::withTrashed()->where('id', $data->ref_id)->where('type', 'Expense')->first();
                                            @endphp
                                            @if ($expense)
                                            <a href="{{ route('expense.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $expense->bill_id }}</a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(\App\Models\SimpleExpense::glTypeLooksLikeServiceBillExpenseId($data->type))
                                            @php
                                            $simpleExpense = App\Models\SimpleExpense::withTrashed()->where('id', $data->ref_id)->first();
                                            @endphp
                                            @if ($simpleExpense)
                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $simpleExpense->expense_id }}</a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(\App\Models\SimpleExpense::glTypeIsExpensePaymentButNotServiceBill($data->type))
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(\App\Models\SimpleExpense::glTypeIsServiceBillPayment($data->type))
                                            <a href="{{ route('payment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ $data->type }}</a>
                                            @elseif(strstr($data->type, 'Invoice delete Payment') !== false && strstr($data->type, 'Invoice delete #INVO') === false)
                                            <a href="{{ route('customerpayment.index', \Crypt::encrypt($data->ref_id)) }}"
                                                class="btn btn-outline-primary">{{ 'Invoice delete Payment ' . Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $data->ref_id)->first()->invoice_id) }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $data->reference }}
                                        </td>
                                        <td class="Id">
                                            @if (strstr($data->type, 'BILL') !== false && $data->user_id != 0)
                                            @php
                                            $vender = App\Models\Vender::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($vender)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat($vender->vender_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'INVO') !== false && $data->user_id != 0)
                                            @php
                                            $customer = App\Models\Customer::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($customer)
                                            <a href="{{ route('customer.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->customerNumberFormat($customer->customer_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'Customer') !== false && $data->user_id != 0)
                                            @php
                                            $customer = App\Models\Customer::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($customer)
                                            <a href="{{ route('customer.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->customerNumberFormat($customer->customer_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'Vendor') !== false && $data->user_id != 0)
                                            @php
                                            $vender = App\Models\Vender::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($vender)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat($vender->vender_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'POS') !== false && $data->user_id != 0)
                                            @php
                                            $customer = App\Models\Customer::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($customer)
                                            <a href="{{ route('customer.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->customerNumberFormat($customer->customer_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'RV') !== false && $data->user_id != 0)
                                            @php
                                            $customer = App\Models\Customer::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($customer)
                                            <a href="{{ route('customer.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->customerNumberFormat($customer->customer_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(strstr($data->type, 'EXP') !== false && !\App\Models\SimpleExpense::glTypeLooksLikeServiceBillExpenseId($data->type) && $data->user_id != 0)
                                            @if ($data->user_type === 'vendor')
                                            @php
                                            $vender = App\Models\Vender::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($vender)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat($vender->vender_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif ($data->user_type === 'customer')
                                            @php
                                            $customer = App\Models\Customer::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($customer)
                                            <a href="{{ route('customer.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->customerNumberFormat($customer->customer_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @else
                                            <a href="{{ route('employeepayment.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->employeeIdFormat($data->user_id) }}
                                            </a>
                                            @endif
                                            @elseif(\App\Models\SimpleExpense::glTypeLooksLikeServiceBillExpenseId($data->type) && $data->user_id != 0)
                                            @php
                                            $vender = App\Models\Vender::where('id', $data->user_id)->first();
                                            @endphp
                                            @if ($vender)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat($vender->vender_id) }}
                                            </a>
                                            @else
                                            <span class="text-danger">{{ $data->type }}</span>
                                            @endif
                                            @elseif(\App\Models\SimpleExpense::glTypeIsExpensePaymentButNotServiceBill($data->type) && $data->user_id != 0)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat(App\Models\Vender::where('id', $data->user_id)->first()->vender_id) }}
                                            </a>
                                            @elseif(\App\Models\SimpleExpense::glTypeIsServiceBillPayment($data->type) && $data->user_id != 0)
                                            <a href="{{ route('vender.show', \Crypt::encrypt($data->user_id)) }}"
                                                class="btn btn-outline-primary">
                                                {{ AUth::user()->venderNumberFormat(App\Models\Vender::where('id', $data->user_id)->first()->vender_id) }}
                                            </a>
                                            @else<a> - </a>
                                            @endif
                                        </td>
                                        {{-- <td>{{ $account->date }}</td> --}}
                                        <td>{{ \Auth::user()->priceFormat($data->total_debit) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($data->total_credit) }}</td>
                                        <td>{{ $data->send_date }}</td>
                                        <td>{{ $data->created_at }}</td>
                                        <td>{{ $data->updated_at }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection