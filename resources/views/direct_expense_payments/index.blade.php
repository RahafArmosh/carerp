@extends('layouts.admin')
@section('page-title')
    {{ __('Direct Expense Payments') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Direct Expense Payments') }}</li>
@endsection
@push('script-page')
<script>
$(document).ready(function() {
    // Wait for SweetAlert2 to be available (check if already loaded or wait)
    function initSweetAlert() {
        if (typeof Swal === 'undefined') {
            // Load SweetAlert2 if not already loaded
            if (!document.querySelector('script[src*="sweetalert"]')) {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                script.onload = function() {
                    attachEventHandlers();
                };
                document.head.appendChild(script);
            } else {
                // SweetAlert2 script exists but not loaded yet, wait a bit
                setTimeout(function() {
                    if (typeof Swal !== 'undefined') {
                        attachEventHandlers();
                    } else {
                        console.warn('SweetAlert2 not loaded');
                    }
                }, 500);
            }
        } else {
            attachEventHandlers();
        }
    }
    
    function attachEventHandlers() {
        // Use event delegation for dynamically loaded forms (DataTables)
        $(document).on('submit', 'form.js-swal-delete', function(e) {
            // Check if form is already confirmed (to prevent infinite loop)
            if ($(this).data('swal-confirmed')) {
                $(this).data('swal-confirmed', false);
                return true; // Allow form submission
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            var form = this;
            
            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: '{{ __('Are you sure you want to delete this payment? This action cannot be undone and will reverse ledger entries if the payment was already marked as paid.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, delete it!') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mark form as confirmed to bypass the handler on next submit
                    $(form).data('swal-confirmed', true);
                    // Use native submit to bypass jQuery event handlers
                    form.submit();
                }
            });
            
            return false;
        });
        
        // Handle mark as paid confirmation
        $(document).on('submit', 'form.js-swal-send', function(e) {
            // Check if form is already confirmed (to prevent infinite loop)
            if ($(this).data('swal-confirmed')) {
                $(this).data('swal-confirmed', false);
                return true; // Allow form submission
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            var form = this;
            
            Swal.fire({
                title: '{{ __('Mark as Paid?') }}',
                text: '{{ __('Are you sure you want to mark this payment as paid? This will create ledger entries and update the direct expense payment status.') }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, mark as paid') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mark form as confirmed to bypass the handler on next submit
                    $(form).data('swal-confirmed', true);
                    // Use native submit to bypass jQuery event handlers
                    form.submit();
                }
            });
            
            return false;
        });
    }
    
    // Initialize
    initSweetAlert();
});
</script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Direct Expense Payments') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('direct_expense_payments.index') }}" method="GET" id="payment_form">
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
                                            <label for="vendor" class="form-label">{{ __('Vendor') }}</label>
                                            <select name="vendor" class="form-control select2">
                                                @foreach ($vendors as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ request()->get('vendor') == $value ? 'selected' : '' }}>
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
                                <a href="{{ route('direct_expense_payments.index') }}" class="btn btn-sm btn-danger">
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
                                    <th>{{ __('Expense No') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount (AED)') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Currency Amount') }}</th>
                                    <th>{{ __('Rate') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>#{{ $payment->id }}</td>
                                        <td>
                                            @if($payment->directExpense)
                                                <a href="{{ route('direct_expenses.show', $payment->direct_expense_id) }}" 
                                                   target="_blank" 
                                                   class="text-primary" 
                                                   title="{{ __('View Direct Expense') }}">
                                                    {{ Auth::user()->expenseNumberFormat($payment->directExpense->expense_number) }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>
                                            @if($payment->currency_id)
                                                {{ optional($payment->currency)->code ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->currency_id && $payment->currency_amount !== null)
                                                {{ number_format($payment->currency_amount, 2) }}
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
                                        <td>{{ optional($payment->vendor)->name ?? '-' }}</td>
                                        <td>{{ $payment->reference ?? '-' }}</td>
                                        <td>
                                            @if ($payment->status == 0)
                                                <span class="badge bg-primary">{{ __('Draft') }}</span>
                                            @elseif($payment->status == 2)
                                                <span class="badge bg-success">{{ __('Paid') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('direct_expense_payments.show', $payment->id) }}"
                                                class="btn btn-sm btn-info">{{ __('View') }}</a>
                                            @if ($payment->status == 0)
                                                <a href="{{ route('direct_expense_payments.edit', $payment->id) }}"
                                                    class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                                                <form action="{{ route('direct_expense_payments.send', $payment->id) }}"
                                                    method="POST" class="d-inline js-swal-send">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        {{ __('Mark as Paid') }}
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('direct_expense_payments.destroy', $payment->id) }}"
                                                method="POST" class="d-inline js-swal-delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
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

