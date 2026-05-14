@extends('layouts.admin')
@section('page-title')
    {{ __('GRN') }}: GRN{{ str_pad($grn->grn_no, 5, '0', STR_PAD_LEFT) }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('grn.index') }}">{{ __('GRN') }}</a></li>
    <li class="breadcrumb-item">{{ __('View') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('GRN') }}: GRN{{ str_pad($grn->grn_no, 5, '0', STR_PAD_LEFT) }}</h5>
                <div>
                    @if($grn->bill_id)
                        <a href="{{ route('bill.show', Crypt::encrypt($grn->bill_id)) }}" class="btn btn-sm btn-info me-2">
                            <i class="ti ti-file-invoice"></i> {{ __('View Bill') }}
                        </a>
                    @endif
                    <a href="{{ route('grn.index') }}" class="btn btn-sm btn-secondary">{{ __('Back') }}</a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('grn.update', Crypt::encrypt($grn->id)) }}" method="POST" id="grn-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="selected_box_no" id="selected_box_no" value="{{ request('box_no', '') }}">
                    @if(!empty($lockGrnQtyEditing))
                        <div class="alert alert-warning">
                            {{ __('Received quantities cannot be changed because the related ASN has been converted to inventory or bill. You can still update GRN status.') }}
                        </div>
                    @endif
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>{{ __('ASN No') }}:</strong> 
                        @if($grn->asn)
                            <a href="{{ route('asn.show', $grn->asn->id) }}">{{ Auth::user()->asnNumberFormat($grn->asn->asn_no) }}</a>
                        @else
                            -
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Supplier') }}:</strong> {{ $grn->supplier_name ?? ($grn->supplier ? $grn->supplier->name : '-') }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('GRN Date') }}:</strong> {{ Auth::user()->dateFormat($grn->grn_date) }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Status') }}:</strong>
                        <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                            <option value="draft" {{ ($grn->status ?? '') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="received" {{ ($grn->status ?? '') === 'received' ? 'selected' : '' }}>{{ __('Partially Received') }}</option>
                            <option value="manually_received" {{ ($grn->status ?? '') === 'manually_received' ? 'selected' : '' }}>{{ __('Manually Received') }}</option>
                            <option value="completed" {{ ($grn->status ?? '') === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                            <option value="cancelled" {{ ($grn->status ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>{{ __('Assigned To') }}:</strong> {{ $grn->assignedUser ? $grn->assignedUser->name : '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Total Qty') }}:</strong> {{ number_format((float)($summary->total_qty ?? 0), 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Received Qty') }}:</strong> {{ number_format((float)($summary->total_received_qty ?? 0), 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('Total Price') }}:</strong> {{ Auth::user()->priceFormat((float)($summary->total_price ?? 0)) }}
                    </div>
                </div>
                @if($grn->notes)
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>{{ __('Notes') }}:</strong> {{ $grn->notes }}
                    </div>
                </div>
                @endif

                <hr>

                <div class="mb-3">
                    <label for="box_no_filter" class="form-label">{{ __('Filter by Box No') }}</label>
                    <select id="box_no_filter" class="form-control select" style="max-width: 300px;">
                        <option value="">{{ __('All Boxes') }}</option>
                        @foreach(($allBoxNos ?? collect()) as $boxNo)
                            <option value="{{ $boxNo }}" {{ request('box_no') == $boxNo ? 'selected' : '' }}>{{ $boxNo }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!empty($isLargeItemSet))
                    <div class="alert alert-info py-2 px-3">
                        {{ __('Large GRN optimization is enabled for faster loading. Showing :shown of :total items on this page.', ['shown' => $grnItems->count(), 'total' => $totalItems ?? $grnItems->total()]) }}
                    </div>
                @endif

                    <div class="table-responsive">
                        <table class="table" id="grn-table">
                            <thead>
                                <tr>
                                    <th>{{ __('BOX NO.') }}</th>
                                    <th>{{ __('SUPPLIER PO NO') }}</th>
                                    <th>{{ __('OUR PRO NO') }}</th>
                                    <th>{{ __('ORDER REF') }}</th>
                                    <th>{{ __('PART NO') }}</th>
                                    <th>{{ __('DESCRIPTION') }}</th>
                                    <th>{{ __('CUSTOM FIELDS') }}</th>
                                    <th>{{ __('QTY') }}</th>
                                    <th class="text-primary">{{ __('RECEIVED QTY') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grnItems as $item)
                                    @php
                                        $asnItem = $item->asnItem;
                                        $boxNo = $asnItem ? $asnItem->box_no : '';
                                    @endphp
                                    <tr data-box-no="{{ $boxNo }}">
                                        <td>{{ $boxNo ?: '-' }}</td>
                                        <td>{{ $asnItem ? ($asnItem->supplier_po_no ?? '-') : '-' }}</td>
                                        <td>{{ $asnItem ? ($asnItem->our_pro_no ?? '-') : '-' }}</td>
                                        <td>{{ $asnItem ? ($asnItem->order_ref ?? '-') : '-' }}</td>
                                        <td>{{ $item->part_no ?? '-' }}</td>
                                        <td>
                                            @php
                                                $product = $item->matchedProduct ?? null;
                                                $displayText = $item->description ?? ($asnItem ? ($asnItem->description ?? '-') : '-');
                                                
                                                if ($product) {
                                                    $parts = [];
                                                    
                                                    if ($product->category && $product->category->name) {
                                                        $parts[] = $product->category->name;
                                                    }
                                                    
                                                    if ($product->brand && $product->brand->name) {
                                                        $parts[] = $product->brand->name;
                                                    }
                                                    
                                                    if ($product->subBrand && $product->subBrand->name) {
                                                        $parts[] = $product->subBrand->name;
                                                    }
                                                    
                                                    if ($product->name) {
                                                        $parts[] = $product->name;
                                                    }
                                                    
                                                    if (!empty($parts)) {
                                                        $displayText = implode(' / ', $parts);
                                                    }
                                                }
                                            @endphp
                                            {{ $displayText }}
                                        </td>
                                        <td>
                                            @php
                                                $customFields = $item->customFields ?? collect();
                                                $customFieldValues = $item->customFieldValues ?? collect();
                                                $binLocationNames = ['bin location 1', 'bin location 2'];
                                                $binLocationFields = $customFields->filter(fn($f) => in_array(trim(strtolower($f->name ?? '')), $binLocationNames, true));
                                            @endphp
                                            @if($binLocationFields->isNotEmpty())
                                                @php
                                                    $binLocationWithValues = $binLocationFields->filter(fn($f) => !empty($customFieldValues->get($f->id)));
                                                @endphp
                                                @if($binLocationWithValues->isNotEmpty())
                                                    <div class="custom-fields-display">
                                                        @foreach($binLocationWithValues as $customField)
                                                            <div class="custom-field-item">{{ $customField->name }}: {{ $customFieldValues->get($customField->id) }}</div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="qty-cell">{{ number_format($item->qty, 2) }}</td>
                                        <td style="max-width: 130px;">
                                            <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                            <input type="number" step="0.01" min="0" class="form-control received-qty-input" 
                                                   name="items[{{ $item->id }}][received_qty]" 
                                                   value="{{ $item->received_qty ?? 0 }}"
                                                   data-qty="{{ $item->qty }}"
                                                   {{ !empty($lockGrnQtyEditing) ? 'readonly' : '' }}>
                                        </td>
                                        <td>
                                            @if(!empty($item->part_no))
                                                @if($asnItem)
                                                    <a href="{{ route('asn.item.barcode', ['asn' => $grn->asn_id, 'item' => $asnItem->id]) }}" 
                                                       target="_blank"
                                                       class="btn btn-sm btn-primary"
                                                       data-bs-toggle="tooltip" 
                                                       title="{{ __('Print Barcode') }}">
                                                        <i class="ti ti-printer"></i> {{ __('Print Barcode') }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $grnItems->withQueryString()->links() }}
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">{{ __('Save GRN') }}</button>
                        <a href="{{ route('grn.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grnForm = document.getElementById('grn-form');
        const selectedBoxNoInput = document.getElementById('selected_box_no');
        if (grnForm) {
            grnForm.addEventListener('submit', function(e) {
                if (grnForm.dataset.confirmed === 'true') {
                    return;
                }
                e.preventDefault();

                Swal.fire({
                    icon: 'question',
                    title: @json(__('Confirm Save')),
                    text: @json(__('Are you sure you want to save and update this GRN?')),
                    showCancelButton: true,
                    confirmButtonText: @json(__('Yes, Save')),
                    cancelButtonText: @json(__('Cancel')),
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        grnForm.dataset.confirmed = 'true';
                        grnForm.submit();
                    }
                });
            });
        }

        const boxNoFilter = document.getElementById('box_no_filter');
        const currentBoxNo = boxNoFilter ? (boxNoFilter.value || '') : '';
        if (currentBoxNo !== '') {
            document.querySelectorAll('#grn-table tbody tr').forEach(function(row) {
                const input = row.querySelector('.received-qty-input');
                if (!input || input.hasAttribute('readonly')) {
                    return;
                }
                const qtyValue = input.getAttribute('data-qty');
                if (qtyValue !== null && qtyValue !== '') {
                    input.value = parseFloat(qtyValue).toFixed(2);
                }
            });
        }

        boxNoFilter.addEventListener('change', function() {
            if (selectedBoxNoInput) {
                selectedBoxNoInput.value = this.value || '';
            }
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('box_no', this.value);
            } else {
                url.searchParams.delete('box_no');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    });
</script>
<style>
    .custom-fields-display {
        font-size: 0.875rem;
    }
    .custom-field-item {
        margin-bottom: 4px;
    }
    .custom-field-item:last-child {
        margin-bottom: 0;
    }
    .custom-field-item strong {
        color: #6c757d;
        font-weight: 600;
    }
</style>

@endsection

