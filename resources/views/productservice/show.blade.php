@extends('layouts.admin')
@section('page-title')
    {{ __('Product') }} — {{ $productService->name }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productservice.index') }}">{{ __('Product & Services') }}</a></li>
    <li class="breadcrumb-item">{{ __('View') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="{{ route('productservice.index') }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip"
            title="{{ __('Back to list') }}">
            <i class="ti ti-arrow-left"></i> {{ __('Back') }}
        </a>
        <a href="{{ route('productservice.brochure.pdf', $productService->id) }}" class="btn btn-sm btn-outline-primary"
            data-bs-toggle="tooltip" title="{{ __('Download brochure PDF') }}" target="_blank" rel="noopener">
            <i class="ti ti-download"></i> {{ __('Brochure PDF') }}
        </a>
        @can('edit product & service')
            @if ($productService->created_by == \Auth::user()->creatorId())
                <a href="#" class="btn btn-sm btn-primary" data-url="{{ route('productservice.edit', $productService->id) }}"
                    data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                    data-title="{{ __('Edit Product') }}">
                    <i class="ti ti-pencil"></i> {{ __('Edit') }}
                </a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Images') }}</h5>
                </div>
                <div class="card-body">
                    @if (count($galleryItems) > 0)
                        <div class="mb-3 text-center border rounded p-2 bg-light">
                            <a href="{{ $galleryItems[0]['url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $galleryItems[0]['url'] }}" alt="{{ $productService->name }}"
                                    class="img-fluid rounded" style="max-height: 320px; object-fit: contain;">
                            </a>
                            <div class="small text-muted mt-2">{{ $galleryItems[0]['caption'] }}</div>
                        </div>
                        @if (count($galleryItems) > 1)
                            <p class="small text-muted mb-2">{{ __('More images') }}</p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                @foreach (array_slice($galleryItems, 1) as $item)
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                        class="border rounded p-1 d-inline-block bg-white">
                                        <img src="{{ $item['url'] }}" alt=""
                                            style="width: 88px; height: 88px; object-fit: cover;" class="rounded">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-photo-off fs-1 d-block mb-2"></i>
                            {{ __('No images uploaded for this product.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Details') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Name') }}</dt>
                        <dd class="col-sm-8">{{ $productService->name }}</dd>
                        <dt class="col-sm-4">{{ __('SKU') }}</dt>
                        <dd class="col-sm-8">{{ $productService->sku }}</dd>
                        <dt class="col-sm-4">{{ __('Category') }}</dt>
                        <dd class="col-sm-8">{{ optional($productService->category)->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Brand') }}</dt>
                        <dd class="col-sm-8">{{ optional($productService->brand)->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Model') }}</dt>
                        <dd class="col-sm-8">{{ optional($productService->subBrand)->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Unit') }}</dt>
                        <dd class="col-sm-8">{{ optional($productService->unit)->name ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('Type') }}</dt>
                        <dd class="col-sm-8">{{ ucfirst($productService->type) }}</dd>
                        <dt class="col-sm-4">{{ __('Sale price') }}</dt>
                        <dd class="col-sm-8">{{ \Auth::user()->priceFormat($productService->sale_price) }}</dd>
                        <dt class="col-sm-4">{{ __('Purchase price') }}</dt>
                        <dd class="col-sm-8">{{ \Auth::user()->priceFormat($productService->purchase_price) }}</dd>
                        @if (! empty($productService->description))
                            <dt class="col-sm-4">{{ __('Description') }}</dt>
                            <dd class="col-sm-8">{!! nl2br(e($productService->description)) !!}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
