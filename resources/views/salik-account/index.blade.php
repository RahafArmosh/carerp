@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Salik Accounts') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Salik Accounts') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create constant salik-account')
            <a href="#" data-url="{{ route('salik-account.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip"
                title="{{ __('Create') }}" title="{{ __('Create') }}" data-title="{{ __('Create New Salik Account') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

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
                                    <th> {{ __('Name') }}</th>
                                    <th>{{ __('Bank') }}</th>
                                    <th> {{ __('Current Balance') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salikAccounts as $account)
                                    <tr>
                                        <td class="font-style">{{ $account->name }}</td>

                                        <td>{{ !empty($account->chartAccount) ? $account->chartAccount->name : '-' }}</td>

                                        <td class="font-style">
                                            {{ \Auth::user()->priceFormat($account->balance) }}
                                        </td>
                                        <td class="Action">
                                            <span>
                                                @can('edit constant salik-account')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('salik-account.edit', $account->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Salik Account') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete constant salik-account')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST"
                                                            action="{{ route('salik-account.destroy', $account->id) }}"
                                                            id="delete-form-{{ $account->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                onclick="event.preventDefault(); document.getElementById('delete-form-{{ $account->id }}').submit();">
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
