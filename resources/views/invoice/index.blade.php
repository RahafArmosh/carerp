@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Invoices') }}
@endsection
@push('css-page')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
@endpush
@push('script-page')
    <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/select/3.0.0/js/dataTables.select.js"></script>
    <script src="https://cdn.datatables.net/select/3.0.0/js/select.dataTables.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script>
    <script>
        function copyToClipboard(element) {

            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            // document.addEventListener('copy', function (e) {
            //     e.clipboardData.setData('text/plain', copyText);
            //     e.preventDefault();
            // }, true);
            //
            // document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmDelete(event, invoiceId) {
            event.preventDefault();

            Swal.fire({
                title: 'Select Delete Date',
                html: `<input type="date" id="deleteDateInput" class="swal2-input" required>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const deleteDate = document.getElementById('deleteDateInput').value;
                    if (!deleteDate) {
                        Swal.showValidationMessage('Please select a delete date');
                    }
                    return deleteDate;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-date-${invoiceId}`).value = result.value;
                    document.getElementById(`delete-form-${invoiceId}`).submit();
                }
            });
        }
    </script>
@endpush


@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    @if ($type === 'rent')
        <li class="breadcrumb-item">{{ __('Rent Invoice') }}</li>
    @else
        <li class="breadcrumb-item">{{ __('Invoice') }}</li>
    @endif
@endsection

@section('action-btn')
    <div class="float-end d-flex flex-wrap gap-1 justify-content-end">
        @can('create invoice')
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
                data-url="{{ route('invoice.file.import') }}" data-ajax-popup="true"
                data-title="{{ __('Import Invoice Excel file') }}" class="btn btn-sm btn-primary">
                {{ __('Import') }}
            </a>
        @endcan

        <a href="{{ route('invoice.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            {{ __('Export') }}
        </a>

        @can('create invoice')
            <a href="{{ route('invoice.create', ['type' => $type]) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                {{ __('Create') }}
            </a>
        @endcan
    </div>
@endsection



@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form id="customer_submit">
                            <div class="row d-flex align-items-center justify-content-end">
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                        <input type="date" name="issue_date" id="issue_date"
                                            class="form-control month-btn">

                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                    <div class="btn-box">
                                        <label for="customer" class="form-label">{{ __('Customer') }}</label>
                                        <select name="customer" id="customer" class="form-control select2">
                                            @foreach ($customer as $id => $customerName)
                                                <option value="{{ $id }}">{{ $customerName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="btn-box">
                                        <label for="status" class="form-label">{{ __('Status') }}</label>
                                        <select name="status" id="status" class="form-control select">
                                            <option value="">Select Status</option>
                                            @foreach ($status as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="btn-box">
                                        <label for="status" class="form-label">{{ __('Payment Status') }}</label>
                                        <select id="paymentstatues" name="paymentstatues" class="form-control select">
                                            <option value="">Select Payment Status</option>
                                            @foreach ($paymentstatues as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-auto float-end ms-2 mt-4">
                                    <button type="button" class="btn btn-sm btn-primary" id="apply-filters"
                                        data-toggle="tooltip" data-original-title="{{ __('apply') }}">
                                        {{ __('Apply') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" id="reset-filters"
                                        data-toggle="tooltip" data-original-title="{{ __('Reset') }}">
                                        {{ __('Reset') }}
                                    </button>
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
                        <table class="table" id="invoice-list-table">
                            <thead>
                                <tr>
                                    <th> {{ __('Invoice') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Issue Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('old-datatable-js')
        <script>
            const invoiceTable = new DataTable('#invoice-list-table', {
                processing: true,
                serverSide: true,
                order: [[2, 'asc']], // Default sort by issue_date column (index 2) ascending (oldest first)
                ajax: {
                    url: "{{ $type === 'rent' ? route('rentinvoice.index') : route('invoice.index') }}",
                    data: function(d) {
                        d.issue_date = $('#issue_date').val();
                        d.customer = $('#customer').val();
                        d.status = $('#status').val();
                        d.paymentstatues = $('#paymentstatues').val();
                    }
                },
                columns: [{
                        data: 'invoice_id',
                        name: 'invoice_id'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'issue_date',
                        name: 'issue_date', // Server-side will sort by issue_date column
                        render: function(data, type, row) {
                            // For display, show formatted date
                            // For sorting, DataTables will use server-side sorting
                            if (type === 'display') {
                                return data;
                            }
                            // For type detection, return raw date
                            return row.issue_date_raw || data;
                        }
                    },
                    {
                        data: 'due_date',
                        name: 'due_date', // Server-side will sort by due_date column
                        render: function(data, type, row) {
                            // For display, show formatted date
                            // For sorting, DataTables will use server-side sorting
                            if (type === 'display') {
                                return data;
                            }
                            // For type detection, return raw date
                            return row.due_date_raw || data;
                        }
                    },
                    {
                        data: 'due_amount',
                        name: 'due_amount',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                        {
                            data: 'action',
                            orderable: false,
                            searchable: false
                        },
                    @endif
                ],
                columnDefs: [{
                        width: 90,
                        targets: 0
                    },
                    {
                        className: 'dt-left',
                        targets: '_all'
                    }
                ],
                info: true,
                paging: true,
                pageLength: 100,
                scrollY: 800,
                scrollX: true,
                scrollCollapse: true,
                autoWidth: true,
                language: {
                    searchPlaceholder: "Search here...",
                    search: "",
                }
            });

            // Apply filters button
            $('#apply-filters').on('click', function() {
                invoiceTable.ajax.reload();
            });

            // Reset filters button
            $('#reset-filters').on('click', function() {
                $('#issue_date').val('');
                $('#customer').val('').trigger('change');
                $('#status').val('');
                $('#paymentstatues').val('');
                invoiceTable.ajax.reload();
            });

            // Auto-apply filters on change (optional)
            $('#issue_date, #customer, #status, #paymentstatues').on('change', function() {
                invoiceTable.ajax.reload();
            });
        </script>
    @endpush
@endsection
