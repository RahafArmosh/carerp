<form method="post" action="{{ route('sub_brand.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="alert {{ isset($updateById) && $updateById ? 'alert-warning' : 'alert-info' }}">
                <h6><i class="ti ti-info-circle"></i> {{ isset($updateById) && $updateById ? __('Update by ID Format') : __('Import Format') }}</h6>
                <p class="mb-2">{{ __('Your Excel/CSV file must have these columns (no header row):') }}</p>
                @if(isset($updateById) && $updateById)
                    <ul class="mb-2">
                        <li><strong>{{ __('Column 1: Model ID') }}</strong> — {{ __('ID of the row to update (required)') }}</li>
                        <li><strong>{{ __('Column 2: Model Name') }}</strong> — {{ __('New name (required)') }}</li>
                        <li><strong>{{ __('Column 3: Brand ID') }}</strong> — {{ __('Brand foreign key (required)') }}</li>
                    </ul>
                    <small class="text-muted">
                        {{ __('Example: 5,Air Max Pro,1') }}
                    </small>
                @else
                <ul class="mb-2">
                        <li><strong>{{ __('Column 1: Model Name') }}</strong> — {{ __('required') }}</li>
                        <li><strong>{{ __('Column 2: Brand ID') }}</strong> — {{ __('required') }}</li>
                </ul>
                <small class="text-muted">
                        {{ __('Example: Air Max,1') }}
                </small>
                @endif
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <label for="file" class="form-label">{{ __('Download Sample File') }}</label>
            <a href="{{ asset('uploads/sample/sub-brand-product.csv') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download Sample')}}
            </a>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" accept=".xlsx,.xls,.csv" required>
                </label>
                <p class="upload_file"></p>
            </div>
            <small class="text-muted">{{ __('Supported formats: .xlsx, .xls, .csv') }}</small>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Upload')}}" class="btn  btn-primary">
</div>
</form>
