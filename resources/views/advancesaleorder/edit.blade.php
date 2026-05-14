@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Advance Sale Order') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('advance-saleorder.index') }}">{{ __('Advance Sale Orders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('advance-saleorder.update', \Crypt::encrypt($advanceSaleOrder->id)) }}" method="POST" id="advance-sale-order-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Advance SO No') }}</label>
                                    <input type="text" class="form-control" value="{{ \Auth::user()->saleOrderNumberFormat($advanceSaleOrder->advance_sale_order_no) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                                        <option value="">{{ __('Select Customer') }}</option>
                                        @foreach($customers as $id => $name)
                                            <option value="{{ $id }}" {{ $advanceSaleOrder->customer_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sales_order_date" class="form-label">{{ __('Sales Order Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="sales_order_date" id="sales_order_date" class="form-control" value="{{ $advanceSaleOrder->sales_order_date ? \Carbon\Carbon::parse($advanceSaleOrder->sales_order_date)->format('Y-m-d') : '' }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control select2">
                                        <option value="">{{ __('Select Currency') }}</option>
                                        @foreach($currencies as $id => $name)
                                            <option value="{{ $id }}" {{ $advanceSaleOrder->currency_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                    <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.000001" min="0" value="{{ $advanceSaleOrder->exchange_rate ?? 1.0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                    <select name="tax_id[]" id="tax_id" class="form-control select2" multiple>
                                        @php
                                            $selectedTaxIds = $advanceSaleOrder->tax_id ? explode(',', $advanceSaleOrder->tax_id) : [];
                                        @endphp
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}" {{ in_array((string)$tax->id, $selectedTaxIds) ? 'selected' : '' }}>
                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control select2" required>
                                        <option value="draft" {{ $advanceSaleOrder->status == 'draft' ? 'selected' : '' }}>{{ __('CREATED') }}</option>
                                        <option value="approved" {{ $advanceSaleOrder->status == 'approved' ? 'selected' : '' }}>{{ __('APPROVED') }}</option>
                                        <option value="shipped" {{ $advanceSaleOrder->status == 'shipped' ? 'selected' : '' }}>{{ __('SHIPPED') }}</option>
                                        <option value="converted" {{ $advanceSaleOrder->status == 'converted' ? 'selected' : '' }}>{{ __('CONVERTED') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">{{ __('Items') }}</h5>
                            <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                <i class="ti ti-plus"></i> {{ __('Add Item') }}
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">{{ __('#') }}</th>
                                        <th style="width: 150px;">{{ __('Part No') }} <span class="text-danger">*</span></th>
                                        <th style="width: 220px;">{{ __('Description') }}</th>
                                        <th style="width: 100px;">{{ __('REQ QTY') }} <span class="text-danger">*</span></th>
                                        <th style="width: 120px;">{{ __('CONVERTED QTY') }}</th>
                                        <th style="width: 120px;">{{ __('Unit Price') }}</th>
                                        <th style="width: 60px;">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="items-container">
                                    @foreach($advanceSaleOrder->items as $index => $item)
                                        <tr class="item-row">
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td><input type="text" name="items[{{ $index }}][part_no]" class="form-control form-control-sm" value="{{ $item->part_no }}" required></td>
                                            <td><input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $item->description }}"></td>
                                            <td><input type="number" name="items[{{ $index }}][req_qty]" class="form-control form-control-sm req-qty" step="0.01" min="0" value="{{ $item->req_qty }}" required></td>
                                            <td><input type="number" name="items[{{ $index }}][converted_qty]" class="form-control form-control-sm converted-qty" step="0.01" min="0" value="{{ $item->converted_qty ?? 0 }}"></td>
                                            <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm unit-price" step="0.01" min="0" value="{{ $item->unit_price }}"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-item" {{ $index == 0 ? 'style=display:none;' : '' }}>
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('advance-saleorder.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    let itemIndex = {{ $advanceSaleOrder->items->count() }};

    $(document).ready(function() {
        $('.select2').select2();

        $('#add-item').click(function() {
            const newRow = $('#items-table tbody tr').first().clone();
            newRow.find('input[type="text"], input[type="number"]').val('');
            newRow.find('input[name]').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + itemIndex + ']'));
                }
            });
            newRow.find('td:first').text(itemIndex + 1);
            newRow.find('.remove-item').show();
            newRow.find('.converted-qty').val('0');
            $('#items-container').append(newRow);
            itemIndex++;
        });

        $(document).on('click', '.remove-item', function() {
            if ($('#items-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                $('#items-table tbody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }
        });
    });
</script>
@endpush
