@php
    $settings = Utility::settings();
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings == 'on' ? 'rtl' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }} - Voucher Barcode</title>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">

    @if (isset($settings['SITE_RTL']) && $settings['SITE_RTL'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css')}}" id="main-style-link">
    @endif

    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 30px 20px;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .voucher-wrapper {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }

        .barcode-box {
            background: #ffffff;
            border: 3px solid #2d3748;
            border-radius: 12px;
            padding: 30px 25px;
            margin: 0 auto;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .voucher-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .barcode-box h3 {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .voucher-subtitle {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        .customer-info {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .barcode-box small {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #2d3748;
            font-weight: 500;
        }

        .customer-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .expiry-info {
            font-size: 13px;
            color: #718096;
            margin-top: 5px;
        }

        .expiry-info strong {
            color: #2d3748;
        }

        .barcode-container {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 15px;
            margin: 25px 0;
            position: relative;
        }

        .barcode-label {
            font-size: 10px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .barcode-box img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px auto;
            padding: 10px;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .barcode-container img {
            max-width: 100%;
            height: auto;
            display: block !important;
            margin: 15px auto;
            padding: 15px;
            background: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            page-break-inside: avoid;
        }

        .voucher-id {
            font-size: 11px;
            color: #a0aec0;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }

        .price-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .price-label {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .price {
            font-weight: 700;
            font-size: 32px;
            color: #2d3748;
            margin: 0;
        }

        .price-currency {
            font-size: 20px;
            color: #667eea;
            margin-left: 5px;
        }

        .old-price {
            text-decoration: line-through;
            color: #e53e3e;
            font-size: 14px;
            margin-right: 8px;
        }

        .footer-note {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #e2e8f0;
            font-size: 10px;
            color: #a0aec0;
            line-height: 1.6;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            body {
                background: #fff !important;
                padding: 0;
                margin: 0;
            }
            
            .barcode-box {
                box-shadow: none;
                border: 2px solid #000;
                page-break-inside: avoid;
            }
            
            .barcode-container {
                background: #ffffff !important;
                border: 2px solid #000 !important;
                page-break-inside: avoid;
            }
            
            .barcode-container img {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                max-width: 100% !important;
                height: auto !important;
                margin: 15px auto !important;
                padding: 15px !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                page-break-inside: avoid;
            }
            
            img {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
<div class="voucher-wrapper">
    <div class="barcode-box">
        <!-- Header -->
        <div class="voucher-header">
            <h3>LUXE MARCA</h3>
            <div class="voucher-subtitle">Gift Voucher</div>
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <div class="customer-name">{{ $customer->name }}</div>
            <div class="expiry-info">
                <strong>Valid Until:</strong> {{ date('M d, Y', strtotime($voucher->valid_until)) }}
            </div>
        </div>

        <!-- Barcode Section -->
        <div class="barcode-container">
            <div class="barcode-label">Scan Barcode</div>
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($id, 'C128') }}" 
                 alt="Voucher Barcode"
                 style="display: block; max-width: 100%; height: auto; margin: 15px auto; padding: 15px; background: #ffffff;" />
            <div class="voucher-id">Voucher ID: {{ $id }}</div>
        </div>

        <!-- Price Section -->
        <div class="price-section">
            <div class="price-label">Voucher Value</div>
            <div class="price">
                {{ number_format($voucher->amount, 2) }}<span class="price-currency"> Dh</span>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="footer-note">
            This voucher can be redeemed at any LUXE MARCA store. Please present this voucher at the time of purchase.
        </div>
    </div>
</div>

<script>
    window.print();
    window.onafterprint = back;

    function back() {
        window.close();
        window.history.back();
    }
</script>

</body>
</html>
