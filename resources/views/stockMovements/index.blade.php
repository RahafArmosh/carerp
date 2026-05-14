 @extends('layouts.admin')
 @section('page-title')
     {{ __('Manage Stock Movements') }}
 @endsection
 @push('script-page')
 @endpush
 @section('breadcrumb')
     <li class="breadcrumb-item">
         <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
     </li>
     <li class="breadcrumb-item">{{ __('Stock Movements') }}</li>
 @endsection
 @section('action-btn')
     <div class="float-end">
         {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a> --}}
         {{-- <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
             class="btn btn-sm btn-primary">
             <i class="ti ti-file-export"></i>
         </a> --}}

         {{-- <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
             data-bs-toggle="tooltip" title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-plus"></i>
         </a> --}}

     </div>
 @endsection

 @section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 show" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('stock_movements.index') }}" method="GET" id="stock_movement_filter">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="barcode" class="form-label">{{ __('Barcode / Product No') }}</label>
                                    <input type="text" name="barcode" id="barcode" class="form-control" 
                                           value="{{ request('barcode') }}" 
                                           placeholder="{{ __('Enter barcode or product number') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="brand_id" class="form-label">{{ __('Brand') }}</label>
                                    <select name="brand_id" class="form-control select2">
                                        <option value="">{{ __('All Brands') }}</option>
                                        @foreach ($brands ?? [] as $id => $name)
                                            <option value="{{ $id }}" {{ request('brand_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="product_id" class="form-label">{{ __('Product') }}</label>
                                    <select name="product_id" class="form-control select2">
                                        <option value="">{{ __('All Products') }}</option>
                                        @foreach ($products as $id => $name)
                                            <option value="{{ $id }}" {{ request('product_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="activity" class="form-label">{{ __('Activity') }}</label>
                                    <select name="activity" class="form-control select2">
                                        <option value="">{{ __('All Activities') }}</option>
                                        <option value="PURCHASE" {{ request('activity') == 'PURCHASE' ? 'selected' : '' }}>{{ __('Purchase') }}</option>
                                        <option value="SALES" {{ request('activity') == 'SALES' ? 'selected' : '' }}>{{ __('Sales') }}</option>
                                        <option value="Sale via POS" {{ request('activity') == 'Sale via POS' ? 'selected' : '' }}>{{ __('Sale via POS') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" class="form-control select2">
                                        <option value="">{{ __('All Customers') }}</option>
                                        @foreach ($customers as $id => $name)
                                            <option value="{{ $id }}" {{ request('customer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                                    <select name="vender_id" class="form-control select2">
                                        <option value="">{{ __('All Vendors') }}</option>
                                        @foreach ($vendors as $id => $name)
                                            <option value="{{ $id }}" {{ request('vender_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                                    <select name="bill_id" class="form-control select2">
                                        <option value="">{{ __('All Bills') }}</option>
                                        @foreach ($bills as $bill)
                                            <option value="{{ $bill->id }}" {{ request('bill_id') == $bill->id ? 'selected' : '' }}>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="invoice_id" class="form-label">{{ __('Invoice') }}</label>
                                    <select name="invoice_id" class="form-control select2">
                                        <option value="">{{ __('All Invoices') }}</option>
                                        @foreach ($invoices as $invoice)
                                            <option value="{{ $invoice->id }}" {{ request('invoice_id') == $invoice->id ? 'selected' : '' }}>{{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">{{ __('Date From') }}</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">{{ __('Date To') }}</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="per_page" class="form-label">{{ __('Per Page') }}</label>
                                    <select name="per_page" class="form-control">
                                        <option value="25" {{ request('per_page', 50) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 50) == 100 ? 'selected' : '' }}>100</option>
                                        <option value="200" {{ request('per_page', 50) == 200 ? 'selected' : '' }}>200</option>
                                        <option value="500" {{ request('per_page', 50) == 500 ? 'selected' : '' }}>500</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('stock_movements.index') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                    <a href="{{ route('stock_movements.export', request()->all()) }}" class="btn btn-success">{{ __('Export to Excel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <div class="row">
         <div class="col-xl-12">
             <div class="card">
                 <div class="card-body table-border-style">
                     <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Sub Product') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Activity') }}</th>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('POS') }}</th>
                                    <th>{{ __('Qty In') }}</th>
                                    <th>{{ __('Qty Out') }}</th>
                                    <th>{{ __('Cost Price') }}</th>
                                    <th>{{ __('Avg Cost') }}</th>
                                    <th>{{ __('Sold Price') }}</th>
                                    <th>{{ __('Vendor/Customer') }}</th>
                                    <th>{{ __('Created By') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockMovements as $item)
                                    <tr class="font-style">
                                        <td>{{ \Auth::user()->dateFormat($item->created_at) }}</td>
                                        <td>
                                            @if($item->product)
                                                @php
                                                    $parts = [];
                                                    
                                                    // Add category
                                                    if ($item->product->category && $item->product->category->name) {
                                                        $parts[] = $item->product->category->name;
                                                    }
                                                    
                                                    // Add brand
                                                    if ($item->product->brand && $item->product->brand->name) {
                                                        $parts[] = $item->product->brand->name;
                                                    }
                                                    
                                                    // Add sub-brand
                                                    if ($item->product->subBrand && $item->product->subBrand->name) {
                                                        $parts[] = $item->product->subBrand->name;
                                                    }
                                                    
                                                    // Add product name
                                                    if ($item->product->name) {
                                                        $parts[] = $item->product->name;
                                                    }
                                                    
                                                    $displayText = !empty($parts) ? implode(' / ', $parts) : __('N/A');
                                                @endphp
                                                {{ $displayText }}
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->product && !empty($item->product->sku))
                                                {{ $item->product->sku }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $subProduct = $item->Subproduct ?? null;
                                                // If relationship not loaded, try to fetch by ID
                                                if (!$subProduct && $item->sub_product_id) {
                                                    $subProduct = \App\Models\SubProduct::find($item->sub_product_id);
                                                }
                                            @endphp
                                            @if($subProduct)
                                                @if($subProduct->chassis_no)
                                                    {{ $subProduct->chassis_no }}
                                                @else
                                                    <span class="text-muted">{{ __('Sub Product #') . $item->sub_product_id }}</span>
                                                @endif
                                            @elseif($item->sub_product_id)
                                                <span class="text-muted">{{ __('Sub Product #') . $item->sub_product_id }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $subProduct = $item->Subproduct ?? null;
                                                // If relationship not loaded, try to fetch by ID
                                                if (!$subProduct && $item->sub_product_id) {
                                                    $subProduct = \App\Models\SubProduct::with('warehouse')->find($item->sub_product_id);
                                                }
                                            @endphp
                                            @if($subProduct && $subProduct->warehouse)
                                                {{ $subProduct->warehouse->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->activity)
                                                @php
                                                    $badgeClass = 'bg-secondary';
                                                    if (strpos($item->activity, 'Purchase') !== false || strpos($item->activity, 'Profit') !== false) {
                                                        $badgeClass = 'bg-success';
                                                    } elseif (strpos($item->activity, 'Sale') !== false || strpos($item->activity, 'Loss') !== false || strpos($item->activity, 'Return') !== false) {
                                                        $badgeClass = 'bg-primary';
                                                    }
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ $item->activity }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->bill_id != null)
                                               @php
                                                   // Try to get bill with trashed if not loaded
                                                   $bill = $item->bill;
                                                   if (!$bill && $item->bill_id) {
                                                       $bill = \App\Models\Bill::withTrashed()->find($item->bill_id);
                                                   }
                                               @endphp
                                               @if ($bill)
                                                <a href="{{ route('bill.show', \Crypt::encrypt($item->bill_id)) }}"
                                                    class="btn btn-outline-primary btn-sm">{{ \Auth::user()->billNumberFormat($bill->bill_id) }}</a>
                                               @else
                                                    <!-- Bill was deleted, show the stored bill_id -->
                                                    <span class="text-danger">{{ \Auth::user()->billNumberFormat($bill->bill_id) }}</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->invoice_id != null)
                                               @if ($item->invoice)
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($item->invoice_id)) }}"
                                                    class="btn btn-outline-primary btn-sm">{{ \Auth::user()->invoiceNumberFormat($item->invoice->invoice_id) }}</a>
                                               @else
                                                    <!-- Invoice was deleted, show the stored invoice_id -->
                                                    <span class="text-danger">{{ "#INVO" . sprintf("%05d", $item->invoice_id) }}</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->pos_id != null)
                                               @php
                                                   // Try to get POS with trashed if not loaded
                                                   $pos = $item->pos;
                                                   if (!$pos && $item->pos_id) {
                                                       $pos = \App\Models\Pos::withTrashed()->find($item->pos_id);
                                                   }
                                               @endphp
                                               @if ($pos)
                                                <a href="{{ route('pos.show', \Crypt::encrypt($item->pos_id)) }}"
                                                    class="btn btn-outline-primary btn-sm">{{ \Auth::user()->posNumberFormat($pos->pos_id) }}</a>
                                               @else
                                                    <!-- POS was deleted, show the stored pos_id -->
                                                    <span class="text-danger">{{ "#POS" . sprintf("%05d", $item->pos_id) }}</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->qty_in > 0)
                                                <span class="text-success">{{ $item->qty_in }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->qty_out > 0)
                                                <span class="text-danger">{{ $item->qty_out }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->cost_price)
                                                {{ \Auth::user()->priceFormat($item->cost_price) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->avg_cost)
                                                {{ \Auth::user()->priceFormat($item->avg_cost) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($item->sold_price) && $item->sold_price > 0)
                                                {{ \Auth::user()->priceFormat($item->sold_price) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $isPurchase = strpos($item->activity, 'Purchase') !== false || strpos($item->activity, 'Profit') !== false;
                                                $isSale = strpos($item->activity, 'Sale') !== false || strpos($item->activity, 'Loss') !== false || strpos($item->activity, 'Return') !== false;
                                            @endphp
                                            @if($isPurchase && $item->vendor)
                                                <span class="badge bg-info">{{ $item->vendor->name }}</span>
                                            @elseif($isSale && $item->customer)
                                                <span class="badge bg-info">{{ $item->customer->name }}</span>
                                            @elseif($item->use_id)
                                                <span class="text-muted">{{ __('ID: ') . $item->use_id }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->user)
                                                {{ $item->user->name }}
                                            @else
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                     </div>
                     <!-- Pagination -->
                     @if ($stockMovements->hasPages())
                         <div class="d-flex justify-content-between align-items-center mt-3">
                             <div>
                                 <p class="mb-0">
                                     @if ($stockMovements->total() > 0)
                                         {{ __('Showing') }} {{ $stockMovements->firstItem() }} {{ __('to') }} {{ $stockMovements->lastItem() }} 
                                         {{ __('of') }} {{ $stockMovements->total() }} {{ __('results') }}
                                     @else
                                         {{ __('No results found') }}
                                     @endif
                                 </p>
                             </div>
                             <div>
                                 {{ $stockMovements->appends(request()->query())->links() }}
                             </div>
                         </div>
                     @endif
                 </div>
             </div>
         </div>
     </div>
@endsection
