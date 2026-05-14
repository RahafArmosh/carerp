<form action="{{ route('invoice.payment', $invoice->id) }}" method="post" enctype="multipart/form-data">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" id="reference" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="account_id" class="form-control select" required>
                    @foreach ($accounts as $id => $account)
                        <option value="{{ $id }}">{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control"
                    value="{{ $invoice->getDue() }}" required step="0.01">
            </div>

            <div class="form-group col-md-6">
                <label for="bank_charge_account_id" class="form-label">{{ __('Charge Account') }}</label>
                <select name="bank_charge_account_id" id="bank_charge_account_id" class="form-control select" required>
                    @foreach ($accounts as $id => $account)
                        <option value="{{ $id }}">{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="charge" class="form-label">{{ __('Charge Amount') }}</label>
                <input type="number" name="charge" id="charge" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2" required>
                    @foreach ($currencies as $id => $currency)
                        <option value="{{ $id }}">{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6 d-none" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.01" min="0" id="currency_rate" name="currency_rate"
                    class="form-control">
            </div>
            <div class="form-group col-md-6 d-none" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Invoice Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency"
                        name="amount_in_currency" class="form-control" value="{{ $invoice->getDueInCurrency() }}">
                    <span class="input-group-text" id="currency_symbol_span">{{ $currency_symbol }}</span>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <div class="choose-file form-group">
                    <label for="file" class="form-label">
                        <input type="file" name="add_receipt" id="add_receipt" class="form-control">
                    </label>
                    <p class="upload_file"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Add') }}" class="btn btn-primary">
    </div>
    @csrf
</form>
<script>
    // Constants
    const AED_CODE = "AED";
    const AED_ID = "{{ \App\Models\Currency::where('code', 'AED')->value('id') }}";

    // Invoice currency defaults to AED if null (system default)
    const invoiceCurrencyId = "{{ $invoice->currency_id }}" || AED_ID;

    /**
     * Get total payment amount (amount + charge)
     */
    function getPaymentTotal() {
        return (parseFloat($('#amount').val()) || 0) + (parseFloat($('#charge').val()) || 0);
    }

    /**
     * Fetch currency rate from payment currency to AED
     * Sets currency_rate field and triggers amount_in_currency calculation
     */
    function fetchRateToAED(paymentCurrencyId) {
        $.ajax({
            url: '{{ route('currency.convertAED') }}',
            method: 'GET',
            data: {
                from_id: paymentCurrencyId,
                to_code: AED_CODE,
                amount: 1
            },
            success: function(data) {
                if (data.result !== undefined) {
                    $('#currency_rate').val(parseFloat(data.result).toFixed(4));
                } else {
                    $('#currency_rate').val('');
                }
            },
            error: function() {
                $('#currency_rate').val('');
            }
        });
    }

    /**
     * Fetch conversion from payment currency to invoice currency
     */
    function fetchConversionToInvoiceCurrency(paymentCurrencyId, invoiceCurrencyId, amount) {
        $.ajax({
            url: '{{ route('currency.convert') }}',
            method: 'GET',
            data: {
                from_id: paymentCurrencyId,
                to_id: invoiceCurrencyId,
                amount: amount
            },
            success: function(data) {
                if (data.result !== undefined) {
                    $('#amount_in_currency').val(parseFloat(data.result).toFixed(2));
                } else {
                    $('#amount_in_currency').val('');
                }
            },
            error: function() {
                $('#amount_in_currency').val('');
            }
        });
    }

    /**
     * Update amount_in_currency based on currency_rate and payment total
     * Used when payment currency is not invoice currency and rate is available
     */
    function updateAmountInCurrency() {
        const paymentTotal = getPaymentTotal();
        const rate = parseFloat($('#currency_rate').val()) || 0;

        if (rate > 0) {
            $('#amount_in_currency').val((paymentTotal * rate).toFixed(2));
        }
    }

    /**
     * Handle currency change logic
     * Determines visibility and values for currency_rate_group and amount_currency_group
     */
    function handleCurrencyChange() {
        const paymentCurrencyId = $('#currency_id').val();
        const paymentTotal = getPaymentTotal();

        // If payment currency is AED (same as system default)
        if (paymentCurrencyId == AED_ID) {
            // Hide currency_rate_group - rate is always 1 for AED
            $('#currency_rate_group').addClass('d-none');
            $('#currency_rate').val(1);

            // Check if invoice currency is also AED
            if (invoiceCurrencyId == AED_ID) {
                // Hide amount_currency_group - both are AED
                $('#amount_currency_group').addClass('d-none');
                $('#amount_in_currency').val(paymentTotal);
            } else {
                // Show amount_currency_group - need to convert AED to invoice currency
                $('#amount_currency_group').removeClass('d-none');
                fetchConversionToInvoiceCurrency(paymentCurrencyId, invoiceCurrencyId, paymentTotal);
            }
        }
        // Payment currency is NOT AED
        else {
            // Show currency_rate_group - need to convert to AED
            $('#currency_rate_group').removeClass('d-none');

            // Check if payment currency matches invoice currency
            if (paymentCurrencyId == invoiceCurrencyId) {
                // Same currency - hide amount_currency_group
                $('#amount_currency_group').addClass('d-none');
                $('#amount_in_currency').val(paymentTotal);

                // Still fetch rate to AED (payment currency to AED)
                fetchRateToAED(paymentCurrencyId);
            } else {
                // Different currencies - show amount_currency_group
                $('#amount_currency_group').removeClass('d-none');

                // If invoice currency is AED, fetch rate from payment currency to AED
                if (invoiceCurrencyId == AED_ID) {
                    fetchRateToAED(paymentCurrencyId);
                }
                // Invoice currency is NOT AED - fetch both conversions
                else {
                    fetchConversionToInvoiceCurrency(paymentCurrencyId, invoiceCurrencyId, paymentTotal);
                    fetchRateToAED(paymentCurrencyId);
                }
            }
        }
    }

    /**
     * Handle amount or charge changes
     * Recalculate amount_in_currency if needed
     */
    $(document).on('input', '#amount, #charge', function() {
        const paymentCurrencyId = $('#currency_id').val();
        const paymentTotal = getPaymentTotal();

        // Only recalculate if payment currency differs from invoice currency
        if (paymentCurrencyId && paymentCurrencyId != invoiceCurrencyId) {
            // If invoice currency is AED, use currency_rate
            if (invoiceCurrencyId == AED_ID) {
                updateAmountInCurrency();
            }
        } else {
            // Same currency - just update amount_in_currency
            $('#amount_in_currency').val(paymentTotal);
        }
    });

    /**
     * Handle currency_rate changes
     * Recalculate amount_in_currency when rate is manually changed
     */
    $(document).on('input', '#currency_rate', function() {
        const paymentCurrencyId = $('#currency_id').val();

        // Only recalculate if showing currency_rate_group
        if ($('#currency_rate_group').hasClass('d-none') === false) {
            updateAmountInCurrency();
        }
    });

    /**
     * Handle payment currency selection change
     */
    $(document).on('change', '#currency_id', function() {
        handleCurrencyChange();
    });
</script>
