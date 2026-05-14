@extends('layouts.admin')

@section('page-title')
    {{ __('Combo Offers') }}
@endsection

@push('script-page')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Combo Offers') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('combo_offers.create') }}" data-size="lg"
            data-ajax-popup="true" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Combo Offer') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Sub Brand') }}</th>
                                    <th>{{ __('Products') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Buy') }}</th>
                                    <th>{{ __('Get') }}</th>
                                    <th>{{ __('Tiered Prices') }}</th>
                                    <th>{{ __('Valid Until') }}</th>
                                    <th>{{ __('Active') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($comboOffers as $offer)
                                    <tr>
                                        <td>{{ $offer->warehouse->name ?? '-' }}</td>
                                        <td>{{ $offer->brand->name ?? '-' }}</td>
                                        <td>{{ $offer->subBrand->name ?? '-' }}</td>
                                        <td>
                                            @if($offer->products->count() > 0)
                                                @php
                                                    $products = $offer->products;
                                                    $displayedProducts = $products->take(5);
                                                    $remainingProducts = $products->skip(5);
                                                @endphp
                                                @foreach($displayedProducts as $product)
                                                    <span class="badge bg-primary">{{ $product->name }}</span>
                                                @endforeach
                                                @if($remainingProducts->count() > 0)
                                                    <span class="badge bg-secondary" 
                                                          data-bs-toggle="tooltip" 
                                                          data-bs-placement="top" 
                                                          title="{{ $remainingProducts->pluck('name')->implode(', ') }}">
                                                        +{{ $remainingProducts->count() }} {{ __('more') }}
                                                    </span>
                                                @endif
                                            @else
                                                {{ $offer->productService->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($offer->type) }}</td>
                                        <td>{{ $offer->buy_quantity ?? '-' }}</td>
                                        <td>{{ $offer->get_quantity ?? '-' }}</td>
                                        <td>
                                            @if ($offer->tiered_price)
                                              {{ $offer->tiered_price }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $offer->valid_until ? $offer->valid_until->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            @if ($offer->active)
                                                <span class="badge bg-success">{{ __('Yes') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $offer->created_at->format('Y-m-d') }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('combo_offers.edit', $offer->id) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Combo Offer') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form
                                                        action="{{ route('combo_offers.destroy', $offer->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}" data-original-title="{{ __('Delete') }}" data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}" data-confirm-yes="document.getElementById('delete-form-{{ $offer->id}}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                    </form>
                                                    {{-- <div class="action-btn bg-danger ms-2">
                                                        <form action="{{ route('product-category.destroy', $category->id) }}" method="POST" id="delete-form-{{ $category->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}" data-original-title="{{ __('Delete') }}" data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}" data-confirm-yes="document.getElementById('delete-form-{{ $category->id }}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        </form>
                                                    </div> --}}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-3">
                            {{ $comboOffers->links() }} {{-- Pagination --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
