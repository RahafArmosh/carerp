<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Customer Statement') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
            padding: 4px 8px 4px 0;
            border: none;
        }
        .logo {
            max-width: 120px;
            max-height: 70px;
        }
        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: {{ $color }};
            margin: 0 0 8px 0;
        }
        .company-block p {
            margin: 0 0 2px 0;
            line-height: 1.35;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 3px 6px;
            border: 0.5px solid #999;
        }
        .meta-label {
            font-weight: bold;
            width: 22%;
            background: #f5f5f5;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            border: 0.5px solid #333;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background: #eaeaea;
            font-size: 8px;
        }
        .num {
            text-align: right;
            white-space: nowrap;
        }
        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="width: 140px;">
            @if(!empty($logoUrl))
                <img class="logo" src="{{ $logoUrl }}" alt="">
            @endif
        </td>
        <td>
            <div class="company-block">
                @if(!empty($settings['company_name']))
                    <p style="font-size: 12px; font-weight: bold;">{{ $settings['company_name'] }}</p>
                @endif
                @if(!empty($settings['company_address']))
                    <p>{{ $settings['company_address'] }}</p>
                @endif
                @if(!empty($settings['company_city']) || !empty($settings['company_state']))
                    <p>{{ trim(implode(', ', array_filter([$settings['company_city'] ?? '', $settings['company_state'] ?? '']))) }}</p>
                @endif
                @if(!empty($settings['company_zipcode']))
                    <p>{{ $settings['company_zipcode'] }}</p>
                @endif
                @if(!empty($settings['company_country']))
                    <p>{{ $settings['company_country'] }}</p>
                @endif
                @if(!empty($settings['company_telephone']))
                    <p>{{ __('Tel') }}: {{ $settings['company_telephone'] }}</p>
                @endif
                @if(!empty($settings['mail_from_address']))
                    <p>{{ __('Email') }}: {{ $settings['mail_from_address'] }}</p>
                @endif
                @if(!empty($settings['vat_number']))
                    <p>{{ __('VAT') }}: {{ $settings['vat_number'] }}</p>
                @endif
            </div>
        </td>
        <td style="width: 200px; text-align: right;">
            <p style="margin:0; font-size: 9px;">{{ __('Generated') }}: {{ $user->dateFormat(date('Y-m-d H:i:s')) }}</p>
        </td>
    </tr>
</table>

<p class="doc-title">{{ __('Customer Statement Summary') }}</p>

<table class="meta-table">
    <tr>
        <td class="meta-label">{{ __('Report') }}</td>
        <td>{{ __('Customer Statement Summary') }}</td>
    </tr>
    @if(($filter['account'] ?? '') != __('All'))
        <tr>
            <td class="meta-label">{{ __('Account') }}</td>
            <td>{{ $filter['account'] }}</td>
        </tr>
    @endif
    @if(($filter['customer'] ?? '') != __('All'))
        <tr>
            <td class="meta-label">{{ __('Customer') }}</td>
            <td>
                @php
                    $cust = \App\Models\Customer::where('id', $filter['customer'])->where('created_by', $user->creatorId())->first();
                @endphp
                {{ $cust ? $cust->name : '' }}
            </td>
        </tr>
    @endif
    <tr>
        <td class="meta-label">{{ __('Duration') }}</td>
        <td>{{ ($filter['startDateRange'] ?? '') . ' ' . __('to') . ' ' . ($filter['endDateRange'] ?? '') }}</td>
    </tr>
    <tr>
        <td class="meta-label">{{ __('Previous Balance') }}</td>
        <td>{{ $user->priceFormat($previousBalance) }}</td>
    </tr>
</table>

@if(!empty($reportData['general_ledger']))
    @php
        $totalDebitRun = $previousBalance;
        $totalCreditRun = 0;
        $creatorId = $user->creatorId();
    @endphp
    <table class="data-table" cellspacing="0">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Update Date') }}</th>
                <th>{{ __('VID') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Account') }}</th>
                <th class="num">{{ __('Debit') }}</th>
                <th class="num">{{ __('Credit') }}</th>
                <th class="num">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['general_ledger'] as $gl)
                @php
                    $totalDebitRun += $gl->total_debit;
                    $totalCreditRun += $gl->total_credit;
                    $customerRow = \App\Models\Customer::where('id', $gl->user_id)->where('created_by', $creatorId)->first();
                    $chartRow = \App\Models\ChartOfAccount::where('id', $gl->account)->where('created_by', $creatorId)->first();
                    $amountShow = $gl->total_debit > 0 ? $gl->total_debit : $gl->total_credit;
                    $typeDisplay = $gl->type ?? '';
                    if (($gl->reference ?? '') === 'POS Deletion Reversal') {
                        $typeDisplay = $typeDisplay !== '' ? $typeDisplay : __('POS Deletion Reversal');
                    }
                @endphp
                <tr>
                    <td>{{ $user->dateFormat($gl->send_date) }}</td>
                    <td>{{ $user->dateFormat($gl->updated_at) }}</td>
                    <td>{{ $gl->vid }}</td>
                    <td class="num">{{ $user->priceFormat($amountShow) }}</td>
                    <td>{{ $typeDisplay }}</td>
                    <td>{{ $customerRow ? $customerRow->name : '' }}</td>
                    <td>{{ $chartRow ? $chartRow->name : '' }}</td>
                    <td class="num">{{ $user->priceFormat($gl->total_debit) }}</td>
                    <td class="num">{{ $user->priceFormat($gl->total_credit) }}</td>
                    <td class="num">{{ $user->priceFormat($totalDebitRun - $totalCreditRun) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7"><strong>{{ __('Total') }}</strong></td>
                <td class="num"><strong>{{ $user->priceFormat($totalDebitRun) }}</strong></td>
                <td class="num"><strong>{{ $user->priceFormat($totalCreditRun) }}</strong></td>
                <td class="num">{{ $user->priceFormat($totalDebitRun - $totalCreditRun) }}</td>
            </tr>
        </tbody>
    </table>
@else
    <p>{{ __('No records found for this period.') }}</p>
@endif

</body>
</html>
