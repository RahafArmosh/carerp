<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>{{ $subProduct->chassis_no ?? __('Item') }} — {{ __('Sub product brochure') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header td {
            vertical-align: top;
        }

        .header .logo-cell {
            width: 150px;
            padding-right: 12px;
        }

        .header .logo-cell img {
            max-width: 140px;
            max-height: 64px;
        }

        .brand {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }

        .tag {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        h1 {
            font-size: 18px;
            margin: 8px 0 4px;
            color: #0f172a;
        }

        .sku {
            font-size: 12px;
            color: #475569;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .grid td {
            vertical-align: top;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .grid td.label {
            width: 28%;
            font-weight: bold;
            color: #334155;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin: 18px 0 10px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        .images {
            margin-top: 8px;
        }

        .img-block {
            margin-bottom: 14px;
            text-align: center;
            page-break-inside: avoid;
        }

        .img-block img {
            max-width: 100%;
            max-height: 220px;
            border: 1px solid #e2e8f0;
        }

        .img-caption {
            font-size: 9px;
            color: #64748b;
            margin-top: 4px;
        }

        .desc {
            line-height: 1.5;
            color: #334155;
        }

        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            text-align: center;
        }

        .footer .contact {
            margin-bottom: 8px;
            line-height: 1.45;
        }
    </style>
</head>

<body>
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if (!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" alt="">
                @endif
            </td>
            <td>
                <div class="tag">{{ __('Sub product brochure') }}</div>
                <div class="brand">{{ $settings['company_name'] ?? config('app.name', 'AutoCore') }}</div>
                <h1>{{ $subProduct->chassis_no ?? __('Chassis') }}</h1>
                <div class="sku">{{ __('Parent SKU') }}: {{ optional($productService)->sku ?? '—' }}</div>
                <div class="sku">{{ __('Item ID') }}: {{ $subProduct->id }}</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td class="label">{{ __('Product name') }}</td>
            <td>{{ optional($productService)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Category') }}</td>
            <td>{{ optional(optional($productService)->category)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Brand') }}</td>
            <td>{{ optional(optional($productService)->brand)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Model') }}</td>
            <td>{{ optional(optional($productService)->subBrand)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Unit') }}</td>
            <td>{{ optional(optional($productService)->unit)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Quantity') }}</td>
            <td>{{ number_format((float) ($subProduct->quantity ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Sale price') }}</td>
            <td>{{ number_format((float) ($subProduct->sale_price ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Purchase price') }}</td>
            <td>{{ number_format((float) ($subProduct->purchase_price ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Warehouse') }}</td>
            <td>{{ optional($subProduct->warehouse)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Purchase status') }}</td>
            <td>{{ $subProduct->getFlagLabel() }}</td>
        </tr>
    </table>

    @if (!empty($customFieldRows))
        <div class="section-title">{{ __('Custom fields') }}</div>
        <table class="grid">
            @foreach ($customFieldRows as $row)
                <tr>
                    <td class="label">{{ __($row['label']) }}</td>
                    <td>
                        @if (($row['type'] ?? 'text') === 'textarea')
                            {!! nl2br(e($row['value'])) !!}
                        @else
                            {{ $row['value'] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($productService && !empty($productService->description))
        <div class="section-title">{{ __('Description') }} ({{ __('Product') }})</div>
        <div class="desc">{!! nl2br(e($productService->description)) !!}</div>
    @endif

    @if (count($imageBlocks) > 0)
        <div class="section-title">{{ __('Item images') }}</div>
        <div class="images">
            @foreach ($imageBlocks as $block)
                <div class="img-block">
                    <img src="{{ $block['src'] }}" alt="">
                    @if (!empty($block['caption']))
                        <div class="img-caption">{{ $block['caption'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @php
        $footerAddress = trim(implode(', ', array_filter([
            $settings['company_address'] ?? '',
            $settings['company_city'] ?? '',
            $settings['company_state'] ?? '',
            $settings['company_zipcode'] ?? '',
            $settings['company_country'] ?? '',
        ])));
    @endphp
    <div class="footer">
        <div class="contact">
            @if (!empty($settings['company_email']))
                <div>{{ __('Email') }}: {{ $settings['company_email'] }}</div>
            @endif
            @if (!empty($settings['company_telephone']))
                <div>{{ __('Phone') }}: {{ $settings['company_telephone'] }}</div>
            @endif
            @if ($footerAddress !== '')
                <div>{{ __('Address') }}: {{ $footerAddress }}</div>
            @endif
        </div>
        <div>{{ __('Generated') }}: {{ now()->format('Y-m-d H:i') }}
            — {{ $settings['company_name'] ?? config('app.name') }}</div>
    </div>
</body>

</html>
