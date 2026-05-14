@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Manufacturers') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manufacturers') }}</li>
@endsection

<!-- SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('action-btn')
    <div class="float-end">
        {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('bill.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a>
        <a href="{{ route('bill.export') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Export') }}">
            <i class="ti ti-file-export"></i>
        </a> --}}
        {{-- @can('create Manufacturer') --}}
        <a href="{{ route('car_accessories.search') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Create') }}">
            <i class="ti ti-plus"></i>
        </a>
        {{-- @endcan --}}
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 table-responsive" id="multiCollapseExample1">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Request No</th>
                        <th>Request Date</th>
                        <th>Created BY</th>
                        <th>Invoice NO</th>
                        <th>Bill NO</th>
                        <th>Total Car Quantity</th>
                        <th>Status</th>
                        {{-- <th>Car</th> --}}
                        {{-- <th>Accessory</th> --}}
                        {{-- <th>Sell Price</th> --}}
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carAccessories as $item)
                        <tr>
                            <td>{{ $item->request_no }}</td>
                            <td>{{ $item->request_date }}</td>
                            <td>{{ $item->created_by_name ?? $item->created_by }}</td>
                            <td>
                                @if($item->invoices_list && Auth::check())
                                    @php
                                        $invoices = explode(', ', $item->invoices_list);
                                        $invoiceDisplay = [];
                                        foreach($invoices as $invoice) {
                                            $parts = explode(':', $invoice);
                                            if(count($parts) == 2) {
                                                $invoiceId = trim($parts[0]);
                                                $paymentStatus = trim($parts[1]);
                                                $formattedInvoice = Auth::user()->invoiceNumberFormat($invoiceId);
                                                
                                                // Use the same pattern as invoice system
                                                $statusBadge = '';
                                                if($paymentStatus == 0) {
                                                    $statusBadge = '<span class="status_badge badge bg-secondary p-2 px-3 rounded">' . __(\App\Models\Invoice::$paymentstatues[$paymentStatus]) . '</span>';
                                                } elseif($paymentStatus == 2) {
                                                    $statusBadge = '<span class="status_badge badge bg-warning p-2 px-3 rounded">' . __(\App\Models\Invoice::$paymentstatues[$paymentStatus]) . '</span>';
                                                } elseif($paymentStatus == 4) {
                                                    $statusBadge = '<span class="status_badge badge bg-primary p-2 px-3 rounded">' . __(\App\Models\Invoice::$paymentstatues[$paymentStatus]) . '</span>';
                                                } else {
                                                    $statusBadge = '<span class="status_badge badge bg-secondary p-2 px-3 rounded">' . __(\App\Models\Invoice::$paymentstatues[$paymentStatus] ?? 'Unknown') . '</span>';
                                                }
                                                
                                                $invoiceDisplay[] = $formattedInvoice . ' ' . $statusBadge;
                                            }
                                        }
                                    @endphp
                                    {!! implode('<br>', $invoiceDisplay) !!}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($item->bills_list && Auth::check())
                                    @php
                                        $bills = explode(', ', $item->bills_list);
                                        $billDisplay = [];
                                        foreach($bills as $bill) {
                                            $parts = explode(':', $bill);
                                            if(count($parts) == 2) {
                                                $billId = trim($parts[0]);
                                                $billStatus = trim($parts[1]);
                                                $formattedBill = Auth::user()->billNumberFormat($billId);
                                                
                                                // Use the same pattern as bill system
                                                $statusBadge = '';
                                                if($billStatus == 0) {
                                                    $statusBadge = '<span class="status_badge badge bg-primary p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus]) . '</span>';
                                                } elseif($billStatus == 1) {
                                                    $statusBadge = '<span class="status_badge badge bg-secondary p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus]) . '</span>';
                                                } elseif($billStatus == 2) {
                                                    $statusBadge = '<span class="status_badge badge bg-warning p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus]) . '</span>';
                                                } elseif($billStatus == 4) {
                                                    $statusBadge = '<span class="status_badge badge bg-danger p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus]) . '</span>';
                                                } elseif($billStatus == 6) {
                                                    $statusBadge = '<span class="status_badge badge bg-info p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus]) . '</span>';
                                                } else {
                                                    $statusBadge = '<span class="status_badge badge bg-secondary p-2 px-3 rounded">' . __(\App\Models\Bill::$statues[$billStatus] ?? 'Unknown') . '</span>';
                                                }
                                                
                                                $billDisplay[] = $formattedBill . ' ' . $statusBadge;
                                            }
                                        }
                                    @endphp
                                    {!! implode('<br>', $billDisplay) !!}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->car_id_count ?? 0 }}</td>
                            <td>
                                <span
                                    class="badge bg-{{ $item->status == 'approved' ? 'success' : ($item->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            {{-- <td>{{ $item->car->name }}</td> --}}
                            {{-- <td>{{ $item->accessory->name }}</td> --}}
                            
                            {{-- <td    >{{ number_format($item->sell_price, 2) }}</td> --}}
                            <td>
                                <a href="{{ route('car_accessories.show', $item->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRequestWithDate({{ $item->id }}, '{{ $item->request_no }}', '{{ $item->status }}')">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection

<script>
// Function to delete request with date picker - defined globally
window.deleteRequestWithDate = function(requestId, requestNo, status) {
    Swal.fire({
        title: 'Select Delete Date',
        html: `
            <div class="mb-3">
                <label for="delete_date" class="form-label">Delete Date:</label>
                <input type="date" id="delete_date" class="form-control" min="{{ date('Y-m-d') }}" required>
            </div>
            <div class="alert alert-info">
                <small><i class="fas fa-info-circle"></i> Please select a delete date. This date must be after any assignment send dates for this request.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Delete Request',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            const deleteDate = document.getElementById('delete_date').value;
            if (!deleteDate) {
                Swal.showValidationMessage('Please select a delete date');
                return false;
            }
            return deleteDate;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const deleteDate = result.value;
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete request "${requestNo}" with delete date: ${deleteDate}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete Request',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Deleting request and processing ledger entries...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Create a form and submit it with the selected date
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("car_accessories.destroy", ":id") }}'.replace(':id', requestId);
                    
                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfToken);
                    
                    // Add method override
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                    
                    // Add delete date
                    const deleteDateInput = document.createElement('input');
                    deleteDateInput.type = 'hidden';
                    deleteDateInput.name = 'delete_date';
                    deleteDateInput.value = deleteDate;
                    form.appendChild(deleteDateInput);
                    
                    // Submit the form
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
};
</script>
