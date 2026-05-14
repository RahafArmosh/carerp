@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Create Car Accessories Request</h3>

    {{-- Search Form --}}
    <form action="{{ route('car_accessories.doSearch') }}" method="POST" class="mb-4">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-2">
                <input type="text" name="invoice_no" class="form-control" placeholder="Search by Invoice No">
            </div>
            <div class="col-md-4 mb-2">
                <input type="text" name="bill_no" class="form-control" placeholder="Search by Bill No">
            </div>
            <div class="col-md-4 mb-2">
                <input type="text" name="product_no" class="form-control" placeholder="Search by Product No">
            </div>
            <div class="col-md-4 mb-2">
                <input type="text" name="warehouse" class="form-control" placeholder="Search by Warehouse">
            </div>
            <div class="col-md-4 mb-2">
                <input type="text" name="customer_name" class="form-control" placeholder="Search by Customer Name">
            </div>
            <div class="col-md-4 mb-2">
                <input type="text" name="vendor_name" class="form-control" placeholder="Search by Vendor Name">
            </div>
        </div>
        <button class="btn btn-primary">Search</button>
    </form>

    {{-- Results --}}
    @isset($cars)
        <h5>Search Results</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product No</th>
                    <th>Name</th>
                    <th>Warehouse</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cars as $car)
                <tr>
                    <td>{{ $car->product_no }}</td>
                    <td>{{ $car->name }}</td>
                    <td>{{ $car->warehouse }}</td>
                    <td>
                        <a href="{{ route('car_accessories.create', ['car_id' => $car->id]) }}" class="btn btn-success btn-sm">
                            Add Accessories
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center">No cars found.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endisset
</div>
@endsection
