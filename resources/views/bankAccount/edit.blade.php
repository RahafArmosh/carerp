<form action="{{ route('bank-account.update', $bankAccount->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select id="chart_account_id" name="chart_account_id" class="form-control select2" required>
                    @foreach($chart_accounts as $id => $name)
                        <option value="{{ $id }}" @if($id == $bankAccount->chart_account_id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="holder_name" class="form-label">{{ __('Bank Holder Name') }}</label>
                <input type="text" id="holder_name" name="holder_name" class="form-control" value="{{ $bankAccount->holder_name }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="bank_name" class="form-label">{{ __('Bank Name') }}</label>
                <input type="text" id="bank_name" name="bank_name" class="form-control" value="{{ $bankAccount->bank_name }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="account_number" class="form-label">{{ __('Account Number') }}</label>
                <input type="text" id="account_number" name="account_number" class="form-control" value="{{ $bankAccount->account_number }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="opening_balance" class="form-label">{{ __('Opening Balance') }}</label>
                <input type="number" id="opening_balance" name="opening_balance" class="form-control" value="{{ $bankAccount->opening_balance }}" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="contact_number" class="form-label">{{ __('Contact Number') }}</label>
                <input type="text" id="contact_number" name="contact_number" class="form-control" value="{{ $bankAccount->contact_number }}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="bank_address" class="form-label">{{ __('Bank Address') }}</label>
                <textarea id="bank_address" name="bank_address" class="form-control" rows="3" required>{{ $bankAccount->bank_address }}</textarea>
            </div>
            <div class="form-group col-md-12">
                <label for="bank_details" class="form-label">{{ __('Bank Details') }}</label>
                <textarea id="bank_details" name="bank_details" class="form-control" rows="3" required>{{ $bankAccount->bank_details }}</textarea>
            </div>
            @if(!$customFields->isEmpty())
                <div class="col-md-12">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        <!-- Include your custom fields form builder code here -->
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
