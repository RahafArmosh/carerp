@extends('layouts.admin')
@section('page-title')
    {{ __('Edit ASN') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('asn.index') }}">{{ __('ASN') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('asn.update', $asn->id) }}" method="POST" id="asn-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_page" value="{{ request('page', 1) }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="asn_no" class="form-label">{{ __('ASN No') }}</label>
                                    <input type="text" class="form-control" value="{{ \Auth::user()->asnNumberFormat($asn->asn_no) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id" class="form-label">{{ __('Supplier Name') }}</label>
                                    <select name="supplier_id" id="supplier_id" class="form-control select2" onchange="loadSupplierData()">
                                        <option value="">{{ __('Select Supplier') }}</option>
                                        @foreach($suppliers as $id => $name)
                                            <option value="{{ $id }}" {{ $asn->supplier_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_code" class="form-label">{{ __('Supplier Code') }}</label>
                                    <input type="text" name="supplier_code" id="supplier_code" class="form-control" value="{{ $asn->supplier_code }}" placeholder="{{ __('Auto-filled from supplier') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="asn_date" class="form-label">{{ __('ASN Date') }}</label>
                                    <input type="date" name="asn_date" id="asn_date" class="form-control" value="{{ $asn->asn_date ? \Carbon\Carbon::parse($asn->asn_date)->format('Y-m-d') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">{{ __('Status') }}</label>
                                    <select name="status" id="status" class="form-control select2">
                                        <option value="created" {{ $asn->status == 'created' ? 'selected' : '' }}>{{ __('Created') }}</option>
                                        <option value="sent" {{ $asn->status == 'sent' ? 'selected' : '' }}>{{ __('Sent') }}</option>
                                        <option value="partially_received" {{ $asn->status == 'partially_received' ? 'selected' : '' }}>{{ __('Partially Received') }}</option>
                                        <option value="fully_received" {{ $asn->status == 'fully_received' ? 'selected' : '' }}>{{ __('Fully Received') }}</option>
                                        <option value="manually_received" {{ $asn->status == 'manually_received' ? 'selected' : '' }}>{{ __('Manually Received') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_inv_no" class="form-label">{{ __('Supplier Inv No') }}</label>
                                    <input type="text" name="supplier_inv_no" id="supplier_inv_no" class="form-control" value="{{ $asn->supplier_inv_no }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="container_no" class="form-label">{{ __('Container No') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="container_no" id="container_no" class="form-control" value="{{ $asn->container_no }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dec_no" class="form-label">{{ __('DEC NO') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="dec_no" id="dec_no" class="form-control" value="{{ $asn->dec_no }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="boe_number" class="form-label">{{ __('BOE Number') }}</label>
                                    <input type="text" name="boe_number" id="boe_number" class="form-control" value="{{ $asn->boe_number }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dec_date" class="form-label">{{ __('DEC DATE') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="dec_date" id="dec_date" class="form-control" value="{{ $asn->dec_date ? \Carbon\Carbon::parse($asn->dec_date)->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control select2">
                                        <option value="">{{ __('Select Warehouse') }}</option>
                                        @foreach($warehouses as $id => $name)
                                            <option value="{{ $id }}" {{ $asn->warehouse_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control select2" onchange="updateExchangeRate()">
                                        <option value="">{{ __('Select Currency') }}</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate ?? 1 }}" {{ $asn->currency_id == $currency->id ? 'selected' : '' }}>{{ $currency->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                    <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.000001" min="0" value="{{ $asn->exchange_rate ?? 1.0 }}" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">{{ __('Items') }}</h5>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="form-group mb-0">
                                    <label for="filter-box-no" class="form-label mb-0 me-2" style="font-size: 0.875rem; font-weight: 500;">{{ __('Filter by Box:') }}</label>
                                    <select id="filter-box-no" class="form-control form-control-sm select2-filter-box" style="min-width: 180px;">
                                        <option value="">{{ __('All Box Numbers') }}</option>
                                        @foreach(($allBoxNos ?? collect()) as $boxNo)
                                            <option value="{{ $boxNo }}">{{ $boxNo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="create-grn-btn" data-bs-toggle="modal" data-bs-target="#createGrnModal">
                                    <i class="ti ti-file-plus"></i> {{ __('Create GRN from Selected') }}
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                    <i class="ti ti-plus"></i> {{ __('Add Item') }}
                                </button>
                            </div>
                        </div>
                        @if(!empty($isLargeItemSet))
                            <div class="alert alert-info py-2">
                                {{ __('Large ASN optimization is enabled for faster loading. PRO selection is read-only in this view.') }}
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 50px;">
                                            <input type="checkbox" id="select-all-items" title="{{ __('Select all eligible lines on this ASN (all pages; excludes items already assigned to GRN)') }}">
                                        </th>
                                        <th style="min-width: 100px;">{{ __('Box No') }}</th>
                                        <th style="min-width: 130px;">{{ __('Supplier PO No') }}</th>
                                        <th style="min-width: 130px;">{{ __('Our PRO No') }}</th>
                                        <th style="min-width: 110px;">{{ __('Order Ref') }}</th>
                                        <th style="min-width: 145px;">{{ __('Part No') }}</th>
                                        <th style="min-width: 260px;">{{ __('Description') }}</th>
                                        <th style="min-width: 90px;">{{ __('QTY') }}</th>
                                        <th style="min-width: 105px;">{{ __('Received QTY') }}</th>
                                        <th style="min-width: 105px;">{{ __('Discrepancy') }}</th>
                                        <th style="min-width: 115px;">{{ __('Unit Price') }}</th>
                                        <th style="min-width: 110px;">{{ __('Total Price') }}</th>
                                        <th style="min-width: 110px;">{{ __('Unit Weight') }}</th>
                                        <th style="min-width: 110px;">{{ __('Total Weight') }}</th>
                                        <th style="min-width: 120px;">{{ __('HS Code') }}</th>
                                        <th style="min-width: 125px;">{{ __('Container No') }}</th>
                                        <th style="min-width: 100px;">{{ __('DEC NO') }}</th>
                                        <th style="min-width: 115px;">{{ __('DEC DATE') }}</th>
                                        <th style="min-width: 120px;">{{ __('Origin') }}</th>
                                        <th style="min-width: 60px;">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="items-container">
                                    @foreach(($asnItems ?? collect()) as $index => $item)
                                        @php
                                            $isAssigned = isset($assignedItemIds) && in_array($item->id, $assignedItemIds);
                                            $assignedInfo = $assignedItemsInfo[$item->id] ?? null;
                                            $rowIndex = (int) (($asnItems->firstItem() ?? 1) - 1 + $index);
                                        @endphp
                                        <tr class="item-row {{ $isAssigned ? 'table-warning' : '' }}" data-item-id="{{ $item->id }}">
                                            <td class="text-center">
                                                @if($isAssigned)
                                                    <input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $item->id }}" data-item-id="{{ $item->id }}" disabled title="{{ __('This item is already assigned to GRN(s): ') . implode(', ', $assignedInfo['grn_numbers'] ?? []) }}">
                                                    <br><small class="text-danger" style="font-size: 0.7rem;">{{ __('Assigned') }}</small>
                                                    @if($assignedInfo && !empty($assignedInfo['grn_numbers']))
                                                        <br><small class="text-muted" style="font-size: 0.65rem;" title="{{ __('GRN Numbers') }}">{{ implode(', ', $assignedInfo['grn_numbers']) }}</small>
                                                    @endif
                                                @else
                                                    <input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $item->id }}" data-item-id="{{ $item->id }}">
                                                @endif
                                            </td>
                                            <td>
                                                <input type="hidden" name="items[{{ $rowIndex }}][id]" value="{{ $item->id }}">
                                                <input type="text" name="items[{{ $rowIndex }}][box_no]" class="form-control form-control-sm box-no-input" value="{{ $item->box_no }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][supplier_po_no]" class="form-control form-control-sm" value="{{ $item->supplier_po_no }}">
                                            </td>
                                            <td>
                                                @if(!empty($isLargeItemSet))
                                                    <input type="hidden" name="items[{{ $rowIndex }}][our_pro_id]" value="{{ $item->our_pro_id }}">
                                                    <input type="text"
                                                        class="form-control form-control-sm"
                                                        value="{{ $proNamesById[$item->our_pro_id] ?? ($item->our_pro_no ?? '-') }}"
                                                        readonly
                                                        title="{{ __('Large ASN mode: PRO dropdown is read-only for faster loading.') }}">
                                                @else
                                                    <select name="items[{{ $rowIndex }}][our_pro_id]" class="form-control form-control-sm select2-item">
                                                        <option value="">{{ __('Select PRO') }}</option>
                                                        @foreach($pros as $id => $name)
                                                            <option value="{{ $id }}" {{ $item->our_pro_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][order_ref]" class="form-control form-control-sm" value="{{ $item->order_ref }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][part_no]" class="form-control form-control-sm" value="{{ $item->part_no }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][description]" class="form-control form-control-sm" value="{{ $item->description }}">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $rowIndex }}][qty]" class="form-control form-control-sm qty-input" step="0.01" min="0" value="{{ $item->qty }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $rowIndex }}][received_qty]" class="form-control form-control-sm received-qty-input" step="0.01" min="0" value="{{ $item->received_qty }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm discrepancy-input" readonly value="{{ number_format($item->discrepancy, 2) }}">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $rowIndex }}][unit_price]" class="form-control form-control-sm unit-price" step="0.01" min="0" value="{{ $item->unit_price }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm total-price" readonly value="{{ number_format($item->total_price, 2) }}">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $rowIndex }}][unit_weight]" class="form-control form-control-sm unit-weight" step="0.01" min="0" value="{{ $item->unit_weight }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm total-weight" readonly value="{{ number_format($item->total_weight, 2) }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][hs_code]" class="form-control form-control-sm" value="{{ $item->hs_code }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][container_no]" class="form-control form-control-sm" value="{{ $item->container_no }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][dec_no]" class="form-control form-control-sm" value="{{ $item->dec_no }}">
                                            </td>
                                            <td>
                                                <input type="date" name="items[{{ $rowIndex }}][dec_date]" class="form-control form-control-sm" value="{{ $item->dec_date ? \Carbon\Carbon::parse($item->dec_date)->format('Y-m-d') : '' }}">
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $rowIndex }}][origin]" class="form-control form-control-sm" value="{{ $item->origin }}">
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm remove-item {{ !empty($hasConvertedItems) ? 'disabled' : '' }}"
                                                    {{ $loop->first ? 'style="display:none;"' : '' }}
                                                    {{ !empty($hasConvertedItems) ? 'disabled' : '' }}
                                                    title="{{ !empty($hasConvertedItems) ? __('Cannot delete items after ASN is converted to inventory/bill.') : '' }}"
                                                >
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(isset($asnItems) && method_exists($asnItems, 'links'))
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    {{ __('Showing :from to :to of :total items', ['from' => $asnItems->firstItem() ?? 0, 'to' => $asnItems->lastItem() ?? 0, 'total' => $asnItems->total() ?? 0]) }}
                                </small>
                                <div>
                                    {{ $asnItems->withQueryString()->links() }}
                                </div>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="text-end">
                                    <a href="{{ route('asn.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create GRN Modal -->
    <div class="modal fade" id="createGrnModal" tabindex="-1" role="dialog" aria-labelledby="createGrnModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGrnModalLabel">{{ __('Create GRN from Selected Items') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="create-grn-form" action="{{ route('asn.create-grn', $asn->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="assigned_user_id" class="form-label">{{ __('Assign to User') }} <span class="text-danger">*</span></label>
                            <select name="assigned_user_id" id="assigned_user_id" class="form-control select2" required>
                                <option value="">{{ __('Select User') }}</option>
                                @foreach($users as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="grn_date" class="form-label">{{ __('GRN Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="grn_date" id="grn_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="grn_notes" class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" id="grn_notes" class="form-control" rows="3" placeholder="{{ __('Optional notes for GRN') }}"></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> {{ __('Selected items will be used to create the GRN. Make sure to select at least one item.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create GRN') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<style>
    #items-table {
        font-size: 0.875rem;
        table-layout: auto;
        width: 100%;
    }
    #items-table th {
        font-weight: 600;
        font-size: 0.8rem;
        white-space: nowrap;
        vertical-align: middle;
        background-color: #f8f9fa;
    }
    #items-table td {
        vertical-align: middle;
        padding: 6px 8px;
        overflow: visible;
    }
    #items-table .form-control-sm {
        font-size: 0.875rem;
        padding: 0.35rem 0.5rem;
        border: 1px solid #ced4da;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    #items-table input[type="text"].form-control-sm,
    #items-table input[type="number"].form-control-sm {
        min-width: 3em;
    }
    #items-table .box-no-input {
        width: auto;
        display: inline-block;
    }
    #items-table .form-control-sm:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    #items-table .select2-container--default .select2-selection--single {
        height: 31px;
        border: 1px solid #ced4da;
    }
    #items-table .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 31px;
        font-size: 0.875rem;
    }
    #items-table .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 29px;
    }
    #items-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    #items-table .remove-item {
        padding: 0.25rem 0.5rem;
    }
    /* Filter Box Dropdown Styles */
    .select2-filter-box {
        font-size: 0.875rem;
    }
    .select2-filter-box .select2-selection {
        height: 31px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-filter-box .select2-selection__rendered {
        line-height: 31px;
        padding-left: 8px;
        font-size: 0.875rem;
        color: #495057;
    }
    .select2-filter-box .select2-selection__arrow {
        height: 29px;
        right: 8px;
    }
    .select2-filter-box.select2-container--focus .select2-selection,
    .select2-filter-box.select2-container--open .select2-selection {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .select2-filter-box + .select2-container {
        min-width: 180px;
    }
    .select2-filter-box + .select2-container .select2-dropdown {
        font-size: 0.875rem;
    }
    .select2-filter-box + .select2-container .select2-results__option {
        padding: 6px 12px;
    }
    .select2-filter-box + .select2-container .select2-results__option--highlighted {
        background-color: #007bff;
        color: white;
    }
    .swal2-over-grn-modal {
        z-index: 20000 !important;
    }
</style>
<script>
    const allSelectableAsnItemIds = @json($allSelectableItemIds ?? []);
    const asnEditSelectAllStorageKey = 'asn_edit_select_all_{{ $asn->id }}';

    function isSelectAllAsnMode() {
        try {
            return sessionStorage.getItem(asnEditSelectAllStorageKey) === '1';
        } catch (e) {
            return false;
        }
    }

    function setSelectAllAsnMode(active) {
        try {
            if (active) {
                sessionStorage.setItem(asnEditSelectAllStorageKey, '1');
            } else {
                sessionStorage.removeItem(asnEditSelectAllStorageKey);
            }
        } catch (e) {}
    }

    function applyVisibleCheckboxesFromSelectAllMode() {
        const selectAll = isSelectAllAsnMode();
        $('#select-all-items').prop('checked', selectAll);
        $('.item-checkbox:visible:not(:disabled)').prop('checked', selectAll);
    }

    let itemIndex = {{ (($asnItems->lastItem() ?? 0) + 1) }};
    const isLargeAsnItemSet = {{ !empty($isLargeItemSet) ? 'true' : 'false' }};
    
    $(document).ready(function() {
        const isEditLockedToExistingItems = true;

        // Exclude modal selects so they are only initialized when modal opens (avoids duplicate "Select User" display)
        $('.select2').not('#createGrnModal .select2').select2();
        if (!isLargeAsnItemSet) {
            $('.select2-item').select2({
                width: '100%',
                dropdownParent: $('#items-table').closest('.card-body')
            });
        }
        
        // Initialize select2 for modal dropdowns when modal is shown (destroy first to avoid duplicate)
        $('#createGrnModal').on('shown.bs.modal', function() {
            var $sel = $('#assigned_user_id');
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }
            $sel.select2({
                width: '100%',
                dropdownParent: $('#createGrnModal')
            });
        });
        
        if (!isLargeAsnItemSet) {
            $('.item-row').each(function(index) {
                calculateItemTotals(index);
            });
            initBoxNoAutosize($(document));
        }
        
        if (isEditLockedToExistingItems) {
            $('#add-item')
                .prop('disabled', true)
                .addClass('disabled')
                .attr('title', '{{ __('Adding new rows is disabled in edit. Update existing rows only.') }}');
        }

        $('#add-item').click(function() {
            if (isEditLockedToExistingItems) {
                return;
            }
            const newRow = $('#items-table tbody tr').first().clone();
            newRow.find('input[type="text"], input[type="number"], select').val('');
            newRow.find('input[type="date"]').val('');
            // New rows must not carry existing ASN item IDs.
            newRow.find('input[type="hidden"][name$="[id]"]').val('');
            newRow.attr('data-item-id', '');
            newRow.find('input[name]').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + itemIndex + ']');
                    $(this).attr('name', newName);
                }
            });
            newRow.find('.remove-item').show();
            newRow.find('.discrepancy-input, .total-price, .total-weight').val('0.00');
            newRow.find('.select2-item').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');
            $('#items-container').append(newRow);
            if (!isLargeAsnItemSet) {
                newRow.find('.select2-item').select2({
                    width: '100%',
                    dropdownParent: $('#items-table').closest('.card-body')
                });
            }
            initBoxNoAutosize(newRow);
            itemIndex++;
        });
        
        $(document).on('click', '.remove-item', function() {
            if ($(this).prop('disabled') || $(this).hasClass('disabled')) {
                return;
            }
            if ($('#items-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });
        
        $(document).on('input', '.qty-input, .received-qty-input, .unit-price, .unit-weight', function() {
            const row = $(this).closest('tr');
            const index = row.index();
            calculateItemTotals(index);
        });

        $('#asn-form').on('submit', function(e) {
            let invalid = false;
            $('#asn-form .item-row').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                const received = parseFloat($(this).find('.received-qty-input').val()) || 0;
                if (received > qty + 1e-9) {
                    invalid = true;
                    return false;
                }
            });
            if (invalid) {
                e.preventDefault();
                alert(@json(__('Received quantity cannot exceed ordered quantity (QTY) on a line.')));
            }
        });

        // Paste from Excel: single column -> fill that column down; multiple columns -> fill row by row
        $('#items-table').on('paste', 'input, select', function(e) {
            const $target = $(e.target);
            const $row = $target.closest('tr.item-row');
            const $td = $target.closest('td');
            if (!$row.length || !$td.length) return;

            e.preventDefault();
            const text = (e.originalEvent.clipboardData || window.clipboardData).getData('text').trim();
            if (!text) return;

            const colIndex = $td.index();
            const startRowIndex = $row.index();
            const lines = text.split(/\r?\n/);
            const hasTabs = text.indexOf('\t') >= 0;

            function setCellValue($input, val) {
                val = String(val).trim();
                if ($input.is('select')) {
                    const $opt = $input.find('option').filter(function() { return $(this).text().trim() === val || $(this).val() === val; }).first();
                    if ($opt.length) $input.val($opt.val()).trigger('change');
                    else if (val) $input.val(val).trigger('change');
                } else if ($input.attr('type') === 'date') {
                    const iso = val.match(/^(\d{4})-(\d{2})-(\d{2})/);
                    const dmy = val.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
                    if (iso) $input.val(iso[1] + '-' + iso[2] + '-' + iso[3]);
                    else if (dmy) $input.val(dmy[3] + '-' + dmy[2].padStart(2,'0') + '-' + dmy[1].padStart(2,'0'));
                    else $input.val(val);
                } else {
                    $input.val(val);
                }
            }

            if (!hasTabs && lines.length > 0) {
                // Single column: paste into the same column for each row (e.g. Origin column -> fill Origin for all rows)
                const currentRows = $('#items-table tbody tr.item-row').length;
                const maxRowsToFill = Math.max(0, currentRows - startRowIndex);
                lines.slice(0, maxRowsToFill).forEach(function(val, i) {
                    const $tr = $('#items-table tbody tr.item-row').eq(startRowIndex + i);
                    const $cell = $tr.children('td').eq(colIndex);
                    const $input = $cell.find('input:not([type="hidden"]), select').first();
                    if ($input.length) {
                        setCellValue($input, val);
                    }
                    calculateItemTotals(startRowIndex + i);
                });
                return;
            }

            // Multiple columns: fill row by row (original behavior)
            const rows = lines.map(function(line) { return line.split(/\t/); });
            if (rows.length && rows[0].length) {
                const first = String(rows[0][0] || '').toLowerCase();
                if (first.indexOf('box') >= 0 || first.indexOf('part') >= 0 || first === 'part no' || first === 'box no') {
                    rows.shift();
                }
            }
            const editableColumnIndexes = [1, 2, 3, 4, 5, 6, 7, 8, 10, 12, 14, 15, 16, 17, 18];
            const currentRows = $('#items-table tbody tr.item-row').length;
            const maxRowsToFill = Math.max(0, currentRows - startRowIndex);
            rows.slice(0, maxRowsToFill).forEach(function(cells, i) {
                const $tr = $('#items-table tbody tr.item-row').eq(startRowIndex + i);
                cells.forEach(function(cell, colIdx) {
                    if (colIdx >= editableColumnIndexes.length) return;
                    const tdIndex = editableColumnIndexes[colIdx];
                    const $cell = $tr.children('td').eq(tdIndex);
                    const $input = $cell.find('input:not([type="hidden"]), select').first();
                    if ($input.length && !$input.prop('readonly')) setCellValue($input, cell);
                });
                calculateItemTotals(startRowIndex + i);
            });
        });
    });
    
    function calculateItemTotals(index) {
        const row = $('#items-table tbody tr').eq(index);
        if (!row.length) {
            return;
        }
        const qty = parseFloat(row.find('.qty-input').val()) || 0;
        const receivedInput = row.find('.received-qty-input');
        if (qty >= 0) {
            receivedInput.attr('max', qty);
        } else {
            receivedInput.removeAttr('max');
        }
        let receivedQty = parseFloat(receivedInput.val()) || 0;
        if (receivedQty > qty) {
            receivedQty = qty;
            receivedInput.val(receivedQty.toFixed(2));
        }
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const unitWeight = parseFloat(row.find('.unit-weight').val()) || 0;
        
        // Calculate discrepancy
        const discrepancy = receivedQty - qty;
        row.find('.discrepancy-input').val(discrepancy.toFixed(2));
        
        // Calculate total price
        const totalPrice = receivedQty * unitPrice;
        row.find('.total-price').val(totalPrice.toFixed(2));
        
        // Calculate total weight
        const totalWeight = receivedQty * unitWeight;
        row.find('.total-weight').val(totalWeight.toFixed(2));
    }

    // Auto-size Box No inputs so the value fits inside the field
    function initBoxNoAutosize($context) {
        $context.find('.box-no-input').each(function () {
            const input = this;
            function resize() {
                const len = (input.value || '').length;
                input.size = Math.max(8, len + 1);
            }
            resize();
            $(input).off('input.boxAuto').on('input.boxAuto', resize);
        });
    }
    
    function loadSupplierData() {
        const supplierId = $('#supplier_id').val();
        if (supplierId) {
            // Auto-fill supplier code if available
        }
    }
    
    function updateExchangeRate() {
        const currencySelect = $('#currency_id');
        const exchangeRateInput = $('#exchange_rate');
        const selectedOption = currencySelect.find('option:selected');
        const rate = selectedOption.data('rate');
        
        if (rate && rate > 0) {
            exchangeRateInput.val(rate);
        }
    }
    
    // Initialize exchange rate on page load
    $(document).ready(function() {
        updateExchangeRate();
        
        // Initialize select2 for filter dropdown
        $('#filter-box-no').select2({
            width: 'resolve',
            placeholder: '{{ __('All Box Numbers') }}',
            allowClear: true,
            dropdownAutoWidth: true,
            minimumResultsForSearch: 5
        });
        
        // Filter by Box No functionality
        $('#filter-box-no').on('change', function() {
            const filterValue = $(this).val();
            const rows = $('#items-table tbody tr.item-row');
            
            if (!filterValue || filterValue === '') {
                // Show all rows if no filter is selected
                rows.show();
            } else {
                // Filter rows based on selected box no
                rows.each(function() {
                    const boxNo = $(this).find('input[name*="[box_no]"]').val() || '';
                    if (boxNo === filterValue) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            if (isSelectAllAsnMode()) {
                $('.item-checkbox:visible:not(:disabled)').prop('checked', true);
            }
            updateSelectAllCheckbox();
        });

        function updateSelectAllCheckbox() {
            if (isSelectAllAsnMode()) {
                $('#select-all-items').prop('checked', true);
                return;
            }
            const visibleCheckboxes = $('.item-checkbox:visible:not(:disabled)');
            const checkedVisibleCheckboxes = $('.item-checkbox:visible:not(:disabled):checked');
            $('#select-all-items').prop('checked', visibleCheckboxes.length > 0 && visibleCheckboxes.length === checkedVisibleCheckboxes.length);
        }

        applyVisibleCheckboxesFromSelectAllMode();

        $('#select-all-items').on('change', function() {
            const checked = $(this).prop('checked');
            if (checked && allSelectableAsnItemIds.length > 0) {
                setSelectAllAsnMode(true);
            } else {
                setSelectAllAsnMode(false);
            }
            $('.item-checkbox:visible:not(:disabled)').prop('checked', checked && allSelectableAsnItemIds.length > 0);
            if (checked && allSelectableAsnItemIds.length === 0) {
                $(this).prop('checked', false);
            }
        });

        $(document).on('change', '.item-checkbox', function() {
            if (!$(this).prop('checked')) {
                setSelectAllAsnMode(false);
                $('#select-all-items').prop('checked', false);
            }
            updateSelectAllCheckbox();
        });
        
        // Create GRN form submission
        $('#create-grn-form').on('submit', function(e) {
            if ($(this).data('confirmed') === true) {
                return true;
            }

            e.preventDefault();
            
            let selectedItems = [];
            if (isSelectAllAsnMode() && allSelectableAsnItemIds.length > 0) {
                selectedItems = allSelectableAsnItemIds.slice();
            } else {
                selectedItems = $('.item-checkbox:checked:not(:disabled)').map(function() {
                    return $(this).val();
                }).get();
            }
            
            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __("No items selected") }}',
                    text: '{{ __("Please select at least one item to create GRN. Items already assigned to GRN cannot be selected.") }}',
                    confirmButtonText: '{{ __("OK") }}',
                    customClass: {
                        container: 'swal2-over-grn-modal'
                    }
                });
                return false;
            }
            
            // Remove any existing selected_items inputs
            $('#create-grn-form input[name="selected_items[]"]').remove();
            
            // Add selected items to form (only enabled checkboxes)
            selectedItems.forEach(function(itemId) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'selected_items[]',
                    value: itemId
                }).appendTo('#create-grn-form');
            });

            const form = this;
            Swal.fire({
                icon: 'question',
                title: '{{ __("Create GRN?") }}',
                text: '{{ __("Are you sure you want to create GRN for the selected items?") }}',
                showCancelButton: true,
                confirmButtonText: '{{ __("Yes, create") }}',
                cancelButtonText: '{{ __("Cancel") }}',
                reverseButtons: true,
                customClass: {
                    container: 'swal2-over-grn-modal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $(form).data('confirmed', true);
                    form.submit();
                }
            });
        });

        // Ensure update payload contains only existing ASN item rows.
        $('#asn-form').on('submit', function(e) {
            const invalidRows = [];
            $('#items-table tbody tr.item-row').each(function(idx) {
                const itemId = ($(this).find('input[type="hidden"][name*="[id]"]').val() || '').trim();
                if (!itemId) {
                    invalidRows.push(idx + 1);
                }
            });

            // Soft warning only; backend has fallback mapping by row order.
            if (invalidRows.length) {
                console.warn('ASN edit: rows missing item id', invalidRows);
            }
        });
    });
</script>
@endpush

