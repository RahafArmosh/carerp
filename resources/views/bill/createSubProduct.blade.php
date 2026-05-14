<form action="{{ route('sub-product-bill.store', ['id' => $id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
<script>
$(document).ready(function() {
    // Initialize select2 when modal is shown
    $(document).on('shown.bs.modal', '.modal', function() {
        var modal = $(this);
        // Initialize select2 for select fields within this modal
        modal.find('.select2').select2({
            dropdownParent: modal
        });
        
        // Handle product change event
        modal.find('select[name="product_id"]').off('change').on('change', function() {
            var productId = $(this).val();
            $.ajax({
                url: '/get-product-prices/' + productId,
                type: 'GET',
                success: function(data) {
                    modal.find('input[name="sale_price"]').val(data.sale_price);
                    modal.find('input[name="purchase_price"]').val(data.purchase_price);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
    
    // Also initialize immediately if modal is already visible (for AJAX-loaded content)
    setTimeout(function() {
        var modal = $('.modal.show, .modal.in').last();
        if (modal.length) {
            modal.find('.select2').select2({
                dropdownParent: modal
            });
            
            modal.find('select[name="product_id"]').off('change').on('change', function() {
                var productId = $(this).val();
                $.ajax({
                    url: '/get-product-prices/' + productId,
                    type: 'GET',
                    success: function(data) {
                        modal.find('input[name="sale_price"]').val(data.sale_price);
                        modal.find('input[name="purchase_price"]').val(data.purchase_price);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });
        }
    }, 100);
});
</script>
<div class="modal-body">

    <div class="row">
        <div class="form-group  col-md-6">
            <label for="product_id" class="form-label">{{ __('Product') }}<span class="text-danger">*</span></label>
            <select name="product_id" id="product_id" class="form-control select2" required>
                <option value="">{{ __('Select Product') }}</option>
                @foreach($product_services as $productId => $productName)
                    <option value="{{ $productId }}">{{ $productName }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_price" class="form-label">{{ __('Sale Price') }}</label><span class="text-danger">*</span>
                <input type="number" id="sale_price" name="sale_price" class="form-control" required="required" step="0.01">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label><span class="text-danger">*</span>
                 <small class="form-text text-muted">
                    {{ __('Enter the purchase price in the bill currency') }} ({{ $currency_symbol ?? $currency_code ?? 'currency' }})
                </small>
                <input type="number" id="purchase_price" name="purchase_price" class="form-control" required="required" step="0.01">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="discount" class="form-label">{{ __('Discount') }}</label><span class="text-danger">*</span>
                <small class="form-text text-muted">
                    {{ __('Enter the purchase price in the bill currency') }} ({{ $currency_symbol ?? $currency_code ?? 'currency' }})
                </small>
                <input type="number" id="discount" name="discount" class="form-control"  step="0.01">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                <select name="warehouse_id" id="warehouse_id" class="form-control select2">
                    @foreach($warehouses as $warehouseId => $warehouseName)
                        <option value="{{ $warehouseId }}" {{ isset($bill) && $bill->warehouse_id == $warehouseId ? 'selected' : '' }}>{{ $warehouseName }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="sub_product_images" class="form-label">{{ __('Images') }}</label>
                <input type="file" id="sub_product_images" name="sub_product_images[]" class="form-control" accept="image/*" multiple>
            </div>
        </div>

        {{-- @if(!$customFields->isEmpty())
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif --}}

        {{-- <input type="hidden" id="product_id" name="product_id" value="{{ $id }}"> --}}

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
</form>
