<form method="POST" action="{{ route('productstock.update', $productService->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="Product" class="form-label">{{ __('Product') }}</label><br>
                {{ $productService->name }}
            </div>
            <div class="form-group col-md-6">
                <label for="Product" class="form-label">{{ __('SKU') }}</label><br>
                {{ $productService->sku }}
            </div>
            <div class="form-group col-md-12">
                <label for="quantity" class="form-label">{{ __('Quantity') }}</label><span class="text-danger">*</span>
                <input type="number" name="quantity" value="" class="form-control" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
    </div>
</form>
