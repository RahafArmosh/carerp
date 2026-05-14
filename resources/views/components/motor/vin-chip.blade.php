@props(['vin'])

<span
    {{ $attributes->merge([
        'class' =>
            'motor-text-vin bg-motor-canvas/80 font-mono uppercase text-motor-ink dark:bg-motor-canvas/35',
    ]) }}
    title="{{ __('Vehicle identification number') }}">{{ $vin }}</span>
