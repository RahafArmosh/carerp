@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Tax Rate') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Taxes') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('create constant tax')
            <a href="#" data-url="{{ route('taxes.create') }}" data-ajax-popup="true" data-title="{{ __('Create Tax Rate') }}"
                data-bs-toggle="tooltip" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection
@push('script-page')
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
@endpush
@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.account_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('ID') }}</th>
                                    <th> {{ __('Tax Name') }}</th>
                                    <th> {{ __('Rate %') }}</th>
                                    <th> {{ __('Account') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($taxes as $taxe)
                                    <tr class="font-style">
                                        <td>{{ $taxe->id }}</td>
                                        <td>{{ $taxe->name }}</td>
                                        <td>{{ $taxe->rate }}</td>
                                        <td>{{ \App\Models\ChartOfAccount::where('id', $taxe->chart_account_id)->first()->name }}
                                        </td>
                                        <td class="Action">
                                            <span>
                                                @can('edit constant tax')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('taxes.edit', $taxe->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Tax Rate') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete constant tax')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST" action="{{ route('taxes.destroy', $taxe->id) }}"
                                                            id="delete-form-{{ $taxe->id }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $taxe->id }}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endcan
                                            </span>
                                        </td>
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
