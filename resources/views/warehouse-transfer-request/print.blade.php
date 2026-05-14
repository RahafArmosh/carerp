<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Warehouse Transfer Form') }} - {{ $request->request_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 24px;
            font-size: 14px;
        }
        .page {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 6px;
            font-size: 22px;
        }
        .header .meta {
            margin: 2px 0;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .info-grid td {
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: top;
            width: 50%;
        }
        .label {
            display: block;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #9ca3af;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th {
            background: #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .signature-grid td {
            width: 33.33%;
            padding: 10px 8px 0;
            vertical-align: bottom;
        }
        .signature-title {
            font-weight: 700;
            margin-bottom: 48px;
        }
        .line {
            border-top: 1px solid #111827;
            padding-top: 6px;
            font-size: 13px;
        }
        .print-actions {
            margin-bottom: 16px;
            text-align: right;
        }
        .btn {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        @media print {
            .print-actions {
                display: none;
            }
            body {
                margin: 0;
            }
            .page {
                max-width: none;
                margin: 0;
                padding: 8mm;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="print-actions">
            <button class="btn" onclick="window.print()">{{ __('Print Transfer Form') }}</button>
        </div>

        <div class="header">
            <h2>{{ __('Warehouse Transfer Form') }}</h2>
            <div class="meta">{{ __('Request No') }}: <strong>{{ $request->request_number }}</strong></div>
            <div class="meta">{{ __('Date') }}: <strong>{{ \Auth::user()->dateFormat($request->request_date) }}</strong></div>
        </div>

        <table class="info-grid">
            <tr>
                <td>
                    <span class="label">{{ __('From Warehouse') }}</span>
                    {{ optional($request->fromWarehouse)->name ?? '-' }}
                </td>
                <td>
                    <span class="label">{{ __('To Warehouse') }}</span>
                    {{ optional($request->toWarehouse)->name ?? '-' }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55px;">#</th>
                    <th>{{ __('Item') }}</th>
                    <th style="width: 180px;">{{ __('Product No') }}</th>
                    <th style="width: 120px;" class="text-right">{{ __('Qty') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($request->transfers as $index => $transfer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ optional($transfer->product)->name ?? __('N/A') }}</td>
                        <td>{{ $transfer->product_no }}</td>
                        <td class="text-right">{{ number_format((float) $transfer->quantity, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">{{ __('No items found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="signature-grid">
            <tr>
                <td>
                    <div class="signature-title">{{ __('Prepared By') }}</div>
                    <div class="line">{{ __('Name & Signature') }}</div>
                </td>
                <td>
                    <div class="signature-title">{{ __('Sent By') }}</div>
                    <div class="line">{{ __('Name & Signature') }}</div>
                </td>
                <td>
                    <div class="signature-title">{{ __('Received By') }}</div>
                    <div class="line">{{ __('Name & Signature') }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
