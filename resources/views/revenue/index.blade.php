@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Revenues') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Revenue') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        {{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
        {{--            <i class="ti ti-filter"></i> --}}
        {{--        </a> --}}

        @can('create revenue')
            <a href="#" data-url="{{ route('revenue.create') }}" data-size="lg" data-ajax-popup="true"
                data-title="{{ __('Create New Revenue') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('revenue.index') }}" method="GET" id="revenue_form">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">

                                        <div class="col-3">
                                            <label for="date" class="form-label">{{ __('Date') }}</label>
                                            <input type="text" name="date" id="date"
                                                class="form-control month-btn"
                                                value="{{ isset($_GET['date']) ? $_GET['date'] : null }}" readonly>
                                        </div>


                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="account" class="form-label">{{ __('Account') }}</label>
                                                <select name="account" id="account" class="form-control select2">
                                                    @foreach ($account as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['account']) && $_GET['account'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 date">
                                            <div class="btn-box">
                                                <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                                <select name="customer" id="customer" class="form-control select2">
                                                    @foreach ($customer as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['customer']) && $_GET['customer'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="category" class="form-label">{{ __('Category') }}</label>
                                                <select name="category" id="category" class="form-control select">
                                                    @foreach ($category as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['category']) && $_GET['category'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div class="col-auto">

                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('revenue_form').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>

                                            <a href="{{ route('revenue.index') }}" class="btn btn-sm btn-danger "
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
                                            </a>


                                        </div>

                                    </div>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style mt-2">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Date') }}</th>
                                    <th> {{ __('Amount') }}</th>
                                    <th> {{ __('Account') }}</th>
                                    <th> {{ __('Customer') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Reference') }}</th>
                                    <th> {{ __('Description') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    <th>{{ __('Project') }}</th>

                                    @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $revenuepath = \App\Models\Utility::get_file('uploads/revenue');
                                @endphp
                                @foreach ($revenues as $revenue)
                                    <tr class="font-style">
                                        <td>{{ Auth::user()->dateFormat($revenue->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($revenue->amount) }}</td>
                                        <td>{{ !empty($revenue->bankAccount) ? $revenue->bankAccount->bank_name . ' ' . $revenue->bankAccount->holder_name : '' }}
                                        </td>
                                        <td>{{ !empty($revenue->customer) ? $revenue->customer->name : '-' }}</td>
                                        <td>{{ !empty($revenue->category) ? $revenue->category->name : '-' }}</td>
                                        <td>{{ !empty($revenue->reference) ? $revenue->reference : '-' }}</td>
                                        <td>{{ !empty($revenue->description) ? $revenue->description : '-' }}</td>


                                        <td>
                                            {{--                                        @if (!empty($revenue->add_receipt)) --}}
                                            {{--                                            <a href="{{asset(Storage::url('uploads/revenue')).'/'.$revenue->add_receipt}}" download="" class="action-btn bg-primary ms-2 mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank"><span class="btn-inner--icon"><i class="ti ti-download text-white" ></i></span></a> --}}

                                            {{--                                            <div class="action-btn bg-secondary"> --}}
                                            {{--                                                <a class="mx-3 btn btn-sm align-items-center" href="{{asset(Storage::url('uploads/revenue')).'/'.$revenue->add_receipt}}" target="_blank"  > --}}
                                            {{--                                                    <i class="ti ti-crosshair text-white" data-bs-toggle="tooltip" data-bs-original-title="{{ __('Preview') }}"></i> --}}
                                            {{--                                                </a> --}}
                                            {{--                                            </div> --}}
                                            {{--                                        @else --}}
                                            {{--                                            - --}}
                                            {{--                                        @endif --}}

                                            @if (!empty($revenue->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ $revenuepath . '/' . $revenue->add_receipt }}" download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ $revenuepath . '/' . $revenue->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>{{ $revenue->project_id != 0 ? $revenue->project->project_name : '-' }}</td>
                                        @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="Action">
                                                <span>
                                                    @can('edit revenue')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('revenue.edit', $revenue->id) }}"
                                                                data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                                title="{{ __('Edit') }}" title="{{ __('Edit') }}"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete revenue')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('revenue.destroy', $revenue->id) }}"
                                                                class="delete-form-btn" id="delete-form-{{ $revenue->id }}">
                                                                @csrf
                                                                @method('DELETE')

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $revenue->id }}').submit();" onclick="confirmDelete(event, {{ $revenue->id }})">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                    <input type="hidden" name="delete_date"
                                                                    id="delete-date-{{ $revenue->id }}" value="">
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
<script>
    function confirmDelete(event, billId) {
        event.preventDefault();

        // Show a confirmation dialog
        if (confirm('Are you sure you want to delete this bill?')) {
            var dateToDelete = prompt('Enter the date to delete (YYYY-MM-DD):');
            if (dateToDelete) {
                // Set the date value in the hidden input
                document.getElementById('delete-date-' + billId).value = dateToDelete;

                // Submit the form
                document.getElementById('delete-form-' + billId).submit();
            }
        }
    }
</script>
