<form action="{{ route('invoice.custom.credit.note.store') }}" method="post">
    {{ csrf_field() }}
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="invoice" class="form-label">{{ __('Invoice') }}</label>
                <select class="form-control select" required="required" id="invoice" name="invoice">
                    <option>{{ __('Select Invoice') }}</option>
                    @foreach($invoices as $key => $invoice)
                        <option value="{{ $key }}">{{ \Auth::user()->invoiceNumberFormat(App\Models\Invoice::where('id',$invoice)->first()->invoice_id) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control" required="required" step="0.01" value="{{ old('amount') }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                    <option value="{{ $id }}" data-rate="{{ \App\Models\Currency::find($id)->exchange_rate ?? 1 }}">{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control">
            </div>
            <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Invoice Currency') }}</label>
                <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency"
                    class="form-control">
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
                <input type="date" id="date" name="date" class="form-control" required="required" value="{{ old('date') }}">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>

<script>
    // Currency codes mapping
    const currencyCodes = {
        @foreach($currencies as $id => $currency)
        {{ $id }}: '{{ \App\Models\Currency::find($id)->code ?? "" }}',
        @endforeach
    };

    let invoiceData = null; // Store invoice data globally

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

    async function fetchInvoiceData(invoiceId) {
        if (!invoiceId) return null;
        
        try {
            const response = await fetch('/get-invoice-details/' + invoiceId);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching invoice details:', error);
            return null;
        }
    }

    async function updateCurrencyRate() {
        const currencySelect = document.getElementById('currency_id');
        const currencyRateInput = document.getElementById('currency_rate');
        const amountInput = document.getElementById('amount');
        const amountInCurrencyInput = document.getElementById('amount_in_currency');
        const amountCurrencyGroup = document.getElementById('amount_currency_group');
        const invoiceSelect = document.getElementById('invoice');
        
        if (!currencySelect || !invoiceSelect) return;
        
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        const selectedCurrencyId = selectedOption.value;
        const selectedCurrencyCode = currencyCodes[selectedCurrencyId];
        const invoiceId = invoiceSelect.value;
        
        // Fetch invoice data if not already available
        if (!invoiceData || invoiceData.id != invoiceId) {
            invoiceData = await fetchInvoiceData(invoiceId);
        }
        
        if (!invoiceData || !selectedCurrencyCode) {
            return;
        }
        
        const invoiceCurrencyId = invoiceData.currency_id;
        const invoiceCurrencyCode = currencyCodes[invoiceCurrencyId] || 'AED';
        
        try {
            // 1. Get exchange rate between chosen currency and AED for display
            const rateToAED = await getExchangeRate(selectedCurrencyCode, 'AED');
            currencyRateInput.value = rateToAED.toFixed(4);
            
            // 2. Check if chosen currency is same as invoice currency
            if (selectedCurrencyId == invoiceCurrencyId) {
                // Same currency - hide amount_in_currency field
                amountCurrencyGroup.style.display = 'none';
                amountInCurrencyInput.value = '';
            } else {
                // Different currency - show amount_in_currency field
                amountCurrencyGroup.style.display = 'block';
                
                // 3. Get exchange rate between chosen currency and invoice currency for amount calculation
                const rateToInvoiceCurrency = await getExchangeRate(selectedCurrencyCode, invoiceCurrencyCode);
                
                // Calculate amount in invoice currency if amount is provided
                if (amountInput.value) {
                    const amountInSelectedCurrency = parseFloat(amountInput.value);
                    const amountInInvoiceCurrency = amountInSelectedCurrency * rateToInvoiceCurrency;
                    amountInCurrencyInput.value = amountInInvoiceCurrency.toFixed(2);
                }
            }
        } catch (error) {
            console.error('Error updating currency rate:', error);
            // Fallback to static rate
            const rate = selectedOption.getAttribute('data-rate') || 1;
            currencyRateInput.value = rate;
            
            if (selectedCurrencyId == invoiceCurrencyId) {
                amountCurrencyGroup.style.display = 'none';
                amountInCurrencyInput.value = '';
            } else {
                amountCurrencyGroup.style.display = 'block';
                
                if (amountInput.value) {
                    const amountInSelectedCurrency = parseFloat(amountInput.value);
                    const amountInInvoiceCurrency = amountInSelectedCurrency * parseFloat(rate);
                    amountInCurrencyInput.value = amountInInvoiceCurrency.toFixed(2);
                }
            }
        }
    }

    async function updateAmountFromCurrency() {
        const currencySelect = document.getElementById('currency_id');
        const amountInput = document.getElementById('amount');
        const amountInCurrencyInput = document.getElementById('amount_in_currency');
        const invoiceSelect = document.getElementById('invoice');
        
        if (!amountInCurrencyInput.value || !invoiceSelect.value) return;
        
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        const selectedCurrencyId = selectedOption.value;
        const selectedCurrencyCode = currencyCodes[selectedCurrencyId];
        const invoiceId = invoiceSelect.value;
        
        // Fetch invoice data if not already available
        if (!invoiceData || invoiceData.id != invoiceId) {
            invoiceData = await fetchInvoiceData(invoiceId);
        }
        
        if (!invoiceData || !selectedCurrencyCode) return;
        
        const invoiceCurrencyId = invoiceData.currency_id;
        const invoiceCurrencyCode = currencyCodes[invoiceCurrencyId] || 'AED';
        
        try {
            // Get exchange rate between chosen currency and invoice currency
            const rateToInvoiceCurrency = await getExchangeRate(selectedCurrencyCode, invoiceCurrencyCode);
            
            // Convert from invoice currency to selected currency
            const amountInInvoiceCurrency = parseFloat(amountInCurrencyInput.value);
            const amountInSelectedCurrency = amountInInvoiceCurrency / rateToInvoiceCurrency;
            amountInput.value = amountInSelectedCurrency.toFixed(2);
        } catch (error) {
            console.error('Error updating amount from currency:', error);
        }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Currency change
        document.getElementById('currency_id').addEventListener('change', updateCurrencyRate);
        
        // Invoice change
        document.getElementById('invoice').addEventListener('change', updateCurrencyRate);
        
        // Amount change
        document.getElementById('amount').addEventListener('input', updateCurrencyRate);
        
        // Amount in currency change
        document.getElementById('amount_in_currency').addEventListener('input', updateAmountFromCurrency);
        
        // Initialize
        updateCurrencyRate();
    });
</script>
