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
           
        <form method="POST" action="{{ route('payment-methods.update', $paymentMethod->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Warehouse') }}</label>
                        <select class="form-control select" name="warehouse_id" required>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ $paymentMethod->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Bank Account') }}</label>
                        <select class="form-control select" name="bank_account_id" required>
                            @foreach($bankAccounts as $bankAccount)
                                <option value="{{ $bankAccount->id }}" {{ $paymentMethod->bank_account_id == $bankAccount->id ? 'selected' : '' }}>
                                    {{ $bankAccount->holder_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Payment Method Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $paymentMethod->name }}" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
                <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
            </div>
        </form>

        </div>
    </div>
    
    @if(isset($logs))
        @include('partials.pos_logs', ['logs' => $logs])
    @endif
@endsection


