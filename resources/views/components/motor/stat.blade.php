@props([
    'label',
    'value',
    'hint' => null,
    'iconClass' => 'ti ti-gauge',
])

<div
    {{ $attributes->merge([
        'class' =>
            'group relative overflow-hidden rounded-motor border border-motor-border bg-motor-elevated p-6 shadow-motor transition-[box-shadow,transform] duration-300 ease-motor-out hover:shadow-motor-lg',
    ]) }}>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-motor-accent/5 via-transparent to-motor-accent2/8 opacity-90">
    </div>
    <div class="relative flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="motor-sm font-medium uppercase tracking-[0.06em] text-motor-muted">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-motor-ink">{{ $value }}</p>
            @if ($hint)
                <p class="mt-2 motor-sm text-motor-subtle">{{ $hint }}</p>
            @endif
        </div>
        <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-motor-accent/12 text-motor-accent ring-1 ring-motor-ring/20">
            <i class="{{ $iconClass }} text-xl leading-none"></i>
        </div>
    </div>
</div>
