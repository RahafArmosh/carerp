<form action="{{ url('bank-account') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select name="chart_account_id" id="chart_account_id" class="form-control select2" required>
                    <option value="">Select Account</option>
                    @foreach ($chart_accounts as $id => $chart_account)
                        <option value="{{ $id }}">{{ $chart_account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="holder_name" class="form-label">{{ __('Bank Holder Name') }}</label>
                <input type="text" id="holder_name" name="holder_name" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="bank_name" class="form-label">{{ __('Bank Name') }}</label>
                <input type="text" id="bank_name" name="bank_name" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="account_number" class="form-label">{{ __('Account Number') }}</label>
                <input type="text" id="account_number" name="account_number" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="opening_balance" class="form-label">{{ __('Opening Balance') }}</label>
                <input type="number" id="opening_balance" name="opening_balance" class="form-control" required
                    step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="contact_number" class="form-label">{{ __('Contact Number') }}</label>
                <input type="text" id="contact_number" name="contact_number" class="form-control" required>
            </div>
            <div class="form-group col-md-12">
                <label for="bank_address" class="form-label">{{ __('Bank Address') }}</label>
                <textarea id="bank_address" name="bank_address" class="form-control" rows="3" required></textarea>
            </div>
            <div class="form-group col-md-12">
                <label for="bank_details" class="form-label">{{ __('Bank Details') }}</label>
                <textarea id="bank_details" name="bank_details" class="form-control" rows="3" required></textarea>
            </div>
            @if (!$customFields->isEmpty())
                <div class="col-md-12">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
<!-- jQuery (Make sure this is included before Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
