@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Detail') }}
@endsection
@push('script-page')


    @section('content')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="invoice">
                            <div class="invoice-print">
                                <div class="row invoice-title mt-2">
                                    <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                        <h4>{{ __('Bill') }}</h4>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                        <h4 class="invoice-number">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</h4>
                                    </div>
                                    <div class="col-12">
                                        <hr>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col text-end">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="me-4">
                                                <small>
                                                    <strong>{{ __('Issue Date') }} :</strong><br>
                                                    {{ \Auth::user()->dateFormat($bill->bill_date) }}<br><br>
                                                </small>
                                            </div>
                                            <div>
                                                <small>
                                                    <strong>{{ __('Delete Date') }} :</strong><br>
                                                    {{ \Auth::user()->dateFormat($bill->due_date) }}<br><br>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="font-bold mb-2">{{ __('Product Summary') }}</div>
                                        <div class="table-responsive mt-3">
                                            <table class="table mb-0 table-striped">
                                                <tr>
                                                    <th class="text-dark" data-width="40">#</th>
                                                    <th class="text-dark">{{ __('Product') }}</th>
                                                    <th class="text-dark">{{ __('Sub Product') }}</th>
                                                    <th class="text-dark">{{ __('Quantity') }}</th>
                                                    <th class="text-dark">{{ __('Rate') }}</th>
                                                    <th class="text-dark">{{ __('Discount') }}</th>
                                                    <th class="text-dark">{{ __('Tax') }}</th>
                                                    <th class="text-dark">{{ __('Chart Of Account') }}</th>
                                                    <th class="text-dark">{{ __('Account Amount') }}</th>
                                                    {{-- <th class="text-dark">{{__('Description')}}</th> --}}
                                                    <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                        <small
                                                            class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                                    </th>
                                                </tr>
                                                @php
                                                    $totalQuantity = count($items);
                                                    $totalRate = 0;
                                                    $totalTaxPrice = 0;
                                                    $totaRTAX = 0;
                                                    $totalDiscount = 0;
                                                    $taxesData = [];
                                                @endphp



                                                @foreach ($items as $key => $item)
                                                    @if (!empty($item->product_id))
                                                        <tr>
                                                            <td>{{ $item->sub_product_id }}</td>

                                                            @php
                                                                $productName = $item->product;
                                                                $sub_productName = \App\Models\SubProduct::where(
                                                                    'id',
                                                                    $item->sub_product_id,
                                                                )->first();
                                                                // $totalQuantity += $item->quantity;
                                                                $totalRate +=
                                                                    $sub_productName->productService->category->type ==
                                                                    'Qty product'
                                                                        ? $sub_productName->purchase_price *
                                                                            $item->quantity
                                                                        : $sub_productName->purchase_price;
                                                                $totalDiscount += $item->discount;
                                                            @endphp
                                                            @if ($productName->brand !== null)
                                                                <td>{{ !empty($productName) ? $productName->brand->name . '/' . $productName->subBrand->name . '/' . $productName->name : '-' }}
                                                                </td>
                                                            @else
                                                                <td>{{ !empty($productName) ? $productName->name : '-' }}</td>
                                                            @endif

                                                            <td>{{ !empty($sub_productName) ? $sub_productName->product_no : '-' }}
                                                            </td>
                                                            <td>{{ $sub_productName->productService->category->type == 'Qty product'
                                                                ? $item->quantity . ' (' . $productName->unit->name . ')'
                                                                : 1 . ' (' . $productName->unit->name . ')' }}
                                                            </td>
                                                            <td>{{ \Auth::user()->priceFormat($sub_productName->purchase_price) }}
                                                            </td>
                                                            <td>{{ \Auth::user()->priceFormat($item->discount) }}</td>

                                                            <td>
                                                                @if (!empty($item->bill_tax))
                                                                    <table>
                                                                        @php
                                                                            $itemTaxes = [];
                                                                            $getTaxData = Utility::getTaxData();
                                                                            $totalTaxPrice = 0;
                                                                            if (!empty($item->bill_tax)) {
                                                                                foreach (
                                                                                    explode(',', $item->bill_tax)
                                                                                    as $tax
                                                                                ) {
                                                                                    $sub_productName->productService
                                                                                        ->category->type ==
                                                                                    'Qty product'
                                                                                        ? ($taxPrice = \Utility::taxRate(
                                                                                            $getTaxData[$tax]['rate'],
                                                                                            $sub_productName->purchase_price *
                                                                                                $item->quantity,
                                                                                            1,
                                                                                        ))
                                                                                        : ($taxPrice = \Utility::taxRate(
                                                                                            $getTaxData[$tax]['rate'],
                                                                                            $sub_productName->purchase_price,
                                                                                            1,
                                                                                        ));
                                                                                    $totalTaxPrice += $taxPrice;
                                                                                    $totaRTAX += $taxPrice;
                                                                                    $itemTax['name'] =
                                                                                        $getTaxData[$tax]['name'];
                                                                                    $itemTax['rate'] =
                                                                                        $getTaxData[$tax]['rate'] . '%';
                                                                                    $itemTax[
                                                                                        'price'
                                                                                    ] = \Auth::user()->priceFormat(
                                                                                        $taxPrice,
                                                                                    );

                                                                                    $itemTaxes[] = $itemTax;
                                                                                    if (
                                                                                        array_key_exists(
                                                                                            $getTaxData[$tax]['name'],
                                                                                            $taxesData,
                                                                                        )
                                                                                    ) {
                                                                                        $taxesData[
                                                                                            $getTaxData[$tax]['name']
                                                                                        ] =
                                                                                            $taxesData[
                                                                                                $getTaxData[$tax][
                                                                                                    'name'
                                                                                                ]
                                                                                            ] + $taxPrice;
                                                                                    } else {
                                                                                        $taxesData[
                                                                                            $getTaxData[$tax]['name']
                                                                                        ] = $taxPrice;
                                                                                    }
                                                                                }
                                                                                $item->itemTax = $itemTaxes;
                                                                            } else {
                                                                                $item->itemTax = [];
                                                                            }
                                                                        @endphp
                                                                        @foreach ($item->itemTax as $tax)
                                                                            <tr>
                                                                                <td>{{ $tax['name'] . ' (' . $tax['rate'] . ')' }}
                                                                                </td>
                                                                                <td>{{ $tax['price'] }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </table>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>

                                                            @php
                                                                $chartAccount = \App\Models\ChartOfAccount::find(
                                                                    $item->chart_account_id,
                                                                );
                                                            @endphp

                                                            <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                            <td>-</td>

                                                            {{-- <td>{{!empty($item->description)?$item->description:'-'}}</td> --}}

                                                            <td class="text-end">
                                                                {{ $sub_productName->productService->category->type == 'Qty product'
                                                                    ? \Auth::user()->priceFormat($sub_productName->purchase_price * $item->quantity - $item->discount + $totalTaxPrice)
                                                                    : \Auth::user()->priceFormat($sub_productName->purchase_price - $item->discount + $totalTaxPrice) }}
                                                            </td>
                                                        </tr>
                                                    @else
                                                        <tr>
                                                            <td>{{ $key + 1 }}</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            @php
                                                                $chartAccount = \App\Models\ChartOfAccount::find(
                                                                    $item['chart_account_id'],
                                                                );
                                                            @endphp
                                                            <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                            <td>{{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                            <td>-</td>
                                                            <td class="text-end">
                                                                {{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                            <td></td>


                                                        </tr>
                                                    @endif
                                                @endforeach
                                                <tfoot>
                                                    <tr>
                                                        <td></td>
                                                        <td></td>
                                                        <td><b>{{ __('Total') }}</b></td>
                                                        <td><b>{{ $totalQuantity }}</b></td>
                                                        <td><b>{{ \Auth::user()->priceFormat($totalRate) }}</b></td>
                                                        <td><b>{{ \Auth::user()->priceFormat($totalDiscount) }}</b></td>
                                                        <td><b>{{ \Auth::user()->priceFormat($totaRTAX) }}</b></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td class="text-end">
                                                            <b>{{ \Auth::user()->priceFormat($totalRate + $totaRTAX) }}</b>
                                                        </td>

                                                    </tr>
                                                    <tr>
                                                        <td colspan="8"></td>
                                                        <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($totalRate) }}</td>
                                                    </tr>

                                                    <tr>
                                                        <td colspan="8"></td>
                                                        <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                        <td class="text-end">
                                                            {{ \Auth::user()->priceFormat($bill->getTotalDiscount()) }}</td>
                                                    </tr>

                                                    @if (!empty($taxesData))
                                                        @foreach ($taxesData as $taxName => $taxPrice)
                                                            <tr>
                                                                <td colspan="8"></td>
                                                                <td class="text-end"><b>{{ $taxName }}</b></td>
                                                                <td class="text-end">
                                                                    {{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    <tr>
                                                        <td colspan="8"></td>
                                                        <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                        <td class="blue-text text-end">
                                                            {{ \Auth::user()->priceFormat($totalRate + $totaRTAX) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
