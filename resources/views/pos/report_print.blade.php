@php
    $settings = Utility::settings();
    use Illuminate\Support\Str;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('POS Report Print') }}</title>
    <style>
        /* Screen Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        #printarea {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .receipt {
            width: 100%;
            max-width: 100%;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .title { 
            font-size: 18px; 
            font-weight: 800; 
            margin: 10px 0;
            text-align: center;
        }

        .divider { 
            border-top: 1px dashed #000; 
            margin: 10px 0; 
        }

        .line {
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }

        .item-line {
            font-size: 11px;
            margin: 2px 0;
            word-wrap: break-word;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .total {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }

        .no-print {
            display: block;
            text-align: center;
            margin: 20px 0;
        }

        .no-print button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }

        .no-print button:hover {
            background: #0056b3;
        }

        /* ===================== PRINT MODE - EPSON TM-M30 THERMAL PRINTER ===================== */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0 !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                width: 100% !important;
                height: auto !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-size: 10px !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                overflow: visible !important;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }

            #printarea {
                display: block !important;
                visibility: visible !important;
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                background: white !important;
                box-sizing: border-box !important;
            }

            .receipt {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 2mm 1mm !important;
                background: white !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 10px !important;
                line-height: 1.2 !important;
                color: #000 !important;
            }

            .title {
                font-size: 14px !important;
                font-weight: bold !important;
                text-align: center !important;
            }

            .item-line {
                font-size: 9px !important;
                margin: 2px 0 !important;
                page-break-inside: avoid !important;
            }

            .item-name {
                font-size: 9px !important;
                font-weight: bold !important;
                margin-bottom: 2px !important;
            }

            .item-details {
                font-size: 8px !important;
            }

            .total {
                font-size: 10px !important;
                font-weight: bold !important;
            }

            .line {
                font-size: 8px !important;
                margin: 1px 0 !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 8px !important;
            }

            table th, table td {
                border: 1px solid #000 !important;
                padding: 2px !important;
                font-size: 8px !important;
            }

            table th {
                background-color: #f0f0f0 !important;
                font-weight: bold !important;
            }

            .divider {
                border-top: 1px dashed #000 !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">{{ __('Print') }}</button>
        <button onclick="window.close()">{{ __('Close') }}</button>
    </div>

    <div id="printarea">
        <div class="receipt">
            <div class="title">{{ __('POS REPORT') }}</div>
            <div class="divider"></div>
            
            @if(!empty($filters['start_date']) || !empty($filters['end_date']))
                <div class="line">
                    <span>{{ __('Period') }}:</span>
                </div>
                @if(!empty($filters['start_date']))
                    <div class="line">
                        <span>{{ __('From') }}: {{ $filters['start_date'] }}</span>
                    </div>
                @endif
                @if(!empty($filters['end_date']))
                    <div class="line">
                        <span>{{ __('To') }}: {{ $filters['end_date'] }}</span>
                    </div>
                @endif
                <div class="divider"></div>
            @endif

            <div class="line" style="font-weight: bold; text-align: center;">
                <span>{{ __('Sold Items Report') }}</span>
            </div>
            <div class="divider"></div>

            {{-- Items List --}}
            @if(!empty($allItems) && count($allItems) > 0)
                <div style="font-size: 8px; margin: 4px 0;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 3px;">
                        <thead>
                            <tr style="background-color: #f8f9fa; font-weight: bold; border-bottom: 1px solid #000;">
                                <th style="text-align: left; padding: 2px; border: 1px solid #000;">{{ __('Item') }}</th>
                                <th style="text-align: right; padding: 2px; border: 1px solid #000;">{{ __('Quantity') }}</th>
                                <th style="text-align: right; padding: 2px; border: 1px solid #000;">{{ __('Price') }}</th>
                                <th style="text-align: right; padding: 2px; border: 1px solid #000;">{{ __('Tax') }}</th>
                                <th style="text-align: right; padding: 2px; border: 1px solid #000;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allItems as $index => $item)
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 3px; border: 1px solid #000; text-align: left;">
                                        {{ $index + 1 }}. {{ Str::limit($item['name'], 35) }}
                                    </td>
                                    <td style="padding: 3px; border: 1px solid #000; text-align: right;">
                                        {{ number_format($item['quantity'], 2) }}
                                    </td>
                                    <td style="padding: 3px; border: 1px solid #000; text-align: right;">
                                        {{ \Auth::user()->priceFormat($item['price_after_discount_combo']) }}
                                    </td>
                                    <td style="padding: 3px; border: 1px solid #000; text-align: right;">
                                        {{ \Auth::user()->priceFormat($item['tax_amount'] ?? 0) }}
                                    </td>
                                    <td style="padding: 3px; border: 1px solid #000; text-align: right; font-weight: bold;">
                                        {{ \Auth::user()->priceFormat($item['total']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="line">{{ __('No items found') }}</div>
            @endif

            <div class="divider"></div>
            
            <div class="total" style="margin-top: 10px;">
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Quantity') }}:</span>
                    <span>{{ number_format($totalQty ?? 0, 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Amount') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalAmount ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Discount') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalDiscount ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Tax') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalTax ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Combo Savings') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalComboSavings ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Voucher') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalVoucher ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0;">
                    <span>{{ __('Total Refund') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalRefund ?? 0) }}</span>
                </div>
                <div class="line" style="margin: 3px 0; font-weight: bold; border-top: 1px solid #000; padding-top: 3px;">
                    <span>{{ __('Total') }}:</span>
                    <span>{{ \Auth::user()->priceFormat($totalActualPaid ?? 0) }}</span>
                </div>
            </div>

            <div class="divider"></div>
            <div style="text-align: center; font-size: 9px; margin-top: 10px;">
                {{ date('Y-m-d H:i:s') }}
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

