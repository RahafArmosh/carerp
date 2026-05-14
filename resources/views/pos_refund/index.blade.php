@extends('layouts.admin')
@section('page-title')
    {{ __('POS Refunds') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('POS Refunds') }}</li>
@endsection
@push('script-page')
    <script>
        function toggleRefundItems(refundId) {
            const itemsRow = document.getElementById('refund-items-' + refundId);
            const toggleIcon = document.getElementById('toggle-icon-' + refundId);
            
            if (itemsRow.style.display === 'none' || itemsRow.style.display === '') {
                itemsRow.style.display = 'table-row';
                toggleIcon.classList.remove('ti-chevron-right');
                toggleIcon.classList.add('ti-chevron-down');
            } else {
                itemsRow.style.display = 'none';
                toggleIcon.classList.remove('ti-chevron-down');
                toggleIcon.classList.add('ti-chevron-right');
            }
        }
    </script>
@endpush
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"></th>
                                    <th>{{ __('Refund ID') }}</th>
                                    <th>{{ __('POS ID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Refund Amount') }}</th>
                                    <th>{{ __('Voucher ID') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($refunds as $refund)
                                    @php
                                        // Calculate total quantity from children items
                                        $totalQuantity = $refund->items->sum('quantity');
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($refund->items->count() > 0)
                                                <button type="button" class="btn btn-sm btn-link p-0" onclick="toggleRefundItems({{ $refund->id }})" style="cursor: pointer;">
                                                    <i id="toggle-icon-{{ $refund->id }}" class="ti ti-chevron-right"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="javascript:void(0);" onclick="toggleRefundItems({{ $refund->id }})" style="cursor: pointer; text-decoration: none; color: inherit;">
                                                <strong>#{{ $refund->id }}</strong>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('pos.show', \Crypt::encrypt($refund->pos_id)) }}" target="_blank">
                                                {{ \Auth::user()->posNumberFormat($refund->pos->pos_id) }}
                                            </a>
                                        </td>
                                        <td>{{ $refund->pos->customer->name ?? 'N/A' }}</td>
                                        <td><strong>{{ $totalQuantity }}</strong></td>
                                        <td><strong>{{ \Auth::user()->priceFormat($refund->total_amount) }}</strong></td>
                                        <td>
                                            @if($refund->voucher)
                                                <a href="{{ route('vouchers.index') }}" target="_blank" class="text-primary">
                                                    #{{ $refund->voucher->id }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($refund->created_at) }}</td>
                                        <td>{{ $refund->description ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('pos.show', \Crypt::encrypt($refund->pos_id)) }}" target="_blank"
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        title="{{ __('View POS') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="{{ route('pos_product_refund.print', $refund->id) }}" target="_blank"
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        title="{{ __('Print Refund') }}">
                                                        <i class="ti ti-printer text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-success ms-2">
                                                    <a href="{{ route('pos_product_refund.ledger', $refund->id) }}" target="_blank"
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        title="{{ __('Show Accounting') }}">
                                                        <i class="ti ti-file-text text-white"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Refund Items Row (Hidden by default) --}}
                                    @if($refund->items->count() > 0)
                                        <tr id="refund-items-{{ $refund->id }}" style="display: none; background-color: #f8f9fa;">
                                            <td colspan="10">
                                                <div class="p-3">
                                                    <h6 class="mb-3">{{ __('Refund Items') }}:</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Item') }}</th>
                                                                <th>{{ __('Quantity') }}</th>
                                                                <th>{{ __('Refund Price') }}</th>
                                                                <th>{{ __('Combo') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($refund->items as $item)
                                                                @php
                                                                    $productName = 'N/A';
                                                                    $productNo = $item->product_no ?? 'N/A';
                                                                    
                                                                    if ($item->posProduct) {
                                                                        if ($item->posProduct->sub_product && $item->posProduct->sub_product->productService) {
                                                                            $productName = $item->posProduct->sub_product->productService->name ?? 'N/A';
                                                                            $productNo = $item->posProduct->sub_product->product_no ?? $productNo;
                                                                        } elseif ($item->posProduct->product) {
                                                                            $productName = $item->posProduct->product->name ?? 'N/A';
                                                                        }
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <div>
                                                                            <strong>{{ $productName }}</strong>
                                                                            @if($productNo != 'N/A')
                                                                                <br><small class="text-muted">({{ $productNo }})</small>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                    <td>{{ $item->quantity }}</td>
                                                                    <td>{{ \Auth::user()->priceFormat($item->return_price) }}</td>
                                                                    <td>
                                                                        @if($item->combo_id)
                                                                            <span class="badge bg-info">{{ __('Yes') }}</span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <p class="text-muted">{{ __('No refunds found') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection