<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{{ __('Source') }}</th>
                <th>{{ __('Document Number') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Product No') }}</th>
                <th>{{ __('Sub Product ID') }}</th>
                <th>{{ __('Quantity') }}</th>
                <th>{{ __('Base Price') }}</th>
                <th>{{ __('Discount') }}</th>
                <th>{{ __('Actual Selling Price') }}</th>
                <th>{{ __('Warehouse') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                @php
                    // Calculate actual selling price
                    $actualPrice = $product->actual_price ?? 0;
                    
                    // For POS: if combo exists, show combo info
                    $hasCombo = false;
                    $comboInfo = '';
                    if ($product->source_type == 'POS' && $product->compo_id && $product->compo_id != 0 && $product->compo_id != '0') {
                        $hasCombo = true;
                        $comboInfo = ' (Combo)';
                    }
                @endphp
                <tr>
                    <td>
                        @if($product->source_type == 'POS')
                            <span class="badge bg-info">{{ __('POS') }}</span>
                        @else
                            <span class="badge bg-warning">{{ __('Invoice') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($product->source_type == 'POS')
                            <strong>{{ Auth::user()->posNumberFormat($product->pos_number) }}</strong>
                        @else
                            <strong>{{ Auth::user()->invoiceNumberFormat($product->invoice_number) }}</strong>
                        @endif
                    </td>
                    <td>
                        @if($product->source_type == 'POS')
                            {{ Auth::user()->dateFormat($product->pos_date) }}
                        @else
                            {{ Auth::user()->dateFormat($product->invoice_date) }}
                        @endif
                    </td>
                    <td><strong>{{ $product->product_no }}</strong></td>
                    <td>{{ $product->sub_product_id }}</td>
                    <td><span class="badge bg-primary">{{ number_format($product->quantity, 2) }}</span></td>
                    <td>
                        @if($hasCombo && $product->combo_price !== null)
                            <span class="text-muted">{{ Auth::user()->priceFormat($product->price) }}</span>
                            <br><small class="text-info">{{ Auth::user()->priceFormat($product->combo_price) }} {{ __('(Combo)') }}</small>
                        @else
                            {{ Auth::user()->priceFormat($product->price) }}
                        @endif
                    </td>
                    <td>{{ $product->discount }}%</td>
                    <td>
                        <strong class="text-success">{{ Auth::user()->priceFormat($actualPrice) }}</strong>
                        @if($hasCombo)
                            <br><small class="text-muted">{{ __('After combo & discount') }}</small>
                        @else
                            <br><small class="text-muted">{{ __('After discount') }}</small>
                        @endif
                    </td>
                    <td>{{ $product->warehouse_name ?? '-' }}</td>
                    <td>
                        @if($product->source_type == 'POS')
                            <a href="{{ route('pos.show', \Crypt::encrypt($product->pos_id)) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye"></i> {{ __('View POS') }}
                            </a>
                        @else
                            <a href="{{ route('invoice.show', \Crypt::encrypt($product->invoice_id)) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye"></i> {{ __('View Invoice') }}
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">
                        <p class="text-muted">{{ __('No sold sub-products found for this product') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(count($products) > 0)
    <div class="mt-3">
        <div class="row">
            <div class="col-md-4">
                <strong>{{ __('Total Quantity Sold:') }}</strong> 
                <span class="badge bg-success">{{ number_format($products->sum('quantity'), 2) }}</span>
            </div>
            <div class="col-md-4">
                <strong>{{ __('Average Cost:') }}</strong> 
                @if(isset($avgCost) && $avgCost > 0)
                    <span class="badge bg-secondary">{{ Auth::user()->priceFormat($avgCost) }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </div>
            <div class="col-md-4 text-end">
                <strong>{{ __('Total Items:') }}</strong> 
                <span class="badge bg-info">{{ count($products) }}</span>
            </div>
        </div>
    </div>
@endif
