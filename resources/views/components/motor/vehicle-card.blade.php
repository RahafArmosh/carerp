@props([
    'title',
    'subtitle' => null,
    'status' => null,
    'statusVariant' => 'neutral',
    'image' => null,
    'meta' => [],
])

@php
    $statusRing = match ($statusVariant) {
        'success' => 'ring-emerald-500/55 text-emerald-400',
        'warning' => 'ring-amber-500/50 text-amber-300',
        'danger' => 'ring-rose-500/50 text-rose-300',
        default => 'ring-white/10 text-slate-200',
    };
@endphp

<article
    {{ $attributes->merge([
        'class' =>
            'flex flex-col overflow-hidden rounded-motor border border-motor-border bg-motor-elevated shadow-motor transition-[box-shadow,transform] duration-300 ease-motor-out hover:-translate-y-0.5 hover:shadow-motor-lg',
    ]) }}>
    <div class="relative aspect-[16/10] w-full bg-motor-canvas">
        @if ($image)
            <img src="{{ $image }}" alt="" class="h-full w-full object-cover" loading="lazy">
        @else
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="ti ti-car text-5xl text-motor-subtle"></i>
            </div>
        @endif
        @if ($status)
            <span
                class="absolute end-3 top-3 inline-flex items-center rounded-full bg-slate-950/70 px-2.5 py-1 motor-xs font-semibold ring-1 {{ $statusRing }} backdrop-blur">
                {{ $status }}
            </span>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 p-5">
        <h3 class="text-motor-lg font-semibold tracking-tight text-motor-ink">{{ $title }}</h3>
        @if ($subtitle)
            <p class="motor-sm leading-relaxed text-motor-muted">{{ $subtitle }}</p>
        @endif
        @if (count($meta))
            <dl class="mt-2 grid gap-2 border-t border-motor-border pt-3">
                @foreach ($meta as $k => $v)
                    <div class="flex justify-between gap-3 motor-sm">
                        <dt class="text-motor-muted">{{ $k }}</dt>
                        <dd class="text-right font-medium text-motor-ink">{{ $v }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</article>
