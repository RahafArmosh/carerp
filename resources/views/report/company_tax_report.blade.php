@extends('layouts.admin')
@section('page-title')
    {{ __('Company Tax Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Company Tax Report') }}</li>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
            title="{{ __('Print') }}" data-original-title="{{ __('Print') }}"><i class="ti ti-printer"></i></a>
    </div>

    <div class="float-end me-2" id="filter">
        <button id="filter" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
    </div>
@endsection

@section('content')
    <div class="mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="mt-2" id="multiCollapseExample1">
                    <div class="card" id="show_filter" style="display:none;">
                        <div class="card-body">
                            <form method="GET" action="{{ route('report.company.tax') }}" id="report_tax_summary">
                                <div class="row align-items-center justify-content-end">
                                    <div class="col-xl-10">
                                        <div class="row">
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                                    <input type="date" name="start_date" id="start_date"
                                                        value="{{ $filter['startDateRange'] }}"
                                                        class="startDate form-control">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                                    <input type="date" name="end_date" id="end_date"
                                                        value="{{ $filter['endDateRange'] }}"
                                                        class="endDate form-control">
                                                </div>
                                            </div>
                                            <div class="col-auto float-end ms-4 mt-4">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('report_tax_summary').submit(); return false;"
                                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                    data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>
                                                <a href="{{ route('report.company.tax') }}"
                                                    class="btn btn-sm btn-danger " data-bs-toggle="tooltip"
                                                    title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-trash text-white-off "></i></span>
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
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive" id="printableArea">
                        <h4 class="text-center mb-4">{{ __('Company Tax Report') }}</h4>
                        <p class="text-center mb-4">
                            {{ __('Period') }}: {{ \Carbon\Carbon::parse($filter['startDateRange'])->format('d M Y') }} 
                            {{ __('to') }} 
                            {{ \Carbon\Carbon::parse($filter['endDateRange'])->format('d M Y') }}
                        </p>
                        {{-- OUTPUT SECTION (Credit side) --}}
                        <h5 class="mt-3 mb-2">{{ __('Output (Credit Side)') }}</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Tax Name') }}</th>
                                    <th>{{ __('Tax Chart Account') }}</th>
                                    <th class="text-end">{{ __('Total Without Tax') }}</th>
                                    <th class="text-end">{{ __('Total Tax') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $grandInputWithoutTax = 0;
                                    $grandInputTax = 0;
                                @endphp
                                @forelse($inputData ?? [] as $taxData)
                                    @if(($taxData['total_tax'] ?? 0) != 0 || ($taxData['total_without_tax'] ?? 0) != 0)
                                        <tr>
                                            <td>{{ $taxData['tax_name'] }}</td>
                                            <td>
                                                @if(!empty($taxData['chart_account_code']) && $taxData['chart_account_code'] != '-')
                                                    <a href="{{ route('report.ledger', ['account' => $taxData['chart_account_id'], 'start_date' => $filter['startDateRange'], 'end_date' => $filter['endDateRange']]) }}" target="_blank" title="{{ __('View Ledger Summary') }}">
                                                        {{ $taxData['chart_account_code'] }} - {{ $taxData['chart_account_name'] }}
                                                    </a>
                                                @else
                                                    {{ __('N/A') }}
                                                @endif
                                            </td>
                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxData['total_without_tax'] ?? 0) }}</td>
                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxData['total_tax'] ?? 0) }}</td>
                                        </tr>
                                        @php
                                            $grandInputWithoutTax += ($taxData['total_without_tax'] ?? 0);
                                            $grandInputTax += ($taxData['total_tax'] ?? 0);
                                        @endphp
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">{{ __('No data found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('Grand Total') }}</th>
                                    <th></th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($grandInputWithoutTax) }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($grandInputTax) }}</th>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- INPUT SECTION (Debit side) --}}
                        <h5 class="mt-4 mb-2">{{ __('Input (Debit Side)') }}</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Tax Name') }}</th>
                                    <th>{{ __('Tax Chart Account') }}</th>
                                    <th class="text-end">{{ __('Total Without Tax') }}</th>
                                    <th class="text-end">{{ __('Total Tax') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $grandOutputWithoutTax = 0;
                                    $grandOutputTax = 0;
                                @endphp
                                @forelse($outputData ?? [] as $taxData)
                                    @if(($taxData['total_tax'] ?? 0) != 0 || ($taxData['total_without_tax'] ?? 0) != 0)
                                        <tr>
                                            <td>{{ $taxData['tax_name'] }}</td>
                                            <td>
                                                @if(!empty($taxData['chart_account_code']) && $taxData['chart_account_code'] != '-')
                                                    <a href="{{ route('report.ledger', ['account' => $taxData['chart_account_id'], 'start_date' => $filter['startDateRange'], 'end_date' => $filter['endDateRange']]) }}" target="_blank" title="{{ __('View Ledger Summary') }}">
                                                        {{ $taxData['chart_account_code'] }} - {{ $taxData['chart_account_name'] }}
                                                    </a>
                                                @else
                                                    {{ __('N/A') }}
                                                @endif
                                            </td>
                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxData['total_without_tax'] ?? 0) }}</td>
                                            <td class="text-end">{{ \Auth::user()->priceFormat($taxData['total_tax'] ?? 0) }}</td>
                                        </tr>
                                        @php
                                            $grandOutputWithoutTax += ($taxData['total_without_tax'] ?? 0);
                                            $grandOutputTax += ($taxData['total_tax'] ?? 0);
                                        @endphp
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">{{ __('No data found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('Grand Total') }}</th>
                                    <th></th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($grandOutputWithoutTax) }}</th>
                                    <th class="text-end">{{ \Auth::user()->priceFormat($grandOutputTax) }}</th>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- NET FOOTER: Input - Output --}}
                        @php
                            $netWithoutTax = ($grandInputWithoutTax ?? 0) - ($grandOutputWithoutTax ?? 0);
                            $netTax        = ($grandInputTax ?? 0) - ($grandOutputTax ?? 0);
                        @endphp
                        <table class="table mt-4">
                            <thead>
                                <tr>
                                    <th>{{ __('Net (Input - Output)') }}</th>
                                    <th></th>
                                    <th class="text-end">{{ __('Net Without Tax') }}</th>
                                    <th class="text-end">{{ __('Net Tax') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ __('All Taxes') }}</td>
                                    <td></td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($netWithoutTax) }}</td>
                                    <td class="text-end">{{ \Auth::user()->priceFormat($netTax) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
