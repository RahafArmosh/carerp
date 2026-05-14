@extends('layouts.admin')
@section('page-title')
{{ __('Ledger Summary') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item">{{ __('Ledger Summary') }}</li>
@endsection
@push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let accountInput = document.getElementById('account_name');
        let accountIdInput = document.getElementById('account_id');
        let accountOptions = document.querySelectorAll('#account_list option');

        // Retrieve selected account ID from URL
        let selectedAccountId = "{{ isset($_GET['account']) ? $_GET['account'] : '' }}";
        // Set default account name if an account ID is already selected
        if (selectedAccountId) {
            accountOptions.forEach(option => {
                if (option.getAttribute('data-id') === selectedAccountId) {
                    accountInput.value = option.value; // Set the name
                    accountIdInput.value = selectedAccountId; // Set the ID
                }
            });
        }

        // Ensure correct account_id is sent on form submission
        document.getElementById('report_ledger').addEventListener('submit', function(e) {
            document.getElementById('report_Gledger').submit();
        });
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
                format: 'A2'
            }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>
@endpush

@section('action-btn')
<div class="float-end">
    {{-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
    {{-- <i class="ti ti-filter"></i> --}}
    {{-- </a> --}}

    <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
        title="{{ __('Download PDF') }}" data-original-title="{{ __('Download PDF') }}">
        <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
    </a>

    <a href="{{ route('ledger.summary.export', request()->query()) }}" class="btn btn-sm btn-success ms-2" data-bs-toggle="tooltip"
        title="{{ __('Export to Excel') }}" data-original-title="{{ __('Export to Excel') }}">
        <span class="btn-inner--icon">
            <i class="ti ti-download"></i>
        </span>
    </a>

</div>
@endsection

@php
$accounts = $accounts->prepend('Select ', 0);
@endphp
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="mt-2 " id="multiCollapseExample1">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('report.ledger') }}" method="GET" id="report_ledger">
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box"></div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                            <input type="date" id="start_date" name="start_date"
                                                value="{{ request()->get('start_date', $filter['startDateRange']) }}"
                                                class="month-btn form-control">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                            <input type="date" id="end_date" name="end_date"
                                                value="{{ request()->get('end_date', $filter['endDateRange']) }}"
                                                class="month-btn form-control">
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="account_name" class="form-label">{{ __('Account') }}</label>
                                            <select class="form-control select2" id="account_id" name="account">
                                                @foreach ($accounts as $accountId => $accountName)
                                                <option value="{{ $accountId }}"
                                                    {{ request()->get('account') == $accountId ? 'selected' : '' }}>
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
                                        <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                            title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </button>
                                        <a href="{{ route('report.ledger') }}" class="btn btn-sm btn-danger"
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
        {{-- <div class="row mt-2">
            <div class="col">
                <input type="hidden"
                    value="{{ __('Ledger') . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
        id="filename">
        <div class="card p-4 mb-4">
            <h6 class="mb-0">{{ __('Report') }} :</h6>
            <h7 class="text-sm mb-0">{{ __('Ledger Summary') }}</h7>
        </div>
    </div>

    <div class="col">
        <div class="card p-4 mb-4">
            <h6 class="mb-0">{{ __('Duration') }} :</h6>
            <h7 class="text-sm mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}</h7>
        </div>
    </div>
</div> --}}
{{-- @if (!empty($account))
            <div class="row mt-2">
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h6 class="mb-0">{{ __('Account Name') }} :</h6>
<h7 class="text-sm mb-0">{{ $account->name }}</h7>
</div>
</div>

<div class="col">
    <div class="card p-4 mb-4">
        <h6 class="mb-0">{{ __('Account Code') }} :</h6>
        <h7 class="text-sm mb-0">{{ $account->code }}</h7>
    </div>
</div>
<div class="col">
    <div class="card p-4 mb-4">
        <h6 class="mb-0">{{ __('Total Debit') }} :</h6>
        <h7 class="text-sm mb-0">{{ \Auth::user()->priceFormat($filter['debit']) }}</h7>
    </div>
</div>
<div class="col">
    <div class="card p-4 mb-4">
        <h6 class="mb-0">{{ __('Total Credit') }} :</h6>
        <h7 class="text-sm mb-0">{{ \Auth::user()->priceFormat($filter['credit']) }}</h7>
    </div>
</div>

<div class="col">
    <div class="card p-4 mb-4">
        <h6 class="mb-0">{{ __('Balance') }} :</h6>
        <h7 class="text-sm mb-0">
            {{ $filter['balance'] > 0 ? __('Cr') . '. ' . \Auth::user()->priceFormat(abs($filter['balance'])) : __('Dr') . '. ' . \Auth::user()->priceFormat(abs($filter['balance'])) }}
        </h7>
    </div>
</div>
</div>
@endif --}}
<div class="row mb-4">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th> {{ __('Account Name') }}</th>
                                <th> {{ __('Name') }}</th>
                                <th> {{ __('Transaction Type') }}</th>
                                <th> {{ __('Description') }}</th>
                                <th> {{ __('Transaction Date') }}</th>
                                <th> {{ __('Debit') }}</th>
                                <th> {{ __('Credit') }}</th>
                                <th> {{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $balance = 0;
                            $totalDebit = 0;
                            $totalCredit = 0;
                            $PrevCredit = 0;

                            $accountArrays = [];
                            foreach ($chart_accounts as $key => $account) {
                            $chartDatas = App\Models\Utility::getAccountData(
                            $account['id'],
                            $filter['startDateRange'],
                            $filter['endDateRange'],
                            );
                           // Only fetch previous balance if an account is selected
                           if(request()->get('account') != null){
                            $PrevCredit = App\Models\Utility::getPreviousAccountBalance(
                                    $account['id'],
                                    $filter['startDateRange']
                                );
                            }
                            else{
                                $PrevCredit = 0 ;
                            }
                            $chartDatas = $chartDatas->toArray();
                            $accountArrays[] = $chartDatas;
                            }
                            @endphp
                            <div class="card mb-4 p-4">
                                <h7 class="report-text gray-text mb-0">{{ __('Previous Balance') }} :</h7>
                                <h6 class="report-text mb-0">{{ Auth::user()->priceFormat($PrevCredit) }}</h6>
                            </div>
                            @foreach ($accountArrays as $accounts)
                            @php $firstRow = true; @endphp  {{-- flag for first row --}}
                            @foreach ($accounts as $account)
                            @php
                            $total = $account->debit + $account->credit;
                            // $balance += $total;
                            $totalCredit += $account->credit;
                            $totalDebit += $account->debit;
                            
                            if($firstRow && request()->get('account') != null) {
                                    // For first row, calculate balance as: PrevCredit + (debit - credit)
                                    $balance = $PrevCredit + ($account->debit - $account->credit);
                                    // Add PrevCredit to totalDebit so subsequent rows calculate correctly
                                    $totalDebit += $PrevCredit;
                                    $firstRow = false; // reset flag so it won't add again
                            } else {
                                    $balance = $totalDebit - $totalCredit;
                            }
                            @endphp
                            @if ($account->reference == 'Invoice')
                            <tr>
                                @php
                                $invoice = \App\Models\Invoice::find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @if ($invoice)
                                    <a href="{{ route('invoice.show', \Crypt::encrypt($invoice->id)) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? \Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                    </a>
                                    @else
                                    <span
                                        class="text-danger">{{ $account->ref_number ?? \Auth::user()->invoiceNumberFormat(\App\Models\Invoice::withTrashed()->find($account->ref_id)->invoice_id) }}</span>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>

                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Invoice Delete Product')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('invoice.showItemdelete', [\Crypt::encrypt($account->sub_product_id), $account->deleted_qty, $account->ref_id]) }}"
                                        class="btn btn-outline-primary">{{ 'Delete Product from ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $account->ref_id)->first()->invoice_id) }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>

                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete Invoice')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('invoice.showdelete', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ 'Delete Invoice ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $account->ref_id)->first()->invoice_id) }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>

                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'POS' || $account->reference == 'Delete POS' || $account->reference == 'POS Deletion Reversal')
                            <tr>
                                @php
                                $pos = \App\Models\Pos::find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @if ($pos)
                                    <a href="{{ route('pos.show', \Crypt::encrypt($pos->id)) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? \Auth::user()->posNumberFormat($pos->pos_id) }}
                                    </a>
                                    @else
                                    <span class="text-danger">{{ '#' . __('POS') . sprintf('%05d', $account->ref_id) }}</span>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>

                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'POS Payment')
                            <tr>
                                @php
                                $pos = \App\Models\Pos::withTrashed()->find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @if ($pos)
                                    <a href="{{ route('pos.show', \Crypt::encrypt($pos->id)) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? \Auth::user()->posNumberFormat($pos->pos_id) . ' Payment' }}
                                    </a>
                                    @else
                                    <span class="text-danger">{{ '#' . __('POS') . sprintf('%05d', $account->ref_id) . ' Payment' }}</span>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>

                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif


                            @if ($account->reference == 'Revenue' || $account->reference == 'Delete Revenue')
                            <tr>
                                @php 
                                $revenue = \App\Models\Revenue::find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('revenue.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->type }}</a>
                                </td>
                                <td>{{ $revenue->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance += $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'Bill' || $account->reference == 'Bill Account')
                            <tr>
                                @php
                                $bill = \App\Models\Bill::find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @if ($bill)
                                    <a href="{{ route('bill.show', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                    @else
                                    {{ Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $account->ref_id)->first()->bill_id) }}
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance -= $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Expense' || $account->reference == 'Expense Account' || $account->reference == 'Delete Expense')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @php
                                    $expense = App\Models\Bill::where('id', $account->ref_id)->where('type', 'Expense')->first();
                                    @endphp
                                    @if ($expense)
                                    <a href="{{ route('expense.show', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? $expense->bill_id }}</a>
                                    @else
                                    <a href="{{ route('expense.index') }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? App\Models\Bill::withTrashed()->where('id', $account->ref_id)->first()->bill_id }}</a>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance -= $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if (\App\Models\SimpleExpense::referenceIsExpenseLine($account->reference))
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @php
                                    $simpleExpense = App\Models\SimpleExpense::withTrashed()->where('id', $account->ref_id)->first();
                                    @endphp
                                    @if ($simpleExpense)
                                    <a href="{{ route('simple-expense.show', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? $simpleExpense->expense_id }}</a>
                                    @else
                                    <a href="{{ route('simple-expense.index') }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? __('Service Bill Not Found') }}</a>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if (strpos($account->reference, 'Stock Count') !== false)
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    @php
                                    $warehouse = \App\Models\warehouse::find($account->ref_id);
                                    @endphp
                                    @if ($warehouse)
                                    <a href="{{ route('warehouse.stock-count', $warehouse->id) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? $account->reference }}</a>
                                    @else
                                    <span class="btn btn-outline-primary">{{ $account->ref_number ?? $account->reference }}</span>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'Delete Item From Bill')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>

                                <td><a href="{{ route('bill.showItemdelete', [\Crypt::encrypt($account->sub_product_id), $account->deleted_qty, $account->ref_id]) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? 'Delete Item From Bill  ' . Auth::user()->billNumberFormat(App\Models\Bill::withTrashed()->where('id', $account->ref_id)->first()->bill_id) }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'Expense Payment')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>

                                <td><a href="{{ route('payment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ 'Expense Payment' . $account->ref_number ?? \App\Models\Bill::withTrashed()->where('id', $account->ref_id)->first()->bill_id }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete Expense Payment')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>

                                <td><a href="{{ route('payment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{  $account->ref_number ?? \App\Models\Bill::withTrashed()->where('id', $account->ref_id)->first()->bill_id }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if (\App\Models\SimpleExpense::referenceIsPaymentLine($account->reference))
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>

                                <td><a href="{{ route('payment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? \App\Models\Payment::formatLabelForId($account->payment_id) }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Payment' || $account->reference == 'Bill Payment')
                            <tr>
                                @php
                                    $payment = \App\Models\Payment::find($account->payment_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('payment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? \App\Models\Payment::formatLabelForId($account->payment_id) }}</a>
                                </td>
                                <td>{{ $payment->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance -= $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete Payment' || $account->reference == 'Delete Bill Payment')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('payment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ 'Delete Payment ' . ($account->ref_number ?? \App\Models\Payment::formatLabelForId($account->payment_id)) }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance -= $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'Customer Payment' || $account->reference == 'Invoice Payment')
                            <tr>
                                @php
                                    $customerPayment = \App\Models\CustomerPayment::find($account->payment_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('customerpayment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($account->payment_id) }}</a>
                                </td>
                                <td>{{ $customerPayment->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete Customer Payment' || $account->reference == 'Delete Invoice Payment')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('customerpayment.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ 'Delete Customer Payment ' . ($account->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($account->payment_id)) }}</a>
                                </td>
                                <td>{{ $journal->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'Customer Refund' || $account->reference == 'Delete Customer Refund')
                            <tr>
                                @php
                                    $customerRefund = \App\Models\CustomerRefund::find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td><a href="{{ route('customerrefunds.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->type }}</a>
                                </td>
                                <td>{{ $customerRefund->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->debit + $account->credit;
                                                        $balance -= $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif

                            @if ($account->reference == 'POS Refund')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    <a href="{{ route('pos_product_refund.print', $account->ref_id) }}" target="_blank"
                                        class="btn btn-outline-primary">{{ $account->type }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if (stripos((string) $account->reference, 'Purchase Return') !== false)
                            <tr>
                                @php
                                    $purchaseReturn = \App\Models\PurchaseReturn::withTrashed()->find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    <a href="{{ route('purchase.return.index') }}" class="btn btn-outline-primary">
                                        {{ $account->type }} 
                                    </a>
                                </td>
                                <td>{{ $purchaseReturn->notes ?? $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if (
                                stripos((string) $account->reference, 'Sales Return') !== false ||
                                stripos((string) $account->type, 'Sales Return') !== false
                            )
                            <tr>
                                @php
                                    $salesReturn = \App\Models\SalesReturn::withTrashed()->find($account->ref_id);
                                @endphp
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>
                                    <a href="{{ route('sales.return.index') }}" class="btn btn-outline-primary">
                                        {{ $account->type }}
                                    </a>
                                </td>
                                <td>{{ $salesReturn->notes ?? $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference === 'Bill Refund' ||
                            $account->reference === 'Vendor Refund' ||
                            $account->reference === 'Delete Bill Refund' ||
                            $account->reference === 'Delete Vendor Refund')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->user_name }}</td>
                                <td>@php
                                    $isVendorPayment = $account->ref_id == -1;
                                    $bill = !$isVendorPayment
                                    ? \App\Models\Bill::withTrashed()->find($account->ref_id)
                                    : null;
                                    @endphp

                                    @if (!$isVendorPayment && $bill)
                                    <a href="{{ route('bill.show', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? 'Bill Refund ' . Auth::user()->billNumberFormat($bill->bill_id) }}
                                    </a>
                                    @else
                                    <a href="{{ route('refund.index', \Crypt::encrypt($account->id)) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? $account->type }}
                                    </a>
                                    @endif
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->debit + $account->credit;
                                                    $balance -= $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Journal Entries' || $account->reference == 'Delete Journal Entries' || $account->reference == 'Reverse Journal Entries')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    @php
                                    $journal = \App\Models\JournalEntry::withTrashed()->find(
                                    $account->ref_id,
                                    );
                                    @endphp

                                    @if ($journal && $journal->deleted_at === null)
                                    <a href="{{ route('journal-entry.show', $journal->id) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? Auth::user()->journalNumberFormat($journal->journal_id) }}
                                    </a>
                                    @elseif ($journal && $journal->deleted_at != null)
                                    <a href="{{ route('journal-entry.showdelete', $journal->id) }}"
                                        class="btn btn-outline-primary">
                                        {{ $account->ref_number ?? Auth::user()->journalNumberFormat($journal->journal_id) }}
                                    </a>
                                    @else
                                    <span class="text-danger">Journal Not Found</span>
                                    @endif
                                </td>
                                <td>{{ $journal->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Bank Transfer')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td><a href="{{ route('bank-transfer.index', \Crypt::encrypt($account->ref_id)) }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? $account->type }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->credit - $account->debit;
                                                        $balance += $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete Bank Transfer')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>{{ $account->ref_number ?? $account->type }}
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->credit - $account->debit;
                                                        $balance += $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Debit Note' || $account->reference == 'Delete Debit Note')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('debit.note') }}"
                                        class="btn btn-outline-primary">{{ $account->ref_number ?? $account->type }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                        $total = $account->credit - $account->debit;
                                                        $balance += $total;
                                                    @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Credit Note' || $account->reference == 'Delete Credit Note')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    @if ($account->reference == 'Credit Note')
                                    <a href="{{ route('credit.note') }}"
                                        class="btn btn-outline-primary">{{ $account->type }}</a>
                                    @elseif ($account->reference == 'Delete Credit Note')
                                    <a href="{{ route('credit.note') }}"
                                        class="btn btn-outline-primary">{{ $account->type }}</a>
                                    @endif
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'opening balance')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('chart-of-account.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Assign Car Accessory')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('car_accessories.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Delete - Assign Car Accessory')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('car_accessories.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Direct Expense')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('direct_expenses.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Direct Expense Reversal')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('direct_expenses.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Direct Expense Item Reversal')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('direct_expenses.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Direct Expense Payment')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('direct_expense_payments.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @if ($account->reference == 'Direct Expense Payment Reversal')
                            <tr>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('direct_expense_payments.index') }}"
                                        class="btn btn-outline-primary">{{ $account->reference }}</a>
                                </td>
                                <td>{{ $account->description ?? '' }}</td>
                                <td>{{ $account->send_date }}</td>
                                <td>{{ \Auth::user()->priceFormat($account->debit) }}</td>
                                {{-- @php
                                                    $total = $account->credit - $account->debit;
                                                    $balance += $total;         
                                                @endphp --}}
                                <td>{{ \Auth::user()->priceFormat($account->credit) }}</td>
                                <td>{{ \Auth::user()->priceFormat($balance) }}</td>
                            </tr>
                            @endif
                            @endforeach
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