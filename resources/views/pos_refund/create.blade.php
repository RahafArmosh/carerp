@extends('layouts.admin')

@section('page-title')
    {{ __('POS Refunds') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('POS Refunds') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('pos_product_refund.index') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-list"></i> {{ __('View All Refunds') }}
        </a>
    </div>
@endsection

@section('content')
    <!-- Create New Refund Section -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Create New Refund') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pos_product_refund.get_products_to_refund') }}">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="Pos_id" class="form-label">{{ __('POS') }}</label>
                                <select name="Pos_id" id="Pos_id" class="form-control select2" required>
                                    <option value="">Select POS</option>
                                    @foreach ($poss as $item)
                                        <option value="{{ $item->id }}">{{ \Auth::user()->posNumberFormat($item->pos_id) }} - {{ $item->customer->name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" onclick="history.back()">
                                <input type="submit" value="{{ __('Next') }}" class="btn btn-primary">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Refunds Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Recent Refunds') }}</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('POS ID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Product No') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Refund Amount') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentRefunds = \App\Models\PosProductsRefund::with(['pos.customer', 'posProduct.sub_product'])
                                        ->whereHas('pos', function($query) {
                                            $query->where('created_by', Auth::user()->creatorId());
                                        })
                                        ->orderBy('created_at', 'desc')
                                        ->limit(10)
                                        ->get();
                                @endphp
                                @forelse($recentRefunds as $refund)
                                    <tr>
                                        <td>
                                            <a href="{{ route('pos.show', \Crypt::encrypt($refund->pos_id)) }}" target="_blank">
                                                {{ \Auth::user()->posNumberFormat($refund->pos->pos_id) }}
                                            </a>
                                        </td>
                                        <td>{{ $refund->pos->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $refund->product_no }}</td>
                                        <td>{{ $refund->quantity }}</td>
                                        <td>{{ \Auth::user()->priceFormat($refund->return_price) }}</td>
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
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <p class="text-muted">{{ __('No refunds found') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($recentRefunds->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('pos_product_refund.index') }}" class="btn btn-sm btn-secondary">
                                {{ __('View All Refunds') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
