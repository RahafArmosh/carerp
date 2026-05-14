@extends('layouts.admin')

@section('page-title')
    <a href="{{ route('quotations.edit', $quotation->id) }}">
        {{ __('Edit Quotation') }} {{ $quotation->quotation_no }}
    </a>
@endsection

@section('content')

<form id="quotation-edit-form" action="{{ route('quotations.quotations_part_manual_update', $quotation->id) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

<input type="hidden" name="inline_edit" value="1">

<div class="card">
    <div class="card-body table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Available</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
            @foreach($quotation->items->where('parent_id', null) as $item)
              
                @if ($item->form_state != 'out of system')
                    <tr class="table-primary">
                        <td>{{ $item->productService->name }}</td>
                        <td>{{ $item->productService->sku }}</td>
                        <td>
                            <input type="number"
                                value="{{ $item->re_quantity }}"
                                min="1"
                                class="form-control item-input"
                                data-id="{{ $item->id }}"
                                readonly>
                            {{-- Hidden flag to mark edited --}}
                            <input type="hidden"
                                class="edited-flag"
                                data-id="{{ $item->id }}"
                                value="0">
                        </td>
                        <td>{{ $item->av_quantity }}</td>
                        <td>{{ Auth::user()->priceFormat($item->unit_price) }}</td>
                    </tr>
                @else
                     <tr class="table-danger">
                        <td>
                            <span class="badge bg-danger ms-2">{{ $item->partnumber }} {{ __('Not in Our System') }} </span>
                        </td>
                        
                        <td>{{ $item->partnumber }}</td>
                        <td>
                            <input type="number"
                                value="{{ $item->re_quantity }}"
                                min="1"
                                class="form-control item-input"
                                data-id="{{ $item->id }}"
                                readonly>
                            {{-- Hidden flag to mark edited --}}
                            <input type="hidden"
                                class="edited-flag"
                                data-id="{{ $item->id }}"
                                value="0">
                        </td>
                        <td>{{ $item->av_quantity }}</td>
                        <td>{{ Auth::user()->priceFormat($item->unit_price) }}</td>
                    </tr>
                    
                @endif

                @foreach($quotation->items->where('parent_id', $item->id) as $alt)
                <tr>
                    <td class="ps-4">↳ {{ $alt->productService->name }} <span class="badge bg-warning">Alt</span></td>
                    <td>{{ $alt->productService->sku }}</td>
                    <td>
                        <input type="number"
                               value="{{ $alt->re_quantity }}"
                               min="0"
                               class="form-control item-input"
                               data-id="{{ $alt->id }}"
                               readonly>
                        <input type="hidden"
                               class="edited-flag"
                               data-id="{{ $alt->id }}"
                               value="0">
                    </td>
                    <td>{{ $alt->av_quantity }}</td>
                    <td>{{ Auth::user()->priceFormat($alt->unit_price) }}</td>
                </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 text-end">
    <button class="btn btn-primary">{{ __('Update Quotation') }}</button>
</div>
</form>

@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function(){

    // Enable field when clicked/focused
    document.querySelectorAll('.item-input').forEach(function(input){
        input.addEventListener('focus', enableEdit);
        input.addEventListener('mousedown', enableEdit);

        function enableEdit(){
            const id = input.dataset.id;
            input.readOnly = false;

            // mark edited
            const flag = document.querySelector('.edited-flag[data-id="'+id+'"]');
            if(flag) flag.value = 1;

            // assign name for backend
            input.name = 'items['+id+'][qty]';
            flag.name = 'edited_items['+id+']';
        }
    });

    // On submit, remove all unedited items
    const form = document.getElementById('quotation-edit-form');
    form.addEventListener('submit', function(){
        document.querySelectorAll('.item-input').forEach(function(input){
            const id = input.dataset.id;
            const flag = document.querySelector('.edited-flag[data-id="'+id+'"]');
            if(flag && flag.value != "1"){
                // remove name so it won't be sent
                input.removeAttribute('name');
                flag.removeAttribute('name');
            }
        });
    });

});
</script>
@endpush
