<form method="post" action="{{ route('pro.import.create-subproducts') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle"></i>
            {{ __('This import will create new sub-products (stock) for part numbers that do not exist yet. For parts not in stock, fill DESCRIPTION and optionally CATEGORY_ID, BRAND_NAME, SUB_BRAND_NAME, SALE PRICE and custom field columns in the sample — the system will create the product and stock so you can import without pre-creating parts.') }}
        </div>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="file" class="form-label">{{ __('Download sample PRO Excel file (Category, Brand, Sub-Brand & Custom Fields)') }}</label>
                <a href="{{ route('pro.sample.create-sub.download') }}" class="btn btn-sm btn-primary" download>
                    <i class="ti ti-download"></i> {{ __('Download Sample (Category, Brand, Sub-Brand & Custom Fields)') }}
                </a>
            </div>
            <div class="col-md-12">
                <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.csv">
                    </label>
                    <p class="upload_file"></p>
                    <small class="text-muted">{{ __('Supported formats: .xlsx, .csv (Max size: 10MB)') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Upload') }}" class="btn btn-primary">
    </div>
</form>

<script>
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'No file chosen';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>
