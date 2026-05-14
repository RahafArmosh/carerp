@extends('layouts.admin')
@section('page-title')
    {{ __('Service Bill Payments') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('simple-expense.index') }}">{{ __('Service Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Service Bill Payments') }}</li>
@endsection
@push('script-page')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('form.js-swal-delete').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: '{{ __('This action cannot be undone.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, delete it!') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endpush

@section('action-btn')
    <div class="float-end">
        @can('create payment')
            <a href="{{ route('simple-expense-payments.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('simple-expense-payments.index') }}" method="GET" id="payment_form">
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="date" class="form-label">{{ __('Date') }}</label>
                                            <input type="date" name="date" class="form-control"
                                                value="{{ request()->get('date') }}">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="expense_id" class="form-label">{{ __('Service Bill') }}</label>
                                            <select name="expense_id" class="form-control select2">
                                                @foreach ($expenses as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ request()->get('expense_id') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="account" class="form-label">{{ __('Account') }}</label>
                                            <select name="account" class="form-control select2">
                                                @foreach ($accounts as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ request()->get('account') == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <label for="status" class="form-label">{{ __('Status') }}</label>
                                            <select name="status" class="form-control select2">
                                                <option value="">{{ __('All') }}</option>
                                                @foreach ($status as $key => $label)
                                                    <option value="{{ $key }}"
                                                        {{ request()->get('status') == $key ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto mt-4">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-search"></i> {{ __('Apply') }}
                                </button>
                                <a href="{{ route('simple-expense-payments.index') }}" class="btn btn-sm btn-danger">
                                    <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Service Bill') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Amount (AED)') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Currency Amount') }}</th>
                                    <th>{{ __('Rate') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>#{{ $payment->id }}</td>
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>
                                            @if($payment->expense)
                                                <a href="{{ route('simple-expense.show', \Crypt::encrypt($payment->expense_id)) }}"
                                                    class="btn btn-outline-primary btn-sm">
                                                    {{ $payment->expense->expense_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ optional($payment->expense->vender)->name ?? '-' }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>
                                            @if($payment->currency_id)
                                                {{ optional($payment->currency)->code ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->currency_id && $payment->amount_in_currency !== null)
                                                {{ number_format($payment->amount_in_currency, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->currency_id && $payment->currency_rate)
                                                {{ number_format($payment->currency_rate, 6) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ optional($payment->bankAccount)->holder_name ?? '-' }}</td>
                                        <td>{{ $payment->reference ?? '-' }}</td>
                                        <td>
                                            @if ($payment->status == 0)
                                                <span class="badge bg-primary">{{ __('Draft') }}</span>
                                            @elseif($payment->status == 2)
                                                <span class="badge bg-success">{{ __('Paid') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @can('show payment')
                                                <a href="{{ route('simple-expense-payments.show', \Crypt::encrypt($payment->id)) }}"
                                                    class="btn btn-sm btn-info">{{ __('View') }}</a>
                                            @endcan
                                            @if ($payment->status == 0)
                                                @can('manage payment')
                                                    <form action="{{ route('simple-expense-payments.send', \Crypt::encrypt($payment->id)) }}"
                                                        method="POST" class="d-inline js-swal-send">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            {{ __('Mark as Paid') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                                @can('edit payment')
                                                    <a href="{{ route('simple-expense-payments.edit', \Crypt::encrypt($payment->id)) }}"
                                                        class="btn btn-sm btn-primary">{{ __('Edit') }}</a>
                                                @endcan
                                                @can('delete payment')
                                                    <form action="{{ route('simple-expense-payments.destroy', \Crypt::encrypt($payment->id)) }}"
                                                        method="POST" class="d-inline js-swal-delete">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

