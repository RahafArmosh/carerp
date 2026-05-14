@php
    use App\Models\Utility;
    $customer = $payment->customer;
    $linkedInvoice = $payment->invoice;
    $refundNo = auth()->check()
        ? auth()->user()->paymentNumberRefundFormat($payment->id)
        : 'VR'.sprintf('%05d', $payment->id);

    $charge = (float) ($payment->charge ?? 0);
    $parts = array_filter([
        optional($payment->category)->name,
        $payment->description,
        $linkedInvoice
            ? __('Invoice').' '.\Auth::user()->invoiceNumberFormat($linkedInvoice->invoice_id)
            : null,
    ]);
    $desc = count($parts) ? implode(' | ', $parts) : __('Customer refund');
    $amt = (float) $payment->amount + $charge;
    $lineRows = [['desc' => $desc, 'rate' => $amt, 'qty' => 1, 'total' => $amt]];
    $subtotal = collect($lineRows)->sum('total');
    $taxAmount = 0.0;
    $grandTotal = $subtotal + $taxAmount;

    if ($customer) {
        $addrBits = array_filter([
            $customer->billing_address ?? null,
            trim(
                ($customer->billing_city ?? '').
                    (($customer->billing_city ?? '') && ($customer->billing_state ?? '') ? ', ' : '').
                    ($customer->billing_state ?? '').
                    (($customer->billing_zip ?? '') ? ' '.$customer->billing_zip : '')
            ) ?: null,
            $customer->billing_country ?? null,
        ]);
        $customerLine = trim(
            ($customer->billing_name ?: $customer->name).' / '.($customer->name ?? '').' / '.implode(', ', $addrBits)
        );
        if (str_replace('/', '', trim($customerLine, ' /')) === '') {
            $customerLine = $customer->name ?? '—';
        }
    } else {
        $customerLine = '—';
    }

    $company_name = $settings['company_name'] ?? '';
    $company_email_display = trim((string) ($settings['company_email'] ?? ''))
        ?: trim((string) ($settings['mail_from_address'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ ($settings_data['SITE_RTL'] ?? $settings['SITE_RTL'] ?? '') == 'on' ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Customer refund') }} {{ $refundNo }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 13px;
                color: #111;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .doc {
                max-width: 720px;
                margin: 0 auto;
                padding: 48px 40px 56px;
                background: #fff;
            }

            .doc-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 32px;
                margin-bottom: 48px;
            }

            .doc-brand {
                text-align: right;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
            }

            .doc-logo {
                max-height: 96px;
                width: auto;
                max-width: 220px;
                object-fit: contain;
            }

            .doc-company-name {
                font-size: 15px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #111;
                max-width: 280px;
                line-height: 1.3;
            }

            .doc-company-email {
                font-size: 12px;
                font-weight: 400;
                color: #333;
                max-width: 280px;
                word-break: break-all;
            }

            .doc-title {
                margin: 0;
                font-size: 2.75rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                line-height: 1.05;
            }

            .doc-meta {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 48px;
                margin-bottom: 40px;
            }

            .meta-label {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                margin-bottom: 10px;
            }

            .meta-addr {
                font-size: 13px;
                line-height: 1.55;
                max-width: 360px;
            }

            .meta-right {
                flex-shrink: 0;
                text-align: right;
            }

            .meta-right table {
                border-collapse: collapse;
                margin-left: auto;
            }

            .meta-right th {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                padding: 4px 20px 4px 0;
                vertical-align: top;
                text-align: right;
                white-space: nowrap;
            }

            .meta-right td {
                padding: 4px 0;
                text-align: right;
                font-size: 13px;
            }

            .lines {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
            }

            .lines thead th {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                padding: 12px 0;
                border-bottom: 1px solid #222;
                text-align: left;
            }

            .lines thead th.num {
                text-align: right;
                width: 22%;
            }

            .lines tbody td {
                padding: 16px 0;
                font-size: 13px;
                vertical-align: top;
                border: none;
            }

            .lines tbody td.num {
                text-align: right;
                white-space: nowrap;
            }

            .lines tbody tr+tr td {
                border-top: 1px solid #e8e8e8;
            }

            .lines tbody tr:last-child td {
                border-bottom: 1px solid #222;
            }

            .totals {
                margin-top: 28px;
                margin-left: auto;
                width: min(100%, 280px);
                font-size: 13px;
            }

            .tot-row {
                display: flex;
                justify-content: space-between;
                gap: 24px;
                padding: 6px 0;
            }

            .tot-row strong {
                font-weight: 600;
            }

            .tot-row.total {
                font-weight: 700;
                margin-top: 10px;
                padding-top: 14px;
                border-top: 1px solid #222;
            }

            .doc-footer {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 48px;
                margin-top: 64px;
                padding-top: 8px;
                font-size: 12px;
            }

            .pay-info-label {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                margin-bottom: 10px;
            }

            .pay-info-body {
                line-height: 1.6;
                max-width: 340px;
            }

            .sign-block {
                text-align: right;
                min-width: 200px;
                position: relative;
            }

            .doc-stamp {
                max-width: 140px;
                max-height: 88px;
                width: auto;
                height: auto;
                object-fit: contain;
                opacity: 0.92;
                display: block;
                margin: 0 0 12px auto;
            }

            .sign-line {
                border-top: 1px solid #222;
                margin-top: 28px;
                margin-bottom: 8px;
                width: 100%;
            }

            .sign-caption {
                font-size: 11px;
                color: #444;
            }

            .doc-footnote {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
                text-align: center;
                font-size: 11px;
                color: #555;
                line-height: 1.5;
            }

            html[dir="rtl"] .doc-header,
            html[dir="rtl"] .doc-meta,
            html[dir="rtl"] .doc-footer {
                flex-direction: row-reverse;
            }

            html[dir="rtl"] .meta-right {
                text-align: left;
            }

            html[dir="rtl"] .meta-right table {
                margin-left: 0;
                margin-right: auto;
            }

            html[dir="rtl"] .meta-right th,
            html[dir="rtl"] .meta-right td {
                text-align: left;
            }

            html[dir="rtl"] .lines thead th.num,
            html[dir="rtl"] .lines tbody td.num {
                text-align: left;
            }

            html[dir="rtl"] .sign-block {
                text-align: left;
            }

            html[dir="rtl"] .doc-brand {
                align-items: flex-start;
                text-align: left;
            }

            html[dir="rtl"] .doc-stamp {
                margin: 0 auto 12px 0;
            }

            html[dir="rtl"] .totals {
                margin-left: 0;
                margin-right: auto;
            }
        </style>
        @if (($settings_data['SITE_RTL'] ?? '') == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body>
        <div class="doc" id="boxes">
            <header class="doc-header">
                <h1 class="doc-title">{{ __('Customer refund') }}</h1>
                <div class="doc-brand">
                    <img src="{{ $company_logo_url }}" alt="{{ $company_name }}" class="doc-logo">
                    @if ($company_name)
                        <div class="doc-company-name">{{ $company_name }}</div>
                    @endif
                    @if ($company_email_display)
                        <div class="doc-company-email">{{ $company_email_display }}</div>
                    @endif
                </div>
            </header>

            <section class="doc-meta">
                <div class="meta-left">
                    <div class="meta-label">{{ __('Issued to') }}</div>
                    <div class="meta-addr">{{ $customerLine }}</div>
                </div>
                <div class="meta-right">
                    <table>
                        <tr>
                            <th>{{ __('Refund no') }}</th>
                            <td>{{ $refundNo }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <td>{{ Utility::dateFormat($settings, $payment->date) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Reference') }}</th>
                            <td>{{ $payment->reference && $payment->reference !== 'NULL' ? $payment->reference : '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Currency') }}</th>
                            <td>{{ $payment->currency ? $payment->currency->name : ($settings['site_currency'] ?? '—') }}</td>
                        </tr>
                        @if ($linkedInvoice)
                            <tr>
                                <th>{{ __('Invoice') }}</th>
                                <td>{{ \Auth::user()->invoiceNumberFormat($linkedInvoice->invoice_id) }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </section>

            <table class="lines">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th class="num">{{ __('Rate') }}</th>
                        <th class="num">{{ __('Qty') }}</th>
                        <th class="num">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lineRows as $row)
                        <tr>
                            <td>{{ $row['desc'] }}</td>
                            <td class="num">{{ Utility::priceFormat($settings, $row['rate']) }}</td>
                            <td class="num">{{ $row['qty'] }}</td>
                            <td class="num">{{ Utility::priceFormat($settings, $row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="tot-row">
                    <span>{{ __('Subtotal') }}</span>
                    <span>{{ Utility::priceFormat($settings, $subtotal) }}</span>
                </div>
                <div class="tot-row">
                    <span>{{ __('Tax') }} (0%)</span>
                    <span>{{ Utility::priceFormat($settings, $taxAmount) }}</span>
                </div>
                <div class="tot-row total">
                    <span>{{ __('Total') }}</span>
                    <span>{{ Utility::priceFormat($settings, $grandTotal) }}</span>
                </div>
            </div>

            <footer class="doc-footer">
                <div>
                    <div class="pay-info-label">{{ __('Payment info') }}</div>
                    <div class="pay-info-body">
                        @if ($payment->bankAccount)
                            {{ $payment->bankAccount->bank_name ?? '' }}<br>
                            {{ $payment->bankAccount->holder_name ?? '' }}<br>
                            @if (! empty($payment->bankAccount->account_number))
                                {{ __('Account') }}:
                                {{ $payment->bankAccount->account_number }}<br>
                            @endif
                            {{ $payment->bankAccount->bank_address ?? '' }}
                            @if (! empty($payment->bankAccount->bank_details))
                                <br>{!! nl2br(e($payment->bankAccount->bank_details)) !!}
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="sign-block">
                    <img src="{{ $company_stamp_url }}?t={{ time() }}" alt="{{ __('Company stamp') }}" class="doc-stamp">
                    <div class="sign-line"></div>
                    <div class="sign-caption">{{ __('Authorized signature') }}</div>
                </div>
            </footer>

            @if (! empty($settings['company_name']) || ! empty($settings['footer_notes']) || $company_email_display)
                <div class="doc-footnote">
                    @if (! empty($settings['company_name']))
                        <strong>{{ $settings['company_name'] }}</strong>
                        @if ($company_email_display)
                            · {{ $company_email_display }}
                        @endif
                        <br>
                    @elseif ($company_email_display)
                        <span>{{ $company_email_display }}</span><br>
                    @endif
                    @if (! empty($settings['company_address']))
                        <span>{{ $settings['company_address'] }}</span>
                        @if (! empty($settings['company_city']))
                            <span>, {{ $settings['company_city'] }}</span>
                        @endif
                        @if (! empty($settings['company_state']))
                            <span>, {{ $settings['company_state'] }}</span>
                        @endif
                        @if (! empty($settings['company_zipcode']))
                            <span> - {{ $settings['company_zipcode'] }}</span>
                        @endif
                        @if (! empty($settings['company_country']))
                            <span>, {{ $settings['company_country'] }}</span>
                        @endif
                        <br>
                    @endif
                    @if (! empty($settings['company_telephone']))
                        <span>{{ $settings['company_telephone'] }}</span><br>
                    @endif
                    @if (! empty($settings['footer_notes']))
                        <span>{!! $settings['footer_notes'] !!}</span>
                    @endif
                </div>
            @endif
        </div>

        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
        <script>
            function closeScript() {
                setTimeout(function() {
                    window.open(window.location, '_self').close();
                }, 1000);
            }

            $(window).on('load', function() {
                var element = document.getElementById('boxes');
                var opt = {
                    margin: 0.5,
                    filename: 'Customer-refund-{{ $payment->id }}.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 1
                    },
                    html2canvas: {
                        scale: 4,
                        dpi: 72,
                        letterRendering: true
                    },
                    jsPDF: {
                        unit: 'in',
                        format: 'A4'
                    }
                };
                html2pdf().set(opt).from(element).save().then(closeScript);
            });
        </script>
    </body>

</html>
