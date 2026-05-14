<form action="{{ route('attendance.import') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-6">
                <label for="file" class="form-label">{{ __('Download sample employee CSV file') }}</label>
                <a href="{{ asset(Storage::url('uploads/sample')) . '/sample_attendance.csv' }}"
                    class="btn btn-sm btn-primary">
                    <i class="ti ti-download"></i> {{ __('Download') }}
                </a>
            </div>
            <div class="col-md-12">
                <label for="file" class="form-label">{{ __('Select CSV File') }}</label>
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" class="form-control" name="file" id="file"
                            data-filename="upload_file" required>
                    </label>
                    <p class="upload_file"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
    </div>
</form>
