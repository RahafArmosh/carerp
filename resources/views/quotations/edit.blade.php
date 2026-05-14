@extends('layouts.admin')

@section('page-title')
    {{ __('Edit Quotation') }} {{ $quotation->quotation_no }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('quotations.index') }}">{{ __('Quotations') }}</a></li>
<li class="breadcrumb-item">{{ __('Edit Quotation') }}</li>
@endsection


@section('action-btn')
        <div class="float-end action-btn  ms-2"> 
            <a class="action-btn bg-success" href="{{ route('quotations.showexport', $quotation->id) }}" title="Export Excel"> 
                <i class="ti ti-file-export text-white"></i> </a> 
        </div>
        <div class="float-end action-btn bg-danger ms-2"> 
            <a class="btn btn-sm btn-danger" href="{{ route('quotations.export.pdf', $quotation->id) }}"  title="Export PDF"> 
                <i class="ti ti-file-export text-white"></i> </a> 
        </div>
        <div class="float-end action-btn bg-info ms-2"> 
            {{-- <a href="{{ route('quotations.convert_to_sale_order', $quotation->id) }}" title="Convert to SO"> 
                <i class="ti ti-refresh text-white"></i> </a>  --}}
            <a href="#" data-size="lg" data-url="{{ route('quotations.quotation2saleorder', $quotation->id) }}" data-ajax-popup="true"
                    data-bs-toggle="tooltip" title="{{ __('Convert') }}" data-title="{{ __('Convert to SO ') }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-refresh text-white"></i>
            </a>
        </div>

@endsection
@section('content')

{{-- Edit Quotation Info --}}
<form id="edit-quotation-form" action="{{ route('quotations.update', $quotation->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">

                {{-- Customer --}}
                <div class="col-md-4">
                    <label>{{ __('Customer') }}</label>
                    <select name="customer_id" class="form-control" required>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ $quotation->customer_id == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Delivery Location --}}
                <div class="col-md-4">
                    <label>{{ __('Delivery Location') }}</label>
                    <input type="text" name="delivery_location" class="form-control"
                           value="{{ $quotation->delivery_location }}">
                </div>

                {{-- Warehouse --}}
                <div class="col-md-2">
                    <label>{{ __('Warehouse') }}</label>
                    <select name="warehouse_id" class="form-control" required disabled>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}"
                                {{ $quotation->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Price Group --}}
                <div class="col-md-2">
                    <label>{{ __('Price Group') }}</label>
                    <select name="price_group" class="form-control" disabled>
                        <option value="">{{ __('Select Price Group') }}</option>
                        @foreach(\App\Models\PricingListType::all() as $group)
                            <option value="{{ $group->id }}"
                                {{ $quotation->price_group == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tax --}}
                <div class="col-md-4 mt-3">
                    <label>{{ __('Tax') }}</label>
                    <select name="tax_id" class="form-control" disabled>
                        <option value="">{{ __('Select Tax') }}</option>
                        @foreach(\App\Models\Tax::all() as $tax)
                            <option value="{{ $tax->id }}"
                                {{ $quotation->tax_id == $tax->id ? 'selected' : '' }}>
                                {{ $tax->name }} ({{ $tax->rate }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">{{ __('Update Quotation Info') }}</button>
    </div>
</form>

<hr>

{{-- Quotation Items --}}
<div class="mt-4">
    <h5>{{ __('Update Quotation Items') }}</h5>
    <button id="manual-btn" class="btn btn-success">{{ __('Edit Manual Items') }}</button>
    <button id="xlsx-btn" class="btn btn-info">{{ __('Upload XLSX') }}</button>
</div>

{{-- Manual Items Table --}}
<div class="card mt-3" id="manual-items-container">
    <div class="card-body table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Item') }}</th>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Quantity') }}</th>
                    <th>{{ __('Available') }}</th>
                    <th>{{ __('Price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quotation->items->whereNotIn('form_state', ['out of system']) as $item)
                    <tr>
                        <td>{{ $item->productService->name }}</td>
                        <td>{{ $item->productService->sku }}</td>
                        <td>
                            <input type="number" name="items[{{ $item->id }}][re_quantity]"
                                   value="{{ $item->re_quantity }}" min="0"
                                   class="form-control item-input" data-id="{{ $item->id }}">
                        </td>
                        <td>{{ $item->av_quantity }}</td>
                        <td>{{ number_format($item->unit_price,2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- XLSX Upload Modal --}}
<div class="modal fade" id="xlsxModal" tabindex="-1" aria-labelledby="xlsxModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">{{ __('Upload Quotation XLSX') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <a href="{{ route('quotations.export',$quotation) }}" class="btn btn-outline-primary mb-3">{{ __('Download Quoattion in XLSX') }}</a>
            <form id="xlsx-upload-form" action="{{ route('quotations.import', $quotation) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="items_file" class="form-control" accept=".xlsx,.xls" required>
                <button type="submit" class="btn btn-primary mt-2">{{ __('Upload XLSX') }}</button>
            </form>
        </div>
    </div>
  </div>
</div>

@endsection

@push('script-page')
<script>
document.getElementById('manual-btn').addEventListener('click', function(e){
    e.preventDefault();
    // redirect to manual items edit page
    window.location.href = "{{ route('quotations.medit', $quotation->id) }}";
});

document.getElementById('xlsx-btn').addEventListener('click', function(e){
    e.preventDefault();
    // show modal
    var xlsxModal = new bootstrap.Modal(document.getElementById('xlsxModal'));
    xlsxModal.show();
});
</script>
@endpush
