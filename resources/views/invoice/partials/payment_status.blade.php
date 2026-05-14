@if ($invoice->payment_status == 0)
    <span
        class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(App\Models\Invoice::$paymentstatues[$invoice->payment_status]) }}</span>
@elseif($invoice->payment_status == 2)
    <span
        class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(App\Models\Invoice::$paymentstatues[$invoice->payment_status]) }}</span>
@elseif($invoice->payment_status == 4)
    <span
        class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(App\Models\Invoice::$paymentstatues[$invoice->payment_status]) }}</span>
@endif
