<form method="post" action="{{ route('bill.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading"><i class="ti ti-alert-triangle"></i> {{ __('Important: Data Quality Requirements') }}</h6>
        <hr>
        <p class="mb-0">
            <strong>{{ __('Please ensure you enter clean and accurate data:') }}</strong>
        </p>
        <ul class="mb-0 mt-2">
            <li>{{ __('All SKU values must be accurate and match existing products (case-insensitive)') }}</li>
            <li>{{ __('Product names, brand names, sub-brand names, and category names should be spelled correctly') }}</li>
            <li>{{ __('Remove any extra spaces, special characters, or formatting issues') }}</li>
            <li>{{ __('Ensure all required fields are filled (SKU, product_name, brand_name, sub_brand_name, category_name)') }}</li>
            <li>{{ __('Verify numeric values (quantity, prices) are valid numbers') }}</li>
            <li>{{ __('Check dates are in the correct format (YYYY-MM-DD)') }}</li>
        </ul>
        <p class="mb-0 mt-2">
            <small class="text-muted">{{ __('Clean data will ensure successful import and accurate product matching.') }}</small>
        </p>
    </div>
    <div class="row">
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample Bill Excel file') }}</label>
            <a href="{{ route('bill.sample.download') }}" class="btn btn-sm btn-primary" download>
                <i class="ti ti-download"></i> {{__('Download Sample')}}
            </a>
            <p class="text-muted mt-2 small">
                {{ __('Note: The sample file includes SKU column for product matching. Products with matching SKUs will be marked as FOUND, others as MISSING.') }}
            </p>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select CSV File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required>
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Upload')}}" class="btn  btn-primary">
</div>
</form>
