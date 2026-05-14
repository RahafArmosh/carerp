{{-- @extends('layouts.admin') --}}
@php
    $settings = Utility::settings();
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';
@endphp
<html lang="en" dir="{{ $settings == 'on' ? 'rtl' : '' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/main.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/style.css') }}">

    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <!-- vendor css -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <title>{{ env('APP_NAME') }} - Payable Report</title>
    @if (isset($settings['SITE_RTL']) && $settings['SITE_RTL'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
    @endif


</head>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#filter").click(function() {
            $("#show_filter").toggle();
        });
    });
</script>

<script>
    window.print();
    window.onafterprint = back;

    function back() {
        window.close();
        window.history.back();
    }
</script>

<body class="{{ $color }}">
    <div class="mt-4">
        @php
            $authUser = \Auth::user()->creatorId();
            $user = App\Models\User::find($authUser);
        @endphp

<div class="row">
    <div class="col-12" id="invoice-container">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="tab-content" id="myTabContent2">
                            @if($reportName  == '#vendor_balance')
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
                                @elseif($reportName == '#payable_summary')
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
                                                $pay_price = ($payPrice != null) ? $payPrice : 0;
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
                                                    <td> {{ \Auth::user()->expenseNumberFormat($bill) }}
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
                            @else
                            <table class="table table-flush" id="report-dataTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Customer Name') }}</th>
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
                                                }
                                                else {
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
                                                    <td> {{ \Auth::user()->expenseNumberFormat($bill) }}
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
                                                    <span
                                                        class="p-2 px-3">-</span>
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
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
    </div>
</body>

</html>
