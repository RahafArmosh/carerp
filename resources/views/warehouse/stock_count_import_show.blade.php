@extends('layouts.admin')
@section('page-title')
    {{ __('Stock count import') }} #{{ $import->id }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse.index') }}">{{ __('Warehouse') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse.stock-count-imports.index') }}">{{ __('Import history') }}</a></li>
    <li class="breadcrumb-item">#{{ $import->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('File') }}:</strong>
                            <span class="text-break">{{ $import->source_filename }}</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('Mode') }}:</strong>
                            {{ $import->import_mode === 'multi' ? __('Multi') : __('Single') }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('Status') }}:</strong>
                            @php
                                $labels = [
                                    'queued' => __('Queued'),
                                    'applied' => __('Applied'),
                                    'apply_failed' => __('Apply failed'),
                                    'recorded' => __('Recorded'),
                                ];
                            @endphp
                            {{ $labels[$import->status] ?? $import->status }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('Date') }}:</strong>
                            {{ Auth::user()->dateFormat($import->created_at) }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('User') }}:</strong>
                            {{ $import->user ? $import->user->name : '—' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('Warehouse (header)') }}:</strong>
                            @if ($import->warehouse_id && $import->warehouse)
                                {{ $import->warehouse->name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>{{ __('Lines') }}:</strong> {{ number_format($import->line_count) }}
                            &nbsp;|&nbsp;
                            <strong>{{ __('Errors (import)') }}:</strong> {{ number_format($import->error_count) }}
                        </div>
                        @if (!empty($import->meta))
                            <div class="col-12 mb-0">
                                <strong>{{ __('Meta') }}:</strong>
                                <pre class="small bg-light p-2 rounded mb-0 mt-1">{{ json_encode($import->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('warehouse.stock-count-imports.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-arrow-left"></i> {{ __('Back to list') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">{{ __('Import lines') }}</h5>
                    <a href="{{ route('warehouse.stock-count-imports.export-lines', $import) }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-file-export"></i> {{ __('Export Excel') }}
                    </a>
                </div>
                <div class="card-body table-border-style">
                    <form method="get" action="{{ route('warehouse.stock-count-imports.show', $import) }}"
                        class="row g-3 align-items-end mb-3">
                        <div class="col-auto">
                            <label for="per_page" class="form-label">{{ __('Per page') }}</label>
                            <select name="per_page" id="per_page" class="form-control form-control-sm"
                                onchange="this.form.submit()">
                                @foreach ([50, 100, 200, 500] as $n)
                                    <option value="{{ $n }}"
                                        {{ (int) request('per_page', 50) === $n ? 'selected' : '' }}>{{ $n }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Row') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Product No') }}</th>
                                    <th class="text-end">{{ __('System qty') }}</th>
                                    <th class="text-end">{{ __('Counted') }}</th>
                                    <th class="text-end">{{ __('Diff') }}</th>
                                    <th>{{ __('Sub product ID') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lines as $line)
                                    @php
                                        $sys = $line->system_qty_before;
                                        $cnt = $line->counted_qty;
                                        $diff = $sys !== null ? $cnt - $sys : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $line->excel_row ?? '—' }}</td>
                                        <td>{{ $line->warehouse ? $line->warehouse->name : '—' }}</td>
                                        <td><code class="small">{{ $line->product_no }}</code></td>
                                        <td class="text-end">{{ $sys !== null ? number_format($sys) : '—' }}</td>
                                        <td class="text-end">{{ number_format($line->counted_qty) }}</td>
                                        <td class="text-end">
                                            @if ($diff !== null)
                                                <span class="{{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') }}">
                                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $line->sub_product_id ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ __('No lines stored for this import.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($lines->hasPages())
                        <div class="mt-3">
                            {{ $lines->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
