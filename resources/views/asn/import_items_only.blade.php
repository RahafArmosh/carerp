<form method="post" action="{{ route('asn.import.items-only') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle"></i>
            {{ __('Enter ASN header fields here, then upload an Excel/CSV file with items only.') }}
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="supplier_id" class="form-label">{{ __('Supplier Name') }} <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplier_id" class="form-control select2" required>
                    <option value="">{{ __('Select Supplier') }}</option>
                    @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="eta_date" class="form-label">{{ __('ETA Date') }}</label>
                <input type="date" name="eta_date" id="eta_date" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label for="supplier_inv_no" class="form-label">{{ __('Supplier Inv No.') }}</label>
                <input type="text" name="supplier_inv_no" id="supplier_inv_no" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="container_no" class="form-label">{{ __('Container No') }}</label>
                <input type="text" name="container_no" id="container_no" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label for="dec_date" class="form-label">{{ __('DEC Date') }}</label>
                <input type="date" name="dec_date" id="dec_date" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label for="boe_number" class="form-label">{{ __('BOE Number') }}</label>
                <input type="text" name="boe_number" id="boe_number" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="hs_code" class="form-label">{{ __('HS Code') }}</label>
                <input type="text" name="hs_code" id="hs_code" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="currency_id" class="form-label">{{ __('Currency ID') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select2">
                    <option value="">{{ __('Select Currency') }}</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate ?? 1 }}">
                            {{ $currency->id }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                <input type="number" name="exchange_rate" id="exchange_rate" step="0.0001" min="0" class="form-control" value="1">
            </div>
            <div class="col-md-6 mb-3">
                <label for="warehouse_id" class="form-label">{{ __('Warehouse') }} <span class="text-danger">*</span></label>
                <select name="warehouse_id" id="warehouse_id" class="form-control select2" required>
                    <option value="">{{ __('Select Warehouse') }}</option>
                    @foreach($warehouses as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12 mb-3">
                <label for="file" class="form-label">{{ __('Download sample ASN items-only file') }}</label>
                <a href="{{ route('asn.sample.items-only.download') }}" class="btn btn-sm btn-primary" download>
                    <i class="ti ti-download"></i> {{ __('Download Items-Only Sample') }}
                </a>
                <small class="d-block text-muted mt-1">
                    {{ __('Sample columns: BOX NO, SUPPLIER PO NO, OUR PRO NO, ORDER REF, PART NO, DESCRIPTION, QTY, UNIT PRICE, UNIT WEIGHT, DEC NO, DED DATE, ORIGIN') }}
                </small>
            </div>
            <div class="col-md-12">
                <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.xls,.csv">
                    </label>
                    <p class="upload_file"></p>
                    <small class="text-muted">{{ __('Supported formats: .xlsx, .xls, .csv') }}</small>
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
    $('.select2').select2();

    $('#currency_id').on('change', function() {
        const selectedRate = parseFloat($(this).find(':selected').data('rate'));
        if (!isNaN(selectedRate)) {
            $('#exchange_rate').val(selectedRate);
        }
    });

    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'No file chosen';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>
