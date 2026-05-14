@extends('layouts.admin')

@section('page-title')
{{ __('On Order Records') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
</li>
<li class="breadcrumb-item">
    <a href="{{ route('master-ledger.stock', ['warehouse_id' => request('warehouse_id')]) }}">{{ __('Stock') }}</a>
</li>
<li class="breadcrumb-item">
    {{ __('On Order Records') }}
</li>
@endsection

@section('content')

<div class="row">
    <div class="col-md-12">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>{{ __('On Order Records') }}</h5>

                <a href="{{ route('master-ledger.stock', ['warehouse_id' => request('warehouse_id')]) }}" 
                   class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> {{ __('Back to Stock') }}
                </a>
            </div>

            <div class="card-body table-border-style">

                <div class="table-responsive">

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('PRO Number') }}</th>
                                <th>{{ __('Supplier Name') }}</th>
                                <th>{{ __('Order Qty') }}</th>
                                <th>{{ __('Supplied Qty') }}</th>
                                <th>{{ __('Remaining Qty') }}</th>
                                <th>{{ __('Created At') }}</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse($records as $record)

                            <tr>

                                <td>
                                    <a href="{{ route('pro.show', $record->pro_id) }}" 
                                       target="_blank" 
                                       class="badge bg-info">
                                        {{ $record->pro->pro_no ?? 'PRO' }}
                                    </a>
                                </td>

                                <td>{{ $record->pro->supplier->name }}</td>

                                <td>{{ $record->order_qty }}</td>

                                <td>{{ $record->supplied_qty }}</td>

                                <td>{{ $record->remaining_qty }}</td>

                                <td>{{ $record->created_at->format('Y-m-d') }}</td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="text-center">
                                    {{ __('No records found') }}
                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $records->links() }}
                </div>

            </div>

        </div>

    </div>
</div>

@endsection