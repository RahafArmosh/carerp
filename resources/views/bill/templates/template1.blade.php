@php
    $settings_data = \App\Models\Utility::settingsById($bill->created_by);
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo/');

    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_small_logo = $setting['company_small_logo'] ?? '';
    $company_stamp = !empty($settings_data['company_stamp']) ? $settings_data['company_stamp'] : '';
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

            .bill-summary td,
            .bill-summary th {
                border: 0.3px solid #000000;
                text-align: left;
                padding: 8px;
                border-collapse: collapse
            }

            .bill-summary tfoot tr td {
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
                padding: 10px 10px;
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

            .bill-preview-main {
                max-width: 700px;
                width: 100%;
                margin: 0 auto;
                background: #ffff;
                box-shadow: 0 0 10px #ddd;
                position: relative;
            }

            .bill-logo {
                width: 150px;
                height: 150px;
            }

            .bill-header table td {
                padding: 10px 10px;
            }

            .text-right {
                qrcode text-align: right;
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
                height: 90px;
                background: var(--white);
            }

            .view-qrcode img {
                width: 90px;
                height: 90px;
            }

            .bill-mid {
                padding: 10px 10px;

            }

            .bill-body {
                padding: 10px 10px;
            }

            .add-border {
                border-collapse: collapse;
                margin-top: 10px;
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

            .bill-summary td,
            .bill-summary th {
                font-size: 10px;
            }

            .total-table td:last-of-type {
                width: 146px;
            }

            .bill-footer {
                padding: 5px 10px;
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
        <div class="bill-preview-main" id="boxes">
            <div class="bill-header" style="background: {{ $color }};color:{{ $font_color }}">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        @if ($setting['cust_darklayout'] && $setting['cust_darklayout'] == 'on')
                            <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                class="bill-logo">
                        @else
                            <img src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}"
                                class="bill-logo">
                        @endif
                    </div>
                    <div class="view-qrcode">
                        {!! DNS2D::getBarcodeHTML(route('bill.link.copy', \Crypt::encrypt($bill->id)), 'QRCODE', 2, 2) !!}
                    </div>
                </div>
                <h3
                    style="text-transform: uppercase; font-size: 24px; font-weight: bold; text-align:center; padding-top:20px">
                    {{ __('bill') }}
                    {{ Utility::billNumberFormat($settings, $bill->id) }}
                </h3>
                <div class="d-flex">
                    <div class="info-card">
                        <h6 class="info-title">{{ __('Vendor ') }}:</h6>
                        <div class="">
                            <div class="info-data">
                                <p class="info-key">{{ __('Vendor Id') }}:</p>
                                <p class="info-value">{{ !empty($vendor) && !empty($vendor->id) ? $vendor->id : '' }}
                                </p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Vendor Name') }}:</p>
                                <p class="info-value">
                                    {{ !empty($vendor) && !empty($vendor->name) ? $vendor->name : '' }}
                                </p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Contact') }}</p>
                                <p class="info-value">
                                    {{ !empty($vendor) && !empty($vendor->contact) ? $vendor->contact : '' }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key"> {{ __('Email') }}</p>
                                <p class="info-value">
                                    {{ !empty($vendor) && !empty($vendor->email) ? $vendor->email : '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="info-card">
                        <h6 class="info-title">{{ __('Reference') }}:</h6>
                        <div class="">
                            <div class="info-data">
                                <p class="info-key">{{ _('ID') }}:</p>
                                <p class="info-value">{{ Utility::billNumberFormat($settings, $bill->id) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ _('Bill Date') }}:</p>
                                <p class="info-value">{{ Utility::dateFormat($settings, $bill->bill_date) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ __('Due Date') }}:</p>
                                <p class="info-value">{{ Utility::dateFormat($settings, $bill->due_date) }}</p>
                            </div>
                            <div class="info-data">
                                <p class="info-key">{{ __('Currency') }}:</p>
                                <p class="info-value">
                                    @if ($bill->currency_id != null)
                                        {{ App\Models\Currency::where('id', $bill->currency_id)->first()->symbol }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex">
                <div class="info-card">
                    <h6 class="info-title">{{ __('Bill To') }}:</h6>
                    @if (!empty($vendor->name))
                        <p style="border: 1px #DDD solid; padding-left:2px">
                            {{ !empty($vendor->name) ? $vendor->name : '' }}<br>
                            {{ !empty($vendor->billing_address) ? $vendor->billing_address : '' }}<br>
                            {{ !empty($vendor->billing_city) ? $vendor->billing_city : '' . ', ' }}<br>
                            {{ !empty($vendor->billing_state) ? $vendor->billing_state . ', ' : '' }},
                            {{ !empty($vendor->billing_zip) ? $vendor->billing_zip : '' }}<br>
                            {{ !empty($vendor->billing_country) ? $vendor->billing_country : '' }}<br>
                            {{ !empty($vendor->billing_phone) ? $vendor->billing_phone : '' }}<br>
                        </p>
                    @else
                        -
                    @endif
                </div>
                <div class="info-card">
                    @if ($settings['shipping_display'] == 'on')
                        <h6 class="info-title">{{ __('Ship To') }}:</h6>
                        @if (!empty($vendor->shipping_name))
                            <p style="border: 1px #DDD solid; padding-left:2px">
                                {{ !empty($vendor->shipping_name) ? $vendor->shipping_name : '' }}<br>
                                {{ !empty($vendor->shipping_address) ? $vendor->shipping_address : '' }}<br>
                                {{ !empty($vendor->shipping_city) ? $vendor->shipping_city : '' . ', ' }}<br>
                                {{ !empty($vendor->shipping_state) ? $vendor->shipping_state : '' . ', ' }},
                                {{ !empty($vendor->shipping_zip) ? $vendor->shipping_zip : '' }}<br>
                                {{ !empty($vendor->shipping_country) ? $vendor->shipping_country : '' }}<br>
                                {{ !empty($vendor->shipping_phone) ? $vendor->shipping_phone : '' }}<br>
                            </p>
                        @else
                            -
                        @endif
                    @endif
                </div>
            </div>
            <div class="bill-body">
                <table class="add-border bill-summary">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
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
                        @if (isset($bill->itemData) && count($bill->itemData) > 0)
                            @foreach ($bill->itemData as $key => $item)
                                <tr>
                                    <td colspan="3">{{ $item->name }}</td>
                                    @php
                                        // $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                    @endphp
                                    <td>{{ $item->quantity . ' (' . !empty($item->unit) ? $item->unit : '' . ')' }}
                                    </td>
                                    <td>{{ Utility::priceFormat($settings, $item->price) }}
                                    </td>

                                    <td>{{ $item->discount != 0 ? Utility::priceFormat($settings, $item->discount) : '-' }}
                                    </td>

                                    @php
                                        $itemtax = 0;
                                    @endphp
                                    <td>
                                        @if (!empty($item->itemTax))
                                            @foreach ($item->itemTax as $taxes)
                                                @php
                                                    $itemtax += $taxes['tax_price'];
                                                @endphp
                                                <p>{{ $taxes['name'] }} ({{ $taxes['rate'] }}) {{ $taxes['price'] }}
                                                </p>
                                            @endforeach
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>


                                    <td>{{ Utility::priceFormat($settings, $item->price - $item->discount + $itemtax) }}
                                    </td>

                                    @if (!empty($item->description))
                                <tr class="itm-description border-0">
                                    <td colspan="6">{{ $item->description }}</td>
                                </tr>
                            @endif
                            </tr>
                        @endforeach
                    @else
                        @endif

                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">{{ __('Total') }}</td>
                            <td>{{ $bill->totalQuantity }}</td>

                            <td>{{ Utility::priceFormat($settings, $bill->totalRate) }}</td>



                            <td>{{ Utility::priceFormat($settings, $bill->totalDiscount) }}</td>

                            {{-- <td>{{$bill->totalQuantity}}</td> --}}


                            <td>{{ Utility::priceFormat($settings, $bill->totalTaxPrice) }}</td>


                            @if ($bill->currency_id != null && $bill->exchange_rate != 0)
                                <td>{{ Utility::priceFormat($settings, $bill->getSubTotal() / $bill->exchange_rate) }}
                                </td>
                            @elseif($bill->currency_id != null && $bill->exchange_rate == 0)
                                <td>{{ Utility::priceFormat(
                                    $settings,
                                    $bill->getSubTotal() / App\Models\Currency::where('id', $bill->currency_id)->first()->exchange_rate,
                                ) }}
                                </td>
                            @else
                                <td>{{ Utility::priceFormat($settings, $bill->getSubTotal()) }}</td>
                            @endif

                        </tr>
                        <tr>
                            <td colspan="7">{{ __('Subtotal') }}:</td>
                            @if ($bill->currency_id != null && $bill->exchange_rate != 0)
                                <td>{{ Utility::priceFormat($settings, $bill->getSubTotal() / $bill->exchange_rate) }}
                                </td>
                            @elseif($bill->currency_id != null && $bill->exchange_rate == 0)
                                <td>{{ Utility::priceFormat(
                                    $settings,
                                    $bill->getSubTotal() / App\Models\Currency::where('id', $bill->currency_id)->first()->exchange_rate,
                                ) }}
                                </td>
                            @else
                                <td>{{ Utility::priceFormat($settings, $bill->getSubTotal()) }}</td>
                            @endif
                        </tr>
                        @if ($bill->getTotalDiscount())
                            <tr>
                                <td colspan="7">{{ __('Discount') }}:</td>
                                <td>{{ Utility::priceFormat($settings, $bill->totalDiscount) }}</td>
                            </tr>
                        @endif
                        @if (!empty($bill->taxesData))
                            @foreach ($bill->taxesData as $taxName => $taxPrice)
                                <tr>
                                    <td colspan="7">{{ $taxName }} :</td>

                                    <td>{{ $bill->totalTaxPrice }}</td>

                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td colspan="7">{{ __('Total') }}:</td>

                            <td>{{ Utility::priceFormat($settings, $bill->totalRate - $bill->totalDiscount + $bill->totalTaxPrice) }}
                            </td>

                        </tr>
                        <tr>
                            <td colspan="7">{{ __('Paid') }}:</td>
                            @if ($bill->currency_id != null && $bill->exchange_rate != 0)
                                <td>
                                    {{ Utility::priceFormatCurr(
                                        $settings,
                                        ($bill->getTotal() - $bill->getDue() - $bill->billTotalDebitNote()) / $bill->exchange_rate,
                                        App\Models\Currency::find($bill->currency_id)->symbol,
                                    ) }}
                                </td>
                            @elseif($bill->currency_id != null && $bill->exchange_rate == 0)
                                @php
                                    $currency = App\Models\Currency::find($bill->currency_id);
                                    $exchangeRate = $currency ? $currency->exchange_rate : 1;
                                @endphp
                                <td>
                                    {{ Utility::priceFormatCurr(
                                        $settings,
                                        ($bill->getTotal() - $bill->getDue() - $bill->billTotalDebitNote()) / $exchangeRate,
                                        $currency ? $currency->symbol : '',
                                    ) }}
                                </td>
                            @else
                                <td>
                                    {{ Utility::priceFormat(
                                        $settings,
                                        $bill->totalRate - $bill->totalDiscount + $bill->totalTaxPrice - $bill->getDue() - $bill->billTotalDebitNote(),
                                    ) }}
                                </td>
                            @endif

                        </tr>
                        <tr>
                            <td colspan="7">{{ __('Debit Note') }}:</td>
                            @php
                                $currency = $bill->currency_id ? App\Models\Currency::find($bill->currency_id) : null;
                                $debitNoteAmount = $bill->billTotalDebitNote();

                                if ($currency && $bill->exchange_rate != 0) {
                                    $convertedAmount = $debitNoteAmount / $bill->exchange_rate;
                                } else {
                                    $convertedAmount = $debitNoteAmount;
                                }
                            @endphp

                            <td>
                                @if ($currency)
                                    {{ Utility::priceFormatCurr($settings, $convertedAmount, $currency->symbol) }}
                                    <br>
                                    <small class="text-muted">
                                        ({{ __('Exchange Rate:') }} {{ number_format($bill->exchange_rate, 4) }})
                                    </small>
                                @else
                                    {{ Utility::priceFormat($settings, $convertedAmount) }}
                                @endif
                            </td>

                        </tr>
                        <tr>
                            <td colspan="7">{{ __('Due Amount') }}:</td>
                            @php
                                $currency = $bill->currency_id ? App\Models\Currency::find($bill->currency_id) : null;
                                $dueAmount = $bill->getDue();

                                if ($currency && $bill->exchange_rate != 0) {
                                    $convertedDue = $dueAmount / $bill->exchange_rate;
                                } elseif ($currency && $bill->exchange_rate == 0 && $currency->exchange_rate != 0) {
                                    $convertedDue = $dueAmount / $currency->exchange_rate;
                                } else {
                                    $convertedDue = $dueAmount;
                                }
                            @endphp

                            <td>
                                @if ($currency)
                                    {{ Utility::priceFormatCurr($settings, $convertedDue, $currency->symbol) }}
                                    <br>
                                    <small class="text-muted">
                                        ({{ __('Exchange Rate:') }}
                                        {{ number_format($bill->exchange_rate ?: $currency->exchange_rate, 4) }})
                                    </small>
                                @else
                                    {{ Utility::priceFormat($settings, $convertedDue) }}
                                @endif
                            </td>

                        </tr>
                    </tfoot>
                </table>
                <div class="bill-footer">
                    <b>{{ $settings['footer_title'] }}</b> <br>
                    {!! $settings['footer_notes'] !!}
                </div>
            </div>
            <div class="d-flex">
                <div class="info-card" style="text-align: center; border: 1px darkgray solid; width: 100%">
                    <div class="info-title">
                        {{ __('Total') }}
                    </div>
                    {{ Utility::formatPriceToWords($bill->totalRate - $bill->totalDiscount + $bill->totalTaxPrice) }}

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
        @if (!isset($preview))
            @include('bill.script');
        @endif

    </body>

</html>
