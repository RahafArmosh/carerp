@extends('layouts.admin')

@section('page-title')
{{ __('Ledger Records') }}
@endsection

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
</li>
<li class="breadcrumb-item">
    <a href="{{ route('master-ledger.stock', ['warehouse_id' => request('warehouse_id')]) }}">{{ __('Stock') }}</a>
</li>
<li class="breadcrumb-item">
    {{ ucfirst($movement) }} {{ __('Records') }}
</li>
@endsection

@section('content')

<div class="row">
    <div class="col-md-12">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>{{ ucfirst($movement) }} {{ __('Records') }}</h5>
                <a href="{{ route('master-ledger.stock', ['warehouse_id' => request('warehouse_id')]) }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> {{ __('Back to Stock') }}
                </a>
            </div>

            <div class="card-body table-border-style">

                <div class="table-responsive">

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Document Type') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Created At') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td>
                                        @php
                                            // Determine route based on document_type
                                            switch($record->document_type) {
                                                case 'INVOICE':
                                                    $route = route('rentinvoice.show2', $record->document_id);
                                                    break;
                                                case 'ASN':
                                                    $route = route('asn.show', $record->document_id);
                                                    break;
                                                case 'SO':
                                                    $route = route('saleorder.show2', $record->document_id);
                                                    break;
                                                case 'BILL':
                                                    $route = route('bill.show2', $record->document_id);
                                                    break;
                                                default:
                                                    $route = '#';
                                            }
                                        @endphp

                                        <a href="{{ $route }}" target="_blank" class="badge bg-info">
                                            {{ $record->document_type }}
                                        </a>
                                    </td>

                                    <td>{{ $record->qty - $record->qty_out  }}</td>
                                    <td>{{ $record->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">{{ __('No records found') }}</td>
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