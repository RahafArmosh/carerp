@extends('layouts.admin')

@section('page-title')
    {{ __('Refund') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create POS Refund') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-url="{{ route('pricelist.create') }}" data-size="lg"
            data-ajax-popup="true" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Price Rule') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
                <form method="POST" action="{{ route('pos_product_refund.store_products_refund') }}">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="Pos_id" class="form-label">{{ __('POS') }}</label>
                                <select name="Pos_id" id="Pos_id" class="form-control select2" required>
                                    <option value="">Select POS</option>
                                    @foreach ($poss as $item)
                                        <option value="{{ $item->id }}">{{ $item->id }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal" onclick="history.back()">
                        <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
                    </div>
                </form>
        </div>
    </div>
@endsection
