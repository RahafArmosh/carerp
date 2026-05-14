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
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
        <style type="text/css">
            body {
                font-family: 'Lato', sans-serif;
                background: #fff;
                color: #222;
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 800px;
                margin: 30px auto;
                background: #fff;
                box-shadow: 0 0 10px #ddd;
                padding: 40px 40px 30px 40px;
                position: relative;
            }

            .header-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
            }

            .company-name-left {
                font-size: 16px;
                font-weight: bold;
                color: #222;
                margin-bottom: 10px;
            }

            .logo-center {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
            }

            .logo-center img.logo {
                max-width: 120px;
                margin-bottom: 10px;
            }

            .company-name-center {
                font-size: 18px;
                font-weight: bold;
                color: #222;
                margin-bottom: 5px;
                text-align: center;
            }

            .invoice-title {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 20px;
                text-align: center;
                color: #222;
            }

            .details-table {
                width: 100%;
                margin-bottom: 25px;
                margin-top: 30px;
                font-size: 12px;
                table-layout: fixed;
                border-collapse: collapse;
            }

            .details-table td {
                padding: 6px 0;
                vertical-align: top;
            }

            .details-table td.details-label {
                width: 130px;
                padding-right: 12px;
                font-weight: 700;
            }

            .details-table td.details-value {
                word-break: break-word;
            }

            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 0;
                table-layout: fixed;
            }

            .summary-table th,
            .summary-table td {
                border: 1px solid #bbb;
                padding: 9px 8px;
                text-align: left;
                font-size: 11px;
                background: none;
                color: #222;
                vertical-align: top;
                word-break: break-word;
                overflow-wrap: anywhere;
            }

            .summary-table thead th {
                font-weight: bold;
                text-align: center;
                background: #f0f0f0;
                padding: 10px 8px;
            }

            .summary-table tbody td.col-no,
            .summary-table tbody td.col-qty,
            .summary-table tbody td.col-unit-price,
            .summary-table tbody td.col-discount,
            .summary-table tbody td.col-total {
                vertical-align: middle;
            }

            .summary-table th {
                font-weight: bold;
            }

            .summary-table .col-no {
                width: 4%;
                text-align: center;
                word-break: normal;
            }
            .summary-table .col-qty {
                width: 6%;
                text-align: center;
                word-break: normal;
                white-space: nowrap;
            }
            .summary-table .col-unit-price,
            .summary-table .col-discount,
            .summary-table .col-total {
                width: 10%;
                text-align: right;
                word-break: normal;
                white-space: nowrap;
            }
            .summary-table .col-item {
                width: 22%;
                line-height: 1.45;
                white-space: normal;
            }

            .item-name-stacked .item-name-line {
                display: block;
                margin-bottom: 3px;
            }

            .item-name-stacked .item-name-line:last-child {
                margin-bottom: 0;
            }

            .summary-table .col-desc {
                width: 16%;
            }
            .summary-table .col-cf {
                width: 7%;
            }

            .summary-table .total-row td {
                font-weight: bold;
                background: none;
            }

            .summary-table tfoot td {
                font-size: 11px;
                background: none;
                border-top: 1px solid #bbb;
                vertical-align: middle;
            }

            .summary-table tfoot td.tfoot-label {
                text-align: right;
                padding-right: 12px;
                font-style: italic;
            }

            .summary-table tfoot td.tfoot-amount {
                text-align: right;
                font-style: normal;
                font-weight: 700;
                white-space: nowrap;
            }

            .summary-table tfoot tr.amount-in-words td {
                font-style: italic;
                text-align: left;
                padding-top: 10px;
            }

            .section-title {
                font-size: 14px;
                font-weight: bold;
                margin: 30px 0 10px 0;
                color: #222;
            }

            .description-section,
            .bank-details-section {
                margin-bottom: 20px;
            }

            /* Optional spacer item inside remarks list to give extra room
               when the list happens to continue on the next page */
            .remarks-spacer {
                list-style: none;
                margin-top: 12px;
                margin-bottom: 4px;
            }

            .bank-details {
                break-inside: avoid;
                break-after: auto;
                page-break-inside: avoid;
            }

            .footer {
                position: relative;
                break-inside: avoid;
                break-after: auto;
                margin-top: 36px;
                padding: 12px 100px 16px 12px;
                font-size: 11px;
                color: #444;
                text-align: center;
                line-height: 1.55;
            }

            .stamp {
                position: absolute;
                right: 12px;
                bottom: 8px;
                max-width: 90px;
                width: auto;
                height: auto;
                opacity: 0.85;
            }

            /* Adjust printed page margins so content doesn't touch the border when remarks flow to a new page */
            @page {
                margin-top: 80px;
                margin-bottom: 60px;
            }

            @media print {
                .container {
                    box-shadow: none;
                }

                .stamp {
                    opacity: 1;
                }

                /* Improve page breaks for long remarks lists */
                .description-section ol {
                    page-break-inside: auto;
                    break-inside: auto;
                }

                .description-section li {
                    page-break-inside: avoid;
                    break-inside: avoid;
                }
            }
        </style>
    </head>

    <body>
        <div class="container" id="boxes">
            <div class="header-row">
                <div class="">
                    <img class="logo" width="120px"
                        src="{{ URL::to('/') . '/' . 'documents' . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                        alt="Company Logo">
                </div>
                <div class="">
                    <div class="company-name-center">
                        {{ $settings['company_name'] ?? '' }}
                    </div>
                    <div class="invoice-title">
                        {{ $pdfTitle ?? __('Proposal Invoice') }}
                        {{ Utility::proposalNumberFormat($settings, $proposal->proposal_id) }}
                    </div>
                </div>
                <div style="width:120px;"></div>
            </div>
            <table class="details-table">
                <tr>
                    <td class="details-label">{{ __('Customer ID') }}:</td>
                    <td class="details-value">{{ $customer->customer_id ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="details-label">{{ __('Name') }}:</td>
                    <td class="details-value">{{ $customer->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="details-label">{{ __('Address') }}:</td>
                    <td class="details-value">{{ $customer->billing_address ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="details-label">{{ __('Phone') }}:</td>
                    <td class="details-value">{{ $customer->billing_phone ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="details-label">{{ __('Email') }}:</td>
                    <td class="details-value">{{ $customer->email ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="details-label">{{ __('Currency') }}:</td>
                    <td class="details-value">
                        {{ $proposal->currency->name ?? '-' }}
                        @if ($proposal->currency_id && $proposal->exchange_rate && $proposal->exchange_rate > 0)
                            <br><span style="font-size: 10px; color: #666;">{{ __('Rate') }}: {{ number_format($proposal->exchange_rate, 4) }}</span>
                        @endif
                    </td>
                </tr>
            </table>
            <table class="summary-table">
                @php
                    // Proposal item custom fields (module = proposal_item) - used to build columns
                    $proposalItemCustomFields = \App\Models\CustomField::where('created_by', (int) $proposal->created_by)
                        ->where('module', 'proposal_item')
                        ->orderBy('id')
                        ->get(['id', 'name']);
                    $proposalItemCustomFieldsCount = $proposalItemCustomFields->count();
                @endphp
                <thead>
                    <tr>
                        <th class="col-no">{{ __('NO') }}</th>
                        <th class="col-item">{{ __('Item') }}</th>
                        <th class="col-qty">{{ __('Qty') }}</th>
                        <th class="col-unit-price">{{ __('Unit Price') }}</th>
                        <th class="col-discount">{{ __('Discount') }}</th>
                        @if ($proposalItemCustomFieldsCount > 0)
                            @foreach ($proposalItemCustomFields as $cf)
                                <th class="col-cf">{{ __($cf->name) }}</th>
                            @endforeach
                        @endif
                        <th class="col-desc">{{ __('Description') }}</th>
                        <th class="col-total">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subTotal = 0;
                        $totalDiscount = 0;
                        $currency = null;
                        $currencySymbol = $settings['site_currency_symbol'] ?? '';
                        if ($proposal->currency_id && ($currency = App\Models\Currency::find($proposal->currency_id))) {
                            $currencySymbol = $currency->symbol;
                        }
                        $formatCurrSpaced = function ($amount) use ($settings, $currencySymbol, $proposal, $currency) {
                            $decimals = (int) ($settings['decimal_number'] ?? 2);
                            if ($proposal->currency_id && $currency) {
                                $num = number_format((float) $amount, $decimals);
                                $sym = (string) $currencySymbol;
                                $pos = $settings['site_currency_symbol_position'] ?? 'post';

                                return $pos === 'pre' ? trim($sym . ' ' . $num) : trim($num . ' ' . $sym);
                            }

                            return \App\Models\Utility::priceFormat($settings, $amount);
                        };
                    @endphp
                    @if (isset($proposal->itemData) && count($proposal->itemData) > 0)
                        @foreach ($proposal->itemData as $key => $item)
                            @php
                                $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                $itemDiscount = $item->discount * $item->quantity;
                                // Item total without tax (tax is at proposal level)
                                $itemTotal = ($item->price * $item->quantity) - $itemDiscount;
                                $subTotal += ($item->price * $item->quantity);
                                $totalDiscount += $itemDiscount;
                            @endphp
                            <tr>
                                <td class="col-no">{{ $key + 1 }}</td>
                                <td class="col-item">
                                    @php
                                        $rawName = (string) ($item->name ?? '');
                                        $nameParts = array_values(array_filter(array_map('trim', preg_split('#/#', $rawName)), function ($p) {
                                            return $p !== '';
                                        }));
                                    @endphp
                                    @if (count($nameParts) > 1)
                                        <div class="item-name-stacked">
                                            @foreach ($nameParts as $part)
                                                <span class="item-name-line">{{ $part }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        {{ $item->name }}
                                    @endif
                                </td>
                                <td class="col-qty">{{ $item->quantity }}</td>
                                <td class="col-unit-price">
                                    {{ $formatCurrSpaced($item->price) }}
                                </td>
                                <td class="col-discount">
                                    @if ($itemDiscount > 0)
                                        {{ $formatCurrSpaced($itemDiscount) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                @if ($proposalItemCustomFieldsCount > 0)
                                    @php
                                        $itemCfMap = [];
                                        if (!empty($item->proposal_item_custom_fields) && is_array($item->proposal_item_custom_fields)) {
                                            foreach ($item->proposal_item_custom_fields as $p) {
                                                $n = $p['name'] ?? null;
                                                if ($n !== null && $n !== '') {
                                                    $itemCfMap[$n] = $p['value'] ?? '';
                                                }
                                            }
                                        }
                                    @endphp
                                    @foreach ($proposalItemCustomFields as $cf)
                                        @php
                                            $v = $itemCfMap[$cf->name] ?? null;
                                        @endphp
                                        <td class="col-cf">{{ ($v !== null && trim((string) $v) !== '') ? $v : '-' }}</td>
                                    @endforeach
                                @endif
                                <td class="col-desc">
                                    @if ($item->description)
                                        @php
                                            // Split description by newlines
                                            $lines = preg_split('/\r?\n/', $item->description, -1, PREG_SPLIT_NO_EMPTY);
                                            $listItems = [];
                                            $currentItem = '';
                                            $hasSubItems = false;
                                            
                                            foreach ($lines as $line) {
                                                $trimmed = trim($line);
                                                if (empty($trimmed)) continue;
                                                
                                                // Check if line starts with a number followed by period/dot
                                                if (preg_match('/^(\d+)\.\s*(.+)$/', $trimmed, $matches)) {
                                                    // If we have a previous item, save it
                                                    if (!empty($currentItem)) {
                                                        $listItems[] = ['text' => $currentItem, 'hasHtml' => $hasSubItems];
                                                        $hasSubItems = false;
                                                    }
                                                    // Start new item
                                                    $currentItem = e(trim($matches[2]));
                                                } elseif (preg_match('/^\(([ivx]+)\)\s*(.+)$/i', $trimmed, $subMatches)) {
                                                    // Handle sub-items like (i), (ii), etc. - append to current item with proper formatting
                                                    if (!empty($currentItem)) {
                                                        $currentItem .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;(' . strtolower($subMatches[1]) . ') ' . e(trim($subMatches[2]));
                                                        $hasSubItems = true;
                                                    } else {
                                                        $listItems[] = ['text' => '(' . strtolower($subMatches[1]) . ') ' . e(trim($subMatches[2])), 'hasHtml' => false];
                                                    }
                                                } else {
                                                    // Continuation of current item (multi-line)
                                                    if (!empty($currentItem)) {
                                                        $currentItem .= ' ' . e($trimmed);
                                                    } else {
                                                        // Line doesn't start with number, treat as regular text
                                                        $listItems[] = ['text' => e($trimmed), 'hasHtml' => false];
                                                    }
                                                }
                                            }
                                            // Add last item if exists
                                            if (!empty($currentItem)) {
                                                $listItems[] = ['text' => $currentItem, 'hasHtml' => $hasSubItems];
                                            }
                                            
                                            // If no numbered items found, use original format
                                            if (empty($listItems)) {
                                                $listItems = array_map(function($line) {
                                                    return ['text' => e(trim($line)), 'hasHtml' => false];
                                                }, array_filter(array_map('trim', $lines)));
                                            }
                                        @endphp
                                        @if (!empty($listItems))
                                            <ol style="margin: 0; padding-left: 20px;">
                                                @foreach ($listItems as $listItem)
                                                    <li style="margin-bottom: 5px;">{!! $listItem['hasHtml'] ? $listItem['text'] : nl2br($listItem['text']) !!}</li>
                                                @endforeach
                                            </ol>
                                        @else
                                            -
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="col-total">
                                    {{ $formatCurrSpaced($itemTotal) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                <tfoot>
                    @php
                        // Calculate proposal-level tax from tax_id on (subtotal - discount)
                        $proposalTax = 0;
                        if (!empty($proposal->tax_id)) {
                            $taxData = \App\Models\Utility::getTaxData();
                            $taxArr = explode(',', $proposal->tax_id);
                            $totalTaxRate = 0;
                            foreach ($taxArr as $taxId) {
                                if (!empty($taxData[$taxId]['rate'])) {
                                    $totalTaxRate += $taxData[$taxId]['rate'];
                                }
                            }
                            // Calculate tax on (subtotal - discount)
                            $taxableAmount = $subTotal - $totalDiscount;
                            $proposalTax = ($totalTaxRate / 100) * $taxableAmount;
                        } else {
                            // Fallback to totalTaxPrice if tax_id is not set
                            $proposalTax = isset($proposal->totalTaxPrice) ? $proposal->totalTaxPrice : 0;
                            // If currency is selected and tax is in AED, convert to currency
                            if ($proposal->currency_id && $currency && $proposal->exchange_rate && $proposal->exchange_rate > 0) {
                                $proposalTax = $proposalTax / $proposal->exchange_rate;
                            }
                        }
                    @endphp
                    <tr>
                        <td class="tfoot-label" colspan="{{ 6 + $proposalItemCustomFieldsCount }}"><strong>{{ __('Sub Total') }}</strong></td>
                        <td class="tfoot-amount col-total">{{ $formatCurrSpaced($subTotal) }}</td>
                    </tr>
                    @if ($totalDiscount > 0)
                    <tr>
                        <td class="tfoot-label" colspan="{{ 6 + $proposalItemCustomFieldsCount }}"><strong>{{ __('Discount') }}</strong></td>
                        <td class="tfoot-amount col-total">{{ $formatCurrSpaced($totalDiscount) }}</td>
                    </tr>
                    @endif
                    @if ($proposalTax > 0)
                    <tr>
                        <td class="tfoot-label" colspan="{{ 6 + $proposalItemCustomFieldsCount }}"><strong>{{ __('Tax') }}</strong></td>
                        <td class="tfoot-amount col-total">{{ $formatCurrSpaced($proposalTax) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="tfoot-label" colspan="{{ 6 + $proposalItemCustomFieldsCount }}"><strong>{{ __('Total') }}</strong></td>
                        <td class="tfoot-amount col-total">
                            @php
                                $finalTotal = $subTotal - $totalDiscount + $proposalTax;
                            @endphp
                            {{ $formatCurrSpaced($finalTotal) }}
                        </td>
                    </tr>
                    <tr class="amount-in-words">
                        <td colspan="{{ 7 + $proposalItemCustomFieldsCount }}">
                            @php
                                $finalTotal = $subTotal - $totalDiscount + $proposalTax;
                            @endphp
                            {{ ucwords(\App\Models\Utility::formatPriceToWords($finalTotal)) }} {{ __('only') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
            <div class="section-title">{{ __('Remarks') }}</div>
            <div class="description-section" style="font-size: 14px">
                @if (!empty($proposal->description))
                    @php
                        // Split description by newlines
                        $lines = preg_split('/\r?\n/', $proposal->description, -1, PREG_SPLIT_NO_EMPTY);
                        $listItems = [];
                        $currentItem = '';
                        $hasSubItems = false;
                        
                        foreach ($lines as $line) {
                            $trimmed = trim($line);
                            if (empty($trimmed)) continue;
                            
                            // Check if line starts with a number followed by period/dot
                            if (preg_match('/^(\d+)\.\s*(.+)$/', $trimmed, $matches)) {
                                // If we have a previous item, save it
                                if (!empty($currentItem)) {
                                    $listItems[] = ['text' => $currentItem, 'hasHtml' => $hasSubItems];
                                    $hasSubItems = false;
                                }
                                // Start new item
                                $currentItem = e(trim($matches[2]));
                            } elseif (preg_match('/^\(([ivx]+)\)\s*(.+)$/i', $trimmed, $subMatches)) {
                                // Handle sub-items like (i), (ii), etc. - append to current item with proper formatting
                                if (!empty($currentItem)) {
                                    $currentItem .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;(' . strtolower($subMatches[1]) . ') ' . e(trim($subMatches[2]));
                                    $hasSubItems = true;
                                } else {
                                    $listItems[] = ['text' => '(' . strtolower($subMatches[1]) . ') ' . e(trim($subMatches[2])), 'hasHtml' => false];
                                }
                            } else {
                                // Continuation of current item (multi-line)
                                if (!empty($currentItem)) {
                                    $currentItem .= ' ' . e($trimmed);
                                } else {
                                    // Line doesn't start with number, treat as regular text
                                    $listItems[] = ['text' => e($trimmed), 'hasHtml' => false];
                                }
                            }
                        }
                        // Add last item if exists
                        if (!empty($currentItem)) {
                            $listItems[] = ['text' => $currentItem, 'hasHtml' => $hasSubItems];
                        }
                        
                        // If no numbered items found, use original format
                        if (empty($listItems)) {
                            $listItems = array_map(function($line) {
                                return ['text' => e(trim($line)), 'hasHtml' => false];
                            }, array_filter(array_map('trim', $lines)));
                        }
                    @endphp
                    @if (!empty($listItems))
                        <ol style="margin: 0; padding-left: 20px; line-height: 1.6;">
                            @foreach ($listItems as $index => $listItem)
                                {{-- After the 8th remark, insert a small spacer item.
                                     This creates extra visual separation in cases where
                                     the following items flow onto the next page. --}}
                                @if ($index === 8)
                                    <li class="remarks-spacer"></li>
                                @endif
                                <li style="margin-bottom: 8px;">{!! $listItem['hasHtml'] ? $listItem['text'] : nl2br($listItem['text']) !!}</li>
                            @endforeach
                        </ol>
                    @else
                        -
                    @endif
                @else
                    -
                @endif
            </div>
            @if (!empty($proposal->bankAccount))
                <div class="bank-details">
                    <div class="section-title">{{ __('Bank Details') }}</div>
                    <div class="bank-details-section">
                        <div style="font-size:12px;line-height:1.7;">
                            {{-- {{ !empty($proposal->bankAccount->bank_name) ? $proposal->bankAccount->bank_name : '' }}<br>
                            {{ !empty($proposal->bankAccount->account_number) ? $proposal->bankAccount->account_number : '' }}<br> --}}
                            {!! !empty($proposal->bankAccount->bank_details) ? nl2br(e($proposal->bankAccount->bank_details)) : '' !!}
                        </div>
                    </div>
                </div>
            @endif
            <div class="footer">
                {{ $settings['company_name'] ?? '' }} | {{ $settings['company_address'] ?? '' }} |
                {{ $settings['company_city'] ?? '' }} {{ $settings['company_state'] ?? '' }}
                {{ $settings['company_zipcode'] ?? '' }} | {{ $settings['company_country'] ?? '' }}<br>
                {{ $settings['company_telephone'] ?? '' }} | {{ $settings['mail_from_address'] ?? '' }} |
                {{ $settings['website'] ?? '' }}
                <img src="{{ (!empty($company_stamp) ? URL::to('/') . '/' . 'documents' . '/' . $company_stamp : URL::to('/') . '/' . 'storage/uploads/logo' . '/' . 'stamp-preview.png') . '?timestamp=' . time() }}"
                    class="stamp" width="60" alt="Company Signature">
            </div>
        </div>
        @if (!isset($preview))
            @include('proposal.script');
        @endif
    </body>

</html>
