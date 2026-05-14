@php
    $settings = Utility::settings();
    $settings_data = \App\Models\Utility::settingsById($saleOrder->created_by);
    $company_logo = $settings['company_logo_dark'] ?? '';
    $company_logos = $settings['company_logo_light'] ?? '';
    $company_stamp = !empty($settings_data['company_stamp']) ? $settings_data['company_stamp'] : '';
    $currencySymbol = $saleOrder->currency ? $saleOrder->currency->symbol : Auth::user()->currencySymbol();

    $totalAmount = 0;
    $totalTax = 0;
    $taxData = \App\Models\Utility::getTaxData();
    $taxIds = $saleOrder->tax_id ? explode(',', $saleOrder->tax_id) : [];
    $totalTaxRate = 0;
    foreach ($taxIds as $taxId) {
        if (!empty($taxData[$taxId]['rate'])) {
            $totalTaxRate += (float) $taxData[$taxId]['rate'];
        }
    }

    // Load logo from settings (uploads/logo) with safe fallback.
    $logoBase = \App\Models\Utility::get_file('uploads/logo/');
    $selectedLogo = ($settings['cust_darklayout'] ?? 'off') === 'on' ? $company_logos : $company_logo;
    $companyLogoUrl = !empty($selectedLogo)
        ? $logoBase . $selectedLogo
        : asset('storage/uploads/logo/logo-dark.png');
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings['SITE_RTL'] == 'on' ? 'rtl' : 'ltr' }}">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <head>
        <meta charset="utf-8">
        <title>{{ __('Sale Order') }}</title>
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
        <style type="text/css">
            :root { --theme-color: {{ $font_color }}; }
            body { font-family: 'Lato', sans-serif; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            table { width: 100%; border-collapse: collapse; }
            table tr th, table tr td { padding: 0.5rem; text-align: left; }
            .preview-main { max-width: 1120px; width: 100%; margin: 0 auto; background: #fff; box-shadow: 0 0 10px #ddd; position: relative; }
            .logo { width: 120px; }
            .text-right { text-align: right; }
            .no-space tr td { padding: 0; white-space: nowrap; }
            .doc-body { border-top: 1px solid {{ $font_color }}; }
            .d-flex { display: flex; justify-content: space-between; gap: 5px; align-items: center; }
            table.add-border tr { border: 1px solid {{ $font_color }}; }
            .doc-summary { table-layout: auto; }
            .doc-summary td, .doc-summary th { font-size: 11px; font-weight: 600; border: 1px solid; white-space: normal; word-break: break-word; }
            .doc-footer { padding: 15px 20px; }
            .stamp { position: absolute; opacity: 0.6; }
            html[dir="rtl"] table tr td, html[dir="rtl"] table tr th { text-align: right; }
            html[dir="rtl"] .text-right { text-align: left; }
        </style>
        @if ($settings['SITE_RTL'] == 'on')
            <link rel="stylesheet" href="{{ asset('css/bootstrap-rtl.css') }}">
        @endif
    </head>
    <body>
        <div class="preview-main" id="boxes">
            <div class="doc-header">
                <div class="d-flex">
                    <div>
                        <img class="logo" src="{{ $companyLogoUrl }}" alt="Company Logo">
                    </div>
                    <div>
                        <h3 style="text-transform: uppercase; font-size: 20px; font-weight: bold; color: {{ $color }}; margin: 0;">
                            {{ __('Sale Order') }}
                        </h3>
                    </div>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <p>
                                    {{ $settings['company_name'] ?? '' }}<br>
                                    {{ $settings['company_email'] ?? '' }}<br>
                                    {{ $settings['company_address'] ?? '' }}
                                </p>
                            </td>
                            <td>
                                <table class="no-space" style="width: 60%; margin-left: auto;">
                                    <tbody>
                                        <tr>
                                            <td>{{ __('Customer') }}:</td>
                                            <td class="text-right">{{ $saleOrder->customer->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Sale Order No') }}:</td>
                                            <td class="text-right">{{ \Auth::user()->saleOrderNumberFormat($saleOrder->sale_order_no) }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Date') }}:</td>
                                            <td class="text-right">{{ Auth::user()->dateFormat($saleOrder->sales_order_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Currency') }}:</td>
                                            <td class="text-right">{{ $saleOrder->currency ? $saleOrder->currency->name : $currencySymbol }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Exchange Rate') }}:</td>
                                            <td class="text-right">{{ number_format($saleOrder->exchange_rate ?? 1.0, 6) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="doc-body">
                <table class="doc-summary add-border" style="margin-bottom: 15px;">
                    <thead style="background: {{ $color }}; color: {{ $font_color }}">
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Part No') }}</th>
                            <th>{{ __('Description') }}</th>
                            @if($showCustomFields)
                                <th>{{ __('Custom Fields') }}</th>
                            @endif
                            <th>{{ __('REQ QTY') }}</th>
                            <th>{{ __('STOCK QTY') }}</th>
                            <th>{{ __('Unit Price') }}</th>
                            <th>{{ __('Tax') }}</th>
                            <th>{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rows = $showCustomFields ? ($printItems ?? collect()) : ($groupedItems ?? collect());
                        @endphp
                        @forelse($rows as $index => $item)
                            @php
                                $qtyForTotal = (float)($item->stock_qty ?? 0);
                                $itemSubtotal = $item->unit_price * $qtyForTotal;
                                $itemTax = ($totalTaxRate / 100) * $itemSubtotal;
                                $itemTotal = $itemSubtotal + $itemTax;
                                $totalAmount += $itemSubtotal;
                                $totalTax += $itemTax;
                                $discrepancy = $item->discrepancy ?? ($item->packed_qty - $item->req_qty);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->part_no }}</td>
                                <td>{{ $item->description ?? '-' }}</td>
                                @if($showCustomFields)
                                    <td>{{ $item->custom_fields_text ?: '-' }}</td>
                                @endif
                                <td>{{ number_format($item->req_qty, 2) }}</td>
                                <td>{{ number_format($item->stock_qty, 2) }}</td>
                                <td>{{ \Auth::user()->priceFormat($item->unit_price) }}</td>
                                <td>{{ \Auth::user()->priceFormat($itemTax) }}</td>
                                <td>{{ \Auth::user()->priceFormat($itemTotal) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showCustomFields ? 9 : 8 }}" class="text-center">{{ __('No items found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="{{ $showCustomFields ? 7 : 6 }}">{{ __('Subtotal') }}</th>
                            <td colspan="2">{{ \Auth::user()->priceFormat($totalAmount) }}</td>
                        </tr>
                        <tr>
                            <th colspan="{{ $showCustomFields ? 7 : 6 }}">{{ __('Tax') }} ({{ number_format($totalTaxRate, 2) }}%)</th>
                            <td colspan="2">{{ \Auth::user()->priceFormat($totalTax) }}</td>
                        </tr>
                        <tr>
                            <th colspan="{{ $showCustomFields ? 7 : 6 }}">{{ __('Total') }}</th>
                            <td colspan="2">{{ \Auth::user()->priceFormat($totalAmount + $totalTax) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="doc-footer">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin: 40px 0;">
                    <div style="text-align: left; width: 45%;">
                        <label style="font-weight: bold;">{{ __('Company Signature') }}</label>
                        <div style="margin-top: 10px;">
                            <img src="{{ (!empty($company_stamp) ? URL::to('/') . '/' . 'documents' . '/' . $company_stamp : URL::to('/') . '/' . 'storage/uploads/logo' . '/' . 'stamp-preview.png') . '?timestamp=' . time() }}"
                                class="stamp" width="60" alt="Company Signature">
                        </div>
                        <div style="margin-top: 30px; border-top: 1px solid #c4c4c4; width: 80%;"></div>
                    </div>
                    <div style="text-align: right; width: 45%;">
                        <label style="font-weight: bold;">{{ __('Customer Signature') }}</label>
                        <div style="margin-top: 40px; border-top: 1px solid #c4c4c4; width: 80%; float: right;"></div>
                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
        <script>
            function closeScript() {
                setTimeout(function() { window.open(window.location, '_self').close(); }, 1000);
            }
            $(window).on('load', function() {
                var element = document.getElementById('boxes');
                var opt = {
                    margin: 0.5,
                    filename: '{{ 'Sale Order PDF #' . $saleOrder->sale_order_no }}',
                    image: { type: 'jpeg', quality: 1 },
                    html2canvas: { scale: 2, dpi: 96, letterRendering: true },
                    jsPDF: { unit: 'in', format: 'A4', orientation: 'landscape' }
                };
                html2pdf().set(opt).from(element).save().then(closeScript);
            });
        </script>
    </body>
</html>
