@php
    $settings = Utility::settings();
    $logo = \App\Models\Utility::get_file('uploads/logo');
    $company_logo = Utility::getValByName('company_logo_dark') ?: Utility::getValByName('company_logo') ?: 'logo-dark.png';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Receipt - {{ $details['pos_id'] ?? 'Receipt' }}</title>
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
            position: relative;
        }
        
        /* Ensure button container is always on top and clickable */
        #printarea > .no-print {
            position: relative !important;
            z-index: 10001 !important;
            pointer-events: auto !important;
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
            font-size: 20px; 
            font-weight: 800; 
            margin: 10px 0;
        }

        .divider { 
            border: 1px dashed #000; 
            margin: 10px 0; 
        }

        .line, .row-line {
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
        }

        .box { 
            border: 1px solid #ccc; 
            padding: 8px; 
            margin: 8px 0; 
            font-size: 12px;
        }

        .section-title { 
            font-size: 16px; 
            font-weight: 700; 
            margin-top: 15px; 
            margin-bottom: 8px;
        }

        .item-name {
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0 4px 0;
        }

        .item-disc {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }

        .subtotal {
            font-weight: bold;
            font-size: 13px;
        }

        .total {
            font-weight: bold;
            font-size: 15px;
        }

        .thank {
            font-size: 13px;
            margin-top: 15px;
            font-weight: 600;
        }

        .no-print {
            display: block;
            position: relative;
            z-index: 9999;
            pointer-events: auto !important;
        }

        .no-print button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer !important;
            font-size: 16px;
            margin: 10px 0;
            position: relative;
            z-index: 10000;
            pointer-events: auto !important;
            user-select: none;
        }

        .no-print button:hover {
            background: #0056b3;
        }
        
        .no-print button:active {
            transform: scale(0.98);
        }
        
        /* Ensure buttons are always clickable */
        #printarea .no-print,
        #printarea .no-print * {
            pointer-events: auto !important;
            z-index: 9999 !important;
        }

        /* ===================== PRINT MODE - EPSON TM-M30 THERMAL PRINTER ===================== */
        @media print {
            /* Epson TM-M30 Printer Settings: 80mm paper width, 72mm printable area (576 dots) */
            @page {
                size: 80mm auto;
                margin: 0 !important;
            }

            /* Reset everything for print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* CRITICAL: Reset html and body - don't restrict width here */
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
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 12px !important;
                line-height: 1.2 !important;
                color: #000 !important;
                font-weight: bold !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                visibility: visible !important;
                display: block !important;
            }

            /* Hide print button only */
            .no-print,
            .no-print * {
                display: none !important;
            }

            /* Print area styling - 72mm printable area - MUST BE VISIBLE */
            #printarea {
                display: block !important;
                visibility: visible !important;
                position: relative !important;
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
                background: white !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
                opacity: 1 !important;
                height: auto !important;
                min-height: 50mm !important;
            }

            /* Ensure printarea is not hidden by any parent */
            body #printarea,
            html body #printarea,
            * #printarea {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Receipt styling for Epson TM-M30 - optimized for 72mm printable area */
            .receipt {
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 2mm 1mm !important;
                background: white !important;
                box-shadow: none !important;
                border: none !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 12px !important;
                line-height: 1.2 !important;
                color: #000 !important;
                display: block !important;
                visibility: visible !important;
                box-sizing: border-box !important;
                word-wrap: break-word !important;
                opacity: 1 !important;
            }

            /* CRITICAL: Make ALL elements visible and black */
            #printarea *,
            .receipt *,
            #printarea div,
            #printarea span,
            #printarea b,
            #printarea strong,
            #printarea h2,
            #printarea h3,
            #printarea p {
                visibility: visible !important;
                opacity: 1 !important;
                color: #000 !important;
                background: transparent !important;
            }

            /* Ensure text content is visible */
            #printarea::before,
            #printarea::after,
            .receipt::before,
            .receipt::after {
                display: none !important;
            }

            /* Proper display types - don't force everything to block */
            .receipt .line,
            .receipt .row-line {
                display: flex !important;
                visibility: visible !important;
            }

            .receipt div:not(.line):not(.row-line) {
                display: block !important;
                visibility: visible !important;
            }

            .receipt span {
                display: inline-block !important;
                visibility: visible !important;
            }

            .receipt b,
            .receipt strong {
                display: inline !important;
                visibility: visible !important;
                font-weight: bold !important;
            }

            /* Company logo */
            .receipt .company-logo {
                width: 100% !important;
                max-width: 60mm !important;
                height: auto !important;
                margin: 0 auto 4px auto !important;
                display: block !important;
                text-align: center !important;
            }
            
            .receipt .company-logo img {
                max-width: 100% !important;
                height: auto !important;
                max-height: 30mm !important;
                object-fit: contain !important;
            }
            
            /* Company title - optimized for TM-M30 */
            .receipt .title {
                font-size: 17px !important;
                margin: 3px 0 !important;
                font-weight: bold !important;
                line-height: 1.2 !important;
                text-align: center !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* All lines and row-lines - optimized for 72mm width */
            .receipt .line,
            .receipt .row-line {
                font-size: 11px !important;
                line-height: 1.2 !important;
                margin: 2px 0 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                word-wrap: break-word !important;
                padding: 0 !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Company info box */
            .receipt .box {
                font-size: 11px !important;
                padding: 4px 2px !important;
                margin: 3px 0 !important;
                border: 1px solid #000 !important;
                line-height: 1.2 !important;
                text-align: center !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            .receipt .box .cmp-name {
                font-size: 12px !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Section titles */
            .receipt .section-title {
                font-size: 12px !important;
                margin: 4px 0 3px 0 !important;
                font-weight: bold !important;
                line-height: 1.2 !important;
                text-align: left !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* Item name */
            .receipt .item-name {
                font-size: 11px !important;
                font-weight: bold !important;
                margin: 4px 0 2px 0 !important;
                line-height: 1.2 !important;
                word-wrap: break-word !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* Item discount label */
            .receipt .item-disc {
                font-size: 10px !important;
                margin: 1px 0 !important;
                line-height: 1.2 !important;
                padding: 0 !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Item combo */
            .receipt .item-combo {
                font-size: 10px !important;
                margin: 2px 0 !important;
                padding: 2px 4px !important;
                line-height: 1.2 !important;
                font-weight: bold !important;
                color: #000 !important;
                border: 1px dashed #000 !important;
                text-align: center !important;
            }

            /* Subtotal */
            .receipt .subtotal {
                font-size: 11px !important;
                font-weight: bold !important;
                margin: 3px 0 !important;
                line-height: 1.2 !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* Total lines */
            .receipt .total {
                font-size: 12px !important;
                font-weight: bold !important;
                margin: 3px 0 !important;
                line-height: 1.2 !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* Return Policy */
            .receipt .return-policy {
                font-size: 10px !important;
                margin-top: 8px !important;
                margin-bottom: 6px !important;
                line-height: 1.3 !important;
                text-align: left !important;
                padding: 4px 0 !important;
                border-top: 1px dashed #000 !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            .receipt .return-policy p {
                margin: 3px 0 !important;
                font-size: 10px !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            /* Footer Info */
            .receipt .footer-info {
                font-size: 10px !important;
                margin-top: 4px !important;
                line-height: 1.2 !important;
                text-align: center !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            /* Thank you message */
            .receipt .thank {
                font-size: 11px !important;
                margin-top: 6px !important;
                line-height: 1.2 !important;
                text-align: center !important;
                padding: 0 !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Dividers - optimized for TM-M30 */
            .receipt .divider {
                border: none !important;
                border-top: 1px dashed #000 !important;
                margin: 4px 0 !important;
                padding: 0 !important;
                height: 0 !important;
                width: 100% !important;
            }

            /* Items container */
            .receipt .item {
                margin: 2px 0 !important;
                padding: 0 !important;
            }

            /* Text alignment */
            .receipt .text-center {
                text-align: center !important;
            }

            /* Spans in row-lines - optimized for TM-M30 72mm width */
            .receipt .line span,
            .receipt .row-line span {
                display: inline-block !important;
                max-width: 50% !important;
                word-wrap: break-word !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            .receipt .row-line span:first-child {
                flex: 0 0 45% !important;
                margin-right: 5px !important;
                text-align: left !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            .receipt .row-line span:last-child {
                flex: 0 0 50% !important;
                text-align: right !important;
                font-weight: bold !important;
                color: #000 !important;
            }
            
            /* Ensure all text elements are bold and black */
            .receipt b,
            .receipt strong,
            .receipt h2,
            .receipt h3 {
                font-weight: bold !important;
                color: #000 !important;
            }
            
            .receipt .text-center {
                font-weight: bold !important;
                color: #000 !important;
            }

            /* Ensure text doesn't overflow - TM-M30 supports ~42 chars per line at 10pt */
            .receipt * {
                max-width: 100% !important;
                overflow-wrap: break-word !important;
            }

            /* CRITICAL: Make ALL fonts bold and black */
            * {
                color: #000 !important;
                font-weight: bold !important;
            }
            
            body {
                color: #000 !important;
                font-weight: bold !important;
            }

            /* CRITICAL: Force single page - Epson TM-M30 continuous paper */
            html {
                height: auto !important;
                min-height: auto !important;
            }

            body {
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
            }

            #printarea {
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            /* Prevent page breaks - keep everything on ONE page */
            .receipt {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                page-break-before: avoid !important;
                break-before: avoid !important;
                orphans: 999 !important;
                widows: 999 !important;
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
            }

            .item,
            .line,
            .row-line,
            .box,
            .section-title,
            .divider {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                page-break-before: avoid !important;
                break-before: avoid !important;
            }

            /* Remove all br spacing */
            br {
                line-height: 1.2 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Epson TM-M30 specific: Ensure proper character spacing with monospace font */
            .receipt {
                letter-spacing: 0 !important;
                word-spacing: normal !important;
            }

            /* Optimize for thermal printing - ensure high contrast */
            .receipt,
            .receipt * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div id="printarea">
        <!-- Print Buttons -->
        <div class="text-center no-print" style="margin-bottom: 15px;">
            <!-- <button id="print" type="button" style="margin-right: 10px; padding: 10px 20px; border-radius: 4px; cursor: pointer; background: #007bff; color: white; border: none;">
                {{ __('Print Receipt (Browser)') }}
            </button> -->
            <button id="printVoucherStyle" type="button" onclick="openPrintView(); return false;" style="margin-right: 10px; padding: 10px 20px; border-radius: 4px; cursor: pointer; background: #6c757d; color: white; border: none;">
                {{ __('Print Receipt (Browser)') }}
            </button>
            <button id="directPrint" type="button" onclick="handleDirectPrintClick(event)" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
                @if(!empty($settings['pos_printer_ip']))
                    {{ __('Print Direct') }} ({{ $settings['pos_printer_ip'] }})
                @else
                    {{ __('Print Direct to Epson TM-M30') }}
                @endif
            </button>
        </div>

        <div class="receipt">
            <!-- Header -->
            <div class="text-center">
                @php
                    // Logo priority: warehouse logo > company_logo_dark > company_logo_light > company_logo > default
                    $logoUrl = '';
                    $logoFilename = '';
                    
                    if (!empty($details['warehouse']['logo'])) {
                        $logoUrl = asset('storage/uploads/warehouse_logo/' . $details['warehouse']['logo']);
                    } elseif (!empty($settings['company_logo_dark'])) {
                        $logoFilename = $settings['company_logo_dark'];
                        $logoUrl = url('/documents/' . $logoFilename);
                    } elseif (!empty($settings['company_logo_light'])) {
                        $logoFilename = $settings['company_logo_light'];
                        $logoUrl = url('/documents/' . $logoFilename);
                    } elseif (!empty($company_logo) && !empty($logo)) {
                        $logoUrl = $logo . '/' . $company_logo;
                    } else {
                        $logoFilename = 'logo-dark.png';
                        $logoUrl = url('/documents/' . $logoFilename);
                    }
                @endphp
                
                @if(!empty($logoUrl))
                    <div class="company-logo">
                        <img src="{{ $logoUrl }}" alt="{{ $details['warehouse']['company_name'] ?? $details['warehouse']['name'] ?? $settings['company_name'] ?? 'Company Logo' }}" style="max-width: 200px; max-height: 100px;" />
                    </div>
                @endif
                
                @if(!empty($details['warehouse']['company_name']))
                    <h2 class="title">{{ $details['warehouse']['company_name'] }}</h2>
                @else
                    <h2 class="title">{{ $settings['company_name'] ?? 'Company Name' }}</h2>
                @endif
                <hr class="divider">
            </div>

            <!-- POS ID -->
            <div class="line">
                <b>{{ $details['pos_id'] ?? '' }}</b>
            </div>

            <!-- Company/Warehouse Info -->
            <div class="box">
                @php
                    $companyInfo = [];
                    if (!empty($details['warehouse']['company_name'])) {
                        $companyInfo[] = '<strong class="cmp-name">' . $details['warehouse']['company_name'] . '</strong><br>';
                    } elseif (!empty($settings['company_name'])) {
                        $companyInfo[] = '<strong class="cmp-name">' . $settings['company_name'] . '</strong><br>';
                    }
                    
                    if (!empty($details['warehouse']['address'])) {
                        $companyInfo[] = $details['warehouse']['address'] . '<br>';
                    } else {
                        if (!empty($settings['company_email'])) {
                            $companyInfo[] = $settings['company_email'] . '<br>';
                        }
                        if (!empty($settings['company_address'])) {
                            $companyInfo[] = $settings['company_address'] . '<br>';
                        }
                        $cityStateZip = trim(($settings['company_city'] ?? '') . ' ' . ($settings['company_state'] ?? '') . ' ' . ($settings['company_zipcode'] ?? ''));
                        if (!empty($cityStateZip)) {
                            $companyInfo[] = $cityStateZip . '<br>';
                        }
                        if (!empty($settings['company_country'])) {
                            $companyInfo[] = $settings['company_country'] . '<br>';
                        }
                    }
                    if (!empty($settings['company_telephone'])) {
                        $companyInfo[] = '<b>Phone:</b> ' . $settings['company_telephone'] . '<br>';
                    }
                @endphp
                {!! implode('', $companyInfo) !!}
            </div>

            <!-- Company Registration Number -->
            @if(!empty($settings['registration_number']))
                <div class="line">
                    <b>{{ __('TRN') }}:</b> {{ $settings['registration_number'] }}
                </div>
            @endif

            <!-- Tax Invoice Title -->
            <div class="text-center" style="margin: 3px 0;">
                <div style="font-size: 13px !important; font-weight: bold !important; color: #000 !important; text-align: center !important;">Tax Invoice</div>
            </div>

            <!-- Customer -->
            @if(!empty($details['customer']['name']))
                <div class="line"><b>Name:</b> {{ $details['customer']['name'] ?? '' }}</div>
                @if(!empty($details['customer']['billing_address']))
                    <div class="line"><b>Address:</b> {{ $details['customer']['billing_address'] }}</div>
                @endif
                @if(!empty($details['customer']['email']))
                    <div class="line"><b>Email:</b> {{ $details['customer']['email'] }}</div>
                @endif
                @if(!empty($details['customer']['billing_phone']))
                    <div class="line"><b>Phone:</b> {{ $details['customer']['billing_phone'] }}</div>
                @endif
            @else
                <div class="line"><b>Customer:</b> Walk-in Customer</div>
            @endif
            <div class="line"><b>Date:</b> {{ $details['date'] ?? '' }}</div>

            @if(!empty($details['warehouse']['name']))
                <div class="line"><b>Warehouse:</b> {{ $details['warehouse']['name'] }}</div>
            @endif

            <h3 class="section-title">Items</h3>

            <!-- Items -->
            @if(!empty($sales['data']))
                @foreach ($sales['data'] as $item)
                    @php
                        $hasCompoText = !empty($item['compo_text']) && 
                                        $item['compo_text'] !== null && 
                                        $item['compo_text'] !== '' &&
                                        trim($item['compo_text']) !== '' &&
                                        trim($item['compo_text']) !== 'null';
                        
                        $hasCompoId = !empty($item['compo_id']) && 
                                     $item['compo_id'] != 0 && 
                                     $item['compo_id'] != '0';
                        
                        $hasCombo = $hasCompoId || (!empty($item['combo_price']) && $item['combo_price'] != null && $item['combo_price'] != 0);
                        
                        // Format numbers to 2 decimal places
                        $itemPrice = $item['price'] ?? 0;
                        $itemPrice = is_string($itemPrice) ? (float)preg_replace('/[^0-9.]/', '', $itemPrice) : (float)$itemPrice;
                        
                        // Get tax rate first
                        $taxRate = $item['tax'] ?? '0%';
                        $taxRate = is_string($taxRate) ? (float)preg_replace('/[^0-9.]/', '', $taxRate) : (float)$taxRate;
                        
                        // Get combo_price - match print-service logic exactly
                        // print-service shows combo_price if: hasCombo && item.combo_price != null && item.combo_price != 0
                        $comboPrice = null;
                        if ($hasCombo && isset($item['combo_price']) && $item['combo_price'] !== null && $item['combo_price'] != 0) {
                            $comboPrice = is_string($item['combo_price']) ? (float)preg_replace('/[^0-9.]/', '', $item['combo_price']) : (float)$item['combo_price'];
                        }
                        
                        // Calculate subtotal before tax and tax amount
                        $itemDiscountPercent = (float)($item['discount'] ?? 0);
                        $itemQuantity = (int)($item['quantity'] ?? 0);
                        
                        if ($comboPrice !== null) {
                            // Use combo_price as base price for calculation
                            $basePrice = $comboPrice;
                        } else {
                            // Use regular price
                            $basePrice = $itemPrice;
                        }
                        
                        // Calculate subtotal before tax: (price - discount) * quantity
                        $itemSubtotalBeforeTax = ($basePrice - ($basePrice * ($itemDiscountPercent / 100))) * $itemQuantity;
                        
                        // Calculate tax amount based on subtotal before tax
                        $itemTaxAmount = $itemSubtotalBeforeTax * ($taxRate / 100);
                        
                        // Calculate subtotal WITH VAT (subtotal = price after discount + tax)
                        // This ensures the subtotal always includes VAT
                        $itemSubtotal = $itemSubtotalBeforeTax + $itemTaxAmount;
                        
                        // Round to 2 decimal places to avoid floating point issues
                        $itemSubtotal = round($itemSubtotal * 100) / 100;
                        $itemTaxAmount = round($itemTaxAmount * 100) / 100;
                    @endphp
                    <div class="item">
                        <div class="item-name"><b>{{ $item['name'] ?? '' }}</b></div>

                        @if($hasCompoText)
                            <div class="item-combo">
                                {{ trim($item['compo_text']) }}
                            </div>
                        @elseif($hasCompoId)
                            <div class="item-combo">
                                COMBO ID: {{ $item['compo_id'] }}
                            </div>
                        @endif

                        @if(!empty($item['discount']) && $item['discount'] != '0' && $item['discount'] != 0)
                            <div class="item-disc">{{ $item['discount'] }}% Discount</div>
                        @endif

                        <div class="row-line"><span>Qty:</span> <span>{{ $item['quantity'] ?? '0' }}</span></div>
                        
                        {{-- Show combo_price if item has combo (same as print-service) --}}
                        @if($comboPrice !== null)
                            <div class="row-line"><span>Price:</span> <span>{{ number_format($comboPrice, 2, '.', '') }}</span></div>
                        @else
                            {{-- Show regular price if no combo --}}
                            <div class="row-line"><span>Price:</span> <span>{{ number_format($itemPrice, 2, '.', '') }}</span></div>
                        @endif
                        
                        @if(!empty($item['discount']) && $item['discount'] != '0' && $item['discount'] != 0)
                            <div class="row-line"><span>Discount:</span> <span>{{ $item['discount'] }}%</span></div>
                        @endif
                        
                        <div class="row-line"><span>Tax:</span> <span>{{ $taxRate }}%</span></div>
                        <div class="row-line"><span>Tax Amount:</span> <span>{{ number_format($itemTaxAmount, 2, '.', '') }}</span></div>

                        <div class="row-line subtotal">
                            <span>Subtotal:</span> <span>{{ number_format($itemSubtotal, 2, '.', '') }}</span>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Vouchers -->
            @php
                $vouchersArray = [];
                
                // Debug: Log vouchers data
                \Log::info('PrintView: Processing vouchers', [
                    'vouchers_type' => gettype($vouchers),
                    'vouchers_empty' => empty($vouchers),
                    'vouchers_is_array' => is_array($vouchers),
                    'vouchers_count' => is_array($vouchers) ? count($vouchers) : 0,
                    'vouchers_data' => $vouchers
                ]);
                
                if (!empty($vouchers) && is_array($vouchers) && count($vouchers) > 0) {
                    // Check if it's an array of arrays (indexed array) or associative array
                    if (isset($vouchers[0]) && is_array($vouchers[0])) {
                        // Already in correct format: [['id' => 1, 'amount' => 50], ...]
                        $vouchersArray = $vouchers;
                    } else {
                        // Convert associative array to indexed array
                        // Format: [voucher_id => ['id' => voucher_id, 'amount' => amount], ...]
                        foreach ($vouchers as $key => $value) {
                            if (is_array($value)) {
                                // Use the 'id' from the value array if available, otherwise use key
                                $voucherId = $value['id'] ?? $key;
                                $amount = $value['amount'] ?? (is_numeric($value) ? $value : 0);
                                $vouchersArray[] = ['id' => $voucherId, 'amount' => (float)$amount];
                            } else {
                                // Direct value (amount only)
                                $vouchersArray[] = ['id' => $key, 'amount' => (float)$value];
                            }
                        }
                    }
                } elseif (is_object($vouchers) && count((array)$vouchers) > 0) {
                    // Convert object to array
                    foreach ((array)$vouchers as $key => $value) {
                        if (is_array($value)) {
                            $voucherId = $value['id'] ?? $key;
                            $amount = $value['amount'] ?? (is_numeric($value) ? $value : 0);
                            $vouchersArray[] = ['id' => $voucherId, 'amount' => (float)$amount];
                        } else {
                            $vouchersArray[] = ['id' => $key, 'amount' => (float)$value];
                        }
                    }
                }
                
                \Log::info('PrintView: Final vouchers array', [
                    'vouchersArray_count' => count($vouchersArray),
                    'vouchersArray' => $vouchersArray
                ]);
            @endphp
            
            @if(count($vouchersArray) > 0)
                <h3 class="section-title">Vouchers</h3>
                @foreach ($vouchersArray as $voucher)
                    @php
                        $voucherId = $voucher['id'] ?? $voucher['voucher_id'] ?? 'N/A';
                        $amount = $voucher['amount'] ?? $voucher ?? 0;
                        $amountNum = (float)$amount;
                    @endphp
                    <div class="row-line">
                        <span>Voucher ID {{ $voucherId }}:</span> <span>{{ number_format($amountNum, 2, '.', '') }}</span>
                    </div>
                @endforeach
            @endif

            <!-- Totals -->
            <hr class="divider">

            @php
                // Format totals to 2 decimal places (keep exact decimal amount, no rounding to whole number)
                $subTotal = $sales['sub_total'] ?? 0;
                $subTotal = is_string($subTotal) ? (float)preg_replace('/[^0-9.]/', '', $subTotal) : (float)$subTotal;
                
                $taxAmount = $sales['tax_amount'] ?? 0;
                $taxAmount = is_string($taxAmount) ? (float)preg_replace('/[^0-9.]/', '', $taxAmount) : (float)$taxAmount;
                
                $discount = $sales['discount'] ?? 0;
                $discount = is_string($discount) ? (float)preg_replace('/[^0-9.]/', '', $discount) : (float)$discount;
                
                // Prefer raw numeric total_number from backend when available, otherwise fall back to formatted total
                $rawTotal = $sales['total_number'] ?? ($sales['total'] ?? 0);
                $rawTotal = is_string($rawTotal) ? (float)preg_replace('/[^0-9.]/', '', $rawTotal) : (float)$rawTotal;
                
                $taxRate = $sales['tax_rate'] ?? 0;
                $taxRate = is_string($taxRate) ? (float)preg_replace('/[^0-9.]/', '', $taxRate) : (float)$taxRate;
            @endphp

            @if($subTotal > 0)
                <div class="row-line"><span>Sub Total:</span> <span>{{ number_format($subTotal, 2, '.', '') }}</span></div>
            @endif

            @if($taxAmount > 0 && $taxAmount != '0.00' && $taxAmount != '0')
                <div class="row-line"><span>Tax ({{ $taxRate }}%):</span> <span>{{ number_format($taxAmount, 2, '.', '') }}</span></div>
            @endif

            @if($discount > 0 && $discount != '0.00' && $discount != '0')
                <div class="row-line total"><span>Discount:</span> <span>{{ number_format($discount, 2, '.', '') }}</span></div>
            @endif

            @php
                // Sum all voucher amounts
                $totalVouchersAmount = 0;
                if (count($vouchersArray) > 0) {
                    foreach ($vouchersArray as $voucher) {
                        $amount = $voucher['amount'] ?? $voucher ?? 0;
                        $totalVouchersAmount += (float)$amount;
                    }
                }
                // Use calculated amount if vouchers_amount is not available
                if ($totalVouchersAmount > 0 && (empty($vouchers_amount) || $vouchers_amount == 0)) {
                    $vouchers_amount = $totalVouchersAmount;
                }
                $vouchers_amount = (float)($vouchers_amount ?? 0);
                
                // Recalculate total using the same logic as the POS screen, but without rounding to whole numbers:
                // total = subtotal + tax - discount - vouchers
                $calculatedTotal = $subTotal + $taxAmount - $discount - $vouchers_amount;
                if ($calculatedTotal < 0) {
                    $calculatedTotal = 0;
                }
                // Normalize to 2 decimal places (no rounding to integer)
                $total = round($calculatedTotal * 100) / 100;
            @endphp

            @if(!empty($vouchers_amount) && $vouchers_amount > 0)
                <div class="row-line total"><span>Vouchers:</span> <span>{{ number_format((float)$vouchers_amount, 2, '.', '') }}</span></div>
            @endif

            <div class="row-line total"><span>Total:</span> <span>{{ number_format($total, 2, '.', '') }}</span></div>
            
            @php
                // Calculate total payment from all payment methods (matching print-service logic)
                $totalPaymentAmount = 0;
                if (!empty($paymentMethods) && is_array($paymentMethods) && count($paymentMethods) > 0) {
                    foreach ($paymentMethods as $pm) {
                        $amount = (float)($pm['amount'] ?? 0);
                        if ($amount > 0) {
                            $totalPaymentAmount += $amount;
                        }
                    }
                }
                // Round to 2 decimal places
                $totalPaymentAmount = round($totalPaymentAmount * 100) / 100;
            @endphp
            
            @if($totalPaymentAmount > 0)
                <div class="row-line total"><span>Customer Pay:</span> <span>{{ number_format($totalPaymentAmount, 2, '.', '') }}</span></div>
            @endif

            <!-- Payment Methods -->
            @php
                // Debug: Log payment methods for troubleshooting
                \Log::info('PrintView: Payment Methods Debug', [
                    'paymentMethods_exists' => isset($paymentMethods),
                    'paymentMethods_type' => isset($paymentMethods) ? gettype($paymentMethods) : 'not set',
                    'paymentMethods_empty' => isset($paymentMethods) ? empty($paymentMethods) : 'not set',
                    'paymentMethods_is_array' => isset($paymentMethods) ? is_array($paymentMethods) : 'not set',
                    'paymentMethods_count' => isset($paymentMethods) && is_array($paymentMethods) ? count($paymentMethods) : 0,
                    'paymentMethods_data' => $paymentMethods ?? null
                ]);
                
                // Try to get payment methods from request if not available
                if (empty($paymentMethods) || !is_array($paymentMethods) || count($paymentMethods) == 0) {
                    // Try to get from request
                    if (request()->has('payment_methods') && is_array(request('payment_methods'))) {
                        $paymentMethods = request('payment_methods');
                    }
                    // Try to get from request amounts array (from POS form)
                    elseif (request()->has('amounts') && is_array(request('amounts'))) {
                        $paymentMethods = [];
                        $amounts = request('amounts');
                        foreach ($amounts as $methodId => $amount) {
                            if ((float)$amount > 0) {
                                $paymentMethod = \App\Models\PaymentMethod::find($methodId);
                                if ($paymentMethod) {
                                    $paymentMethods[] = [
                                        'id' => $paymentMethod->id,
                                        'name' => $paymentMethod->name,
                                        'amount' => (float)$amount
                                    ];
                                } else {
                                    // Cash payment (no payment method ID)
                                    $paymentMethods[] = [
                                        'id' => null,
                                        'name' => 'Cash',
                                        'amount' => (float)$amount
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Filter valid payment methods
                $validPaymentMethods = [];
                if (!empty($paymentMethods) && is_array($paymentMethods) && count($paymentMethods) > 0) {
                    $validPaymentMethods = array_filter($paymentMethods, function($pm) {
                        $amount = (float)($pm['amount'] ?? 0);
                        return $amount > 0;
                    });
                }
            @endphp
            
            @if(count($validPaymentMethods) > 0)
                <hr class="divider">
                <h3 class="section-title">Payment Methods</h3>
                @foreach ($validPaymentMethods as $pm)
                    @php
                        $methodName = $pm['name'] ?? 'Payment';
                        $methodAmount = (float)($pm['amount'] ?? 0);
                    @endphp
                    <div class="row-line"><span>Customer Paid via {{ $methodName }}:</span> <span>{{ number_format($methodAmount, 2, '.', '') }}</span></div>
                @endforeach
            @endif

            <!-- Return Policy -->
            <div class="return-policy">
                <p>Items must be returned within 14 days for exchange or store credit, and they must be in their original condition with the receipt. Promotional products cannot be exchanged or returned.</p>
                <p>يجب إرجاع العناصر خلال 14 يومًا للتبديل أو الحصول على رصيد المتجر، ويجب أن تكون بحالتها الأصلية مع الفاتورة. لا يمكن استبدال أو إرجاع المنتجات الترويجية.</p>
            </div>

            <!-- Footer Info -->
            <div class="footer-info">
                @php
                    $footerHtml = '';
                    $warehouse = $details['warehouse'] ?? [];
                    
                    // Prioritize warehouse address, city, and country if available
                    if (!empty($warehouse['address']) || !empty($warehouse['city']) || !empty($warehouse['country'])) {
                        // Address - no comma before first item
                        if (!empty($warehouse['address'])) {
                            $footerHtml .= '<span>' . $warehouse['address'] . '</span>';
                        }
                        
                        // City - comma before
                        if (!empty($warehouse['city'])) {
                            $footerHtml .= '<span>, ' . $warehouse['city'] . '</span>';
                        }
                        
                        // Zipcode - " - " separator
                        if (!empty($warehouse['city_zip'])) {
                            $footerHtml .= '<span> - ' . $warehouse['city_zip'] . '</span>';
                        }
                        
                        // Country - comma before
                        if (!empty($warehouse['country'])) {
                            $footerHtml .= '<span>, ' . $warehouse['country'] . '</span>';
                        }
                    } else {
                        // Fallback to system company settings
                        // Email (mail_from_address or company_email) - no comma before first item
                        if (!empty($settings['company_email']) || !empty($settings['mail_from_address'])) {
                            $footerHtml .= '<span>' . ($settings['company_email'] ?? $settings['mail_from_address']) . '</span>';
                        }
                        
                        // Address - comma before
                        if (!empty($settings['company_address'])) {
                            $footerHtml .= '<span>, ' . $settings['company_address'] . '</span>';
                        }
                        
                        // City - comma before
                        if (!empty($settings['company_city'])) {
                            $footerHtml .= '<span>, ' . $settings['company_city'] . '</span>';
                        }
                        
                        // State - comma before
                        if (!empty($settings['company_state'])) {
                            $footerHtml .= '<span>, ' . $settings['company_state'] . '</span>';
                        }
                        
                        // Zipcode - " - " separator
                        if (!empty($settings['company_zipcode'])) {
                            $footerHtml .= '<span> - ' . $settings['company_zipcode'] . '</span>';
                        }
                        
                        // Country - comma before
                        if (!empty($settings['company_country'])) {
                            $footerHtml .= '<span>, ' . $settings['company_country'] . '</span>';
                        }
                    }
                    
                    // Telephone - comma before (always from settings)
                    if (!empty($settings['company_telephone'])) {
                        $footerHtml .= '<span>, ' . $settings['company_telephone'] . '</span>';
                    }
                @endphp
                {!! $footerHtml !!}
            </div>

            <div class="thank text-center">
                Thank You For Shopping With Us.
            </div>
        </div>
    </div>

    @php
        $receiptId = $details['pos_id'] ?? 'Receipt';
        // Get printer settings automatically
        $printerIp = $settings['pos_printer_ip'] ?? '10.255.254.17';
        $printerPort = $settings['pos_printer_port'] ?? '9100';
        
        // Use original session data passed from controller (before formatting)
        // This ensures the controller receives data in the expected format with raw numeric values
        $salesDataForPrint = $originalSessionData ?? session()->get('pos', []);
        
        // If still empty, try to reconstruct from $sales['data'] by extracting numeric values
        // This handles edge cases where session was cleared
        if (empty($salesDataForPrint) && !empty($sales['data'])) {
            foreach ($sales['data'] as $key => $item) {
                // Extract numeric price from formatted string (e.g., "46.67Dhs" -> 46.67)
                $priceRaw = $item['price'] ?? '0.00';
                if (is_string($priceRaw)) {
                    $priceRaw = preg_replace('/[^0-9.]/', '', $priceRaw);
                }
                $priceRaw = (float)$priceRaw;
                
                // Extract numeric subtotal from formatted string
                $subtotalRaw = $item['subtotal'] ?? '0.00';
                if (is_string($subtotalRaw)) {
                    $subtotalRaw = preg_replace('/[^0-9.]/', '', $subtotalRaw);
                }
                $subtotalRaw = (float)$subtotalRaw;
                
                // Reconstruct original format expected by controller
                $salesDataForPrint[$key] = [
                    'name' => $item['name'] ?? '',
                    'quantity' => (int)($item['quantity'] ?? 0),
                    'price' => $priceRaw,
                    'subtotal' => $subtotalRaw,
                    'discount' => (float)($item['discount'] ?? 0),
                    'compo_text' => $item['compo_text'] ?? null,
                    'compo_id' => 0, // Default if not available
                ];
            }
        }
        
        // Prepare data to pass to JavaScript (since session might be cleared)
        $printData = [
            'vc_name' => $details['customer']['id'] ?? request('vc_name') ?? '',
            'warehouse_name' => $details['warehouse']['id'] ?? request('warehouse_name') ?? '',
            'discount' => request('discount', 0),
            'payments' => $customer_pay ?? request('payments', 0),
            'sales_data' => $salesDataForPrint, // Use original format with raw numeric values
            'vouchers' => $vouchers ?? [],
            'tax_id' => session('tax_id') ?? request('tax_id'),
            'customer_return' => $customer_return ?? 0,
            'payment_methods' => $paymentMethods ?? [], // Include payment methods
        ];
    @endphp
    <script>
        // CRITICAL: Prevent auto-print IMMEDIATELY - before anything else runs
        (function() {
            var printPrevented = true;
            var originalPrint = window.print.bind(window);
            var pageLoadTime = Date.now();
            
            // Override window.print to prevent auto-print
            // BUT: Allow direct calls from buttons (like vouchers do)
            window.print = function() {
                // If print is explicitly allowed (via button click), allow it immediately
                if (!printPrevented) {
                    console.log('Print allowed - calling original print');
                    return originalPrint();
                }
                
                // Check if this is a user-initiated print (button click) vs auto-print
                // User clicks have a very short time between events, auto-print happens on load
                var timeSinceLoad = Date.now() - pageLoadTime;
                
                // If more than 2 seconds have passed, allow print (user clicked button)
                if (timeSinceLoad > 2000) {
                    console.log('Print allowed (user action) - calling original print');
                    return originalPrint();
                }
                
                // Prevent auto-print within first 2 seconds
                console.log('Auto-print prevented (page just loaded). Use the print button to print.');
                return false;
            };
            
            // Store original print function globally so button can use it
            window._originalPrint = originalPrint;
            window._printPrevented = function() { return printPrevented; };
            window._setPrintPrevented = function(val) { printPrevented = val; };
        })();
        
        // Debug: Log that script is loaded
        console.log('Print view script loaded');
        
        // Immediately ensure buttons are clickable (before DOMContentLoaded)
        (function() {
            function ensureButtonsClickable() {
                var printBtn = document.getElementById('print');
                var voucherBtn = document.getElementById('printVoucherStyle');
                
                if (printBtn) {
                    printBtn.style.pointerEvents = 'auto';
                    printBtn.style.cursor = 'pointer';
                    printBtn.style.zIndex = '10000';
                    printBtn.disabled = false;
                }
                
                if (voucherBtn) {
                    voucherBtn.style.pointerEvents = 'auto';
                    voucherBtn.style.cursor = 'pointer';
                    voucherBtn.style.zIndex = '10000';
                    voucherBtn.disabled = false;
                }
            }
            
            // Try immediately
            ensureButtonsClickable();
            
            // Try on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ensureButtonsClickable);
            } else {
                ensureButtonsClickable();
            }
            
            // Try after a short delay
            setTimeout(ensureButtonsClickable, 100);
        })();
        
        // Global print function for button onclick (like vouchers) - Define EARLY so it's available
        // Make it explicitly global by assigning to window
        window.printReceipt = function() {
            console.log('printReceipt() called');
            
            // Allow print - disable prevention
            if (window._setPrintPrevented) {
                window._setPrintPrevented(false);
                console.log('Print prevention disabled');
            }
            
            // Force visibility
            if (typeof forceVisibility === 'function') {
                forceVisibility();
                console.log('Force visibility called');
            } else {
                console.warn('forceVisibility function not found');
            }
            
            window.document.title = 'POS Receipt - {{ $receiptId }}';
            
            // Simple direct print like vouchers - use original print to bypass prevention
            setTimeout(function() {
                if (typeof forceVisibility === 'function') {
                    forceVisibility();
                }
                console.log('printReceipt: Calling print...');
                // Always use _originalPrint to bypass the prevention mechanism
                if (window._originalPrint) {
                    console.log('printReceipt: Using _originalPrint to bypass prevention');
                    try {
                        window._originalPrint();
                    } catch(e) {
                        console.error('Error calling _originalPrint:', e);
                        // Fallback: ensure prevention is disabled and try window.print
                        if (window._setPrintPrevented) {
                            window._setPrintPrevented(false);
                        }
                        window.print();
                    }
                } else {
                    console.log('printReceipt: _originalPrint not found, trying direct print');
                    // Ensure prevention is disabled
                    if (window._setPrintPrevented) {
                        window._setPrintPrevented(false);
                    }
                    // Fallback: try to call print directly
                    try {
                        window.print();
                    } catch(e) {
                        console.error('Print failed:', e);
                    }
                }
            }, 100);
            
            return false;
        };
        
        // Also make it available without window prefix for inline handlers
        var printReceipt = window.printReceipt;
        
        console.log('printReceipt function defined:', typeof window.printReceipt);
        
        // Print function that works exactly like vouchers - simple and direct
        // This function prints directly without going through any service
        // Uses the same simple approach as vouchers: bypass prevention and print directly
        window.printLikeVoucher = function() {
            console.log('printLikeVoucher called - printing directly like vouchers');
            
            // Use original print function to bypass prevention (like vouchers do)
            // Vouchers don't have prevention, so they just call window.print() directly
            // We need to use _originalPrint to bypass our prevention mechanism
            if (window._originalPrint) {
                window._originalPrint();
            } else {
                // Fallback: disable prevention and print
                if (window._setPrintPrevented) {
                    window._setPrintPrevented(false);
                }
                window.print();
            }
            
            return false;
        };
        
        // Make it available globally
        var printLikeVoucher = window.printLikeVoucher;
        
        console.log('printLikeVoucher function defined:', typeof window.printLikeVoucher);
        
        // Function to open printview in new window (like vouchers do)
        window.openPrintView = function() {
            console.log('openPrintView called - opening printview in new window');
            
            // Get print data from PHP
            var printData = @json($printData ?? []);
            
            // Get pos_id from details (available on current page) - use JSON encoding for safety
            // Fallback to URL parameter if not in details (in case page was opened directly)
            var posId = @json($details['pos_id'] ?? '');
            if (!posId) {
                // Try to get from URL parameters
                var urlParams = new URLSearchParams(window.location.search);
                posId = urlParams.get('pos_id') || '';
            }
            
            // Construct URL with necessary parameters
            var printUrl = '{{ route("pos.printview") }}';
            var params = new URLSearchParams();
            
            // CRITICAL: Pass pos_id so printview can load data from database if session is cleared
            if (posId) {
                params.append('pos_id', posId);
            }
            
            if (printData.vc_name) {
                params.append('vc_name', printData.vc_name);
            }
            if (printData.warehouse_name) {
                params.append('warehouse_name', printData.warehouse_name);
            }
            if (printData.payments) {
                params.append('payments', printData.payments);
            }
            if (printData.discount) {
                params.append('discount', printData.discount);
            }
            if (printData.tax_id) {
                params.append('tax_id', printData.tax_id);
            }
            
            // Build full URL
            var fullUrl = printUrl + '?' + params.toString();
            
            console.log('Opening printview URL:', fullUrl);
            console.log('POS ID being passed:', posId);
            
            // Open in new window - it will auto-print on load (like vouchers)
            window.open(fullUrl, '_blank');
            
            return false;
        };
        
        // Printer configuration (automatically detected from settings)
        var printerConfig = {
            ip: '{{ $printerIp }}',
            port: '{{ $printerPort }}',
            configured: '{{ !empty($settings['pos_printer_ip']) ? 'true' : 'false' }}'
        };
        
        // Receipt data (passed from PHP to avoid session issues)
        var receiptData = @json($printData);
        
        console.log('Printer config initialized:', printerConfig);
        console.log('Receipt data available:', receiptData);

        // Force visibility on all elements before print - AGGRESSIVE
        function forceVisibility() {
            var printArea = document.getElementById('printarea');
            if (printArea) {
                // Force printarea visibility
                printArea.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important;';
                
                // Force visibility on all children
                var allElements = printArea.querySelectorAll('*');
                for (var i = 0; i < allElements.length; i++) {
                    var el = allElements[i];
                    el.style.visibility = 'visible';
                    el.style.opacity = '1';
                    el.style.color = '#000';
                    el.style.display = '';
                    
                    // Remove any inline styles that might hide content
                    if (el.style.display === 'none') {
                        el.style.display = '';
                    }
                    if (el.style.visibility === 'hidden') {
                        el.style.visibility = 'visible';
                    }
                    if (el.style.opacity === '0') {
                        el.style.opacity = '1';
                    }
                }
                
                // Force receipt visibility
                var receipt = printArea.querySelector('.receipt');
                if (receipt) {
                    receipt.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
                }
            }
        }

        // Global function to handle direct print click (called from onclick)
        function handleDirectPrintClick(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            console.log('Direct print button clicked!');
            console.log('Printer config:', printerConfig);
            
            // Check if printer is configured
            if (!printerConfig.configured || !printerConfig.ip || printerConfig.ip === '') {
                alert('Printer not configured. Please set pos_printer_ip in system settings.');
                return false;
            }
            
            var directPrintBtn = document.getElementById('directPrint');
            if (!directPrintBtn) {
                console.error('Direct print button not found!');
                alert('Print button not found. Please refresh the page.');
                return false;
            }
            
            // Disable button and show loading
            directPrintBtn.disabled = true;
            directPrintBtn.textContent = 'Printing...';
            
            // Validate data before sending
            if (!receiptData || !receiptData.sales_data) {
                alert('No items found to print. Please ensure the receipt has items.');
                directPrintBtn.disabled = false;
                directPrintBtn.textContent = printerConfig.configured && printerConfig.ip ? 
                    '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                    '{{ __("Print Direct to Epson TM-M30") }}';
                return false;
            }
            
            // Convert sales_data to array if it's an object (JSON encoding can convert arrays to objects)
            var salesDataArray = [];
            if (Array.isArray(receiptData.sales_data)) {
                salesDataArray = receiptData.sales_data;
            } else if (typeof receiptData.sales_data === 'object' && receiptData.sales_data !== null) {
                // Convert object to array (preserve keys as they might be product IDs)
                salesDataArray = Object.keys(receiptData.sales_data).map(function(key) {
                    var item = receiptData.sales_data[key];
                    // Ensure item has required fields
                    return {
                        name: item.name || '',
                        quantity: parseInt(item.quantity) || 0,
                        price: parseFloat(item.price) || 0,
                        subtotal: parseFloat(item.subtotal) || 0,
                        discount: parseFloat(item.discount) || 0,
                        compo_text: item.compo_text || null,
                        compo_id: item.compo_id || 0
                    };
                });
            }
            
            if (salesDataArray.length === 0) {
                alert('No items found to print. Please ensure the receipt has items.');
                directPrintBtn.disabled = false;
                directPrintBtn.textContent = printerConfig.configured && printerConfig.ip ? 
                    '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                    '{{ __("Print Direct to Epson TM-M30") }}';
                return false;
            }
            
            // Prepare form data with auto-detected printer settings
            // Use receiptData from PHP (passed directly) instead of session
            var formData = {
                vc_name: receiptData.vc_name || '',
                warehouse_name: receiptData.warehouse_name || '',
                discount: receiptData.discount || 0,
                payments: receiptData.payments || 0,
                printer_ip: printerConfig.ip,  // Auto-detected from settings
                printer_port: printerConfig.port,  // Auto-detected from settings
                sales_data: salesDataArray,  // Use converted array
                vouchers: receiptData.vouchers || [],  // Pass vouchers directly
                tax_id: receiptData.tax_id || null,  // Pass tax_id directly
                customer_return: receiptData.customer_return || 0,  // Pass customer return
                payment_methods: receiptData.payment_methods || []  // Pass payment methods
            };
            
            // Validate route URL
            var routeUrl = '{{ route("pos.printview.direct") }}';
            if (!routeUrl || routeUrl.includes('pos.printview.direct')) {
                console.error('Route URL is invalid:', routeUrl);
                alert('Error: Print route is not configured correctly. Route URL: ' + routeUrl);
                directPrintBtn.disabled = false;
                directPrintBtn.textContent = printerConfig.configured && printerConfig.ip ? 
                    '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                    '{{ __("Print Direct to Epson TM-M30") }}';
                return false;
            }
            
            // Validate CSRF token
            var csrfToken = '{{ csrf_token() }}';
            if (!csrfToken || csrfToken.length < 10) {
                console.error('CSRF token is invalid:', csrfToken);
                alert('Error: CSRF token is missing or invalid. Please refresh the page.');
                directPrintBtn.disabled = false;
                directPrintBtn.textContent = printerConfig.configured && printerConfig.ip ? 
                    '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                    '{{ __("Print Direct to Epson TM-M30") }}';
                return false;
            }
            
            // Log the request
            console.log('=== PRINT REQUEST DEBUG ===');
            console.log('Route URL:', routeUrl);
            console.log('CSRF Token (first 10 chars):', csrfToken.substring(0, 10) + '...');
            console.log('Printer config:', printerConfig);
            console.log('Form data:', formData);
            console.log('Sales data count:', formData.sales_data.length);
            if (formData.sales_data.length > 0) {
                console.log('Sales data sample (first item):', formData.sales_data[0]);
            } else {
                console.error('WARNING: Sales data array is empty!');
            }
            console.log('Vouchers:', formData.vouchers);
            console.log('Tax ID:', formData.tax_id);
            console.log('Customer return:', formData.customer_return);
            
            // Send AJAX request
            var fetchPromise = fetch(routeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData),
                credentials: 'same-origin' // Include cookies for session
            });
            
            // Add timeout to fetch (10 seconds)
            var timeoutPromise = new Promise(function(resolve, reject) {
                setTimeout(function() {
                    reject(new Error('Request timeout: Server did not respond within 10 seconds'));
                }, 10000);
            });
            
            Promise.race([fetchPromise, timeoutPromise])
            .then(async response => {
                console.log('Response received');
                console.log('Response status:', response.status);
                console.log('Response statusText:', response.statusText);
                
                // Get response text first to check if it's JSON
                const responseText = await response.text();
                console.log('Response text length:', responseText.length);
                console.log('Response text (first 500 chars):', responseText.substring(0, 500));
                
                // Check if response is ok
                if (!response.ok) {
                    let errorMessage = 'HTTP Error: ' + response.status + ' ' + response.statusText;
                    let errorData = {};
                    
                    // Try to parse as JSON
                    try {
                        if (responseText.trim()) {
                            errorData = JSON.parse(responseText);
                            errorMessage = errorData.message || errorData.error || errorMessage;
                            
                            // Handle Laravel validation errors
                            if (errorData.errors && typeof errorData.errors === 'object') {
                                var validationErrors = [];
                                for (var field in errorData.errors) {
                                    if (Array.isArray(errorData.errors[field])) {
                                        validationErrors.push(field + ': ' + errorData.errors[field].join(', '));
                                    } else {
                                        validationErrors.push(field + ': ' + errorData.errors[field]);
                                    }
                                }
                                if (validationErrors.length > 0) {
                                    errorMessage = 'Validation Error:\n' + validationErrors.join('\n');
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Failed to parse error response as JSON:', e);
                        // If not JSON, use the response text (might be HTML error page)
                        if (responseText.trim()) {
                            errorMessage = responseText.length > 200 ? 
                                responseText.substring(0, 200) + '...' : 
                                responseText;
                        }
                    }
                    
                    // Handle specific error codes
                    if (response.status === 419) {
                        errorMessage = 'CSRF token mismatch (419). Please refresh the page and try again.\n\nThis usually happens when:\n- Your session expired\n- You opened the page in a new tab\n- The CSRF token is invalid';
                    } else if (response.status === 401) {
                        errorMessage = 'Authentication required (401). Please log in again.';
                    } else if (response.status === 403) {
                        errorMessage = 'Access forbidden (403). You may not have permission to print.';
                    } else if (response.status === 404) {
                        errorMessage = 'Route not found (404). The print endpoint may not be configured correctly.';
                    } else if (response.status === 422) {
                        errorMessage = 'Validation error (422): ' + (errorData.message || errorMessage);
                    } else if (response.status === 500) {
                        errorMessage = 'Server error (500). Please check Laravel logs: storage/logs/laravel.log\n\nError: ' + (errorData.message || errorMessage);
                    } else if (response.status === 0) {
                        errorMessage = 'Network error: Could not connect to server. Check:\n- Server is running\n- Network connection\n- CORS settings';
                    }
                    
                    const error = new Error(errorMessage);
                    error.status = response.status;
                    error.data = errorData;
                    error.responseText = responseText;
                    throw error;
                }
                
                // Parse JSON response
                try {
                    if (!responseText.trim()) {
                        throw new Error('Empty response from server');
                    }
                    var jsonData = JSON.parse(responseText);
                    console.log('Parsed JSON response:', jsonData);
                    return jsonData;
                } catch (e) {
                    console.error('Failed to parse response as JSON:', e);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response from server. Response: ' + responseText.substring(0, 200));
                }
            })
            .then(data => {
                console.log('Print response:', data);
                
                if (data.success) {
                    // Success - show brief notification
                    directPrintBtn.textContent = '✓ Queued!';
                    directPrintBtn.style.background = '#28a745';
                    
                    // Show info message about queue system
                    var queueInfo = data.queue_info || 'Print job has been queued. The local print service will process it automatically.';
                    if (data.job_id) {
                        queueInfo += '\n\nJob ID: ' + data.job_id;
                    }
                    
                    // Use a more user-friendly notification
                    if (typeof show_toastr !== 'undefined') {
                        show_toastr('success', data.message || 'Print job queued successfully!');
                    } else {
                        alert(data.message + '\n\n' + queueInfo);
                    }
                    
                    setTimeout(function() {
                        // Restore button text with printer IP
                        var btnText = printerConfig.configured && printerConfig.ip ? 
                            '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                            '{{ __("Print Direct to Epson TM-M30") }}';
                        directPrintBtn.textContent = btnText;
                        directPrintBtn.style.background = '#28a745';
                    }, 3000);
                } else {
                    console.error('Print failed:', data);
                    var errorMsg = 'Print failed: ' + (data.message || 'Unknown error');
                    if (data.error_details) {
                        errorMsg += '\n\nDetails: ' + JSON.stringify(data.error_details);
                    }
                    
                    if (typeof show_toastr !== 'undefined') {
                        show_toastr('error', errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Restore button text
                    var btnText = printerConfig.configured && printerConfig.ip ? 
                        '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                        '{{ __("Print Direct to Epson TM-M30") }}';
                    directPrintBtn.textContent = btnText;
                }
            })
            .catch(error => {
                console.error('=== PRINT REQUEST ERROR ===');
                console.error('Error name:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                console.error('Error status:', error.status);
                console.error('Error data:', error.data);
                console.error('Response text:', error.responseText);
                console.error('Request URL:', '{{ route("pos.printview.direct") }}');
                console.error('Request data:', formData);
                console.error('CSRF Token:', '{{ csrf_token() }}');
                
                // Build detailed error message
                var errorMsg = 'Print Request Failed\n\n';
                
                // Check error type
                if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    errorMsg += 'Network Error: Could not connect to server.\n\n';
                    errorMsg += 'Possible causes:\n';
                    errorMsg += '- Server is not running\n';
                    errorMsg += '- Network connection issue\n';
                    errorMsg += '- CORS policy blocking the request\n';
                    errorMsg += '- Firewall blocking the connection\n\n';
                    errorMsg += 'URL: {{ route("pos.printview.direct") }}\n';
                } else if (error.message && error.message.includes('timeout')) {
                    errorMsg += 'Request Timeout: Server did not respond in time.\n\n';
                    errorMsg += 'The server may be:\n';
                    errorMsg += '- Overloaded\n';
                    errorMsg += '- Processing a large request\n';
                    errorMsg += '- Experiencing database issues\n';
                } else {
                    errorMsg += 'Error: ' + (error.message || 'Unknown error') + '\n\n';
                }
                
                if (error.status) {
                    errorMsg += 'HTTP Status: ' + error.status + '\n';
                }
                
                if (error.responseText) {
                    errorMsg += '\nServer Response:\n';
                    if (error.responseText.length > 300) {
                        errorMsg += error.responseText.substring(0, 300) + '...\n';
                    } else {
                        errorMsg += error.responseText + '\n';
                    }
                }
                
                if (error.data) {
                    if (error.data.errors && typeof error.data.errors === 'object') {
                        errorMsg += '\nValidation Errors:\n';
                        for (var field in error.data.errors) {
                            if (Array.isArray(error.data.errors[field])) {
                                errorMsg += '  - ' + field + ': ' + error.data.errors[field].join(', ') + '\n';
                            } else {
                                errorMsg += '  - ' + field + ': ' + error.data.errors[field] + '\n';
                            }
                        }
                    }
                    if (error.data.error_details) {
                        errorMsg += '\nError Details: ' + JSON.stringify(error.data.error_details) + '\n';
                    }
                    if (error.data.message && error.data.message !== error.message) {
                        errorMsg += '\nServer Message: ' + error.data.message + '\n';
                    }
                }
                
                errorMsg += '\n=== Troubleshooting Steps ===\n';
                errorMsg += '1. Open browser console (F12) and check for detailed errors\n';
                errorMsg += '2. Check Laravel logs: storage/logs/laravel.log\n';
                errorMsg += '3. Verify printer IP: ' + printerConfig.ip + '\n';
                errorMsg += '4. Check if route exists: {{ route("pos.printview.direct") }}\n';
                errorMsg += '5. Verify you are logged in (session may have expired)\n';
                errorMsg += '6. Try refreshing the page (Ctrl+F5) and logging in again\n';
                errorMsg += '7. Check if print_jobs table exists (run: php artisan migrate)\n';
                
                // Show error
                console.error('Final error message:', errorMsg);
                
                if (typeof show_toastr !== 'undefined') {
                    show_toastr('error', error.message || 'Print request failed. Check console (F12) for details.');
                } else {
                    alert(errorMsg);
                }
                
                // Restore button text
                var btnText = printerConfig.configured && printerConfig.ip ? 
                    '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                    '{{ __("Print Direct to Epson TM-M30") }}';
                directPrintBtn.textContent = btnText;
            })
            .finally(() => {
                directPrintBtn.disabled = false;
                // Ensure button text is restored
                if (directPrintBtn.textContent === 'Printing...') {
                    var btnText = printerConfig.configured && printerConfig.ip ? 
                        '{{ __("Print Direct") }} (' + printerConfig.ip + ')' : 
                        '{{ __("Print Direct to Epson TM-M30") }}';
                    directPrintBtn.textContent = btnText;
                }
            });
            
            return false;
        }

        // Handle direct print button click - Auto-detect printer IP/Port (event listener version)
        function handleDirectPrint() {
            var directPrintBtn = document.getElementById('directPrint');
            if (directPrintBtn) {
                directPrintBtn.addEventListener('click', function(e) {
                    handleDirectPrintClick(e);
                });
            }
        }

        // Handle print button click and auto-print on page load (like vouchers)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Setting up print buttons');
            
            // Check if direct print button exists
            var directPrintBtnCheck = document.getElementById('directPrint');
            if (directPrintBtnCheck) {
                console.log('Direct print button found:', directPrintBtnCheck);
                console.log('Button onclick:', directPrintBtnCheck.getAttribute('onclick'));
            } else {
                console.error('Direct print button NOT FOUND!');
            }
            
            // Force visibility on load
            setTimeout(forceVisibility, 100);
            
            // Setup direct print (event listener as backup)
            handleDirectPrint();
            
            // Function to trigger print (used by both button and auto-print)
            function triggerPrint() {
                // Allow print
                if (window._setPrintPrevented) {
                    window._setPrintPrevented(false);
                }
                
                // Force visibility multiple times
                forceVisibility();
                
                window.document.title = 'POS Receipt - {{ $receiptId }}';
                
                setTimeout(function() {
                    forceVisibility();
                    setTimeout(function() {
                        forceVisibility();
                        // Temporarily disable prevention and call original print
                        if (window._setPrintPrevented) {
                            window._setPrintPrevented(false);
                        }
                        // Use the stored original print function
                        if (window._originalPrint) {
                            window._originalPrint();
                        } else {
                            // Fallback: temporarily disable prevention and call print
                            window._setPrintPrevented(false);
                            window.print();
                        }
                        // Re-enable prevention after print
                        setTimeout(function() {
                            if (window._setPrintPrevented) {
                                window._setPrintPrevented(true);
                            }
                        }, 1000);
                    }, 50);
                }, 100);
            }
            
            // Setup print button - ensure it works even if onclick attribute fails
            var printButton = document.getElementById('print');
            if (printButton) {
                console.log('Print button found:', printButton);
                console.log('printReceipt function available:', typeof window.printReceipt);
                
                // Ensure button is enabled and clickable
                printButton.disabled = false;
                printButton.style.pointerEvents = 'auto';
                printButton.style.cursor = 'pointer';
                printButton.style.zIndex = '10000';
                printButton.style.position = 'relative';
                
                // Remove existing onclick and use event listener for more reliable execution
                printButton.removeAttribute('onclick');
                
                // Use event listener for more reliable execution
                printButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Print button clicked!');
                    
                    if (typeof window.printReceipt === 'function') {
                        window.printReceipt();
                    } else {
                        console.error('printReceipt function not found!');
                        // Fallback: direct print
                        if (window._setPrintPrevented) {
                            window._setPrintPrevented(false);
                        }
                        if (typeof forceVisibility === 'function') {
                            forceVisibility();
                        }
                        setTimeout(function() {
                            // Always use _originalPrint to bypass prevention
                            if (window._originalPrint) {
                                console.log('Fallback: Using _originalPrint');
                                window._originalPrint();
                            } else {
                                console.log('Fallback: _originalPrint not available, trying window.print');
                                window.print();
                            }
                        }, 100);
                    }
                    return false;
                });
                
                console.log('Print button event listener attached');
            } else {
                console.error('Print button NOT FOUND!');
            }
            
            // Setup Print Like Voucher button - opens printview in new window for printing (like vouchers)
            // Note: Button already has onclick="openPrintView(); return false;" attribute
            // This event listener is just a backup
            var printVoucherButton = document.getElementById('printVoucherStyle');
            if (printVoucherButton) {
                console.log('Print Like Voucher button found:', printVoucherButton);
                
                // Ensure button is enabled and clickable
                printVoucherButton.disabled = false;
                printVoucherButton.style.pointerEvents = 'auto';
                printVoucherButton.style.cursor = 'pointer';
                printVoucherButton.style.zIndex = '10000';
                printVoucherButton.style.position = 'relative';
                
                // Backup event listener (onclick attribute should work, but this is a fallback)
                printVoucherButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Print Like Voucher button clicked - opening printview in new window for printing');
                    
                    // Open printview in new window with proper parameters - it will auto-print on load (like vouchers)
                    if (typeof window.openPrintView === 'function') {
                        window.openPrintView();
                    } else {
                        console.error('openPrintView function not found!');
                        // Fallback: try to construct URL from current page data
                        var printUrl = '{{ route("pos.printview") }}';
                        var currentParams = new URLSearchParams(window.location.search);
                        var fullUrl = printUrl + '?' + currentParams.toString();
                        window.open(fullUrl, '_blank');
                    }
                    
                    return false;
                });
                
                console.log('Print Like Voucher button event listener attached');
            } else {
                console.error('Print Like Voucher button NOT FOUND!');
            }
            
            // Auto-print on page load (like vouchers)
            // Wait a bit for page to fully load, then trigger print
            setTimeout(function() {
                triggerPrint();
            }, 500);
        });
        
        // Handle after print - close window or go back (like vouchers)
        window.onafterprint = function() {
            // Close window if opened in new window/tab, otherwise go back
            if (window.opener) {
                window.close();
            } else {
                window.history.back();
            }
        };

        // Print event listeners - CRITICAL for visibility
        window.addEventListener('beforeprint', function() {
            forceVisibility();
            // Force again after a tiny delay
            setTimeout(forceVisibility, 10);
        });

        window.addEventListener('afterprint', function() {
            // Don't reset - keep visibility
        });
    </script>
</body>
</html>
