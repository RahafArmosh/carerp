<form action="{{ route('bill.payment.store', $bill->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" id="date" name="date" class="form-control" required="required">

            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" value="{{ number_format($bill->getDue(), 2, '.', '') }}" class="form-control"
                    required="required" step="0.01" min="0">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2" required>
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

            <div class="form-group col-md-6 d-none" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency"
                        name="amount_in_currency" class="form-control" value="{{ number_format($bill->getDueInCurrency(), 2, '.', '') }}">
                    <span class="input-group-text" id="currency_symbol_span">{{ $currency_symbol }}</span>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select id="account_id" name="account_id" class="form-control" required="required">
                    @foreach ($accounts as $accountId => $accountName)
                        <option value="{{ $accountId }}">{{ $accountName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" id="reference" name="reference" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            </div>



            <div class="col-md-6 form-group">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <div class="choose-file ">
                    <label for="file" class="form-label">
                        <input type="file" name="add_receipt" id="image" class="form-control">
                    </label>
                    <p class="upload_file"></p>
                </div>
            </div>

        </div>
        <div class="modal-footer">

            <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Add') }}" class="btn  btn-primary">
        </div>

    </div>
</form>

<script>
    // Constants
    const AED_CODE = "AED";
    const AED_ID = "{{ \App\Models\Currency::where('code', 'AED')->value('id') }}";

    // Bill currency defaults to AED if null (system default)
    const billCurrencyId = "{{ $bill->currency_id }}" || AED_ID;

    /**
     * Get payment amount
     */
    function getPaymentAmount() {
        return parseFloat($('#amount').val()) || 0;
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
     * Fetch conversion from payment currency to bill currency
     */
    function fetchConversionToBillCurrency(paymentCurrencyId, billCurrencyId, amount) {
        $.ajax({
            url: '{{ route('currency.convert') }}',
            method: 'GET',
            data: {
                from_id: paymentCurrencyId,
                to_id: billCurrencyId,
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
     * Update amount_in_currency based on currency_rate and payment amount
     * Used when payment currency is not bill currency and rate is available
     */
    function updateAmountInCurrency() {
        const paymentAmount = getPaymentAmount();
        const rate = parseFloat($('#currency_rate').val()) || 0;

        if (rate > 0) {
            $('#amount_in_currency').val((paymentAmount * rate).toFixed(2));
        }
    }

    /**
     * Handle currency change logic
     * Determines visibility and values for currency_rate_group and amount_currency_group
     */
    function handleCurrencyChange() {
        const paymentCurrencyId = $('#currency_id').val();
        const paymentAmount = getPaymentAmount();

        // If payment currency is AED (same as system default)
        if (paymentCurrencyId == AED_ID) {
            // Hide currency_rate_group - rate is always 1 for AED
            $('#currency_rate_group').addClass('d-none');
            $('#currency_rate').val(1);

            // Check if bill currency is also AED
            if (billCurrencyId == AED_ID) {
                // Hide amount_currency_group - both are AED
                $('#amount_currency_group').addClass('d-none');
                $('#amount_in_currency').val(paymentAmount);
            } else {
                // Show amount_currency_group - need to convert AED to bill currency
                $('#amount_currency_group').removeClass('d-none');
                fetchConversionToBillCurrency(paymentCurrencyId, billCurrencyId, paymentAmount);
            }
        }
        // Payment currency is NOT AED
        else {
            // Show currency_rate_group - need to convert to AED
            $('#currency_rate_group').removeClass('d-none');

            // Check if payment currency matches bill currency
            if (paymentCurrencyId == billCurrencyId) {
                // Same currency - hide amount_currency_group
                $('#amount_currency_group').addClass('d-none');
                $('#amount_in_currency').val(paymentAmount);

                // Still fetch rate to AED (payment currency to AED)
                fetchRateToAED(paymentCurrencyId);
            } else {
                // Different currencies - show amount_currency_group
                $('#amount_currency_group').removeClass('d-none');

                // If bill currency is AED, fetch rate from payment currency to AED
                if (billCurrencyId == AED_ID) {
                    fetchRateToAED(paymentCurrencyId);
                }
                // Bill currency is NOT AED - fetch both conversions
                else {
                    fetchConversionToBillCurrency(paymentCurrencyId, billCurrencyId, paymentAmount);
                    fetchRateToAED(paymentCurrencyId);
                }
            }
        }
    }

    /**
     * Handle amount changes
     * Recalculate amount_in_currency if needed
     */
    $(document).on('input', '#amount', function() {
        const paymentCurrencyId = $('#currency_id').val();
        const paymentAmount = getPaymentAmount();

        // Only recalculate if payment currency differs from bill currency
        if (paymentCurrencyId && paymentCurrencyId != billCurrencyId) {
            // If bill currency is AED, use currency_rate
            if (billCurrencyId == AED_ID) {
                updateAmountInCurrency();
            }
        } else {
            // Same currency - just update amount_in_currency
            $('#amount_in_currency').val(paymentAmount);
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
