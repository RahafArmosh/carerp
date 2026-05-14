<form method="POST" action="{{ route('customerpayment.allocate.store', $payment->id) }}" style="
    padding: 20px;
">
    @csrf

    <div class="table-responsive">
        @php
            $totalPaymentAmount = $payment->amount;
            $allocatedAmount = $payment->invoicePayments->sum('amount');
            $remainingAmount = $totalPaymentAmount - $allocatedAmount;
        @endphp

        <div class="alert alert-info mb-3">
            <strong>{{ __('Total Payment:') }}</strong> {{ Auth::user()->priceFormat($totalPaymentAmount) }} <br>
            <strong>{{ __('Already Allocated:') }}</strong> <span id="allocated-amount-display">
                {{ Auth::user()->priceFormat($allocatedAmount) }}
            </span><br>
            <strong>{{ __('Remaining to Allocate:') }}</strong> <span id="remaining-amount-display">
                {{ Auth::user()->priceFormat($remainingAmount) }}
            </span>
        </div>

        <div class="text-end mb-3">
            <button type="button" class="btn btn-secondary" id="auto-fill">{{ __('Auto-Fill Invoices') }}</button>
        </div>
        <div class="progress my-3">
            <div id="allocation-progress" class="progress-bar bg-success" style="width: 0%">
                0%
            </div>
        </div>
        <table class="table align-items-center">
            <thead>
                <tr>
                    <th>{{ __('Invoice No') }}</th>
                    <th>{{ __('Invoice Date') }}</th>
                    <th>{{ __('Invoice Total Amount') }}</th>
                    <th>{{ __('Due Amount') }}</th>
                    <th>{{ __('Allocate Amount') }}</th>
                    <th>{{ __('Amount In Invoice Currency') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    @php
                        $paidAmount = $invoice->getTotalPaid();
                        $dueAmount = $invoice->getDue();
                    @endphp
                    <tr>
                        <td>{{ Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d') }}</td>
                        <td>{{ $invoice->currency_id != null ? Auth::user()->priceFormatCurr($invoice->getTotalExchange(), $invoice->currency->symbol) : Auth::user()->priceFormat($invoice->getTotal()) }}
                        </td>
                        <td>{{ Auth::user()->priceFormat($dueAmount) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $dueAmount }}"
                                name="allocations[{{ $invoice->id }}][amount]" class="form-control allocate-amount"
                                placeholder="{{ __('Enter amount') }}">
                        </td>
                        <td>
                            @if ($invoice->currency_id != null && $invoice->currency_id != 4)
                                <input type="number" name="allocations[{{ $invoice->id }}][amount_in_currency]"
                                    step="0.01" min="0" ` class="form-control rate-input"
                                    placeholder="{{ __('Enter amount in invoice currency') }}">
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">{{ __('No unpaid invoices found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">{{ __('Allocate Payment') }}</button>
    </div>
</form>
<script>
    // Initialize allocation form script
    function initAllocationForm() {
        const allocateInputs = document.querySelectorAll('input[name*="[amount]"]');
        const form = document.querySelector('form');
        const remainingDisplay = document.getElementById('remaining-amount-display');
        const allocatedDisplay = document.getElementById('allocated-amount-display');
        const submitBtn = document.querySelector('button[type="submit"]');
        const progressBar = document.getElementById('allocation-progress');
        const autoFillBtn = document.getElementById('auto-fill');

        if (!allocateInputs.length || !autoFillBtn) {
            console.log('Allocation form elements not found, skipping init');
            return;
        }

        const paymentAmount = {{ (float) $remainingAmount }};
        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'AED',
            minimumFractionDigits: 2
        });

        function getMaxDue(input) {
            return parseFloat(input.getAttribute('max')) || 0;
        }

        function calculateAndUpdate() {
            let total = 0;

            allocateInputs.forEach(input => {
                const val = parseFloat(input.value);
                if (!isNaN(val)) total += val;
            });

            const remaining = paymentAmount - total;

            // Update text displays
            remainingDisplay.innerHTML = remaining < 0 ?
                `<span class="text-danger">${formatter.format(remaining)}</span>` :
                formatter.format(remaining);

            allocatedDisplay.innerHTML = formatter.format(total);

            // Update progress bar
            let percent = Math.min((total / paymentAmount) * 100, 100);
            progressBar.style.width = percent.toFixed(2) + '%';
            progressBar.innerHTML = percent.toFixed(2) + '%';

            // Disable submit if over-allocated
            submitBtn.disabled = remaining < 0;

            // Validation per input
            allocateInputs.forEach(input => {
                const val = parseFloat(input.value);
                const maxPerBill = getMaxDue(input);
                if (isNaN(val) || val < 0 || val > maxPerBill || total > paymentAmount) {
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
        }

        // Bind input listeners
        allocateInputs.forEach(input => {
            input.addEventListener('input', calculateAndUpdate);
        });

        // Bind form submit
        if (form) {
            form.addEventListener('submit', function(e) {
                let total = 0;
                allocateInputs.forEach(input => {
                    const val = parseFloat(input.value);
                    if (!isNaN(val)) total += val;
                });

                if (total > paymentAmount) {
                    e.preventDefault();
                    alert("Total allocated amount cannot exceed the available payment.");
                }
            });
        }

        // Bind auto-fill button
        if (autoFillBtn) {
            autoFillBtn.addEventListener('click', function() {
                let remaining = paymentAmount;
                allocateInputs.forEach(input => {
                    const maxDue = getMaxDue(input);
                    const valueToFill = Math.min(maxDue, remaining);
                    input.value = valueToFill.toFixed(2);
                    remaining -= valueToFill;
                });
                calculateAndUpdate();
            });
        }

        calculateAndUpdate();
    }

    // Call on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllocationForm);
    } else {
        // If page is already loaded (modal injected via AJAX), initialize immediately
        initAllocationForm();
    }

    // Re-initialize when Bootstrap modal is shown (AJAX-loaded modals)
    document.addEventListener('shown.bs.modal', function(e) {
        if (e.target && e.target.querySelector('input[name*="[amount]"]')) {
            initAllocationForm();
        }
    });

    // Support for custom AJAX library events (if used)
    if (window.jQuery) {
        jQuery(document).on('ajaxComplete', function() {
            // Delay slightly to ensure DOM is fully rendered
            setTimeout(initAllocationForm, 100);
        });
    }
</script>
