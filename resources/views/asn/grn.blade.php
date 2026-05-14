@extends('layouts.admin')
@section('page-title')
    {{ __('Goods Receipt Note (GRN)') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('asn.index') }}">{{ __('ASN') }}</a></li>
    <li class="breadcrumb-item">{{ __('GRN') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('ASN') }}: {{ Auth::user()->asnNumberFormat($asn->asn_no) }}</h5>
                <a href="{{ route('asn.show', $asn->id) }}" class="btn btn-sm btn-secondary">{{ __('Back') }}</a>
            </div>
            <div class="card-body">
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
                        {{ __('Large ASN optimization is enabled for faster loading. Showing :shown of :total items on this page.', ['shown' => $asnItems->count(), 'total' => $allItemCount ?? $asnItems->total()]) }}
                    </div>
                @endif
                <form action="{{ route('asn.grn.store', $asn->id) }}" method="POST" id="grn-form">
                    @csrf
                    <input type="hidden" name="selected_box_no" id="selected_box_no" value="{{ request('box_no', '') }}">
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
                                @foreach($asnItems as $item)
                                    @php
                                        $isAssigned = isset($item->isAssigned) && $item->isAssigned;
                                    @endphp
                                    <tr data-box-no="{{ $item->box_no ?? '' }}" class="{{ $isAssigned ? 'table-warning' : '' }}">
                                        <td>
                                            {{ $item->box_no ?? '-' }}
                                            @if($isAssigned)
                                                <br><span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ __('Assigned to GRN') }}</span>
                                                @if(!empty($item->assignedGrnNumbers))
                                                    <br><small class="text-muted" style="font-size: 0.65rem;">{{ implode(', ', $item->assignedGrnNumbers) }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $item->supplier_po_no ?? '-' }}</td>
                                        <td>{{ $item->our_pro_no ?? '-' }}</td>
                                        <td>{{ $item->order_ref ?? '-' }}</td>
                                        <td>{{ $item->part_no ?? '-' }}</td>
                                        <td>
                                            @php
                                                $product = $item->matchedProduct ?? null;
                                                $displayText = $item->description ?? '-';
                                                
                                                if ($product) {
                                                    $parts = [];
                                                    
                                                    // Add category
                                                    if ($product->category && $product->category->name) {
                                                        $parts[] = $product->category->name;
                                                    }
                                                    
                                                    // Add brand
                                                    if ($product->brand && $product->brand->name) {
                                                        $parts[] = $product->brand->name;
                                                    }
                                                    
                                                    // Add sub-brand
                                                    if ($product->subBrand && $product->subBrand->name) {
                                                        $parts[] = $product->subBrand->name;
                                                    }
                                                    
                                                    // Add product name
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
                                            @endphp
                                            @if($customFields->count() > 0)
                                                <div class="custom-fields-display">
                                                    @foreach($customFields as $customField)
                                                        @php
                                                            $value = $customFieldValues->get($customField->id) ?? null;
                                                        @endphp
                                                        @if($value)
                                                            <div class="custom-field-item">
                                                                {{ $customField->name }}: {{ $value }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
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
                                                   data-qty="{{ $item->qty }}">
                                        </td>
                                        <td>
                                            @if(!empty($item->part_no))
                                                <a href="{{ route('asn.item.barcode', ['asn' => $asn->id, 'item' => $item->id]) }}" 
                                                   target="_blank"
                                                   class="btn btn-sm btn-primary"
                                                   data-bs-toggle="tooltip" 
                                                   title="{{ __('Print Barcode') }}">
                                                    <i class="ti ti-printer"></i> {{ __('Print Barcode') }}
                                                </a>
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
                        {{ $asnItems->withQueryString()->links() }}
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Save GRN') }}</button>
                        <a href="{{ route('asn.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const boxNoFilter = document.getElementById('box_no_filter');
        const selectedBoxNoInput = document.getElementById('selected_box_no');
        const currentBoxNo = boxNoFilter ? (boxNoFilter.value || '') : '';
        if (currentBoxNo !== '') {
            document.querySelectorAll('#grn-table tbody tr').forEach(function(row) {
                const input = row.querySelector('.received-qty-input');
                if (!input) {
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


