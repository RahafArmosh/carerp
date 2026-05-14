@php
    $currentRoute = Request::route()?->getName();
    $segment1 = Request::segment(1);
    $menuItems = [
        ['route' => 'taxes.index', 'label' => __('Taxes')],
        ['route' => 'product-category.index', 'label' => __('Category')],
        ['route' => 'brand.index', 'label' => __('Brand')],
        ['route' => 'sub-brand.index', 'label' => __('Model')],
        ['route' => 'product-unit.index', 'label' => __('Unit')],
        ['route' => 'custom-field.index', 'label' => __('Custom Field')],
        ['route' => 'currency.index', 'label' => __('Currencies')],
        ['route' => 'countries.index', 'label' => __('Countries'), 'segment' => 'countries'],
        ['route' => 'warehouse.index', 'label' => __('Warehouse')],
        ['route' => 'chart-of-account-type.index', 'label' => __('Account Type')],
        ['route' => 'chart-of-account-sub-type.index', 'label' => __('Account Sub Type')],
    ];
@endphp

<div class="card sticky-top account-setup-sidebar" style="top:30px">
    <div class="list-group list-group-flush" id="useradd-sidenav">
        @foreach ($menuItems as $item)
            <a href="{{ route($item['route']) }}"
                class="list-group-item list-group-item-action border-0 d-flex align-items-center justify-content-between account-setup-link {{ ($currentRoute === $item['route'] || (!empty($item['segment']) && $segment1 === $item['segment'])) ? 'active' : '' }}">
                <span>{{ $item['label'] }}</span>
                <i class="ti ti-chevron-right"></i>
            </a>
        @endforeach
    </div>
</div>
