@props(['title', 'time' => null, 'accent' => false])

<li class="relative flex gap-4 border-s border-motor-border ps-6">
    <span
        class="absolute -start-[5px] top-1 h-2.5 w-2.5 rounded-full ring-4 ring-motor-canvas {{ $accent ? 'bg-motor-accent' : 'bg-motor-subtle' }}"></span>
    <div class="min-w-0 pb-6">
        @if ($time)
            <p class="motor-xs font-mono uppercase tracking-wider text-motor-muted">{{ $time }}</p>
        @endif
        <p class="mt-1 text-motor-base font-medium text-motor-ink">{{ $title }}</p>
        @if (isset($slot) && strlen(trim((string) $slot)) > 0)
            <div class="mt-2 motor-sm text-motor-muted">{{ $slot }}</div>
        @endif
    </div>
</li>
