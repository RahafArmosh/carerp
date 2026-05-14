<form method="post" action="{{ route('productservice.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-3">
            <p class="text-muted small mb-0">
                {{ __('Match') }}
                <strong>category_name</strong>, <strong>brand_name</strong>, {{ __('and') }} <strong>model_name</strong>
                {{ __('to existing records (exact name, case-insensitive). Optional:') }}
                <strong>unit_name</strong>, <strong>tax</strong> ({{ __('tax ID or tax name, comma-separated') }}).
            </p>
        </div>
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample Excel file (.xlsx)') }}</label>
            <a href="{{ route('productservice.import.sample') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select CSV File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".csv,.xlsx,.xls">
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
