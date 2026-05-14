@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Detail') }}
@endsection
@php
    $setting = \App\Models\Utility::settings();
@endphp
@push('script-page')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).on('click', '#shipping', function() {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data: 'is_display': is_display,
                success: function(data) {
                    // console.log(data);
                }
            });
        });

        function copyToClipboard(element) {

            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            // document.addEventListener('copy', function (e) {
            //     e.clipboardData.setData('text/plain', copyText);
            //     e.preventDefault();
            // }, true);
            //
            // document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>
    <script>
        function confirmSend(billId) {
            Swal.fire({
                title: 'Enter Send Date',
                html: `
                <input type="date" id="send_date" class="swal2-input" required>
            `,
                showCancelButton: true,
                confirmButtonText: 'Send',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const sendDate = document.getElementById('send_date').value;
                    if (!sendDate) {
                        Swal.showValidationMessage('Please select a date');
                        return false;
                    }
                    return {
                        send_date: sendDate
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/bill/${billId}/sent`;

                    // CSRF token
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = token;
                    form.appendChild(csrf);

                    // Add the send_date input
                    const dateInput = document.createElement('input');
                    dateInput.type = 'hidden';
                    dateInput.name = 'send_date';
                    dateInput.value = result.value.send_date;
                    form.appendChild(dateInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-confirm').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    let formId = this.getAttribute('data-form-id');
                    Swal.fire({
                        title: '{{ __('Are You Sure?') }}',
                        text: '{{ __('This action can not be undone. Do you want to continue?') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{{ __('Yes, delete it!') }}',
                        cancelButtonText: '{{ __('Cancel') }}'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(formId).submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
@php
    $settings = Utility::settings();
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</li>
@endsection

@section('content')

    @can('send bill')
        {{-- @if ($bill->status != 6) --}}
        <div class="row">
            <div class="col-12">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="row timeline-wrapper">
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                                <h6 class="text-primary my-3">{{ __('Create Bill') }}</h6>
                                <p class="text-muted text-sm mb-3"><i
                                        class="ti ti-clock mr-2"></i>{{ __('Created on ') }}{{ \Auth::user()->dateFormat($bill->bill_date) }}
                                </p>
                                @if ($statusChangesSendToApprove)
                                    <p class="text-muted text-sm mb-3"><i
                                            class="ti ti-clock mr-2"></i>{{ __('Send To Approve on ') }}{{ $statusChangesSendToApprove ? \Auth::user()->dateFormat($statusChangesSendToApprove->changed_at) : '' }}
                                    </p>
                                @endif
                                @if ($bill->status === 0)
                                    @can('edit bill')
                                        <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Edit Info') }}"><i
                                                class="ti ti-pencil mr-2"></i>{{ __('Edit Info') }}</a>
                                    @endcan
                                    @can('edit bill')
                                        <a href="{{ route('api.addSubProducts', $bill->id) }}" class="btn btn-sm btn-primary"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Edit Products') }}"><i
                                                class="ti ti-pencil mr-2"></i>{{ __('Edit Products') }}</a>
                                    @endcan
                                    <br />
                                    <a href="{{ route('bill.sendtoapprove', $bill->id) }}"
                                        class="btn btn-sm btn-secondary mt-2" data-bs-toggle="tooltip"
                                        data-original-title="{{ __('Send To Approve') }}"><i
                                            class="ti ti-send mr-2"></i>{{ __('Send To Approve') }}</a>
                                @endif
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-accessible text-warning"></i>
                                </div>
                                <h6 class="text-warning my-3">{{ __('Approved Bill') }}</h6>
                                <p class="text-muted text-sm mb-3">
                                    @if ($bill->status === 0 || $bill->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Approved') }}</small>
                                    @elseif ($statusChangesApprove)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Approved on ') }}{{ $statusChangesApprove ? \Auth::user()->dateFormat($statusChangesApprove->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($bill->status === 1)
                                    @can('send bill')
                                        <a href="{{ route('bill.approve', $bill->id) }}" class="btn btn-sm btn-warning"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Approved') }}"><i
                                                class="ti ti-user-exclamation mr-2"></i>{{ __('Approved') }}</a>
                                    @endcan
                                    @can('send bill')
                                        <a href="{{ route('bill.notapprove', $bill->id) }}" class="btn btn-sm btn-danger"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Not Approved') }}"><i
                                                class="ti ti-user-x mr-2"></i>{{ __('Not Approved') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-send text-danger"></i>
                                </div>
                                <h6 class="text-danger my-3">{{ __('Send Bill') }}</h6>
                                <p class="text-muted text-sm mb-3">
                                    @if ($bill->status === 2 || $bill->status === 0 || $bill->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Sent') }}</small>
                                    @elseif ($statusChangesSend)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Send on ') }}{{ $statusChangesSend ? \Auth::user()->dateFormat($statusChangesSend->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($bill->status === 2)
                                    @can('send bill')
                                        <a href="javascript:void(0);" onclick="confirmSend({{ $bill->id }})"
                                            class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Mark Sent') }}">
                                            <i class="ti ti-send mr-2"></i>{{ __('Send') }}
                                        </a>
                                    @endcan
                                    @can('send bill')
                                        <a href="{{ route('bill.backtoapprove', $bill->id) }}" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Not Sent') }}"><i
                                                class="ti ti-rotate-clockwise mr-2"></i>{{ __('Back To Approve') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-3 col-lg-2 col-xl-2">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-mail text-info"></i>
                                </div>

                                <h6 class="text-info my-3">{{ __('Received Bill') }}</h6>

                                <p class="text-muted text-sm mb-3">
                                    @if ($bill->status == 0 || $bill->status == 2 || $bill->status == 4 || $bill->status === 1)
                                        <small>{{ __('Status') }} : {{ __('Not Received') }}</small>
                                    @elseif ($bill->status == 6)
                                        <i
                                            class="ti ti-clock mr-2"></i>{{ __('Received on ') }}{{ $statusChangesReceived ? \Auth::user()->dateFormat($statusChangesReceived->changed_at) : '' }}
                                    @endif
                                </p>
                                @if ($bill->status == 4)
                                    @can('receive bill')
                                        <a href="{{ route('bill.receive', $bill->id) }}" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" data-original-title="{{ __('Mark Received') }}"><i
                                                class="ti ti-send mr-2"></i>{{ __('Received') }}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4">

                                @if ($bill->payment_status == 0)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-secondary"></i>
                                    </div>
                                    <h6 class="text-secondary my-3">{{ __('Get Paid') }}</h6>
                                    <p class="text-muted text-sm mb-3">{{ __('Status') }} : {{ __('Awaiting payment') }}
                                    </p>
                                    @can('create payment bill')
                                        <a href="#" data-url="{{ route('bill.payment', $bill->id) }}" data-ajax-popup="true"
                                            data-title="{{ __('Add Payment') }}" class="btn btn-sm btn-secondary"
                                            data-original-title="{{ __('Add Payment') }}"><i
                                                class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}</a> <br>
                                    @endcan
                                @endif
                                @if ($bill->payment_status == 2)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{ __('Get Paid') }}</h6>
                                    <p class="text-warning text-sm mb-3">{{ __('Status') }} : {{ __('Partialy Paid') }}
                                    </p>
                                    @can('create payment bill')
                                        <a href="#" data-url="{{ route('bill.payment', $bill->id) }}"
                                            data-ajax-popup="true" data-title="{{ __('Add Payment') }}"
                                            class="btn btn-sm btn-warning" data-original-title="{{ __('Add Payment') }}"><i
                                                class="ti ti-report-money mr-2"></i>{{ __('Add Payment') }}</a> <br>
                                    @endcan
                                @endif
                                @if ($bill->payment_status == 4)
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{ __('Paid') }}</h6>
                                    <p class="text-primary text-sm mb-3">{{ __('Status') }} : {{ __('Paid') }}</p>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- @endif --}}
    @endcan

    @if (\Auth::user()->type == 'company' || \Auth::user()->can('view bill') || \Auth::user()->can('show bill'))

        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                @if ($bill->status != 0)
                    @if (!empty($billPayment))
                        <div class="all-button-box mx-2">
                            <a href="#" data-url="{{ route('bill.debit.note', $bill->id) }}"
                                data-ajax-popup="true" data-title="{{ __('Add Debit Note') }}"
                                class="btn btn-sm btn-primary">
                                {{ __('Add Debit Note') }}
                            </a>
                        </div>
                    @endif
                    <div class="all-button-box mx-2">
                        <a href="{{ route('bill.resent', $bill->id) }}" class="btn btn-sm btn-primary">
                            {{ __('Resend Bill') }}
                        </a>
                    </div>
                @endif
                <div class="all-button-box">
                    <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id)) }}" target="_blank"
                        class="btn btn-sm btn-primary">
                        {{ __('Download') }}
                    </a>
                </div>
                <div class="all-button-box mx-2">
                    <a href="{{ route('bill.export.products', Crypt::encrypt($bill->id)) }}"
                        class="btn btn-sm btn-success">
                        <i class="ti ti-file-export mr-2"></i>{{ __('Export Products') }}
                    </a>
                </div>
                <div class="all-button-box mr-2 ml-2" style="margin-left: 10px;">
                    <a href="{{ route('bill.ledger', $bill->id) }}" target="_blank"
                        class="btn btn-sm btn-primary">{{ __('Show Accounting') }}</a>
                </div>
            </div>
        </div>


    @endif
    <div class="card ">
        <div class="card-body employee-detail-body fulls-card">
            <h5>{{ __('Document Detail') }}</h5>
            <hr>
            <div class="row">
                @if ($bill->accountingDocuments()->count() > 0)
                    <div class="col-md-6">
                        <div class="info text-sm">
                            <strong class="font-bold">Documents:</strong>
                            <ul>
                                @foreach ($bill->accountingDocuments as $document)
                                    <li>
                                        <a href="{{ URL::to('/') . '/' . $document->document_path }}" target="_blank"
                                            >{{ $document->document_name }}</a>
                                        <div class="action-btn bg-danger ms-2">
                                            <form action="{{ route('bill.file.destroy', $document->id) }}" method="POST"
                                                id="delete-form-{{ $document->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="document_id" value="{{ $document->id }}">
                                                <a href="#" class="mx-3 btn btn-sm align-items-center text-white delete-confirm"
                                                    data-form-id="delete-form-{{ $document->id }}"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
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
                                    <h4>{{ __('Bill') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</h4>
                                    @if(isset($requestNumbers) && !empty($requestNumbers))
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>{{ __('Accessory Request Number(s)') }}:</strong><br>
                                                @foreach($requestNumbers as $requestNo)
                                                    {{ $requestNo }}@if(!$loop->last), @endif
                                                @endforeach
                                            </small>
                                        </div>
                                    @endif
                                    @if(isset($asnNumbers) && !empty($asnNumbers))
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>{{ __('ASN Number(s)') }}:</strong><br>
                                                @foreach($asnNumbers as $asnNo)
                                                    {{ $asnNo }}@if(!$loop->last), @endif
                                                @endforeach
                                            </small>
                                        </div>
                                    @endif
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
                                                <strong>{{ __('Due Date') }} :</strong><br>
                                                {{ \Auth::user()->dateFormat($bill->due_date) }}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{ __('Vendor') }} :</strong><br>
                                        {{ !empty($vendor->name) ? $vendor->name : '' }}<br>
                                    </small>
                                </div>
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($vendor->name))
                                            {{ !empty($vendor->name) ? $vendor->name : '' }}<br>
                                            {{ !empty($vendor->billing_address) ? $vendor->billing_address : '' }}<br>
                                            {{ !empty($vendor->billing_city) ? $vendor->billing_city : '' . ', ' }}<br>
                                            {{ !empty($vendor->billing_state) ? $vendor->billing_state : '' . ', ' }},
                                            {{ !empty($vendor->billing_zip) ? $vendor->billing_zip : '' }}<br>
                                            {{ !empty($vendor->billing_country) ? $vendor->billing_country : '' }}<br>
                                            {{ !empty($vendor->billing_phone) ? $vendor->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($vendor->tax_number) ? $vendor->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>

                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            @if (!empty($vendor->shipping_name))
                                                {{ !empty($vendor->shipping_name) ? $vendor->shipping_name : '' }}<br>
                                                {{ !empty($vendor->shipping_address) ? $vendor->shipping_address : '' }}<br>
                                                {{ !empty($vendor->shipping_city) ? $vendor->shipping_city : '' . ', ' }}<br>
                                                {{ !empty($vendor->shipping_state) ? $vendor->shipping_state : '' . ', ' }},
                                                {{ !empty($vendor->shipping_zip) ? $vendor->shipping_zip : '' }}<br>
                                                {{ !empty($vendor->shipping_country) ? $vendor->shipping_country : '' }}<br>
                                                {{ !empty($vendor->shipping_phone) ? $vendor->shipping_phone : '' }}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif

                                <div class="col">
                                    <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML(
                                            route('bill.link.copy', \Illuminate\Support\Facades\Crypt::encrypt($bill->id)),
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
                                        @if ($bill->status == 0)
                                            <span
                                                class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span
                                                class="badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span
                                                class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 6)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Payment Status') }} :</strong><br>
                                        @if ($bill->payment_status == 0)
                                            <span
                                                class="badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                        @elseif($bill->payment_status == 2)
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                        @elseif($bill->payment_status == 4)
                                           
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$paymentstatues[$bill->payment_status]) }}</span>
                                            
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Warehouse') }} :</strong><br>
                                        @if ($bill->warehouse_id != null)
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ $bill->warehouse->name }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded"></span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Currencу') }} :</strong><br>
                                        @if ($bill->currency_id != null)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ $bill->currency->name }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ $settings['site_currency'] }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Rate') }} :</strong><br>
                                        @if ($bill->currency_id != null)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ $bill->exchange_rate }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded"></span>
                                        @endif
                                    </small>
                                </div>

                                @if (!empty($customFields) && count($bill->customField) > 0)
                                    @foreach ($customFields as $field)
                                        <div class="col text-md-end">
                                            <small>
                                                <strong>{{ $field->name }} :</strong><br>
                                                {{ !empty($bill->customField) ? $bill->customField[$field->id] : '-' }}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-bold mb-2">{{ __('Product Summary') }}</div>
                                    <small class="mb-2">{{ __('All items here cannot be deleted.') }}</small>
                                    <div class="table-responsive mt-3">
                                        <table class="table mb-0 table-striped">
                                            <thead>
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
                                                    <th class="text-end text-dark" width="12%">
                                                        {{ __('Price') }}<br>
                                                        <small
                                                            class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                                    </th>
                                                    @if ($bill->status != 0)
                                                        <th>{{ __('Action') }}</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($paginatedItems as $key => $item)
                                                    @if (!empty($item->product_id))
                                                        @php
                                                            $product = $item->product;
                                                            $subProduct = $item->subProduct;
                                                            $categoryType = null;
                                                            if ($subProduct && $subProduct->productService && $subProduct->productService->category) {
                                                                $categoryType = $subProduct->productService->category->type;
                                                            }
                                                            $isQtyProduct = $categoryType == 'Qty product';
                                                            $quantity = $isQtyProduct ? $item->quantity : 1;

                                                        @endphp

                                                        <tr>
                                                            <td>{{ $item->sub_product_id }}</td>

                                                            <td>
                                                                @if ($product && $product->brand)
                                                                    {{ $product->brand->name ?? 'No Brand' }}/{{ $product->subBrand->name ?? __('No Model') }}/{{ $product->name ?? '-' }}/{{ $product->sku ?? '-' }}
                                                                @elseif ($product)
                                                                    {{ $product->name ?? '-' }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>

                                                            <td>{{ $subProduct->chassis_no ?? '-' }}</td>

                                                            <td>{{ $quantity }} ({{ $product && $product->unit ? $product->unit->name : '-' }})
                                                            </td>

                                                            <td>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($item->exchange_price, $bill->currency->symbol) : \Auth::user()->priceFormat(price: $item->price) }}
                                                            </td>
                                                            <td>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($item->exchange_discount, $bill->currency->symbol) : \Auth::user()->priceFormat($item->discount) }}
                                                            </td>
                                                            <td>
                                                                {{-- @if (!empty($item->bill_tax)) --}}
                                                                <table>

                                                                    <tr>
                                                                        <td>{{ $bill->currency_id != null ? $item->getTaxPriceExchangeAttribute() : $item->getTaxPriceAttribute() }}
                                                                        </td>
                                                                        <td>{{ $item->tax_name }}
                                                                            ({{ $item->tax_rate }})</td>
                                                                    </tr>

                                                                </table>
                                                                {{-- @else
                                                                    -
                                                                @endif --}}
                                                            </td>

                                                            <td>
                                                                {{ $product && $product->category && $product->category->purchaseAccount ? $product->category->purchaseAccount->name : '-' }}
                                                            </td>

                                                            <td>-</td>

                                                            <td class="text-end">
                                                                @if ($isQtyProduct)
                                                                    {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($item->exchange_price * $quantity - $item->exchange_discount * $quantity + $item->getTaxPriceExchangeAttribute(), $bill->currency->symbol) : \Auth::user()->priceFormat($item->price * $quantity - $item->discount * $quantity + $item->getTaxPriceAttribute()) }}
                                                                @else
                                                                    {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($item->exchange_price - $item->exchange_discount + $item->getTaxPriceExchangeAttribute(), $bill->currency->symbol) : Auth::user()->priceFormat($item->price - $item->discount + $item->getTaxPriceAttribute()) }}
                                                                @endif
                                                            </td>

                                                            @if ($bill->status != 0)
                                                                <td class="Action">
                                                                    {{-- @if ($item->flag == 1)
                                                                        <div class="action-btn bg-danger ms-2">
                                                                            <form
                                                                                action="{{ route('sub-product.deleteBill', $item->sub_product_id) }}"
                                                                                method="POST"
                                                                                id="delete-form-{{ $item->sub_product_id }}">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <a href="#"
                                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                                    data-bs-toggle="tooltip"
                                                                                    title="{{ __('Delete') }}"
                                                                                    onclick="confirmDelete(event, {{ $item->sub_product_id }}, '{{ $categoryType }}')">
                                                                                    <i class="ti ti-trash text-white"></i>
                                                                                </a>
                                                                                <input type="hidden" name="delete_date"
                                                                                    id="delete-date-{{ $item->sub_product_id }}">
                                                                                <input type="hidden" name="delete_qty"
                                                                                    id="delete-qty-{{ $item->sub_product_id }}">
                                                                            </form>
                                                                        </div>
                                                                    @elseif ($item->flag == 0)
                                                                <td>Cancelled</td>
                                                            @endif --}}
                                                            </td>
                                                    @endif
                                                    </tr>
                                                @else
                                                    {{-- Handle non-product items (accounts) --}}
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>
                                                            {{ $$item->chart_account_name ?? '-' }}
                                                        </td>
                                                        <td>{{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                        <td>-</td>
                                                        <td class="text-end">
                                                            {{ \Auth::user()->priceFormat($item['amount']) }}</td>
                                                        <td></td>
                                                    </tr>
                                                @endif
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3"><b>{{ __('Total') }}</b></td>
                                                    <td><b>{{ $totalQuantity }}</b></td>
                                                    <td><b>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($totalRate, $bill->currency->symbol) : \Auth::user()->priceFormat($totalRate) }}</b>
                                                    </td>
                                                    <td><b>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($totalDiscount, $bill->currency->symbol) : \Auth::user()->priceFormat($totalDiscount) }}</b>
                                                    </td>
                                                    <td><b>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($totalTaxPrice, $bill->currency->symbol) : \Auth::user()->priceFormat($totalTaxPrice) }}</b>
                                                    </td>
                                                    <td colspan="2"></td>
                                                    <td class="text-end">
                                                        <b>{{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($subTotal - $totalDiscount + $totalTaxPrice, $bill->currency->symbol) : \Auth::user()->priceFormat($subTotal - $totalDiscount + $totalTaxPrice) }}</b>
                                                    </td>
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Sub Total') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($subTotal, $bill->currency->symbol) : \Auth::user()->priceFormat($subTotal) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Discount') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($totalDiscount, $bill->currency->symbol) : \Auth::user()->priceFormat($totalDiscount) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>Tax</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($totalTaxPrice, $bill->currency->symbol) : \Auth::user()->priceFormat($totalTaxPrice) }}
                                                    </td>
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                                    <td class="blue-text text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($total, $bill->currency->symbol) : \Auth::user()->priceFormat($total) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($paidAmount, $bill->currency->symbol) : \Auth::user()->priceFormat($paidAmount) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Refund') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($refundTotal, $bill->currency->symbol) : \Auth::user()->priceFormat($refundTotal) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Debit Note') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($debitNoteTotal, $bill->currency->symbol) : \Auth::user()->priceFormat($debitNoteTotal) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{ __('Due') }}</b></td>
                                                    <td class="text-end">
                                                        {{ $bill->currency_id != null ? \Auth::user()->priceFormatCurr($due + $refundTotal, $bill->currency->symbol) : \Auth::user()->priceFormat($due + $refundTotal) }}
                                                    </td> <!-- Precomputed -->
                                                    @if ($bill->status != 0)
                                                        <td></td>
                                                    @endif
                                                </tr>
                                            </tfoot>
                                        </table>

                                        {{-- Pagination Links --}}
                                        <div class="d-flex justify-content-center mt-3">
                                            {{ $paginatedItems->links() }}
                                        </div>
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
                    <h5 class=" d-inline-block mb-5">{{ __('Payment Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Payment ID') }}</th>
                                    <th class="text-dark">{{ __('Payment Receipt') }}</th>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Currency') }}</th>
                                    <th class="text-dark">{{ __('Amount in AED') }}</th>
                                    <th class="text-dark">{{ __('Amount in Bill Currency') }}</th>
                                    <th class="text-dark">{{ __('Account') }}</th>
                                    <th class="text-dark">{{ __('Reference') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    @can('delete payment bill')
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endcan
                                </tr>
                            </thead>
                            @forelse($bill->payments as $key =>$payment)
                                <tr>
                                    @php
                                        $bill = \App\Models\Bill::find($payment->bill_id);
                                        $currencySymbol =
                                            $bill && $bill->currency
                                                ? $bill->currency->symbol
                                                : Auth::user()->currencySymbol();
                                    @endphp
                                    <td>{{ $payment->payment_id ? \App\Models\Payment::formatLabelForId($payment->payment_id) : 'N/A' }}</td>
                                    <td>
                                        @if (!empty($payment->add_receipt))
                                            <a href="{{ URL::to('/') . '/uploads/payment' . '/' . $payment->add_receipt }}"
                                                 class="btn btn-sm btn-secondary btn-icon rounded-pill"
                                                target="_blank"><span class="btn-inner--icon"><i
                                                        class="ti ti-download"></i></span></a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ \Auth::user()->dateFormat($payment->date) }}</td>
                                    <td>{{ ($payment->currency && $payment->currency_rate > 0)
                                        ? Auth::user()->priceFormatCurr($payment->amount / $payment->currency_rate, $payment->currency->symbol) . ' (' . $payment->currency_rate . ')'
                                        : ($payment->currency
                                            ? Auth::user()->priceFormatCurr($payment->amount, $payment->currency->symbol)
                                            : Auth::user()->priceFormat($payment->amount))
                                        }}
                                    </td>
                                    <td>{{ $payment->currency ? $payment->currency->name : \Auth::user()->currencySymbol() }}
                                    </td>
                                    <td>{{ \Auth::user()->priceFormat($payment->amount) }}</td>
                                    <td>{{ $payment->amount_in_currency
                                        ? Auth::user()->priceFormatCurr($payment->amount_in_currency, $currencySymbol)
                                        : '-' }}
                                    </td>
                                    <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
                                    </td>
                                    <td>{{ $payment->reference }}</td>
                                    <td>{{ $payment->description }}</td>
                                    <td class="text-dark">
                                        @can('delete bill product')
                                            <div class="action-btn bg-danger ms-2">
                                                <form action="{{ route('bill.payment.destroy', [$bill->id, $payment->id]) }}"
                                                    method="POST" id="delete-form-{{ $payment->id }}">
                                                    @csrf
                                                    @method('POST')
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm align-items-center text-white delete-confirm"
                                                        data-form-id="delete-form-{{ $payment->id }}"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                </form>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-dark">
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
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Debit Note Summary') }}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="text-dark">{{ __('Date') }}</th>
                                    <th class="text-dark">{{ __('Amount') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                    {{-- @if (Gate::check('edit debit note') || Gate::check('delete debit note'))
                                        <th class="text-dark">{{ __('Action') }}</th>
                                    @endif --}}
                                </tr>
                            </thead>
                            @forelse($bill->debitNote as $key =>$debitNote)
                                <tr>
                                    <td>{{ \Auth::user()->dateFormat($debitNote->date) }}</td>
                                    <td>{{ \Auth::user()->priceFormat($debitNote->amount) }}</td>
                                    <td>{{ $debitNote->description }}</td>
                                    {{-- <td>
                                        @can('edit debit note')
                                            <a data-url="{{ route('bill.edit.debit.note', [$debitNote->bill, $debitNote->id]) }}"
                                                data-ajax-popup="true" data-title="{{ __('Add Debit Note') }}"
                                                href="#" class="mx-3 btn btn-sm align-items-center"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                        @endcan
                                        @can('delete debit note')
                                            <div class="action-btn bg-danger ms-2">
                                                <form
                                                    action="{{ route('bill.delete.debit.note', [$debitNote->bill, $debitNote->id]) }}"
                                                    method="POST" id="delete-form-{{ $debitNote->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-form-{{ $debitNote->id }}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                </form>
                                            </div>
                                        @endcan
                                    </td> --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-dark">
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
                                    <th class="text-dark">{{ __('Amount in Bill Currency') }}</th>
                                    <th class="text-dark">{{ __('Account') }}</th>
                                    <th class="text-dark">{{ __('Reference') }}</th>
                                    <th class="text-dark">{{ __('Description') }}</th>
                                </tr>
                            </thead>
                            @forelse($bill->refunds as $key =>$refund)
                                <tr>
                                    @php
                                        $currencySymbol =
                                            $refund->currency
                                                ? $refund->currency->symbol
                                                : Auth::user()->currencySymbol();
                                    @endphp
                                    <td>{{ \Auth::user()->dateFormat($refund->date) }}</td>
                                    <td>{{ ($refund->currency && $refund->currency_rate > 0)
                                        ? Auth::user()->priceFormatCurr($refund->amount / $refund->currency_rate, $refund->currency->symbol) . ' (' . $refund->currency_rate . ')'
                                        : ($refund->currency
                                            ? Auth::user()->priceFormatCurr($refund->amount, $refund->currency->symbol)
                                            : Auth::user()->priceFormat($refund->amount))
                                        }}
                                    </td>
                                    <td>{{ $refund->currency ? $refund->currency->name : \Auth::user()->currencySymbol() }}
                                    </td>
                                    <td>{{ \Auth::user()->priceFormat($refund->amount) }}</td>
                                    <td>{{ $refund->amount_in_currency
                                        ? Auth::user()->priceFormatCurr($refund->amount_in_currency, $currencySymbol)
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
                    <form id="fileUploadForm" action="{{ route('upload.file.bill') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="billId" value="{{ $bill['id'] }}">
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
