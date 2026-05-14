@extends('layouts.admin')
@section('page-title')
    {{ __('Stock Movement Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Stock Movement Report') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('subproduct.stock_movement_report') }}" method="GET" id="stock_movement_report_form">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="activity" class="form-label">{{ __('Activity') }}</label>
                                    <select name="activity" class="form-control select2">
                                        <option value="">{{ __('All Activities') }}</option>
                                        <option value="PURCHASE" {{ request('activity') == 'PURCHASE' ? 'selected' : '' }}>{{ __('Purchase') }}</option>
                                        <option value="SALES" {{ request('activity') == 'SALES' ? 'selected' : '' }}>{{ __('Sales') }}</option>
                                    </select>
                                </div>
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
                                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                    <a href="{{ route('subproduct.stock_movement_report') }}" class="btn btn-danger">{{ __('Reset') }}</a>
                                    <a href="{{ route('subproduct.stock_movement_report.export', request()->all()) }}" class="btn btn-success">{{ __('Export to Excel') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('DATE') }}</th>
                                    <th>{{ __('ACTIVITY') }}</th>
                                    <th>{{ __('PRODUCT') }}</th>
                                    <th>{{ __('ITEM') }}</th>
                                    <th>{{ __('WAREHOUSE') }}</th>
                                    <th>{{ __('CUSTOMER/SUPPLIER') }}</th>
                                    <th>{{ __('QTY IN') }}</th>
                                    <th>{{ __('QTY OUT') }}</th>
                                    <th>{{ __('STOCK') }}</th>
                                    <th>{{ __('UNIT VALUE') }}</th>
                                    <th>{{ __('TOTAL VALUE') }}</th>
                                    <th>{{ __('SOLD PRICE') }}</th>
                                    <th>{{ __('BILL') }}</th>
                                    <th>{{ __('INVOICE') }}</th>
                                    <th>{{ __('ASN') }}</th>
                                    <th>{{ __('GRN') }}</th>
                                    <th>{{ __('SALES ORDER') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paginatedMovements ?? $stockMovements as $movement)
                                    <tr class="font-style">
                                        <td>{{ \Carbon\Carbon::parse($movement->created_at)->format('Y-m-d') }}</td>
                                        <td>
                                            @php
                                                $badgeClass = 'bg-secondary';
                                                if ($movement->activity) {
                                                    if (strpos($movement->activity, 'Purchase') !== false || strpos($movement->activity, 'Profit') !== false) {
                                                        $badgeClass = 'bg-success';
                                                    } elseif (strpos($movement->activity, 'Sale') !== false || strpos($movement->activity, 'Loss') !== false || strpos($movement->activity, 'Return') !== false) {
                                                        $badgeClass = 'bg-info';
                                                    }
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $movement->activity ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($movement->Subproduct && $movement->Subproduct->productService)
                                                {{ optional($movement->Subproduct->productService->brand)->name ?? '' }}{{ optional($movement->Subproduct->productService->brand) ? '/' : '' }}{{ optional($movement->Subproduct->productService->subBrand)->name ?? '' }}{{ optional($movement->Subproduct->productService->subBrand) ? '/' : '' }}{{ $movement->Subproduct->productService->name ?? 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($movement->Subproduct && $movement->Subproduct->product_no)
                                                {{ $movement->Subproduct->product_no }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($movement->Subproduct && $movement->Subproduct->warehouse)
                                                {{ $movement->Subproduct->warehouse->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $isPurchase = $movement->activity && (strpos($movement->activity, 'Purchase') !== false || strpos($movement->activity, 'Profit') !== false);
                                                $isSale = $movement->activity && (strpos($movement->activity, 'Sale') !== false || strpos($movement->activity, 'Loss') !== false || strpos($movement->activity, 'Return') !== false);
                                            @endphp
                                            @if(isset($movement->customer_supplier))
                                                {{ $movement->customer_supplier }}
                                            @elseif($isSale && isset($movement->customer) && $movement->customer)
                                                {{ $movement->customer->name }}
                                            @elseif($isPurchase && isset($movement->vendor) && $movement->vendor)
                                                {{ $movement->vendor->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $movement->qty_in ?? 0 }}</td>
                                        <td>{{ $movement->qty_out ?? 0 }}</td>
                                        <td><strong>{{ $movement->running_stock ?? 0 }}</strong></td>
                                        <td>{{ \Auth::user()->priceFormat($movement->cost_price ?? 0) }}</td>
                                        <td>
                                            @php
                                                $qty = ($movement->qty_in ?? 0) - ($movement->qty_out ?? 0);
                                                $totalValue = ($movement->cost_price ?? 0) * abs($qty);
                                            @endphp
                                            {{ \Auth::user()->priceFormat($totalValue) }}
                                        </td>
                                        <td>
                                            @if(isset($movement->sold_price) && $movement->sold_price > 0)
                                                {{ \Auth::user()->priceFormat($movement->sold_price) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($movement->bill_id && $movement->bill)
                                                <a href="{{ route('bill.show', \Crypt::encrypt($movement->bill_id)) }}" class="btn btn-outline-primary btn-sm">
                                                    {{ Auth::user()->billNumberFormat($movement->bill->bill_id) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($movement->invoice_id && $movement->invoice)
                                                <a href="{{ route('invoice.show', \Crypt::encrypt($movement->invoice_id)) }}" class="btn btn-outline-primary btn-sm">
                                                    {{ Auth::user()->invoiceNumberFormat($movement->invoice->invoice_id) }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($movement->asn_no))
                                                {{ $movement->asn_no }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($movement->asn_no))
                                                {{ $movement->asn_no }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($movement->invoice && $movement->invoice->order_number)
                                                {{ $movement->invoice->order_number }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-3">
                            @if(isset($paginatedMovements))
                                {{ $paginatedMovements->withQueryString()->links() }}
                            @else
                                {{ $subProducts->withQueryString()->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

