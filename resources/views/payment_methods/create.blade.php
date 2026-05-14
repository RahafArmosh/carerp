@extends('layouts.admin')

@section('page-title')
    {{ __('Payment Methods') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payment Methods') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('payment-methods.create') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> {{ __('Add Payment Method') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <form method="POST" action="{{ route('payment-methods.store') }}">
                @csrf
                <div class="card">
                    <div class="card-body row">
                        <div class="form-group col-md-6">
                            <label class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" class="form-control select" required>
                                <option value="" disabled selected>{{ __('Select Warehouse') }}</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="form-label">{{ __('Bank Account') }}</label>
                            <select name="bank_account_id" class="form-control select" required>
                                <option value="" disabled selected>{{ __('Select Bank Account') }}</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->holder_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Payment Method Name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Cash, Bank Transfer" required>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a href="{{ route('payment-methods.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
