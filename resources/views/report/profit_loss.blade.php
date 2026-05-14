@extends('layouts.admin')
@section('page-title')
    {{ __('Profit & Loss') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Profit & Loss') }}</li>
@endsection


@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
    <script>
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
            
            // Update export form dates when filter dates change
            $('.startDate, .endDate').on('change', function() {
                $('#export_start_date').val($('#start_date').val());
                $('#export_end_date').val($('#end_date').val());
            });
            
            // Update export form dates on form submit to ensure latest values
            $('#profit_loss_export_form').on('submit', function() {
                $('#export_start_date').val($('#start_date').val() || '{{ $filter['startDateRange'] ?? '' }}');
                $('#export_end_date').val($('#end_date').val() || '{{ $filter['endDateRange'] ?? '' }}');
            });
        });
    </script>
@endpush
@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
            title="{{ __('Print') }}" data-original-title="{{ __('Print') }}"><i class="ti ti-printer"></i></a>
    </div>

    <div class="float-end me-2">
        <form method="POST" action="{{ route('profit.loss.export') }}" id="profit_loss_export_form">
            @csrf
            <input type="hidden" name="start_date" id="export_start_date" value="{{ $filter['startDateRange'] ?? '' }}">
            <input type="hidden" name="end_date" id="export_end_date" value="{{ $filter['endDateRange'] ?? '' }}">
            <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                data-original-title="{{ __('Export') }}"><i class="ti ti-file-export"></i></button>
        </form>
    </div>


    <div class="float-end me-2" id="filter">
        <button id="filter" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
    </div>

    <div class="float-end me-2">
        <a href="{{ route('report.profit.loss', 'horizontal') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Horizontal View') }}" data-original-title="{{ __('Horizontal View') }}"><i
                class="ti ti-separator-vertical"></i></a>
    </div>
@endsection



@section('content')
    <div class="row justify-content-center">
        <div class="col-sm-8">
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card" id="show_filter" style="display:none;">
                    <div class="card-body">
                        <form method="GET" action="{{ route('report.profit.loss') }}" id="report_trial_balance">
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
                                                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                                <input id="start_date" type="date" name="start_date"
                                                    value="{{ $filter['startDateRange'] }}" class="startDate form-control">
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                                <input id="end_date" type="date" name="end_date"
                                                    value="{{ $filter['endDateRange'] }}" class="endDate form-control">
                                            </div>
                                        </div>


                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div class="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('report_trial_balance').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>

                                            <a href="{{ route('report.profit.loss') }}" class="btn btn-sm btn-danger "
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $authUser = \Auth::user()->creatorId();
        $user = App\Models\User::find($authUser);
    @endphp

    <div class="row justify-content-center" id="printableArea">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="account-main-title mb-5">
                        <h5>{{ 'Profit & Loss of ' . $user->name . ' as of ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}
                            </h4>
                    </div>
                    <div
                        class="aacount-title d-flex align-items-center justify-content-between border-top border-bottom py-2">
                        <h6 class="mb-0">{{ __('Account') }}</h6>
                        <h6 class="mb-0 text-center">{{ _('Account Code') }}</h6>
                        <h6 class="mb-0 text-end">{{ __('Total') }}</h6>

                    </div>
                    @php
                        $totalIncome = 0;
                        $netProfit = 0;
                        $totalCosts = 0;
                        $grossProfit = 0;
                    @endphp

                    @foreach ($totalAccounts as $accounts)
                        @if ($accounts['Type'] == 'Income')
                            <div class="account-main-inner border-bottom py-2">
                                <p class="fw-bold mb-2">{{ $accounts['Type'] }}</p>

                                @foreach ($accounts['account'] as $key => $record)
                                    @php
                                        $signedAmount = ((float) $record['netAmount']);
                                    @endphp
                                    <div class="account-inner d-flex align-items-center justify-content-between">
                                        @if (!preg_match('/\btotal\b/i', $record['account_name']))
                                            <p class="mb-2 ps-3"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ $record['account_name'] }}</a>
                                            </p>
                                        @else
                                            <p class="fw-bold mb-2"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-dark">{{ $record['account_name'] }}</a>
                                        @endif
                                        <p class="mb-2 text-center">{{ $record['account_code'] }}</p>
                                        <p class="text-primary mb-2 float-end text-end">
                                            {{ \Auth::user()->priceFormat($signedAmount) }}</p>
                                    </div>

                                    @php
                                        if ($record['account_name'] === 'Total Income') {
                                            $totalIncome = $signedAmount;
                                        }

                                        if ($record['account_name'] == 'Total Costs of Goods Sold') {
                                            $totalCosts = $signedAmount;
                                        }
                                        $grossProfit = $totalIncome + $totalCosts;
                                    @endphp
                                @endforeach
                            </div>
                        @endif
                        @if ($accounts['Type'] == 'Costs of Goods Sold')
                            <div class="account-main-inner border-bottom py-2">
                                <p class="fw-bold mb-2">{{ $accounts['Type'] }}</p>

                                @foreach ($accounts['account'] as $key => $record)
                                    @php
                                        $netAmount = ((float) $record['netAmount']);
                                    @endphp
                                    <div class="account-inner d-flex align-items-center justify-content-between">
                                        @if (!preg_match('/\btotal\b/i', $record['account_name']))
                                            <p class="mb-2 ps-3"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ $record['account_name'] }}</a>
                                            </p>
                                        @else
                                            <p class="fw-bold mb-2"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-dark">{{ $record['account_name'] }}</a>
                                        @endif
                                        <p class="mb-2 text-center">{{ $record['account_code'] }}</p>
                                        <p class="text-primary mb-2 float-end text-end">
                                            {{ \Auth::user()->priceFormat($netAmount) }}</p>
                                    </div>

                                    @php
                                        if ($record['account_name'] === 'Total Income') {
                                            $totalIncome = $netAmount;
                                        }

                                        if ($record['account_name'] == 'Total Costs of Goods Sold') {
                                            $totalCosts = $netAmount;
                                        }
                                        $grossProfit = $totalIncome + $totalCosts;
                                    @endphp
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    @php
                        $grossLabel = $grossProfit >= 0 ? __('Gross Profit') : __('Gross Loss');
                        $grossClass = $grossProfit >= 0 ? 'text-primary' : 'text-danger';
                    @endphp
                    <div class="account-inner d-flex align-items-center justify-content-between border-bottom">
                        <p></p>
                        <p class="fw-bold mb-2 text-center">{{ $grossLabel }}</p>
                        <p class="{{ $grossClass }} mb-2 float-end text-end">
                            {{ \Auth::user()->priceFormat($grossProfit) }}</p>
                    </div>
                    @foreach ($totalAccounts as $accounts)
                        @if ($accounts['Type'] == 'Expenses')
                            <div class="account-main-inner border-bottom py-2">
                                <p class="fw-bold mb-2">{{ $accounts['Type'] }}</p>

                                @foreach ($accounts['account'] as $key => $record)
                                    @php
                                        $netAmount = ((float) $record['netAmount']);
                                    @endphp
                                    <div class="account-inner d-flex align-items-center justify-content-between">
                                        @if (!preg_match('/\btotal\b/i', $record['account_name']))
                                            <p class="mb-2 ps-3"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-primary">{{ $record['account_name'] }}</a>
                                            </p>
                                        @else
                                            <p class="fw-bold mb-2"><a
                                                    href="{{ route('report.ledger', $record['account_id']) }}?start_date={{ $filter['startDateRange'] }}&end_date={{ $filter['endDateRange'] }}&account={{ $record['account_id'] }}"
                                                    class="text-dark">{{ $record['account_name'] }}</a>
                                        @endif
                                        <p class="mb-2 text-center">{{ $record['account_code'] }}</p>
                                        <p class="text-primary mb-2 float-end text-end">
                                            {{ \Auth::user()->priceFormat($netAmount) }}</p>
                                    </div>

                                    @php
                                        if ($record['account_name'] === 'Total Expenses') {
                                            $netProfit = $grossProfit + $netAmount;
                                        }
                                    @endphp
                                @endforeach
                            </div>

                            <div class="account-inner d-flex align-items-center justify-content-between border-bottom">
                                <p></p>
                                <p class="fw-bold mb-2 text-center">{{ __('Net Profit/Loss') }}</p>
                                <p class="text-primary mb-2 float-end text-end">
                                    {{ \Auth::user()->priceFormat($netProfit) }}</p>
                            </div>
                        @endif
                    @endforeach

                </div>
            </div>
        </div>
    </div>
@endsection
