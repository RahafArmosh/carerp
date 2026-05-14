@extends('layouts.admin')
@section('page-title')
    {{ __('Receivable Reports') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Receivable Reports') }}</li>
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
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var start_date = $(".startDate").val();
                var end_date = $(".endDate").val();

                $('.start_date').val(start_date);
                $('.end_date').val(end_date);
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            var id1 = $('.nav-item .active').attr('href');
            $('.report').val(id1);

            $("ul.nav-pills > li > a").click(function() {
                var report = $(this).attr('href');
                $('.report').val(report);
            });
        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <form method="POST" action="{{ route('receivables.print') }}">
            @csrf
            <input type="hidden" name="start_date" class="start_date">
            <input type="hidden" name="end_date" class="end_date">
            <input type="hidden" name="report" class="report">
            <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Print') }}"
                data-original-title="{{ __('Print') }}"><i class="ti ti-printer"></i></button>
        </form>
    </div>



    <div class="float-end me-2" id="filter">
        <button id="filter" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
    </div>

    {{-- <div class="float-end me-2">
        <a href="{{ route('report.balance.sheet', 'vertical') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Vertical View') }}" data-original-title="{{ __('Vertical View') }}"><i
                class="ti ti-separator-horizontal"></i></a>
    </div> --}}
@endsection

@section('content')
    <div class="mt-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="mt-2" id="multiCollapseExample1">
                    <div class="card" id="show_filter" style="display:none;">
                        <div class="card-body">
                            <form method="GET" action="{{ route('report.receivables') }}" id="report_bill_summary">
                                <div class="row align-items-center justify-content-end">
                                    <div class="col-xl-10">
                                        <div class="row">
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                                    <select name="customer" id="customer" class="form-control select select2">
                                                        @foreach ($customers as $cid => $cname)
                                                            <option value="{{ $cid }}" {{ (string)($filter['customer'] ?? '') === (string)$cid ? 'selected' : '' }}>
                                                                {{ $cname }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="start_date"
                                                        class="form-label">{{ __('Start Date') }}</label>
                                                    <input type="date" name="start_date" id="start_date"
                                                        class="startDate form-control"
                                                        value="{{ $filter['startDateRange'] }}">
                                                </div>
                                            </div>

                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                                    <input type="date" name="end_date" id="end_date"
                                                        class="endDate form-control" value="{{ $filter['endDateRange'] }}">
                                                </div>

                                            </div>
                                            <input type="hidden" name="report" class="report">
                                        </div>
                                    </div>
                                    <div class="col-auto mt-4">
                                        <div class="row">
                                            <div class="col-auto">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('report_bill_summary').submit(); return false;"
                                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                    data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>

                                                <a href="{{ route('report.receivables') }}" class="btn btn-sm btn-danger "
                                                    data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                    data-original-title="{{ __('Reset') }}">
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
        </div>

        <div class="row">
            <div class="col-12" id="invoice-container">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between w-100">
                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="receivable-tab1" data-bs-toggle="pill"
                                        href="#customer_balance" role="tab" aria-controls="pills-customer-balance"
                                        aria-selected="true">{{ __('Customer Balance') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="receivable-tab2" data-bs-toggle="pill"
                                        href="#receivable_summary" role="tab"
                                        aria-controls="pills-receivable-summary"
                                        aria-selected="false">{{ __('Receivable Summary') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="receivable-tab3" data-bs-toggle="pill"
                                        href="#receivable_details" role="tab"
                                        aria-controls="pills-receivable-details"
                                        aria-selected="false">{{ __('Receivable Details') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="receivable-tab4" data-bs-toggle="pill" href="#aging_summary"
                                        role="tab" aria-controls="pills-aging-summary"
                                        aria-selected="false">{{ __('Aging Summary') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="receivable-tab5" data-bs-toggle="pill" href="#aging_details"
                                        role="tab" aria-controls="pills-aging-details"
                                        aria-selected="false">{{ __('Aging Details') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="tab-content" id="myTabContent2">
                                    <div class="tab-pane fade fade show active" id="customer_balance" role="tabpanel"
                                        aria-labelledby="receivable-tab1">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th width="33%">{{ __('Customer Name') }}</th>
                                                    <th width="20%">{{ __('Previous Balance') }}</th>
                                                    <th width="33%">{{ __('Total Debit') }}</th>
                                                    <th width="33%">{{ __('Total Credits') }}</th>
                                                    <th class="text-end">{{ __('Balance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalBalanceDebit = 0;
                                                    $totalBalanceCredit = 0;
                                                    $totalPreviousBalance = 0;
                                                @endphp
                                        
                                                @foreach ($receivableCustomers as $customer)
                                                    @php
                                                        $previousBalance = (float)($customer->previous_balance ?? 0);
                                                        $balance = $previousBalance + ((float)$customer->total_debit - (float)$customer->total_credit);
                                                        $totalBalanceDebit += $customer->total_debit;
                                                        $totalBalanceCredit += $customer->total_credit;
                                                        $totalPreviousBalance += $previousBalance;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $customer->customer_name }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($previousBalance) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($customer->total_debit) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($customer->total_credit) }}</td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($balance) }}</td>
                                                    </tr>
                                                @endforeach
                                        
                                                @if (!empty($receivableCustomers))
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <td>{{ \Auth::user()->priceFormat($totalPreviousBalance) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($totalBalanceDebit) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($totalBalanceCredit) }}</td>
                                                        <th class="text-end">
                                                            {{ \Auth::user()->priceFormat($totalPreviousBalance + ($totalBalanceDebit - $totalBalanceCredit)) }}
                                                        </th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>                                                                           
                                    </div>
                                    <div class="tab-pane fade fade show" id="receivable_summary" role="tabpanel"
                                        aria-labelledby="receivable-tab2">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Customer Name') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Transaction') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Payment Status') }}</th>
                                                    <th>{{ __('Transaction Type') }}</th>
                                                    <th>{{ __('Total') }}</th>
                                                    <th>{{ __('Balance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total = 0;
                                                    $totalAmount = 0;
                                                    $receivableBalance = 0;
                                                    $balance  = 0;
                                                    function compare($a, $b)
                                                    {
                                                        return strtotime($b['issue_date']) -
                                                            strtotime($a['issue_date']);
                                                    }
                                                    usort($receivableSummaries, 'compare');
                                                @endphp
                                                @foreach ($receivableSummaries as $receivableSummary)
                                                    <tr>
                                                        @php
                                                            if ($receivableSummary['invoice']) {
                                                                $receivableBalance =
                                                                    $receivableSummary['price'] +
                                                                    $receivableSummary['total_tax'] + $receivableSummary['total_expense'];
                                                            } else {
                                                                $receivableBalance = -$receivableSummary['price'];
                                                            }
                                                            $pay_price =
                                                                $receivableSummary['pay_price'] != null
                                                                    ? $receivableSummary['pay_price']
                                                                    : 0;
                                                            $balance = $receivableBalance - $pay_price;
                                                            $total += $balance;
                                                            $totalAmount += $receivableBalance;
                                                        @endphp
                                                        <td> {{ $receivableSummary['name'] }}</td>
                                                        <td> {{ $receivableSummary['issue_date'] }}</td>
                                                        @if ($receivableSummary['invoice'])
                                                            <td> {{ \Auth::user()->invoiceNumberFormat($receivableSummary['invoice']) }}
                                                            @else
                                                            <td>{{ __('Credit Note') }}</td>
                                                        @endif
                                                        </td>
                                                        <td>
                                                            @if ($receivableSummary['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableSummary['status']]) }}</span>
                                                            @elseif($receivableSummary['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableSummary['status']]) }}</span>
                                                            @elseif($receivableSummary['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableSummary['status']]) }}</span>
                                                            @elseif($receivableSummary['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableSummary['status']]) }}</span>
                                                            @elseif($receivableSummary['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableSummary['status']]) }}</span>
                                                            @else
                                                                <span class="p-2 px-3">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($receivableSummary['invoice'])
                                                                @php $ps = $receivableSummary['payment_status'] ?? 0; @endphp
                                                                @php
                                                                    $cpStatus = $receivableSummary['customer_payment_status'] ?? null; // 0=draft, 2=received
                                                                    $paymentBadgeClass = 'bg-light text-dark';
                                                                    if ((int) $cpStatus === 2) {
                                                                        $paymentBadgeClass = 'bg-success';
                                                                    } elseif ((int) $cpStatus === 0) {
                                                                        $paymentBadgeClass = 'bg-warning text-dark';
                                                                    }
                                                                @endphp
                                                                <span class="status_badge badge {{ $paymentBadgeClass }} p-2 px-3 rounded">
                                                                    {{ __(\App\Models\Invoice::$paymentstatues[$ps] ?? '-') }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        @if ($receivableSummary['invoice'])
                                                            <td> {{ __('Invoice') }}
                                                            @else
                                                            <td>{{ __('Credit Note') }}</td>
                                                        @endif
                                                        <td> {{ \Auth::user()->priceFormat($receivableBalance) }} </td>

                                                        <td> {{ \Auth::user()->priceFormat($balance) }} </td>

                                                        {{-- <td> {{ !empty($receivableCustomer['credit_price']) ? \Auth::user()->priceFormat($receivableCustomer['credit_price']) : \Auth::user()->priceFormat(0) }} --}}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if ($receivableSummaries != [])
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($totalAmount) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($total) }}</th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane fade fade show" id="receivable_details" role="tabpanel"
                                        aria-labelledby="receivable-tab3">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Customer Name') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Transaction') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Payment Status') }}</th>
                                                    <th>{{ __('Transaction Type') }}</th>
                                                    <th>{{ __('Item Name') }}</th>
                                                    <th>{{ __('Quantity Ordered') }}</th>
                                                    <th>{{ __('Item Price') }}</th>
                                                    <th>{{ __('Expenses') }}</th>
                                                    <th>{{ __('Tax') }}</th>
                                                    <th>{{ __('Total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total = 0;
                                                    $totalItem = 0;
                                                    $totalExpens = 0;
                                                    $totalTax = 0;
                                                    $totalQuantity = 0;

                                                    function compares($a, $b)
                                                    {
                                                        return strtotime($b['issue_date']) -
                                                            strtotime($a['issue_date']);
                                                    }
                                                    usort($receivableDetails, 'compares');
                                                @endphp
                                                @foreach ($receivableDetails as $receivableDetail)
                                                    <tr>
                                                        @php
                                                            if ($receivableDetail['invoice']) {
                                                                $receivableBalance = $receivableDetail['price'];
                                                                $receivableexpense = $receivableDetail['total_expense'];
                                                                $receivableTax = $receivableDetail['total_tax'];
                                                            } else {
                                                                $receivableBalance = -$receivableDetail['price'];
                                                                $receivableexpense = $receivableDetail['total_expense'];
                                                                $receivableTax = $receivableDetail['total_tax'];
                                                            }
                                                            if ($receivableDetail['invoice']) {
                                                                $quantity = $receivableDetail['quantity'];
                                                            } else {
                                                                $quantity = 0;
                                                            }

                                                            if ($receivableDetail['invoice']) {
                                                                $itemTotal =
                                                                    ($receivableBalance * $receivableDetail['quantity']) + $receivableDetail['total_expense'] + $receivableDetail['total_tax'];
                                                            } else {
                                                                $itemTotal = -$receivableDetail['price'];
                                                            }

                                                            $total += $itemTotal;
                                                            $totalQuantity += $quantity;
                                                            $totalItem += $receivableBalance;
                                                            $totalExpens += $receivableexpense ;
                                                            $totalTax += $receivableTax;
                                                        @endphp
                                                        <td> {{ $receivableDetail['name'] }}</td>
                                                        <td> {{ $receivableDetail['issue_date'] }}</td>
                                                        @if ($receivableDetail['invoice'])
                                                            <td> {{ \Auth::user()->invoiceNumberFormat($receivableDetail['invoice']) }}
                                                            </td>
                                                        @else
                                                            <td>{{ __('Credit Note') }}</td>
                                                        @endif
                                                        </td>
                                                        <td>
                                                            @if ($receivableDetail['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableDetail['status']]) }}</span>
                                                            @elseif($receivableDetail['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableDetail['status']]) }}</span>
                                                            @elseif($receivableDetail['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableDetail['status']]) }}</span>
                                                            @elseif($receivableDetail['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableDetail['status']]) }}</span>
                                                            @elseif($receivableDetail['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$receivableDetail['status']]) }}</span>
                                                            @else
                                                                <span class="p-2 px-3">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($receivableDetail['invoice'])
                                                                @php $ps = $receivableDetail['payment_status'] ?? 0; @endphp
                                                                @php
                                                                    $cpStatus = $receivableDetail['customer_payment_status'] ?? null; // 0=draft, 2=received
                                                                    $paymentBadgeClass = 'bg-light text-dark';
                                                                    if ((int) $cpStatus === 2) {
                                                                        $paymentBadgeClass = 'bg-success';
                                                                    } elseif ((int) $cpStatus === 0) {
                                                                        $paymentBadgeClass = 'bg-warning text-dark';
                                                                    }
                                                                @endphp
                                                                <span class="status_badge badge {{ $paymentBadgeClass }} p-2 px-3 rounded">
                                                                    {{ __(\App\Models\Invoice::$paymentstatues[$ps] ?? '-') }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        @if ($receivableDetail['invoice'])
                                                            <td> {{ __('Invoice') }}</td>
                                                        @else
                                                            <td>{{ __('Credit Note') }}</td>
                                                        @endif
                                                        <td>{{ $receivableDetail['product_name'] }}</td>
                                                        <td> {{ $quantity }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($receivableBalance) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($receivableexpense) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($receivableTax) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($itemTotal) }}</td>

                                                    </tr>
                                                @endforeach
                                                @if ($receivableSummaries != [])
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ $totalQuantity }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalItem) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalExpens) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalTax) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($total) }}</th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane fade fade show" id="aging_summary" role="tabpanel"
                                        aria-labelledby="receivable-tab4">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Customer Name') }}</th>
                                                    <th>{{ __('Current') }}</th>
                                                    <th>{{ __('1-15 DAYS') }}</th>
                                                    <th>{{ __('16-30 DAYS') }}</th>
                                                    <th>{{ __('31-45 DAYS') }}</th>
                                                    <th>{{ __('> 45 DAYS') }}</th>
                                                    <th>{{ __('Total') }}</th>
                                                </tr>
                                            </thead>



                                            <tbody>
                                                @php
                                                    $currentTotal = 0;
                                                    $days15 = 0;
                                                    $days30 = 0;
                                                    $days45 = 0;
                                                    $daysMore45 = 0;
                                                    $total = 0;

                                                @endphp
                                                @foreach ($agingSummaries as $key => $agingSummary)
                                                    <tr>
                                                        <td> {{ $key }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['current']) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['1_15_days']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['16_30_days']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['31_45_days']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['greater_than_45_days']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($agingSummary['total_due']) }}
                                                        </td>
                                                    </tr>

                                                    @php
                                                        $currentTotal += $agingSummary['current'];
                                                        $days15 += $agingSummary['1_15_days'];
                                                        $days30 += $agingSummary['16_30_days'];
                                                        $days45 += $agingSummary['31_45_days'];
                                                        $daysMore45 += $agingSummary['greater_than_45_days'];
                                                        $total += $agingSummary['total_due'];

                                                    @endphp
                                                @endforeach
                                                @if ($agingSummaries != [])
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($currentTotal) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days15) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days30) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days45) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($daysMore45) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($total) }}</th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane fade fade show" id="aging_details" role="tabpanel"
                                        aria-labelledby="receivable-tab5">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Transaction') }}</th>
                                                    <th>{{ __('Type') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Customer Name') }}</th>
                                                    <th>{{ __('Age') }}</th>
                                                    <th>{{ __('Amount') }}</th>
                                                    <th>{{ __('Balance Due') }}</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @php
                                                    $currentTotal = 0;
                                                    $currentDue = 0;
                                                    $days15Total = 0;
                                                    $days15Due = 0;

                                                    $days30Total = 0;
                                                    $days30Due = 0;

                                                    $days45Total = 0;
                                                    $days45Due = 0;

                                                    $daysMore45Total = 0;
                                                    $daysMore45Due = 0;

                                                    $total = 0;
                                                @endphp
                                                @if ($moreThan45 != [])
                                                    <tr>
                                                        <th>{{ __(' > 45 Days') }}</th>
                                                    </tr>
                                                @endif
                                                @foreach ($moreThan45 as $value)
                                                    @php
                                                        $daysMore45Total += $value['total_price'];
                                                        $daysMore45Due += $value['balance_due'];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $value['due_date'] }}</td>
                                                        <td>{{ \Auth::user()->invoiceNumberFormat($value['invoice_id']) }}
                                                        </td>
                                                        <td>{{ __('Invoice') }}</td>
                                                        <td>
                                                            @if ($value['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$value['status']]) }}</span>
                                                            @elseif($value['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$value['status']]) }}</span>
                                                            @elseif($value['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$value['status']]) }}</span>
                                                            @elseif($value['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$value['status']]) }}</span>
                                                            @elseif($value['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$value['status']]) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $value['name'] }}</td>
                                                        <td> {{ $value['age'] . __(' Days') }} </td>
                                                        <td>{{ \Auth::user()->priceFormat($value['total_price']) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($value['balance_due']) }}</td>
                                                    </tr>
                                                @endforeach
                                                @if ($moreThan45 != [])
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($daysMore45Total) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($daysMore45Due) }}</th>
                                                    </tr>
                                                @endif


                                                @if ($days31to45 != [])
                                                    <tr>
                                                        <th>{{ __(' 31 to 45 Days') }}</th>
                                                    </tr>
                                                @endif
                                                @foreach ($days31to45 as $day31to45)
                                                    @php
                                                        $days45Total += $day31to45['total_price'];
                                                        $days45Due += $day31to45['balance_due'];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $day31to45['due_date'] }}</td>
                                                        <td>{{ \Auth::user()->invoiceNumberFormat($day31to45['invoice_id']) }}
                                                        </td>
                                                        <td>{{ __('Invoice') }}</td>
                                                        <td>
                                                            @if ($day31to45['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day31to45['status']]) }}</span>
                                                            @elseif($day31to45['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day31to45['status']]) }}</span>
                                                            @elseif($day31to45['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day31to45['status']]) }}</span>
                                                            @elseif($day31to45['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day31to45['status']]) }}</span>
                                                            @elseif($day31to45['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day31to45['status']]) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $day31to45['name'] }}</td>
                                                        <td> {{ $day31to45['age'] . __(' Days') }} </td>
                                                        <td>{{ \Auth::user()->priceFormat($day31to45['total_price']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($day31to45['balance_due']) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if ($days31to45 != [])
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($days45Total) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days45Due) }}</th>
                                                    </tr>
                                                @endif

                                                @if ($days16to30 != [])
                                                    <tr>
                                                        <th>{{ __(' 16 to 30 Days') }}</th>
                                                    </tr>
                                                @endif
                                                @foreach ($days16to30 as $day16to30)
                                                    @php
                                                        $days30Total += $day16to30['total_price'];
                                                        $days30Due += $day16to30['balance_due'];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $day16to30['due_date'] }}</td>
                                                        <td>{{ \Auth::user()->invoiceNumberFormat($day16to30['invoice_id']) }}
                                                        </td>
                                                        <td>{{ __('Invoice') }}</td>
                                                        <td>
                                                            @if ($day16to30['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day16to30['status']]) }}</span>
                                                            @elseif($day16to30['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day16to30['status']]) }}</span>
                                                            @elseif($day16to30['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day16to30['status']]) }}</span>
                                                            @elseif($day16to30['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day16to30['status']]) }}</span>
                                                            @elseif($day16to30['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day16to30['status']]) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $day16to30['name'] }}</td>
                                                        <td> {{ $day16to30['age'] . __(' Days') }} </td>
                                                        <td>{{ \Auth::user()->priceFormat($day16to30['total_price']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($day16to30['balance_due']) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if ($days16to30 != [])
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($days30Total) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days30Due) }}</th>
                                                    </tr>
                                                @endif

                                                @if ($days1to15 != [])
                                                    <tr>
                                                        <th>{{ __(' 1 to 15 Days') }}</th>
                                                    </tr>
                                                @endif
                                                @foreach ($days1to15 as $day1to15)
                                                    @php
                                                        $days15Total += $day1to15['total_price'];
                                                        $days15Due += $day1to15['balance_due'];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $day1to15['due_date'] }}</td>
                                                        <td>{{ \Auth::user()->invoiceNumberFormat($day1to15['invoice_id']) }}
                                                        </td>
                                                        <td>{{ __('Invoice') }}</td>
                                                        <td>
                                                            @if ($day1to15['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day1to15['status']]) }}</span>
                                                            @elseif($day1to15['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day1to15['status']]) }}</span>
                                                            @elseif($day1to15['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day1to15['status']]) }}</span>
                                                            @elseif($day1to15['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day1to15['status']]) }}</span>
                                                            @elseif($day1to15['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$day1to15['status']]) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $day1to15['name'] }}</td>
                                                        <td> {{ $day1to15['age'] . __(' Days') }} </td>
                                                        <td>{{ \Auth::user()->priceFormat($day1to15['total_price']) }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($day1to15['balance_due']) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if ($days1to15 != [])
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($days15Total) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($days15Due) }}</th>
                                                    </tr>
                                                @endif

                                                @if ($currents != [])
                                                    <tr>
                                                        <th>{{ __('Current') }}</th>
                                                    </tr>
                                                @endif
                                                @foreach ($currents as $current)
                                                    @php
                                                        $currentTotal += $current['total_price'];
                                                        $currentDue += $current['balance_due'];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $current['due_date'] }}</td>
                                                        <td>{{ \Auth::user()->invoiceNumberFormat($current['invoice_id']) }}
                                                        </td>
                                                        <td>{{ __('Invoice') }}</td>
                                                        <td>
                                                            @if ($current['status'] == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$current['status']]) }}</span>
                                                            @elseif($current['status'] == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$current['status']]) }}</span>
                                                            @elseif($current['status'] == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$current['status']]) }}</span>
                                                            @elseif($current['status'] == 4)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$current['status']]) }}</span>
                                                            @elseif($current['status'] == 6)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$current['status']]) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $current['name'] }}</td>
                                                        <td> - </td>
                                                        <td>{{ \Auth::user()->priceFormat($current['total_price']) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($current['balance_due']) }}</td>
                                                    </tr>
                                                @endforeach
                                                @if ($currents != [])
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($currentTotal) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($currentDue) }}</th>
                                                    </tr>
                                                @endif
                                                @if ($currents != [] || $days1to15 != [] || $days16to30 != [] || $days31to45 != [] || $moreThan45 != [])
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($currentTotal + $days15Total + $days30Total + $days45Total + $daysMore45Total) }}
                                                        </th>
                                                        <th>{{ \Auth::user()->priceFormat($currentDue + $days15Due + $days30Due + $days45Due + $daysMore45Due) }}
                                                        </th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
