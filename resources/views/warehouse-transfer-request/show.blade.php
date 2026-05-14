@extends('layouts.admin')
@section('page-title')
    {{ __('Transfer Request') }}: {{ $request->request_number }}
@endsection
@push('script-page')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Approve request confirmation
        $(document).on('click', '.approve-request-btn', function(e) {
            e.preventDefault();
            const formId = $(this).data('form-id');
            
            Swal.fire({
                title: '{{ __('Approve Transfer Request') }}',
                html: '{{ __('Are you sure you want to approve this transfer request? Stock will be moved for all items.') }}<br><br><strong class="text-warning">{{ __('This action cannot be undone.') }}</strong>',
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

        // Edit quantity inline
        $(document).on('click', '.edit-qty-btn', function(e) {
            e.preventDefault();
            const transferId = $(this).data('transfer-id');
            const currentQty = $(this).data('current-qty');
            const productNo = $(this).data('product-no');
            
            Swal.fire({
                title: '{{ __('Edit Quantity') }}',
                html: `
                    <p>{{ __('Product No') }}: <strong>${productNo}</strong></p>
                    <input type="number" id="swal-qty-input" class="swal2-input" value="${currentQty}" min="1" required>
                `,
                showCancelButton: true,
                confirmButtonText: '{{ __('Update') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value || value < 1) {
                        return '{{ __('Quantity must be at least 1') }}';
                    }
                },
                preConfirm: () => {
                    const qty = document.getElementById('swal-qty-input').value;
                    if (!qty || qty < 1) {
                        Swal.showValidationMessage('{{ __('Quantity must be at least 1') }}');
                        return false;
                    }
                    return qty;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newQty = result.value;
                    
                    // Show loading
                    Swal.fire({
                        title: '{{ __('Updating...') }}',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request
                    $.ajax({
                        url: '{{ url('warehouse-transfer') }}/' + transferId + '/update-quantity',
                        type: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        data: {
                            _token: '{{ csrf_token() }}',
                            quantity: newQty
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: '{{ __('Success') }}',
                                    text: response.message || '{{ __('Quantity updated successfully') }}',
                                    icon: 'success',
                                    confirmButtonText: '{{ __('OK') }}'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: '{{ __('Error') }}',
                                    text: response.message || '{{ __('An error occurred while updating quantity.') }}',
                                    icon: 'error',
                                    confirmButtonText: '{{ __('OK') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = '{{ __('An error occurred while updating quantity.') }}';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.status === 422) {
                                // Validation errors
                                const errors = xhr.responseJSON.errors;
                                if (errors && errors.quantity) {
                                    errorMessage = errors.quantity[0];
                                }
                            } else if (xhr.status === 403) {
                                errorMessage = '{{ __('Permission denied.') }}';
                            } else if (xhr.status === 400) {
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
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
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse-transfer-request.index') }}">{{ __('Transfer Requests') }}</a></li>
    <li class="breadcrumb-item">{{ $request->request_number }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('warehouse-transfer-request.print', $request->id) }}"
           target="_blank"
           class="btn btn-sm btn-primary me-2">
            <i class="ti ti-printer"></i> {{ __('Print Form') }}
        </a>
        @if(in_array($request->status, ['draft', 'pending']) && !empty($canApproveRequest))
            @can('edit transfer')
                <form id="approve-request-form" action="{{ route('warehouse-transfer-request.approve', $request->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="button" 
                            class="btn btn-sm btn-success approve-request-btn"
                            data-form-id="approve-request-form"
                            data-bs-toggle="tooltip" 
                            title="{{ __('Approve Request') }}">
                        <i class="ti ti-check"></i> {{ __('Approve Request') }}
                    </button>
                </form>
            @endcan
        @endif
        <a href="{{ route('warehouse-transfer-request.index') }}" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left"></i> {{ __('Back') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            {{-- Request Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Request Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>{{ __('Request Number') }}:</strong><br>
                            <span class="text-primary">{{ $request->request_number }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('From Warehouse') }}:</strong><br>
                            {{ optional($request->fromWarehouse)->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('To Warehouse') }}:</strong><br>
                            {{ optional($request->toWarehouse)->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Status') }}:</strong><br>
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
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <strong>{{ __('Request Date') }}:</strong><br>
                            {{ Auth::user()->dateFormat($request->request_date) }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Created By') }}:</strong><br>
                            {{ optional($request->creator)->name }}
                        </div>
                        @if($request->approved_by)
                        <div class="col-md-3">
                            <strong>{{ __('Approved By') }}:</strong><br>
                            {{ optional($request->approver)->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('Approved At') }}:</strong><br>
                            {{ Auth::user()->dateFormat($request->approved_at) }} {{ Auth::user()->timeFormat($request->approved_at) }}
                        </div>
                        @endif
                    </div>
                    @if($request->notes)
                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>{{ __('Notes') }}:</strong><br>
                            <p class="text-muted">{{ $request->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>{{ __('Attachment') }}:</strong><br>
                            @if($request->attachment)
                                <a href="{{ asset($request->attachment) }}" target="_blank" class="btn btn-sm btn-info mt-1">
                                    <i class="ti ti-paperclip"></i> {{ __('View Attachment') }}
                                </a>
                            @else
                                <span class="text-muted">{{ __('No attachment uploaded') }}</span>
                            @endif
                        </div>
                    </div>

                    @if(in_array($request->status, ['draft', 'pending']) && (\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer')))
                        <div class="row mt-3">
                            <div class="col-md-8 col-lg-6">
                                <form action="{{ route('warehouse-transfer-request.attachment.upload', $request->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label class="form-label mb-1">{{ __('Upload / Replace Attachment') }}</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control @error('attachment') is-invalid @enderror" name="attachment" required>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="ti ti-upload"></i> {{ __('Upload') }}
                                        </button>
                                    </div>
                                    @error('attachment')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Allowed: jpg, jpeg, png, pdf, doc, docx, xls, xlsx, csv, txt. Max 10MB.') }}</small>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Transfer Items Table --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Transfer Items') }} ({{ $request->transfers->count() }})</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Product No') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(in_array($request->status, ['draft', 'pending']))
                                    <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($request->transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->product_no }}</td>
                                        <td>{{ optional($transfer->product)->name ?? '-' }}</td>
                                        <td>
                                            <strong>{{ $transfer->quantity }}</strong>
                                        </td>
                                        <td>
                                            @if($transfer->status == 'draft')
                                                <span class="badge bg-secondary">{{ __('Draft') }}</span>
                                            @elseif($transfer->status == 'approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                            @endif
                                        </td>
                                        @if(in_array($request->status, ['draft', 'pending']))
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary edit-qty-btn"
                                                    data-transfer-id="{{ $transfer->id }}"
                                                    data-current-qty="{{ $transfer->quantity }}"
                                                    data-product-no="{{ $transfer->product_no }}"
                                                    data-bs-toggle="tooltip" 
                                                    title="{{ __('Edit Quantity') }}">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ in_array($request->status, ['draft', 'pending']) ? '5' : '4' }}" class="text-center text-muted">
                                            {{ __('No transfer items found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Activity Logs --}}
            @include('partials.pos_logs', ['logs' => $logs])
        </div>
    </div>
@endsection

