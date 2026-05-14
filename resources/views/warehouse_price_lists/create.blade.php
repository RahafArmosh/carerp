<form method="POST" action="{{ route('warehouse-price-list.store') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Warehouse') }}</label>
                <select name="warehouse_id" class="form-control select" required>
                    <option value="" disabled selected>{{ __('Select Warehouse') }}</option>
                    @foreach ($warehouses as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach

                </select>
            </div>

            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Product / Service') }}</label>
                <select name="productservice_id" class="form-control select" required>
                    <option value="" disabled selected>{{ __('Select Product') }}</option>
                    @foreach ($products as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-12">
                <label class="form-label">{{ __('Sale Price') }}</label>
                <input type="number" step="0.01" name="sale_price" class="form-control" required>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
    </div>
</form>
