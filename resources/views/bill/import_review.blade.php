@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Import Review') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bills') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Review') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Import Review') }}</h5>
                    <div class="float-end">
                        <a href="{{ route('bill.file.import') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left"></i> {{ __('Back to Import') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Found Products') }}</h5>
                                    <h2>{{ $foundCount }}</h2>
                                    <p class="mb-0">{{ __('Products matched in system') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Missing Products') }}</h5>
                                    <h2>{{ $missingCount }}</h2>
                                    <p class="mb-0">{{ __('Products not found in system') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Total Products') }}</h5>
                                    <h2>{{ $stagingProducts->count() }}</h2>
                                    <p class="mb-0">{{ __('Total items in import') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    @if($missingCount > 0)
                        <div class="alert alert-warning">
                            <h5><i class="ti ti-alert-triangle"></i> {{ __('Action Required') }}</h5>
                            <p>{{ __('You have :count missing products. Please choose how to proceed:', ['count' => $missingCount]) }}</p>
                            
                            <form method="POST" action="{{ route('bill.import.process', $sessionId) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="auto_create">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to auto-create {{ $missingCount }} missing products?')">
                                    <i class="ti ti-check"></i> {{ __('Option A: Auto-Create Missing Products') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('bill.import.process', $sessionId) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="export_missing">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-download"></i> {{ __('Option B: Export Missing Items for Review') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <h5><i class="ti ti-check-circle"></i> {{ __('All Products Found') }}</h5>
                            <p>{{ __('All products in the import were found in the system. You can proceed with the import.') }}</p>
                            
                            <form method="POST" action="{{ route('bill.import.process', $sessionId) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="auto_create">
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-check"></i> {{ __('Process Import') }}
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Products Table -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Row') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th>{{ __('Product Name') }}</th>
                                    <th>{{ __('Product ID') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Purchase Price') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    <th>{{ __('Message') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stagingProducts as $product)
                                    <tr class="{{ $product->status == 'FOUND' ? 'table-success' : 'table-danger' }}">
                                        <td>{{ $product->row_number }}</td>
                                        <td>
                                            @if($product->status == 'FOUND')
                                                <span class="badge bg-success">{{ __('FOUND') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('MISSING') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $product->sku ?? '-' }}</td>
                                        <td>{{ $product->product_name ?? '-' }}</td>
                                        <td>
                                            @if($product->product_id)
                                                <a href="{{ route('productservice.show', $product->product_id) }}" target="_blank">
                                                    {{ $product->product_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $product->quantity }}</td>
                                        <td>{{ \Auth::user()->priceFormat($product->purchase_price) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($product->sale_price) }}</td>
                                        <td>
                                            <small class="text-muted">{{ $product->status_message }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

