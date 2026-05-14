@extends('layouts.admin')

@section('page-title')
{{ __('Warehouse Stock') }}
@endsection

@push('css-page')
<style>

.warehouse-card{
    transition: all 0.25s ease;
    cursor:pointer;
}

.warehouse-card:hover{
    transform: translateY(-5px);
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

.warehouse-icon{
    width:60px;
    height:60px;
    margin:auto;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:28px;
}

</style>
@endpush
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
</li>
<li class="breadcrumb-item">
    {{ __('Warehouse Stock') }}
</li>
@endsection

@section('content')

<div class="row">
    {{-- WAREHOUSES --}}
    @foreach($warehouses as $warehouse)

    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12">
        <a href="{{ route('master-ledger.stock',['warehouse_id'=>$warehouse->id]) }}" class="text-decoration-none">

            <div class="card warehouse-card text-center">
                <div class="card-body">

                    <div class="warehouse-icon bg-info">
                        <i class="ti ti-building-warehouse"></i>
                    </div>

                    <h5 class="mt-3">
                        {{ $warehouse->name }}
                    </h5>

                    <p class="text-muted">
                        {{ __('Click to view stock') }}
                    </p>

                </div>
            </div>

        </a>
    </div>

    @endforeach

</div>

@endsection