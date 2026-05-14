@extends('layouts.admin')
@section('page-title')
    {{__('POS Summary')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('POS Summary')}}</li>
@endsection
@section('action-btn')
    @if(\Auth::user()->type == 'company')
        <div class="float-end">
            <a href="{{ route('pos.logs') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('View POS Logs') }}">
                <i class="ti ti-list"></i> {{ __('POS Logs') }}
            </a>
        </div>
    @endif
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
@endpush

@section('content')
    <div id="printableArea">
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-3">{{ __('POS Summary') }}</h5>
                        <form method="GET" action="{{ url('/report/pos') }}" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('From Date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('To Date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control">
                                    <option value="">{{ __('All Warehouses') }}</option>
                                    @foreach(($warehouses ?? []) as $warehouseId => $warehouseName)
                                        <option value="{{ $warehouseId }}" {{ (string)($filters['warehouse_id'] ?? '') === (string)$warehouseId ? 'selected' : '' }}>
                                            {{ $warehouseName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Cashier') }}</label>
                                <select name="cashier_id" class="form-control">
                                    <option value="">{{ __('All Cashiers') }}</option>
                                    @foreach(($cashiers ?? []) as $cashierId => $cashierName)
                                        <option value="{{ $cashierId }}" {{ (string)($filters['cashier_id'] ?? '') === (string)$cashierId ? 'selected' : '' }}>
                                            {{ $cashierName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-filter"></i> {{ __('Filter') }}
                                </button>
                                <a href="{{ url('/report/pos') }}" class="btn btn-secondary">
                                    <i class="ti ti-refresh"></i> {{ __('Reset') }}
                                </a>
                                <a href="{{ url('/report/pos/export') . '?' . http_build_query(request()->query()) }}" class="btn btn-success">
                                    <i class="ti ti-file-export"></i> {{ __('Export to Excel') }}
                                </a>
                                <button type="button" onclick="window.open('{{ url('/report/pos/print') . '?' . http_build_query(request()->query()) }}', '_blank')" class="btn btn-info">
                                    <i class="ti ti-printer"></i> {{ __('Print Report') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead>
                                <tr>
                                    <th>{{__('POS ID')}}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Cashier') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Sub Total') }}</th>
                                    <th>{{ __('Discount') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th>{{ __('Total') }}</th>
                                    <th>{{ __('Actual Amount Paid') }}</th>
                                    <th>{{ __('Difference') }}</th>
                                    @if(\Auth::user()->type == 'company')
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                                </thead>

                                <tbody>

                                @forelse ($posPayments as $posPayment)
                                    @php
                                        // Pre-aggregated in controller via withSum() to avoid N+1 queries
                                        $actualPaid = (float)($posPayment->actual_paid_sum ?? 0);
                                        
                                        // Calculate values for display:
                                        // Sub Total = Raw subtotal (before discount, before tax)
                                        $rawSubtotal = $posPayment->getRawSubTotal();
                                        
                                        // Total Discount = Product discount + Overall discount
                                        $totalDiscount = $posPayment->getTotalDiscountAmount();
                                        
                                        // Tax = calculated on (subtotal - discount)
                                        $tax = $posPayment->getTotalTax();
                                        
                                        // Total = (subtotal - discount) + tax; show 0 if negative
                                        $total = $posPayment->getTotal();
                                        $displayTotal = $total < 0 ? 0 : $total;

                                        // Pre-aggregated in controller via withSum() to avoid looping large collections
                                        $totalQuantity = (float)($posPayment->items_qty_sum ?? 0);

                                        // Difference between calculated total and actual paid
                                        $difference = $displayTotal - $actualPaid;
                                    @endphp
                                    <tr>
{{--                                        <td>{{ AUth::user()->posNumberFormat($posPayment->pos_id) }}</td>--}}

                                        <td class="Id">
                                            <a href="{{ route('pos.show',\Crypt::encrypt($posPayment->id)) }}" class="btn btn-outline-primary">
                                                {{ AUth::user()->posNumberFormat($posPayment->pos_id) }}
                                            </a>
                                        </td>
                                        <td>{{ Auth::user()->dateFormat($posPayment->pos_date)}}</td>
                                        @if($posPayment->customer_id == 0)
                                            <td class="">{{__('Walk-in Customer')}}</td>
                                        @else
                                            <td>{{ !empty($posPayment->customer) ? $posPayment->customer->name : '' }} </td>
                                        @endif
                                        <td>{{ !empty($posPayment->warehouse) ? $posPayment->warehouse->name : '' }} </td>
                                        <td>{{ !empty($posPayment->cashier) ? $posPayment->cashier->name : __('N/A') }}</td>
                                        <td><strong>{{ number_format($totalQuantity, 2) }}</strong></td>
                                        <td><strong>{{ \Auth::user()->priceFormat($rawSubtotal) }}</strong></td>
                                        <td>{{ \Auth::user()->priceFormat($totalDiscount) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($tax) }}</td>
                                        <td><strong>{{ \Auth::user()->priceFormat($displayTotal) }}</strong></td>
                                        <td><strong>{{ \Auth::user()->priceFormat($actualPaid) }}</strong></td>
                                        <td><strong>{{ \Auth::user()->priceFormat($difference) }}</strong></td>
                                        @if(\Auth::user()->type == 'company')
                                            <td>
                                                @if(\Auth::user()->can('change pos date') || in_array(\Auth::user()->type, ['company', 'super admin']))
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning me-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editPosDateModal-{{ $posPayment->id }}"
                                                            title="{{ __('Edit POS Date') }}">
                                                        <i class="ti ti-calendar-time"></i>
                                                    </button>

                                                    <div class="modal fade" id="editPosDateModal-{{ $posPayment->id }}" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">{{ __('Edit POS Date') }} - {{ \Auth::user()->posNumberFormat($posPayment->pos_id) }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                                                                </div>
                                                                <form action="{{ route('pos.update-date', $posPayment->id) }}" method="POST">
                                                                    @csrf
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">{{ __('POS Date') }}</label>
                                                                            <input type="date"
                                                                                   name="pos_date"
                                                                                   class="form-control"
                                                                                   value="{{ $posPayment->pos_date }}"
                                                                                   required>
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            {{ __('This updates POS date and related ledger send date.') }}
                                                                        </small>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="action-btn bg-danger ms-2 d-inline-block">
                                                    <form action="{{ route('pos.destroy', $posPayment->id) }}" method="POST" id="delete-form-{{ $posPayment->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#" class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                           data-bs-toggle="tooltip"
                                                           title="{{ __('Delete') }}"
                                                           onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to delete this POS? This will reverse all ledger entries and return stock.') }}')) { document.getElementById('delete-form-{{ $posPayment->id }}').submit(); }">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ \Auth::user()->type == 'company' ? '13' : '12' }}" class="text-center text-dark"><p>{{__('No Data Found')}}</p></td>
                                    </tr>
                                @endforelse
                                
                                {{-- Footer Row with Totals --}}
                                @if(isset($mainTotals))
                                    <tfoot>
                                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                                            <td colspan="5" class="text-end"><strong>{{ __('Sales Total') }}:</strong></td>
                                            <td><strong>{{ number_format($mainTotals['quantity'] ?? 0, 2) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat($mainTotals['subtotal']) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat($mainTotals['discount']) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat($mainTotals['tax']) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat($mainTotals['total']) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat($mainTotals['actual_paid']) }}</strong></td>
                                            <td><strong>{{ \Auth::user()->priceFormat(($mainTotals['total'] ?? 0) - ($mainTotals['actual_paid'] ?? 0)) }}</strong></td>
                                            @if(\Auth::user()->type == 'company')
                                                <td></td>
                                            @endif
                                        </tr>
                                        @if(isset($refundTotals) && ($refundTotals['quantity'] > 0 || $refundTotals['total'] > 0))
                                            <tr style="background-color: #fff3cd; font-weight: bold;">
                                                <td colspan="5" class="text-end"><strong>{{ __('Refunds') }}:</strong></td>
                                                <td><strong>- {{ number_format($refundTotals['quantity'], 2) }}</strong></td>
                                                <td colspan="3"></td>
                                                <td><strong>- {{ \Auth::user()->priceFormat($refundTotals['total']) }}</strong></td>
                                                <td><strong>- {{ \Auth::user()->priceFormat($refundTotals['total']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat(0) }}</strong></td>
                                                @if(\Auth::user()->type == 'company')
                                                    <td></td>
                                                @endif
                                            </tr>
                                            <tr style="background-color: #d1e7dd; font-weight: bold;">
                                                <td colspan="5" class="text-end"><strong>{{ __('Net Total') }}:</strong></td>
                                                <td><strong>{{ number_format(($mainTotals['quantity'] ?? 0) - $refundTotals['quantity'], 2) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat($mainTotals['subtotal']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat($mainTotals['discount']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat($mainTotals['tax']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat(($mainTotals['total'] ?? 0) - $refundTotals['total']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat(($mainTotals['actual_paid'] ?? 0) - $refundTotals['total']) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat((($mainTotals['total'] ?? 0) - $refundTotals['total']) - (($mainTotals['actual_paid'] ?? 0) - $refundTotals['total'])) }}</strong></td>
                                                @if(\Auth::user()->type == 'company')
                                                    <td></td>
                                                @endif
                                            </tr>
                                        @endif
                                    </tfoot>
                                @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- POS Refunds Section (filtered by refund date) --}}
                        @if(isset($posRefunds) && $posRefunds->isNotEmpty())
                            <div class="mt-4">
                                <h5 class="mb-3">{{ __('POS Refunds') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Refund ID') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Original POS') }}</th>
                                                <th>{{ __('Customer') }}</th>
                                                <th>{{ __('Warehouse') }}</th>
                                                <th>{{ __('Cashier') }}</th>
                                                <th>{{ __('Voucher ID') }}</th>
                                                <th>{{ __('Quantity') }}</th>
                                                <th>{{ __('Total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($posRefunds as $refund)
                                                @php
                                                    $refundQty = $refund->items->sum('quantity');
                                                @endphp
                                                <tr>
                                                    <td><strong>#{{ $refund->id }}</strong></td>
                                                    <td>{{ Auth::user()->dateFormat($refund->created_at) }}</td>
                                                    <td>
                                                        <a href="{{ route('pos.show', \Crypt::encrypt($refund->pos_id)) }}" class="btn btn-outline-primary btn-sm">
                                                            {{ Auth::user()->posNumberFormat($refund->pos->pos_id ?? '') }}
                                                        </a>
                                                    </td>
                                                    @if($refund->pos && $refund->pos->customer_id == 0)
                                                        <td>{{ __('Walk-in Customer') }}</td>
                                                    @else
                                                        <td>{{ $refund->pos && $refund->pos->customer ? $refund->pos->customer->name : __('N/A') }}</td>
                                                    @endif
                                                    <td>{{ $refund->pos && $refund->pos->warehouse ? $refund->pos->warehouse->name : __('N/A') }}</td>
                                                    <td>{{ $refund->pos && $refund->pos->cashier ? $refund->pos->cashier->name : __('N/A') }}</td>
                                                    <td>
                                                        @if($refund->voucher)
                                                            <a href="{{ route('vouchers.index') }}" target="_blank" class="text-primary">
                                                                #{{ $refund->voucher->id }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td><strong>{{ number_format($refundQty, 2) }}</strong></td>
                                                    <td><strong>{{ \Auth::user()->priceFormat($refund->total_amount) }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr style="background-color: #fff3cd; font-weight: bold;">
                                                <td colspan="7" class="text-end"><strong>{{ __('Refunds Total') }}:</strong></td>
                                                <td><strong>{{ number_format($refundTotals['quantity'] ?? 0, 2) }}</strong></td>
                                                <td><strong>{{ \Auth::user()->priceFormat($refundTotals['total'] ?? 0) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        @if(isset($warehouseTotals) && !empty($warehouseTotals))
                            <div class="mt-4">
                                <h5 class="mb-3">{{ __('Payment Totals by Warehouse') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Warehouse') }}</th>
                                                <th class="text-end">{{ __('Total Card Payments') }}</th>
                                                <th class="text-end">{{ __('Total Cash Payments') }}</th>
                                                <th class="text-end">{{ __('Total All Payments') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($warehouseTotals as $warehouseId => $totals)
                                                @php
                                                    $warehouseTotal = $totals['card_total'] + $totals['cash_total'];
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $totals['warehouse_name'] }}</strong></td>
                                                    <td class="text-end">{{ \Auth::user()->priceFormat($totals['card_total']) }}</td>
                                                    <td class="text-end">{{ \Auth::user()->priceFormat($totals['cash_total']) }}</td>
                                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($warehouseTotal) }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if(isset($warehouseTotalsFooter))
                                            <tfoot>
                                                <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                    <td class="text-end"><strong>{{ __('Total All Warehouses') }}:</strong></td>
                                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($warehouseTotalsFooter['card_total']) }}</strong></td>
                                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($warehouseTotalsFooter['cash_total']) }}</strong></td>
                                                    <td class="text-end"><strong>{{ \Auth::user()->priceFormat($warehouseTotalsFooter['card_total'] + $warehouseTotalsFooter['cash_total']) }}</strong></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
