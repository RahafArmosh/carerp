@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Pos') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Daily Pos Report') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
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
        (function() {
            var chartBarOptions = {
                series: [{
                    name: '{{ __('Pos') }}',
                    data: {!! json_encode($data) !!},
                    // data:   [300,80,400,200,100,300,100,290,156,250,350,200,80,230,120,300,180,300,400,280,100,150,280,100,160,100,300,150,100,90],


                }, ],

                chart: {
                    height: 300,
                    type: 'area',
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: 18,
                        left: 7,
                        blur: 10,
                        opacity: 0.2
                    },
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                title: {
                    text: '',
                    align: 'left'
                },
                xaxis: {
                    categories: {!! json_encode($arrDuration) !!},
                    title: {
                        text: '{{ __('Days') }}'
                    }
                },
                colors: ['#6fd944', '#6fd944'],

                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: false,
                },
                yaxis: {
                    title: {
                        text: '{{ __('Amount') }}'
                    },

                }

            };
            var arChart = new ApexCharts(document.querySelector("#daily-pos"), chartBarOptions);
            arChart.render();
        })();
    </script>
@endpush
@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()"data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>
    </div>
@endsection

@section('content')
    <ul class="nav nav-pills my-3" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" href="#daily-chart" role="tab"
                aria-controls="pills-home" aria-selected="true">{{ __('Daily') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" href="{{ route('report.monthly.pos') }}"
                onclick="window.location.href = '{{ route('report.monthly.pos') }}'" role="tab"
                aria-controls="pills-profile" aria-selected="false">{{ __('Monthly') }}</a>
        </li>
    </ul>

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 ">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('report.daily.pos') }}" method="GET" id="daily_pos_report_submit">
                            <div class="row d-flex align-items-center justify-content-end">
                                <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12">
                                    <div class="btn-box">
                                        <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                        <input type="date" id="start_date" name="start_date"
                                            value="{{ isset($_GET['start_date']) ? $_GET['start_date'] : '' }}"
                                            class="form-control month-btn" />
                                    </div>
                                </div>
                                <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                        <input type="date" id="end_date" name="end_date"
                                            value="{{ isset($_GET['end_date']) ? $_GET['end_date'] : '' }}"
                                            class="form-control month-btn" />
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        <label for="warehouse" class="form-label">{{ __('Warehouse') }}</label>
                                        <select name="warehouse" id="warehouse" class="form-control select">
                                            @foreach ($warehouse as $warehouseOption)
                                                <option value="{{ $warehouseOption }}"
                                                    {{ isset($_GET['warehouse']) && $_GET['warehouse'] == $warehouseOption ? 'selected' : '' }}>
                                                    {{ $warehouseOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                        <select name="customer" id="customer" class="form-control select">
                                            @foreach ($customer as $customerOption)
                                                <option value="{{ $customerOption }}"
                                                    {{ isset($_GET['customer']) && $_GET['customer'] == $customerOption ? 'selected' : '' }}>
                                                    {{ $customerOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-auto float-end ms-2 mt-4">
                                    <button type="submit" class="btn btn-sm btn-primary" data-toggle="tooltip"
                                        data-original-title="{{ __('Apply') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </button>
                                    <a href="{{ route('report.daily.pos') }}" class="btn btn-sm btn-danger"
                                        data-toggle="tooltip" data-original-title="{{ __('Reset') }}">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off"></i></span>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="printableArea">

        <div class="row mt-0">
            <div class="col">
                <input type="hidden"
                    value="{{ $filter['warehouse'] . ' ' . __('Daily Pos') . ' ' . 'Report of' . ' ' . $filter['startDate'] . ' to ' . $filter['endDate'] }}"
                    id="filename">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Report') }} :</h7>
                    <h6 class="report-text mb-0">{{ __('Daily Pos Report') }}</h6>
                </div>
            </div>
            @if (!empty($filter['warehouse']))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Warehouse') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['warehouse'] }}</h6>
                    </div>
                </div>
            @endif
            @if (!empty($filter['customer']))
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h7 class="report-text gray-text mb-0">{{ __('Customer') }} :</h7>
                        <h6 class="report-text mb-0">{{ $filter['customer'] }}</h6>
                    </div>
                </div>
            @endif
            <div class="col">
                <div class="card p-4 mb-4">
                    <h7 class="report-text gray-text mb-0">{{ __('Duration') }} :</h7>
                    <h6 class="report-text mb-0">{{ $filter['startDate'] . ' to ' . $filter['endDate'] }}</h6>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="setting-tab">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="daily-chart" role="tabpanel">
                                <div class="col-lg-12">
                                    <div class="card-header">
                                        <div class="row ">
                                            <div class="col-6">
                                                <h6>{{ __('Daily Report') }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="daily-pos"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
