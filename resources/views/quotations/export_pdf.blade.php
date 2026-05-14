<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans;
            font-size: 11px;
        }

        .header {
            width: 100%;
            margin-bottom: 15px;
        }

        .logo {
            width: 120px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #333;
            padding: 5px;
        }

        th {
            background: #f0f0f0;
        }

        .alt {
            background-color: #e0e0e0;
        }

        .label {
            font-weight: bold;
            width: 150px;
        }

        .totals {
            margin-top: 15px;
            width: 40%;
            float: right;
        }

        .totals td {
            border: none;
            padding: 4px;
        }

        .totals .value {
            text-align: right;
        }

        .grand-total {
            font-weight: bold;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<table class="header">
    <tr>
        <td style="border:none;">
            {{-- COMPANY LOGO --}}
            <img src="{{ public_path('logo.png') }}" class="logo">
        </td>

        <td style="border:none;">
            <div class="company-name">
                Your Company Name
            </div>
        </td>

        <td class="right" style="border:none;">
            <div><strong>Date:</strong> {{ now()->format('Y-m-d') }}</div>
            <div><strong>Quotation:</strong> {{ $quotation->quotation_no }}</div>
        </td>
    </tr>
</table>

<h3>Quotation Details</h3>

<table>
    <tr>
        <td class="label">Quotation No</td>
        <td>{{ $quotation->quotation_no }}</td>
    </tr>
    <tr>
        <td class="label">Customer</td>
        <td>{{ $quotation->customer->name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Date</td>
        <td>{{ $quotation->quotation_date }}</td>
    </tr>
    <tr>
        <td class="label">Warehouse</td>
        <td>{{ $quotation->warehouse->name ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Price Group</td>
        <td>{{ $quotation->priceGroup->name ?? '' }}</td>
    </tr>
</table>

<br>

<table>
    <thead>
        <tr>
            <th>PartNumber</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Available</th>
            <th>Unit Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>

    @php
        $mainItems = $quotation->items->where('is_alternative', 0);
    @endphp

    @foreach($mainItems as $main)
    @if ($main->form_state == 'out of system')
        <tr>
            <td>{{ $main->partnumber }}</td>
            <td>Out of System</td>
            <td>{{ $main->re_quantity }}</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
        </tr>
    @else
        <tr>
            <td>{{ $main->productService->sku }}</td>
            <td>{{ $main->productService->name }}</td>
            <td>{{ $main->re_quantity }}</td>
            <td>{{ $main->av_quantity }}</td>
            <td>{{ number_format($main->unit_price, 2) }}</td>
            <td>{{ number_format($main->av_quantity * $main->unit_price, 2) }}</td>
        </tr>
        @foreach(
            $quotation->items
                ->where('is_alternative', 1)
                ->where('parent_id', $main->id)
            as $alt
        )
            <tr class="alt">
                <td>{{ $alt->productService->sku }}</td>
                <td>↳ {{ $alt->productService->name }}</td>
                <td>{{ $alt->re_quantity }}</td>
                <td>{{ $alt->av_quantity }}</td>
                <td>{{ number_format($alt->unit_price, 2) }}</td>
                <td>{{ number_format($alt->av_quantity * $alt->unit_price, 2) }}</td>
            </tr>
        @endforeach
    @endif
        

        
    @endforeach

    </tbody>
</table>

{{-- TOTALS --}}
<table class="totals">
    
    <tr>
        <td>Subtotal:</td>
        <td class="value">{{ number_format($quotation->subtotal, 2) }}</td>
    </tr>

    <tr>
        <td>Tax:</td>
        <td class="value">{{ number_format($quotation->tax_amount, 2) }}</td>
    </tr>

    <tr class="grand-total">
        <td>Total:</td>
        <td class="value">{{ number_format($quotation->total, 2) }}</td>
    </tr>

</table>

</body>
</html>
