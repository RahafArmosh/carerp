<form method="POST" action="{{ route('warehouse-transfer.import') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="file" class="form-label">{{ __('Download Sample Excel File') }}</label>
                <a href="{{ route('warehouse-transfer.sample.download') }}" class="btn btn-sm btn-primary" download>
                    <i class="ti ti-download"></i> {{ __('Download Sample') }}
                </a>
                {{-- <p class="text-muted mt-2">{{ __('Please ensure your Excel file follows this structure:') }}</p>
                <ul class="text-muted small">
                    <li>{{ __('Row 1: Column headers (from_warehouse, to_warehouse, product_no, quantity, date)') }}</li>
                    <li>{{ __('Row 2+: Data rows with warehouse IDs, product numbers, quantities, and dates') }}</li>
                    <li>{{ __('Date format: YYYY-MM-DD (e.g., 2024-01-15)') }}</li>
                    <li>{{ __('Warehouse IDs must exist in your system') }}</li>
                    <li>{{ __('Product numbers must exist in the source warehouse') }}</li>
                    <li>{{ __('Quantities must be greater than 0') }}</li>
                </ul> --}}
            </div>
            <div class="col-md-12">
                <label for="file" class="form-label">{{ __('Select Excel/CSV File') }} <span class="text-danger">*</span></label>
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.csv">
                    </label>
                    <p class="upload_file"></p>
                    <small class="text-muted">{{ __('Supported formats: .xlsx, .csv (Max size: 10MB)') }}</small>
                    @error('file')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
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

