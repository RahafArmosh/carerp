@extends('layouts.admin')
@section('page-title')
    {{ __('Employee Statement Summary') }}
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
            updateHiddenInput('employee_name', 'employee_id', 'employees');
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
    <li class="breadcrumb-item">{{ __('Employee Statement Summary') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">
        {{-- <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button"
        aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}">
        --}}
        {{-- <i class="ti ti-filter"></i> --}}
        {{-- </a> --}}

        <a href="{{ route('accountstatement.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
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
                        <form action="{{ route('report.employee.statement') }}" method="GET" id="report_account">
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
                                                <select class="form-control select2" id="account" name="account"
                                                    style="width: 100%;" data-placeholder="Select Account">
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

                                        <!-- Employee Selection -->
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="employeeس" class="form-label">{{ __('Employee') }}</label>
                                                <select class="form-control select2" id="employee" name="employee"
                                                    style="width: 100%;" data-placeholder="Select Employee">
                                                    <option value="">{{ __('Select Employee') }}</option>
                                                    @foreach ($employee as $employeeId => $employeeLabel)
                                                        <option value="{{ $employeeId }}"
                                                            {{ isset($_GET['employee']) && $_GET['employee'] == $employeeId ? 'selected' : '' }}>
                                                            {{ $employeeLabel }}
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
                                            <a href="{{ route('report.employee.statement') }}"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
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
                        value="{{ __('Employee Statement') . ' ' . $filter['employee'] . ' ' . 'Report of' . ' ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}"
                        id="filename">
                    <div class="card mb-4 p-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                        <h6 class="report-text mb-0">{{ __('Employee Statement Summary') }}</h6>
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
                @if ($filter['employee'] != __('All'))
                    <div class="col">
                        <div class="card mb-4 p-4">
                            <h7 class="report-text gray-text mb-0">{{ __('Employee') }} :</h7>
                            <h6 class="report-text mb-0">
                                {{ \App\Models\Employee::where('id', $filter['employee'])->first() != null
                                    ? \App\Models\Employee::where('id', $filter['employee'])->first()->name
                                    : '' }}
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
                                            $totalDebit = 0;
                                            $totalCredit = 0;
                                            $totalBalance = 0;
                                        @endphp
                                        @foreach ($reportData['general_ledger'] as $general_ledger)
                                            @php
                                                $totalDebit += $general_ledger->total_debit;
                                                $totalCredit += $general_ledger->total_credit;
                                            @endphp
                                            <tr class="font-style">
                                                <td>{{ Auth::user()->dateFormat($general_ledger->created_at) }}</td>
                                                <td>{{ Auth::user()->dateFormat($general_ledger->updated_at) }}</td>
                                                <td>{{ $general_ledger->vid }}</td>
                                                @if ($general_ledger->debit > 0)
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_debit) }}</td>
                                                @else
                                                    <td>{{ Auth::user()->priceFormat($general_ledger->total_credit) }}</td>
                                                @endif
                                                <td>{{ $general_ledger->type }}</td>
                                                <td>{{ \App\Models\Employee::where('id', $general_ledger->user_id)->first()->name }}
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
