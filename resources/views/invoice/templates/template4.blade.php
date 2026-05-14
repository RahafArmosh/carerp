@php
    $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
    $company_logo = $settings_data['company_logo_dark'] ?? '';
    $company_dark_logo = $settings_data['company_logo_light'] ?? '';
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
                --theme-color: {{ $color }};
                --white: #ffffff;
                --black: #000000;
            }

            body {
                font-family: 'Lato', sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            p,
            li,
            ul,
            ol {
                margin: 0;
                padding: 0;
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

            table th small {
                display: block;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            tr,
            .invoice-footer {
                break-inside: avoid;
                break-after: auto;
            }

            thead {
                display: table-header-group;

            }

            tfoot {
                display: table-footer-group;
            }

            .invoice-preview-main {
                max-width: 700px;
                width: 100%;
                margin: 0 auto;
                background: #ffff;
                box-shadow: 0 0 10px #ddd;
            }

            .invoice-logo {
                max-width: 200px;
                width: 100%;
            }

            .invoice-header table td {
                padding: 15px 30px;
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
                margin-top: 15px;
                background: var(--white);
            }

            table.add-border tr {
                border-top: 1px solid var(--theme-color);
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
                font-weight: 600;
            }

            .total-table td:last-of-type {
                width: 146px;
            }

            .invoice-footer {
                padding: 5px 20px;
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

            .invoice-footer {
                font-size: 12px;
            }

            .invoice-summary p {
                margin-bottom: 0;
            }

            .invoice-header-flex {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 15px 30px 0 30px;
            }

            .customer-wrapper {
                display: flex;
                align-items: center;
                gap: 10px;
                text-align: left;

            }

            .logo {
                width: 120px;
            }

            .invoice-logo {
                max-width: 200px;
                width: 100%;
            }

            .invoice-summary {
                border: 1px solid #e0e0e0;
                overflow: hidden;
            }

            /* Borders for invoice-summary and total-table */
            .invoice-summary,
            .total-table {
                border: 1px solid #e0e0e0;
                background: #fff;
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin-bottom: 0.5rem;
            }

            .invoice-summary th,
            .invoice-summary td,
            .total-table th,
            .total-table td {
                border: 1px solid #e0e0e0;
                padding: 0.75rem;
                text-align: left;
            }

            .invoice-summary thead th {
                background: var(--theme-color);
                color: {{ $font_color }};
                font-weight: bold;
                border-bottom: 2px solid var(--theme-color);
            }

            .stamp {
                width: 85px
            }
        </style>

        @if ($settings_data['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>

    <body>
        <div class="invoice-preview-main" id="boxes">
            <div class="invoice-header">
                <div class="invoice-header-flex">
                    <div class="text-left">
                        <img class="logo"
                            src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') }}"
                            alt="Company Logo">
                    </div>
                    <div class="text-right">
                        <img class="logo"
                            src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_dark_logo) && !empty($company_dark_logo) ? $company_dark_logo : 'logo-dark.png') }}"
                            alt="Company Logo">
                    </div>
                </div>
                <div class="invoice-header-flex">
                    <div class="text-left rent-invoice-data">
                        @if (isset($invoice->type) && $invoice->type == 'rent')
                            <div>
                                <div style="font-weight: bold;margin-bottom: 10px;">{{ __('Tax Invoice') }}</div>
                            </div>
                            <div style="font-size: 13px; line-height: 1.7;">
                                <div><span style="font-weight: bold;">{{ __('TRN') }}:</span>
                                    <span>{{ $settings['registration_number'] }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('Invoice ID') }}:</span>
                                    <span>{{ Utility::invoiceNumberFormat($settings, $invoice->id) }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('Created At') }}:</span>
                                    <span>{{ Utility::dateFormat($settings, $invoice->created_at) }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('Currency') }}:</span> <span>AED</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('Ref Number') }}:</span>
                                    <span>{{ $invoice->ref_number }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('Number of days') }}:</span>
                                    <span>{{ $invoice->getDaysDifferenceAttribute() }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('From') }}:</span>
                                    <span>{{ Utility::dateFormat($settings, $invoice->issue_date) }}</span>
                                </div>
                                <div><span style="font-weight: bold;">{{ __('To') }}:</span>
                                    <span>{{ Utility::dateFormat($settings, $invoice->due_date) }}</span>
                                </div>
                                {{-- Vehicle info line for rent invoice --}}
                                @php
                                    $vehicleItem = collect($invoice->itemData ?? [])->first();
                                @endphp
                                @if ($vehicleItem)
                                    @php
                                        $subProduct = App\Models\SubProduct::find($vehicleItem->sub_product_id);
                                        $customFields = isset($vehicleItem->customField)
                                            ? $vehicleItem->customField
                                            : [];
                                        $customFieldValues = collect($customFields)
                                            ->map(function ($value, $fieldId) {
                                                $field = App\Models\CustomField::find($fieldId);
                                                return $value;
                                            })
                                            ->filter()
                                            ->values()
                                            ->toArray();
                                    @endphp
                                    <div><span style="font-weight: bold;">{{ __('Vehicle') }}:</span>
                                        <span>
                                            {{ $vehicleItem->name ?? '-' }},
                                            {{ $subProduct->chassis_no ?? '-' }}
                                            @if (count($customFieldValues) > 0)
                                                , {{ implode(', ', $customFieldValues) }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="text-right rent-invoice-customer">
                        @if (isset($invoice->type) && $invoice->type == 'rent')
                            <div
                                style="border: 1px solid #ccc; border-radius: 6px; padding: 10px 16px; font-size: 13px; min-width: 220px;">
                                <div class="customer-wrapper">
                                    <div class="">
                                        <div style="font-weight: bold; margin-bottom: 6px;">{{ __('Invoice To') }}
                                        </div>
                                        <div style="font-weight: bold;">{{ $customer->name }}</div>
                                        <div>
                                            {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                            {{ !empty($customer->billing_city) ? $customer->billing_city : '' }}
                                            @if (!empty($customer->billing_city))
                                                ,
                                            @endif
                                            {{ !empty($customer->billing_state) ? $customer->billing_state : '' }}
                                            @if (!empty($customer->billing_state))
                                                ,
                                            @endif
                                            {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                            {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                            {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}

                                        </div>
                                        <div style="font-weight: bold; margin-bottom: 6px;">{{ __('Driver') }}
                                        </div>
                                        <div style="font-weight: bold;">
                                            {{ $invoice->driver_id ? $invoice->driver->name : '-' }}</div>
                                    </div>
                                    <div class="view-qrcode">
                                        {!! DNS2D::getBarcodeHTML(route('invoice.link.copy', \Crypt::encrypt($invoice->id)), 'QRCODE', 1.5, 1.5) !!}
                                    </div>
                                </div>
                                @if (!empty($customer->name))
                                @else
                                    <div>-</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

            </div>
            <div class="invoice-body">
                <table class=" invoice-summary" style="margin-top: 30px;">
                    <thead style="background: {{ $color }};color:{{ $font_color }}">
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Total (before tax)') }}</th>
                            <th>{{ __('Tax') }} (%)</th>
                            <th>{{ __('Balance') }} <small>{{ __('after tax') }}</small></th>
                        </tr>
                    </thead>
                    <tbody style="border-bottom:1px solid {{ $color }};">
                        @if (isset($invoice->itemData) && count($invoice->itemData) > 0)
                            @if (isset($invoice->expenses) && count($invoice->expenses) > 0)
                                @foreach ($invoice->expenses as $expense)
                                    <tr>
                                        <td><small>{{ $expense->description }}</small></td>
                                        <td>{{ number_format($expense->amount, 2) }}</td>
                                        <td>-</td>
                                        <td>{{ number_format($expense->amount, 2) }}</td>
                                        <td>-</td>
                                        <td>{{ number_format($expense->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            @foreach ($invoice->itemData as $key => $item)
                                <tr>
                                    <td>
                                        @php
                                            $subProduct = isset($item->sub_product_id)
                                                ? App\Models\SubProduct::find($item->sub_product_id)
                                                : null;
                                        @endphp
                                        <small>{{ $item->name }},
                                            {{ $subProduct->chassis_no ?? '-' }}</small>
                                        @if (!empty($item->description))
                                            <br><small>{{ $item->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($item->price, 2) }}
                                    </td>
                                    <td>{{ $item->discount != 0 ? number_format($item->discount, 2) : '-' }}</td>
                                    <td>
                                        @php
                                            $amountBeforeTax = $item->price - $item->discount;
                                        @endphp
                                        {{ number_format($amountBeforeTax, 2) }}
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
                                                <p>{{ $taxes['name'] }} ({{ $taxes['rate'] }})<br />
                                                    {{ number_format($taxes['tax_price'], 2) }}</p>
                                            @endforeach
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($amountBeforeTax + $itemtax, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="border-bottom:1px solid {{ $color }};">
                            <td>{{ __('Invoice Amount') }}</td>
                            <td>{{ number_format($invoice->getTotalAmount(), 2) }}</td>
                            <td>{{ number_format($invoice->totalDiscount, 2) }}</td>
                            <td>{{ number_format($invoice->getSubTotal(), 2) }}</td>
                            <td>{{ number_format($invoice->totalTaxPrice, 2) }}</td>
                            <td>{{ number_format($invoice->getSubTotal() - $invoice->getTotalDiscount() + $invoice->getTotalTax(), 2) }}
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid {{ $color }};">
                            <td>{{ __('Paid') }}:</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{{ number_format($invoice->getTotal() - $invoice->getDue() - $invoice->invoiceTotalCreditNote(), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('Due Amount') }}:</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{{ number_format($invoice->getDue(), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
                <div class="invoice-bank-details" style="margin-top: 20px;">
                    {{-- <ul style="margin-left: 30px;"> --}}
                    {{-- @if (!empty($invoice->bankAccount->holder_name))
                            <li>{{ __('Account Holder') }}: {{ $invoice->bankAccount->holder_name }}</li>
                        @endif
                        @if (!empty($invoice->bankAccount->bank_name))
                            <li>{{ __('Bank Name') }}: {{ $invoice->bankAccount->bank_name }}</li>
                        @endif
                        @if (!empty($invoice->bankAccount->account_number))
                            <li>{{ __('Account Number') }}: {{ $invoice->bankAccount->account_number }}
                            </li>
                        @endif
                        @if (!empty($invoice->bankAccount->contact_number))
                            <li>{{ __('Contact Number') }}: {{ $invoice->bankAccount->contact_number }}
                            </li>
                        @endif
                        @if (!empty($invoice->bankAccount->bank_address))
                            <li>{{ __('Bank Address') }}: {{ $invoice->bankAccount->bank_address }}</li>
                        @endif --}}
                    @if (!empty($invoice->bankAccount->bank_details))
                        {{-- <li>{{ __('Bank Details') }}: --}}
                        @php
                            $lines = explode("\n", $invoice->bankAccount->bank_details);
                        @endphp
                        @foreach ($lines as $line)
                            <li style="list-style-type: disc; margin-left: 30px;">{{ $line }}</li>
                        @endforeach
                        </li>
                    @endif
                    {{-- </ul> --}}
                </div>
            </div>
            <div style="margin-top: 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <!-- Receiver Signature -->
                    <div
                        style="flex: 1 1 33%; text-align: center; border: 1px solid #222732; padding: 16px 8px; margin-right: 10px;">
                        <div style="font-weight: bold; margin-bottom: 20px;">{{ __('Receiver Signature') }}</div>
                        <div style="height: 30px;"></div>
                    </div>
                    <!-- Manager Signature -->
                    <div
                        style="flex: 1 1 33%; text-align: center; border: 1px solid #222732; padding: 16px 8px; margin-right: 10px;">
                        <div style="font-weight: bold; margin-bottom: 20px;">{{ __('Manager Signature') }}</div>
                        <div style="height: 30px;"></div>
                    </div>
                    <!-- Accountant Signature with Image -->
                    <div style="flex: 1 1 33%; text-align: center;">
                        <div style="font-weight: bold; margin-bottom: 8px;">{{ __('Accountant Signature') }}</div>
                        <img src="{{ (!empty($company_stamp) ? URL::to('/') . '/' . 'documents' . '/' . $company_stamp : URL::to('/') . '/' . 'storage/uploads/logo' . '/' . 'stamp-preview.png') . '?timestamp=' . time() }}"
                            class="stamp" width="60" alt="Company Signature">
                    </div>
                </div>
            </div>
            <div class="invoice-footer" style="background-color: #222732; color: #fff;">
                <div style="display: flex; gap:35px">
                    <!-- Left: Company Contact Info -->
                    <div style="flex: 1 1 50%; min-width: 250px;">
                        <div style="color: #fff;">
                            <div style="display: flex; align-items: center;justify-content: space-between;">
                                <div style="margin-bottom: 6px;">
                                    <smll>{{ __('Phone') }}:</smll>
                                    <span>{{ !empty($settings['company_telephone']) ? $settings['company_telephone'] : '-' }}</span>
                                </div>
                                <div style="margin-bottom: 6px;">
                                    <smll>{{ __('Mobile') }}:</smll>
                                    <span>{{ !empty($settings['company_telephone']) ? $settings['company_telephone'] : '-' }}</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                {{-- <div style="margin-bottom: 6px;">
                                <smll>{{ __('Email') }}:</smll>
                                <span>{{ !empty($settings['company_email']) ? $settings['company_email'] : '-' }}</span>
                            </div> --}}
                                {{-- <div style="margin-bottom: 6px;">
                                <smll>{{ __('Website') }}:</smll>
                                <span>{{ !empty($settings['company_website']) ? $settings['company_website'] : '-' }}</span>
                            </div> --}}
                            </div>
                        </div>
                    </div>
                    <!-- Right: Company Address -->
                    <div style="flex: 1 1 50%; min-width: 250px;">
                        <samll>
                            @if (!empty($settings['company_address']))
                                {{ $settings['company_address'] }}
                            @endif
                            @if (!empty($settings['company_city']))
                                <br>{{ $settings['company_city'] }},
                            @endif
                            @if (!empty($settings['company_state']))
                                {{ $settings['company_state'] }}
                            @endif
                            @if (!empty($settings['company_zipcode']))
                                - {{ $settings['company_zipcode'] }}
                            @endif
                            @if (!empty($settings['company_country']))
                                <br>{{ $settings['company_country'] }}
                            @endif
                        </samll>
                    </div>
                </div>
            </div>
        </div>
        @if (!isset($preview))
            @include('invoice.script');
        @endif
    </body>

</html>
