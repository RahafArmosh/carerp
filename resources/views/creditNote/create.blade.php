<form action="{{ route('invoice.credit.note', $invoice_id) }}" method="post">
    {{ csrf_field() }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            <label for="date" class="form-label">{{ __('Date') }}</label>
            <input type="date" id="date" name="date" class="form-control" required="required">
        </div>
        <div class="form-group col-md-6">
            <label for="account_id" class="form-label">{{ __('Account') }}</label>
            <select id="account_id" name="account_id" class="form-control select select2" required="required">
                @foreach ($chartAccounts as $Id => $account)
                    <option value="{{ $Id }}">{{ $account }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            <label for="amount" class="form-label">{{ __('Amount') }}</label>
            <input type="number" id="amount" name="amount" class="form-control" required="required" step="0.01" value="{{ !empty($invoiceDue) ? $invoiceDue->getDue() : 0 }}">
        </div>
        <div class="form-group col-md-6">
            <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
            <select name="currency_id" id="currency_id" class="form-control select select2" onchange="updateCurrencyRate()">
                @foreach ($currencies as $id => $currency)
                <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}">{{ $currency }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6" id="currency_rate_group">
            <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
            <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                class="form-control" readonly>
        </div>
        <div class="form-group col-md-6" id="amount_currency_group">
            <label for="amount_in_currency" class="form-label">{{ __('Amount in Invoice Currency') }}</label>
            <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency"
                class="form-control">
        </div>
        <div class="form-group col-md-12">
            <label for="description" class="form-label">{{ __('Description') }}</label>
            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>

    </div>
</div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Add')}}" class="btn  btn-primary">
    </div>
</form>

<script>
    // Currency codes mapping
    const currencyCodes = {
        @foreach($currencies as $id => $currency)
        {{ $id }}: '{{ \App\Models\Currency::find($id)->code ?? "" }}',
        @endforeach
    };

    async function getExchangeRate(fromCurrency, toCurrency) {
        try {
            const response = await fetch('https://open.er-api.com/v6/latest/' + fromCurrency);
            const data = await response.json();
            
            if (data.result === 'success' && data.rates[toCurrency]) {
                return data.rates[toCurrency];
            }
            return 1; // Fallback rate
        } catch (error) {
            console.error('Error fetching exchange rate:', error);
            return 1; // Fallback rate
        }
    }

    async function updateCurrencyRate() {
        var currencySelect = document.getElementById('currency_id');
        var currencyRateInput = document.getElementById('currency_rate');
        var amountInput = document.getElementById('amount');
        var amountInCurrencyInput = document.getElementById('amount_in_currency');
        
        var selectedOption = currencySelect.options[currencySelect.selectedIndex];
        var selectedCurrencyId = selectedOption.value;
        var selectedCurrencyCode = currencyCodes[selectedCurrencyId];
        
        var invoiceCurrencyId = '{{ $invoiceDue->currency_id ?? "" }}';
        var invoiceCurrencyCode = currencyCodes[invoiceCurrencyId] || 'AED';
        
        if (selectedCurrencyCode && invoiceCurrencyCode) {
            // Get exchange rate from API
            const rate = await getExchangeRate(selectedCurrencyCode, invoiceCurrencyCode);
            currencyRateInput.value = rate.toFixed(4);
            
            // Calculate amount in invoice currency when amount changes
            if (amountInput.value) {
                var amountInSelectedCurrency = parseFloat(amountInput.value);
                var amountInInvoiceCurrency = amountInSelectedCurrency * rate;
                amountInCurrencyInput.value = amountInInvoiceCurrency.toFixed(2);
            }
        } else {
            // Fallback to static rate
            var rate = selectedOption.getAttribute('data-rate') || 1;
            currencyRateInput.value = rate;
            
            if (amountInput.value) {
                if (selectedCurrencyId == invoiceCurrencyId) {
                    amountInCurrencyInput.value = parseFloat(amountInput.value).toFixed(2);
                } else {
                    var amountInSelectedCurrency = parseFloat(amountInput.value);
                    var amountInInvoiceCurrency = amountInSelectedCurrency * parseFloat(rate);
                    amountInCurrencyInput.value = amountInInvoiceCurrency.toFixed(2);
                }
            }
        }
    }

    // Update amount in currency when amount changes
    document.getElementById('amount').addEventListener('input', function() {
        updateCurrencyRate();
    });

    // Update amount when amount_in_currency changes
    document.getElementById('amount_in_currency').addEventListener('input', function() {
        var currencySelect = document.getElementById('currency_id');
        var currencyRateInput = document.getElementById('currency_rate');
        var amountInput = document.getElementById('amount');
        var amountInCurrencyInput = document.getElementById('amount_in_currency');
        
        var selectedOption = currencySelect.options[currencySelect.selectedIndex];
        var selectedCurrencyId = selectedOption.value;
        var invoiceCurrencyId = '{{ $invoiceDue->currency_id ?? "" }}';
        
        if (amountInCurrencyInput.value && currencyRateInput.value) {
            if (selectedCurrencyId == invoiceCurrencyId) {
                // Same currency, amounts are equal
                amountInput.value = parseFloat(amountInCurrencyInput.value).toFixed(2);
            } else {
                // Convert from invoice currency to selected currency
                var amountInInvoiceCurrency = parseFloat(amountInCurrencyInput.value);
                var rate = parseFloat(currencyRateInput.value);
                var amountInSelectedCurrency = amountInInvoiceCurrency / rate;
                amountInput.value = amountInSelectedCurrency.toFixed(2);
            }
        }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCurrencyRate();
    });
</script>
