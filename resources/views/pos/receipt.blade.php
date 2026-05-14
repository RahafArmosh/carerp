@php
    $settings = Utility::settings();
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings == 'on' ? 'rtl' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }} - POS Barcode</title>

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
    /* REAL THERMAL LABEL SIZE */
@media print {
    @page {
        size: 60mm 40mm;
        margin: 0; /* must be 0 for exact size */
    }
    body {
        margin: 0;
        padding: 0;
        background: white !important;
    }
}

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: #f5f5f5;
    padding: 0px;
    margin: 0;
}

.barcode-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.barcode-box {
    width: 60mm;
    min-height: 22mm; /* base height */
    padding: 1mm 2mm;
    background: white;
    border: 1px solid #ccc;
    display: flex;
    flex-direction: column;
    justify-content: center;   /* center all content vertically */
    box-sizing: border-box;
}

/* Make all text black */
.barcode-box * {
    color: #000 !important;
}

/* Text Tweaks */
.product-name {
    font-size: 11px; /* slightly smaller to save vertical space */
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
    margin-bottom: 0;
}

.brand-info {
    font-size: 9px;
    text-align: center;
    margin-bottom: 0;
    font-weight: bold;
}

.custom-fields-wrapper {
    display: flex;
    flex-wrap: nowrap;          /* keep all custom fields on one horizontal line */
    align-items: center;
    gap: 2px;
}

.custom-field-item {
    font-size: 9px;
    line-height: 1;
    margin: 0;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;        /* prevent wrapping inside each field */
}

.price-section {
    font-size: 10px;
    padding: 0;
    margin: 0;
    text-align: center;
}

.old-price {
    text-decoration: line-through;
    font-size: 10px;           /* slightly larger so it's easier to read */
    font-weight: 700;          /* bold for better visibility */
    margin-right: 4px;
    opacity: 1;                /* full opacity so the old price is clear */
}

.current-price {
    font-weight: 700;
}

.discount-badge {
    font-size: 8px;
    font-weight: 700;
    margin-left: 2px;
}

.product-no {
    text-align: center;
    font-size: 10px;
    font-weight: bold;
}

/* BARCODE FIX */
.barcode-image {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
    padding: 0;
}

.barcode-image svg,
.barcode-image img {
    width: 100% !important;
    height: auto !important;
    max-height: 12mm !important; /* reduced further to avoid page break */
}

@media print {
    .barcode-box {
        border: none;
        box-shadow: none;
        page-break-inside: avoid;
    }
    /* small top margin instead of none */
    #bot {
        margin-top: 1mm !important;
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
                    @if(!empty($brand))
                        <div class="brand-info">
                            {{ Str::limit($brand, 20) }}
                        </div>
                    @endif
                    
                    <!-- Product Name -->
                    <div class="product-name">{{ Str::limit($productName, 30) }}</div>
                    
                    <!-- Custom Fields -->
                    @if(!empty($selectedCustomFields) && count($selectedCustomFields) > 0)
                        <div class="custom-fields-wrapper">
                            @foreach($selectedCustomFields as $customField)
                                @php
                                    // Parse custom field string (format: "Field Name : Value")
                                    $parts = explode(' : ', $customField, 2);
                                    $fieldName = isset($parts[0]) ? trim($parts[0]) : '';
                                    $fieldValue = isset($parts[1]) ? trim($parts[1]) : '';
                                @endphp
                                @if(!empty($fieldName) || !empty($fieldValue))
                                    <div class="custom-field-item">
                                        @if(!empty($fieldName))
                                            <strong>{{ $fieldName }}:</strong>
                                        @endif
                                        @if(!empty($fieldValue))
                                            <span>{{ $fieldValue }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Price display -->
                    <div class="price-section">
                        @if(!empty($old_price) && $old_price > 0 && !empty($discount) && $discount > 0)
                            <span class="old-price">{{ $currencySymbol }}{{ number_format($old_price, 2) }}</span>
                        @endif
                        <span class="price current-price">{{ $currencySymbol }}{{ number_format($price_on_ticket, 2) }}</span>
                        @if(!empty($discount) && $discount > 0)
                            <span class="discount-badge">-{{ $discount }}%</span>
                        @endif
                    </div>
                </div>

                <!-- Barcode -->
                <div class="barcode-image">
                    @if(!empty($subproduct->product_no))
                        <div style="width: 100%; text-align: center;">
                            {!! DNS1D::getBarcodeHTML($subproduct->product_no, 'C128', 2, 50) !!}
                        </div>
                    @else
                        <div style="color: #000000; font-size: 11px;">No Product Number</div>
                    @endif
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
