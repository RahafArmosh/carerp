@extends('layouts.admin')
@section('page-title')
    {{__('Manage Product Stock')}}
@endsection
@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Product Stock')}}</li>
@endsection
@section('action-btn')
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">s
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Sku') }}</th>
                                    <th>{{ __('Current Quantity') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($productServices as $productService)
                                    <tr class="font-style">
                                        @php
                                            $nameParts = [];
                                            if ($productService->brand && $productService->brand->name) {
                                                $nameParts[] = $productService->brand->name;
                                            }
                                            if ($productService->subBrand && $productService->subBrand->name) {
                                                $nameParts[] = $productService->subBrand->name;
                                            }
                                            if ($productService->name) {
                                                $nameParts[] = $productService->name;
                                            }
                                            $displayName = !empty($nameParts) ? implode('/', $nameParts) : ($productService->name ?? 'N/A');
                                        @endphp
                                        <td>{{ $displayName }}</td>
                                        <td>{{ $productService->sku }}</td>
                                        <td>{{ $productService->quantity }}</td>

                                        <td class="Action">
                                            <div class="action-btn bg-info ms-2">
                                                <a data-size="md" href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center" data-url="{{ route('productstock.edit', $productService->id) }}" data-ajax-popup="true"  data-size="xl" data-bs-toggle="tooltip" title="{{__('Update Quantity')}}">
                                                    <i class="ti ti-plus text-white"></i>
                                                </a>
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
