<form method="post" action="{{ route('productservice.stock.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample stock CSV file') }}</label>
            <a href="{{ asset('uploads/sample/sample-stock.csv') }}" class="btn btn-sm btn-primary" target="_blank">
                <i class="ti ti-download"></i> {{__('Download Sample')}}
            </a>
        </div>
        <div class="col-md-12">
            <div class="alert alert-info">
                <h6>{{ __('Stock Import Guide') }}</h6>
                <p class="mb-2">{{ __('This import will create:') }}</p>
                <ul class="mb-0">
                    <li>{{ __('Brands (if they don\'t exist)') }}</li>
                    <li>{{ __('Sub Brands (if they don\'t exist)') }}</li>
                    <li>{{ __('Products (if they don\'t exist by SKU)') }}</li>
                    <li>{{ __('Sub Products (new entries)') }}</li>
                    <li>{{ __('Custom Fields for both products and sub-products') }}</li>
                </ul>
            </div>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.xls,.csv">
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
        <div class="col-md-12 mt-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="use_queue" id="use_queue" value="1" checked>
                <label class="form-check-label" for="use_queue">
                    {{ __('Process in background (Recommended for large files)') }}
                </label>
                <small class="form-text text-muted d-block">
                    {{ __('Uncheck to process immediately. Background processing prevents timeouts for large imports.') }}
                </small>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Upload')}}" class="btn  btn-primary">
</div>
</form>

<script>
    // Display selected file name
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>

