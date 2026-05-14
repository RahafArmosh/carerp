@extends('layouts.admin')
@section('page-title')
    {{ __('Inventory Report') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Inventory Report') }}</li>
@endsection
@section('content')
    <div class="container" style="max-width: 100%">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Products linked to account: {{ $account->name }}</h4>
            <form action="{{ route('accounts.add-stock-movements', $account->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('{{ __('Are you sure you want to add stock movements for all initial stock entries?') }}')">
                    <i class="ti ti-plus"></i> {{ __('Add All Initial Stock to Stock Movements') }}
                </button>
            </form>
        </div>
        <hr>
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Product No') }}</th>
                                <th>{{ __('Sale Price') }}</th>
                                <th>{{ __('Purchase Price') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Initial Stock') }}</th>
                                <th>{{ __('Initial Rate') }}</th>
                                <th>{{ __('Initial Value') }}</th>
                                <th>{{ __('Purchase Status') }}</th>
                                <th>{{ __('Book Status') }}</th>
                                <th>{{ __('Created By') }}</th>
                                @foreach ($customFields as $customField)
                                    <th>{{ __($customField->name) }}</th>
                                @endforeach
                                <th>{{ __('Action') }}</th> 
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($allSubProducts as $sub)
                                @php
                                    $initialValue = ($sub->initial_stock * $sub->initial_rate);
                                    
                                    // Get product service with safe access
                                    $productService = $sub->productService ?? null;
                                    $brandName = $productService && $productService->brand ? $productService->brand->name : '';
                                    $subBrandName = $productService && $productService->subBrand ? $productService->subBrand->name : '';
                                    $productName = $productService ? $productService->name : '';
                                    $sku = $productService ? $productService->sku : '';
                                    
                                    // Get invoice with safe access
                                    $invoice = $sub->invoice ?? null;
                                    $invoiceType = $invoice ? $invoice->type : null;
                                    
                                    // Get user from preloaded attribute
                                    $userName = isset($sub->created_by_user) && $sub->created_by_user ? $sub->created_by_user->name : '-';
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('sub-product.expenses', $sub->id) }}" class="btn btn-outline-primary btn-sm">
                                            {{ $sub->id }}
                                        </a>
                                    </td>
                                    <td>{{ $brandName . ($brandName && $subBrandName ? '/' : '') . $subBrandName . (($brandName || $subBrandName) && $productName ? '/' : '') . $productName }}</td>
                                    <td>{{ $sku }}</td>
                                    <td>{{ $sub->product_no }}</td>
                                    <td>{{ \Auth::user()->priceFormat($sub->sale_price) }}</td>
                                    <td>{{ \Auth::user()->priceFormat($sub->purchase_price) }}</td>
                                    <td>{{ $sub->quantity }}</td>
                                    <td>{{ $sub->initial_stock }}</td>
                                    <td>{{ \Auth::user()->priceFormat($sub->initial_rate) }}</td>
                                    <td>{{ \Auth::user()->priceFormat($initialValue) }}</td>
                                    <td>
                                        @php
                                            $flag = $sub->flag ?? 0;
                                            $flagLabels = [
                                                0 => 'Pending',
                                                1 => 'Purchased',
                                                2 => 'Cancelled',
                                                3 => 'Consignment'
                                            ];
                                        @endphp
                                        {{ $flagLabels[$flag] ?? 'Unknown' }}
                                    </td>
                                    <td>
                                        @if ($sub->booked == 0)
                                            Free
                                        @elseif ($sub->booked == 1 && $sub->invoice_id != null && $invoiceType == 'rent')
                                            Rented
                                        @elseif ($sub->booked == 1 && $sub->invoice_id != null && $invoiceType == 'regular')
                                            Booked
                                        @elseif($sub->booked == 2 && $sub->invoice_id == null)
                                            Sold
                                        @elseif(($sub->booked == 2 && $invoiceType == 'regular') || ($sub->booked == 1 && $sub->pos_id != null))
                                            Sold
                                        @elseif($sub->booked == 2 && $invoiceType == 'rent')
                                            Rented
                                        @else
                                            Delivered
                                        @endif
                                    </td>
                                    <td>{{ $userName }}</td>
                                    @foreach ($customFields as $field)
                                        <td>
                                            {{ isset($customFieldValues[$sub->id]) && isset($customFieldValues[$sub->id][$field->id]) ? $customFieldValues[$sub->id][$field->id] : '' }}
                                        </td>
                                    @endforeach
                                    <td>
                                        <div class="action-btn bg-info ms-2">
                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                data-url="{{ route('sub-product.edit', $sub->id) }}"
                                                data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                title="{{ __('Edit') }}"
                                                data-title="{{ __('Edit Product') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 13 + count($customFields) }}" class="text-center">
                                        {{ __('No sub-products found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="{{ 9 + count($customFields) }}" class="text-end fw-bold">
                                    {{ __('Total Initial Value') }}:
                                </td>
                                <td colspan="4" class="fw-bold">
                                    {{ \Auth::user()->priceFormat($totalStock) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- Pagination -->
                @if ($allSubProducts->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <p class="mb-0">
                                @if ($allSubProducts->total() > 0)
                                    {{ __('Showing') }} {{ $allSubProducts->firstItem() }} {{ __('to') }} {{ $allSubProducts->lastItem() }} 
                                    {{ __('of') }} {{ $allSubProducts->total() }} {{ __('results') }}
                                @else
                                    {{ __('No results found') }}
                                @endif
                            </p>
                        </div>
                        <div>
                            {{ $allSubProducts->links() }}
                        </div>
                    </div>
                @endif
                <!-- Total Stock Display -->
                <div class="mt-3">
                    <p class="fw-bold mb-0">
                        {{ __('Total Initial Value (All Items)') }}: {{ \Auth::user()->priceFormat($totalStock) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
