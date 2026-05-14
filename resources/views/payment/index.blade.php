@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Payments') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payment') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create payment')
            <a href="#" data-url="{{ route('payment.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" data-size="lg"
                data-title="{{ __('Create New Payment') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus me-1"></i>{{ __('Create') }}
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('payment.index') }}" method="GET" id="payment_form">

                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="date" class="form-label">{{ __('Date') }}</label>
                                                <input type="date" name="date" id="pc-daterangepicker-1"
                                                    class="form-control month-btn"
                                                    value="{{ isset($_GET['date']) ? $_GET['date'] : '' }}">
                                            </div>

                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="account" class="form-label">{{ __('Account') }}</label>
                                                <select name="account" id="choices-multiple" class="form-control select2">
                                                    @foreach ($account as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ isset($_GET['account']) && $_GET['account'] == $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="vender" class="form-label">{{ __('Vendor') }}</label>
                                                <select name="vender" id="choices-multiple1" class="form-control select2">
                                                    @foreach ($vender as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ isset($_GET['vender']) && $_GET['vender'] == $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="category" class="form-label">{{ __('Category') }}</label>
                                                <select name="category" id="choices-multiple2" class="form-control select">
                                                    @foreach ($category as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ isset($_GET['category']) && $_GET['category'] == $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div clas="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('payment_form').submit(); return false;"
                                                data-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('payment.index') }}" class="btn btn-sm btn-danger"
                                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __(key: 'ID') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount Currency') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Total Payment') }}</th>
                                    <th>{{ __('Already Allocated') }}</th>
                                    <th>{{ __('Remaining to Allocate') }}</th>
                                    <th>{{ __('Amount In Bill Currency') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    {{--                                <th> {{__('Chart Of Account')}}</th> --}}
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    <th> {{ __('Bill') }}</th>
                                    @if (Gate::check('edit payment') || Gate::check('delete payment'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentpath = public_path('uploads/payment');
                                @endphp

                                @foreach ($payments as $payment)
                                    <tr class="font-style">
                                        @php
                                            // Use eager-loaded bill relationship instead of querying
                                            $bill = $payment->bill;
                                            // If no direct bill, use first bill from bills relationship
                                            if (!$bill && $payment->bills->isNotEmpty()) {
                                                $bill = $payment->bills->first();
                                            }
                                            $currencySymbol =
                                                $bill && $bill->currency
                                                    ? $bill->currency->symbol
                                                    : Auth::user()->currencySymbol();
                                        @endphp
                                        <td>{{ \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id) }}</td>
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>
                                            @if($payment->currency && $payment->currency_rate && $payment->currency_rate > 0)
                                                {{ Auth::user()->priceFormatCurr($payment->amount / $payment->currency_rate, $payment->currency->symbol) }}
                                            @else
                                                {{ Auth::user()->priceFormat($payment->amount) }}
                                            @endif
                                        </td>
                                        <td>{{ $payment->currency ? $payment->currency->name : \Auth::user()->currencySymbol() }}
                                        </td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->billPayments->sum('amount')) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount - $payment->billPayments->sum('amount')) }}
                                        </td>
                                        <td>{{ $payment->amount_in_currency
                                            ? Auth::user()->priceFormatCurr($payment->amount_in_currency, $currencySymbol)
                                            : '-' }}
                                        </td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
                                        </td>
                                        {{--                                    <td>{{ !empty($payment->chartAccount)?$payment->chartAccount->name :'-' }}</td> --}}
                                        <td>{{ !empty($payment->vender) ? $payment->vender->name : '-' }}</td>
                                        <td>{{ !empty($payment->category) ? $payment->category->name : '-' }}</td>
                                        <td>{{ !empty($payment->reference) ? $payment->reference : '-' }}</td>
                                        <td>
                                            @if (!empty($payment->description))
                                                <div style="max-width: 250px;overflow: hidden; text-overflow: ellipsis; "
                                                    title="{{ $payment->description }}">
                                                    {{ \Illuminate\Support\Str::limit($payment->description, 80, '...') }}
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($payment->status == 0)
                                                <span
                                                    class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Payment::$statues[$payment->status]) }}</span>
                                            @elseif($payment->status == 2)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Payment::$statues[$payment->status]) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($payment->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ URL::to('/') . '/uploads/payment' . '/' . $payment->add_receipt }}"
                                                    download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ URL::to('/') . '/uploads/payment' . '/' . $payment->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>
                                            @if ($payment->bills->count())
                                                @foreach ($payment->bills as $bill)
                                                    @php
                                                        $linkRoute =
                                                            $bill->type === 'Bill'
                                                                ? route('bill.show', \Crypt::encrypt($bill->id))
                                                                : route('expense.show', \Crypt::encrypt($bill->id));

                                                        $label =
                                                            $bill->type === 'Bill'
                                                                ? Auth::user()->billNumberFormat($bill->bill_id)
                                                                : __('Expense') . ' ' . $bill->bill_id;
                                                    @endphp

                                                    <a href="{{ $linkRoute }}"
                                                        class="btn btn-outline-primary btn-sm mb-1">
                                                        {{ $label }} ({{ __('Allocated:') }}
                                                        {{ Auth::user()->priceFormat($bill->pivot->amount) }})
                                                    </a><br>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="action">
                                                @can('edit payment')
                                                @if (!$payment->bills->count())
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('payment.edit', $payment->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Payment') }}"
                                                            data-size="lg" data-bs-toggle="tooltip"
                                                            title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                @endcan
                                                @can('delete payment')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form method="POST"
                                                            action="{{ route('payment.destroy', $payment->id) }}"
                                                            id="delete-form-{{ $payment->id }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" name="delete_date"
                                                                id="delete-date-{{ $payment->id }}">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Delete') }}"
                                                                title="{{ __('Delete') }}"
                                                                onclick="confirmDeleteWithDate({{ $payment->id }})">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endcan
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('printvendorpayment', ['payment' => $payment->id]) }}"
                                                        class="mx-3 btn btn-sm align-items-center" target="_blank"
                                                        title="{{ __('Print') }}"
                                                        data-original-title="{{ __('Print') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a>
                                                </div>
                                                {{-- ✅ Allocate Button --}}
                                                @if($payment->billPayments->sum('amount') != $payment->amount)
                                                <div class="action-btn bg-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('payment.allocate.form', $payment->id) }}"
                                                        data-ajax-popup="true"
                                                        data-title="{{ __('Allocate Payment to Bills') }}" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Allocate') }}"
                                                        data-original-title="{{ __('Allocate') }}">
                                                        <i class="ti ti-link text-white"></i>
                                                    </a>
                                                </div>
                                                @endif
                                                @if ($payment->status === 0)
                                                    <div class="action-btn bg-warning ms-2">
                                                        <form method="GET"
                                                            action="{{ route('sendpayment', ['payment' => $payment->id]) }}"
                                                            id="send-form-{{ $payment->id }}">
                                                            @csrf
                                                            <input type="hidden" name="send_date" id="send-date-{{ $payment->id }}">
                                                            <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center"
                                                            title="{{ __('Receive Payment') }}"
                                                            onclick="openSendDateAlert({{ $payment->id }}); return false;">
                                                                <i class="ti ti-send text-white"></i>
                                                            </a>
                                                        </form>
                                                    </div>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $payments->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
   function openSendDateAlert(paymentId) {
    Swal.fire({
        title: "Select Send Date",
        html: `<input type="date" id="send-date-input" class="swal2-input" required>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Confirm",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const date = document.getElementById('send-date-input').value;
            if (!date) {
                Swal.showValidationMessage('Please select a date');
                return false;
            }
            return date;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('send-date-' + paymentId).value = result.value;
            document.getElementById('send-form-' + paymentId).submit();
        }
    });
}

// Confirm delete with date
function confirmDeleteWithDate(id) {
    Swal.fire({
        title: 'Are you sure?',
        html: `<input type="date" id="delete-date-input" class="swal2-input" required>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const date = document.getElementById('delete-date-input').value;
            if (!date) {
                Swal.showValidationMessage('Delete date is required');
                return false;
            }
            return date;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-date-' + id).value = result.value;
            document.getElementById('delete-form-' + id).submit();
        }
    });
}
</script>
