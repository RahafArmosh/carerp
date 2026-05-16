<form action="{{ route('bill.custom.debit.note.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="bill" class="form-label">{{ __('Bill') }}</label>
                <select class="form-control select" required="required" id="bill" name="bill">
                    <option value="0">{{ __('Select Bill') }}</option>
                    @foreach ($bills as $key => $bill)
                        <option value="{{ $bill->id }}" data-due="{{ $bill->getDue() }}"
                            data-due-currency="{{ $bill->getDueInCurrency() }}"
                            data-currency-symbol="{{ $bill->currency->symbol ?? Auth::user()->currencySymbol() }}">
                            {{ Auth::user()->billNumberFormat($bill->bill_id) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control" required="required"
                    step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select">
                    @foreach ($currencies as $id => $currency)
                        <option value="{{ $id }}">{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control">
            </div>
            <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency"
                        name="amount_in_currency" class="form-control">
                    <span class="input-group-text" id="currency_symbol_span"></span>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select id="account_id" name="account_id" class="form-control select select2" required="required">
                    @foreach ($chartAccounts as $Id => $account)
                        <option value="{{ $Id }}">{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" id="date" name="date" class="form-control" required="required">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
<script>
    $(document).ready(function() {
        // Bind currency dropdown change
        $(document).on('change', '#currency_id', function() {
            const currencyId = $(this).val();
            if (currencyId) {
                fetch(`/currencies/${currencyId}/rate`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Currency rate response:', data);
                        $('#currency_rate').val(data.rate ?? '');
                    })
                    .catch(error => {
                        console.error('Error fetching currency rate:', error);
                        $('#currency_rate').val('');
                    });
            } else {
                $('#currency_rate').val('');
            }
        });
        document.getElementById('bill').addEventListener('change', function() {
            let selected = this.options[this.selectedIndex];
            let due = selected.getAttribute('data-due');
            let dueCurrency = selected.getAttribute('data-due-currency');
            let symbol = selected.getAttribute('data-currency-symbol');

            document.getElementById('amount').value = due || '';
            document.getElementById('amount_in_currency').value = dueCurrency || '';
            document.getElementById('currency_symbol_span').innerText = symbol || '';
        });
    });
</script>
