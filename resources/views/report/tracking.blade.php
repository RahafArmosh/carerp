@extends('layouts.admin')
@section('page-title')
    {{ __('Tracking') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Tracking') }}</li>
@endsection
@section('content')
    <form method="GET" action="{{ route('tracking.index') }}" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="employee">{{ __('Employee') }}</label>
                <select id="employee" name="employee" class="form-control">
                    <option value="">{{ __('Select Employee') }}</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->user_id }}" {{ request('employee') == $employee->user_id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="start_date">{{ __('Start Date') }}</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-4">
                <label for="end_date">{{ __('End Date') }}</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('tracking.index') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
            </div>
        </div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>User</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Location Name</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trackingRecords as $record)
                <tr>

                    <td>{{ $record->user->name }}</td>
                    <td>{{ $record->latitude }}</td>
                    <td>{{ $record->longitude }}</td>
                    <td>{{ $record->location_name }}</td>
                    <td>{{ $record->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $record->latitude }},{{ $record->longitude }}"
                           target="_blank" class="btn btn-info btn-sm">{{ __('View on Map') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No tracking records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $trackingRecords->appends(request()->query())->links() }}
@endsection
