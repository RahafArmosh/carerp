<form class="" method="post" action="{{ route('barcode.setting') }}">
    @csrf

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="barcode_type" class="form-label text-dark">{{ __('Barcode Type') }}</label>
                <select name="barcode_type" id="barcode_type" class="form-control" data-toggle="select">
                    <option value="code128" {{ $settings['barcode_type'] === 'code128' ? 'selected' : '' }}>Code 128
                    </option>
                    <option value="code39" {{ $settings['barcode_type'] === 'code39' ? 'selected' : '' }}>Code 39
                    </option>
                    <option value="code93" {{ $settings['barcode_type'] === 'code93' ? 'selected' : '' }}>Code 93
                    </option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="barcode_format" class="form-label text-dark">{{ __('Barcode Format') }}</label>
                <select name="barcode_format" id="barcode_format" class="form-control" data-toggle="select">
                    <option value="css" {{ $settings['barcode_format'] === 'css' ? 'selected' : '' }}>CSS</option>
                    <option value="bmp" {{ $settings['barcode_format'] === 'bmp' ? 'selected' : '' }}>BMP</option>
                </select>
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
    </div>
</form>
