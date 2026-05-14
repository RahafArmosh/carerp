@php
    $settings = Utility::settings();
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings == 'on' ? 'rtl' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }} - ASN Barcode</title>

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
                margin: 10mm;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }

        .barcode-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
            max-width: 100%;
        }

        .barcode-box {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 4px;
            margin: 0;
            text-align: center;
            width: 5cm;
            height: 3cm;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .barcode-box:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .barcode-header {
            display: flex;
            flex-direction: column;
            margin-bottom: 2px;
            flex-shrink: 0;
        }

        .brand-section {
            font-size: 7px;
            color: #2d3748;
            margin-bottom: 1px;
            padding: 1px 2px;
            line-height: 1.1;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .brand-text {
            color: #2d3748;
            font-weight: 700;
            font-size: 7px;
        }

        .description-section {
            font-size: 6px;
            color: #2d3748;
            margin-bottom: 1px;
            padding: 1px 2px;
            line-height: 1.1;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .description-text {
            color: #2d3748;
            font-weight: 500;
            font-size: 6px;
        }

        .custom-fields-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 1px;
            margin-bottom: 1px;
            justify-content: center;
            flex-shrink: 0;
        }

        .custom-field-section {
            font-size: 5px;
            color: #2d3748;
            padding: 1px 2px;
            background: #f7fafc;
            border-radius: 2px;
            text-align: center;
            line-height: 1.1;
        }

        .custom-field-section strong {
            color: #2d3748;
            font-weight: 600;
            margin-right: 2px;
        }

        .custom-field-value {
            color: #2d3748;
            font-weight: 500;
        }

        .price-section {
            font-size: 7px;
            color: #2d3748;
            margin-bottom: 2px;
            padding: 1px 2px;
            background: #e6fffa;
            border-radius: 2px;
            font-weight: 700;
            text-align: center;
            flex-shrink: 0;
            line-height: 1.1;
        }

        .price-section .price-label {
            font-size: 5px;
            color: #4a5568;
            text-transform: uppercase;
            margin-bottom: 1px;
            font-weight: 600;
        }

        .price-section .price-value {
            font-size: 8px;
            color: #2d3748;
            font-weight: 700;
        }

        .barcode-image {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1px;
            min-height: 0;
            overflow: hidden;
        }

        .barcode-image img {
            width: 100%;
            height: auto;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        .product-no {
            font-size: 6px;
            color: #2d3748;
            margin-top: 1px;
            font-family: 'Courier New', monospace;
            flex-shrink: 0;
            font-weight: 600;
            text-align: center;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .barcode-box {
                page-break-inside: avoid;
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .barcode-box:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
<div id="bot" class="mt-4">
    <div class="barcode-container">
        @for($i = 1; $i <= $quantity; $i++)
            <div class="barcode-box">
                <div class="barcode-header">
                    <!-- Brand -->
                    @if(isset($subproduct) && is_object($subproduct) && $subproduct->productService && $subproduct->productService->brand)
                        <div class="brand-section">
                            <div class="brand-text">{{ $subproduct->productService->brand->name }}</div>
                        </div>
                    @endif
                    
                    <!-- Description -->
                    @if(!empty($item->description))
                        <div class="description-section">
                            <div class="description-text">{{ Str::limit($item->description, 30) }}</div>
                        </div>
                    @endif
                    
                    <!-- Custom Fields -->
                    @if(isset($customFields) && $customFields->count() > 0)
                        <div class="custom-fields-wrapper">
                            @foreach($customFields as $field)
                                @php
                                    $value = isset($customFieldValues[$field->id]) ? $customFieldValues[$field->id]->value : '';
                                @endphp
                                @if(!empty($value))
                                    <div class="custom-field-section">
                                        <strong>{{ Str::limit($field->name, 8) }}:</strong>
                                        <span class="custom-field-value">{{ Str::limit($value, 10) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Price -->
                    @php
                        $displayPrice = 0;
                        if (isset($subproduct) && is_object($subproduct)) {
                            // Use final_price_with_tax if available (includes tax), otherwise use sale_price
                            if (isset($subproduct->final_price_with_tax) && $subproduct->final_price_with_tax > 0) {
                                $displayPrice = $subproduct->final_price_with_tax;
                            } elseif (isset($subproduct->sale_price) && $subproduct->sale_price > 0) {
                                $displayPrice = $subproduct->sale_price;
                            }
                        }
                    @endphp
                    @if($displayPrice > 0)
                        <div class="price-section">
                            <div class="price-label">{{ __('Price') }}</div>
                            <div class="price-value">{{ $currencySymbol }}{{ number_format($displayPrice, 2) }}</div>
                        </div>
                    @endif
                </div>

                <!-- Barcode -->
                <div class="barcode-image">
                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($subproduct->product_no, 'C128', 2, 50) }}" alt="barcode" />
                </div>

                <!-- Product Number -->
                <div class="product-no">{{ $subproduct->product_no }}</div>
            </div>
        @endfor
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

