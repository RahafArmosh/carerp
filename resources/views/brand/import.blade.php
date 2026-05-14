<form method="post" action="{{ route('brand.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="alert alert-info">
                <strong>{{ __('Import Format:') }}</strong><br>
                {{ __('Column 1:') }} <strong>{{ __('ID (Optional)') }}</strong> - {{ __('Leave empty to create new brand, or enter existing Brand ID to update') }}<br>
                {{ __('Column 2:') }} <strong>{{ __('Brand Name') }}</strong> - {{ __('Required') }}<br>
                {{ __('Column 3:') }} <strong>{{ __('Category IDs') }}</strong> - {{ __('Comma-separated category IDs (e.g., 1,2,3)') }}
            </div>
        </div>
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample brand CSV file') }}</label>
            <a href="{{ asset('uploads/sample/brand-product.xlsx') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
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
