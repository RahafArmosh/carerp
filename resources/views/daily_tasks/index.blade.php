@extends('layouts.admin')

@section('page-title')
    {{ __('Task') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Daily task log') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('daily-tasks.report') }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
            title="{{ __('Task report') }}">
            <i class="ti ti-report-analytics"></i>
        </a>
        <a href="{{ route('daily-tasks.chart', array_filter(['employee_id' => request('employee_id'), 'department_id' => request('department_id')])) }}"
            class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="{{ __('Task chart') }}">
            <i class="ti ti-chart-bar"></i>
        </a>
        @if ($me || $isCompanyAdmin)
            <a href="{{ route('daily-tasks.create', array_filter(['employee_id' => request('employee_id'), 'log_date' => request('log_date')])) }}"
                class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    @if ($isCompanyAdmin && ($employeesForFilter->isNotEmpty() || $departmentsForFilter->isNotEmpty()))
                        <form method="get" action="{{ route('daily-tasks.index') }}" class="row mb-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('User') }}</label>
                                <select name="employee_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">{{ __('All') }}</option>
                                    @foreach ($employeesForFilter as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) request('employee_id') === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
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
                        </form>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    @if ($showEmployeeColumn)
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Department') }}</th>
                                    <th >{{ __('Tasks') }}</th>
                                    <th>{{ __('Total time') }}</th>
                                    <th width="180">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    @php
                                        $totalMin = $log->tasks->sum('duration_minutes');
                                        $th = intdiv($totalMin, 60);
                                        $tm = $totalMin % 60;
                                    @endphp
                                    <tr>
                                        <td>{{ $log->log_date ? $log->log_date->format('Y-m-d') : '' }}</td>
                                        @if ($showEmployeeColumn)
                                            <td>{{ $log->employee->name ?? '—' }}</td>
                                        @endif
                                        <td>{{ $log->department->name ?? '—' }}</td>
                                        <td >{{ $log->tasks->count() }}</td>
                                        <td>{{ $th }}h {{ str_pad((string) $tm, 2, '0', STR_PAD_LEFT) }}m</td>
                                        <td>
                                            <a href="{{ route('daily-tasks.show', $log) }}"
                                                class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                            @php
                                                $canEdit =
                                                    $isCompanyAdmin ||
                                                    ($me && (int) $log->employee_id === (int) $me->id);
                                            @endphp
                                            @if ($canEdit)
                                                <a href="{{ route('daily-tasks.edit', $log) }}"
                                                    class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                                                <form action="{{ route('daily-tasks.destroy', $log) }}" method="post"
                                                    class="d-inline"
                                                    onsubmit="return confirm('{{ __('Are You Sure?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showEmployeeColumn ? 6 : 5 }}"
                                            class="text-center text-muted">{{ __('No records found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
