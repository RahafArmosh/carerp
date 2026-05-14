<form method="POST" action="{{ route('payment.allocate.store', $payment->id) }}" style="
    padding: 20px;
">
    @csrf

    <div class="table-responsive">
        @php
            $totalPaymentAmount = $payment->amount;
            $allocatedAmount = $payment->billPayments->sum('amount'); // use billPayments not bills
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
            <button type="button" class="btn btn-secondary" id="auto-fill">{{ __('Auto-Fill Bills') }}</button>
        </div>
        <div class="progress my-3">
            <div id="allocation-progress" class="progress-bar bg-success" style="width: 0%">
                0%
            </div>
        </div>
        <table class="table align-items-center">
            <thead>
                <tr>
                    <th>{{ __('Bill No') }}</th>
                    <th>{{ __('Bill Date') }}</th>
                    <th>{{ __('Bill Total Amount') }}</th>
                    <th>{{ __('Due Amount') }}</th>
                    <th>{{ __('Allocate Amount') }}</th>
                    <th>{{ __('Amount In Bill Currency') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bills as $bill)
                    @php
                        $paidAmount = $bill->getTotalPaid();
                        $dueAmount = $bill->getDue();
                    @endphp
                    <tr>
                        <td>{{ Auth::user()->billNumberFormat($bill->bill_id) }}</td>
                        <td>{{ \Carbon\Carbon::parse($bill->bill_date)->format('Y-m-d') }}</td>
                        <td>{{ $bill->currency_id != null ? Auth::user()->priceFormatCurr($bill->getTotalExchange(), $bill->currency->symbol) : Auth::user()->priceFormat($bill->getTotal()) }}
                        </td>
                        <td>{{ Auth::user()->priceFormat($dueAmount) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="{{ $dueAmount }}"
                                name="allocations[{{ $bill->id }}][amount]" class="form-control allocate-amount"
                                placeholder="{{ __('Enter amount') }}">
                        </td>
                        <td>
                            @if ($bill->currency_id != null && $bill->currency_id != 4)
                                <div class="input-group">

                                    <input type="number" step="0.01" min="0"
                                        name="allocations[{{ $bill->id }}][amount_in_currency]"
                                        class="form-control rate-input"
                                        placeholder="{{ __('Enter amount in bill currency') }}">
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">{{ __('No unpaid bills found.') }}</td>
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
    const allocateInputs = document.querySelectorAll('input[name*="[amount]"]');
    const remainingDisplay = document.getElementById('remaining-amount-display');
    const allocatedDisplay = document.getElementById('allocated-amount-display');
    const submitBtn = document.querySelector('button[type="submit"]');
    const progressBar = document.getElementById('allocation-progress');
    const autoFillBtn = document.getElementById('auto-fill');

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

    allocateInputs.forEach(input => {
        input.addEventListener('input', calculateAndUpdate);
    });

    document.querySelector('form').addEventListener('submit', function(e) {
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
</script>
