@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Manage Vendor-Detail') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vender.index') }}">{{ __('Vendor') }}</a></li>
    <li class="breadcrumb-item">{{ $vendor['name'] }}</li>
@endsection
@push('script-page')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endpush
@section('action-btn')
    <div class="float-end">
        @can('create bill')
            <a href="{{ route('bill.create', $vendor->id) }}" class="btn btn-sm btn-primary">
                {{ __('Create Bill') }}
            </a>
        @endcan

        @can('edit vender')
            <a href="#" class="btn btn-sm btn-primary" data-size="xl" data-url="{{ route('vender.edit', $vendor['id']) }}"
                data-ajax-popup="true" title="{{ __('Edit') }}" data-bs-toggle="tooltip"
                data-original-title="{{ __('Edit') }}">
                <i class="ti ti-pencil"></i>
            </a>
        @endcan
        @can('delete vender')
            <form method="POST" action="{{ route('vender.destroy', $vendor['id']) }}" class="delete-form-btn"
                id="delete-form-{{ $vendor['id'] }}">
                @csrf
                @method('DELETE')
                <a href="#" class="btn btn-sm btn-danger bs-pass-para" data-bs-toggle="tooltip"
                    title="{{ __('Delete') }}" data-original-title="{{ __('Delete') }}"
                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                    data-confirm-yes="document.getElementById('delete-form-{{ $vendor['id'] }}').submit();">
                    <i class="ti ti-trash text-white"></i>
                </a>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box vendor_card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Vendor Info') }}</h5>
                    <p class="card-text mb-0">{{ $vendor->name }}</p>
                    <p class="card-text mb-0">{{ $vendor->email }}</p>
                    <p class="card-text mb-0">{{ $vendor->contact }}</p>
                    <p class="card-text mb-0">
                        {{ $vendor['chart_account_id'] != 0 ? \App\Models\ChartOfAccount::where('id', $vendor['chart_account_id'])->first()->name : '-' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box vendor_card">
                <div class="card-body">
                    <h3 class="card-title">{{ __('Billing Info') }}</h3>
                    <p class="card-text mb-0">{{ $vendor->name }}</p>
                    <p class="card-text mb-0">{{ $vendor->billing_address }}</p>
                    <p class="card-text mb-0">
                        {{ $vendor->billing_city . ', ' . $vendor->billing_state . ', ' . $vendor->billing_zip }}</p>
                    <p class="card-text mb-0">{{ $vendor->billing_country }}</p>
                    <p class="card-text mb-0">{{ $vendor->billing_phone }}</p>

                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4 col-xl-4">
            <div class="card pb-0 customer-detail-box vendor_card">
                <div class="card-body">
                    <h3 class="card-title">{{ __('Shipping Info') }}</h3>
                    <p class="card-text mb-0">{{ $vendor->shipping_name }}</p>
                    <p class="card-text mb-0">{{ $vendor->shipping_address }}</p>
                    <p class="card-text mb-0">
                        {{ $vendor->shipping_city . ', ' . $vendor->shipping_state . ', ' . $vendor->shipping_zip }}</p>
                    <p class="card-text mb-0">{{ $vendor->shipping_country }}</p>
                    <p class="card-text mb-0">{{ $vendor->shipping_phone }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card pb-0">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Company Info') }}</h5>
                    <div class="row">
                        @php
                            $totalBillSum = $vendor->vendorTotalBillSum($vendor['id']);
                            $totalBill = $vendor->vendorTotalBill($vendor['id']);
                            $averageSale = $totalBillSum != 0 ? $totalBillSum / $totalBill : 0;
                        @endphp
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Vendor Id') }}</p>
                                <h6 class="report-text mb-3">{{ \Auth::user()->venderNumberFormat($vendor->vender_id) }}
                                </h6>
                                <p class="card-text mb-0">{{ __('Total Sum of Bills') }}</p>
                                <h6 class="report-text mb-0">{{ \Auth::user()->priceFormat($totalBillSum) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Date of Creation') }}</p>
                                <h6 class="report-text mb-3">{{ \Auth::user()->dateFormat($vendor->created_at) }}</h6>
                                <p class="card-text mb-0">{{ __('Quantity of Bills') }}</p>
                                <h6 class="report-text mb-0">{{ $totalBill }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Balance') }}</p>
                                <h6 class="report-text mb-3">{{ \Auth::user()->priceFormat($vendor->balance) }}</h6>
                                <p class="card-text mb-0">{{ __('Average Sales') }}</p>
                                <h6 class="report-text mb-0">{{ \Auth::user()->priceFormat($averageSale) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-4">
                                <p class="card-text mb-0">{{ __('Overdue') }}</p>
                                <h6 class="report-text mb-3">
                                    {{ \Auth::user()->priceFormat($vendor->vendorOverdue($vendor->id)) }}</h6>
                                <p class="card-text mb-0">{{ __('Total Paid') }}</p>
                                <h6 class="report-text mb-3">{{ \Auth::user()->priceFormat($vendor->total_paid) }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card ">
        <div class="card-body employee-detail-body fulls-card">
            <h5>{{ __('Document Detail') }}</h5>
            <hr>
            <div class="row">
                @if ($vendor->accountingDocuments()->count() > 0)
                    <div class="col-md-6">
                        <div class="info text-sm">
                            <strong class="font-bold">Documents:</strong>
                            <ul>
                                @foreach ($vendor->accountingDocuments as $document)
                                    <li>
                                        <a href="{{ URL::to('/') . '/' . $document->document_path }}"
                                            target="_blank">{{ $document->document_name }}</a>
                                        <div class="action-btn bg-danger ms-2">
                                            <form action="{{ route('vendor.file.destroy', $document->id) }}" method="POST"
                                                id="delete-form-{{ $document->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="document_id" value="{{ $document->id }}">
                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    data-original-title="{{ __('Delete') }}"
                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="document.getElementById('delete-form-{{ $document->id }}').submit();">
                                                    <i class="ti ti-trash text-white text-white"></i>
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
                <div class="card-body table-border-style">
                    <h5 class=" d-inline-block mb-5">{{ __('Bills') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Bill') }}</th>
                                    <th>{{ __('Bill Date') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Due Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%"> {{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($vendor->vendorBill($vendor->id) as $bill)
                                    <tr class="font-style">
                                        <td class="Id">
                                            @php
                                                $billRecord = null;

                                                $billRecord = \App\Models\Bill::find($bill->id);

                                            @endphp
                                            @if ($bill->type == 'Bill')
                                                <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                    class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($bill->bill_id) }}
                                                </a>
                                            @else
                                                <a href="{{ route('expense.show', \Crypt::encrypt($bill->id)) }}"
                                                    class="btn btn-outline-primary">{{ $bill->bill_id }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($bill->bill_date) }}</td>
                                        <td>
                                            @if ($bill->due_date < date('Y-m-d'))
                                                <p class="text-danger"> {{ \Auth::user()->dateFormat($bill->due_date) }}
                                                </p>
                                            @else
                                                {{ \Auth::user()->dateFormat($bill->due_date) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($bill->type === 'Bill')
                                                {{ \Auth::user()->priceFormat($bill->getDue()) }}
                                            @else
                                                {{ \Auth::user()->priceFormat(0) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($bill->status == 0)
                                                <span
                                                    class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 1)
                                                <span
                                                    class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 2)
                                                <span
                                                    class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 3)
                                                <span
                                                    class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 4)
                                                <span
                                                    class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @elseif($bill->status == 6)
                                                <span
                                                    class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    {{-- @can('duplicate bill')
                                                        <div class="action-btn bg-success ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Duplicate Bill') }}"
                                                                data-original-title="{{ __('Duplicate') }}"
                                                                data-confirm="You want to confirm this action. Press Yes to continue or Cancel to go back"
                                                                data-confirm-yes="document.getElementById('duplicate-form-{{ $bill->id }}').submit();">
                                                                <i class="ti ti-copy text-white text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan --}}
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                class="mx-3 btn btn-sm  align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @if ($bill->status == 0)
                                                        @can('edit bill')
                                                            <div class="action-btn bg-primary ms-2">
                                                                <a href="{{ route('bill.edit', \Crypt::encrypt($bill->id)) }}"
                                                                    class="mx-3 btn btn-sm  align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                    @endif
                                                    @can('delete bill')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form action="{{ route('bill.destroy', $bill->id) }}"
                                                                method="POST" id="delete-form-{{ $bill->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    title="{{ __('Delete') }}">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
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
                    <h5 class="d-inline-block mb-5">{{ __('Service Bills') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Expense') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($simpleExpenses as $expense)
                                    <tr>
                                        <td>
                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($expense->id)) }}"
                                                class="btn btn-outline-primary">{{ $expense->expense_id }}</a>
                                        </td>
                                        <td>{{ !empty($expense->category) ? $expense->category->name : '-' }}</td>
                                        <td>{{ Auth::user()->dateFormat($expense->expense_date) }}</td>
                                        <td>
                                            <span class="status_badge badge bg-primary p-2 px-3 rounded">
                                                {{ __(\App\Models\SimpleExpense::$statues[$expense->status] ?? 'Unknown') }}
                                            </span>
                                        </td>
                                        <td>{{ \Auth::user()->priceFormat($expense->getTotal()) }}</td>
                                        <td>
                                            @if($expense->payment_status == 0)
                                                <span class="badge bg-warning">{{ __('Unpaid') }}</span>
                                            @elseif($expense->payment_status == 2)
                                                <span class="badge bg-info">{{ __('Partially Paid') }}</span>
                                            @elseif($expense->payment_status == 4)
                                                <span class="badge bg-success">{{ __('Paid') }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('simple-expense.show', \Crypt::encrypt($expense->id)) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No service bills found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
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
                    <h5 class="d-inline-block mb-5">{{ __('Direct Expenses') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Expense Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Items') }}</th>
                                    <th>{{ __('Total Amount') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <th width="10%">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($directExpenses as $expense)
                                    <tr>
                                        <td>
                                            <a href="{{ route('direct_expenses.show', $expense->id) }}"
                                                class="btn btn-outline-primary">
                                                {{ Auth::user()->expenseNumberFormat($expense->expense_number) }}
                                            </a>
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($expense->created_at) }}</td>
                                        <td>{{ $expense->items->count() }}</td>
                                        <td>{{ \Auth::user()->priceFormat($expense->getTotalAmount() + $expense->getTotalTaxAmount()) }}</td>
                                        <td>
                                            @if($expense->currency)
                                                {{ $expense->currency->code }}
                                            @else
                                                {{ Auth::user()->currencySymbol() }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($expense->payment_status == 0)
                                                <span class="badge bg-warning">{{ __('Unpaid') }}</span>
                                            @elseif($expense->payment_status == 2)
                                                <span class="badge bg-info">{{ __('Partially Paid') }}</span>
                                            @elseif($expense->payment_status == 4)
                                                <span class="badge bg-success">{{ __('Paid') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                            @endif
                                        </td>
                                        @if (Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                            <td class="Action">
                                                <span>
                                                    @can('show bill')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('direct_expenses.show', $expense->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Show') }}"
                                                                data-original-title="{{ __('Detail') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No direct expenses found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <h5 class="d-inline-block mb-5">{{ __('Payment') }}</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __(key: 'Payment') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th> {{ __('Status') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Payment Receipt') }}</th>
                                    <th> {{ __('Bill') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($venderPyment as $payment)
                                    <tr>
                                        {{--                                        <td>{{ AUth::user()->posNumberFormat($posPayment->pos_id) }}</td> --}}
                                        <td>{{ \Auth::user()->paymentNumberFormat($payment->payment_number ?? $payment->id) }}</td>
                                        <td>{{ Auth::user()->dateFormat($payment->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($payment->amount) }}</td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : '' }}
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
                                        <td>{{ !empty($payment->vender) ? $payment->vender->name : '-' }}</td>
                                        <td>{{ !empty($payment->category) ? $payment->category->name : '-' }}</td>
                                        <td>{{ !empty($payment->reference) ? $payment->reference : '-' }}</td>
                                        <td>{{ !empty($payment->description) ? $payment->description : '-' }}</td>
                                        <td>
                                            @if (!empty($payment->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ asset('uploads/payment') . '/' . $payment->add_receipt }}"
                                                    download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ asset('uploads/payment') . '/' . $payment->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}"
                                                    target="_blank"><span class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        <td>
                                            @php
                                                $billPayments = $payment->billPayments()->with('bill')->get();
                                            @endphp
                                            @if ($billRecord)
                                                @if ($billRecord->type == 'Bill')
                                                    <a href="{{ route('bill.show', \Crypt::encrypt($payment->bill_id)) }}"
                                                        class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($billRecord->bill_id) }}</a>
                                                @else
                                                    <a href="{{ route('expense.show', \Crypt::encrypt($payment->bill_id)) }}"
                                                        class="btn btn-outline-primary">{{ $billRecord->bill_id }}</a>
                                                @endif
                                            @else
                                                -
                                                @if ($billPayments->isNotEmpty())
                                                    @foreach ($billPayments as $billPayment)
                                                        @php $bill = $billPayment->bill; @endphp
                                                        @if ($bill)
                                                            @if ($bill->type == 'Bill')
                                                                <a href="{{ route('bill.show', \Crypt::encrypt($bill->id)) }}"
                                                                    class="btn btn-outline-primary mb-1">
                                                                    {{ Auth::user()->billNumberFormat($bill->bill_id) }}
                                                                </a>
                                                            @else
                                                                <a href="{{ route('expense.show', \Crypt::encrypt($bill->id)) }}"
                                                                    class="btn btn-outline-primary mb-1">
                                                                    {{ $bill->bill_id }}
                                                                </a>
                                                            @endif
                                                        @else
                                                            <span class="text-danger d-block mb-1">Bill not found</span>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
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
                    <form id="fileUploadForm" action="{{ route('upload.file.vendor') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="vendorId" value="{{ $vendor['id'] }}">
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
