@php
    $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
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
                position: relative;
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
                break-inside: auto;

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

            .invoice-preview-main {
                max-width: 700px;
                width: 100%;
                margin: 0 auto;
                background: #ffff;
                box-shadow: 0 0 10px #ddd;
                position: relative;
                display: flex;
                flex-direction: column;
                min-height: 100%;
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

            .view-qrcode {
                max-width: 114px;
                height: 114px;
                margin-left: auto;
                margin-top: 15px;
                background: var(--white);
            }

            .view-qrcode img {
                width: 100%;
                height: 100%;
            }

            .invoice-body {
                flex-grow: 1;
                border-top: 1px solid {{ $color }};
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

            .invoice-summary td,
            .invoice-summary th {
                font-size: 13px;
                border: 1px solid;
            }

            .total-table td:last-of-type {
                width: 146px;
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

            .invoice-summary p {
                margin-bottom: 0;
            }

            .stamp {
                position: absolute;
                opacity: 0.6;
            }

            .invoice-summary . {
                border: 0px;
            }
        </style>
        @if ($settings_data['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body>
        <div class="invoice-preview-main" id="boxes">
            <div class="invoice-header" style="">
                <div class="d-flex" style="align-items: center;">
                    <div class="text-left">
                        <div class="view-qrcode">
                            {!! DNS2D::getBarcodeHTML(route('invoice.link.copy', \Crypt::encrypt($invoice->invoice_id)), 'QRCODE', 2, 2) !!}
                        </div>
                    </div>
                    <div class="text-center">
                        <h3
                            style="text-transform: uppercase; font-size: 20px; font-weight: bold; color: {{ $font_color }}; margin:0;">
                            INVOICE
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
                                            Registration Number : {{ $settings['registration_number'] }}
                                        @endif
                                        <br>
                                        @if ($settings['vat_gst_number_switch'] == 'on')
                                            @if (!empty($settings['tax_type']) && !empty($settings['vat_number']))
                                                {{ $settings['tax_type'] }} Number : {{ $settings['vat_number'] }}
                                                <br>
                                            @endif
                                        @endif
                                    </p>
                                </td>
                            @endif
                            <td>
                                <table class="no-space" style="width: 45%;margin-left: auto;">
                                    <tbody>
                                        <tr>
                                            <td>Number:</td>
                                            <td class="text-right">
                                                {{ Utility::invoiceNumberFormat($settings, $invoice->invoice_id) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Issue Date:</td>
                                            <td class="text-right">
                                                {{ Utility::dateFormat($settings, $invoice->issue_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Currency:</td>
                                            <td class="text-right">
                                                {{ $invoice->currency_id ?? $settings['site_currency_symbol'] }}
                                                {{ $invoice->currency_id && ($currency = App\Models\Currency::find($invoice->currency_id)) ? $currency->name : $settings['site_currency_symbol'] ?? '' }}
                                            </td>
                                        </tr>
                                        @if (!empty($customFields) && count($invoice->customField) > 0)
                                            @foreach ($customFields as $field)
                                                <tr>
                                                    <td>{{ $field->name }} :</td>
                                                    <td> {{ !empty($invoice->customField) ? $invoice->customField[$field->id] : '-' }}
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
            <div class="invoice-body">
                <table class="vertical-align-top">
                    <tbody>
                        <tr>
                            <td>
                                <strong style="margin-bottom: 10px; display:block;">Bill To:</strong>
                                @if (!empty($customer->name))
                                    <p>
                                        {{ !empty($customer->name) ? $customer->name : '' }}<br>
                                        {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                        {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }}<br>
                                        {{ !empty($customer->billing_state) ? $customer->billing_state . ', ' : '' }},
                                        {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                        {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                        {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                    </p>
                                @else
                                    -
                                @endif
                            </td>
                            @if ($settings_data['shipping_display'] == 'on')
                                <td class="text-right">
                                    <strong style="margin-bottom: 10px; display:block;">Ship To:</strong>
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
                <table class="invoice-summary add-border" style="border-bottom:1px solid {{ $font_color }};">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
                        <tr>
                            <th colspan="4" style="width: 400px">Item</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Discount</th>
                            <th>Price <small>after discount</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($invoice->itemData) && count($invoice->itemData) > 0)
                            @foreach ($invoice->itemData as $key => $item)
                                <tr style="border-bottom:1px solid {{ $font_color }};">
                                    <td colspan="4">{{ $item->name }}</td>
                                    @php
                                        $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                    @endphp
                                    <td>{{ $item->quantity }}
                                        {{ ' (' . (!empty($unitName) ? $unitName->name : '') . ')' }}
                                    </td>
                                    <td>{{ Utility::priceNumberFormat($settings, $item->price) }}</td>
                                    <td>{{ $item->discount != 0 ? Utility::priceNumberFormat($settings, $item->discount) : '-' }}
                                    </td>
                                    <td>{{ Utility::priceNumberFormat($settings, $item->price * $item->quantity - $item->discount) }}
                                    </td>
                                </tr>
                                @if (!empty($item->description))
                                    <tr class="itm-description">
                                        <td colspan="8">{{ $item->description }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid {{ $font_color }};">
                            <td colspan="4">Total</td>
                            <td>{{ $invoice->totalQuantity }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $invoice->totalRate) }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $invoice->totalDiscount) }}</td>
                            <td>{{ Utility::priceNumberFormat($settings, $invoice->getSubTotal()) }}</td>
                        </tr>
                        @if ($invoice->type == 'rent')
                            <tr>
                                <td colspan="6"></td>
                                <td colspan="7">{{ __('Number of days') }}:</td>
                                <td>{{ $invoice->getDaysDifferenceAttribute() }}</td>

                            </tr>
                        @endif
                        <tr>
                            <td colspan="6"></td>
                            <td>{{ __('Subtotal') }}:</td>
                            <td>{{ Utility::priceNumberFormat($settings, $invoice->getSubTotal()) }}</td>
                        </tr>
                        @if ($invoice->getTotalDiscount())
                            <tr>
                                <td colspan="6"></td>
                                <td>Discount:</td>
                                <td>{{ Utility::priceNumberFormat($settings, $invoice->getTotalDiscount()) }}
                                </td>
                            </tr>
                        @endif
                        @if (!empty($invoice->taxesData))
                            @foreach ($invoice->taxesData as $taxName => $taxPrice)
                                <tr>
                                    <td colspan="6"></td>
                                    <td>{{ __('Tax') }} :</td>
                                    @if ($invoice->currency_id != null)
                                        <td>{{ Utility::priceFormatCurr(
                                            $settings,
                                            $taxPrice,
                                            App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                        ) }}
                                        </td>
                                    @else
                                        <td>{{ Utility::priceFormat($settings, $taxPrice) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td colspan="6"></td>
                            <td>{{ __('Total') }}:</td>
                            <td>{{ Utility::priceNumberFormat($settings, $invoice->getSubTotal() - $invoice->getTotalDiscount()) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex" style="margin-top: 5px;">
                <div class="info-card" style="text-align: center; border: 1px {{ $font_color }} solid; width: 100%">
                    <div class="info-title"
                        style="background: {{ $color }}; border-bottom: 1px {{ $font_color }} solid;">
                        Total
                    </div>
                    {{ Utility::formatPriceToWords($invoice->getSubTotal() - $invoice->getTotalDiscount()) }}
                </div>
            </div>
            <div class="invoice-body">
                <table style="width:100%; margin-top: 15px;" class="invoice-summary">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
                        <tr>
                            <th style="vertical-align: top; width: 50%;">Bank Details</th>
                            <th style="vertical-align: top; width: 50%;">Terms And Conditions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <p>
                                    {{ !empty($invoice->bankAccount->holder_name) ? $invoice->bankAccount->holder_name : '' }}<br>
                                    {{ !empty($invoice->bankAccount->bank_name) ? $invoice->bankAccount->bank_name : '' }}<br>
                                    {{ !empty($invoice->bankAccount->account_number) ? $invoice->bankAccount->account_number : '' }}<br>
                                    {{ !empty($invoice->bankAccount->contact_number) ? $invoice->bankAccount->contact_number : '' }}<br>
                                    {{ !empty($invoice->bankAccount->bank_address) ? $invoice->bankAccount->bank_address : '' }}<br>
                                    {{ !empty($invoice->bankAccount->bank_details) ? $invoice->bankAccount->bank_details : '' }}
                                </p>
                            </td>
                            <td>
                                <p>
                                    {{ !empty($invoice->terms) ? $invoice->terms : '' }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="invoice-footer">
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
                        <span>, Registration Number : {{ $settings['registration_number'] }}</span>
                    @endif
                    @if ($settings['vat_gst_number_switch'] == 'on')
                        @if (!empty($settings['tax_type']) && !empty($settings['vat_number']))
                            <span>, {{ $settings['tax_type'] }} Number : {{ $settings['vat_number'] }}</span>
                        @endif
                    @endif
                </p>
            </div>
        </div>
        @if (!isset($preview))
            @include('invoice.script');
        @endif
    </body>

</html>
