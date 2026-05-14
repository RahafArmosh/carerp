@extends('layouts.admin')
@section('page-title')
    {{ __('Warehouse Transfer') }}
@endsection
@push('script-page')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Hide global loading overlay once the page JS is ready
        const pageLoader = $('#page-loading-overlay');
        if (pageLoader.length) {
            setTimeout(function() {
                pageLoader.fadeOut(300, function() {
                    $(this).addClass('d-none');
                });
            }, 200); // small delay to ensure layout is rendered
        }
        // Toggle transfer items visibility
        $(document).on('click', '.toggle-transfers', function(e) {
            e.preventDefault();
            const requestId = $(this).data('request-id');
            const icon = $(this).find('i');
            const transfersRow = $(`#transfers-${requestId}`);
            
            if (transfersRow.is(':visible')) {
                transfersRow.slideUp();
                icon.removeClass('ti-chevron-down').addClass('ti-chevron-right');
            } else {
                transfersRow.slideDown();
                icon.removeClass('ti-chevron-right').addClass('ti-chevron-down');
            }
        });

        // Approve transfer confirmation
        $(document).on('click', '.approve-transfer-btn', function(e) {
            e.preventDefault();
            const formId = $(this).data('form-id');
            const transferId = $(this).data('transfer-id');
            
            Swal.fire({
                title: '{{ __('Approve Transfer') }}',
                text: '{{ __('Are you sure you want to approve this transfer? Stock will be moved.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Approve') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });

        // Delete transfer confirmation
        $(document).on('click', '.delete-transfer-btn', function(e) {
            e.preventDefault();
            const formId = $(this).data('form-id');
            
            Swal.fire({
                title: '{{ __('Delete Transfer') }}',
                text: '{{ __('Are you sure you want to delete this draft transfer?') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Delete') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });

        // Approve all draft transfers
        $(document).on('click', '.approve-all-transfers-btn', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: '{{ __('Approve All Draft Transfers') }}',
                html: '{{ __('Are you sure you want to approve all draft transfers? Stock will be moved for all approved transfers.') }}<br><br><strong class="text-warning">{{ __('This action cannot be undone.') }}</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Approve All') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: '{{ __('Processing...') }}',
                        text: '{{ __('Please wait while we approve all transfers.') }}',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request
                    $.ajax({
                        url: '{{ route('warehouse-transfer.approve-all') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '{{ __('Success') }}',
                                text: response.message || '{{ __('All draft transfers have been approved successfully.') }}',
                                icon: 'success',
                                confirmButtonText: '{{ __('OK') }}'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = '{{ __('An error occurred while approving transfers.') }}';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                title: '{{ __('Error') }}',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: '{{ __('OK') }}'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
<style>
    #page-loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }
    #page-loading-overlay .loader-box {
        background: #ffffff;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        color: #333333;
    }
    #page-loading-overlay .spinner-border {
        width: 1.75rem;
        height: 1.75rem;
        border-width: 0.2em;
    }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Transfer') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @if(\Auth::user()->type == 'company' && isset($requests) && $requests->where('status', 'draft')->count() > 0)
            <button type="button" 
                    class="btn btn-sm btn-success me-2 approve-all-transfers-btn"
                    data-bs-toggle="tooltip" 
                    title="{{ __('Approve All Draft Transfers') }}">
                <i class="ti ti-check"></i> {{ __('Approve All Draft') }}
            </button>
        @endif
        @if (\Auth::user()->type == 'company' || \Auth::user()->can('create transfer'))
        <a href="#" data-url="{{ route('warehouse-transfer.file.import') }}" data-size="lg"
           data-bs-toggle="tooltip" title="{{ __('Import from Excel') }}"
           data-title="{{ __('Import Warehouse Transfer from Excel') }}"
           data-ajax-popup="true"
           class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i> {{ __('Import') }}
        </a>
        <a href="{{ route('warehoustrans.create') }}" data-size="lg" data-url=""
           data-bs-toggle="tooltip" title="{{ __('Create') }}"
           data-title="{{ __('Create Warehouse Transfer') }}"
           class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
        @endif
    </div>
@endsection

@section('content')
    @php
        $userWarehouseIds = \Auth::user()->warehouses()->pluck('warehouses.id')->map(fn($id) => (int) $id)->all();
    @endphp
    <div class="row">
        <div class="col-xl-12">
            {{-- Full-page loading overlay to block interaction until initial JS is ready --}}
            <div id="page-loading-overlay">
                <div class="loader-box">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                    <span>{{ __('Loading warehouse transfers, please wait...') }}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        {{-- Removed "datatable" class to prevent heavy JS processing on very large datasets
                             which was causing the Warehouse Transfer page to become unresponsive. --}}
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Request Number') }}</th>
                                    <th>{{ __('From Warehouse') }}</th>
                                    <th>{{ __('To Warehouse') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    {{-- Request Row --}}
                                    <tr class="font-style request-row" style="background-color: #f8f9fa;">
                                        <td>
                                            <a href="{{ route('warehouse-transfer-request.show', $request->id) }}" class="btn btn-outline-primary btn-sm">
                                                {{ $request->request_number }}
                                            </a>
                                        </td>
                                        <td>{{ optional($request->fromWarehouse)->name }}</td>
                                        <td>{{ optional($request->toWarehouse)->name }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $request->transfers->count() }} {{ __('items') }}</span>
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($request->request_date) }}</td>
                                        <td>
                                            @if($request->status == 'draft')
                                                <span class="badge bg-secondary">{{ __('Draft') }}</span>
                                            @elseif($request->status == 'pending')
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @elseif($request->status == 'approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                            @elseif($request->status == 'rejected')
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @elseif($request->status == 'cancelled')
                                                <span class="badge bg-dark">{{ __('Cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td class="Action">
                                            <span class="d-inline-flex">
                                                <div class="action-btn bg-primary">
                                                    <a href="{{ route('warehouse-transfer-request.show', $request->id) }}" 
                                                       class="mx-3 btn btn-sm align-items-center" 
                                                       data-bs-toggle="tooltip" 
                                                       title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                                @if($request->status == 'draft' || $request->status == 'pending')
                                                    @if(\Auth::user()->type == 'company' || \Auth::user()->can('delete transfer'))
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form action="{{ route('warehouse-transfer-request.destroy', $request->id) }}"
                                                                  method="POST"
                                                                  id="delete-request-form-{{ $request->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#"
                                                                   class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                   data-bs-toggle="tooltip"
                                                                   title="{{ __('Delete') }}"
                                                                   onclick="confirmDelete('{{ __('Are You Sure?') }}', 'delete-request-form-{{ $request->id }}')">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endif
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                
                                {{-- Transfers without requests (backward compatibility) --}}
                                @if(isset($transfersWithoutRequest) && $transfersWithoutRequest->count() > 0)
                                    <tr class="font-style" style="background-color: #fff3cd;">
                                        <td colspan="7">
                                            <strong class="text-warning">{{ __('Legacy Transfers (without request)') }}</strong>
                                        </td>
                                    </tr>
                                    @foreach($transfersWithoutRequest as $warehouse_transfer)
                                        <tr class="font-style">
                                            <td><span class="text-muted">-</span></td>
                                            <td><span class="text-muted">{{ __('No Request') }}</span></td>
                                            <td>{{ optional($warehouse_transfer->fromWarehouse)->name }}</td>
                                            <td>{{ optional($warehouse_transfer->toWarehouse)->name }}</td>
                                            <td>{{ optional($warehouse_transfer->product)->name ?? '-' }}</td>
                                            <td>{{ $warehouse_transfer->product_no }}</td>
                                            <td>{{ $warehouse_transfer->quantity }}</td>
                                            <td>{{ Auth::user()->dateFormat($warehouse_transfer->date) }}</td>
                                            <td>
                                                @if($warehouse_transfer->status == 'draft')
                                                    <span class="badge bg-warning">{{ __('Draft') }}</span>
                                                @elseif($warehouse_transfer->status == 'approved')
                                                    <span class="badge bg-success">{{ __('Approved') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($warehouse_transfer->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="Action">
                                                @if($warehouse_transfer->status == 'draft')
                                                    @if(\Auth::user()->type == 'company' || in_array((int) $warehouse_transfer->to_warehouse, $userWarehouseIds, true))
                                                    @can('edit warehouse')
                                                        <div class="action-btn bg-success ms-2">
                                                            <form id="approve-form-{{ $warehouse_transfer->id }}"
                                                                  action="{{ route('warehouse-transfer.approve', $warehouse_transfer->id) }}"
                                                                  method="POST">
                                                                @csrf
                                                                <a href="#"
                                                                   class="mx-3 btn btn-sm align-items-center bs-pass-para approve-transfer-btn"
                                                                   data-bs-toggle="tooltip"
                                                                   title="{{ __('Approve') }}"
                                                                   data-form-id="approve-form-{{ $warehouse_transfer->id }}"
                                                                   data-transfer-id="{{ $warehouse_transfer->id }}">
                                                                    <i class="ti ti-check text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                    @endif
                                                    @can('delete warehouse')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form id="delete-form-{{ $warehouse_transfer->id }}"
                                                                  action="{{ route('warehouse-transfer.destroy', $warehouse_transfer->id) }}"
                                                                  method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#"
                                                                   class="mx-3 btn btn-sm align-items-center bs-pass-para delete-transfer-btn"
                                                                   data-bs-toggle="tooltip"
                                                                   title="{{ __('Delete') }}"
                                                                   data-form-id="delete-form-{{ $warehouse_transfer->id }}">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                @else
                                                    <span class="text-muted">{{ __('No actions available') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $requests->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
