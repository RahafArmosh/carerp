@php
    $settings_data = \App\Models\Utility::settingsById($proposal->created_by);
    $company_logo = $settings_data['company_logo_dark'] ?? '';
    $company_logos = $settings_data['company_logo_light'] ?? '';
    $company_stamp = !empty($settings_data['company_stamp']) ? $settings_data['company_stamp'] : '';
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings_data['SITE_RTL'] == 'on' ? 'rtl' : '' }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
                padding: 0.3rem;
                text-align: left;
            }

            table tr td {
                padding: 0.5rem;
                text-align: left;
            }

            table th small {
                display: block;
                font-size: 12px;
            }

            .proposal-preview-main {
                max-width: 700px;
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

            .no-space tr td {
                padding: 0;
                white-space: nowrap;
            }

            .vertical-align-top td {
                vertical-align: top;
            }

            .view-qrcode img {
                width: 100%;
                height: 100%;
            }

            .proposal-body {
                border-top: 1px solid {{ $font_color }};
            }

            .d-flex {
                display: flex;
                justify-content: space-between;
                gap: 5px
            }

            .info-title {
                font-size: 16px;
                font-weight: 600;
                color: {{ $font_color }};
            }

            .info-card {
                width: 50%;
                display: flex;
                flex-direction: column;
                margin-bottom: 2px;
            }

            .info-card p {
                border: 1px {{ $font_color }} solid;
                flex: 1;
            }

            table.add-border tr {
                border: 1px solid {{ $font_color }};
            }



            tfoot tr:first-of-type {
                border-bottom: 1px solid var(--theme-color);
            }

            .total-table tr:first-of-type td {
                padding-top: 0;
            }

            .total-table tr:first-of-type {
                border-top: 0;
            }

            .sub-total {
                padding-right: 0;
                padding-left: 0;
            }

            .border-0 {
                border: none !important;
            }

            .proposal-summary td,
            .proposal-summary th {
                font-size: 13px;
                font-weight: 600;
                border: 1px solid #000000;
            }

            .total-table td:last-of-type {
                width: 146px;
            }

            .proposal-footer {
                padding: 15px 20px;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .itm-description td {
                padding-top: 0;
            }

            html[dir="rtl"] table tr td,
            html[dir="rtl"] table tr th {
                text-align: right;
            }

            html[dir="rtl"] .text-right {
                text-align: left;
            }

            html[dir="rtl"] .view-qrcode {
                margin-left: 0;
                margin-right: auto;
            }

            p:not(:last-of-type) {
                margin-bottom: 15px;
            }

            .proposal-summary p {
                margin-bottom: 0;
            }

            .stamp {
                position: absolute;
                opacity: 0.6;
            }
        </style>
        @if ($settings_data['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body>
        <div class="proposal-preview-main" id="boxes">
            <div class="proposal-header" style="">
                <div class="d-flex" style="align-items: center;">
                    <div class="text-left">
                        <div class="view-qrcode">
                            {!! DNS2D::getBarcodeHTML(route('proposal.link.copy', \Crypt::encrypt($proposal->proposal_id)), 'QRCODE', 2, 2) !!}
                        </div>
                    </div>
                    <div class="text-center">
                        <h3
                            style="text-transform: uppercase; font-size: 20px; font-weight: bold; color: {{ $font_color }}; margin:0;">
                            {{ __('PROPOSAL') }}
                        </h3>
                    </div>
                    <div class="text-right">
                        @if ($settings_data['cust_darklayout'] && $settings_data['cust_darklayout'] == 'on')
                            <img class="logo"
                                src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                alt="Company Logo">
                        @else
                            <img class="logo"
                                src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}"
                                alt="Company Logo">
                        @endif
                    </div>
                </div>
                <table class="vertical-align-top">
                    <tbody>
                        <tr>
                            @if (!empty($settings['company_name']) && !empty($settings['mail_from_address']) && !empty($settings['company_address']))
                                <td>
                                    <p>
                                        @if ($settings['company_name'])
                                            {{ $settings['company_name'] }}
                                        @endif
                                        <br>
                                        @if ($settings['mail_from_address'])
                                            {{ $settings['mail_from_address'] }}
                                        @endif
                                        <br>
                                        @if ($settings['company_address'])
                                            {{ $settings['company_address'] }}
                                        @endif
                                        @if ($settings['company_city'])
                                            <br> {{ $settings['company_city'] }},
                                        @endif
                                        @if ($settings['company_state'])
                                            {{ $settings['company_state'] }}
                                        @endif
                                        @if ($settings['company_zipcode'])
                                            - {{ $settings['company_zipcode'] }}
                                        @endif
                                        @if ($settings['company_country'])
                                            <br>{{ $settings['company_country'] }}
                                        @endif
                                        @if ($settings['company_telephone'])
                                            {{ $settings['company_telephone'] }}
                                        @endif
                                        <br>
                                        @if (!empty($settings['registration_number']))
                                            {{ __('Registration Number') }} : {{ $settings['registration_number'] }}
                                        @endif
                                        <br>
                                        @if ($settings['vat_gst_number_switch'] == 'on')
                                            @if (!empty($settings['tax_type']) && !empty($settings['vat_number']))
                                                {{ $settings['tax_type'] . ' ' . __('Number') }} :
                                                {{ $settings['vat_number'] }} <br>
                                            @endif
                                        @endif
                                    </p>

                                </td>
                            @endif
                            <td>
                                <table class="no-space" style="width: 45%;margin-left: auto;">
                                    <tbody>
                                        <tr>
                                            <td>{{ __('Number') }}:</td>
                                            <td class="text-right">
                                                {{ Utility::proposalNumberFormat($settings, $proposal->proposal_id) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Issue Date') }}:</td>
                                            <td class="text-right">
                                                {{ Utility::dateFormat($settings, $proposal->issue_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Currency') }}:</td>
                                            <td class="text-right">
                                                {{ $proposal->currency_id && ($currency = App\Models\Currency::find($proposal->currency_id)) ? $currency->name : $settings['site_currency_symbol'] ?? '' }}
                                            </td>
                                        </tr>

                                        @if (!empty($customFields) && count($proposal->customField) > 0)
                                            @foreach ($customFields as $field)
                                                <tr>
                                                    <td>{{ $field->name }} :</td>
                                                    <td> {{ !empty($proposal->customField) ? $proposal->customField[$field->id] : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="proposal-body">
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="margin-bottom: 10px; display:block;">{{ __('Bill To') }}:</strong>
                                <p>
                                    {{ !empty($customer->name) ? $customer->name : '' }}<br>
                                    {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                    {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }}<br>
                                    {{ !empty($customer->billing_state) ? $customer->billing_state . ', ' : '' }},
                                    {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                    {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                    {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                </p>
                            </td>
                            @if ($settings_data['shipping_display'] == 'on')
                                <td class="text-right">
                                    <strong style="margin-bottom: 10px; display:block;">{{ __('Ship To') }}:</strong>
                                    @if (!empty($customer->shipping_name))
                                        <p>
                                            {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}<br>
                                            {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}<br>
                                            {{ !empty($customer->shipping_city) ? $customer->shipping_city : '' . ', ' }}<br>
                                            {{ !empty($customer->shipping_state) ? $customer->shipping_state : '' . ', ' }},
                                            {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}<br>
                                            {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}<br>
                                            {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}<br>
                                        </p>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>

                <table class="proposal-summary add-border" style="border-bottom:1px solid {{ $font_color }};">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
                        <tr>
                            <th colspan="4" style="width: 400px">{{ __('Item') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Rate') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Tax') }}</th>
                            <th>{{ __('Price') }} <small>{{ __('after tax & discount') }}</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($proposal->itemData) && count($proposal->itemData) > 0)
                            @foreach ($proposal->itemData as $key => $item)
                                <tr style="border-bottom:1px solid {{ $font_color }};">
                                    <td colspan="4">{{ $item->name }}</td>
                                    @php
                                        $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                    @endphp
                                    <td>
                                        {{ $item->quantity }}
                                        {{ !empty($unitName) ? ' (' . $unitName->name . ')' : '' }}
                                    </td>
                                    <td>{{ Utility::priceNumberFormat($settings, $item->price) }}</td>
                                    <td>{{ $item->discount != 0 ? Utility::priceNumberFormat($settings, $item->discount) : '-' }}
                                    </td>
                                    <td>
                                        @if (!empty($item->itemTax))
                                            @foreach ($item->itemTax as $tax)
                                                <div>{{ $tax['name'] }} ({{ $tax['rate'] }}): {{ $tax['price'] }}
                                                </div>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ Utility::priceNumberFormat($settings, $item->price * $item->quantity - $item->discount) }}
                                    </td>
                                </tr>
                                @if (!empty($item->description))
                                    <tr class="itm-description">
                                        <td colspan="9">{{ $item->description }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #000000">
                            <td colspan="5">{{ __('Total') }}</td>
                            <td>{{ $proposal->totalQuantity }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $proposal->totalRate) }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $proposal->totalDiscount) }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $proposal->getSubTotal()) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td>{{ __('Subtotal') }}:</td>
                            <td>{{ Utility::priceNumberFormat($settings, $proposal->getSubTotal()) }}</td>
                        </tr>
                        @if ($proposal->getTotalDiscount())
                            <tr>
                                <td colspan="7"></td>
                                <td>{{ __('Discount') }}:</td>
                                <td>{{ Utility::priceNumberFormat($settings, $proposal->getTotalDiscount()) }}
                                </td>
                            </tr>
                        @endif
                        @if (!empty($proposal->taxesData))
                            @foreach ($proposal->taxesData as $taxName => $taxPrice)
                                <tr>
                                    <td colspan="7"></td>
                                    <td>{{ $taxName }}</td>
                                    <td>{{ Utility::priceNumberFormat($settings, $taxPrice) }}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td colspan="7"></td>
                            <td>{{ __('Total') }} :</td>
                            <td>{{ Utility::priceNumberFormat($settings, $proposal->getSubTotal() - $proposal->getTotalDiscount() + $proposal->getTotalTax()) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <table style="width:100%; margin-top: 15px;" class="proposal-summary">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
                        <tr>
                            <th style="vertical-align: top; width: 50%;">{{ __('Bank Details') }}</th>
                            <th style="vertical-align: top; width: 50%;">{{ __('Terms And Conditions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <p>
                                    {{ !empty($proposal->bankAccount->holder_name) ? $proposal->bankAccount->holder_name : '' }}<br>
                                    {{ !empty($proposal->bankAccount->bank_name) ? $proposal->bankAccount->bank_name : '' }}<br>
                                    {{ !empty($proposal->bankAccount->account_number) ? $proposal->bankAccount->account_number : '' }}<br>
                                    {{ !empty($proposal->bankAccount->contact_number) ? $proposal->bankAccount->contact_number : '' }}<br>
                                    {{ !empty($proposal->bankAccount->bank_address) ? $proposal->bankAccount->bank_address : '' }}<br>
                                    {{ !empty($proposal->bankAccount->bank_details) ? $proposal->bankAccount->bank_details : '' }}
                                </p>
                            </td>
                            <td>
                                <p>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="proposal-footer">
                <table class="proposal-summary add-border"
                    style="width:100%; margin-top: 5px; border:1px {{ $font_color }} solid;">
                    <thead>
                        <tr>
                            <th class="info-title"
                                style="background: {{ $color }}; border-bottom: 1px {{ $color }} solid; text-align:center;">
                                {{ __('Total in Words') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">
                                {{ Utility::formatPriceToWords($proposal->getSubTotal() - $proposal->getTotalDiscount() + $proposal->getTotalTax()) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                {{-- <b>{{ $settings['footer_title'] }}</b>
                {!! $settings['footer_notes'] !!} --}}
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-end; margin: 40px 0 40px 0;">
                    <!-- Company Signature -->
                    <div style="text-align: left; width: 45%;">
                        <label style="font-weight: bold;">{{ __('Company Signature') }}</label>
                        <div style="margin-top: 10px;">
                            <img src="{{ (!empty($company_stamp) ? URL::to('/') . '/' . 'documents' . '/' . $company_stamp : URL::to('/') . '/' . 'storage/uploads/logo' . '/' . 'stamp-preview.png') . '?timestamp=' . time() }}"
                                class="stamp" width="60" alt="Company Signature">
                        </div>
                        <div style="margin-top: 30px; border-top: 1px solid #c4c4c4; width: 80%;"></div>
                    </div>
                    <!-- Customer Signature -->
                    <div style="text-align: right; width: 45%;">
                        <label style="font-weight: bold;">{{ __('Customer Signature') }}</label>
                        <div style="margin-top: 40px; border-top: 1px solid #c4c4c4; width: 80%; float: right;"></div>
                    </div>
                </div>
                <p style="border-top: 1px solid #c4c4c4; text-align: center;">
                    @if ($settings['company_name'])
                        <span
                            style="text-align: center; display: block; font-weight:bold">{{ $settings['company_name'] }}</span>
                    @endif
                    @if ($settings['mail_from_address'])
                        <span>{{ $settings['company_email'] }}</span>
                    @endif
                    @if ($settings['company_address'])
                        <span>, {{ $settings['company_address'] }}</span>
                    @endif
                    @if ($settings['company_city'])
                        <span>, {{ $settings['company_city'] }}</span>
                    @endif
                    @if ($settings['company_state'])
                        <span>, {{ $settings['company_state'] }}</span>
                    @endif
                    @if ($settings['company_zipcode'])
                        <span> - {{ $settings['company_zipcode'] }}</span>
                    @endif
                    @if ($settings['company_country'])
                        <span>, {{ $settings['company_country'] }}</span>
                    @endif
                    @if ($settings['company_telephone'])
                        <span>, {{ $settings['company_telephone'] }}</span>
                    @endif
                    @if (!empty($settings['registration_number']))
                        <span>, {{ __('Registration Number') }} :
                            {{ $settings['registration_number'] }}</span>
                    @endif
                    @if ($settings['vat_gst_number_switch'] == 'on')
                        @if (!empty($settings['tax_type']) && !empty($settings['vat_number']))
                            <span>, {{ $settings['tax_type'] . ' ' . __('Number') }} :
                                {{ $settings['vat_number'] }}</span>
                        @endif
                    @endif
                </p>

            </div>
        </div>
        @if (!isset($preview))
            @include('proposal.script');
        @endif
    </body>

</html>
