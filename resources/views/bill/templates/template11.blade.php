@php
    $settings_data = \App\Models\Utility::settingsById($bill->created_by);
    $billLogo = $logo_path ?? ($img ?? '');
    $companyEmail = trim(
        (string) ($settings['company_email'] ?? $settings['mail_from_address'] ?? $settings_data['company_email'] ?? ''),
    );
    $companyName = trim((string) ($settings['company_name'] ?? $settings_data['company_name'] ?? ''));
    $stampFile = $settings['company_stamp'] ?? $settings_data['company_stamp'] ?? '';
    $stampUrl = !empty($stampFile) ? url('documents/' . $stampFile) : null;
    $billNoDisplay = \App\Models\Utility::billNumberFormat($settings, $bill->bill_id);
    $issueDate = $bill->bill_date ?? $bill->issue_date ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ ($settings_data['SITE_RTL'] ?? '') == 'on' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: #111;
            background: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .page {
            max-width: 720px;
            margin: 0 auto;
        }

        .top-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .top-row td {
            vertical-align: top;
            padding: 0;
        }

        .doc-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin: 0 0 12px 0;
            line-height: 1;
        }

        .under-title {
            font-size: 13px;
            line-height: 1.55;
            color: #222;
        }

        .under-title strong {
            font-weight: 600;
        }

        .logo-wrap {
            text-align: right;
        }

        .logo-wrap img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
        }

        .mid-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .mid-row td {
            vertical-align: top;
            width: 50%;
            padding: 0 16px 0 0;
        }

        .mid-row td:last-child {
            padding-right: 0;
            padding-left: 16px;
        }

        .label-strong {
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-table td:first-child {
            font-weight: 700;
            width: 42%;
        }

        .meta-table td:last-child {
            text-align: right;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items thead th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-align: left;
            padding: 10px 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .items thead th:nth-child(2),
        .items thead th:nth-child(3),
        .items thead th:nth-child(4) {
            text-align: right;
        }

        .items tbody td {
            padding: 12px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e5e5e5;
        }

        .items tbody td:nth-child(2),
        .items tbody td:nth-child(3),
        .items tbody td:nth-child(4) {
            text-align: right;
            white-space: nowrap;
        }

        .totals-wrap {
            margin-top: 20px;
            width: 100%;
        }

        .totals-wrap table {
            margin-left: auto;
            width: 280px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .totals-wrap td {
            padding: 6px 0;
        }

        .totals-wrap td:first-child {
            font-weight: 600;
        }

        .totals-wrap td:last-child {
            text-align: right;
        }

        .totals-wrap tr.grand td {
            font-weight: 700;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #000;
            width: 100%;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: bottom;
            padding: 0;
        }

        .footer-address {
            font-size: 12px;
            line-height: 1.6;
            color: #222;
            max-width: 65%;
        }

        .stamp-cell {
            text-align: right;
        }

        .stamp-cell img {
            max-width: 140px;
            max-height: 100px;
            object-fit: contain;
        }

        .tax-line {
            font-size: 11px;
            color: #444;
            margin-top: 2px;
        }

        html[dir="rtl"] .meta-table td:last-child,
        html[dir="rtl"] .items thead th:nth-child(2),
        html[dir="rtl"] .items thead th:nth-child(3),
        html[dir="rtl"] .items thead th:nth-child(4),
        html[dir="rtl"] .items tbody td:nth-child(2),
        html[dir="rtl"] .items tbody td:nth-child(3),
        html[dir="rtl"] .items tbody td:nth-child(4),
        html[dir="rtl"] .totals-wrap td:last-child {
            text-align: left;
        }

        html[dir="rtl"] .logo-wrap {
            text-align: left;
        }

        html[dir="rtl"] .stamp-cell {
            text-align: left;
        }
    </style>
    @if (($settings_data['SITE_RTL'] ?? '') == 'on')
        <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
    @endif
</head>

<body>
    <div class="page" id="boxes">
        <table class="top-row">
            <tr>
                <td>
                    <h1 class="doc-title">{{ __('Bill') }}</h1>
                    <div class="under-title">
                        <div><strong>{{ __('Bill No.') }}</strong> {{ $billNoDisplay }}</div>
                        @if ($companyName !== '')
                            <div><strong>{{ __('Company') }}</strong> {{ $companyName }}</div>
                        @endif
                        @if ($companyEmail !== '')
                            <div><strong>{{ __('Email') }}</strong> {{ $companyEmail }}</div>
                        @endif
                    </div>
                </td>
                <td class="logo-wrap">
                    @if (!empty($billLogo))
                        <img src="{{ $billLogo }}" alt="{{ __('Logo') }}">
                    @endif
                </td>
            </tr>
        </table>

        <table class="mid-row">
            <tr>
                <td>
                    <span class="label-strong">{{ __('Issued to') }}</span>
                    @if (!empty($vendor->name))
                        <div style="line-height:1.6;">
                            {{ $vendor->name }}<br>
                            @if (!empty($vendor->billing_address))
                                {{ $vendor->billing_address }}<br>
                            @endif
                            @php
                                $cityLine = trim(
                                    implode(', ', array_filter([
                                        $vendor->billing_city ?? '',
                                        $vendor->billing_state ?? '',
                                        $vendor->billing_zip ?? '',
                                    ])),
                                );
                            @endphp
                            @if ($cityLine !== '')
                                {{ $cityLine }}<br>
                            @endif
                            @if (!empty($vendor->billing_country))
                                {{ $vendor->billing_country }}<br>
                            @endif
                            @if (!empty($vendor->billing_phone))
                                {{ $vendor->billing_phone }}<br>
                            @endif
                            @if (!empty($vendor->email))
                                {{ $vendor->email }}
                            @endif
                        </div>
                    @else
                        —
                    @endif
                </td>
                <td>
                    <table class="meta-table">
                        <tr>
                            <td>{{ __('Bill No.') }}</td>
                            <td>{{ $billNoDisplay }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Date') }}</td>
                            <td>{{ $issueDate ? \App\Models\Utility::dateFormat($settings, $issueDate) : '—' }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Due date') }}</td>
                            <td>{{ !empty($bill->due_date) ? \App\Models\Utility::dateFormat($settings, $bill->due_date) : '—' }}
                            </td>
                        </tr>
                    </table>
                    @if (!empty($customFields) && !empty($bill->customField) && count($bill->customField) > 0)
                        <table class="meta-table" style="margin-top:12px;">
                            @foreach ($customFields as $field)
                                <tr>
                                    <td>{{ $field->name }}</td>
                                    <td>{{ $bill->customField[$field->id] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Rate') }}</th>
                    <th>{{ __('Qty') }}</th>
                    <th>{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($bill->itemData) && count($bill->itemData) > 0)
                    @foreach ($bill->itemData as $item)
                        @php
                            $unitName = \App\Models\ProductServiceUnit::find($item->unit);
                            $unitLabel = !empty($unitName) ? $unitName->name : '';
                            $itemtax = 0;
                        @endphp
                        <tr>
                            <td>
                                {{ $item->name }}
                                @if (!empty($item->description))
                                    <div class="tax-line">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td>{{ \App\Models\Utility::priceFormat($settings, $item->price) }}</td>
                            <td>{{ $item->quantity }}@if ($unitLabel !== '') ({{ $unitLabel }}) @endif</td>
                            <td>
                                @php
                                    foreach ($item->itemTax ?? [] as $taxes) {
                                        $itemtax += $taxes['tax_price'] ?? 0;
                                    }
                                @endphp
                                {{ \App\Models\Utility::priceFormat($settings, $item->price * $item->quantity - $item->discount + $itemtax) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" style="text-align:center;">—</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="totals-wrap">
            <table>
                <tr>
                    <td>{{ __('Subtotal') }}</td>
                    <td>{{ \App\Models\Utility::priceFormat($settings, $bill->getSubTotal()) }}</td>
                </tr>
                @if ($bill->getTotalDiscount())
                    <tr>
                        <td>{{ __('Discount') }}</td>
                        <td>{{ \App\Models\Utility::priceFormat($settings, $bill->getTotalDiscount()) }}</td>
                    </tr>
                @endif
                @if (!empty($bill->taxesData))
                    @foreach ($bill->taxesData as $taxName => $taxPrice)
                        <tr>
                            <td>{{ $taxName }}</td>
                            <td>{{ \App\Models\Utility::priceFormat($settings, $taxPrice) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr class="grand">
                    <td>{{ __('Total') }}</td>
                    <td>{{ \App\Models\Utility::priceFormat($settings, $bill->getSubTotal() - $bill->getTotalDiscount() + $bill->getTotalTax()) }}
                    </td>
                </tr>
                <tr>
                    <td>{{ __('Paid') }}</td>
                    <td>{{ \App\Models\Utility::priceFormat($settings, $bill->getTotal() - $bill->getDue() - $bill->billTotalDebitNote()) }}
                    </td>
                </tr>
                <tr>
                    <td>{{ __('Debit Note') }}</td>
                    <td>{{ \App\Models\Utility::priceFormat($settings, $bill->billTotalDebitNote()) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Due amount') }}</td>
                    <td>{{ \App\Models\Utility::priceFormat($settings, $bill->getDue()) }}</td>
                </tr>
            </table>
        </div>

        @if (!empty($settings['footer_title']) || !empty($settings['footer_notes']))
            <div style="margin-top:24px;font-size:12px;line-height:1.5;">
                @if (!empty($settings['footer_title']))
                    <strong>{{ $settings['footer_title'] }}</strong><br>
                @endif
                @if (!empty($settings['footer_notes']))
                    {!! $settings['footer_notes'] !!}
                @endif
            </div>
        @endif

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-address">
                        @if (!empty($settings['company_address']) || !empty($settings['company_city']) || !empty($settings['company_country']))
                            <span class="label-strong" style="margin-bottom:6px;">{{ __('Company address') }}</span>
                            @if (!empty($settings['company_address']))
                                {{ $settings['company_address'] }}<br>
                            @endif
                            @php
                                $coLine = trim(
                                    implode(', ', array_filter([
                                        $settings['company_city'] ?? '',
                                        $settings['company_state'] ?? '',
                                        $settings['company_zipcode'] ?? '',
                                    ])),
                                );
                            @endphp
                            @if ($coLine !== '')
                                {{ $coLine }}<br>
                            @endif
                            @if (!empty($settings['company_country']))
                                {{ $settings['company_country'] }}
                            @endif
                        @endif
                    </td>
                    <td class="stamp-cell">
                        <span class="label-strong" style="margin-bottom:6px;">{{ __('Stamp') }}</span>
                        @if (!empty($stampUrl))
                            <img src="{{ $stampUrl }}?t={{ time() }}" alt="{{ __('Company stamp') }}">
                        @else
                            <span style="font-size:12px;color:#888;">—</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if (!isset($preview))
        @include('bill.script')
    @endif
</body>

</html>
