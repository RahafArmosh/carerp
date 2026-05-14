<form action="{{ route('bank-transfer.update', $transfer->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="from_account" class="form-label">{{ __('Credit Account') }}</label>
                <select name="from_account" id="choices-multiple" class="form-control select" required>
                    @foreach($bankAccount as $id => $account)
                        <option value="{{ $id }}" {{ $id == $transfer->from_account ? 'selected' : '' }}>
                            {{ $account }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="to_account" class="form-label">{{ __('Debit Account') }}</label>
                <select name="to_account" id="choices-multiple1" class="form-control select" required>
                    @foreach($bankAccount as $id => $account)
                        <option value="{{ $id }}" {{ $id == $transfer->to_account ? 'selected' : '' }}>
                            {{ $account }}
                        </option>
                    @endforeach
                </select>
            </div>
             <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select">
                    <option value="">Select Currency</option>
                   @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate }}" @if($currency->id  == $transfer->currency_id) selected @endif>
                            {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="currency_rate" class="form-label">{{ __('Rate') }}</label>
                <input type="number" name="currency_rate" id="currency_rate" class="form-control" step="0.01" value="{{ $transfer->currency_rate }}">
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" value="{{ $transfer->amount }}" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ $transfer->date }}" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" value="{{ $transfer->reference }}" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ $transfer->description }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
<script>
    $(document).ready(function () {
        $('#currency_id').on('change', function () {
            const rate = $(this).find(':selected').data('rate');
            $('#currency_rate').val(rate || '');
        });
    });
</script>