@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Pick List') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('picklist.index') }}">{{ __('Pick Lists') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Pick List Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table">
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
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Pick List Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($pickList->pick_list_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Assigned To') }}</th>
                                    <td>{{ $pickList->assignedUser->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Picked By') }}</th>
                                    <td>{{ $pickList->picker->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ __('Items - Enter Picked Quantities') }}</h5>
                            <small class="text-muted">{{ __('Enter the quantity you picked for each item. This will update the sale order packed quantity.') }}</small>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="form-group mb-0">
                                <label for="filter-bin-location" class="form-label mb-0 me-2" style="font-size: 0.875rem; font-weight: 500;">{{ __('Filter by Bin:') }}</label>
                                <select id="filter-bin-location" class="form-control form-control-sm select2-filter-bin" style="min-width: 200px;">
                                    <option value="">{{ __('All Bin Locations') }}</option>
                                    @php
                                        $uniqueBinLocations = $pickList->items->pluck('bin_location')->filter()->unique()->sort()->values();
                                    @endphp
                                    @foreach($uniqueBinLocations as $binLocation)
                                        <option value="{{ $binLocation }}">{{ $binLocation }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="auto-fill-qty-btn">
                                <i class="ti ti-check"></i> {{ __('Auto-Fill Qty for Filtered') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>{{ __('Please fix the following errors:') }}</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <form action="{{ route('picklist.update', \Crypt::encrypt($pickList->id)) }}" method="POST" id="picklist-edit-form">
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
                                        <th>{{ __('Picked QTY') }} <span class="text-danger">*</span></th>
                                        <th>{{ __('Tick') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sortedItems = $pickList->items->sortBy(function($item) {
                                            $bin = $item->bin_location ?? '';
                                            return $bin === '' ? "\xFF\xFF\xFF" : $bin;
                                        }, SORT_NATURAL)->values();
                                    @endphp
                                    @foreach($sortedItems as $index => $item)
                                        <tr class="item-row" data-bin-location="{{ $item->bin_location ?? '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $index }}][bin_location]" value="{{ $item->bin_location ?? '' }}">
                                                <span class="text-muted">{{ $item->bin_location ?: '-' }}</span>
                                            </td>
                                            <td>{{ $item->part_no }}</td>
                                            <td>{{ $item->description ?? '-' }}</td>
                                            <td class="req-qty-display" data-req-qty="{{ $item->req_qty }}">{{ number_format($item->req_qty, 2) }}</td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][picked_qty]" 
                                                       class="form-control form-control-sm picked-qty-input" 
                                                       step="0.01" min="0" max="{{ $item->req_qty }}"
                                                       value="{{ $item->picked_qty ?? 0 }}" 
                                                       required
                                                       data-item-id="{{ $item->id }}"
                                                       data-part-no="{{ $item->part_no }}"
                                                       data-req-qty="{{ $item->req_qty }}">
                                                <small class="text-muted">{{ __('Max: ') }}{{ number_format($item->req_qty, 2) }}</small>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="items[{{ $index }}][tick]" value="1" class="item-tick" 
                                                       {{ $item->tick ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="packing_ref" class="form-label">{{ __('Packing Ref') }}</label>
                                    <input type="text" name="packing_ref" id="packing_ref" class="form-control" value="{{ $pickList->packing_ref }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pick_list_date" class="form-label">{{ __('Pick List Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="pick_list_date" id="pick_list_date" class="form-control" value="{{ $pickList->pick_list_date ? \Carbon\Carbon::parse($pickList->pick_list_date)->format('Y-m-d') : date('Y-m-d') }}" required>
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
                                    <a href="{{ route('picklist.show', \Crypt::encrypt($pickList->id)) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Save Picking') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<style>
    .select2-filter-bin {
        font-size: 0.875rem;
    }
    .select2-filter-bin + .select2-container .select2-selection--single {
        height: 31px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 0.25rem 0.5rem;
        line-height: 1.5;
        font-size: 0.875rem;
    }
    .select2-filter-bin + .select2-container .select2-selection--single:focus,
    .select2-filter-bin + .select2-container .select2-selection--single:hover {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .select2-filter-bin + .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 21px;
        padding-left: 0;
        font-size: 0.875rem;
    }
    .select2-filter-bin + .select2-container .select2-selection--single .select2-selection__arrow {
        height: 29px;
        top: 1px;
        right: 5px;
    }
    .select2-filter-bin + .select2-container {
        min-width: 200px;
    }
    .select2-filter-bin + .select2-container .select2-dropdown {
        font-size: 0.875rem;
    }
    .select2-filter-bin + .select2-container .select2-results__option {
        padding: 6px 12px;
    }
    .select2-filter-bin + .select2-container .select2-results__option--highlighted {
        background-color: #007bff;
        color: white;
    }
</style>
<script>
    $(document).ready(function() {
        // Initialize select2 for filter dropdown and form dropdowns
        $('#filter-bin-location').select2({
            width: 'resolve',
            placeholder: '{{ __('All Bin Locations') }}',
            allowClear: true,
            dropdownAutoWidth: true,
            minimumResultsForSearch: 5
        });
        
        $('.select2').select2();
        
        // Filter by Bin Location functionality
        $('#filter-bin-location').on('change', function() {
            const filterValue = $(this).val();
            const rows = $('#picklist-edit-form tbody tr.item-row');
            
            if (!filterValue || filterValue === '') {
                // Show all rows if no filter is selected
                rows.show();
            } else {
                // Filter rows based on selected bin location
                rows.each(function() {
                    const rowBinLocation = $(this).attr('data-bin-location') || '';
                    if (rowBinLocation === filterValue) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Auto-fill picked_qty for visible (filtered) items
        $('#auto-fill-qty-btn').on('click', function() {
            const visibleRows = $('#picklist-edit-form tbody tr.item-row:visible');

            const runAutoFill = function () {
                let filledCount = 0;
                visibleRows.each(function() {
                    const pickedQtyInput = $(this).find('.picked-qty-input');
                    const reqQty = parseFloat(pickedQtyInput.data('req-qty')) || 0;
                    const currentRawValue = (pickedQtyInput.val() ?? '').toString().trim();
                    const currentQty = currentRawValue === '' ? 0 : (parseFloat(currentRawValue) || 0);

                    // Do not overwrite user-entered values; only fill empty/zero inputs.
                    if (reqQty > 0 && currentQty <= 0) {
                        pickedQtyInput.val(reqQty);
                        // Also check the tick checkbox
                        $(this).find('.item-tick').prop('checked', true);
                        filledCount++;
                    }
                });

                if (filledCount > 0) {
                    show_toastr('success', '{{ __("Auto-filled :count item(s) that had empty/zero picked quantity.") }}'.replace(':count', filledCount));
                } else {
                    show_toastr('warning', '{{ __("No empty/zero picked quantity items were available to auto-fill.") }}');
                }
            };
            
            if (visibleRows.length === 0) {
                if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __("No items visible") }}',
                        text: '{{ __("Please select a bin location filter first.") }}',
                        confirmButtonText: '{{ __("OK") }}'
                    });
                } else {
                    alert('{{ __("No items visible. Please select a bin location filter first.") }}');
                }
                return;
            }

            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                Swal.fire({
                    icon: 'question',
                    title: '{{ __("Confirm Auto Fill") }}',
                    text: '{{ __("This will set picked quantity equal to required quantity only for items with empty/zero picked quantity. Continue?") }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Yes, continue") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    reverseButtons: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        runAutoFill();
                    }
                });
            } else if (confirm('{{ __("This will set picked quantity equal to required quantity only for items with empty/zero picked quantity. Continue?") }}')) {
                runAutoFill();
            }
        });
        
        // Validate picked quantity doesn't exceed req_qty
        $('.picked-qty-input').on('blur', function() {
            const pickedQty = parseFloat($(this).val()) || 0;
            const maxQty = parseFloat($(this).attr('max')) || 0;
            
            if (pickedQty > maxQty) {
                alert('{{ __("Picked quantity cannot exceed required quantity.") }}');
                $(this).val(maxQty);
            }
        });
    });
</script>
@endpush
