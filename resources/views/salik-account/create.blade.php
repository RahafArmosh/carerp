<form method="POST" action="{{ route('salik-account.store') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Salik Account Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select name="chart_account_id" id="chart_account_id" class="form-control select" required>
                    @foreach ($chart_accounts as $chart_account)
                        <option value="{{ $chart_account->id }}">{{ $chart_account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="balance" class="form-label">{{ __('Opening Balance') }}</label>
                <input type="number" name="balance" id="balance" class="form-control" required step="0.01">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
