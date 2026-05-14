@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Holiday') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Holiday') }}</li>
@endsection

@section('action-btn')
    @can('create holiday')
        <div class="float-end">
            <a href="{{ route('holiday.calender') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Calender View') }}" data-original-title="{{ __('Calender View') }}">
                <i class="ti ti-calendar"></i>
            </a>
            <a href="#" data-size="lg" data-url="{{ route('holiday.create') }}" data-ajax-popup="true"
                data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create New Holiday') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endcan
@endsection


@section('content')
    @can('create holiday')
        <div class="row">
            <div class="col-sm-12">
                <div class=" mt-2 " id="multiCollapseExample1">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('holiday.calender') }}" method="get" id="holiday_filter">
                                @csrf
                                <div class="row align-items-center justify-content-end">
                                    <div class="col-xl-10">
                                        <div class="row">
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="start_date" class="form-label">Start Date</label>
                                                    <input type="date" name="start_date" id="start_date"
                                                        class="month-btn form-control"
                                                        value="{{ isset($_GET['start_date']) ? $_GET['start_date'] : '' }}">
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="end_date" class="form-label">End Date</label>
                                                    <input type="date" name="end_date" id="end_date"
                                                        class="month-btn form-control"
                                                        value="{{ isset($_GET['end_date']) ? $_GET['end_date'] : '' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="row">
                                            <div class="col-auto mt-4">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('holiday_filter').submit(); return false;"
                                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                    data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>
                                                <a href="{{ route('holiday.calender') }}" class="btn btn-sm btn-danger "
                                                    data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                    data-original-title="{{ __('Reset') }}">
                                                    <span class="btn-inner--icon"><i
                                                            class="ti ti-trash-off text-white-off "></i></span>
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
        @endcan

        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Occasion') }}</th>
                                        <th>{{ __('Start Date') }}</th>
                                        <th>{{ __('End Date') }}</th>
                                        @if (Gate::check('edit holiday') || Gate::check('delete holiday'))
                                            <th>{{ __('Action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="font-style">
                                    @foreach ($holidays as $holiday)
                                        <tr>
                                            <td>{{ $holiday->occasion }}</td>
                                            <td>{{ \Auth::user()->dateFormat($holiday->date) }}</td>
                                            <td>{{ \Auth::user()->dateFormat($holiday->end_date) }}</td>
                                            @if (Gate::check('edit holiday') || Gate::check('delete holiday'))
                                                <td class="Action">
                                                    <span>
                                                        @can('edit holiday')
                                                            <div class="action-btn bg-primary ms-2">
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                    data-url="{{ route('holiday.edit', $holiday->id) }}"
                                                                    data-ajax-popup="true"
                                                                    data-title="{{ __('Edit Holiday') }}"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                        @can('delete holiday')
                                                            <div class="action-btn bg-danger ms-2">
                                                                <form method="POST"
                                                                    action="{{ route('holiday.destroy', $holiday->id) }}"
                                                                    id="delete-form-{{ $holiday->id }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        data-original-title="{{ __('Delete') }}"
                                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                        data-confirm-yes="document.getElementById('delete-form-{{ $holiday->id }}').submit();">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                </form>
                                                            </div>
                                                        @endcan
                                                    </span>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
