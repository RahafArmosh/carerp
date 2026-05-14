@extends('layouts.admin')
@section('page-title')
    {{ __('Customer Statement Summary') }}
@endsection
@push('script-page')
    {{-- <script src="https://js.stripe.com/v3/"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script> --}}
    <script type="text/javascript"></script>

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
    <li class="breadcrumb-item">{{ __('Customer Statement Summary') }}</li>
@endsection


@section('action-btn')
    <div class="float-end d-flex align-items-center gap-1 flex-wrap justify-content-end">
        @php
            $exportStartMonth = !empty($filter['startDateRange']) ? \Carbon\Carbon::createFromFormat('M-Y', $filter['startDateRange'])->format('Y-m') : request('start_month');
            $exportEndMonth = !empty($filter['endDateRange']) ? \Carbon\Carbon::createFromFormat('M-Y', $filter['endDateRange'])->format('Y-m') : request('end_month');
        @endphp
        <a href="{{ route('report.customer.statement.export', array_filter(['customer' => $filter['customer'] ?? request('customer'), 'start_month' => $exportStartMonth, 'end_month' => $exportEndMonth, 'account' => request('account')])) }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        {{-- ti-file-type-pdf is not in older Tabler icon sets and can render as a stray dash / missing glyph --}}
        <a href="{{ route('report.customer.statement.pdf', array_filter(['customer' => $filter['customer'] ?? request('customer'), 'start_month' => $exportStartMonth, 'end_month' => $exportEndMonth, 'account' => request('account')])) }}" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="{{ __('PDF') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-printer"></i>
        </a>

        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF(); return false;" data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <i class="ti ti-download"></i>
        </a>
    </div>
@endsection
@section('content')

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('report.customer.statement') }}" method="GET" id="report_account">
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
                                                <label for="account_name" class="form-label">{{ __('Account') }}</label>
                                                <select name="account" id="accounts" class="form-control select select2">
                                                    @foreach ($account as $accountId => $accountLabel)
                                                        <option value="{{ $accountId }}" data-id="{{ $accountId }}"
                                                            {{ isset($_GET['account']) && $_GET['account'] == $accountId ? 'selected' : '' }}>
                                                            {{ $accountLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Customer Selection -->
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="customer_name" class="form-label">{{ __('Customer') }}</label>
                                                <select name="customer" id="customers" class="form-control select select2">
                                                    @foreach ($customer as $customerId => $customerLabel)
                                                        <option value="{{ $customerId }}" data-id="{{ $customerId }}"
                                                            {{ isset($_GET['customer']) && $_GET['customer'] == $customerId ? 'selected' : '' }}>
                                                            {{ $customerLabel }}</option>
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
                                            <a href="{{ route('report.customer.statement') }}"
                                                class="btn btn-sm btn-danger " data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
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
                        value="{{ __('Customer Statement') . ' ' . $filter['customer'] . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
                        id="filename">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                        <h6 class="report-text mb-0">{{ __('Customer Statement Summary') }}</h6>
                    </div>
                </div>
                @if ($filter['account'] != __('All'))
                    <div class="col">
                        <div class="card p-4 mb-4">
                            <h7 class="report-text gray-text mb-0">{{ __('Account') }} :</h7>
                            <h6 class="report-text mb-0">{{ $filter['account'] }}</h6>
                        </div>
                    </div>
                @endif
                @if ($filter['customer'] != __('All'))
                    <div class="col">
                        <div class="card p-4 mb-4">
                            <h7 class="report-text gray-text mb-0">{{ __('Customer') }} :</h7>
                            <h6 class="report-text mb-0">
                                {{ \App\Models\Customer::where('id', $filter['customer'])->first() != null
                                    ? \App\Models\Customer::where('id', $filter['customer'])->first()->name
                                    : '' }}
                            </h6>
                        </div>
                    </div>
                @endif
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}
                        </h6>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-4 mb-4">
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
                            <table class="table datatable">
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
                                @if (!empty($reportData['general_ledger']))
                                    <tbody>
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
                                                @if ($general_ledger->total_debit > 0)
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_debit) }}</td>
                                                @else
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_credit) }}</td>
                                                @endif
                                                <td>
                                                    @if ($general_ledger->reference === 'Invoice')
                                                        @php
                                                            $invoice = App\Models\Invoice::find(
                                                                $general_ledger->ref_id,
                                                            );
                                                        @endphp
                                                        @if ($invoice)
                                                            @if ($general_ledger->ref_number)
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                    class="btn btn-outline-primary">
                                                                    {{ $general_ledger->ref_number }}
                                                                </a>
                                                            @else
                                                                <a href="{{ route('invoice.show', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                                    class="btn btn-outline-primary">
                                                                    {{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                                                </a>
                                                            @endif
                                                        @else
                                                            <span class="text-danger">
                                                                {{ Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $general_ledger->ref_id)->first()->invoice_id) }}
                                                            </span>
                                                        @endif
                                                    @elseif ($general_ledger->reference === 'Invoice Delete Product')
                                                        @php
                                                            $subProductId = !empty($general_ledger->sub_product_id) ? \Crypt::encrypt($general_ledger->sub_product_id) : null;
                                                            $deletedQty = (int)($general_ledger->deleted_qty ?? 0);
                                                            $invoiceId = !empty($general_ledger->ref_id) ? (int)$general_ledger->ref_id : null;
                                                        @endphp
                                                        @if ($subProductId && $invoiceId)
                                                            <a href="{{ route('invoice.showItemdelete', ['id' => $subProductId, 'qty' => $deletedQty, 'inv_id' => $invoiceId]) }}"
                                                                class="btn btn-outline-primary">{{ 'Delete Product from ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $general_ledger->ref_id)->first()->invoice_id) }}</a>
                                                        @else
                                                            <span class="text-muted">{{ 'Delete Product from ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $general_ledger->ref_id)->first()->invoice_id) }}</span>
                                                        @endif
                                                    @elseif ($general_ledger->reference === 'Delete Invoice')
                                                        <a href="{{ route('invoice.showdelete', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ 'Delete Invoice ' . \Auth::user()->invoiceNumberFormat(App\Models\Invoice::withTrashed()->where('id', $general_ledger->ref_id)->first()->invoice_id) }}</a>
                                                    @elseif ($general_ledger->reference == 'Revenue' || $general_ledger->reference == 'Delete Revenue')
                                                        <a href="{{ route('revenue.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Customer Payment' || $general_ledger->reference == 'Invoice Payment')
                                                        <a href="{{ route('customerpayment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($general_ledger->payment_id) }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete Customer Payment' || $general_ledger->reference == 'Delete Invoice Payment')
                                                        <a href="{{ route('customerpayment.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ 'Delete Customer Payment ' . ($general_ledger->ref_number ?? \App\Models\CustomerPayment::formatLabelForId($general_ledger->payment_id)) }}</a>
                                                    @elseif ($general_ledger->reference == 'Customer Refund' || $general_ledger->reference == 'Delete Customer Refund')
                                                        <a href="{{ route('customerrefunds.index', \Crypt::encrypt($general_ledger->ref_id)) }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Credit Note' || $general_ledger->reference == 'Delete Credit Note')
                                                        <a href="{{ route('credit.note') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'opening balance')
                                                        <a href="{{ route('chart-of-account.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS Refund')
                                                        <a href="{{ route('pos_product_refund.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS Refund')
                                                        <a href="{{ route('pos_product_refund.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete opening balance')
                                                        <a href="{{ route('chart-of-account.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS_payment')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS Payment')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS Payment')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS_payment')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS Payment Reversal')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'POS Deletion Reversal')
                                                        <a href="{{ route('pos.report') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type ?: __('POS Deletion Reversal') }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS_payment')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @elseif ($general_ledger->reference == 'Delete POS Payment Reversal')
                                                        <a href="{{ route('pos.index') }}"
                                                            class="btn btn-outline-primary">{{ $general_ledger->type }}</a>
                                                    @else
                                                        <span class="text-muted">No reference</span>
                                                    @endif
                                                </td>
                                                <td>{{ \App\Models\Customer::where('id', $general_ledger->user_id)->first()->name }}
                                                </td>
                                                <td>{{ \App\Models\ChartOfAccount::where('id', $general_ledger->account)->first()->name }}
                                                </td>
                                                <td>{{ Auth::user()->priceFormat($general_ledger->total_debit) }}</td>
                                                <td>{{ Auth::user()->priceFormat($general_ledger->total_credit) }}</td>
                                                <td>{{ \Auth::user()->priceFormat($totalDebit - $totalCredit) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
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
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
