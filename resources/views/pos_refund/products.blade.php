@extends('layouts.admin')

@section('page-title') {{ __('Refund Products') }} @endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <form method="POST" action="{{ route('pos_product_refund.store_products_refund') }}">
            @method('POST')
            @csrf
            <input type="hidden" name="pos_id" value="{{ $pos->id }}">

            <div class="card">
                <div class="card-header">
                    <h5>{{ __('POS #') }} {{ $pos->id }} - {{ __('Refund Products') }}</h5>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Product No') }}</th>
                                <th>{{ __('Unit Price (avg)') }}</th>
                                <th>{{ __('Total Bought') }}</th>
                                <th>{{ __('Paid Units') }}</th>
                                <th>{{ __('Total Paid') }}</th>
                                <th>{{ __('Already Refunded Qty') }}</th>
                                <th>{{ __('Already Refunded Amount') }}</th>
                                <th>{{ __('Available to Refund') }}</th>
                                <th>{{ __('Refund Qty') }}</th>
                                <th>{{ __('Estimated Refund Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $item)
                                <tr data-product="{{ $item['product_no'] }}" 
                                    data-avg-paid="{{ $item['avg_paid_per_item'] }}">
                                    <td>{{ $item['product_no'] }}</td>
                                    <td>{{ number_format($item['unit_price_avg'], 2) }}</td>
                                    <td>{{ $item['total_bought'] }}</td>
                                    <td>{{ $item['paid_units'] }}</td>
                                    <td>{{ number_format($item['total_paid'], 2) }}</td>
                                    <td>{{ $item['refunded_quantity'] }}</td>
                                    <td>{{ number_format($item['refunded_amount'], 2) }}</td>
                                    <td>{{ $item['available_to_refund'] }}</td>
                                    <td style="min-width:120px">
                                        @if($item['available_to_refund'] > 0)
                                            <input type="number"
                                                   name="refunds[{{ $item['product_no'] }}]"
                                                   class="form-control refund-qty"
                                                   min="0"
                                                   max="{{ $item['available_to_refund'] }}"
                                                   value="0">
                                        @else
                                            <span class="text-muted">{{ __('0') }}</span>
                                            <input type="hidden" name="refunds[{{ $item['product_no'] }}]" value="0">
                                        @endif
                                    </td>
                                    <td>
                                        <span class="estimated-refund">0.00</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-end">
                    <a href="{{ route('pos_product_refund.create') }}" class="btn btn-light">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Submit Refund') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('script-page')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('tr[data-product]');
        rows.forEach(row => {
            const qtyInput = row.querySelector('.refund-qty');
            const avgPaid = parseFloat(row.dataset.avgPaid) || 0;
            const estSpan = row.querySelector('.estimated-refund');

            if (!qtyInput) return;

            const update = () => {
                let qty = parseInt(qtyInput.value || 0, 10);
                if (isNaN(qty) || qty <= 0) {
                    estSpan.textContent = (0).toFixed(2);
                    return;
                }
                const val = qty * avgPaid;
                estSpan.textContent = val.toFixed(2);
            };
            qtyInput.addEventListener('input', update);
            update();
        });
    });
</script>
@endpush

@endsection
