@php
    $reminders = $headerDealReminders ?? collect();
@endphp
@if($reminders->count() > 0)
    <div style="background:#dc2626;color:#fff;padding:8px 12px;border-radius:4px;margin:12px 12px 0 12px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <strong>{{ __('Attention') }}:</strong>
            {{ __('You have') }} {{ $reminders->count() }} {{ __('pending deal reminder(s)') }}.
        </div>
        <div>
            <a href="{{ route('deal-reminders.index') }}" style="color:#fff;text-decoration:underline;">{{ __('View Reminders') }}</a>
        </div>
    </div>
@endif


