@props(['steps' => [], 'activeIndex' => 0])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-y-3']) }}>
    @foreach ($steps as $index => $label)
        @php
            $pill =
                $index === $activeIndex
                    ? 'border-motor-ring/60 bg-motor-accent/10 text-motor-ink ring-2 ring-motor-ring/35 font-semibold'
                    : ($index < $activeIndex
                        ? 'border-motor-border bg-motor-canvas text-motor-muted line-through decoration-motor-muted/60'
                        : 'border-motor-border bg-motor-elevated text-motor-muted');
        @endphp
        <span
            class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 motor-sm transition-colors duration-300 {{ $pill }}">
            <span class="font-mono motor-xs">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
            {{ $label }}
        </span>
        @if (!$loop->last)
            <i class="ti ti-chevron-right mx-2 hidden text-motor-subtle sm:inline" aria-hidden="true"></i>
        @endif
    @endforeach
</div>
