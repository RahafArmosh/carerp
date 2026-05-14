@extends('layouts.admin')
@section('page-title')
    {{ __('Bank Balance Transfer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bank Balance Transfer') }}</li>
@endsection
@php
    $settings = Utility::settings();
@endphp
@section('action-btn')
    <div class="float-end">
        {{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
        {{--            <i class="ti ti-filter"></i> --}}
        {{--        </a> --}}
        @can('create bank transfer')
            <a href="#" data-url="{{ route('bank-transfer.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create Bank-Transfer') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('bank-transfer.index') }}" method="GET" id="transfer_form">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">

                                        <div class="col-3">
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="date" class="form-label">{{ __('Date') }}</label>
                                                <input type="date" id="date" name="date"
                                                    class="form-control month-btn"
                                                    value="{{ isset($_GET['date']) ? $_GET['date'] : '' }}">


                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 date">
                                            <div class="btn-box">
                                                <label for="f_account"
                                                    class="form-label">{{ __('Credit Account') }}</label>
                                                <select id="f_account" name="f_account" class="form-control select2"
                                                    data-placeholder="{{ __('Select Account') }}">
                                                    <option value="" selected disabled>{{ __('Select Account') }}
                                                    </option>
                                                    @foreach ($account as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['f_account']) && $_GET['f_account'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}</option>
                                                    @endforeach
                                                </select>

                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="t_account" class="form-label">{{ __('Debit Account') }}</label>
                                                <select id="t_account" name="t_account" class="form-control select2"
                                                    data-placeholder="{{ __('Select Account') }}">
                                                    <option value="" selected disabled>{{ __('Select Account') }}
                                                    </option>
                                                    @foreach ($account as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($_GET['t_account']) && $_GET['t_account'] == $key ? 'selected' : '' }}>
                                                            {{ $value }}</option>
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
                                                onclick="document.getElementById('transfer_form').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>

                                            <a href="{{ route('bank-transfer.index') }}" class="btn btn-sm btn-danger "
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
                <div class="card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Date') }}</th>
                                    <th> {{ __('Credit Account') }}</th>
                                    <th> {{ __('Debit Account') }}</th>
                                    <th> {{ __('Amount') }}</th>
                                    <th> {{ __('Currancy') }}</th>
                                    <th> {{ __('Reference') }}</th>
                                    <th> {{ __('Description') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($transfers as $transfer)
                                    <tr class="font-style">
                                        <td>{{ \Auth::user()->dateFormat($transfer->date) }}</td>
                                        <td>{{ !empty($transfer->fromBankAccount) ? $transfer->fromBankAccount->bank_name . ' ' . $transfer->fromBankAccount->holder_name : '' }}
                                        </td>
                                        <td>{{ !empty($transfer->toBankAccount) ? $transfer->toBankAccount->bank_name . ' ' . $transfer->toBankAccount->holder_name : '' }}
                                        </td>
                                        <td>{{ $transfer->amount }}</td>
                                        <td>{{ $transfer->currency != null ? $transfer->currency->name : $settings['site_currency_symbol'] }}
                                        </td>
                                        <td>{{ $transfer->reference }}</td>
                                        <td>{{ $transfer->description }}</td>
                                        <td class="Action">
                                            <span>
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('bank-transfer.print', $transfer->id) }}" 
                                                        target="_blank"
                                                        class="mx-3 btn btn-sm align-items-center" 
                                                        data-bs-toggle="tooltip" 
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Print') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a>
                                                </div>
                                                @can('edit transfer')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('bank-transfer.edit', $transfer->id) }}"
                                                            data-ajax-popup="true" title="{{ __('Edit') }}"
                                                            data-title="{{ __('Edit Transfer') }}" data-bs-toggle="tooltip"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete transfer')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form id="delete-form"
                                                            action="{{ route('bank-transfer.destroy', $transfer->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="delete_date" id="delete-date">
                                                            <input type="hidden" name="transfer_id"
                                                                id="delete-transfer-id">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                onclick="confirmDelete({{ $transfer->id }})"
                                                                title="{{ __('Delete') }}">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDelete(transferId) {
        Swal.fire({
            title: "Select Delete Date",
            html: `<input type="date" id="delete-date-input" class="swal2-input" required>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "Cancel",
            reverseButtons: true,
            preConfirm: () => {
                const date = document.getElementById('delete-date-input').value;
                if (!date) {
                    Swal.showValidationMessage('Please select a date.');
                    return false;
                }
                return date;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedDate = result.value;
                document.getElementById('delete-transfer-id').value = transferId;
                document.getElementById('delete-date').value = selectedDate;
                document.getElementById('delete-form').submit();
            }
        });
    }
</script>
