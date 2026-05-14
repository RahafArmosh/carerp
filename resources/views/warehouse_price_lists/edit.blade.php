<form method="POST" action="{{ route('warehouse-price-list.update', $entry->id) }}">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Warehouse') }}</label>
                <select class="form-control select" name="warehouse_id" required>
                    @foreach($warehouses as $id => $name)
                        <option value="{{ $id }}" {{ $entry->warehouse_id == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Product') }}</label>
                <select class="form-control select" name="productservice_id" required>
                    @foreach($products as $id => $name)
                        <option value="{{ $id }}" {{ $entry->productservice_id == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Sale Price') }}</label>
                <input type="number" step="0.01" class="form-control" name="sale_price"
                       value="{{ $entry->sale_price }}" required>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
