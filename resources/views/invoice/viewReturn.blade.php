@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ AUth::user()->invoiceNumberFormat($invoice->invoice_id) }}</li>
@endsection
@php
    $settings = Utility::settings();
@endphp
@push('css-page')
    <style>
        #card-element {
            border: 1px solid #a3afbb !important;
            border-radius: 10px !important;
            padding: 10px !important;
        }
    </style>
@endpush
@push('script-page')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script type="text/javascript">
        @if (
            $invoice->getDue() > 0 &&
                !empty($company_payment_setting) &&
                $company_payment_setting['is_stripe_enabled'] == 'on' &&
                !empty($company_payment_setting['stripe_key']) &&
                !empty($company_payment_setting['stripe_secret']))

            var stripe = Stripe('{{ $company_payment_setting['stripe_key'] }}');
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            var style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '14px',
                    color: '#32325d',
                },
            };

            // Create an instance of the card Element.
            var card = elements.create('card', {
                style: style
            });

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');

            // Create a token or display an error when the form is submitted.
            var form = document.getElementById('payment-form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        $("#card-errors").html(result.error.message);
                        show_toastr('error', result.error.message, 'error');
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment-form');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                // Submit the form
                form.submit();
            }
        @endif

        @if (isset($company_payment_setting['paystack_public_key']))
            $(document).on("click", "#pay_with_paystack", function() {

                $('#paystack-payment-form').ajaxForm(function(res) {
                    var amount = res.total_price;
                    if (res.flag == 1) {
                        var paystack_callback = "{{ url('/invoice/paystack') }}";

                        var handler = PaystackPop.setup({
                            key: '{{ $company_payment_setting['paystack_public_key'] }}',
                            email: res.email,
                            amount: res.total_price * 100,
                            currency: res.currency,
                            ref: 'pay_ref_id' + Math.floor((Math.random() * 1000000000) +
                                1
                            ), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                            metadata: {
                                custom_fields: [{
                                    display_name: "Email",
                                    variable_name: "email",
                                    value: res.email,
                                }]
                            },

                            callback: function(response) {

                                window.location.href = paystack_callback + '/' + response
                                    .reference + '/' + '{{ encrypt($invoice->id) }}' +
                                    '?amount=' + amount;
                            },
                            onClose: function() {
                                alert('window closed');
                            }
                        });
                        handler.openIframe();
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', res.message, 'msg');
                    }

                }).submit();
            });
        @endif

        @if (isset($company_payment_setting['flutterwave_public_key']))
            //    Flaterwave Payment
            $(document).on("click", "#pay_with_flaterwave", function() {
                $('#flaterwave-payment-form').ajaxForm(function(res) {

                    if (res.flag == 1) {
                        var amount = res.total_price;
                        var API_publicKey = '{{ $company_payment_setting['flutterwave_public_key'] }}';
                        var nowTim = "{{ date('d-m-Y-h-i-a') }}";
                        var flutter_callback = "{{ url('/invoice/flaterwave') }}";
                        var x = getpaidSetup({
                            PBFPubKey: API_publicKey,
                            customer_email: '{{ Auth::user()->email }}',
                            amount: res.total_price,
                            currency: '{{ App\Models\Utility::getValByName('site_currency') }}',
                            txref: nowTim + '__' + Math.floor((Math.random() * 1000000000)) +
                                'fluttpay_online-' + '{{ date('Y-m-d') }}' + '?amount=' + amount,
                            meta: [{
                                metaname: "payment_id",
                                metavalue: "id"
                            }],
                            onclose: function() {},
                            callback: function(response) {
                                var txref = response.tx.txRef;
                                if (
                                    response.tx.chargeResponseCode == "00" ||
                                    response.tx.chargeResponseCode == "0"
                                ) {
                                    window.location.href = flutter_callback + '/' + txref +
                                        '/' +
                                        '{{ \Illuminate\Support\Facades\Crypt::encrypt($invoice->id) }}';
                                } else {
                                    // redirect to a failure page.
                                }
                                x
                                    .close(); // use this to close the modal immediately after payment.
                            }
                        });
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', data.message, 'msg');
                    }

                }).submit();
            });
        @endif

        @if (isset($company_payment_setting['razorpay_public_key']))
            // Razorpay Payment
            $(document).on("click", "#pay_with_razorpay", function() {
                $('#razorpay-payment-form').ajaxForm(function(res) {
                    if (res.flag == 1) {
                        var amount = res.total_price;
                        var razorPay_callback = '{{ url('/invoice/razorpay') }}';
                        var totalAmount = res.total_price * 100;
                        var coupon_id = res.coupon;
                        var options = {
                            "key": "{{ $company_payment_setting['razorpay_public_key'] }}", // your Razorpay Key Id
                            "amount": totalAmount,
                            "name": 'Plan',
                            "currency": '{{ App\Models\Utility::getValByName('site_currency') }}',
                            "description": "",
                            "handler": function(response) {
                                window.location.href = razorPay_callback + '/' + response
                                    .razorpay_payment_id + '/' +
                                    '{{ \Illuminate\Support\Facades\Crypt::encrypt($invoice->id) }}' +
                                    '?amount=' + amount;
                            },
                            "theme": {
                                "color": "#528FF0"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.open();
                    } else if (res.flag == 2) {
                        toastrs('Error', res.msg, 'msg');
                    } else {
                        toastrs('Error', data.message, 'msg');
                    }

                }).submit();
            });
        @endif


        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            show_toastr('success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
    <script>
        $(document).on('click', '#shipping', function() {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: {
                    'is_display': is_display,
                },
                success: function(data) {
                    // console.log(data);
                }
            });
        })
    </script>
@endpush



@section('content')





    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{ __('Invoice') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">
                                        {{ AUth::user()->invoiceNumberFormat($invoice->invoice_id) }}</h4>
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
                                                {{ \Auth::user()->dateFormat($invoice->issue_date) }}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{ __('Delete Date') }} :</strong><br>
                                                {{ \Auth::user()->dateFormat($invoice->due_date) }}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                <small>{{ __('All items here cannot be deleted.') }}</small>
                                <div class="table-responsive mt-2">
                                    <table class="table mb-0 table-striped">
                                        <tr>
                                            <th data-width="40" class="text-dark">#</th>
                                            <th class="text-dark">{{ __('Product') }}</th>
                                            <th class="text-dark">{{ __('Sub Product') }}</th>
                                            <th class="text-dark">{{ __('Category') }}</th>
                                            <th class="text-dark">{{ __('Quantity') }}</th>
                                            <th class="text-dark">{{ __('Rate') }}</th>
                                            <th class="text-dark">{{ __('Discount') }}</th>
                                            <th class="text-dark">{{ __('Tax') }}</th>
                                            {{-- <th class="text-dark">{{ __('Description') }}</th> --}}
                                            <th class="text-end text-dark" width="12%">{{ __('Price') }}<br>
                                                <small
                                                    class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                            </th>
                                        </tr>
                                        @php
                                            $totalQuantity = 0;
                                            $totalRate = 0;
                                            $totalTaxPrice = 0;
                                            $totalDiscount = 0;
                                            $taxesData = [];
                                            $totalTaxPriceTotal = 0;
                                        @endphp
                                        @foreach ($iteams as $key => $iteam)
                                            {{-- @if (!empty($iteam->tax))
                                                    @php
                                                        $taxes = App\Models\Utility::tax($iteam->tax);
                                                        $totalQuantity += $iteam->quantity;
                                                        $totalRate += $iteam->price;
                                                        $totalDiscount += $iteam->discount;
                                                        foreach ($taxes as $taxe) {
                                                            $taxDataPrice = App\Models\Utility::taxRate($taxe->rate, $iteam->price, $iteam->quantity, $iteam->discount);
                                                            if (array_key_exists($taxe->name, $taxesData)) {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name] + $taxDataPrice;
                                                            } else {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif --}}
                                            <tr>
                                                <td>{{ $iteam->sub_product_id }}</td>
                                                @php
                                                    $productName = $iteam->product;
                                                    $sub_productName = \App\Models\SubProduct::where(
                                                        'id',
                                                        $iteam->sub_product_id,
                                                    )->first();
                                                    $totalRate +=$iteam->price * $iteam->quantity;
                                                    $totalQuantity +=
                                                        $sub_productName->productService->category->type ==
                                                        'Qty product'
                                                            ? $iteam->quantity
                                                            : 1;
                                                    $totalDiscount += $iteam->discount;
                                                    $product = \App\Models\ProductService::where(
                                                        'id',
                                                        $iteam->product_id,
                                                    )->first();
                                                    $sub_productName = \App\Models\SubProduct::where(
                                                        'id',
                                                        $iteam->sub_product_id,
                                                    )->first();
                                                @endphp
                                                @if ($product->brand !== null)
                                                    <td>{{ !empty($productName)
                                                        ? ($product->brand->name ?? 'No Brand') . '/' . ($product->subBrand->name ?? __('No Model')) . '/' . $productName->name
                                                        : ''
                                                    }}
                                                    </td>
                                                @else
                                                    <td> {{ !empty($productName) ? $productName->name : '' }}</td>
                                                @endif
                                                <td>{{ !empty($sub_productName) ? $sub_productName->product_no : '-' }}
                                                </td>
                                                <td> {{ !empty($product->category) ? $product->category->name : '' }}
                                                </td>
                                                <td>{{ $sub_productName->productService->category->type == 'Qty product'
                                                    ? $iteam->quantity . ' (' . $productName->unit->name . ')'
                                                    : 1 . ' (' . $productName->unit->name . ')' }}
                                                </td>
                                                <td>{{ \Auth::user()->priceFormat($iteam->price) }}
                                                </td>
                                                <td>{{ \Auth::user()->priceFormat($iteam->discount) }}</td>

                                                {{-- <td>
                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                    $totalTaxRate = 0;
                                                                @endphp
                                                                @foreach ($taxes as $tax)
                                                                    @php
                                                                        $taxPrice=App\Models\Utility::taxRate($tax->rate,$iteam->price,$iteam->quantity,$iteam->discount) ;
                                                                        $totalTaxPrice+=$taxPrice;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{$tax->name .' ('.$tax->rate .'%)'}}</td>
                                                                        <td>{{\Auth::user()->priceFormat($taxPrice)}}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        @else
                                                            -
                                                        @endif
                                                    </td> --}}

                                                <td>
                                                    @if (
                                                        !empty($invoice->tax_id) &&
                                                            \App\Models\ProductService::where('id', $iteam->product_id)->first()->type === 'product')
                                                        <table>
                                                            @php
                                                                $itemTaxes = [];
                                                                $getTaxData = Utility::getTaxData();
                                                                $totalTaxPrice = 0;
                                                                if (!empty($invoice->tax_id)) {
                                                                    foreach (explode(',', $invoice->tax_id) as $tax) {
                                                                        $taxPrice =
                                                                            $sub_productName->productService->category
                                                                                ->type == 'Qty product'
                                                                                ? \Utility::taxRate(
                                                                                    $getTaxData[$tax]['rate'],
                                                                                    ($iteam->price * $iteam->quantity) - $iteam->discount,
                                                                                    1,
                                                                                )
                                                                                : \Utility::taxRate(
                                                                                    $getTaxData[$tax]['rate'],
                                                                                    $iteam->price - $iteam->discount,
                                                                                    1,
                                                                                );
                                                                        $totalTaxPrice += $taxPrice;
                                                                        $totalTaxPriceTotal += $totalTaxPrice;
                                                                        $itemTax['name'] = $getTaxData[$tax]['name'];
                                                                        $itemTax['rate'] =
                                                                            $getTaxData[$tax]['rate'] . '%';
                                                                        $itemTax['price'] = \Auth::user()->priceFormat(
                                                                            $taxPrice,
                                                                        );

                                                                        $itemTaxes[] = $itemTax;
                                                                        if (
                                                                            array_key_exists(
                                                                                $getTaxData[$tax]['name'],
                                                                                $taxesData,
                                                                            )
                                                                        ) {
                                                                            $taxesData[$getTaxData[$tax]['name']] =
                                                                                $taxesData[$getTaxData[$tax]['name']] +
                                                                                $taxPrice;
                                                                        } else {
                                                                            $taxesData[
                                                                                $getTaxData[$tax]['name']
                                                                            ] = $taxPrice;
                                                                        }
                                                                    }
                                                                    $iteam->itemTax = $itemTaxes;
                                                                } else {
                                                                    $iteam->itemTax = [];
                                                                }
                                                            @endphp
                                                            @foreach ($iteam->itemTax as $tax)
                                                                <tr>
                                                                    <td>{{ $tax['name'] . ' (' . $tax['rate'] . ')' }}</td>
                                                                    <td>{{ $tax['price'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </table>
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                {{-- <td>{{ !empty($iteam->description) ? $iteam->description : '-' }}</td> --}}
                                                @if (\App\Models\ProductService::where('id', $iteam->product_id)->first()->type === 'product')
                                                    <td class="text-end">
                                                        {{ $sub_productName->productService->category->type == 'Qty product'
                                                            ? \Auth::user()->priceFormat(($iteam->price * $iteam->quantity) - $iteam->discount + $totalTaxPrice)
                                                            : \Auth::user()->priceFormat($iteam->price - $iteam->discount + $totalTaxPrice) }}
                                                    </td>
                                                @else
                                                    <td class="text-end">
                                                        {{ $sub_productName->productService->category->type == 'Qty product'
                                                            ? \Auth::user()->priceFormat($iteam->price * $iteam->quantity)
                                                            : \Auth::user()->priceFormat($iteam->price) }}
                                                    </td>
                                                @endif

                                            </tr>
                                        @endforeach
                                        @php
                                            $totalExpenseAmount = 0;
                                            @endphp
                                            @if (!empty($expenses))
                                            <tr>
                                                <th colspan="9" class="text-dark">{{ __('Additional Expenses') }}</th>
                                            </tr>

                                            @foreach ($expenses as $key => $expense)
                                                @php
                                                    $totalExpenseAmount += $expense->amount;
                                                @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td colspan="4" >{{ \App\Models\ChartOfAccount::where('id',$expense->account_id)->first()->name }}</td>
                                                    <td >{{ $expense->amount }}</td>
                                                    <td colspan="2">{{ $expense->description }}</td>
                                                    <td class="text-end">{{ \Auth::user()->priceFormat($expense->amount) }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        <tfoot>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td><b>{{ __('Total') }}</b></td>
                                                <td><b>{{ $totalQuantity }}</b></td>
                                                <td><b>{{ \Auth::user()->priceFormat($totalRate) }}</b></td>
                                                <td><b>{{ \Auth::user()->priceFormat($totalDiscount) }}</b></td>
                                                <td><b>{{ \Auth::user()->priceFormat($totalTaxPriceTotal) }}</b></td>
                                                <td class="text-end">
                                                    <b>{{ \Auth::user()->priceFormat($totalRate - $totalDiscount  + $totalTaxPriceTotal + $totalExpenseAmount) }}</b>
                                                </td>

                                            </tr>
                                            <tr>
                                                <td colspan="7"></td>
                                                <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                <td class="text-end">
                                                    {{ \Auth::user()->priceFormat($totalRate) }}</td>
                                                @if ($invoice->status != 0)
                                                    <td></td>
                                                @endif

                                            </tr>

                                            <tr>
                                                <td colspan="7"></td>
                                                <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                <td class="text-end">
                                                    {{ \Auth::user()->priceFormat($invoice->getTotalDiscount()) }}
                                                </td>
                                                @if ($invoice->status != 0)
                                                    <td></td>
                                                @endif
                                            </tr>

                                            @if (!empty($taxesData))
                                                @foreach ($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="7"></td>
                                                        <td class="text-end"><b>{{ $taxName }}</b></td>
                                                        <td class="text-end">
                                                            {{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                        @if ($invoice->status != 0)
                                                            <td></td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="7"></td>
                                                <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                <td class="blue-text text-end">
                                                    {{ \Auth::user()->priceFormat($totalTaxPriceTotal + ($totalRate - $totalDiscount + $totalExpenseAmount)) }}</td>
                                                @if ($invoice->status != 0)
                                                    <td></td>
                                                @endif
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
