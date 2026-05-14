@php
    $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $company_stamp = !empty($settings_data['company_stamp']) ? $settings_data['company_stamp'] : '';

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';

    $emailTemplate = \App\Models\EmailTemplate::emailTemplateData();
    $lang = Auth::user()->lang;

    $userPlan = \App\Models\Plan::getPlan(\Auth::user()->show_dashboard());
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-8';
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
                --theme-color: {
                        {
                        $color
                    }
                }

                ;
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

            table tr th {
                padding: 0.75rem;
                text-align: left;
            }

            table tr td {
                padding: 0.75rem;
                text-align: left;
            }

            .invoice-summary td,
            .invoice-summary th {
                border: 0.3px solid #000000;
                text-align: left;
                padding: 8px;
                border-collapse: collapse
            }

            .invoice-summary tfoot tr td {
                border-top: 3px solid #000000;
            }

            .total-table tr {
                border: 1px solid #000000;
                text-align: left;
                padding: 8px;
            }

            table th small {
                display: block;
                font-size: 12px;
            }

            p {
                font-size: 12px;
            }

            .d-flex {
                display: flex;
                justify-content: space-between;
                padding: 5px 0px;
                gap: 5px
            }

            .info-card {
                width: 50%;
            }

            .info-title {
                padding: 6px;
                background-color: darkgray;
                border-top: 1px solid #000;
            }

            .info-data {
                display: flex;
            }

            .info-data p {
                font-size: 12px;
            }

            .info-data p:first-child {
                width: 30%;
            }

            .invoice-preview-main {
                max-width: 700px;
                width: 100%;
                margin: 0 auto;
                background: #ffff;
                box-shadow: 0 0 10px #ddd;
                position: relative;
            }

            .invoice-logo {
                width: 120px;
            }

            .invoice-header table td {
                padding: 10px 15px;
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

            td img {
                width: 90px;
                height: 90px;
            }

            .view-qrcode {
                max-width: 90px;
                height: 90px;
                width: 100%;
                margin-left: auto;
                background: var(--white);
                padding: 13px;
                border-radius: 10px;
            }

            .view-qrcode img {
                width: 90px;
                height: 90px;
            }

            .invoice-mid {
                padding: 10px 10px;

            }

            .add-border {
                border-collapse: collapse;
                width: 100%;
            }


            tfoot tr:first-of-type {
                border-bottom: 1px solid var(--theme-color);
            }

            tfoot td p {
                font-weight: bold;
                font-size: 12px;
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
                font-size: 10px;
            }

            .total-table td:last-of-type {
                width: 146px;
            }

            .invoice-footer {
                padding: 5px 0px;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .itm-description td {
                padding-top: 0;
            }

            .stamp {
                position: absolute;
                bottom: 50px;
                right: 30px;
                width: 130px;
                height: 30;
                opacity: 0.6;
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
        </style>

        @if ($settings_data['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body class="">
        <div class="invoice-preview-main" id="boxes">
            <div class="invoice-header" style="background: {{ $color }};color:{{ $font_color }}">
                <table>
                    <tbody>
                        <tr>
                            <td>
                                @if ($setting['cust_darklayout'] && $setting['cust_darklayout'] == 'on')
                                    <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                        class="invoice-logo">
                                @else
                                    <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}"
                                        class="invoice-logo">
                                @endif
                                {{-- <img class="invoice-logo" src="{{$img}}" alt=""> --}}
                            </td>
                            <td class="text-right">
                                <div class="view-qrcode">
                                    {!! DNS2D::getBarcodeHTML(route('invoice.link.copy', \Crypt::encrypt($invoice->id)), 'QRCODE', 1.5, 1.5) !!}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h3
                    style="text-transform: uppercase; font-size: 24px; font-weight: bold; text-align:center; padding-top:20px">
                    {{ __('INVOICE') }}
                    {{ Utility::invoiceNumberFormat($settings, $invoice->invoice_id) }}
                </h3>
                <div class="d-flex">
                    <div class="info-card">
                        <h6 class="info-title">{{ __('Customer') }}:</h6>
                        <div class="">
                            <div class="info-data">
                                <p class="info-key">{{ __('Customer Id') }}:</p>
                                <p class="info-value">{{ !empty($customer->id) ? $customer->id : '' }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Customer Name') }}:</p>
                                <p class="info-value">
                                    {{ !empty($customer) && !empty($customer->name) ? $customer->name : '' }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Contact') }}</p>
                                <p class="info-value">
                                    {{ !empty($customer) && !empty($customer->contact) ? $customer->contact : '' }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Email') }}</p>
                                <p class="info-value">
                                    {{ !empty($customer) && !empty($customer->email) ? $customer->email : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <h6 class="info-title">{{ __('Reference') }}:</h6>
                        <div class="">
                            <div class="info-data">
                                <p class="info-key">{{ _('ID') }}:</p>
                                <p class="info-value">{{ Utility::invoiceNumberFormat($settings, $invoice->id) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ _('Issue Date') }}:</p>
                                <p class="info-value">{{ Utility::dateFormat($settings, $invoice->issue_date) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ __('Due Date') }}:</p>
                                <p class="info-value">{{ Utility::dateFormat($settings, $invoice->due_date) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ __('Currency') }}:</p>
                                <p class="info-value">
                                    @if ($invoice->currency_id != null)
                                        {{ App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol }}
                                    @else
                                        {{ $settings['site_currency_symbol'] ?? '' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex">
                <div class="info-card">
                    <h6 class="info-title">{{ __('Bill To') }}:</h6>
                    @if (!empty($customer->name))
                        <p style="border: 1px #DDD solid; padding-left:2px">
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
                </div>
                <div class="info-card">
                    @if ($settings['shipping_display'] == 'on')
                        <h6 class="info-title">{{ __('Ship To') }}:</h6>
                        @if (!empty($customer->shipping_name))
                            <p style="border: 1px #DDD solid; padding-left:2px">
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
                    @endif
                </div>
            </div>
            @if (!empty($invoice->bankAccount))
                <div class="d-flex">
                    <div class="info-card">
                        <h6 class="info-title">{{ __('Bank Details') }}:</h6>
                        <p style="border: 1px #DDD solid; padding-left:2px">
                            {{ !empty($invoice->bankAccount->holder_name) ? $invoice->bankAccount->holder_name : '' }}<br>
                            {{ !empty($invoice->bankAccount->bank_name) ? $invoice->bankAccount->bank_name : '' }}<br>
                            {{ !empty($invoice->bankAccount->account_number) ? $invoice->bankAccount->account_number : '' }}<br>
                            {{ !empty($invoice->bankAccount->contact_number) ? $invoice->bankAccount->contact_number : '' }}<br>
                            {{ !empty($invoice->bankAccount->bank_address) ? $invoice->bankAccount->bank_address : '' }}<br>
                            {{ !empty($invoice->bankAccount->bank_details) ? $invoice->bankAccount->bank_details : '' }}
                        </p>
                    </div>
                    <div class="info-card" style="height: 100%">
                        <h6 class="info-title">{{ __('Notes') }}:</h6>
                        <p style="border: 1px #DDD solid; padding-left:2px">
                        </p>
                    </div>
                </div>
            @endif
            <div class="invoice-body">
                <table class="add-border invoice-summary">
                    <thead>
                        <tr>
                            <th class="info-title" colspan="3" style="width: 190px">{{ __('Item') }}</th>
                            <th class="info-title" colspan="1">{{ __('Quantity') }}</th>
                            <th class="info-title" colspan="1">{{ __('Rate') }}</th>
                            <th class="info-title" colspan="1">{{ __('Discount') }}</th>
                            <th class="info-title" colspan="1">{{ __('Tax') }} (%)</th>
                            <th class="info-title" colspan="1">{{ __('Price') }} <small>after tax &
                                    discount</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($invoice->itemData) && count($invoice->itemData) > 0)
                            @foreach ($invoice->itemData as $key => $item)
                                <tr>
                                    {{-- @if (\App\Models\ProductService::where('id', $item->product_id)->first()->type === 'product')
                        <td>{{$item->brand .'/'. $item->subBrand .'/'. $item->name . '/' .
                            $item->subProductName}}</td>
                        @else --}}
                                    <td colspan="3">{{ !empty($item->name) ? $item->name : '' }}</td>
                                    {{-- @endif --}}

                                    @php
                                        $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                    @endphp
                                    <td>
                                        {{ !empty($item->quantity) ? $item->quantity : '' }}
                                        {{ !empty($unitName) && !empty($unitName->name) ? ' (' . $unitName->name . ')' : '' }}
                                    </td>
                                    @if ($invoice->currency_id != null)
                                        <td>{{ !empty($item->price) ? Utility::priceFormatCurr($settings, $item->price, App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) : '' }}
                                        </td>
                                    @else
                                        <td>{{ !empty($item->price) ? Utility::priceFormat($settings, $item->price) : '' }}
                                        </td>
                                    @endif

                                    @if ($invoice->currency_id != null)
                                        <td>{{ !empty($item->discount) && $item->discount != 0 ? Utility::priceFormatCurr($settings, $item->discount, App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) : '-' }}
                                        </td>
                                    @else
                                        <td>{{ !empty($item->discount) && $item->discount != 0 ? Utility::priceFormat($settings, $item->discount) : '-' }}
                                        </td>
                                    @endif

                                    @php
                                        $itemtax = 0;
                                    @endphp
                                    <td>
                                        @if (
                                            !empty($item->itemTax) &&
                                                !empty($item->product_id) &&
                                                \App\Models\ProductService::where('id', $item->product_id)->first()->type === 'product')
                                            @foreach ($item->itemTax as $taxes)
                                                @php
                                                    $itemtax += $taxes['tax_price'];
                                                @endphp
                                                <p>
                                                    {{ !empty($taxes['name']) ? $taxes['name'] : '' }}
                                                    ({{ !empty($taxes['rate']) ? $taxes['rate'] : '' }})
                                                    {{ !empty($taxes['price']) ? $taxes['price'] : '' }}
                                                </p>
                                            @endforeach
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    @if ($invoice->currency_id != null)
                                        <td>{{ Utility::priceFormatCurr(
                                            $settings,
                                            $item->price - $item->discount + $itemtax,
                                            App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                        ) }}
                                        </td>
                                    @else
                                        <td>{{ Utility::priceFormat($settings, $item->price - $item->discount + $itemtax) }}
                                        </td>
                                    @endif
                                    @if (!empty($itemcustomFields) && count($item->customField) > 0)
                                        @foreach ($itemcustomFields as $field)
                                <tr>
                                    <td>{{ $field->name }} :</td>
                                    <td> {{ !empty($item->customField) ? $item->customField[$field->id] : '-' }}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tr>
                        @endforeach
                    @else
                        @endif
                    </tbody>
                    <tfoot style="border-bottom: 1px solid #000">
                        <tr>
                            <td colspan="3">
                                <p>{{ __('Total') }}</p>
                            </td>
                            <td>
                                <p>{{ $invoice->totalQuantity }}</p>
                            </td>
                            @if ($invoice->currency_id != null)
                                <td>
                                    <p>{{ Utility::priceFormatCurr($settings, $invoice->totalRate, App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) }}
                                    </p>
                                </td>
                            @else
                                <td>
                                    <p>{{ Utility::priceFormat($settings, $invoice->totalRate) }}</p>
                                </td>
                            @endif

                            @if ($invoice->currency_id != null)
                                <td>
                                    <p>{{ Utility::priceFormatCurr($settings, $invoice->totalDiscount, App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) }}
                                    </p>
                                </td>
                            @else
                                <td>
                                    <p>{{ Utility::priceFormat($settings, $invoice->totalDiscount) }}</p>
                                </td>
                            @endif

                            @if ($invoice->currency_id != null)
                                <td>
                                    <p>{{ Utility::priceFormatCurr($settings, $invoice->totalTaxPrice, App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) }}
                                    </p>
                                </td>
                            @else
                                <td>
                                    <p>{{ Utility::priceFormat($settings, $invoice->totalTaxPrice) }}</p>
                                </td>
                            @endif

                            @if ($invoice->currency_id != null && $invoice->exchange_rate != 0)
                                <td>
                                    <p>{{ Utility::priceFormatCurr(
                                        $settings,
                                        $invoice->getSubTotal() / $invoice->exchange_rate,
                                        App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                    ) }}
                                    </p>
                                </td>
                            @elseif($invoice->currency_id != null && $invoice->exchange_rate == 0)
                                <td>
                                    <p>{{ Utility::priceFormatCurr(
                                        $settings,
                                        $invoice->getSubTotal() / App\Models\Currency::where('id', $invoice->currency_id)->first()->exchange_rate,
                                        App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                    ) }}
                                    </p>
                                </td>
                            @else
                                <td>
                                    <p>{{ Utility::priceFormat($settings, $invoice->getSubTotal()) }}</p>
                                </td>
                            @endif
                        </tr>
                        {{-- @if (!empty($item->description))
                    </tr>
                    <tr class="border-0 itm-description">
                        <td colspan="6">{{$item->description}}</td>
                    </tr>
                    @endif --}}
                        @if ($invoice->type == 'rent')
                            <tr>
                                <td colspan="7">{{ __('Number of datyes') }}:</td>

                                <td>{{ $invoice->getDaysDifferenceAttribute() }}</td>

                            </tr>
                        @endif
                        <tr>
                            <td colspan="7">{{ __('Subtotal') }}:</td>
                            @if ($invoice->currency_id != null && $invoice->exchange_rate != 0)
                                <td colspan="1">
                                    {{ Utility::priceFormatCurr(
                                        $settings,
                                        $invoice->getSubTotal() / $invoice->exchange_rate,
                                        App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                    ) }}
                                </td>
                            @elseif($invoice->currency_id != null && $invoice->exchange_rate == 0)
                                <td colspan="1">
                                    {{ Utility::priceFormatCurr(
                                        $settings,
                                        $invoice->getSubTotal() / App\Models\Currency::where('id', $invoice->currency_id)->first()->exchange_rate,
                                        App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                    ) }}
                                </td>
                            @else
                                <td colspan="1">{{ Utility::priceFormat($settings, $invoice->getSubTotal()) }}</td>
                            @endif
                        </tr>
                        @if ($invoice->getTotalDiscount())
                            <tr>
                                <td colspan="7">{{ __('Discount') }}:</td>
                                <td>{{ Utility::priceFormat($settings, $invoice->getTotalDiscount()) }}</td>
                            </tr>
                        @endif
                        @if (!empty($invoice->taxesData))
                            @foreach ($invoice->taxesData as $taxName => $taxPrice)
                                <tr>
                                    <td colspan="7">{{ __('Tax') }} :</td>
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
                            <td colspan="7">{{ __('Total') }}:</td>
                            @if ($invoice->currency_id != null && $invoice->exchange_rate != 0)
                                <td>{{ Utility::priceFormatCurr(
                                    $settings,
                                    $invoice->getSubTotal() / $invoice->exchange_rate -
                                        $invoice->getTotalDiscount() +
                                        $invoice->getTotalTax() / $invoice->exchange_rate,
                                    App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                ) }}
                                </td>
                            @elseif($invoice->currency_id != null && $invoice->exchange_rate == 0)
                                <td>{{ Utility::priceFormatCurr(
                                    $settings,
                                    $invoice->getSubTotal() / App\Models\Currency::where('id', $invoice->currency_id)->first()->exchange_rate -
                                        $invoice->getTotalDiscount() +
                                        $invoice->getTotalTax() / App\Models\Currency::where('id', $invoice->currency_id)->first()->exchange_rate,
                                    App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol,
                                ) }}
                                </td>
                            @else
                                <td>{{ Utility::priceFormat($settings, $invoice->getSubTotal() - $invoice->getTotalDiscount() + $invoice->getTotalTax()) }}
                                </td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="7">{{ __('Credit Note') }}:</td>
                            @if ($invoice->currency_id != null)
                                <td>{{ Utility::priceFormatCurr($settings, $invoice->invoiceTotalCreditNote(), App\Models\Currency::where('id', $invoice->currency_id)->first()->symbol) }}
                                </td>
                            @else
                                <td>{{ Utility::priceFormat($settings, $invoice->invoiceTotalCreditNote()) }}</td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
                <table class="total-table">
                    {{-- <tr>
                    <td>{{__('Paid')}}:</td>
                    @if ($invoice->currency_id != null && $invoice->exchange_rate != 0)
                    <td>{{Utility::priceFormatCurr($settings,($invoice->getTotal()-$invoice->getDue())-($invoice->invoiceTotalCreditNote())
                        / $invoice->exchange_rate ,
                        App\Models\Currency::where('id',$invoice->currency_id)->first()->symbol)}}</td>
                    @elseif($invoice->currency_id != null && $invoice->exchange_rate == 0)
                    <td>{{Utility::priceFormatCurr($settings,($invoice->getTotal()-$invoice->getDue())-($invoice->invoiceTotalCreditNote())
                        / App\Models\Currency::where('id',$invoice->currency_id)->first()->exchange_rate
                        , App\Models\Currency::where('id',$invoice->currency_id)->first()->symbol)}}
                    </td>
                    @else
                    <td>{{Utility::priceFormat($settings,($invoice->getTotal()-$invoice->getDue())-($invoice->invoiceTotalCreditNote()))}}
                    </td>
                    @endif
                </tr> --}}
                    {{-- <tr>
                    <td>{{__('Due Amount')}}:</td>
                    @if ($invoice->currency_id != null && $invoice->exchange_rate != 0)
                    <td>{{Utility::priceFormatCurr($settings,$invoice->getDue() /
                        $invoice->exchange_rate ,
                        App\Models\Currency::where('id',$invoice->currency_id)->first()->symbol)}}</td>
                    @elseif($invoice->currency_id != null && $invoice->exchange_rate == 0)
                    <td>{{Utility::priceFormatCurr($settings,$invoice->getDue() /
                        App\Models\Currency::where('id',$invoice->currency_id)->first()->exchange_rate ,
                        App\Models\Currency::where('id',$invoice->currency_id)->first()->symbol)}}</td>
                    @else
                    <td>{{Utility::priceFormat($settings,$invoice->getDue())}}</td>
                    @endif
                </tr> --}}
                </table>
                <div class="invoice-footer">
                    <div class="d-flex">
                        <div class="info-card" style="text-align: center; border: 1px darkgray solid; width: 100%">
                            <div class="info-title">
                                {{ __('Total') }}
                            </div>
                            {{ Utility::formatPriceToWords($invoice->getSubTotal() - $invoice->getTotalDiscount() + $invoice->getTotalTax()) }}
                        </div>
                    </div>
                    <p style="margin-top: 90px; border-top: 1px solid #c4c4c4; text-align: center;">
                        @if ($settings['company_name'])
                            <span
                                style="text-align: center; display: block; font-weight:bold">{{ $settings['company_name'] }}</span>
                        @endif
                        @if ($settings['mail_from_address'])
                            <span>{{ $settings['company_email'] }}</span>
                        @endif,
                        @if ($settings['company_address'])
                            <span>{{ $settings['company_address'] }}</span>
                        @endif,
                        @if ($settings['company_city'])
                            <span>{{ $settings['company_city'] }},</span>
                        @endif,
                        @if ($settings['company_state'])
                            <span>{{ $settings['company_state'] }}</span>
                        @endif,
                        @if ($settings['company_zipcode'])
                            <span> - {{ $settings['company_zipcode'] }}</span>
                        @endif,
                        @if ($settings['company_country'])
                            <span>{{ $settings['company_country'] }}</span>
                        @endif,
                        @if ($settings['company_telephone'])
                            <span>{{ $settings['company_telephone'] }}</span>
                        @endif,
                        @if (!empty($settings['registration_number']))
                            <span>{{ __('Registration Number') }} :
                                {{ $settings['registration_number'] }}</span>
                        @endif,
                        @if ($settings['vat_gst_number_switch'] == 'on')
                            @if (!empty($settings['tax_type']) && !empty($settings['vat_number']))
                                <span>{{ $settings['tax_type'] . ' ' . __('Number') }} :
                                    {{ $settings['vat_number'] }}</span>
                            @endif
                        @endif
                    </p>
                    <img src="{{ (!empty($company_stamp) ? URL::to('/') . '/' . 'documents' . '/' . $company_stamp : URL::to('/') . '/' . 'storage/uploads/logo' . '/' . 'stamp-preview.png') . '?timestamp=' . time() }}"
                        class="stamp" width="60" alt="Company Signature">
                </div>
            </div>
        </div>
        @if (!isset($preview))
            @include('invoice.script');
        @endif

    </body>

</html>
