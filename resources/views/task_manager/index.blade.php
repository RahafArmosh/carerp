@extends('layouts.admin')

@section('page-title')
    {{ __('Task Manager') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Task Manager') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">{{ __('Employee task management') }}</h5>
                    <p class="text-muted mb-4">
                        {{ __('Manage predefined tasks, daily logs, and time reporting from here.') }}
                    </p>
                    @can('manage task master')
                        <a href="{{ route('task-master.index') }}" class="btn btn-primary">
                            <i class="ti ti-list-details me-1"></i> {{ __('Task Master') }}
                        </a>
                    @endcan
                    @if (Auth::user()->type == 'Employee' || Gate::check('manage employee') || Gate::check('manage daily task log'))
                        <a href="{{ route('daily-tasks.index') }}" class="btn btn-outline-primary ms-1">
                            <i class="ti ti-checkbox me-1"></i> {{ __('Daily task log') }}
                        </a>
                        <a href="{{ route('daily-tasks.report') }}" class="btn btn-outline-secondary ms-1">
                            <i class="ti ti-report-analytics me-1"></i> {{ __('Task report') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
