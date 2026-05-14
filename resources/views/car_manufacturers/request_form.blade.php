@extends('layouts.admin')

@section('content')
<div class="container">
    <h3>Create Car Accessories Request</h3>

    <form action="{{ route('car_accessories.storeRequest') }}" method="POST" id="request-form">
        @csrf

        <div class="mb-3">
            <label class="form-label">Request Date</label>
            <input type="date" name="request_date" class="form-control" value="{{ now()->toDateString() }}">
        </div>

        @foreach ($cars as $index => $car)
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Car:</strong> {{ $car->product_no }} — {{ $car->name }}
                </div>
                <div class="card-body">
                    <input type="hidden" name="cars[{{ $index }}][car_id]" value="{{ $car->id }}">

                    <table class="table align-middle" id="items-table-{{ $index }}">
                        <thead>
                            <tr>
                                <th style="width: 45%">Accessory</th>
                                <th style="width: 20%">Quantity</th>
                                <th style="width: 25%">Sell Price</th>
                                <th style="width: 10%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="cars[{{ $index }}][items][0][accessory_id]" class="form-select" required>
                                        <option value="">Select accessory</option>
                                        @foreach($accessories as $acc)
                                            <option value="{{ $acc->product_id }}">{{ $acc->label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" name="cars[{{ $index }}][items][0][quantity]" class="form-control" value="1" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="cars[{{ $index }}][items][0][sell_price]" class="form-control">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-light add-row" data-car-index="{{ $index }}">+
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">Save Request</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-row').forEach(function(btn){
        btn.addEventListener('click', function(){
            const carIndex = this.getAttribute('data-car-index');
            const table = document.getElementById('items-table-' + carIndex).querySelector('tbody');
            const currentRows = table.querySelectorAll('tr').length;

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select name="cars[${carIndex}][items][${currentRows}][accessory_id]" class="form-select" required>
                        <option value="">Select accessory</option>
                        ${`@foreach($accessories as $acc)<option value="{{ $acc->product_id }}">{{ $acc->label }}</option>@endforeach`}
                    </select>
                </td>
                <td>
                    <input type="number" min="1" name="cars[${carIndex}][items][${currentRows}][quantity]" class="form-control" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="cars[${carIndex}][items][${currentRows}][sell_price]" class="form-control">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                </td>
            `;
            table.appendChild(newRow);

            newRow.querySelector('.remove-row').addEventListener('click', function(){
                newRow.remove();
            });
        });
    });
});
</script>
@endsection


