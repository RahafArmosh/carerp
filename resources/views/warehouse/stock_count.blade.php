@extends('layouts.admin')

@section('page-title')
    {{ __('Stock Count') }} - {{ $warehouse->name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse.index') }}">{{ __('Warehouse') }}</a></li>
    <li class="breadcrumb-item">{{ __('Stock Count') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Stock Count for') }}: <strong>{{ $warehouse->name }}</strong></h5>
                    <div>
                        <a href="#" data-size="lg" data-url="{{ route('warehouse.stock-count.import-single', $warehouse->id) }}" data-ajax-popup="true"
                            data-bs-toggle="tooltip" title="{{ __('Import Stock Count from Excel') }}" data-title="{{ __('Import Stock Count from Excel') }}"
                            class="btn btn-sm btn-success">
                            <i class="ti ti-file-import"></i> {{ __('Import from Excel') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(request()->get('import_job'))
                        <div class="alert alert-info" id="importJobAlert" data-token="{{ request()->get('import_job') }}">
                            <strong>{{ __('Stock count is applying in background...') }}</strong>
                            <div class="mt-2">
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;" id="importJobProgress"></div>
                                </div>
                                <div class="small text-muted mt-1" id="importJobMessage">{{ __('Queued') }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- When import has many items: show "Apply from import" instead of loading full table (avoids crash) --}}
                    @if(isset($bulkImportApply) && $bulkImportApply)
                        <div class="alert alert-info mb-3">
                            <h6><i class="ti ti-file-import"></i> {{ __('Large import') }}</h6>
                            <p class="mb-2">
                                {{ __('You have imported :count item(s). To avoid the page from freezing, apply the stock count in one step below.', ['count' => $importedCount]) }}
                            </p>
                            <p class="mb-3 text-muted small">
                                {{ __('Profit (count &gt; current): quantity will be added to the newest barcode. Loss (count &lt; current): quantity will be subtracted from the oldest barcode(s) first. Ledger entries will be created with sub-product ID.') }}
                            </p>
                            <form method="POST" action="{{ route('warehouse.stock-count.apply-import', $warehouse->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check"></i> {{ __('Apply stock count from import') }}
                                </button>
                            </form>
                            <a href="{{ route('warehouse.stock-count', $warehouse->id) }}?clear_import=1" class="btn btn-secondary ms-2">
                                <i class="ti ti-x"></i> {{ __('Cancel and show all products') }}
                            </a>
                        </div>
                    @else
                    {{-- Imported Data Display (if any) --}}
                    @if(isset($importedDataForView) && !empty($importedDataForView))
                        <div class="alert alert-info mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6><i class="ti ti-info-circle"></i> {{ __('Imported Stock Count Data') }}</h6>
                                    <p class="mb-0">
                                        @if(isset($importSuccessCount) && $importSuccessCount > 0)
                                            <strong>{{ __('Successfully imported: :count item(s)', ['count' => $importSuccessCount]) }}</strong>
                                            <br>
                                        @endif
                                        {{ __('Showing only imported items with their differences below.') }}
                                    </p>
                                </div>
                                <div class="ms-3">
                                    <a href="{{ route('warehouse.stock-count', $warehouse->id) }}" 
                                       class="btn btn-sm btn-secondary">
                                        <i class="ti ti-x"></i> {{ __('Show All Products') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Import Errors Display --}}
                    @php
                        // Check both flash and persistent session for error items
                        $hasErrorItems = false;
                        if(isset($importErrorItems) && !empty($importErrorItems)) {
                            $hasErrorItems = true;
                        } else {
                            // Try persistent session as fallback
                            $persistentErrorItems = session('import_error_items_' . $warehouse->id, []);
                            if(!empty($persistentErrorItems)) {
                                $importErrorItems = $persistentErrorItems;
                                $hasErrorItems = true;
                            }
                        }
                    @endphp
                    @if(isset($importErrors) && !empty($importErrors) || $hasErrorItems)
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0"><i class="ti ti-alert-triangle"></i> {{ __('Import Errors') }} 
                                    @if(isset($importErrorCount) && $importErrorCount > 0)
                                        <span class="badge bg-danger">{{ $importErrorCount }}</span>
                                    @endif
                                </h6>
                                @if($hasErrorItems)
                                    <a href="{{ route('warehouse.stock-count.export-errors', $warehouse->id) }}" 
                                       class="btn btn-sm btn-danger">
                                        <i class="ti ti-download"></i> {{ __('Download Errors as Excel') }}
                                    </a>
                                @endif
                            </div>
                            @if($hasErrorItems && isset($importErrorItems) && !empty($importErrorItems))
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Row') }}</th>
                                                <th>{{ __('Product No') }}</th>
                                                <th>{{ __('Quantity') }}</th>
                                                <th>{{ __('Error') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($importErrorItems as $errorItem)
                                                <tr>
                                                    <td>{{ $errorItem['row'] ?? '' }}</td>
                                                    <td><strong>{{ $errorItem['product_no'] ?? '' }}</strong></td>
                                                    <td>{{ $errorItem['quantity'] ?? '' }}</td>
                                                    <td>
                                                        <span class="badge bg-danger">
                                                            {{ isset($errorItem['error_type']) && $errorItem['error_type'] == 'not_found' ? __('Barcode not found') : __('Invalid Quantity') }}
                                                        </span>
                                                        @if(!empty($errorItem['error_message']))
                                                            <div class="small text-muted mt-1">{{ $errorItem['error_message'] }}</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            @if(isset($importErrors) && !empty($importErrors))
                                <div class="max-height-300 overflow-auto mt-2">
                                    <ul class="mb-0">
                                        @foreach($importErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Filters (server-side, applies to all pages) --}}
                    <form method="GET" action="{{ route('warehouse.stock-count', $warehouse->id) }}" class="mb-3">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 100) }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-12">
                                <label class="form-label mb-1">{{ __('Product No (paste from Excel)') }}</label>
                                <textarea name="product_nos" class="form-control font-monospace" rows="2" placeholder="{{ __('Paste product numbers here: one per line, or comma/tab separated') }}">{{ request('product_nos') }}</textarea>
                                <small class="text-muted">{{ __('Paste a column from Excel to filter by product no.') }}</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">{{ __('SKU') }}</label>
                                <input type="text" name="sku" class="form-control" value="{{ request('sku') }}" placeholder="{{ __('Search SKU...') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">{{ __('Sub Product No') }}</label>
                                <input type="text" name="sub_product_no" class="form-control" value="{{ request('sub_product_no') }}" placeholder="{{ __('Search Sub Product No...') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">{{ __('Brand') }}</label>
                                <select name="brand_id" class="form-control">
                                    <option value="">{{ __('All Brands') }}</option>
                                    @foreach(($brands ?? []) as $brand)
                                        <option value="{{ $brand->id }}" {{ (string)request('brand_id') === (string)$brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">{{ __('Sub Brand') }}</label>
                                <select name="sub_brand_id" class="form-control">
                                    <option value="">{{ __('All Sub Brands') }}</option>
                                    @foreach(($subBrands ?? []) as $subBrand)
                                        <option value="{{ $subBrand->id }}" {{ (string)request('sub_brand_id') === (string)$subBrand->id ? 'selected' : '' }}>
                                            {{ $subBrand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2 mt-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-filter"></i> {{ __('Apply Filters') }}
                                </button>
                                <a href="{{ route('warehouse.stock-count', $warehouse->id) }}?per_page={{ request('per_page', 100) }}" class="btn btn-light btn-sm">
                                    <i class="ti ti-refresh"></i> {{ __('Clear') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Info --}}
                    <div class="row mb-3">
                        <div class="col-md-12 text-end">
                            @php
                                if(isset($hasImportedData) && $hasImportedData) {
                                    // When showing imported items, show count from imported data
                                    $totalItems = isset($importSuccessCount) ? $importSuccessCount : count($importedDataForView ?? []);
                                    $showingFrom = 1;
                                    $showingTo = $totalItems;
                                } else {
                                    $totalItems = $productData instanceof \Illuminate\Pagination\LengthAwarePaginator ? $productData->total() : count($productData);
                                    $currentPage = $productData instanceof \Illuminate\Pagination\LengthAwarePaginator ? $productData->currentPage() : 1;
                                    $perPage = $productData instanceof \Illuminate\Pagination\LengthAwarePaginator ? $productData->perPage() : count($productData);
                                    $showingFrom = ($currentPage - 1) * $perPage + 1;
                                    $showingTo = min($currentPage * $perPage, $totalItems);
                                }
                            @endphp
                            <span class="text-muted">
                                @if(isset($hasImportedData) && $hasImportedData)
                                    {{ __('Showing :count imported item(s)', ['count' => $totalItems]) }}
                                @else
                                    {{ __('Showing :from-:to of :total items', [
                                        'from' => $showingFrom,
                                        'to' => $showingTo,
                                        'total' => $totalItems
                                    ]) }}
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Stock Count Form --}}
                    <form method="POST" action="{{ route('warehouse.stock-count.store', $warehouse->id) }}" id="stockCountForm">
                        @csrf
                        @if(request()->get('debug_stock_count') == '1')
                            <input type="hidden" name="debug_stock_count" value="1">
                        @endif
                        @if(request()->get('auto_save') == '1')
                            <input type="hidden" name="auto_save" value="1">
                        @endif
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="stockCountTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product No') }}</th>
                                        <th>{{ __('Product Name') }}</th>
                                        <th>{{ __('Current Qty') }}</th>
                                        <th>{{ __('Avg Purchase Price') }}</th>
                                        <th>{{ __('New Qty') }}</th>
                                        <th>{{ __('Difference') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Products are already filtered in controller if import data exists
                                        $filteredProducts = $productData instanceof \Illuminate\Pagination\LengthAwarePaginator 
                                            ? $productData->items() 
                                            : $productData;
                                    @endphp
                                    @forelse ($filteredProducts as $product)
                                        @php
                                            // Check if this product was imported
                                            $importedQty = null;
                                            $hasDiscrepancy = false;
                                            if(isset($importedDataForView) && isset($importedDataForView[$product['product_no']])) {
                                                $importedQty = $importedDataForView[$product['product_no']]['quantity'];
                                                // Compare against total current quantity for this Product No
                                                $hasDiscrepancy = ($importedQty != $product['current_qty']);
                                            }
                                            // Display quantity is either imported total or summed current total
                                            $displayQty = $importedQty !== null ? $importedQty : $product['current_qty'];
                                            $difference = $displayQty - $product['current_qty'];
                                        @endphp
                                        <tr class="{{ $hasDiscrepancy ? 'table-warning' : '' }}">
                                            <td>{{ $product['product_no'] }}</td>
                                            <td style="white-space: normal; word-wrap: break-word; max-width: 400px;">
                                                <div style="white-space: normal; word-wrap: break-word; word-break: break-word;">
                                                    {{-- Display full hierarchy: Category → Brand → Sub Brand → Product Name --}}
                                                    <div class="product-hierarchy mb-1">
                                                        @if(!empty($product['category_name']))
                                                            <span class="fw-bold text-primary">{{ $product['category_name'] }}</span>
                                                            @if(!empty($product['brand_name']) || !empty($product['sub_brand_name']) || !empty($product['product_name_raw']))
                                                                <span class="mx-1">→</span>
                                                            @endif
                                                        @endif
                                                        
                                                        @if(!empty($product['brand_name']))
                                                            <span class="fw-semibold text-info">{{ $product['brand_name'] }}</span>
                                                            @if(!empty($product['sub_brand_name']) || !empty($product['product_name_raw']))
                                                                <span class="mx-1">→</span>
                                                            @endif
                                                        @endif
                                                        
                                                        @if(!empty($product['sub_brand_name']))
                                                            <span class="fw-semibold text-success">{{ $product['sub_brand_name'] }}</span>
                                                            @if(!empty($product['product_name_raw']))
                                                                <span class="mx-1">→</span>
                                                            @endif
                                                        @endif
                                                        
                                                        @if(!empty($product['product_name_raw']))
                                                            <span class="fw-bold">{{ $product['product_name_raw'] }}</span>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- Display product number below the name --}}
                                                    @if(!empty($product['product_no']))
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <i class="ti ti-hash"></i> {{ __('Product No') }}: <strong>{{ $product['product_no'] }}</strong>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ $product['current_qty'] }}</strong>
                                            </td>
                                            <td>{{ \Auth::user()->priceFormat($product['purchase_price']) }}</td>
                                            <td>
                                                {{-- One new total per barcode; backend groups by barcode and applies profit (newest) / loss (oldest first) --}}
                                                <input type="number"
                                                       name="group_quantities[{{ $product['product_no'] }}]"
                                                       value="{{ $displayQty }}"
                                                       min="0"
                                                       class="form-control group-new-qty-input {{ $hasDiscrepancy ? 'border-warning' : '' }}"
                                                       data-current-total="{{ $product['current_qty'] }}"
                                                       data-product-no="{{ $product['product_no'] }}"
                                                       @if($importedQty !== null)
                                                           data-imported="{{ $importedQty }}"
                                                           title="{{ __('Imported quantity from Excel') }}"
                                                       @endif
                                                       required>
                                                @if($importedQty !== null && $hasDiscrepancy)
                                                    <small class="text-warning d-block mt-1">
                                                        <i class="ti ti-alert-triangle"></i> {{ __('Imported: :qty', ['qty' => $importedQty]) }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="difference-badge" 
                                                      data-product-no="{{ $product['product_no'] }}"
                                                      data-difference="{{ $difference }}">
                                                    {{ $difference }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                @if(isset($hasImportedData) && $hasImportedData)
                                                    {{ __('No imported products found.') }}
                                                @else
                                                    {{ __('No products found in this warehouse.') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @php
                            // Check if we have products to show
                            $hasItems = $productData instanceof \Illuminate\Pagination\LengthAwarePaginator 
                                ? $productData->total() > 0 
                                : count($productData) > 0;
                        @endphp
                        @if ($hasItems)
                            <div class="form-group mt-3">
                                <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" onclick="location.href='{{ route('warehouse.index') }}'">
                                <input type="submit" value="{{ __('Save Stock Count') }}" class="btn btn-primary">
                            </div>
                        @endif
                    </form>

                    {{-- Pagination - Only show if not filtering by imported items --}}
                    @if(!isset($hasImportedData) || !$hasImportedData)
                        @if($productData instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label">{{ __('Items per page:') }}</label>
                                    <select id="perPageSelect" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                        <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                                        <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200</option>
                                        <option value="500" {{ request('per_page', 100) == 500 ? 'selected' : '' }}>500</option>
                                    </select>
                                </div>
                                <div>
                                    {{ $productData->appends(request()->query())->links() }}
                                </div>
                            </div>
                        @endif
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $(document).ready(function() {
        // Debug: Log when import button is clicked
        $(document).on('click', 'a[data-ajax-popup="true"][data-url*="import-single"]', function(e) {
            console.log('Import button clicked:', {
                url: $(this).data('url'),
                title: $(this).data('title'),
                size: $(this).data('size')
            });
        });
        
        // Update difference badge when grouped quantity changes (backend does distribution by barcode)
        $(document).on('input', '.group-new-qty-input', function() {
            const currentTotal = parseInt($(this).data('current-total')) || 0;
            const newTotal = parseInt($(this).val()) || 0;
            const difference = newTotal - currentTotal;

            const row = $(this).closest('tr');
            const badge = row.find('.difference-badge');
            badge.text(difference);

            badge.removeClass('badge-success badge-danger badge-info');
            if (difference > 0) {
                badge.addClass('badge badge-success');
            } else if (difference < 0) {
                badge.addClass('badge badge-danger');
            } else {
                badge.addClass('badge badge-info');
            }
        });

        // Paste from Excel into NEW QTY: paste a column (or row) of numbers to fill inputs in order
        $(document).on('paste', '.group-new-qty-input', function(e) {
            const pasteData = (e.originalEvent && e.originalEvent.clipboardData) ? e.originalEvent.clipboardData.getData('text') : '';
            if (!pasteData || !pasteData.trim()) return;

            e.preventDefault();
            const lines = pasteData.split(/\r?\n/);
            const values = [];
            lines.forEach(function(line) {
                const parts = line.split(/[\t,;]/);
                const first = (parts[0] || '').trim();
                const num = parseInt(first, 10);
                if (!isNaN(num) && num >= 0) values.push(num);
            });
            if (values.length === 0) return;

            const inputs = $('#stockCountForm').find('.group-new-qty-input');
            values.forEach(function(val, i) {
                if (inputs[i]) {
                    $(inputs[i]).val(val).trigger('input');
                }
            });
        });

        // Initialize differences and hidden quantities on page load
        $('.group-new-qty-input').each(function() {
            $(this).trigger('input');
        });

        // Simple search functionality (client-side filtering)
        // Per page selector
        $('#perPageSelect').on('change', function() {
            const perPage = $(this).val();
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', '1'); // Reset to first page
            window.location.href = url.toString();
        });

        // Auto-save after Excel import (save imported quantities directly)
        @if(isset($hasImportedData) && $hasImportedData && request()->get('auto_save') == '1')
            setTimeout(function() {
                const form = $('#stockCountForm');
                if (form.length && form.get(0)) {
                    // Native submit ensures the POST is actually sent
                    form.get(0).submit();
                }
            }, 500);
        @endif

        // Background import status polling
        const jobAlert = $('#importJobAlert');
        if (jobAlert.length) {
            const token = jobAlert.data('token');
            const statusUrl = "{{ route('warehouse.stock-count.import-status', ['token' => '__TOKEN__']) }}".replace('__TOKEN__', encodeURIComponent(token));
            const progressBar = $('#importJobProgress');
            const msg = $('#importJobMessage');

            const poll = function() {
                $.get(statusUrl)
                    .done(function(res) {
                        const pct = res.progress || 0;
                        progressBar.css('width', pct + '%');
                        msg.text(res.message || res.status || '');
                        if (res.status === 'done') {
                            // Remove token from URL and reload to show updated quantities
                            const url = new URL(window.location.href);
                            url.searchParams.delete('import_job');
                            window.location.href = url.toString();
                            return;
                        }
                        if (res.status === 'error') {
                            jobAlert.removeClass('alert-info').addClass('alert-danger');
                            msg.text(res.message || 'Error');
                            return;
                        }
                        setTimeout(poll, 2000);
                    })
                    .fail(function() {
                        setTimeout(poll, 4000);
                    });
            };
            setTimeout(poll, 1000);
        }
    });
</script>
<style>
    .difference-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
    }
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    .badge-info {
        background-color: #17a2b8;
        color: white;
    }
    .max-height-300 {
        max-height: 300px;
        overflow-y: auto;
    }
</style>
@endpush
