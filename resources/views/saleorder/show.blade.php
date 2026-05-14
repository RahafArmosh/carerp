@extends('layouts.admin')
@section('page-title')
    {{ __('Sale Order Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('saleorder.index') }}">{{ __('Sale Orders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create sale order')
            <a href="{{ route('saleorder.print', ['id' => \Crypt::encrypt($saleOrder->id)]) }}" class="btn btn-sm btn-secondary ms-2" target="_blank">
                <i class="ti ti-printer"></i> {{ __('Print') }}
            </a>
            <a href="{{ route('saleorder.print', ['id' => \Crypt::encrypt($saleOrder->id), 'show_custom_fields' => 1]) }}" class="btn btn-sm btn-info ms-2" target="_blank">
                <i class="ti ti-printer"></i> {{ __('Print + Custom Fields') }}
            </a>
            @if (!$saleOrder->isConverted() && !$saleOrder->pickList)
                <a href="{{ route('saleorder.edit', \Crypt::encrypt($saleOrder->id)) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-pencil"></i> {{ __('Edit') }}
                </a>
                <form action="{{ route('saleorder.destroy', \Crypt::encrypt($saleOrder->id)) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('{{ __('Are you sure you want to delete this sale order? This will unbook all sub-products and delete all related pick/packing lists.') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="ti ti-trash"></i> {{ __('Delete') }}
                    </button>
                </form>
            @endif
            @if (!$saleOrder->pickList)
                <form action="{{ route('saleorder.convert-to-picklist', \Crypt::encrypt($saleOrder->id)) }}"
                      method="POST"
                      class="d-inline js-send-pickpack-form">
                    @csrf
                    <button type="submit"
                            class="btn btn-sm btn-warning ms-2">
                        <i class="ti ti-clipboard-list"></i> {{ __('Send For Pick And Pack') }}
                    </button>
                </form>
            @else
                <a href="{{ route('picklist.show', \Crypt::encrypt($saleOrder->pickList->id)) }}" class="btn btn-sm btn-info ms-2">
                    <i class="ti ti-clipboard-list"></i> {{ __('View Pick List') }}
                </a>
            @endif
        @endcan
        @if ($saleOrder->isConverted())
            @can('show invoice')
                <a href="{{ route('invoice.show', \Crypt::encrypt($saleOrder->invoice_id)) }}" class="btn btn-sm btn-success ms-2">
                    <i class="ti ti-file-invoice"></i> {{ __('View Invoice') }}
                </a>
            @endcan
        @elseif (in_array($saleOrder->status, ['approved', 'packed']))
            @can('create invoice')
                <a href="{{ route('saleorder.convert-to-invoice', \Crypt::encrypt($saleOrder->id)) }}" class="btn btn-sm btn-success ms-2" 
                   onclick="return confirm('{{ __('Are you sure you want to convert this approved sale order to an invoice?') }}')">
                    <i class="ti ti-file-invoice"></i> {{ __('Post Sales Invoice') }}
                </a>
            @endcan
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Sale Order Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Sale Order No') }}</th>
                                    <td>{{ \Auth::user()->saleOrderNumberFormat($saleOrder->sale_order_no) }}</td>
                                </tr>
                                @if ($saleOrder->converted_quotation_id)
                                    <tr>
                                        <th>{{ __('From Quotation') }}</th>
                                        <td>{{ \Auth::user()->saleOrderNumberFormat($saleOrder->converted_quotation_id) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ $saleOrder->customer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer TRN No') }}</th>
                                    <td>{{ $saleOrder->customer->customer_trn_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sales Order Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($saleOrder->sales_order_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        <form action="{{ route('saleorder.update-status', \Crypt::encrypt($saleOrder->id)) }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto" style="max-width: 220px;">
                                                <option value="draft" {{ ($saleOrder->status ?? '') === 'draft' ? 'selected' : '' }}>{{ __('CREATED') }}</option>
                                                <option value="picking" {{ ($saleOrder->status ?? '') === 'picking' ? 'selected' : '' }}>{{ __('PICKING IN PROGRESS') }}</option>
                                                <option value="packing_in_progress" {{ ($saleOrder->status ?? '') === 'packing_in_progress' ? 'selected' : '' }}>{{ __('PACKING IN PROGRESS') }}</option>
                                                <option value="packed" {{ ($saleOrder->status ?? '') === 'packed' ? 'selected' : '' }}>{{ __('PACKED') }}</option>
                                                <option value="shipped" {{ ($saleOrder->status ?? '') === 'shipped' ? 'selected' : '' }}>{{ __('SHIPPED') }}</option>
                                                <option value="invoiced" {{ in_array($saleOrder->status ?? '', ['invoiced', 'converted']) ? 'selected' : '' }}>{{ __('INVOICED') }}</option>
                                                <option value="converted" {{ ($saleOrder->status ?? '') === 'converted' ? 'selected' : '' }}>{{ __('INVOICED') }} (legacy)</option>
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
                                    <th>{{ __('Currency') }}</th>
                                    <td>
                                        @if($saleOrder->currency)
                                            {{ $saleOrder->currency->name }} ({{ $saleOrder->currency->code }})
                                        @else
                                            {{ Auth::user()->currencySymbol() }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Tax') }}</th>
                                    <td>
                                        @if($saleOrder->tax_id)
                                            @php
                                                $taxIds = explode(',', $saleOrder->tax_id);
                                                $taxNames = [];
                                                foreach ($taxIds as $taxId) {
                                                    $tax = $taxes->firstWhere('id', $taxId);
                                                    if ($tax) {
                                                        $taxNames[] = $tax->name . ' (' . $tax->rate . '%)';
                                                    }
                                                }
                                            @endphp
                                            {{ !empty($taxNames) ? implode(', ', $taxNames) : '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Exchange Rate') }}</th>
                                    <td>{{ number_format($saleOrder->exchange_rate ?? 1.0, 6) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created By') }}</th>
                                    <td>{{ $saleOrder->creator->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created At') }}</th>
                                    <td>{{ Auth::user()->dateFormat($saleOrder->created_at) }}</td>
                                </tr>
                                @if ($saleOrder->isConverted())
                                    <tr>
                                        <th>{{ __('Invoice') }}</th>
                                        <td>
                                            @can('show invoice')
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($saleOrder->invoice_id)) }}" class="badge bg-success">
                                                    {{ __('View Invoice') }}
                                                </a>
                                            @else
                                                <span class="badge bg-success">{{ __('Converted') }}</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endif
                                @if ($saleOrder->pickList)
                                    <tr>
                                        <th>{{ __('Pick List') }}</th>
                                        <td>
                                            <a href="{{ route('picklist.show', \Crypt::encrypt($saleOrder->pickList->id)) }}" class="badge bg-info">
                                                {{ __('View Pick List') }}
                                            </a>
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
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('REQ QTY') }}</th>
                                    <th>{{ __('STOCK QTY') }}</th>
                                    <th>{{ __('PICKING QTY') }}</th>
                                    <th>{{ __('PACKED QTY') }}</th>
                                    <th>{{ __('DISCREPANCY') }}</th>
                                    <th>{{ __('Unit Price') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th>{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalAmount = 0;
                                    $totalTax = 0;
                                    $taxData = \App\Models\Utility::getTaxData();
                                    $taxIds = $saleOrder->tax_id ? explode(',', $saleOrder->tax_id) : [];
                                    $totalTaxRate = 0;
                                    foreach ($taxIds as $taxId) {
                                        if (!empty($taxData[$taxId]['rate'])) {
                                            $totalTaxRate += (float)$taxData[$taxId]['rate'];
                                        }
                                    }
                                    $displayItems = isset($groupedItems) ? $groupedItems : $saleOrder->items;
                                @endphp
                                @forelse($displayItems as $index => $item)
                                    @php
                                        $qtyForTotal = (float)($item->stock_qty ?? 0);
                                        $itemSubtotal = $item->unit_price * $qtyForTotal;
                                        $itemTax = ($totalTaxRate / 100) * $itemSubtotal;
                                        $itemTotal = $itemSubtotal + $itemTax;
                                        $totalAmount += $itemSubtotal;
                                        $totalTax += $itemTax;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($item->subProduct)
                                                <a href="#" class="text-primary">{{ $item->part_no }}</a>
                                            @else
                                                {{ $item->part_no }}
                                            @endif
                                        </td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td>{{ number_format($item->req_qty, 2) }}</td>
                                        <td>{{ number_format($item->stock_qty, 2) }}</td>
                                        <td>{{ number_format($item->picking_qty ?? 0, 2) }}</td>
                                        <td>{{ number_format($item->packed_qty, 2) }}</td>
                                        <td>
                                            @php
                                                $discrepancy = $item->discrepancy ?? ($item->packed_qty - $item->req_qty);
                                                $discrepancyClass = $discrepancy >= 0 ? 'text-success' : 'text-danger';
                                            @endphp
                                            <span class="{{ $discrepancyClass }}">{{ number_format($discrepancy, 2) }}</span>
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($item->unit_price) }}</td>
                                        <td>
                                            @if($totalTaxRate > 0)
                                                {{ \Auth::user()->priceFormat($itemTax) }}
                                                <br><small class="text-muted">({{ number_format($totalTaxRate, 2) }}%)</small>
                                            @else
                                                {{ \Auth::user()->priceFormat(0) }}
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($itemTotal) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">{{ __('No items found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="9" class="text-end"><strong>{{ __('Subtotal') }}:</strong></td>
                                    <td colspan="2"><strong>{{ \Auth::user()->priceFormat($totalAmount) }}</strong></td>
                                </tr>
                                @if($totalTaxRate > 0)
                                    <tr>
                                        <td colspan="9" class="text-end"><strong>{{ __('Tax') }} ({{ number_format($totalTaxRate, 2) }}%):</strong></td>
                                        <td colspan="2"><strong>{{ \Auth::user()->priceFormat($totalTax) }}</strong></td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="9" class="text-end"><strong>{{ __('Total') }}:</strong></td>
                                    <td colspan="2"><strong>{{ \Auth::user()->priceFormat($totalAmount + $totalTax) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pick list conversion no longer uses an assignment modal; conversion happens directly from the action button above. --}}
@endsection

@push('script-page')
<script>
    $(document).ready(function () {
        $('.js-send-pickpack-form').on('submit', function (e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                icon: 'question',
                title: '{{ __("Send For Pick And Pack?") }}',
                text: '{{ __("Are you sure you want to send this sale order to Pick & Pack?") }}',
                showCancelButton: true,
                confirmButtonText: '{{ __("Yes, send") }}',
                cancelButtonText: '{{ __("Cancel") }}',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
