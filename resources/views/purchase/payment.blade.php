<form method="post" action="{{ route('purchase.payment', $purchase->id) }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" value="{{ $purchase->getDue() }}" class="form-control" required
                    step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" class="form-control select" required>
                    @foreach ($accounts as $accountId => $accountName)
                        <option value="{{ $accountId }}">{{ $accountName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-md-6 form-group">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <div class="choose-file">
                    <input type="file" name="add_receipt" id="image" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Add') }}" class="btn btn-primary">
        </div>
    </div>
</form>
