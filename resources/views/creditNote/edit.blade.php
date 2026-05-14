<form action="{{ route('invoice.edit.credit.note', [$creditNote->invoice, $creditNote->id]) }}" method="post">
    {{ csrf_field() }}
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" id="date" name="date" class="form-control" required="required" value="{{ old('date', $creditNote->date) }}">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select id="account_id" name="account_id" class="form-control select select2" required="required">
                    @foreach ($chartAccounts as $Id => $account)
                        <option value="{{ $Id }}" @if ($Id === $creditNote->account_id) selected @endif>{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control" required="required" step="0.01" value="{{ old('amount', $creditNote->amount) }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2" onchange="updateCurrencyRate()">
                    @foreach ($currencies as $id => $currency)
                    <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}" @if ($id == $creditNote->currency_id) selected @endif>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control" value="{{ old('currency_rate', $creditNote->currency_rate ?? 1) }}" readonly>
            </div>
            <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Currency') }}</label>
                <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency"
                    class="form-control" value="{{ old('amount_in_currency', $creditNote->amount_in_currency ?? '') }}" readonly>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $creditNote->description) }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>

<script>
    function updateCurrencyRate() {
        var currencySelect = document.getElementById('currency_id');
        var currencyRateInput = document.getElementById('currency_rate');
        var amountInput = document.getElementById('amount');
        var amountInCurrencyInput = document.getElementById('amount_in_currency');
        
        var selectedOption = currencySelect.options[currencySelect.selectedIndex];
        var rate = selectedOption.getAttribute('data-rate') || 1;
        
        currencyRateInput.value = rate;
        
        // Calculate amount in currency when amount changes
        if (amountInput.value) {
            // For credit notes, amount_in_currency should be the amount in invoice currency
            // If same currency as invoice, use entered amount, otherwise convert
            var invoiceCurrencyId = '{{ $creditNote->invoice()->first()->currency_id ?? "" }}';
            var invoiceExchangeRate = '{{ $creditNote->invoice()->first()->exchange_rate ?? 1 }}';
            var selectedCurrencyId = selectedOption.value;
            
            if (selectedCurrencyId == invoiceCurrencyId) {
                // Same currency as invoice
                amountInCurrencyInput.value = parseFloat(amountInput.value).toFixed(2);
            } else {
                // Different currency, convert AED to invoice currency
                var amountAED = parseFloat(amountInput.value) * parseFloat(rate);
                var amountInInvoiceCurrency = amountAED / parseFloat(invoiceExchangeRate);
                amountInCurrencyInput.value = amountInInvoiceCurrency.toFixed(2);
            }
        }
    }

    // Update amount in currency when amount changes
    document.getElementById('amount').addEventListener('input', function() {
        updateCurrencyRate();
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrencyRate();
    });
</script>
