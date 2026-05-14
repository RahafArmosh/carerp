<form method="post" action="{{ route('pro.import.items-only') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="alert alert-info mb-3">
            <i class="ti ti-info-circle"></i>
            {{ __('Enter PRO header fields here, then upload an Excel/CSV file that contains only item columns.') }}
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
                <label for="advance_sale_order_id" class="form-label">{{ __('Advance Sale Order') }}</label>
                <select name="advance_sale_order_id" id="advance_sale_order_id" class="form-control select2">
                    <option value="">{{ __('None') }}</option>
                    @foreach($advanceSaleOrders as $advanceSo)
                        <option value="{{ $advanceSo->id }}">
                            {{ \Auth::user()->saleOrderNumberFormat($advanceSo->advance_sale_order_no) }} - {{ $advanceSo->customer->name ?? __('Unknown Customer') }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">
                    {{ __('If selected, imported PO items must match Advance SO part numbers and quantities exactly.') }}
                </small>
            </div>
            <div class="col-md-6 mb-3">
                <label for="supplier_proforma_no" class="form-label">{{ __('Supplier Proforma No') }}</label>
                <input type="text" name="supplier_proforma_no" id="supplier_proforma_no" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="our_order_ref" class="form-label">{{ __('Our Order Ref') }}</label>
                <input type="text" name="our_order_ref" id="our_order_ref" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="supplier_ref" class="form-label">{{ __('Supplier Ref') }}</label>
                <input type="text" name="supplier_ref" id="supplier_ref" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="supplier_proforma_date" class="form-label">{{ __('Supplier Proforma Date') }}</label>
                <input type="date" name="supplier_proforma_date" id="supplier_proforma_date" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="eta_date" class="form-label">{{ __('ETA Date') }}</label>
                <input type="date" name="eta_date" id="eta_date" class="form-control">
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
                <input type="number" step="0.0001" min="0" name="exchange_rate" id="exchange_rate" class="form-control" value="1">
            </div>
            <div class="col-md-12 mb-3">
                <label for="file" class="form-label">{{ __('Download sample item-only PRO file') }}</label>
                <a href="{{ route('pro.sample.items-only.download') }}" class="btn btn-sm btn-primary" download>
                    <i class="ti ti-download"></i> {{ __('Download Items-Only Sample') }}
                </a>
                <small class="d-block text-muted mt-1">
                    {{ __('Sample columns: PART NO, DESCRIPTION, ORDER QTY, UNIT PRICE') }}
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
