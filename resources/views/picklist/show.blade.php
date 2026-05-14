@extends('layouts.admin')
@section('page-title')
    {{ __('Pick List Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('picklist.index') }}">{{ __('Pick Lists') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create sale order')
            @if (!$pickList->packingList)
            <a href="{{ route('picklist.edit', \Crypt::encrypt($pickList->id)) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i> {{ __('Edit Picking') }}
            </a>
            @endif
            <a href="{{ route('picklist.status-logs', \Crypt::encrypt($pickList->id)) }}" class="btn btn-sm btn-dark ms-2">
                <i class="ti ti-history"></i> {{ __('Status Logs') }}
            </a>
            @if (!$pickList->packingList)
                <button type="button"
                   class="btn btn-sm btn-success ms-2"
                   data-bs-toggle="modal"
                   data-bs-target="#convertToPackingListModal">
                    <i class="ti ti-package"></i> {{ __('Convert to Packing List') }}
                </button>
            @else
                <a href="{{ route('packinglist.show', \Crypt::encrypt($pickList->packingList->id)) }}" class="btn btn-sm btn-info ms-2">
                    <i class="ti ti-package"></i> {{ __('View Packing List') }}
                </a>
            @endif
            <a href="{{ route('saleorder.show', \Crypt::encrypt($pickList->sales_order_id)) }}" class="btn btn-sm btn-info ms-2">
                <i class="ti ti-arrow-left"></i> {{ __('Back to Sale Order') }}
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Pick List Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table" id="picking-assignment-section">
                                <tr>
                                    <th>{{ __('Pick List ID') }}</th>
                                    <td>#{{ $pickList->id }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sale Order') }}</th>
                                    <td>
                                        <a href="{{ route('saleorder.show', \Crypt::encrypt($pickList->sales_order_id)) }}" class="btn btn-outline-primary btn-sm">
                                            {{ \Auth::user()->saleOrderNumberFormat($pickList->saleOrder->sale_order_no ?? 0) }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ $pickList->customer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Pick List Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($pickList->pick_list_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        <form action="{{ route('picklist.update-status', \Crypt::encrypt($pickList->id)) }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto" style="max-width: 200px;">
                                                <option value="draft" {{ ($pickList->status ?? 'draft') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                                <option value="under_picking" {{ ($pickList->status ?? '') === 'under_picking' ? 'selected' : '' }}>{{ __('Under Picking') }}</option>
                                                <option value="partially_picked" {{ ($pickList->status ?? '') === 'partially_picked' ? 'selected' : '' }}>{{ __('Partially Picked') }}</option>
                                                <option value="picking_completed" {{ ($pickList->status ?? '') === 'picking_completed' ? 'selected' : '' }}>{{ __('Picking Completed') }}</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Update') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Packing Ref') }}</th>
                                    <td>{{ $pickList->packing_ref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Assigned To') }}</th>
                                    <td>{{ $pickList->assignedUser->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Picking Assignment Status') }}</th>
                                    <td>
                                        @if($pickList->assigned_to)
                                            <span class="badge bg-success">{{ __('Assigned') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Not Assigned') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($pickList->assign_note)
                                <tr>
                                    <th>{{ __('Assign Note') }}</th>
                                    <td>{{ $pickList->assign_note }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>{{ __('Picked By') }}</th>
                                    <td>{{ $pickList->picker->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created By') }}</th>
                                    <td>{{ $pickList->creator->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created At') }}</th>
                                    <td>{{ Auth::user()->dateFormat($pickList->created_at) }}</td>
                                </tr>
                                @if(!$pickList->packingList)
                                <tr>
                                    <th>{{ __('Assign Picker') }}</th>
                                    <td>
                                        <form action="{{ route('picklist.assign', \Crypt::encrypt($pickList->id)) }}" method="POST" class="d-inline-flex align-items-start gap-2 flex-wrap">
                                            @csrf
                                            <div class="d-flex flex-column flex-md-row gap-2 w-100">
                                                <select name="assigned_to" class="form-select form-select-sm" style="min-width: 200px; max-width: 260px;">
                                                    @foreach(($assignUsers ?? []) as $userId => $userName)
                                                        <option value="{{ $userId }}" {{ $pickList->assigned_to == $userId ? 'selected' : '' }}>
                                                            {{ $userName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <textarea name="assign_note" class="form-control form-control-sm" rows="2" placeholder="{{ __('Add note for picker (optional)') }}">{{ old('assign_note', $pickList->assign_note) }}</textarea>
                                                <button type="submit" class="btn btn-sm btn-outline-primary align-self-start">{{ __('Assign') }}</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>{{ __('Items') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('picklist.update', \Crypt::encrypt($pickList->id)) }}" method="POST" id="picklist-form">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Bin Location') }}</th>
                                        <th>{{ __('Part No') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('REQ QTY') }}</th>
                                        <th>{{ __('Tick') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pickList->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $index }}][bin_location]" value="{{ $item->bin_location ?? '' }}">
                                                <span class="text-muted">{{ $item->bin_location ?: '-' }}</span>
                                            </td>
                                            <td>{{ $item->part_no }}</td>
                                            <td>{{ $item->description ?? '-' }}</td>
                                            <td>{{ number_format($item->req_qty, 2) }}</td>
                                            <td>{{ number_format($item->picked_qty ?? 0, 2) }}</td>
                                            <td class="text-center">
                                                <input type="checkbox" name="items[{{ $index }}][tick]" value="1" class="item-tick" 
                                                       {{ $item->tick ? 'checked' : '' }} 
                                                       data-item-id="{{ $item->id }}"
                                                       data-picklist-id="{{ \Crypt::encrypt($pickList->id) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="packing_ref" class="form-label">{{ __('Packing Ref') }}</label>
                                    <input type="text" name="packing_ref" id="packing_ref" class="form-control" value="{{ $pickList->packing_ref }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pick_list_date" class="form-label">{{ __('Pick List Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="pick_list_date" id="pick_list_date" class="form-control" value="{{ $pickList->pick_list_date ? \Carbon\Carbon::parse($pickList->pick_list_date)->format('Y-m-d') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Picked By') }}</label>
                                    <input type="text" class="form-control" value="{{ \Auth::user()->name }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <a href="{{ route('picklist.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <a href="{{ route('picklist.edit', \Crypt::encrypt($pickList->id)) }}" class="btn btn-warning">{{ __('Edit Picking') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (!$pickList->packingList)
    <div class="modal fade" id="convertToPackingListModal" tabindex="-1" aria-labelledby="convertToPackingListModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('picklist.convert-to-packinglist', \Crypt::encrypt($pickList->id)) }}" method="POST" id="convert-to-packinglist-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="convertToPackingListModalLabel">{{ __('Convert to Packing List') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="packed_by" class="form-label">{{ __('Assign Packing To') }} <span class="text-danger">*</span></label>
                            <select name="packed_by" id="packed_by" class="form-select" required>
                                <option value="">{{ __('Select User') }}</option>
                                @foreach(($assignUsers ?? []) as $userId => $userName)
                                    <option value="{{ $userId }}" {{ (string) old('packed_by', $pickList->assigned_to) === (string) $userId ? 'selected' : '' }}>
                                        {{ $userName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted">{{ __('This selected user will be saved as the packing assignee for the new packing list.') }}</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('Convert') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('script-page')
<style>
    .swal2-over-picklist-modal {
        z-index: 20000 !important;
    }
</style>
<script>
    $(document).ready(function() {
        $('.select2').select2();
        
        $('#convert-to-packinglist-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const packedBy = $('#packed_by').val();
            if (!packedBy) {
                show_toastr('error', '{{ __("Please select a packing user before converting.") }}');
                return;
            }

            Swal.fire({
                icon: 'question',
                title: '{{ __("Convert to Packing List?") }}',
                text: '{{ __("Are you sure you want to convert this pick list to a packing list?") }}',
                customClass: {
                    container: 'swal2-over-picklist-modal'
                },
                showCancelButton: true,
                confirmButtonText: '{{ __("Yes, convert") }}',
                cancelButtonText: '{{ __("Cancel") }}',
                reverseButtons: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
        
        // Handle tick checkbox change via AJAX
        $('.item-tick').on('change', function() {
            const itemId = $(this).data('item-id');
            const pickListId = $(this).data('picklist-id');
            const isChecked = $(this).is(':checked') ? 1 : 0;
            
            $.ajax({
                url: '{{ route("picklist.item-tick", ":id") }}'.replace(':id', pickListId),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    item_id: itemId,
                    tick: isChecked
                },
                success: function(response) {
                    if (response.success) {
                        // Optionally show a success message
                    }
                },
                error: function(xhr) {
                    alert('{{ __("Failed to update item. Please try again.") }}');
                    // Revert checkbox
                    $(this).prop('checked', !isChecked);
                }.bind(this)
            });
        });
    });
</script>
@endpush
