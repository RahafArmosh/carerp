@extends('layouts.admin')
@section('page-title')
    {{ __('Payable Reports') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payable Reports') }}</li>
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
        <form method="POST" action="{{ route('payables.print') }}">
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
                            <form id="report_payable_summary" action="{{ route('report.payables') }}" method="GET">
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
                                                    <label for="start_date"
                                                        class="form-label">{{ __('Start Date') }}</label>
                                                    <input type="date" id="start_date" name="start_date"
                                                        value="{{ $filter->startDateRange ?? $filter['startDateRange'] ?? '' }}"
                                                        class="startDate form-control">
                                                </div>
                                            </div>

                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                                    <input type="date" id="end_date" name="end_date"
                                                        value="{{ $filter->endDateRange ?? $filter['endDateRange'] ?? '' }}" class="endDate form-control">
                                                </div>
                                            </div>
                                            <input type="hidden" name="report" class="report">
                                        </div>
                                    </div>
                                    <div class="col-auto mt-4">
                                        <div class="row">
                                            <div class="col-auto">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('report_payable_summary').submit(); return false;"
                                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                    data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>

                                                <a href="{{ route('report.payables') }}" class="btn btn-sm btn-danger "
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
                                    <a class="nav-link active" id="payable-tab1" data-bs-toggle="pill"
                                        href="#vendor_balance" role="tab" aria-controls="pills-vendor-balance"
                                        aria-selected="true">{{ __('Vendor Balance') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="payable-tab2" data-bs-toggle="pill" href="#payable_summary"
                                        role="tab" aria-controls="pills-payable-summary"
                                        aria-selected="false">{{ __('Payable Summary') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="payable-tab3" data-bs-toggle="pill" href="#payable_details"
                                        role="tab" aria-controls="pills-payable-details"
                                        aria-selected="false">{{ __('Payable Details') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="tab-content" id="myTabContent2">
                                    <div class="tab-pane fade fade show active" id="vendor_balance" role="tabpanel"
                                        aria-labelledby="payable-tab1">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th width="25%"> {{ __('Vendor Name') }}</th>
                                                    <th width="25%"> {{ __('Total Debit') }}</th>
                                                    <th width="25%"> {{ __('Total Credit') }}</th>
                                                    <th class="text-end" width="25%"> {{ __('Balance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalDebit = 0;
                                                    $totalCredit = 0;
                                                    $totalBalance = 0;
                                                @endphp
                                                @foreach ($payableVendors as $vendor)
                                                    @php
                                                        $debit = floatval($vendor->total_debit ?? $vendor['total_debit'] ?? 0);
                                                        $credit = floatval($vendor->total_credit ?? $vendor['total_credit'] ?? 0);
                                                        $balance = floatval($vendor->balance ?? $vendor['balance'] ?? 0);
                                                        $totalDebit += $debit;
                                                        $totalCredit += $credit;
                                                        $totalBalance += $balance;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $vendor->name ?? $vendor['name'] }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($debit) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($credit) }}</td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($balance) }}</td>
                                                    </tr>
                                                @endforeach
                                                @if (count($payableVendors) > 0)
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalDebit) }}</th>
                                                        <th>{{ \Auth::user()->priceFormat($totalCredit) }}</th>
                                                        <th class="text-end">{{ \Auth::user()->priceFormat($totalBalance) }}</th>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="tab-pane fade fade show" id="payable_summary" role="tabpanel"
                                        aria-labelledby="payable-tab2">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Vendor Name') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Transaction') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Transaction Type') }}</th>
                                                    <th>{{ __('Total') }}</th>
                                                    <th>{{ __('Balance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total = 0;
                                                    $totalAmount = 0;

                                                    usort($payableSummaries, function($a, $b) {
                                                        $dateA = $a->bill_date ?? $a['bill_date'] ?? '';
                                                        $dateB = $b->bill_date ?? $b['bill_date'] ?? '';
                                                        return strtotime($dateB) - strtotime($dateA);
                                                    });
                                                @endphp
                                                @foreach ($payableSummaries as $payableSummary)
                                                    <tr>
                                                        @php
                                                            $bill = $payableSummary->bill ?? $payableSummary['bill'] ?? null;
                                                            $price = $payableSummary->price ?? $payableSummary['price'] ?? 0;
                                                            $totalTax = $payableSummary->total_tax ?? $payableSummary['total_tax'] ?? 0;
                                                            $payPrice = $payableSummary->pay_price ?? $payableSummary['pay_price'] ?? 0;
                                                            
                                                            if ($bill) {
                                                                $payableBalance = $price + $totalTax;
                                                            } else {
                                                                $payableBalance = -$price;
                                                            }
                                                            $pay_price = $payPrice != null ? $payPrice : 0;
                                                            $balance = $payableBalance - $pay_price;
                                                            $total += $balance;
                                                            $totalAmount += $payableBalance;
                                                        @endphp
                                                        <td> {{ $payableSummary->name ?? $payableSummary['name'] }}</td>
                                                        <td> {{ $payableSummary->bill_date ?? $payableSummary['bill_date'] }}</td>
                                                        @if ($bill)
                                                            @if (($payableSummary->type ?? $payableSummary['type']) == 'Bill')
                                                                <td> {{ \Auth::user()->billNumberFormat($bill) }}
                                                                </td>
                                                            @elseif(($payableSummary->type ?? $payableSummary['type']) == 'Expense')
                                                                <td> {{$bill }}
                                                                </td>
                                                            @endif
                                                        @else
                                                            <td>{{ __('Debit Note') }}</td>
                                                        @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $status = $payableSummary->status ?? $payableSummary['status'] ?? 0;
                                                            @endphp
                                                            @if ($status == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 3)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 4)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @else
                                                                <span class="p-2 px-3">-</span>
                                                            @endif
                                                        </td>
                                                        @if ($bill)
                                                            <td> {{ $payableSummary->type ?? $payableSummary['type'] }}
                                                            @else
                                                            <td>{{ __('Debit Note') }}</td>
                                                        @endif
                                                        <td> {{ \Auth::user()->priceFormat($payableBalance) }} </td>

                                                        <td> {{ \Auth::user()->priceFormat($balance) }} </td>

                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if (count($payableSummaries) > 0)
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
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

                                    <div class="tab-pane fade fade show" id="payable_details" role="tabpanel"
                                        aria-labelledby="payable-tab3">
                                        <table class="table table-flush" id="report-dataTable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Vendor Name') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Transaction') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Transaction Type') }}</th>
                                                    <th>{{ __('Item Name') }}</th>
                                                    <th>{{ __('Quantity Ordered') }}</th>
                                                    <th>{{ __('Item Price') }}</th>
                                                    <th>{{ __('Total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total = 0;
                                                    $totalQuantity = 0;

                                                    usort($payableDetails, function($a, $b) {
                                                        $dateA = $a->bill_date ?? $a['bill_date'] ?? '';
                                                        $dateB = $b->bill_date ?? $b['bill_date'] ?? '';
                                                        return strtotime($dateB) - strtotime($dateA);
                                                    });
                                                @endphp
                                                @foreach ($payableDetails as $payableDetail)
                                                    <tr>
                                                        @php
                                                            $bill = $payableDetail->bill ?? $payableDetail['bill'] ?? null;
                                                            $price = $payableDetail->price ?? $payableDetail['price'] ?? 0;
                                                            $quantity = $payableDetail->quantity ?? $payableDetail['quantity'] ?? 0;
                                                            
                                                            if ($bill) {
                                                                $receivableBalance = $price;
                                                            } else {
                                                                $receivableBalance = -$price;
                                                            }
                                                            if ($bill) {
                                                                $quantity = $quantity;
                                                            } else {
                                                                $quantity = 0;
                                                            }

                                                            if ($bill) {
                                                                $itemTotal = $receivableBalance * $quantity;
                                                            } else {
                                                                $itemTotal = -$price;
                                                            }
                                                            $total += $itemTotal;
                                                            $totalQuantity += $quantity;
                                                        @endphp
                                                        <td> {{ $payableDetail->name ?? $payableDetail['name'] }}</td>
                                                        <td> {{ $payableDetail->bill_date ?? $payableDetail['bill_date'] }}</td>
                                                        @if ($bill)
                                                            @if (($payableDetail->type ?? $payableDetail['type']) == 'Bill')
                                                                <td> {{ \Auth::user()->billNumberFormat($bill) }}
                                                                </td>
                                                            @elseif(($payableDetail->type ?? $payableDetail['type']) == 'Expense')
                                                                <td> {{ $bill }}
                                                                </td>
                                                            @endif
                                                        @else
                                                            <td>{{ __('Debit Note') }}</td>
                                                        @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $status = $payableDetail->status ?? $payableDetail['status'] ?? 0;
                                                            @endphp
                                                            @if ($status == 0)
                                                                <span
                                                                    class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 1)
                                                                <span
                                                                    class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 2)
                                                                <span
                                                                    class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 3)
                                                                <span
                                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @elseif($status == 4)
                                                                <span
                                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$status]) }}</span>
                                                            @else
                                                                <span class="p-2 px-3">-</span>
                                                            @endif
                                                        </td>
                                                        @if ($bill)
                                                            <td> {{ $payableDetail->type ?? $payableDetail['type'] }}
                                                            @else
                                                            <td>{{ __('Debit Note') }}</td>
                                                        @endif
                                                        <td>{{ $payableDetail->product_name ?? $payableDetail['product_name'] }}</td>
                                                        <td> {{ $quantity }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($receivableBalance) }}</td>
                                                        <td>{{ \Auth::user()->priceFormat($itemTotal) }}</td>

                                                    </tr>
                                                @endforeach
                                                @if (count($payableDetails) > 0)
                                                    <tr>
                                                        <th>{{ __('Total') }}</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th>{{ $totalQuantity }}</th>
                                                        <th></th>
                                                        <th>{{ \Auth::user()->priceFormat($total) }}</th>
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
