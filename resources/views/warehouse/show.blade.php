@extends('layouts.admin')
@section('page-title')
    {{__('Warehouse Stock Details')}}
@endsection

@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Warehouse Stock Details')}}</li>
@endsection
@section('action-btn')
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
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Quantity') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($warehouseProducts as $warehouses)
                                <tr class="font-style">
                                    @if(!empty($warehouses->product))
                                        <td>{{ !empty($warehouses->product)? $warehouses->product->name:'' }}</td>
                                        <td>{{ $warehouses->product->getFreeQuantity() }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">{{ __('No products found in this warehouse') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @if(isset($logs))
        @include('partials.pos_logs', ['logs' => $logs])
    @endif

@endsection

