@extends('layouts.admin')

@section('page-title')
    {{ __('Task report') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('daily-tasks.index') }}">{{ __('Daily task log') }}</a></li>
    <li class="breadcrumb-item">{{ __('Task report') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <form method="get" action="{{ route('daily-tasks.report') }}" class="row mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('From date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('To date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        @if ($isCompanyAdmin && ($employeesForFilter->isNotEmpty() || $departmentsForFilter->isNotEmpty()))
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
                                <select name="department_id" class="form-control">
                                    <option value="">{{ __('All') }}</option>
                                    @foreach ($departmentsForFilter as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) request('department_id') === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mt-2">
                            <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                            <a href="{{ route('daily-tasks.report') }}" class="btn btn-light">{{ __('Reset') }}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Task type') }}</th>
                                    <th class="text-end" style="width: 100px">{{ __('Hours') }}</th>
                                    <th class="text-end" style="width: 100px">{{ __('Min') }}</th>
                                    <th>{{ __('Salesman') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $taskLabel = trim((string) ($row->task_name ?? '')) !== ''
                                            ? trim($row->task_name)
                                            : (trim((string) ($row->master_task_name ?? '')) !== ''
                                                ? $row->master_task_name
                                                : '—');
                                        $h = (int) $row->hours;
                                        $m = (int) $row->minutes;
                                    @endphp
                                    <tr>
                                        <td>{{ $taskLabel }}</td>
                                        <td class="text-end">{{ $h > 0 ? $h : '' }}</td>
                                        <td class="text-end">{{ $m }}</td>
                                        <td>{{ $row->employee_name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('No records found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">{{ $rows->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
