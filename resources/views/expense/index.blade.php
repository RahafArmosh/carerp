@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Expenses') }}
@endsection
@push('script-page')
    <script>
        $('.copy_link').click(function(e) {
            e.preventDefault();
            var copyText = $(this).attr('href');

            document.addEventListener('copy', function(e) {
                e.clipboardData.setData('text/plain', copyText);
                e.preventDefault();
            }, true);

            document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- <script>
        function confirmDelete(expenseId) {
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
                    document.getElementById('delete-expense-id').value = expenseId;
                    document.getElementById('delete-date').value = selectedDate;
                    document.getElementById('delete-form').submit();
                }
            });
        }
    </script> --}}
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Expense') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create bill')
            <a href="{{ route('expense.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection


@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('expense.index') }}" method="GET" id="frm_submit">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-3"></div>
                                        <div class="col-3"></div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="bill_date" class="form-label">Payment Date</label>
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
                                            <a href="{{ route('expense.index') }}" class="btn btn-sm btn-danger "
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}"
                                                data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
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
                                    <th> {{ __('Expense') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="Id">
                                            <a href="{{ route('expense.show', \Crypt::encrypt($expense->id)) }}"
                                                class="btn btn-outline-primary">{{ $expense->bill_id }}</a>
                                        </td>
                                        <td>{{ !empty($expense->category) ? $expense->category->name : '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($expense->bill_date) }}</td>
                                        <td>
                                            <span
                                                class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>

                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('expense.show', \Crypt::encrypt($expense->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('edit bill')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="{{ route('expense.edit', \Crypt::encrypt($expense->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="Edit"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form id="delete-form-{{ $expense->id }}"
                                                                action="{{ route('expense.destroy', $expense->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    {{-- data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}" --}}
                                                                    onclick="confirmDelete(event, {{ $expense->id }})">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                    <input type="hidden" name="delete_date" id="delete-date-{{ $expense->id }}" value="">
                                                                    <!-- Hidden input for date -->
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
<script>
    function confirmDelete(event, subProductId) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure you want to delete this item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Enter the date to delete (YYYY-MM-DD):',
                    html: '<input type="date" id="swal-delete-date" class="swal2-input" />',
                    focusConfirm: false,
                    didOpen: () => {
                        const input = document.getElementById('swal-delete-date');
                        const today = (new Date()).toISOString().split("T")[0];
                        // input.min = today;
                    },
                    preConfirm: () => {
                        const input = document.getElementById('swal-delete-date');
                        if (!input.value) {
                            Swal.showValidationMessage('Please select a date');
                        }
                        return input.value;
                    },
                    inputPlaceholder: 'YYYY-MM-DD',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'You need to enter a date!';
                        }
                        // Simple date format validation
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                            return 'Please enter a valid date in YYYY-MM-DD format!';
                        }
                    }
                }).then((dateResult) => {
                    if (dateResult.isConfirmed && dateResult.value) {
                        console.log('Date to delete:', dateResult.value);
                        document.getElementById('delete-date-' + subProductId).value = dateResult
                            .value;
                        document.getElementById('delete-form-' + subProductId).submit();
                    }
                });
            }
        });
    }
</script>
