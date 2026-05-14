@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Direct Expenses') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Direct Expenses') }}</li>
@endsection
@section('content')
<div class="row">
    <div class="col-sm-12">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto">
                        <h5 class="mb-0">{{ __('Direct Expenses') }}</h5>
                    </div>
                    <div class="col-auto">
                        @can('create bill')
                        <a href="{{ route('direct_expenses.search') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus"></i> {{ __('Create Direct Expense') }}
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Expense Number') }}</th>
                                <th>{{ __('Vendor') }}</th>
                                <th>{{ __('Items Count') }}</th>
                                <th>{{ __('Total Amount') }}</th>
                                <th>{{ __('Currency') }}</th>
                                <th>{{ __('Payment Status') }}</th>
                                <th>{{ __('Expense Date') }}</th>
                                <th width="10%">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td>
                                        <a href="{{ route('direct_expenses.show', $expense->id) }}" class="btn btn-outline-primary btn-sm">
                                            {{ Auth::user()->expenseNumberFormat($expense->expense_number) }}
                                        </a>
                                    </td>
                                    <td>{{ optional($expense->vendor)->name ?? '-' }}</td>
                                    <td>{{ $expense->items->count() }}</td>
                                    <td>{{ number_format($expense->getTotalAmount() + $expense->getTotalTaxAmount(), 2) }} {{ Auth::user()->currencySymbol() }}</td>
                                    <td>
                                        @if($expense->currency)
                                            {{ $expense->currency->code }}
                                            @if($expense->exchange_rate > 0)
                                                <br><small class="text-muted">(Rate: {{ number_format($expense->exchange_rate, 4) }})</small>
                                            @endif
                                        @else
                                            {{ Auth::user()->currencySymbol() }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($expense->payment_status == 0)
                                            <span class="badge bg-warning">{{ __('Unpaid') }}</span>
                                        @elseif($expense->payment_status == 2)
                                            <span class="badge bg-info">{{ __('Partially Paid') }}</span>
                                        @elseif($expense->payment_status == 4)
                                            <span class="badge bg-success">{{ __('Paid') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ Auth::user()->dateFormat($expense->expense_date ?? $expense->created_at) }}</td>
                                    <td>
                                        <div class="action-btn bg-info ms-2">
                                            <a href="{{ route('direct_expenses.show', $expense->id) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Show') }}" data-original-title="{{ __('Detail') }}">
                                                <i class="ti ti-eye text-white"></i>
                                            </a>
                                        </div>
                                        @if($expense->payment_status == 0)
                                        @can('edit bill')
                                        <div class="action-btn bg-primary ms-2">
                                            <a href="{{ route('direct_expenses.edit', $expense->id) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        </div>
                                        @endcan
                                        @endif
                                        @can('delete bill')
                                        <div class="action-btn bg-danger ms-2">
                                            <form action="{{ route('direct_expenses.destroy', $expense->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash text-white"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('No direct expenses found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $expenses->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
