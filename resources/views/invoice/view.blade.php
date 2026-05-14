@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    @if ($invoice->type === 'rent')
        <li class="breadcrumb-item"><a href="{{ route('rentinvoice.index') }}">{{ __('Rent Invoice') }}</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    @endif
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
                                Swal.fire({
                                    title: '{{ __('Payment Window Closed') }}',
                                    text: '{{ __('The payment window was closed.') }}',
                                    icon: 'info',
                                    confirmButtonText: '{{ __('OK') }}'
                                });
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openDatePrompt(invoiceId) {
            Swal.fire({
                title: 'Select Date',
                html: '<input type="date" id="selectedDate" class="swal2-input" />',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                preConfirm: () => {
                    const date = document.getElementById('selectedDate').value;
                    if (!date) {
                        Swal.showValidationMessage('Please select a date');
                    }
                    return date;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const date = result.value;
                    const url = `{{ route('invoice.sent', ':id') }}?date=${date}`.replace(':id', invoiceId);
                    window.location.href = url;
                }
            });
        }
    </script>
@endpush


@section('content')

    @can('send invoice')
        {{-- @if ($invoice->status != 4) --}}
        <div class="row">
            <div class="col-12">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card ">
                    <div class="card-body">
                        <div class="row timeline-wrapper">
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                                <h6 class="text-primary my-3">{{ __('Create Invoice') }}</h6>
                                <p class="text-muted text-sm mb-3"><i
                                        class="ti ti-clock mr-2"></i>{{ __('Created on ') }}{{ \Auth::user()->dateFormat($invoice->issue_date) }}
                                </p>
                                @if ($statusChangesSendToApprove)
                                    <p class="text-muted text-sm mb-3"><i
                                            class="ti ti-clock mr-2"></i>{{ __('Send To Approve on ') }}{{ $statusChangesSendToApprove ? \Auth::user()->dateFormat($statusChangesSendToApprove->changed_at) : '' }}
                                    </p>
                                @endif
                                @can('edit invoice')
                                    @if ($invoice->status == 0)
                                        <a href="{{ route('invoice.edit', \Crypt::encrypt($invoice->id)) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Edit') }}"><i
                                                class="ti ti-pencil mr-2"></i>{{ __('Edit') }}</a>
                                        @can('edit invoice')
                                            <a href="{{ route('invoice.addSubProducts', $invoice->id) }}"
                                                class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                data-original-title="{{ __('Edit Products') }}"><i
                                                    class="ti ti-pencil mr-2"></i>{{ __('Edit Products') }}</a>
                                        @endcan
                                        <br />
                                        @can('send to approve invoice')
                                            <a href="{{ route('invoice.sendtoapprove', $invoice->id) }}"
                                                class="btn btn-sm btn-secondary mt-2" data-bs-toggle="tooltip"
                                                data-original-title="{{ __('Send To Approve') }}"><i
                                                    class="ti ti-send mr-2"></i>{{ __('Send To Approve') }}</a>
                                        @endcan
                                    @endif
                                @endcan
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-accessible text-warning"></i>
                                </div>
                                <h6 class="text-warning my-3">{{ __('Approved Invoice') }}</h6>
                                <p class="text-muted text-sm mb-3">
                                    @if ($invoice->status === 0 || $invoice->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Approved') }}</small>
                                    @elseif ($statusChangesApprove)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Approved on ') }}{{ $statusChangesApprove ? \Auth::user()->dateFormat($statusChangesApprove->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($invoice->status === 1)
                                    @can('approve invoice')
                                        <a href="{{ route('invoice.approve', $invoice->id) }}" class="btn btn-sm btn-warning"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Approved') }}"><i
                                                class="ti ti-user-exclamation mr-2"></i>{{ __('Approved') }}</a>
                                    @endcan
                                    @can('approve invoice')
                                        <a href="{{ route('invoice.notapprove', $invoice->id) }}" class="btn btn-sm btn-danger"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Not Approved') }}"><i
                                                class="ti ti-user-x mr-2"></i>{{ __('Not Approved') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-send text-danger"></i>
                                </div>
                                <h6 class="text-danger my-3">{{ __('Send Invoice') }}</h6>
                                <p class="text-muted text-sm mb-3">
                                    @if ($invoice->status === 2 || $invoice->status === 0 || $invoice->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Sent') }}</small>
                                    @elseif ($statusChangesSend)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Send on ') }}{{ $statusChangesSend ? \Auth::user()->dateFormat($statusChangesSend->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($invoice->status === 2)
                                    @can('send invoice')
                                        <a href="javascript:void(0);" onclick="openDatePrompt({{ $invoice->id }})"
                                            class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Mark Sent') }}">
                                            <i class="ti ti-send mr-2"></i>{{ __('Send') }}
                                        </a>
                                    @endcan
                                    @can('send invoice')
                                        <a href="{{ route('invoice.backtoapprove', $invoice->id) }}" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Not Sent') }}"><i
                                                class="ti ti-rotate-clockwise mr-2"></i>{{ __('Back To Approve') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-mail text-info"></i>
                                </div>

                                <h6 class="text-info my-3">{{ __('Delivered Invoice') }}</h6>

                                <p class="text-muted text-sm mb-3">
                                    @if ($invoice->status == 0 || $invoice->status == 2 || $invoice->status == 4 || $invoice->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Delivered') }}</small>
                                    @elseif ($invoice->status == 6)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Delivered on ') }}{{ $statusChangesReceived ? \Auth::user()->dateFormat($statusChangesReceived->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($invoice->status == 4)
                                    @can('receive invoice')
                                        <a href="{{ route('invoice.receive', $invoice->id) }}" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Delivered') }}"><i
                                                class="ti ti-send mr-2"></i>{{ __('Delivered') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4">
                                @if ($invoice->payment_status == 0)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-secondary"></i>
                                    </div>
                                    <h6 class="text-secondary my-3">{{ __('Get Paid') }}</h6>
                                    <p class="text-muted text-sm mb-3">{{ __('Status') }} : {{ __('Awaiting payment') }}
                                    </p>
                                    @can('create payment invoice')
                                        <a href="#" data-url="{{ route('invoice.payment', $invoice->id) }}"
                                            data-ajax-popup="true" data-title="{{ __('Add Payment') }}"
                                            class="btn btn-sm btn-secondary" data-original-title="{{ __('Add Payment') }}"><i
                                                class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}</a> <br>
                                    @endcan
                                @endif
                                @if ($invoice->payment_status == 2)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{ __('Get Paid') }}</h6>
                                    <p class="text-warning  text-sm mb-3">{{ __('Status') }} : {{ __('Partialy Paid') }}
                                    </p>
                                    @can('create payment invoice')
                                        <a href="#" data-url="{{ route('invoice.payment', $invoice->id) }}"
                                            data-ajax-popup="true" data-title="{{ __('Add Payment') }}"
                                            class="btn btn-sm btn-warning" data-original-title="{{ __('Add Payment') }}"><i
                                                class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}</a> <br>
                                    @endcan
                                @endif
                                @if ($invoice->payment_status == 4)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{ __('Paid') }}</h6>
                                    <p class="text-primary text-sm mb-3">{{ __('Status') }} : {{ __('Paid') }} </p>
                                    {{-- @can('create payment invoice')
                                            <a href="#" data-url="{{ route('invoice.payment', $invoice->id) }}"
                                                data-ajax-popup="true" data-title="{{ __('Add Payment') }}"
                                                class="btn btn-sm btn-info" data-original-title="{{ __('Add Payment') }}"><i
                                                    class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}</a> <br>
                                        @endcan --}}
                                @endif
                                @php
                                    $totalAmount = $invoice->getTotal() + $invoice->salik_amount;
                                    $customerBalance = \App\Models\Customer::where('id', $invoice->customer_id)->first()
                                        ->balance;
                                @endphp

                                @if ($customerBalance < 0 && abs($customerBalance) > $totalAmount)
                                    @can('create payment invoice')
                                        <br />
                                        <a href="{{ route('invoice.userpayment', $invoice->id) }}"
                                            data-title="{{ __('Add Payment') }}" class="btn btn-sm btn-danger"
                                            data-original-title="{{ __('Pay From User Balance') }}">
                                            <i class="ti ti-report-money mr-2"></i>{{ __('Pay From User Balance') }}
                                        </a>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- @endif --}}
    @endcan

    @if (Gate::check('show invoice') || Gate::check('view invoice'))
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end flex-wrap gap-2">
                @if ($invoice->status != 0)
                    @if (!empty($invoicePayment))
                        <div class="all-button-box mx-2 mr-2">
                            <a href="#" class="btn btn-sm btn-primary"
                                data-url="{{ route('invoice.credit.note', $invoice->id) }}" data-ajax-popup="true"
                                data-title="{{ __('Add Credit Note') }}">
                                {{ __('Add Credit Note') }}
                            </a>
                        </div>
                    @endif
                    @if ($invoice->status != 4)
                        <div class="all-button-box mr-2">
                            <a href="{{ route('invoice.payment.reminder', $invoice->id) }}"
                                class="btn btn-sm btn-primary me-2">{{ __('Receipt Reminder') }}</a>
                        </div>
                    @endif
                    <div class="all-button-box mr-2">
                        <a href="{{ route('invoice.resent', $invoice->id) }}"
                            class="btn btn-sm btn-primary me-2">{{ __('Resend Invoice') }}</a>
                    </div>
                @endif
                <div class="all-button-box">
                    <a href="{{ route('invoice.pdf', Crypt::encrypt($invoice->id)) }}" target="_blank"
                        class="btn btn-sm btn-primary">{{ __('Download') }}</a>
                </div>
                <div class="all-button-box ms-2">
                    <a href="{{ route('invoice.pdf', ['id' => Crypt::encrypt($invoice->id), 'show_custom_fields' => 1]) }}" target="_blank"
                        class="btn btn-sm btn-info">{{ __('Download + Custom Fields') }}</a>
                </div>
                @if ($invoice->status != 0)
                    <div class="all-button-box ms-2">
                        <a href="{{ route('invoice.print.grouped', Crypt::encrypt($invoice->id)) }}" target="_blank"
                            class="btn btn-sm btn-secondary">{{ __('Print Grouped Invoice') }}</a>
                    </div>
                    <div class="all-button-box ms-2">
                        <a href="{{ route('invoice.items.export', Crypt::encrypt($invoice->id)) }}"
                            class="btn btn-sm btn-success">{{ __('Export Items Excel') }}</a>
                    </div>
                    <div class="all-button-box mr-2 ml-2" style="margin-left: 10px;">
                        <a href="{{ route('invoice.ledger', $invoice->id) }}" target="_blank"
                            class="btn btn-sm btn-primary">{{ __('Show Accounting') }}</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
    <div class="card ">
        <div class="card-body employee-detail-body fulls-card">
            <h5>{{ __('Document Detail') }}</h5>
            <hr>
            <div class="row">
                @php
                    $documentPath = \App\Models\Utility::get_file('uploads/document');
                @endphp
                @if ($invoice->accountingDocuments()->count() > 0)
                    <div class="col-md-6">
                        <div class="info text-sm">
                            <strong class="font-bold">Documents:</strong>
                            <ul>
                                @foreach ($invoice->accountingDocuments as $document)
                                    <li>
                                        <a href="{{ URL::to('/') . '/' . $document->document_path }}"
                                            target="_blank">{{ $document->document_name }}</a>
                                        <div class="action-btn bg-danger ms-2">
                                            <form method="POST"
                                                action="{{ route('invoice.file.destroy', $document->id) }}"
                                                id="delete-form-{{ $document->id }}">
                                                @csrf
                                                @method('POST')
                                                <input type="hidden" name="document_id" value="{{ $document->id }}">
                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                        class="ti ti-trash text-white"></i></a>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#fileModal">Add
                            Document</button>
                    </div>
                @endif

            </div>
        </div>
    </div>
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
                                                <strong>{{ __('Due Date') }} :</strong><br>
                                                {{ \Auth::user()->dateFormat($invoice->due_date) }}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($customer->name))
                                            {{ !empty($customer->name) ? $customer->name : '' }}<br>
                                            {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}<br>
                                            {{ !empty($customer->billing_city) ? $customer->billing_city : '' . ', ' }}<br>
                                            {{ !empty($customer->billing_state) ? $customer->billing_state : '' . ', ' }},
                                            {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}<br>
                                            {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}<br>
                                            {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif

                                    </small>
                                </div>

                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col ">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            @if (!empty($customer->shipping_name))
                                                {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}<br>
                                                {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}<br>
                                                {{ !empty($customer->shipping_city) ? $customer->shipping_city : '' . ', ' }}<br>
                                                {{ !empty($customer->shipping_state) ? $customer->shipping_state : '' . ', ' }},
                                                {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}<br>
                                                {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}<br>
                                                {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif
                                @if ($invoice->type == 'rent')
                                    <div class="col ">
                                        <small>
                                            <strong>{{ __('Driver') }} :</strong><br>
                                            @if (!empty($driver->name))
                                                {{ !empty($driver->name) ? $driver->name : '' }}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif
                                <div class="col ">
                                    <small>
                                        <strong>{{ __('Bank') }} :</strong><br>
                                        @if (!empty($invoice->bank_account_id) && $invoice->bankAccount)
                                            {{ $invoice->bankAccount->holder_name . ' ' . $invoice->bankAccount->bank_name . ' ' . $invoice->bankAccount->bank_details }}
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML(
                                            route('invoice.link.copy', \Illuminate\Support\Facades\Crypt::encrypt($invoice->id)),
                                            'QRCODE',
                                            2,
                                            2,
                                        ) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} :</strong><br>
                                        @if ($invoice->status == 0)
                                            <span
                                                class="badge bg-primary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 1)
                                            <span
                                                class="badge bg-warning">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 2)
                                            <span
                                                class="badge bg-danger">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 3)
                                            <span
                                                class="badge bg-info">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 4)
                                            <span
                                                class="badge bg-primary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @elseif($invoice->status == 6)
                                            <span
                                                class="badge bg-primary">{{ __(\App\Models\Invoice::$statues[$invoice->status]) }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Currency') }} :</strong><br>
                                        @if ($invoice->currency_id != null)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ $invoice->currency->name }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ $settings['site_currency'] }}</span>
                                        @endif
                                    </small>
                                </div>

                                @if (!empty($customFields) && count($invoice->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-md-right">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($invoice->customField) ? $invoice->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Product Summary') }}</div>
                                    <small>{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 table-striped">
                                            <thead>
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
                                                    <th class="text-end text-dark" width="12%">
                                                        {{ __('Price') }}<br>
                                                        <small
                                                            class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                                    </th>
                                                    @if ($invoice->status != 0)
                                                        <th>{{ __('Action') }}</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            @php
                                                $totalQuantity = 0;
                                                $totalRate = 0;
                                                $totalTaxPrice = 0;
                                                $totalDiscount = 0;
                                                $taxesData = [];
                                                $totalTaxPriceTotal = 0;
                                            @endphp
                                            @foreach ($iteams as $key => $iteam)
                                                <tr>
                                                    <td>{{ $iteam->sub_product_id }}</td>
                                                    @php
                                                        $productName = $iteam->product;
                                                        $itemTaxTotal = 0;
                                                        $totalRate +=
                                                            \App\Models\SubProduct::where(
                                                                'id',
                                                                $iteam->sub_product_id,
                                                            )->first()->productService->category->type == 'Qty product'
                                                                ? $iteam->price * $iteam->quantity
                                                                : $iteam->price;
                                                        $subForTotals = \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first();
                                                        $isQtyProductForTotals = $subForTotals && $subForTotals->productService && $subForTotals->productService->category && $subForTotals->productService->category->type == 'Qty product';
                                                        $totalQuantity += $isQtyProductForTotals ? $iteam->quantity : 1;
                                                        $totalDiscount += $iteam->discount;
                                                        $product = \App\Models\ProductService::where('id', $iteam->product_id)->first();
                                                        $sub_productName = \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first();
                                                        $brandName = $product && $product->brand ? $product->brand->name : null;
                                                        $subBrandName = $product && $product->subBrand ? $product->subBrand->name : null;
                                                        $categoryName = $product && $product->category ? $product->category->name : null;
                                                        $unitName = $productName && $productName->unit ? $productName->unit->name : '';
                                                        $subCategoryType = null;
                                                        if ($sub_productName && $sub_productName->productService && $sub_productName->productService->category) {
                                                            $subCategoryType = $sub_productName->productService->category->type;
                                                        }
                                                    @endphp
                                                    <td>
                                                        @if (!empty($productName))
                                                            @php
                                                                $parts = [];
                                                                $parts[] = $brandName ?? 'No Brand';
                                                                $parts[] = $subBrandName ?? __('No Model');
                                                                $parts[] = $productName->name;
                                                            @endphp
                                                            {{ implode(' / ', $parts) }}
                                                        @else
                                                            {{ $productName->name ?? '' }}
                                                        @endif
                                                    </td>
                                                    <td>{{ !empty($sub_productName) ? $sub_productName->product_no : '-' }}</td>
                                                    <td>{{ $categoryName ?? '' }}</td>
                                                    <td>
                                                        @if ($subCategoryType === 'Qty product')
                                                            {{ $iteam->quantity }} {{ $unitName ? '(' . $unitName . ')' : '' }}
                                                        @else
                                                            {{ 1 }} {{ $unitName ? '(' . $unitName . ')' : '' }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($iteam->exchange_price, $invoice->currency->symbol) : \Auth::user()->priceFormat($iteam->price) }}
                                                    </td>
                                                    <td>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($iteam->exchange_discount, $invoice->currency->symbol) : \Auth::user()->priceFormat($iteam->discount) }}
                                                    </td>
                                                    <td>
                                                        @if (
                                                            !empty($invoice->tax_id) &&
                                                                \App\Models\ProductService::where('id', $iteam->product_id)->first()->type === 'product')
                                                            <table>
                                                                @php
                                                                    $itemTaxes = [];
                                                                    $getTaxData = Utility::getTaxData();
                                                                    $itemTaxTotal = 0;
                                                                    $taxtype = '';
                                                                    if (!empty($invoice->tax_id)) {
                                                                        foreach (
                                                                            explode(',', $invoice->tax_id)
                                                                            as $tax
                                                                        ) {
                                                                            $taxPrice = \Utility::taxRate(
                                                                                $getTaxData[$tax]['rate'],
                                                                                $invoice->currency_id != null
                                                                                    ? $iteam->exchange_price -
                                                                                        $iteam->exchange_discount
                                                                                    : $iteam->price - $iteam->discount,
                                                                                $iteam->quantity,
                                                                            );
                                                                            $itemTaxTotal += $taxPrice;
                                                                            $totalTaxPriceTotal += $taxPrice;
                                                                            $itemTax['name'] =
                                                                                $getTaxData[$tax]['name'];
                                                                            $itemTax['rate'] =
                                                                                $getTaxData[$tax]['rate'] . '%';
                                                                            $itemTax['price'] =
                                                                                $invoice->currency_id != null
                                                                                    ? \Auth::user()->priceFormatCurr(
                                                                                        $taxPrice,
                                                                                        $invoice->currency->symbol,
                                                                                    )
                                                                                    : \Auth::user()->priceFormat(
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
                                                                                    $taxesData[
                                                                                        $getTaxData[$tax]['name']
                                                                                    ] + $taxPrice;
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

                                                    {{-- <td>{{ !empty($iteam->description) ? $iteam->description : '-' }}</td> --}}

                                                    @if (\App\Models\ProductService::where('id', $iteam->product_id)->first()->type === 'product')
                                                        <td class="text-end">
                                                            {{ $invoice->currency_id != null
                                                                ? \Auth::user()->priceFormatCurr(
                                                                    ($iteam->exchange_price - $iteam->exchange_discount) * $iteam->quantity + $itemTaxTotal,
                                                                    $invoice->currency->symbol,
                                                                )
                                                                : \Auth::user()->priceFormat($iteam->price - $iteam->discount + $itemTaxTotal) }}
                                                        </td>
                                                    @else
                                                        <td class="text-end">
                                                            {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr(($iteam->price - $iteam->discount) / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($iteam->price - $iteam->discount) }}
                                                        </td>
                                                    @endif
                                                    @if (
                                                        ($invoice->status == 4 || $invoice->status == 6) &&
                                                            \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first()->invoice_id == $invoice->id &&
                                                            \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first()->booked != 3)
                                                        <td class="Action">
                                                            {{-- <div class="action-btn bg-info ms-2">
                                                                <form method="POST"
                                                                    action="{{ route('sub-product.sent', $iteam->sub_product_id) }}"
                                                                    id="delete-form-{{ $iteam->sub_product_id }}">
                                                                    @csrf
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('Edit') }}"
                                                                        data-confirm="{{ __('Are You Sure?') }}"><i
                                                                            class="ti ti-edit text-white"></i></a>
                                                                </form>
                                                            </div> --}}

                                                            {{-- @can('delete invoice product')
                                                                <div class="action-btn bg-danger ms-2">
                                                                    @php
                                                                        $subProduct = \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first();
                                                                        $categoryType = null;
                                                                        if ($subProduct && $subProduct->productService && $subProduct->productService->category) {
                                                                            $categoryType = $subProduct->productService->category->type;
                                                                        }
                                                                    @endphp
                                                                    <form method="POST"
                                                                        action="{{ route('sub-product.deleteinvoice', $iteam->sub_product_id) }}"
                                                                        id="delete-form-{{ $iteam->sub_product_id }}">
                                                                        @method('DELETE')
                                                                        @csrf
                                                                        <input type="hidden" name="delete_date"
                                                                            id="delete-date-{{ $iteam->sub_product_id }}">
                                                                        <input type="hidden" name="delete_qty"
                                                                            id="delete-qty-{{ $iteam->sub_product_id }}">
                                                                        <a href="#"
                                                                            class="mx-3 btn btn-sm align-items-center"
                                                                            data-bs-toggle="tooltip"
                                                                            title="{{ __('Delete') }}"
                                                                            onclick="confirmDelete(event, {{ $iteam->sub_product_id }}, '{{ $categoryType }}')"><i
                                                                                class="ti ti-trash text-white"></i></a>
                                                                    </form>
                                                                </div>
                                                            @endcan --}}

                                                        </td>
                                                    @elseif(\App\Models\SubProduct::where('id', $iteam->sub_product_id)->first()->booked == 3 && $invoice->status == 6)
                                                        <td>Delivered</td>
                                                    @elseif(($invoice->status == 4 || $invoice->status == 6) && \App\Models\SubProduct::where('id', $iteam->sub_product_id)->first()->booked == 0 &&
                                                            $invoice->type == 'rent')
                                                        <td>rental has been completed</td>
                                                    @elseif (($invoice->status == 4 || $invoice->status == 6) && \App\Models\InvoiceProduct::where('sub_product_id', $iteam->sub_product_id)->first()->booked == 0)
                                                        <td>Deleted</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                            @if (method_exists($iteams, 'links'))
                                                <tr>
                                                    <td colspan="{{ $invoice->status != 0 ? 10 : 9 }}">
                                                        <div class="d-flex justify-content-end mt-2">
                                                            {{ $iteams->links() }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                            <!-- Expenses Section -->
                                            @php
                                                $totalExpenseAmount = 0;
                                            @endphp
                                            @if ($expenses->isNotEmpty())
                                                <tr>
                                                    <th colspan="9" class="text-dark">{{ __('Additional Expenses') }}
                                                    </th>
                                                </tr>

                                                @foreach ($expenses as $key => $expense)
                                                    @php
                                                        // Use amount_in_currency if available, otherwise use AED amount
                                                        $expenseAmount =
                                                            $expense->amount_in_currency ?? $expense->amount;
                                                        $totalExpenseAmount += $expenseAmount;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td colspan="4">
                                                            {{ \App\Models\ChartOfAccount::where('id', $expense->account_id)->first()->name }}
                                                        </td>
                                                        <td>{{ $expense->currency_id != null ? \Auth::user()->priceFormatCurr($expense->amount_in_currency, $expense->currency->symbol) : \Auth::user()->priceFormat($expense->amount) }}
                                                        </td>
                                                        <td colspan="2">{{ $expense->description }}</td>
                                                        <td class="text-end">
                                                            {{ $expense->currency_id != null ? \Auth::user()->priceFormatCurr($expense->amount_in_currency, $expense->currency->symbol) : \Auth::user()->priceFormat($expense->amount) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tfoot>
                                                <tr>
                                                    <td><b>{{ __('Total') }}</b></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($totalRate / $invoice->exchange_rate + $totalExpenseAmount, $invoice->currency->symbol) : \Auth::user()->priceFormat($totalRate + $totalExpenseAmount) }}</b>
                                                    </td>
                                                    <td><b>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($totalDiscount / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($totalDiscount) }}</b>
                                                    </td>
                                                    <td><b>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($totalTaxPriceTotal, $invoice->currency->symbol) : \Auth::user()->priceFormat($totalTaxPriceTotal) }}</b>
                                                    </td>
                                                    <td class="text-end">
                                                        <b>{{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr(($totalRate - $totalDiscount) / $invoice->exchange_rate + $totalTaxPriceTotal + $totalExpenseAmount, $invoice->currency->symbol) : \Auth::user()->priceFormat($totalRate - $totalDiscount + $totalTaxPriceTotal + $totalExpenseAmount) }}</b>
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($invoice->getSubTotal() / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->getSubTotal()) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif

                                                </tr>
                                                @if ($invoice->type == 'rent')
                                                    <tr>
                                                        <td colspan="7"></td>
                                                        <td class="text-end"><b>{{ __('Number of Dayes') }}</b></td>
                                                        <td class="text-end">
                                                            {{ $invoice->getDaysDifferenceAttribute() }}</td>
                                                        @if ($invoice->status != 0)
                                                            <td></td>
                                                        @endif

                                                    </tr>
                                                @endif

                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($invoice->getTotalDiscount() / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->getTotalDiscount()) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                @if (!empty($taxesData))
                                                    {{-- @foreach ($taxesData as $taxName => $taxPrice) --}}
                                                    <tr>
                                                        <td colspan="7"></td>
                                                        <td class="text-end">
                                                            <b>{{ $tax['name'] . ' (' . $tax['rate'] . ')' }}</b>
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($totalTaxPriceTotal, $invoice->currency->symbol) : \Auth::user()->priceFormat($totalTaxPriceTotal) }}
                                                        </td>
                                                        @if ($invoice->status != 0)
                                                            <td></td>
                                                        @endif
                                                    </tr>
                                                    {{-- @endforeach --}}
                                                @endif
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($invoice->getTotal() / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->getTotal()) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr(($invoice->getTotal() - $invoice->getDue() - $invoice->invoiceTotalCreditNote()) / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->getTotal() - $invoice->getDue() - $invoice->invoiceTotalCreditNote()) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Refund') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($refundTotal, $invoice->currency->symbol) : \Auth::user()->priceFormat($refundTotal) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Credit Note') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr($invoice->invoiceTotalCreditNote() / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->invoiceTotalCreditNote()) }}
                                                    </td>
                                                    @if ($invoice->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td class="text-end"><b>{{ __('Due') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $invoice->currency_id != null ? \Auth::user()->priceFormatCurr(($invoice->getDue() + $refundTotal) / $invoice->exchange_rate, $invoice->currency->symbol) : \Auth::user()->priceFormat($invoice->getDue() + $refundTotal) }}
                                                    </td>
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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class=" d-inline-block">{{ __('Receipt Summary') }}</h5><br>
                    @if ($user_plan->storage_limit <= $invoice_user->storage_limit)
                        <small
                            class="text-danger font-bold">{{ __('Your plan storage limit is over , so you can not see customer uploaded payment receipt') }}</small><br>
                    @endif

                    <div class="table-responsive mt-3">
                        <table class="table ">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('ID') }}</th>
                                    <th class="text-dark">{{ __('Payment Receipt') }}</th>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Currency') }}</th>
                                    <th class="text-dark">{{ __('Amount in AED') }}</th>
                                    <th class="text-dark">{{ __('Amount in Invoice Currency') }}</th>
                                    <th class="text-dark">{{ __('Payment Type') }}</th>
                                    <th class="text-dark">{{ __('Account') }}</th>
                                    <th class="text-dark">{{ __('Reference') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    <th class="text-dark">{{ __('Receipt') }}</th>
                                    <th class="text-dark">{{ __('OrderId') }}</th>
                                    @can('delete payment invoice')
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endcan
                                </tr>
                            </thead>

                            @if (!empty($invoice->payments) && $invoice->bankPayments)
                                @php
                                    $path = \App\Models\Utility::get_file('uploads/order');
                                @endphp

                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        @php
                                            $currencySymbol =
                                                $invoice && $invoice->currency
                                                    ? $invoice->currency->symbol
                                                    : Auth::user()->currencySymbol();
                                        @endphp
                                        <td>
                                            {{ $payment->payment_id ? \App\Models\CustomerPayment::formatLabelForId($payment->payment_id) : 'N/A' }}
                                        </td>
                                        <td>
                                            @if (!empty($payment->add_receipt))
                                                <a href="{{ asset(Storage::url('uploads/payment')) . '/' . $payment->add_receipt }}"
                                                    download="" class="btn btn-sm btn-secondary btn-icon rounded-pill"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-download"></i></span></a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ $payment->currency && $payment->currency_rate > 0 ? Auth::user()->priceFormatCurr($payment->amount / $payment->currency_rate, $payment->currency->symbol) . ' (' . $payment->currency_rate . ')' : Auth::user()->priceFormat($payment->amount) }}
                                        </td>
                                        <td>
                                            {{ $payment->currency->name ?? \Auth::user()->currencySymbol() }}
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($payment->amount) }}
                                        </td>
                                        <td>{{ $payment->amount_in_currency
                                            ? Auth::user()->priceFormatCurr($payment->amount_in_currency, $currencySymbol)
                                            : '-' }}
                                        </td>
                                        <td>{{ $payment->payment_type }}</td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '--' }}
                                        </td>
                                        <td>{{ !empty($payment->reference) ? $payment->reference : '--' }}</td>
                                        <td>{{ !empty($payment->description) ? $payment->description : '--' }}</td>
                                        @if ($user_plan->storage_limit <= $invoice_user->storage_limit)
                                            <td>
                                                --
                                            </td>
                                        @else
                                            <td>
                                                @if (!empty($payment->receipt))
                                                    <a href="{{ $path . '/' . $payment->receipt }}" target="_blank">
                                                        <i class="ti ti-file"></i>{{ __('Receipt') }}</a>
                                                @elseif(!empty($payment->add_receipt))
                                                    <a href="{{ asset(Storage::url('uploads/payment')) . '/' . $payment->add_receipt }}"
                                                        target="_blank">
                                                        <i class="ti ti-file"></i>{{ __('Receipt') }}</a>
                                                @else
                                                    --
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ !empty($payment->order_id) ? $payment->order_id : '--' }}</td>
                                        @can('delete invoice product')
                                            <td>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form method="POST"
                                                        action="{{ route('invoice.payment.destroy', [$invoice->id, $payment->id]) }}"
                                                        id="delete-form-{{ $payment->id }}">
                                                        @method('POST')
                                                        @csrf

                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="Delete"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $payment->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach

                                {{--  start for bank transfer --}}
                                @foreach ($invoice->bankPayments as $key => $bankPayment)
                                    <tr>
                                        <td>-</td>
                                        <td>{{ \Auth::user()->dateFormat($bankPayment->date) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($bankPayment->amount) }}</td>
                                        <td>{{ __('Bank Transfer') }}<br>
                                        </td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>

                                        @if ($user_plan->storage_limit <= $invoice_user->storage_limit)
                                            <td>
                                                ---
                                            </td>
                                        @else
                                            <td>
                                                @if (!empty($bankPayment->receipt))
                                                    <a href="{{ $path . '/' . $bankPayment->receipt }}" target="_blank">
                                                        <i class="ti ti-file"></i> {{ __('Receipt') }}
                                                    </a>
                                                @endif

                                            </td>
                                        @endif
                                        <td>{{ !empty($bankPayment->order_id) ? $bankPayment->order_id : '--' }}</td>
                                        @can('delete invoice product')
                                            <td>
                                                @if ($bankPayment->status == 'Pending')
                                                    <div class="action-btn bg-warning">
                                                        <a href="#"
                                                            data-url="{{ URL::to('invoice/' . $bankPayment->id . '/action') }}"
                                                            data-size="lg" data-ajax-popup="true"
                                                            data-title="{{ __('Payment Status') }}"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Payment Status') }}"
                                                            data-original-title="{{ __('Payment Status') }}">
                                                            <i class="ti ti-caret-right text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                <div class="action-btn bg-danger ms-2">
                                                    <form method="POST"
                                                        action="{{ route('invoice.payment.destroy', [$invoice->id, $bankPayment->id]) }}"
                                                        id="delete-form-{{ $bankPayment->id }}">
                                                        @method('DELETE')
                                                        @csrf

                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="Delete"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $bankPayment->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                                {{--  end for bank transfer --}}
                            @else
                                <tr>
                                    <td colspan="{{ Gate::check('delete invoice product') ? '10' : '9' }}"
                                        class="text-center text-dark">
                                        <p>{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Credit Note Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Currency') }}</th>
                                    <th class="text-dark">{{ __('Amount in AED') }}</th>
                                    <th class="text-dark">{{ __('Amount in Invoice Currency') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    @if (Gate::check('edit credit note') || Gate::check('delete credit note'))
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            @forelse($invoice->creditNote as $key =>$creditNote)
                                <tr>
                                    @php
                                        // Use invoice currency symbol (not the credit note currency)
                                        $currencySymbol = $invoice->currency
                                            ? $invoice->currency->symbol
                                            : Auth::user()->currencySymbol();
                                    @endphp
                                    <td>{{ \Auth::user()->dateFormat($creditNote->date) }}</td>
                                    <td>{{ $creditNote->currency && $creditNote->currency_rate > 0 ? Auth::user()->priceFormatCurr($creditNote->amount / $creditNote->currency_rate, $creditNote->currency->symbol) . ' (' . $creditNote->currency_rate . ')' : Auth::user()->priceFormat($creditNote->amount) }}
                                    </td>
                                    <td>{{ $creditNote->currency ? $creditNote->currency->name : \Auth::user()->currencySymbol() }}
                                    </td>
                                    <td>{{ \Auth::user()->priceFormat($creditNote->amount) }}</td>
                                    <td>{{ $creditNote->amount_in_currency
                                        ? Auth::user()->priceFormatCurr($creditNote->amount_in_currency, $currencySymbol)
                                        : '-' }}
                                    </td>
                                    <td>{{ $creditNote->description }}</td>
                                    <td>
                                        @can('edit credit note')
                                            <div class="action-btn bg-primary ms-2">
                                                <a data-url="{{ route('invoice.edit.credit.note', [$creditNote->invoice, $creditNote->id]) }}"
                                                    data-ajax-popup="true" title="{{ __('Edit') }}"
                                                    data-original-title="{{ __('Credit Note') }}" href="#"
                                                    class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                    data-original-title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('delete credit note')
                                            <div class="action-btn bg-danger ms-2">
                                                <form method="POST"
                                                    action="{{ route('invoice.delete.credit.note', [$creditNote->invoice, $creditNote->id]) }}"
                                                    id="delete-form-{{ $creditNote->id }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para "
                                                        data-bs-toggle="tooltip" title="Delete"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $creditNote->id }}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                </form>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <p class="text-dark">{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Refund Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Currency') }}</th>
                                    <th class="text-dark">{{ __('Amount in AED') }}</th>
                                    <th class="text-dark">{{ __('Amount in Invoice Currency') }}</th>
                                    <th class="text-dark">{{ __('Account') }}</th>
                                    <th class="text-dark">{{ __('Reference') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                </tr>
                            </thead>
                            @forelse($invoice->refunds as $key =>$refund)
                                <tr>
                                    @php
                                        $currencySymbol = $refund->currency
                                            ? $refund->currency->symbol
                                            : Auth::user()->currencySymbol();
                                    @endphp
                                    <td>{{ \Auth::user()->dateFormat($refund->date) }}</td>
                                    <td>{{ $refund->currency && $refund->currency_rate > 0
                                        ? Auth::user()->priceFormatCurr($refund->amount / $refund->currency_rate, $refund->currency->symbol) .
                                            ' (' .
                                            $refund->currency_rate .
                                            ')'
                                        : ($refund->currency
                                            ? Auth::user()->priceFormatCurr($refund->amount, $refund->currency->symbol)
                                            : Auth::user()->priceFormat($refund->amount)) }}
                                    </td>
                                    <td>{{ $refund->currency ? $refund->currency->name : \Auth::user()->currencySymbol() }}
                                    </td>
                                    <td>{{ \Auth::user()->priceFormat($refund->amount) }}</td>
                                    <td>
                                        @php
                                            // Calculate refund amount in invoice currency
                                            $refundInInvoiceCurrency = 0;
                                            if ($refund->currency_id == $invoice->currency_id) {
                                                // Same currency as invoice
                                                $refundInInvoiceCurrency = $refund->amount_in_currency;
                                            } else {
                                                // Different currency, convert AED amount to invoice currency
                                                if ($invoice->currency_id && $invoice->exchange_rate > 0) {
                                                    $refundInInvoiceCurrency =
                                                        $refund->amount / $invoice->exchange_rate;
                                                } else {
                                                    $refundInInvoiceCurrency = $refund->amount;
                                                }
                                            }
                                            $invoiceCurrencySymbol = $invoice->currency
                                                ? $invoice->currency->symbol
                                                : Auth::user()->currencySymbol();
                                        @endphp
                                        {{ $refundInInvoiceCurrency > 0
                                            ? Auth::user()->priceFormatCurr($refund->amount_in_currency, $invoiceCurrencySymbol)
                                            : '-' }}
                                    </td>
                                    <td>{{ !empty($refund->bankAccount) ? $refund->bankAccount->bank_name . ' ' . $refund->bankAccount->holder_name : '' }}
                                    </td>
                                    <td>{{ $refund->reference }}</td>
                                    <td>{{ $refund->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-dark">
                                        <p>{{ __('No Data Found') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="fileModal" tabindex="-1" role="dialog" aria-labelledby="fileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fileModalLabel">Upload Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="fileUploadForm" action="{{ route('upload.file.invoice') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="invoiceId" value="{{ $invoice['id'] }}">
                        <div class="form-group">
                            <label for="fileInput">Choose File:</label>
                            <input type="file" class="form-control-file" id="fileInput" name="fileInput[]" multiple>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="fileUploadForm" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    function confirmDelete(event, id, categoryType) {
        event.preventDefault();

        let htmlContent = `
            <input type="date" id="deleteDate" class="swal2-input" placeholder="Delete Date" required>
        `;

        if (categoryType === 'Qty product') {
            htmlContent += `
                <input type="number" id="deleteQty" class="swal2-input" placeholder="Delete Quantity" min="1" step="1" required>
            `;
        }

        Swal.fire({
            title: 'Confirm Deletion',
            html: htmlContent,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const deleteDate = document.getElementById('deleteDate').value;
                const deleteQty = categoryType === 'Qty product' ? document.getElementById('deleteQty')
                    .value : null;

                if (!deleteDate) {
                    Swal.showValidationMessage('Delete date is required');
                    return false;
                }

                if (categoryType === 'Qty product' && (!deleteQty || deleteQty <= 0)) {
                    Swal.showValidationMessage('Quantity must be greater than 0');
                    return false;
                }

                return {
                    deleteDate,
                    deleteQty
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-date-${id}`).value = result.value.deleteDate;
                if (categoryType === 'Qty product') {
                    document.getElementById(`delete-qty-${id}`).value = result.value.deleteQty;
                }
                document.getElementById(`delete-form-${id}`).submit();
            }
        });
    }
</script>
