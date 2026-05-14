@extends('layouts.admin')
@section('page-title')
    {{ __('Warehouse') }}
@endsection
@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('edit warehouse')
            <a href="{{ route('warehouse.stock-count-imports.index') }}" class="btn btn-sm btn-outline-secondary me-2"
                data-bs-toggle="tooltip" title="{{ __('Stock count import history') }}">
                <i class="ti ti-history"></i>
            </a>
        @endcan
        <a href="#" data-size="lg" data-url="{{ route('warehouse.stock-count.import') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Import Stock Count from Excel') }}" data-title="{{ __('Import Stock Count from Excel') }}"
            class="btn btn-sm btn-success me-2">
            <i class="ti ti-file-import"></i> {{ __('Import Stock Count') }}
        </a>

        <a href="#" data-size="lg" data-url="{{ route('warehouse.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create Warehouse') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.account_setup')
        </div>
        <div class="col-9">
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Address') }}</th>
                                    <th>{{ __('City') }}</th>
                                    <th>{{ __('Zip Code') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($warehouses as $warehouse)
                                    <tr class="font-style">
                                        <td>{{ $warehouse->id }}</td>
                                        <td>{{ $warehouse->name }}</td>
                                        <td>{{ $warehouse->address }}</td>
                                        <td>{{ $warehouse->city }}</td>
                                        <td>{{ $warehouse->city_zip }}</td>
                                        <td>{{ $warehouse->tax ? $warehouse->tax->name : '-' }}</td>

                                        @if (Gate::check('show warehouse') || Gate::check('edit warehouse') || Gate::check('delete warehouse'))
                                            <td class="Action">
                                                @can('show warehouse')
                                                    <div class="action-btn bg-warning ms-2">

                                                        <a href="{{ route('warehouse.show', $warehouse->id) }}"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('View') }}"><i
                                                                class="ti ti-eye text-white"></i></a>

                                                    </div>
                                                @endcan
                                                @can('edit warehouse')
                                                    <div class="action-btn bg-success ms-2">
                                                        <a href="{{ route('warehouse.stock-count', $warehouse->id) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Stock Count') }}">
                                                            <i class="ti ti-clipboard-list text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn bg-secondary ms-2">
                                                        <a href="{{ route('warehouse.stock-count-imports.index', ['warehouse_id' => $warehouse->id]) }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ __('Stock count import history') }}">
                                                            <i class="ti ti-history text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                            data-url="{{ route('warehouse.edit', $warehouse->id) }}"
                                                            data-ajax-popup="true" data-size="lg " data-bs-toggle="tooltip"
                                                            title="{{ __('Edit') }}"
                                                            data-title="{{ __('Edit Warehouse') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete warehouse')
                                                    <div class="action-btn {{ ($subProductCounts[$warehouse->id] ?? 0) > 0 ? 'bg-secondary' : 'bg-danger' }} ms-2">
                                                        @if (($subProductCounts[$warehouse->id] ?? 0) > 0)
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" 
                                                                title="{{ __('Cannot delete: :count sub-product(s) associated', ['count' => $subProductCounts[$warehouse->id]]) }}"
                                                                style="pointer-events: none; cursor: not-allowed;">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        @else
                                                            <form id="delete-form-{{ $warehouse->id }}"
                                                                action="{{ route('warehouse.destroy', $warehouse->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endcan
                                            </td>
                                        @endif
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
