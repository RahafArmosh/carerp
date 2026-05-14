@extends('layouts.admin')
@section('page-title')
    {{ __('ASN Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('asn.index') }}">{{ __('ASN') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('manage bill')
            <a href="{{ route('asn.single.export', $asn->id) }}" class="btn btn-sm btn-success ms-2" data-bs-toggle="tooltip"
                title="{{ __('Export to Excel') }}" data-original-title="{{ __('Export to Excel') }}">
                <i class="ti ti-file-export"></i> {{ __('Export') }}
            </a>
        @endcan
        @can('create bill')
            @if(!$hasInventoryItems)
                @if($asn->status === 'fully_received' || $asn->status === 'manually_received')
                    <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#convertToBillModal">
                        <i class="ti ti-file-invoice"></i> {{ __('Convert to Bill') }}
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-secondary ms-2" disabled data-bs-toggle="tooltip" title="{{ __('ASN must be fully received before converting to Bill. Current status: :status', ['status' => $asn->status_label ?? $asn->status ?? 'created']) }}">
                        <i class="ti ti-file-invoice"></i> {{ __('Convert to Bill') }}
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#convertSelectedToBillModal" id="convertSelectedToBillBtn" disabled>
                    <i class="ti ti-file-invoice"></i> {{ __('Convert Selected to Bill') }}
                </button>
            @else
                <button type="button" class="btn btn-sm btn-secondary ms-2" disabled data-bs-toggle="tooltip" title="{{ __('Cannot convert all items to Bill. Some items have already been converted to Inventory. Use "Convert Selected to Bill" instead.') }}">
                    <i class="ti ti-file-invoice"></i> {{ __('Convert to Bill') }}
                </button>
                <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#convertSelectedToBillModal" id="convertSelectedToBillBtn" disabled>
                    <i class="ti ti-file-invoice"></i> {{ __('Convert Selected to Bill') }}
                </button>
            @endif
            
            @if($hasBillItems)
                <button type="button" class="btn btn-sm btn-secondary ms-2" disabled data-bs-toggle="tooltip" title="{{ __('Cannot convert to Inventory. Some items have already been converted to Bill.') }}">
                    <i class="ti ti-package"></i> {{ __('Convert to Inventory') }}
                </button>
            @elseif($asn->status !== 'fully_received' && $asn->status !== 'manually_received')
                <button type="button" class="btn btn-sm btn-secondary ms-2" disabled data-bs-toggle="tooltip" title="{{ __('ASN must be fully or manually received before converting to Inventory. Current status: :status', ['status' => $asn->status_label ?? $asn->status ?? 'created']) }}">
                    <i class="ti ti-package"></i> {{ __('Convert to Inventory') }}
                </button>
            @elseif(!$canConvertMoreToInventory)
                <button type="button" class="btn btn-sm btn-secondary ms-2" disabled data-bs-toggle="tooltip" title="{{ __('All eligible ASN lines have already been converted to inventory.') }}">
                    <i class="ti ti-package"></i> {{ __('Convert to Inventory') }}
                </button>
            @else
                <button type="button" class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#convertToInventoryModal">
                    <i class="ti ti-package"></i> {{ __('Convert to Inventory') }}
                </button>
            @endif
        @endcan
        @can('edit bill')
            @if(!($asnLockedForEdit ?? false))
                <a href="{{ route('asn.edit', $asn->id) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-pencil"></i> {{ __('Edit') }}
                </a>
            @else
                <button type="button" class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="{{ __('This ASN cannot be edited because it has been converted to inventory or bill.') }}">
                    <i class="ti ti-pencil"></i> {{ __('Edit') }}
                </button>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('ASN Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('ASN No') }}</th>
                                    <td>{{ \Auth::user()->asnNumberFormat($asn->asn_no) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Name') }}</th>
                                    <td>{{ $asn->supplier_name ?? ($asn->supplier->name ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Code') }}</th>
                                    <td>{{ $resolvedSupplierCode ?? ($asn->supplier_code ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Supplier Inv No') }}</th>
                                    <td>{{ $asn->supplier_inv_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ASN Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($asn->asn_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td><span class="{{ $asn->getStatusBadgeClass() }}">{{ $asn->status_label }}</span></td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created By') }}</th>
                                    <td>{{ $asn->creator?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sold QTY (from this ASN)') }}</th>
                                    <td>
                                        {{ $totalSoldQty ?? 0 }} {{ __('(from Invoice + POS)') }}
                                        @can('create bill')
                                            @if(($totalSoldQty ?? 0) > 0)
                                                <button type="button"
                                                    class="btn btn-sm btn-primary ms-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#convertSoldToBillModal"
                                                    id="convertSoldToBillBtn">
                                                    <i class="ti ti-file-invoice"></i> {{ __('Bill Sold') }}
                                                </button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Bills') }}</th>
                                    <td>
                                        @if($asn->asnBills && $asn->asnBills->isNotEmpty())
                                            @foreach($asn->asnBills as $asnBill)
                                                @if($asnBill->bill)
                                                    @php $encBillId = \Illuminate\Support\Facades\Crypt::encrypt($asnBill->bill_id); @endphp
                                                    <a href="{{ route('bill.show', $encBillId) }}" class="badge bg-success me-1">{{ \Auth::user()->billNumberFormat($asnBill->bill->bill_id) }}</a>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="badge bg-secondary">{{ __('No Bills') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Container No') }}</th>
                                    <td>{{ $asn->container_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('DEC NO') }}</th>
                                    <td>{{ $asn->dec_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('BOE Number') }}</th>
                                    <td>{{ $asn->boe_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('DEC DATE') }}</th>
                                    <td>{{ $asn->dec_date ? Auth::user()->dateFormat($asn->dec_date) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Warehouse') }}</th>
                                    <td>{{ $asn->warehouse ? $asn->warehouse->name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Currency') }}</th>
                                    <td>
                                        @if($asn->currency)
                                            {{ $asn->currency->name }} ({{ $asn->currency->code }})
                                        @else
                                            {{ Auth::user()->currencySymbol() }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($asn->exchange_rate ?? 1.0, 6) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Total QTY (this ASN)') }}</th>
                                    <td>{{ $asnTotalQty ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Total Amount (this ASN)') }}</th>
                                    <td>
                                        @php
                                            $headerCurrencySymbol = $asn->currency ? $asn->currency->symbol : Auth::user()->currencySymbol();
                                        @endphp
                                        {{ Auth::user()->priceFormatCurr($asnTotalAmount ?? 0, $headerCurrencySymbol) }}
                                    </td>
                                </tr>
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
                    @php
                        // Get currency symbol once for all price formatting
                        $currencySymbol = $asn->currency ? $asn->currency->symbol : Auth::user()->currencySymbol();
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllItems" title="{{ __('Select All') }}">
                                    </th>
                                    <th>{{ __('Box No') }}</th>
                                    <th>{{ __('Supplier PO No') }}</th>
                                    <th>{{ __('Our PRO No') }}</th>
                                    <th>{{ __('Order Ref') }}</th>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('QTY') }}</th>
                                    <th>{{ __('Received QTY') }}</th>
                                    <th>{{ __('Converted QTY') }}</th>
                                    <th>{{ __('Sold QTY') }}</th>
                                    <th>{{ __('In Hand QTY') }}</th>
                                    <th>{{ __('Reverse QTY') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                    <th>{{ __('Discrepancy') }}</th>
                                    <th>{{ __('Unit Price') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Unit Weight') }}</th>
                                    <th>{{ __('Total Weight') }}</th>
                                    <th>{{ __('HS Code') }}</th>
                                    <th>{{ __('BIN LOCATION 1') }}</th>
                                    <th>{{ __('Container NO') }}</th>
                                    <th>{{ __('DEC NO') }}</th>
                                    <th>{{ __('DEC DATE') }}</th>
                                    <th>{{ __('Origin') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($asnItems ?? collect()) as $item)
                                    @php
                                        $isSplitChild = !empty($item->split_from_asn_item_id);
                                        $isBillSplitChild = $isSplitChild && !empty($item->bill_id);
                                        $isAssigned = isset($item->isAssigned) && $item->isAssigned;
                                        // Allow selection until full received qty is converted (converted_qty can accumulate across multiple bills)
                                        $convertedSoFar = (float)($item->converted_qty ?? 0);
                                        // Allow root lines + transfer-split children (bill_id null). Keep bill-split children locked.
                                        $canSelect = (!$isBillSplitChild) && $item->received_qty > 0 && $convertedSoFar < (float)$item->received_qty;
                                        $remainingQty = max(0, (float)$item->received_qty - $convertedSoFar);
                                    @endphp
                                    <tr class="{{ $isAssigned ? 'table-warning' : '' }} {{ $isSplitChild ? 'table-light' : '' }}" data-item-id="{{ $item->id }}" data-received-qty="{{ $item->received_qty }}" data-converted-qty="{{ $item->converted_qty ?? 0 }}" data-remaining-qty="{{ $remainingQty }}" data-sold-qty="{{ $item->sold_qty ?? 0 }}" data-part-no="{{ e($item->part_no ?? '') }}">
                                        <td>
                                            @if($canSelect)
                                                <input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $item->id }}">
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
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
                                        <td>
                                            {{ $item->part_no ?? '-' }}
                                            @if($isSplitChild)
                                                <br>
                                                <span class="badge bg-secondary" style="font-size: 0.65rem;">
                                                    {{ $isBillSplitChild ? __('Bill split') : __('Transfer split') }}
                                                </span>
                                            @endif
                                        </td>
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
                                        <td>{{ $item->qty }}</td>
                                        <td>{{ $item->received_qty }}</td>
                                        <td>{{ $item->converted_qty !== null ? $item->converted_qty : '-' }}</td>
                                        <td>{{ $item->sold_qty ?? 0 }}</td>
                                        <td>{{ (float)($item->received_qty ?? 0) - (float)($item->sold_qty ?? 0) }}</td>
                                        <td>{{ (float)($item->inventory_reversed_qty ?? 0) }}</td>
                                        <td>
                                            @can('create bill')
                                                @if(!empty($item->canReverseInventory))
                                                    <form method="POST" action="{{ route('asn.item.reverse-inventory', [$asn->id, $item->id]) }}" onsubmit="return confirm('{{ __('Are you sure you want to reverse remaining consignment inventory for this item?') }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            {{ __('Reverse Inventory') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled data-bs-toggle="tooltip" title="{{ __('Reverse allowed only when Sold QTY equals Converted QTY and item has inventory.') }}">
                                                        {{ __('Reverse Inventory') }}
                                                    </button>
                                                @endif
                                            @endcan
                                        </td>
                                        <td>{{ $item->discrepancy }}</td>
                                        <td>{{ Auth::user()->priceFormatCurr($item->unit_price, $currencySymbol) }}</td>
                                        <td>{{ Auth::user()->priceFormatCurr($item->total_price, $currencySymbol) }}</td>
                                        <td>{{ $item->unit_weight }}</td>
                                        <td>{{ $item->total_weight }}</td>
                                        <td>{{ ($item->hsCode ?? '') !== '' ? $item->hsCode : '-' }}</td>
                                        <td>{{ $item->binLocation ?? '-' }}</td>
                                        <td>{{ $item->container_no ?? '-' }}</td>
                                        <td>{{ $item->dec_no ?? '-' }}</td>
                                        <td>{{ $item->dec_date ? Auth::user()->dateFormat($item->dec_date) : '-' }}</td>
                                        <td>{{ $item->origin ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="14" class="text-end">{{ __('Total') }}</th>
                                    <th>{{ Auth::user()->priceFormatCurr($asnTotalPrice ?? 0, $currencySymbol) }}</th>
                                    <th colspan="7"></th>
                                    <th>{{ $asnTotalWeight ?? 0 }}</th>
                                </tr>
                            </tfoot>
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
                </div>
            </div>
            
            <div class="mt-3">
                <a href="{{ route('asn.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
            </div>
        </div>
    </div>

    {{-- Convert to Bill Modal --}}
    @can('create bill')
        @if(!$hasInventoryItems && ($asn->status === 'fully_received' || $asn->status === 'manually_received'))
            <div class="modal fade" id="convertToBillModal" tabindex="-1" role="dialog" aria-labelledby="convertToBillModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="convertToBillModalLabel">{{ __('Convert ASN to Bill') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('asn.convert-to-bill', $asn->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label for="bill_date" class="form-label">{{ __('Bill Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="due_date" class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" id="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="tax_id" class="form-label">{{ __('Tax') }} <span class="text-danger">*</span></label>
                                    <select name="tax_id" id="tax_id" class="form-control select2" required>
                                        <option value="">{{ __('Select Tax') }}</option>
                                        @foreach($taxes ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">{{ __('Select tax to apply to the bill.') }}</small>
                                </div>
                                <div class="alert alert-info">
                                    <p class="mb-0"><strong>{{ __('Note:') }}</strong> {{ __('This will create a bill from all ASN items. Make sure all items have been received before converting.') }}</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Convert to Bill') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Convert to Inventory Modal --}}
    @can('create bill')
        @if(!$hasBillItems && ($asn->status === 'fully_received' || $asn->status === 'manually_received') && $canConvertMoreToInventory)
            <div class="modal fade" id="convertToInventoryModal" tabindex="-1" role="dialog" aria-labelledby="convertToInventoryModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="convertToInventoryModalLabel">{{ __('Convert ASN to Inventory') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('asn.convert-to-inventory', $asn->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <p class="mb-0"><strong>{{ __('Note:') }}</strong> {{ __('This will convert all ASN items to consignment stock. Sub products will be created/updated with flag 3 (consignment). Ledger entries will be created: Debit Inventory, Credit Goods Received Clearing account.') }}</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('Convert to Inventory') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Convert Selected Items to Bill Modal --}}
    @can('create bill')
        <div class="modal fade" id="convertSelectedToBillModal" tabindex="-1" role="dialog" aria-labelledby="convertSelectedToBillModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="convertSelectedToBillModalLabel">{{ __('Convert Selected Items to Bill') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('asn.convert-selected-to-bill', $asn->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div id="selectedItemsContainer">
                                <table class="table table-sm mb-3" id="selectedItemsQtyTable" style="display:none;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Part No') }}</th>
                                            <th>{{ __('Received Qty') }}</th>
                                            <th>{{ __('Sold Qty') }}</th>
                                            <th>{{ __('Converted Qty') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedItemsQtyBody"></tbody>
                                </table>
                            </div>
                            <div class="form-group mb-3 d-none" id="selectedItemsHiddenContainer"></div>
                            <div class="form-group mb-3">
                                <label for="selected_bill_date" class="form-label">{{ __('Bill Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="bill_date" id="selected_bill_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="selected_due_date" class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="selected_due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="selected_tax_id" class="form-label">{{ __('Tax') }} <span class="text-danger">*</span></label>
                                <select name="tax_id" id="selected_tax_id" class="form-control select2" required>
                                    <option value="">{{ __('Select Tax') }}</option>
                                    @foreach($taxes ?? [] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ __('Select tax to apply to the bill.') }}</small>
                            </div>
                            <div class="alert alert-info">
                                <p class="mb-0"><strong>{{ __('Note:') }}</strong> {{ __('This will create a bill with status "Sent" from selected ASN items. Ledger entries will be created: Debit Inventory, Credit Accounts Payable. Items flag will be set to 1 (purchased).') }}</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Convert to Bill') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- Convert Sold Items to Bill Modal --}}
    @can('create bill')
        <div class="modal fade" id="convertSoldToBillModal" tabindex="-1" role="dialog" aria-labelledby="convertSoldToBillModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="convertSoldToBillModalLabel">{{ __('Convert Sold Items to Bill') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('asn.convert-selected-to-bill', $asn->id) }}" method="POST" id="convertSoldToBillForm">
                        @csrf
                        <input type="hidden" name="sold_only_mode" value="1">
                        <div class="modal-body">
                            <div id="soldItemsContainer" class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
                                <table class="table table-sm table-striped align-middle mb-3" id="soldItemsQtyTable" style="{{ !empty($soldBillItems) && $soldBillItems->count() ? '' : 'display:none;' }}">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">{{ __('Part No') }}</th>
                                            <th class="text-nowrap">{{ __('Sell Price') }}</th>
                                            <th class="text-nowrap">{{ __('Purchase Price') }}</th>
                                            <th class="text-nowrap">{{ __('Unconverted Sold Qty') }}</th>
                                            <th class="text-nowrap">{{ __('Bill Qty') }}</th>
                                            <th class="text-nowrap">{{ __('Purchase Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="soldItemsQtyBody">
                                        @foreach(($soldBillItems ?? collect()) as $item)
                                            <tr>
                                                <td class="text-nowrap">{{ $item->part_no ?? '-' }}</td>
                                                <td class="text-nowrap">{{ number_format((float) ($item->sell_price ?? 0), 2) }}</td>
                                                <td class="text-nowrap">{{ number_format((float) ($item->purchase_price ?? 0), 2) }}</td>
                                                <td class="text-nowrap">{{ $item->unconverted_sold_qty ?? 0 }}</td>
                                                <td>
                                                    <input type="number"
                                                        name="converted_qty[{{ $item->id }}]"
                                                        class="form-control form-control-sm sold-bill-qty-input"
                                                        value="{{ $item->default_bill_qty ?? 0 }}"
                                                        min="0"
                                                        step="0.01"
                                                        required
                                                        data-item-id="{{ $item->id }}"
                                                        data-purchase-price="{{ (float) ($item->purchase_price ?? 0) }}"
                                                        style="min-width:90px;">
                                                </td>
                                                <td class="text-nowrap">
                                                    <span class="sold-purchase-total-value" data-item-id="{{ $item->id }}">
                                                        {{ number_format((float) ($item->purchase_total ?? 0), 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-end mb-3" id="soldItemsGrandTotalWrap" style="{{ !empty($soldBillItems) && $soldBillItems->count() ? '' : 'display:none;' }}">
                                    <div class="fw-bold">
                                        {{ __('Total Purchase (Purchase Price × Total Qty):') }}
                                        <span id="soldItemsGrandTotalValue">
                                            {{ number_format((float) (($soldBillItems ?? collect())->sum('purchase_total')), 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3 d-none" id="soldItemsHiddenContainer">
                                @foreach(($soldBillItems ?? collect()) as $item)
                                    <input type="hidden" name="selected_items[]" value="{{ $item->id }}">
                                @endforeach
                            </div>
                            <div class="form-group mb-3">
                                <label for="sold_bill_date" class="form-label">{{ __('Bill Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="bill_date" id="sold_bill_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="sold_due_date" class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="sold_due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="sold_tax_id" class="form-label">{{ __('Tax') }} <span class="text-danger">*</span></label>
                                <select name="tax_id" id="sold_tax_id" class="form-control" required>
                                    <option value="">{{ __('Select Tax') }}</option>
                                    @foreach($taxes ?? [] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ __('Select tax to apply to the bill.') }}</small>
                            </div>
                            <div class="alert alert-info">
                                <p class="mb-0">
                                    <strong>{{ __('Note:') }}</strong>
                                    {{ __('This will create a bill only for sold quantity not already converted. Bill qty defaults to (sold - converted), capped by remaining receipted qty.') }}
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="submitSoldToBillBtn">{{ __('Convert to Bill') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('script-page')
<script>
    $(document).ready(function() {
        // Select all checkbox functionality
        $('#selectAllItems').on('change', function() {
            $('.item-checkbox').prop('checked', $(this).prop('checked'));
            updateConvertButtonState();
        });

        // Individual checkbox change
        $(document).on('change', '.item-checkbox', function() {
            updateConvertButtonState();
            // Update select all checkbox state
            var totalCheckboxes = $('.item-checkbox').length;
            var checkedCheckboxes = $('.item-checkbox:checked').length;
            $('#selectAllItems').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        });

        // Update convert button state based on selected items
        function updateConvertButtonState() {
            var selectedCount = $('.item-checkbox:checked').length;
            var convertBtn = $('#convertSelectedToBillBtn');
            if (convertBtn.length) {
                convertBtn.prop('disabled', selectedCount === 0);
            }
        }

        // Initialize select2 for modal dropdowns when modal is shown
        $('#convertToBillModal').on('shown.bs.modal', function() {
            $('#warehouse_id').select2({
                width: '100%',
                dropdownParent: $('#convertToBillModal')
            });
            var $tax = $('#tax_id');
            if ($tax.hasClass('select2-hidden-accessible')) {
                $tax.select2('destroy');
            }
            $tax.select2({
                width: '100%',
                dropdownParent: $('#convertToBillModal')
            });
        });

        $('#convertSelectedToBillModal').on('shown.bs.modal', function() {
            // Collect selected rows and their data
            var selectedRows = [];
            $('.item-checkbox:checked').each(function() {
                var $row = $(this).closest('tr');
                var remainingQty = parseFloat($row.data('remaining-qty')) || 0;
                if (remainingQty <= 0) return;
                selectedRows.push({
                    id: $row.data('item-id'),
                    receivedQty: parseFloat($row.data('received-qty')) || 0,
                    soldQty: parseFloat($row.data('sold-qty')) || 0,
                    remainingQty: remainingQty,
                    partNo: $row.data('part-no') || '-'
                });
            });

            // Clear and populate table + hidden inputs
            $('#selectedItemsQtyBody').empty();
            $('#selectedItemsHiddenContainer').empty();
            selectedRows.forEach(function(item) {
                var remaining = parseFloat(item.remainingQty) || 0;
                if (remaining <= 0) return;
                var escapedPartNo = $('<div>').text(item.partNo).html();
                $('#selectedItemsQtyBody').append(
                    '<tr>' +
                    '<td>' + escapedPartNo + '</td>' +
                    '<td>' + item.receivedQty + ' <small class="text-muted">({{ __("remaining") }}: ' + remaining + ')</small></td>' +
                    '<td>' + item.soldQty + '</td>' +
                    '<td><input type="number" name="converted_qty[' + item.id + ']" class="form-control form-control-sm" value="' + remaining + '" min="0" max="' + remaining + '" step="0.01" required style="width:100px;"></td>' +
                    '</tr>'
                );
                $('#selectedItemsHiddenContainer').append('<input type="hidden" name="selected_items[]" value="' + item.id + '">');
            });
            $('#selectedItemsQtyTable').toggle(selectedRows.length > 0);
            var $submitBtn = $('#convertSelectedToBillModal').find('button[type="submit"]');
            if ($submitBtn.length) $submitBtn.prop('disabled', selectedRows.length === 0);

            $('#selected_warehouse_id').select2({
                width: '100%',
                dropdownParent: $('#convertSelectedToBillModal')
            });
            var $selectedTax = $('#selected_tax_id');
            if ($selectedTax.hasClass('select2-hidden-accessible')) {
                $selectedTax.select2('destroy');
            }
            $selectedTax.select2({
                width: '100%',
                dropdownParent: $('#convertSelectedToBillModal')
            });
        });

        $('#convertSoldToBillModal').on('shown.bs.modal', function() {
            var $submitBtn = $('#submitSoldToBillBtn');
            var hasRows = $('#soldItemsQtyBody').find('tr').length > 0;
            if ($submitBtn.length) $submitBtn.prop('disabled', !hasRows);
        });

        function updateSoldPurchaseTotal($input) {
            var itemId = $input.data('item-id');
            var purchasePrice = parseFloat($input.data('purchase-price')) || 0;
            var qty = parseFloat($input.val()) || 0;
            var total = purchasePrice * qty;
            var $target = $('.sold-purchase-total-value[data-item-id="' + itemId + '"]');
            if ($target.length) {
                $target.text(total.toFixed(2));
            }
        }

        function updateSoldGrandTotal() {
            var grandTotal = 0;
            $('.sold-bill-qty-input').each(function() {
                var $input = $(this);
                var purchasePrice = parseFloat($input.data('purchase-price')) || 0;
                var qty = parseFloat($input.val()) || 0;
                grandTotal += (purchasePrice * qty);
            });
            $('#soldItemsGrandTotalValue').text(grandTotal.toFixed(2));
        }

        // Keep "Convert to Bill" disabled when all bill qty are 0 and update purchase totals
        $(document).on('input', '.sold-bill-qty-input', function() {
            updateSoldPurchaseTotal($(this));
            updateSoldGrandTotal();
            var anyPositive = false;
            $('.sold-bill-qty-input').each(function() {
                if ((parseFloat($(this).val()) || 0) > 0) anyPositive = true;
            });
            var $submitBtn = $('#submitSoldToBillBtn');
            if ($submitBtn.length) $submitBtn.prop('disabled', !anyPositive);
        });

        // Initialize totals on page load/modal open.
        $('.sold-bill-qty-input').each(function() {
            updateSoldPurchaseTotal($(this));
        });
        updateSoldGrandTotal();

        // Check if any items can be selected on page load
        updateConvertButtonState();
    });
</script>
@endpush
