@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Stock Movements') }}
@endsection
@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
    </li>
    <li class="breadcrumb-item">{{ __('Stock Movements') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a> --}}
        {{-- <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a> --}}

        {{-- <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a> --}}

    </div>
@endsection

@section('content')
    {{-- <h3>Car Rent Report ({{ $from->format('M Y') }} - {{ $to->format('M Y') }})</h3> --}}
        <div class="card mt-4">
            <form method="GET" action="{{ route('reports.rent.monthly') }}" class="row mb-4">
                <div class="col-md-3">
                    <label for="from">From Date</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ request('from') }}">
                </div>
            
                <div class="col-md-3">
                    <label for="to">To Date</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ request('to') }}">
                </div>
            
                <div class="col-md-4">
                    <label for="car">Car</label>
                    <select name="car_id" id="car" class="form-control select2">
                        <option value="">-- All Cars --</option>
                        @foreach ($allCars as $car)
                            <option value="{{ $car->id }}" {{ request('car_id') == $car->id ? 'selected' : '' }}>
                                {{ $car->product_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
            
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    @foreach ($report as $carReport)
        <div class="card mt-4">

            <div class="card-body">
                @foreach ($report as $car)
                    </br>
                    <h4>Car: {{ $car['product_no'] ?? 'N/A' }} ({{ $car['product_name'] ?? 'Unknown Product' }})</h4>

                    <p><strong>Rental Period:</strong> {{ $car['from'] }} to {{ $car['to'] }}</p>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Rented Days</th>
                                <th>Free Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalRented = 0;
                                $totalFree = 0;
                            @endphp
                            @foreach ($car['monthly'] as $month => $data)
                                @php
                                    $totalRented += $data['rented_days'];
                                    $totalFree += $data['free_days'];
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</td>
                                    <td>{{ $data['rented_days'] }}</td>
                                    <td>{{ $data['free_days'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-secondary font-weight-bold">
                                <td><strong>Total</strong></td>
                                <td><strong>{{ $totalRented }}</strong></td>
                                <td><strong>{{ $totalFree }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach

            </div>
        </div>
    @endforeach
@endsection
