<form method="post" action="{{ route('invoice.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample invoice Excel file') }}</label>
            <a href="{{ route('invoice.sample.download') }}" class="btn btn-sm btn-primary" target="_blank">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select Excel File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.csv">
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

