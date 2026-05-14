@php
    use App\Models\Utility;
    use App\Models\CustomField;

    $settings = Utility::settingsById($invoice->created_by);
    $company_logo = $settings['company_logo_dark'] ?? '';
    $logoPath = URL::to('/') . '/' . 'documents' . '/' . (!empty($company_logo) ? $company_logo : 'logo-dark.png');
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings['SITE_RTL'] == 'on' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .invoice-wrapper { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 10px; }
        .header h2 { margin: 0; }
        .group-header { background: #f0f0f0; font-weight: bold; margin-top: 10px; }
        .bordered th, .bordered td { border: 1px solid #000; }
    </style>
</head>
<body>
<div class="invoice-wrapper" id="print-area">
    <div class="header">
        <div>
            <img src="{{ $logoPath }}" alt="Logo" style="height: 50px;">
            <div>
                <strong>{{ $settings['company_name'] ?? '' }}</strong><br>
                {{ $settings['company_address'] ?? '' }}
            </div>
        </div>
        <div class="text-right">
            <h2>{{ __('Invoice') }}</h2>
            <div>{{ \Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}</div>
            <div>{{ __('Issue Date') }}: {{ \Auth::user()->dateFormat($invoice->issue_date) }}</div>
            <div>{{ __('Due Date') }}: {{ \Auth::user()->dateFormat($invoice->due_date) }}</div>
        </div>
    </div>

    <table>
        <tr>
            <td style="width: 50%;">
                <strong>{{ __('Billed To') }}</strong><br>
                {{ optional($customer)->name }}<br>
                {{ optional($customer)->billing_address }}<br>
                {{ optional($customer)->billing_city }},
                {{ optional($customer)->billing_state }}
                {{ optional($customer)->billing_zip }}<br>
                {{ optional($customer)->billing_country }}
            </td>
            <td style="width: 50%;">
                <strong>{{ __('Customer Tax Number') }}</strong><br>
                {{ optional($customer)->tax_number ?? '-' }}
            </td>
        </tr>
    </table>

    @php
        $subFieldDefs = \App\Models\CustomField::where('module', 'sub-product')->get()->keyBy('id');
        $grouped = collect($groupedItems);
    @endphp

    @foreach ($grouped as $groupKey => $rows)
        @php
            /** @var \Illuminate\Support\Collection $rows */
            $first = $rows->first();
            $subProductNo = $first->sub_product_no ?? $first->sub_product_id;
            $cfValues = $first->customField ?? collect();
        @endphp

    <table class="group-header">
        <tr>
            <td>
                {{ __('Sub Product No') }}: {{ $subProductNo }}
                @if($cfValues && $cfValues->count())
                    | {{ __('Custom Fields') }}:
                    @foreach($cfValues as $fieldId => $value)
                        @php $field = $subFieldDefs->get($fieldId); @endphp
                        {{ $field ? $field->name : ('CF#'.$fieldId) }}:
                        @if(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value }}
                        @endif
                        @if(!$loop->last) | @endif
                    @endforeach
                @endif
            </td>
        </tr>
    </table>

    <table class="bordered" style="margin-top: 2px;">
        <thead>
        <tr>
            <th>{{ __('Part / Product') }}</th>
            <th>{{ __('Qty') }}</th>
            <th>{{ __('Unit Price') }}</th>
            <th>{{ __('Discount') }}</th>
            <th>{{ __('Tax') }}</th>
            <th class="text-right">{{ __('Line Total') }}</th>
        </tr>
        </thead>
        <tbody>
        @php
            $groupSubtotal = 0;
        @endphp
        @foreach($rows as $row)
            @php
                $lineTotal = ($row->price - $row->discount) * $row->quantity;
                if (!empty($row->itemTax)) {
                    foreach($row->itemTax as $t) {
                        $lineTotal += $t['tax_price'] ?? 0;
                    }
                }
                $groupSubtotal += $lineTotal;
            @endphp
            <tr>
                <td>{{ trim($row->brand . ' / ' . $row->subBrand . ' / ' . $row->name, ' /') }}</td>
                <td>{{ $row->quantity }}</td>
                <td>{{ Utility::priceFormat($settings, $row->price) }}</td>
                <td>{{ Utility::priceFormat($settings, $row->discount) }}</td>
                <td>
                    @if(!empty($row->itemTax))
                        @foreach($row->itemTax as $t)
                            {{ $t['name'] ?? '' }} ({{ $t['rate'] ?? '' }})<br>
                        @endforeach
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">{{ Utility::priceFormat($settings, $lineTotal) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5" class="text-right"><strong>{{ __('Group Subtotal') }}</strong></td>
            <td class="text-right"><strong>{{ Utility::priceFormat($settings, $groupSubtotal) }}</strong></td>
        </tr>
        </tbody>
    </table>
    @endforeach

    <table style="margin-top: 10px;">
        <tr>
            <td class="text-right">
                <strong>{{ __('Invoice Total') }}:</strong>
                {{ Utility::priceFormat($settings, $invoice->getTotal()) }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>

