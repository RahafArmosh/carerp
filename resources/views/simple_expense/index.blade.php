@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Service Bills') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Service Bill') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create bill')
            <a href="{{ route('simple-expense.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('simple-expense.index') }}" method="GET" id="frm_submit">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="bill_date" class="form-label">Expense Date</label>
                                                <input type="text" name="bill_date"
                                                    value="{{ isset($_GET['bill_date']) ? $_GET['bill_date'] : '' }}"
                                                    class="form-control month-btn" id="pc-daterangepicker-1" readonly>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="category" class="form-label">Category</label>
                                                <select name="category" class="form-control select">
                                                    @foreach ($category as $key => $value)
                                                        <option value="{{ $key }}"
                                                            @if (isset($_GET['category']) && $_GET['category'] == $key) selected @endif>
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
                                                onclick="document.getElementById('frm_submit').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('simple-expense.index') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-trash"></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{ __('Expense') }}</th>
                                <th>{{ __('Vendor') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payment Status') }}</th>
                                <th>{{ __('Amount') }}</th>
                                @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                    <th width="10%">{{ __('Action') }}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($expense->id)) }}"
                                                class="btn btn-outline-primary">{{ $expense->expense_id }}</a>
                                        </td>
                                        <td>{{ optional($expense->vender)->name ?? '-' }}</td>
                                        <td>{{ !empty($expense->category) ? $expense->category->name : '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($expense->expense_date) }}</td>
                                        <td>
                                            <span
                                                class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\SimpleExpense::$statues[$expense->status]) }}</span>
                                        </td>
                                        <td>
                                            @if ($expense->payment_status == 0)
                                                <span class="status_badge badge bg-secondary p-2 px-3 rounded">
                                                    {{ __(\App\Models\SimpleExpense::$paymentstatues[$expense->payment_status] ?? '-') }}
                                                </span>
                                            @elseif($expense->payment_status == 2)
                                                <span class="status_badge badge bg-warning p-2 px-3 rounded">
                                                    {{ __(\App\Models\SimpleExpense::$paymentstatues[$expense->payment_status] ?? '-') }}
                                                </span>
                                            @elseif($expense->payment_status == 4)
                                                <span class="status_badge badge bg-primary p-2 px-3 rounded">
                                                    {{ __(\App\Models\SimpleExpense::$paymentstatues[$expense->payment_status] ?? '-') }}
                                                </span>
                                            @else
                                                <span class="status_badge badge bg-secondary p-2 px-3 rounded">-</span>
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($expense->getTotal()) }}</td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($expense->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="{{ route('simple-expense.edit', \Crypt::encrypt($expense->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('simple-expense.destroy', $expense->id) }}"
                                                                id="delete-form-{{ $expense->id }}">
                                                                @method('DELETE')
                                                                @csrf
                                                                <input type="hidden" name="delete_date"
                                                                    id="delete-date-{{ $expense->id }}">
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    title="{{ __('Delete') }}"
                                                                    onclick="confirmDeleteWithDate({{ $expense->id }})">
                                                                    <i class="ti ti-trash text-white"></i>
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

@push('script-page')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeleteWithDate(id) {
    Swal.fire({
        title: 'Are you sure?',
        html: `<input type="date" id="delete-date-input" class="swal2-input" required>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const date = document.getElementById('delete-date-input').value;
            if (!date) {
                Swal.showValidationMessage('Delete date is required');
                return false;
            }
            return date;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-date-' + id).value = result.value;
            document.getElementById('delete-form-' + id).submit();
        }
    });
}
</script>
@endpush