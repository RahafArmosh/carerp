<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('POS Refund') }} - {{ \Auth::user()->posNumberFormat($refund->pos->pos_id) }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 10px;
            background: white;
            color: black;
            font-size: 12px;
            line-height: 1.2;
            width: 80mm; /* Standard receipt width */
            max-width: 80mm;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .company-info {
            margin-bottom: 10px;
        }
        .company-info h2 {
            font-size: 14px;
            margin: 5px 0;
            font-weight: bold;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 10px;
        }
        .refund-details {
            margin-bottom: 15px;
        }
        .refund-details h3 {
            font-size: 12px;
            margin: 10px 0 5px 0;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            padding: 1px 0;
            font-size: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 50%;
            color: black;
        }
        .detail-value {
            width: 50%;
            text-align: right;
            color: black;
            font-weight: bold;
        }
        .amount-section {
            border: 1px solid #000;
            padding: 8px;
            margin: 15px 0;
        }
        .amount-section h3 {
            font-size: 12px;
            margin: 0 0 8px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .total-amount {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border: 2px solid #000;
            margin-top: 10px;
            color: black;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            font-size: 9px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 3px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .center-text {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        @media print {
            body { 
                margin: 0; 
                padding: 5px;
                width: 80mm;
                max-width: 80mm;
            }
            .no-print { display: none; }
            .page-break { page-break-after: always; }
        }
        @page {
            size: 80mm auto;
            margin: 5mm;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            {{ __('Print') }}
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            {{ __('Close') }}
        </button>
    </div>

    <div class="header">
        <div class="center-text bold" style="font-weight: bold; color: black; font-size: 16px;">{{ __('REFUND RECEIPT') }}</div>
        <div class="company-info">
            <div class="center-text bold" style="font-weight: bold; color: black;">{{ \Auth::user()->name }}</div>
            <div class="center-text" style="font-weight: bold; color: black;">{{ __('Refund ID') }}: <strong>#{{ $refund->id }}</strong></div>
            <div class="center-text" style="font-weight: bold; color: black;">{{ __('Date') }}: {{ \Auth::user()->dateFormat($refund->created_at) }}</div>
            <div class="center-text" style="font-weight: bold; color: black;">{{ __('Time') }}: {{ $refund->created_at->format('H:i:s') }}</div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="refund-details">
        <div class="center-text bold" style="font-weight: bold; color: black; font-size: 12px;">{{ __('REFUND INFORMATION') }}</div>
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black;">{{ __('POS ID') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black;">{{ \Auth::user()->posNumberFormat($refund->pos->pos_id) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Customer') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black;">{{ $refund->pos->customer->name ?? 'N/A' }}</span>
        </div>
        @if($refund->pos->customer && $refund->pos->customer->phone)
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Phone') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black;">{{ $refund->pos->customer->phone }}</span>
        </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="refund-details">
        <div class="center-text bold" style="font-weight: bold; color: black; font-size: 12px;">{{ __('REFUND ITEMS') }}</div>
        @if($refund->items && $refund->items->count() > 0)
            @foreach($refund->items as $index => $item)
                @php
                    $productName = 'N/A';
                    $productNo = $item->product_no ?? 'N/A';
                    
                    if ($item->posProduct) {
                        if ($item->posProduct->sub_product && $item->posProduct->sub_product->productService) {
                            $productName = $item->posProduct->sub_product->productService->name ?? 'N/A';
                            $productNo = $item->posProduct->sub_product->product_no ?? $productNo;
                        } elseif ($item->posProduct->product) {
                            $productName = $item->posProduct->product->name ?? 'N/A';
                        }
                    }
                @endphp
                <div style="border-bottom: 1px solid #000; padding: 5px 0; margin-bottom: 5px;">
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Item') }} {{ $index + 1 }}:</span>
                        <span class="detail-value" style="font-weight: bold; color: black;">{{ $productName }}</span>
                    </div>
                    @if($productNo != 'N/A')
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Product No') }}:</span>
                        <span class="detail-value" style="font-weight: bold; color: black;">{{ $productNo }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Quantity') }}:</span>
                        <span class="detail-value" style="font-weight: bold; color: black;">{{ $item->quantity }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Refund Price') }}:</span>
                        <span class="detail-value" style="font-weight: bold; color: black;">{{ \Auth::user()->priceFormat($item->return_price) }}</span>
                    </div>
                    @if($item->combo_id)
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Combo') }}:</span>
                        <span class="detail-value" style="font-weight: bold; color: black;">{{ __('Yes') }}</span>
                    </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="center-text" style="font-weight: bold; color: black;">{{ __('No items found') }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="amount-section" style="border: 2px solid #000;">
        <div class="center-text bold" style="font-weight: bold; color: black; font-size: 14px; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px;">{{ __('TOTAL RETURN AMOUNT') }}</div>
        <div class="total-amount" style="font-size: 16px; font-weight: bold; color: black; border: 2px solid #000; padding: 10px; margin-top: 10px;">
            <strong style="font-weight: bold; color: black;">{{ \Auth::user()->priceFormat($refund->total_amount) }}</strong>
        </div>
    </div>

    @if($refund->description)
    <div class="divider"></div>
    <div class="refund-details">
        <div class="center-text bold">{{ __('DESCRIPTION') }}</div>
        <div class="center-text">{{ $refund->description }}</div>
    </div>
    @endif

    @if($refund->voucher_id && $refund->voucher)
    <div class="divider"></div>
    <div class="refund-details" style="border: 2px solid #000; padding: 10px;">
        <div class="center-text bold" style="font-weight: bold; color: black; font-size: 14px; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px;">{{ __('VOUCHER INFORMATION') }}</div>
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black; font-size: 12px;">{{ __('Voucher ID') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black; font-size: 14px;"><strong>#{{ $refund->voucher->id }}</strong></span>
        </div>
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Voucher Amount') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black;">{{ \Auth::user()->priceFormat($refund->voucher->amount) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label" style="font-weight: bold; color: black;">{{ __('Valid Until') }}:</span>
            <span class="detail-value" style="font-weight: bold; color: black;">{{ \Auth::user()->dateFormat($refund->voucher->valid_until) }}</span>
        </div>
        <div class="center-text" style="margin: 10px 0;">
            <div class="center-text bold" style="font-size: 10px; margin-bottom: 5px; font-weight: bold; color: black;">{{ __('Voucher Barcode') }}</div>
            <div style="text-align: center; margin: 5px 0;">
                {!! DNS1D::getBarcodeHTML((string)$refund->voucher->id, 'C128', 1, 30) !!}
            </div>
            <div class="center-text" style="font-size: 8px; margin-top: 3px; font-weight: bold; color: black;">{{ $refund->voucher->id }}</div>
        </div>
    </div>
    @endif

    <div class="divider"></div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                {{ __('Customer Signature') }}
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                {{ __('Authorized By') }}
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <div class="center-text">{{ __('Computer Generated Receipt') }}</div>
        <div class="center-text">{{ __('Thank you for your business!') }}</div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
