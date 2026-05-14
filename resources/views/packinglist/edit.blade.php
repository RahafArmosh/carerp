@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Packing List') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('packinglist.index') }}">{{ __('Packing Lists') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Packing List Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Packing List No') }}</th>
                                    <td>{{ \Auth::user()->packingListNumberFormat($packingList->packing_list_no) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sale Order') }}</th>
                                    <td>
                                        @if($packingList->saleOrder)
                                            <a href="{{ route('saleorder.show', \Crypt::encrypt($packingList->sale_order_id)) }}" class="btn btn-outline-primary btn-sm">
                                                {{ \Auth::user()->saleOrderNumberFormat($packingList->saleOrder->sale_order_no) }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ $packingList->customer->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Pick List') }}</th>
                                    <td>
                                        @if($packingList->pickList)
                                            <a href="{{ route('picklist.show', \Crypt::encrypt($packingList->pick_list_id)) }}" class="btn btn-outline-info btn-sm">
                                                #{{ $packingList->pickList->id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Packing List Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($packingList->packing_list_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Packed By') }}</th>
                                    <td>{{ $packingList->packer->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Box Scanning Workflow -->
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="ti ti-scan"></i> {{ __('Box Scanning Workflow') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info" id="box-status-alert">
                                <strong>{{ __('Current Box:') }}</strong> <span id="current-box-display">{{ __('No box open') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success btn-lg w-100" id="generate-box-btn">
                                <i class="ti ti-box"></i> {{ __('Generate Box Number') }}
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-warning btn-lg w-100" id="close-box-btn" disabled>
                                <i class="ti ti-box-off"></i> {{ __('Close Box') }}
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-danger btn-lg w-100" id="close-packing-list-btn">
                                <i class="ti ti-check"></i> {{ __('Close Packing List') }}
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary btn-lg w-100" id="refresh-items-btn">
                                <i class="ti ti-refresh"></i> {{ __('Refresh Items') }}
                            </button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="scan-part-no" class="form-label">{{ __('Scan Part Number') }}</label>
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control" id="scan-part-no" placeholder="{{ __('Scan or enter part number') }}" autofocus>
                                <button class="btn btn-primary" type="button" id="scan-part-btn">
                                    <i class="ti ti-scan"></i> {{ __('Scan') }}
                                </button>
                            </div>
                            <div id="part-info" class="mt-2" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 id="part-no-display"></h6>
                                        <p class="mb-1"><strong>{{ __('Description:') }}</strong> <span id="part-description"></span></p>
                                        <p class="mb-1"><strong>{{ __('Required Qty:') }}</strong> <span id="part-req-qty"></span></p>
                                        <p class="mb-1"><strong>{{ __('Current Packed:') }}</strong> <span id="part-current-packed"></span></p>
                                        <p class="mb-0"><strong>{{ __('Remaining:') }}</strong> <span id="part-remaining" class="text-danger"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="add-qty" class="form-label">{{ __('Quantity') }}</label>
                            <input type="number" class="form-control form-control-lg" id="add-qty" step="0.01" min="0" value="0" placeholder="0.00">
                            <small class="text-muted">{{ __('Enter quantity to pack') }}</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-success btn-lg w-100" id="add-to-box-btn" disabled>
                                <i class="ti ti-plus"></i> {{ __('Add to Box') }}
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>{{ __('Current Box Items') }}</h6>
                            <div id="current-box-items" class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('#') }}</th>
                                            <th>{{ __('Box No') }}</th>
                                            <th>{{ __('Part No') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Qty') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="current-box-items-body">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">{{ __('No items in current box') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Items - Packing Details (Hidden for now) -->
            <div class="card mt-3" style="display: none;">
                <div class="card-header">
                    <h5>{{ __('All Items - Packing Details') }}</h5>
                    <small class="text-muted">{{ __('View and edit all packed items.') }}</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('packinglist.update', \Crypt::encrypt($packingList->id)) }}" method="POST" id="packinglist-edit-form">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="packing_ref" class="form-label">{{ __('Packing Ref') }}</label>
                                    <input type="text" name="packing_ref" id="packing_ref" class="form-control" value="{{ $packingList->packing_ref }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="packing_list_date" class="form-label">{{ __('Packing List Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="packing_list_date" id="packing_list_date" class="form-control" value="{{ $packingList->packing_list_date ? \Carbon\Carbon::parse($packingList->packing_list_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="packed_by" class="form-label">{{ __('Packed By') }}</label>
                                    <select name="packed_by" id="packed_by" class="form-control select2">
                                        <option value="">{{ __('Select User') }}</option>
                                        @foreach($users as $id => $name)
                                            <option value="{{ $id }}" {{ $packingList->packed_by == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control select2" required>
                                        <option value="draft" {{ $packingList->status == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                        <option value="under_packing" {{ $packingList->status == 'under_packing' ? 'selected' : '' }}>{{ __('Under Packing') }}</option>
                                        <option value="partially_packed" {{ $packingList->status == 'partially_packed' ? 'selected' : '' }}>{{ __('Partially Packed') }}</option>
                                        <option value="packing_completed" {{ in_array($packingList->status, ['packing_completed', 'packed', 'shipped', 'delivered']) ? 'selected' : '' }}>{{ __('Packing Completed') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Box No') }}</th>
                                        <th>{{ __('Part No') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Packed QTY') }}</th>
                                        <th>{{ __('Box L') }}</th>
                                        <th>{{ __('Box W') }}</th>
                                        <th>{{ __('Box H') }}</th>
                                        <th>{{ __('Box Weight') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($packingList->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                <input type="text" name="items[{{ $index }}][box_no]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $item->box_no }}">
                                            </td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][part_no]" value="{{ $item->part_no }}">
                                                {{ $item->part_no }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][description]" value="{{ $item->description ?? '' }}">
                                                {{ $item->description ?? '-' }}
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][packed_qty]" 
                                                       class="form-control form-control-sm" 
                                                       step="0.01" min="0"
                                                       value="{{ $item->packed_qty ?? 0 }}" 
                                                       required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][box_l]" 
                                                       class="form-control form-control-sm" 
                                                       step="0.01" min="0"
                                                       value="{{ $item->box_l ?? '' }}" 
                                                       placeholder="L">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][box_w]" 
                                                       class="form-control form-control-sm" 
                                                       step="0.01" min="0"
                                                       value="{{ $item->box_w ?? '' }}" 
                                                       placeholder="W">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][box_h]" 
                                                       class="form-control form-control-sm" 
                                                       step="0.01" min="0"
                                                       value="{{ $item->box_h ?? '' }}" 
                                                       placeholder="H">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][box_weight]" 
                                                       class="form-control form-control-sm" 
                                                       step="0.01" min="0"
                                                       value="{{ $item->box_weight ?? '' }}" 
                                                       placeholder="Weight">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <a href="{{ route('packinglist.show', \Crypt::encrypt($packingList->id)) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Close Box Modal -->
    <div class="modal fade" id="closeBoxModal" tabindex="-1" aria-labelledby="closeBoxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeBoxModalLabel">{{ __('Close Box Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">{{ __('Enter box measurements before closing the current box.') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal-box-l" class="form-label">{{ __('Box L') }}</label>
                            <input type="number" id="modal-box-l" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modal-box-w" class="form-label">{{ __('Box W') }}</label>
                            <input type="number" id="modal-box-w" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modal-box-h" class="form-label">{{ __('Box H') }}</label>
                            <input type="number" id="modal-box-h" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="modal-box-weight" class="form-label">{{ __('Box Weight') }}</label>
                            <input type="number" id="modal-box-weight" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-warning" id="confirm-close-box-btn">
                        <i class="ti ti-box-off"></i> {{ __('Close Box') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        const packingListId = '{{ \Crypt::encrypt($packingList->id) }}';
        let currentBoxNo = null;
        let currentPartNo = null;
        let closeBoxModalInstance = null;

        $(document).ready(function() {
            $('.select2').select2();
            const closeBoxModalElement = document.getElementById('closeBoxModal');
            closeBoxModalInstance = closeBoxModalElement ? new bootstrap.Modal(closeBoxModalElement) : null;
            
            // Check for existing open box
            checkCurrentBox();

            // Generate Box Number
            $('#generate-box-btn').on('click', function() {
                $.ajax({
                    url: '{{ route("packinglist.generate-box", \Crypt::encrypt($packingList->id)) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            currentBoxNo = response.box_no;
                            updateBoxStatus();
                            showNotification('success', response.message);
                            loadCurrentBoxItems();
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.error || '{{ __("Failed to generate box number") }}');
                    }
                });
            });

            // Scan Part Number
            $('#scan-part-btn, #scan-part-no').on('click keypress', function(e) {
                if (e.type === 'keypress' && e.which !== 13) return;
                e.preventDefault();
                
                const partNo = $('#scan-part-no').val().trim();
                if (!partNo) {
                    showNotification('error', '{{ __("Please enter a part number") }}');
                    return;
                }

                if (!currentBoxNo) {
                    showNotification('error', '{{ __("Please generate a box number first") }}');
                    return;
                }

                $.ajax({
                    url: '{{ route("packinglist.scan-part", \Crypt::encrypt($packingList->id)) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        part_no: partNo
                    },
                    success: function(response) {
                        if (response.success) {
                            currentPartNo = response.part_no;
                            $('#part-no-display').text(response.part_no);
                            $('#part-description').text(response.description || '-');
                            $('#part-req-qty').text(response.req_qty);
                            $('#part-current-packed').text(response.current_packed_qty);
                            $('#part-remaining').text(response.remaining_qty);
                            $('#part-info').show();
                            $('#add-qty').val('0');
                            $('#add-qty').attr('max', response.remaining_qty);
                            $('#add-qty').attr('min', '0');
                            $('#add-to-box-btn').prop('disabled', false);
                            $('#add-qty').focus();
                            $('#add-qty').select();
                        } else {
                            showNotification('error', response.error || '{{ __("Part number not found") }}');
                            $('#part-info').hide();
                            $('#add-to-box-btn').prop('disabled', true);
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.error || '{{ __("Failed to scan part number") }}');
                        $('#part-info').hide();
                        $('#add-to-box-btn').prop('disabled', true);
                    }
                });
            });

            // Add to Box
            $('#add-to-box-btn').on('click', function() {
                const qty = parseFloat($('#add-qty').val()) || 0;
                if (qty <= 0) {
                    showNotification('error', '{{ __("Please enter a quantity greater than 0") }}');
                    return;
                }

                if (!currentPartNo) {
                    showNotification('error', '{{ __("Please scan a part number first") }}');
                    return;
                }

                $.ajax({
                    url: '{{ route("packinglist.add-to-box", \Crypt::encrypt($packingList->id)) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        part_no: currentPartNo,
                        qty: qty
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', response.message);
                            $('#part-current-packed').text(response.current_packed_qty);
                            $('#part-remaining').text(response.remaining_qty);
                            $('#add-qty').val('0');
                            $('#add-qty').attr('max', response.remaining_qty);
                            loadCurrentBoxItems();
                            // Re-focus on qty input for next entry
                            $('#add-qty').focus();
                            $('#add-qty').select();
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.error || '{{ __("Failed to add item to box") }}');
                    }
                });
            });

            // Close Box
            $('#close-box-btn').on('click', function() {
                if (!currentBoxNo) {
                    showNotification('error', '{{ __("No box is currently open.") }}');
                    return;
                }

                // Reset modal fields each time the user opens close dialog.
                $('#modal-box-l, #modal-box-w, #modal-box-h, #modal-box-weight').val('');
                if (closeBoxModalInstance) {
                    closeBoxModalInstance.show();
                }
            });

            // Confirm close box from modal
            $('#confirm-close-box-btn').on('click', function() {
                $.ajax({
                    url: '{{ route("packinglist.close-box", \Crypt::encrypt($packingList->id)) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        box_l: $('#modal-box-l').val(),
                        box_w: $('#modal-box-w').val(),
                        box_h: $('#modal-box-h').val(),
                        box_weight: $('#modal-box-weight').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            if (closeBoxModalInstance) {
                                closeBoxModalInstance.hide();
                            }
                            showNotification('success', response.message);
                            currentBoxNo = null;
                            updateBoxStatus();
                            $('#close-box-btn').prop('disabled', true);
                            $('#part-info').hide();
                            $('#scan-part-no').val('');
                            $('#add-qty').val('');
                            $('#add-to-box-btn').prop('disabled', true);
                            loadCurrentBoxItems();
                            // Refresh the page after a short delay to show updated items
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.error || '{{ __("Failed to close box") }}');
                    }
                });
            });

            // Close Packing List
            $('#close-packing-list-btn').on('click', function() {
                const submitClosePackingList = function() {
                    $.ajax({
                        url: '{{ route("packinglist.close-packing-list", \Crypt::encrypt($packingList->id)) }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                showNotification('success', response.message);
                                setTimeout(function() {
                                    window.location.href = response.redirect_url;
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            showNotification('error', xhr.responseJSON?.error || '{{ __("Failed to close packing list") }}');
                        }
                    });
                };

                Swal.fire({
                    icon: 'question',
                    title: '{{ __("Close Packing List?") }}',
                    text: '{{ __("Are you sure you want to close this packing list? This will set the status to Packing Completed.") }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Yes, close it") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    reverseButtons: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        submitClosePackingList();
                    }
                });
            });

            // Enter key on part number input
            $('#scan-part-no').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#scan-part-btn').click();
                }
            });

            // Enter key on quantity input
            $('#add-qty').on('keypress', function(e) {
                if (e.which === 13 && !$('#add-to-box-btn').prop('disabled')) {
                    $('#add-to-box-btn').click();
                }
            });

            // Refresh items table
            $('#refresh-items-btn').on('click', function() {
                location.reload();
            });
        });

        function checkCurrentBox() {
            // Try to get current box from session
            $.ajax({
                url: '{{ route("packinglist.current-box-items", \Crypt::encrypt($packingList->id)) }}',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.box_no) {
                        currentBoxNo = response.box_no;
                        updateBoxStatus();
                    }
                    loadCurrentBoxItems();
                }
            });
        }

        function updateBoxStatus() {
            if (currentBoxNo) {
                $('#current-box-display').text(currentBoxNo).addClass('text-success');
                $('#box-status-alert').removeClass('alert-info').addClass('alert-success');
                // When a box is open, user must close it before generating a new one
                $('#generate-box-btn').prop('disabled', true);
                $('#close-box-btn').prop('disabled', false);
            } else {
                $('#current-box-display').text('{{ __("No box open") }}').removeClass('text-success');
                $('#box-status-alert').removeClass('alert-success').addClass('alert-info');
                // No open box: allow generating a new box, but nothing to close
                $('#generate-box-btn').prop('disabled', false);
                $('#close-box-btn').prop('disabled', true);
            }
        }

        function loadCurrentBoxItems() {
            if (!currentBoxNo) {
                $('#current-box-items-body').html('<tr><td colspan="5" class="text-center text-muted">{{ __("No items in current box") }}</td></tr>');
                return;
            }

            $.ajax({
                url: '{{ route("packinglist.current-box-items", \Crypt::encrypt($packingList->id)) }}',
                method: 'GET',
                success: function(response) {
                    const tbodyBox = $('#current-box-items-body');
                    tbodyBox.empty();

                    if (response.success && response.items && response.items.length > 0) {
                        let itemIndex = 1;
                        response.items.forEach(function(item) {
                            tbodyBox.append(
                                '<tr>' +
                                '<td>' + itemIndex + '</td>' +
                                '<td>' + (currentBoxNo || '-') + '</td>' +
                                '<td>' + (item.part_no || '-') + '</td>' +
                                '<td>' + (item.description || '-') + '</td>' +
                                '<td>' + parseFloat(item.packed_qty || 0).toFixed(2) + '</td>' +
                                '</tr>'
                            );
                            itemIndex++;
                        });
                    } else {
                        tbodyBox.append('<tr><td colspan="5" class="text-center text-muted">{{ __("No items in current box") }}</td></tr>');
                    }
                },
                error: function() {
                    // Fallback to reading from table
                    const tbody = $('#items-table tbody');
                    let currentBoxItems = [];
                    
                    tbody.find('tr').each(function() {
                        const boxNo = $(this).find('input[name*="[box_no]"]').val();
                        if (boxNo && boxNo === currentBoxNo) {
                            const partNo = $(this).find('td').eq(2).text().trim();
                            const description = $(this).find('td').eq(3).text().trim();
                            const qty = $(this).find('input[name*="[packed_qty]"]').val();
                            currentBoxItems.push({part_no: partNo, description: description, qty: qty});
                        }
                    });

                    const tbodyBox = $('#current-box-items-body');
                    tbodyBox.empty();

                    if (currentBoxItems.length === 0) {
                        tbodyBox.append('<tr><td colspan="5" class="text-center text-muted">{{ __("No items in current box") }}</td></tr>');
                    } else {
                        let itemIndex = 1;
                        currentBoxItems.forEach(function(item) {
                            tbodyBox.append(
                                '<tr>' +
                                '<td>' + itemIndex + '</td>' +
                                '<td>' + (currentBoxNo || '-') + '</td>' +
                                '<td>' + item.part_no + '</td>' +
                                '<td>' + (item.description || '-') + '</td>' +
                                '<td>' + parseFloat(item.qty).toFixed(2) + '</td>' +
                                '</tr>'
                            );
                            itemIndex++;
                        });
                    }
                }
            });
        }

        function showNotification(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            
            $('.card-body').first().prepend(alert);
            
            setTimeout(function() {
                alert.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }

        // Refresh current box items periodically
        setInterval(function() {
            if (currentBoxNo) {
                loadCurrentBoxItems();
            }
        }, 2000);
    </script>
@endpush
