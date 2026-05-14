@if ($invoice->status == 0)
    <span
        class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(App\Models\Invoice::$statues[$invoice->status]) }}</span>
@elseif($invoice->status == 1)
    <span
        class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(App\Models\Invoice::$statues[$invoice->status]) }}</span>
@elseif($invoice->status == 2)
    <span
        class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(App\Models\Invoice::$statues[$invoice->status]) }}</span>
@elseif($invoice->status == 4)
    <span
        class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(App\Models\Invoice::$statues[$invoice->status]) }}</span>
@elseif($invoice->status == 6)
    <span
        class="status_badge badge bg-info p-2 px-3 rounded">{{ __(App\Models\Invoice::$statues[$invoice->status]) }}</span>
@endif
