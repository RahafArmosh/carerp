<form method="post" action="{{ route('pro.import') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="file" class="form-label">{{ __('Download sample PRO Excel file') }}</label>
                <a href="{{ route('pro.sample.download') }}" class="btn btn-sm btn-primary" download>
                    <i class="ti ti-download"></i> {{ __('Download Sample') }}
                </a>
                {{-- <p class="text-muted mt-2">{{ __('Please ensure your Excel file follows this structure:') }}</p>
                <ul class="text-muted small">
                    <li>{{ __('Row 1: Title "Purchase Order"') }}</li>
                    <li>{{ __('Row 2-7: Header information (Supplier Name, etc.)') }}</li>
                    <li>{{ __('Row 10: Column headers for items (PART NO, DESCRIPTION, ORDER QTY, SUPPLIED QTY, REMAINING QTY, UNIT PRICE, TOTAL AMOUNT)') }}</li>
                    <li>{{ __('Row 11+: Item data rows') }}</li>
                </ul> --}}
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

