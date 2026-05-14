@extends('layouts.admin')
@section('page-title')
    {{ __('Expense Detail') }}
@endsection
@push('script-page')
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
@php
    $settings = Utility::settings();
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expense.index') }}">{{ __('Expense') }}</a></li>
    <li class="breadcrumb-item">{{ $expense->bill_id }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{ __('Expense') }}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ $expense->bill_id }}</h4>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5">
                                    <small class="font-style">
                                        <strong>{{ __('Billed To') }} :</strong><br>
                                        @if (!empty($user->name))
                                            {{ !empty($user->name) ? $user->name : '' }}<br>
                                            {{ !empty($user->billing_address) ? $user->billing_address : '' }}<br>
                                            {{ !empty($user->billing_city) ? $user->billing_city : '' . ', ' }}<br>
                                            {{ !empty($user->billing_state) ? $user->billing_state : '' . ', ' }},
                                            {{ !empty($user->billing_zip) ? $user->billing_zip : '' }}<br>
                                            {{ !empty($user->billing_country) ? $user->billing_country : '' }}<br>
                                            {{ !empty($user->billing_phone) ? $user->billing_phone : '' }}<br>
                                            @if ($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{ __('Tax Number ') }} :
                                                </strong>{{ !empty($user->tax_number) ? $user->tax_number : '' }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>
                                @if (App\Models\Utility::getValByName('shipping_display') == 'on')
                                    <div class="col-4">
                                        <small>
                                            <strong>{{ __('Shipped To') }} :</strong><br>
                                            @if (!empty($user->shipping_name))
                                                {{ !empty($user->shipping_name) ? $user->shipping_name : '' }}<br>
                                                {{ !empty($user->shipping_address) ? $user->shipping_address : '' }}<br>
                                                {{ !empty($user->shipping_city) ? $user->shipping_city : '' . ', ' }}<br>
                                                {{ !empty($user->shipping_state) ? $user->shipping_state : '' . ', ' }},
                                                {{ !empty($user->shipping_zip) ? $user->shipping_zip : '' }}<br>
                                                {{ !empty($user->shipping_country) ? $user->shipping_country : '' }}<br>
                                                {{ !empty($user->shipping_phone) ? $user->shipping_phone : '' }}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif



                                <div class="col">
                                    <small>
                                        <strong>{{ __('Payment Date') }} :</strong><br>
                                        {{ \Auth::user()->dateFormat($expense->bill_date) }}<br><br>
                                    </small>

                                </div>

                            </div>
                            <div class="row">

                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Status') }} : </strong><br>
                                        <span
                                            class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Bill::$statues[$expense->status]) }}</span>

                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Payment Status') }} :</strong><br>
                                        @php
                                            $due = $expense->getExpenseDue();
                                            $total = $expense->getTotal();
                                        @endphp
                                        @if($due <= 0)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __('Paid') }}</span>
                                        @elseif($due < $total)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __('Partially Paid') }}</span>
                                        @else
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __('Unpaid') }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="col">
                                    <small>
                                        <strong>{{ __('Currencу') }} :</strong><br>
                                        @if ($expense->currency_id != null)
                                            <span
                                                class="badge bg-info p-2 px-3 rounded">{{ $expense->currency->name }}</span>
                                        @else
                                            <span
                                                class="badge bg-warning p-2 px-3 rounded">{{ $settings['site_currency'] }}</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-between align-items-center mb-3">
        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">

            {{-- <div class="all-button-box mx-2">
                <a href="{{ route('expense.edit', \Crypt::encrypt($expense->id)) }}" class="btn btn-sm btn-primary">
                    {{ __('Edit') }}
                </a>
            </div> --}}

            <div class="all-button-box">
                <a href="" target="_blank" class="btn btn-sm btn-primary">
                    {{ __('Download') }}
                </a>
            </div>

            @if($expense->getExpenseDue() > 0)
                <div class="all-button-box mx-2">
                    <a href="#" data-url="{{ route('expense.payment', Crypt::encrypt($expense->id)) }}" 
                        data-ajax-popup="true" 
                        data-title="{{ __('Add Payment') }}" 
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus mr-2"></i>{{ __('Add Payment') }}
                    </a>
                </div>          
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="font-bold mb-2">{{ __('Product Summary') }}</div>
                            <small class="mb-2">{{ __('All items here cannot be deleted.') }}</small>
                            <div class="table-responsive mt-3">
                                <table class="table mb-0 table-striped">
                                    <tr>
                                        <th class="text-dark" data-width="40">#</th>
                                        <th class="text-dark">{{ __('Product') }}</th>
                                        <th class="text-dark">{{ __('Chart Of Account') }}</th>
                                        <th class="text-dark">{{ __('Account Amount') }}</th>
                                        <th class="text-dark">{{ __('Description') }}</th>
                                        <th class="text-end text-dark" width="12%">{{ __('Total (with tax)') }}</th>
                                        <th></th>
                                    </tr>
                                    @php
                                        $totalQuantity = 0;
                                        $totalRate = 0;
                                        $totalamount = 0;
                                        $totalTaxPrice = 0;
                                        $totalDiscount = 0;
                                        $taxesData = [];
                                    @endphp



                                    @php
                                        $headerTaxRate = 0;
                                        if (!empty($expense->tax_id)) {
                                            foreach (explode(',', $expense->tax_id) as $tid) {
                                                $t = \App\Models\Tax::find($tid);
                                                if ($t) { $headerTaxRate += $t->rate; }
                                            }
                                        }
                                    @endphp
                                    @foreach ($items as $key => $item)
                                        @if (!empty($item->product_id))
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                @php
                                                    $productName = $item->product;
                                                    $subProduct = $item->subProduct;
                                                    $totalQuantity += $item->quantity;
                                                    $totalRate += $item->price;
                                                    $totalamount += $item->amount;
                                                    $totalDiscount += $item->discount;
                                                @endphp
                                                <td>{{ !empty($productName) ? $productName->name . '-' . $productName->sku . '/' . $subProduct->chassis_no : '-' }}
                                                </td>
                                                {{-- Product line: show product name; other columns minimal --}}
                                                @php
                                                    $chartAccount = \App\Models\ChartOfAccount::find(
                                                        $item->chart_account_id,
                                                    );
                                                @endphp
                                                <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                @php $acctAmount = $item->amount ?? 0; $amountWithTax = $acctAmount * (1 + ($headerTaxRate/100)); @endphp
                                                <td>
                                                    {{ (!empty($chartAccount) ? $chartAccount->name : '-') . ' - ' . \Auth::user()->priceFormat($amountWithTax) }}
                                                </td>

                                                <td>{{ !empty($item->description) ? $item->description : '-' }}
                                                </td>

                                                <td class="text-end">
                                                    {{ \Auth::user()->priceFormat($amountWithTax) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>-</td>
                                                @php
                                                    $chartAccount = \App\Models\ChartOfAccount::find(
                                                        $item['chart_account_id'],
                                                    );
                                                @endphp
                                                <td>{{ !empty($chartAccount) ? $chartAccount->name : '-' }}</td>
                                                @php $amountWithTax = $item['amount'] * (1 + ($headerTaxRate/100)); @endphp
                                                <td>
                                                    {{ (!empty($chartAccount) ? $chartAccount->name : '-') . ' - ' . \Auth::user()->priceFormat($amountWithTax) }}
                                                </td>
                                                <td>-</td>
                                                <td class="text-end">
                                                    {{ \Auth::user()->priceFormat($amountWithTax) }}</td>
                                                <td></td>


                                            </tr>
                                        @endif
                                    @endforeach
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <td><b>{{ __('Total') }}</b></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td><b>{{ \Auth::user()->priceFormat($expense->getAccountTotal() * (1 + ($headerTaxRate/100))) }}</b>
                                            </td>

                                        </tr>
                                        <tr>
                                            <td colspan="8"></td>
                                            <td class="text-end"><b>{{ __('Sub Total (with tax)') }}</b></td>
                                            <td class="text-end">
                                                {{ \Auth::user()->priceFormat(price: $expense->getAccountTotal() * (1 + ($headerTaxRate/100))) }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="8"></td>
                                            <td class="blue-text text-end"><b>{{ __('Total') }}</b></td>
                                            <td class="blue-text text-end">
                                                {{ \Auth::user()->priceFormat($expense->getAccountTotal() * (1 + ($headerTaxRate/100))) }}
                                            </td>
                                        </tr>
                                        @php
                                            $paidAmount = \App\Models\BillPayment::where('bill_id', $expense->id)->sum('amount');
                                        @endphp
                                        <tr>
                                            <td colspan="8"></td>
                                            <td class="text-end"><b>{{ __('Paid') }}</b></td>
                                            <td class="text-end">
                                                {{ \Auth::user()->priceFormat($paidAmount) }}
                                            </td>
                                        </tr>
                                        {{-- <tr>
                                            <td colspan="8"></td>
                                            <td class="text-end"><b>{{ __('Due') }}</b></td>
                                            <td class="text-end">
                                                {{ \Auth::user()->priceFormat($expense->getExpenseDue()) }}
                                            </td>
                                        </tr> --}}

                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
