<form action="{{ route('bill.edit.debit.note.update', [$debitNote->bill, $debitNote->id]) }}" method="post">
    @csrf
    @method('post')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control" required value="{{ $debitNote->date }}">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="account_id" class="form-control select select2" required>
                    @foreach ($chartAccounts as $Id => $account)
                        <option value="{{ $Id }}" @if ($Id === $debitNote->account_id) selected @endif>
                            {{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01" value="{{ $debitNote->currency_id ? $debitNote->amount / $debitNote->currency_rate : $debitNote->amount }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                    <option value="{{ $id }}" @if($id === $debitNote->currency_id ) selected @endif>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control" value="{{ $debitNote->currency_rate }}">
            </div>

            <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency" class="form-control" value="{{ $debitNote->amount_in_currency }}">
                    <span class="input-group-text" id="currency_symbol_span">{{ $currency_symbol }}</span>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" rows="2">{{ $debitNote->description }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
