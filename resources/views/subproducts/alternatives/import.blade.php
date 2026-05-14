<form method="POST"
      action="{{ route('alternatives.import') }}"
      enctype="multipart/form-data"
      style="padding:40px;">
    @csrf

    <div class="form-group mb-3">
        <label>{{ __('Upload XLSX File') }}</label>
        <input type="file"
               name="file"
               class="form-control"
               accept=".xlsx"
               required>
    </div>

    <div class="d-flex justify-content-between mt-4">
        {{-- Download Template --}}
        <a href="{{ route('alternatives.import.template') }}"
           class="btn btn-outline-secondary">
            <i class="ti ti-download"></i> {{ __('Download Template') }}
        </a>

        {{-- Import --}}
        <button type="submit" class="btn btn-primary">
            {{ __('Import') }}
        </button>
    </div>
    <small class="text-muted">
        {{ __('Columns must match the downloaded template exactly.') }}
    </small>

</form>
