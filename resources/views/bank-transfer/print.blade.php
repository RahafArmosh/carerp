@php
    $settings_data = \App\Models\Utility::settingsById($transfer->created_by);
    $company_logo = $settings['company_logo_dark'] ?? '';
    $company_logos = $settings['company_logo_light'] ?? '';
    $company_stamp = !empty($settings_data['company_stamp']) ? $settings_data['company_stamp'] : '';
    $currencySymbol = $transfer->currency ? $transfer->currency->symbol : Auth::user()->currencySymbol();
    $color = $settings['color'] ?? '#114e7c';
    $font_color = $settings['font_color'] ?? '#114e7c';
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings['SITE_RTL'] == 'on' ? 'rtl' : 'ltr' }}">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <head>
        <meta charset="utf-8">
        <title>{{ __('Bank Transfer') }}</title>
        <link
            href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap"
            rel="stylesheet">

        <style type="text/css">
            :root {
                --theme-color: {{ $font_color }};
                --white: #ffffff;
                --black: #000000;
            }

            body {
                font-family: 'Lato', sans-serif;
            }

            p,
            li,
            ul,
            ol {
                margin: 0;
                padding: 0;
                list-style: none;
                line-height: 1.5;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            tr {
                break-inside: avoid;
                break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            table tr th {
                padding: 0.5rem;
                text-align: left;
                font-weight: 600;
            }

            table tr td {
                padding: 0.5rem;
                text-align: left;
            }

            .transfer-preview-main {
                max-width: 800px;
                width: 100%;
                margin: 0 auto;
                background: #ffff;
                box-shadow: 0 0 10px #ddd;
                position: relative;
            }

            .logo {
                width: 120px;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .text-left {
                text-align: left;
            }

            .transfer-header {
                padding: 20px;
                border-bottom: 2px solid {{ $color }};
            }

            .transfer-body {
                padding: 20px;
            }

            .transfer-footer {
                padding: 15px 20px;
                border-top: 1px solid {{ $font_color }};
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .d-flex {
                display: flex;
                justify-content: space-between;
                gap: 5px;
            }

            .info-title {
                font-size: 16px;
                font-weight: 600;
                color: {{ $font_color }};
                margin-bottom: 10px;
            }

            .info-card {
                width: 48%;
                margin-bottom: 20px;
            }

            .detail-row {
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .detail-label {
                font-weight: 600;
                color: {{ $font_color }};
                margin-bottom: 5px;
            }

            .detail-value {
                font-size: 14px;
            }

            .amount-box {
                background-color: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }

            .amount-label {
                font-size: 14px;
                font-weight: 600;
                color: {{ $font_color }};
            }

            .amount-value {
                font-size: 24px;
                font-weight: bold;
                color: {{ $color }};
                margin-top: 5px;
            }

            .account-box {
                border: 2px solid {{ $color }};
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }

            .account-title {
                font-size: 14px;
                font-weight: 600;
                color: {{ $color }};
                margin-bottom: 10px;
                text-transform: uppercase;
            }

            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }

                .transfer-preview-main {
                    box-shadow: none;
                }

                .no-print {
                    display: none;
                }
            }

            html[dir="rtl"] table tr td,
            html[dir="rtl"] table tr th {
                text-align: right;
            }

            html[dir="rtl"] .text-right {
                text-align: left;
            }

            .stamp {
                position: absolute;
                opacity: 0.6;
                bottom: 20px;
                right: 20px;
            }
        </style>
        @if ($settings['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body>
        <div class="transfer-preview-main" id="boxes">
            <div class="transfer-header">
                <div class="d-flex" style="align-items: center;">
                    <div class="text-left">
                        @if ($settings['cust_darklayout'] && $settings['cust_darklayout'] == 'on')
                            <img class="logo"
                                src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                alt="Company Logo">
                        @else
                            <img class="logo"
                                src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}"
                                alt="Company Logo">
                        @endif
                    </div>
                    <div class="text-center" style="flex: 1;">
                        <h3
                            style="text-transform: uppercase; font-size: 24px; font-weight: bold; color: {{ $color }}; margin:0;">
                            {{ __('Bank Transfer') }}
                        </h3>
                    </div>
                </div>
                <table style="margin-top: 20px;">
                    <tbody>
                        <tr>
                            @if (!empty($settings['company_name']) && !empty($settings['company_email']) && !empty($settings['company_address']))
                                <td>
                                    <p style="font-weight: 600; font-size: 16px; color: {{ $font_color }};">
                                        {{ $settings['company_name'] }}
                                    </p>
                                    <p>{{ $settings['company_address'] }}</p>
                                    <p>{{ $settings['company_city'] }}, {{ $settings['company_state'] }}
                                        {{ $settings['company_zipcode'] }}</p>
                                    <p>{{ $settings['company_country'] }}</p>
                                </td>
                            @endif
                            <td class="text-right">
                                <p><strong>{{ __('Transfer Date') }}:</strong> {{ \Auth::user()->dateFormat($transfer->date) }}</p>
                                @if (!empty($transfer->reference))
                                    <p><strong>{{ __('Reference') }}:</strong> {{ $transfer->reference }}</p>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="transfer-body">
                <div class="d-flex">
                    <div class="info-card">
                        <div class="account-box">
                            <div class="account-title">{{ __('From Account') }}</div>
                            <div class="detail-value">
                                @if (!empty($transfer->fromBankAccount))
                                    <p><strong>{{ __('Bank Name') }}:</strong> {{ $transfer->fromBankAccount->bank_name }}</p>
                                    <p><strong>{{ __('Account Holder') }}:</strong> {{ $transfer->fromBankAccount->holder_name }}</p>
                                    @if (!empty($transfer->fromBankAccount->account_number))
                                        <p><strong>{{ __('Account Number') }}:</strong> {{ $transfer->fromBankAccount->account_number }}</p>
                                    @endif
                                    @if (!empty($transfer->fromBankAccount->bank_address))
                                        <p><strong>{{ __('Address') }}:</strong> {{ $transfer->fromBankAccount->bank_address }}</p>
                                    @endif
                                @else
                                    <p>{{ __('N/A') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="account-box">
                            <div class="account-title">{{ __('To Account') }}</div>
                            <div class="detail-value">
                                @if (!empty($transfer->toBankAccount))
                                    <p><strong>{{ __('Bank Name') }}:</strong> {{ $transfer->toBankAccount->bank_name }}</p>
                                    <p><strong>{{ __('Account Holder') }}:</strong> {{ $transfer->toBankAccount->holder_name }}</p>
                                    @if (!empty($transfer->toBankAccount->account_number))
                                        <p><strong>{{ __('Account Number') }}:</strong> {{ $transfer->toBankAccount->account_number }}</p>
                                    @endif
                                    @if (!empty($transfer->toBankAccount->bank_address))
                                        <p><strong>{{ __('Address') }}:</strong> {{ $transfer->toBankAccount->bank_address }}</p>
                                    @endif
                                @else
                                    <p>{{ __('N/A') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="amount-box">
                    <div class="amount-label">{{ __('Transfer Amount') }}</div>
                    <div class="amount-value">
                        {{ $currencySymbol }}{{ \Auth::user()->priceFormat($transfer->amount) }}
                        @if ($transfer->currency && $transfer->currency->name != $settings['site_currency'])
                            <span style="font-size: 14px; font-weight: normal;">({{ $transfer->currency->name }})</span>
                        @endif
                    </div>
                </div>

                @if (!empty($transfer->description))
                    <div style="margin-top: 20px;">
                        <div class="info-title">{{ __('Description') }}</div>
                        <div class="detail-value" style="padding: 10px; background-color: #f9f9f9; border-radius: 5px;">
                            {{ $transfer->description }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="transfer-footer">
                <div style="text-align: center; color: #666; font-size: 12px;">
                    <p>{{ __('This is a computer-generated document. No signature is required.') }}</p>
                </div>
                @if (!empty($company_stamp))
                    <div class="stamp">
                        <img src="{{ URL::to('/') . '/' . 'documents' . '/' . $company_stamp }}" alt="Company Stamp"
                            style="width: 150px; height: auto;">
                    </div>
                @endif
            </div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background-color: {{ $color }}; color: white; border: none; border-radius: 5px;">
                {{ __('Print') }}
            </button>
        </div>

        <script>
            window.onload = function() {
                // Auto print when page loads
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>

</html>
