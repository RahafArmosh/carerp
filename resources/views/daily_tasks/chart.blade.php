@extends('layouts.admin')

@section('page-title')
    {{ __('Task chart') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('daily-tasks.index') }}">{{ __('Daily task log') }}</a></li>
    <li class="breadcrumb-item">{{ __('Chart') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('daily-tasks.index', array_filter(['employee_id' => request('employee_id'), 'department_id' => request('department_id')])) }}"
            class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="{{ __('Back') }}">
            <i class="ti ti-arrow-left"></i>
        </a>
        <a href="{{ route('daily-tasks.report', array_filter(['from_date' => request('from_date'), 'to_date' => request('to_date'), 'employee_id' => request('employee_id'), 'department_id' => request('department_id')])) }}"
            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Task report') }}">
            <i class="ti ti-report-analytics"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <form method="get" action="{{ route('daily-tasks.chart') }}" class="row g-3 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('From date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('To date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date', $toDate) }}">
                        </div>

                        @if ($isCompanyAdmin)
                            <div class="col-md-3">
                                <label class="form-label">{{ __('User') }}</label>
                                <select name="employee_id" class="form-control">
                                    <option value="">{{ __('All') }}</option>
                                    @foreach ($employeesForFilter as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) request('employee_id') === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Department') }}</label>
                                <select name="department_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">{{ __('All') }}</option>
                                    @foreach ($departmentsForFilter as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) request('department_id') === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if (request('department_id'))
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('Task') }}</label>
                                    <select name="task_master_id" class="form-control">
                                        <option value="">{{ __('All') }}</option>
                                        @foreach ($tasksForFilter as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ (string) request('task_master_id') === (string) $id ? 'selected' : '' }}>
                                                {{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif

                        <div class="col-12">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="ti ti-filter"></i> {{ __('Apply') }}
                            </button>
                        </div>
                    </form>

                    <div class="mb-2 text-muted">
                        {{ __('X: Task, Y: Total time (hours). Bars are grouped by employee.') }}
                    </div>

                    <div style="position: relative; height: 520px;">
                        <canvas id="dailyTasksChart"></canvas>
                    </div>

                    @if (empty($employees))
                        <div class="alert alert-info mt-3 mb-0">
                            {{ __('No data for selected filters.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const labels = @json($taskLabels);
            const datasetsRaw = @json($datasets);

            function colorForIndex(i) {
                const hues = [210, 150, 30, 270, 0, 110, 330, 60, 190, 20, 250, 90];
                const h = hues[i % hues.length];
                return `hsl(${h}, 70%, 55%)`;
            }

            const datasets = datasetsRaw.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                backgroundColor: colorForIndex(i),
                borderWidth: 0,
            }));

            const ctx = document.getElementById('dailyTasksChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: '{{ __('Hours') }}' }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const v = context.raw || 0;
                                    return `${context.dataset.label}: ${Number(v).toFixed(2)}h`;
                                }
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        })();
    </script>
@endpush

