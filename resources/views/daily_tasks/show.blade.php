@extends('layouts.admin')

@section('page-title')
    {{ __('Daily task log') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('daily-tasks.index') }}">{{ __('Task') }}</a></li>
    <li class="breadcrumb-item">{{ __('View') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @if ($canEdit)
            <a href="{{ route('daily-tasks.edit', $log) }}" class="btn btn-sm btn-primary">{{ __('Edit') }}</a>
        @endif
        <a href="{{ route('daily-tasks.index') }}" class="btn btn-sm btn-light">{{ __('Back') }}</a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>{{ __('Date') }}</strong><br>
                            {{ $log->log_date ? $log->log_date->format('Y-m-d') : '' }}
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('Employee') }}</strong><br>
                            {{ $log->employee->name ?? '—' }}
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('Department') }}</strong><br>
                            {{ $log->department->name ?? '—' }}
                        </div>
                    </div>
                    @if ($log->day_notes)
                        <p><strong>{{ __('Day notes') }}:</strong> {{ $log->day_notes }}</p>
                    @endif

                    <h6 class="mt-4">{{ __('Tasks') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('List of tasks') }}</th>
                                    <th>{{ __('Hours') }}</th>
                                    <th>{{ __('Min') }}</th>
                                    <th>{{ __('Note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($log->tasks as $t)
                                    <tr>
                                        <td>{{ $t->task_name ?: optional($t->taskMaster)->name }}</td>
                                        <td>{{ $t->hours }}</td>
                                        <td>{{ $t->minutes }}</td>
                                        <td>{{ $t->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @php
                        $totalMin = $log->tasks->sum('duration_minutes');
                        $th = intdiv($totalMin, 60);
                        $tm = $totalMin % 60;
                    @endphp
                    <p class="mt-2"><strong>{{ __('Total time') }}:</strong> {{ $th }}h
                        {{ str_pad((string) $tm, 2, '0', STR_PAD_LEFT) }}m</p>
                </div>
            </div>
        </div>
    </div>
@endsection
