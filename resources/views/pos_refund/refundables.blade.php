@extends('layouts.admin')

@section('content')
<div class="container">
    <h3>Refundable Items</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product No</th>
                <th>Product Name</th>
                <th>Unit Price</th>
                <th>Total Bought</th>
                <th>Total Paid</th>
                <th>Total Refunded</th>
                <th>Available to Refund</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->product_no }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->total_bought }}</td>
                    <td>{{ number_format($item->total_paid, 2) }}</td>
                    <td>{{ $item->total_refunded }}</td>
                    <td>{{ $item->available_to_refund }}</td>
                    <td>
                        @if($item->available_to_refund > 0)
                            <form method="POST">
                                @csrf
                                <input type="hidden" name="product_no" value="{{ $item->product_no }}">
                                <input type="number" name="quantity" max="{{ $item->available_to_refund }}" min="1" class="form-control" required>
                                <button type="submit" class="btn btn-warning btn-sm mt-2">Refund</button>
                            </form>
                        @else
                            <span class="text-muted">Fully Refunded</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
