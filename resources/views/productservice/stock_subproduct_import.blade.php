<form method="post" action="{{ route('subproductservice.import') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <p class="text-muted small mb-2">
                    {{ __('Required columns:') }}
                    <strong>product_sku</strong> ({{ __('parent product SKU') }}),
                    <strong>product_no</strong>,
                    <strong>initial_rate</strong>,
                    <strong>initial_stock</strong>.
                    {{ __('Optional: sale_price, purchase_price, warehouse_name (must match a warehouse name in your company), warehouse_id (legacy numeric ID). The downloaded sample also includes one column per sub-product custom field you have configured (header = exact field name).') }}
                </p>
            </div>
            <div class="col-md-12 mb-6">
                <label class="form-label">{{ __('Download sample Excel file (.xlsx)') }}</label>
                <a href="{{ route('productservice.stock.subproduct.sample') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-download"></i> {{ __('Download') }}
                </a>
            </div>
            <div class="col-md-12">
                <label for="stock_subproduct_file" class="form-label">{{ __('Select CSV or Excel file') }}</label>
                <div class="choose-file form-group">
                    <label for="stock_subproduct_file" class="form-label">
                        <input type="file" class="form-control" name="file" id="stock_subproduct_file" data-filename="upload_file" required accept=".csv,.xlsx,.xls">
                    </label>
                    <p class="upload_file"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Upload') }}" class="btn btn-primary">
    </div>
</form>
