@extends('layouts.admin')
@php
    // $profile=asset(Storage::url('uploads/avatar/'));
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
@endphp
@push('script-page')
    <script>
        $(document).on('click', '#billing_data', function() {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })
    </script>
@endpush
@section('page-title')
    {{ __('Manage Customers') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Customer') }}</li>
@endsection

@section('action-btn')
    <div class="float-end d-flex flex-wrap gap-1 justify-content-end">
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('customer.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import customer CSV file') }}" class="btn btn-sm btn-primary">
            {{ __('Import') }}
        </a>
        <a href="{{ route('customer.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary">
            {{ __('Export') }}
        </a>

        <a href="#" data-size="lg" data-url="{{ route('customer.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Customer') }}"
            class="btn btn-sm btn-primary">
            {{ __('Create') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Customer ID') }}</th>
                                    <th>#</th>
                                    <th> {{ __('Name') }}</th>
                                    <th> {{ __('Customer Code') }}</th>
                                    <th> {{ __('Contact') }}</th>
                                    <th> {{ __('Email') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th> {{ __('Balance') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $k => $customer)
                                    <tr class="cust_tr" id="cust_detail"
                                        data-url="{{ route('customer.show', $customer['id']) }}"
                                        data-id="{{ $customer['id'] }}">
                                        <td>{{ $customer['id'] }}</td>
                                        <td class="Id">
                                            @can('show customer')
                                                <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                    class="btn btn-outline-primary">
                                                    {{ \Auth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-outline-primary">
                                                    {{ \Auth::user()->customerNumberFormat($customer['customer_id']) }}
                                                </a>
                                            @endcan
                                        </td>
                                        <td class="font-style">{{ $customer['name'] }}</td>
                                        <td>{{ $customer['customer_code'] ?? '-' }}</td>
                                        <td>{{ $customer['contact'] }}</td>
                                        <td>{{ $customer['email'] }}</td>
                                        <td>{{ $customer['chart_account_id'] != 0 ? \App\Models\ChartOfAccount::where('id', $customer['chart_account_id'])->first()->name :'-' }}</td>
                                        <td>{{ \Auth::user()->priceFormat($ledgerBalances[$customer['id']] ?? 0) }}</td>
                                        <td class="Action">
                                            <span>
                                                @if ($customer['is_active'] == 0)
                                                    <span class="text-muted">{{ __('Inactive') }}</span>
                                                @else
                                                    @can('show customer')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('customer.show', \Crypt::encrypt($customer['id'])) }}"
                                                                class="mx-2 px-2 btn btn-sm align-items-center text-white"
                                                                data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                                {{ __('View') }}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit customer')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-2 px-2 btn btn-sm align-items-center text-white"
                                                                data-url="{{ route('customer.edit', $customer['id']) }}"
                                                                data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                                title="{{ __('Edit') }}"
                                                                data-title="{{ __('Edit Customer') }}">
                                                                {{ __('Edit') }}
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete customer')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('customer.destroy', $customer['id']) }}"
                                                                id="delete-form-{{ $customer['id'] }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#"
                                                                    class="mx-2 px-2 btn btn-sm align-items-center text-white bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}">{{ __('Delete') }}</a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                @endif
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
