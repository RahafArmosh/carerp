@extends('layouts.admin')

@section('page-title', __('Pricing Lists'))

@section('action-btn')
<div class="float-end">
    <a href="#" data-bs-toggle="modal" data-bs-target="#importPricingModal" title="Upload xlsx"
       class="btn btn-sm btn-primary">
        <i class="ti ti-upload"></i>
    </a>

    <a href="{{ route('pricing-lists.create') }}" class="btn btn-sm btn-success" title="Create">
        <i class="ti ti-plus"></i>
    </a>
</div>
@endsection

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Import Errors:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Search --}}
<form method="GET" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by SKU or Product Name"
            value="{{ $search }}"
        >
        <button class="btn btn-primary">Search</button>
    </div>
</form>

{{-- Pricing Table --}}
<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Warehouse</th>

            @foreach($pricingTypes as $type)
                <th>{{ $type->name }}</th>
                <th>Action</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['sku'] }}</td>
                <td>{{ $row['warehouse'] }}</td>

                @foreach($pricingTypes as $type)
                    @php
                        $pricing = $row['prices'][$type->id];
                    @endphp

                    <td>
                        {{ $pricing ? number_format($pricing->current_price, 2) : '-' }}
                    </td>

                    <td>
                        @if($pricing)
                            <a href="{{ route('pricing-lists.edit', $pricing) }}"
                               class="btn btn-sm btn-warning">
                                Edit
                            </a>

                            <form action="{{ route('pricing-lists.destroy', $pricing) }}"
                                  method="POST"
                                  style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure?')"
                                >
                                    Delete
                                </button>
                            </form>
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ 2 + ($pricingTypes->count() * 2) }}" class="text-center">
                    No pricing data found
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Pagination --}}
<div class="mt-3">
    {{ $products->links() }}
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importPricingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('pricing-lists.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Import Pricing List') }}</h5>



                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">-- Select Warehouse --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file"
                               name="file"
                               class="form-control"
                               accept=".xlsx,.xls"
                               required>
                    </div>
                    <br>
                    <br>
                    <a href="{{ route('pricing-lists.export') }}"
                       class="btn btn-sm btn-secondary ms-3">
                        <i class="ti ti-download"></i> Downlaod Template
                    </a>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button class="btn btn-success">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
