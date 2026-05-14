@extends('layouts.admin')

@section('page-title')
    {{ __('Warehouse Price List') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Price List') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="lg" data-url="{{ route('warehouse-price-list.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Add Price Entry') }}"
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
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($priceLists as $entry)
                                    <tr>
                                        <td>{{ $entry->warehouse->name ?? '-' }}</td>
                                        <td>
                                            {{ $entry->productService->name ?? '-' }}<br>
                                            @if($entry->productService?->image)
                                                <img src="{{ asset('storage/uploads/pro_image/' . $entry->productService->image) }}"
                                                    alt="Product Image" style="width: 50px; height: auto;">
                                            @endif
                                        </td>
                                        <td>{{ Auth::user()->priceFormat($entry->sale_price) }}</td>
                                        <td class="Action">
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('warehouse-price-list.edit', $entry->id) }}"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Price Entry') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form id="delete-form-{{ $entry->id }}"
                                                        action="{{ route('warehouse-price-list.destroy', $entry->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
