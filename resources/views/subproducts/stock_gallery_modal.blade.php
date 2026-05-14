<div class="modal-body p-3">
    @php
        $ps = $subProduct->productService;
        $label = $ps
            ? trim(implode(' / ', array_filter([
                optional($ps->brand)->name,
                optional($ps->subBrand)->name,
                $ps->name,
            ])))
            : '';
    @endphp
    @if ($label !== '')
        <p class="mb-2"><strong>{{ __('Product') }}:</strong> {{ $label }}</p>
    @endif
    <p class="text-muted mb-3">
        <strong>{{ __('Chassis No') }}:</strong> {{ $subProduct->chassis_no ?? '—' }}
        <span class="ms-2">#{{ $subProduct->id }}</span>
    </p>
    @forelse ($subProduct->images as $img)
        <div class="mb-3 text-center">
            <img src="{{ $img->url() }}" class="img-fluid rounded border" style="max-height: 360px; object-fit: contain;"
                alt="">
        </div>
    @empty
        <p class="text-muted mb-0">{{ __('No images uploaded for this item.') }}</p>
    @endforelse
</div>
