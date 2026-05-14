@extends('layouts.admin')
@section('page-title')
    {{ __('Stock count import history') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse.index') }}">{{ __('Warehouse') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import history') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <form method="get" action="{{ route('warehouse.stock-count-imports.index') }}"
                        class="row g-3 align-items-end mb-3">
                        <div class="col-auto">
                            <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-control form-control-sm"
                                onchange="this.form.submit()">
                                <option value="">{{ __('All warehouses') }}</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}"
                                        {{ (string) ($filterWarehouseId ?? '') === (string) $w->id ? 'selected' : '' }}>
                                        {{ $w->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="per_page" class="form-label">{{ __('Per page') }}</label>
                            <select name="per_page" id="per_page" class="form-control form-control-sm"
                                onchange="this.form.submit()">
                                @foreach ([25, 50, 100] as $n)
                                    <option value="{{ $n }}"
                                        {{ (int) request('per_page', 25) === $n ? 'selected' : '' }}>{{ $n }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('File') }}</th>
                                    <th>{{ __('Mode') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Lines') }}</th>
                                    <th class="text-end">{{ __('Errors') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th width="10%">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($imports as $import)
                                    <tr>
                                        <td>{{ $import->id }}</td>
                                        <td>{{ Auth::user()->dateFormat($import->created_at) }}</td>
                                        <td><span class="text-break d-inline-block" style="max-width: 14rem;">{{ $import->source_filename }}</span></td>
                                        <td>{{ $import->import_mode === 'multi' ? __('Multi') : __('Single') }}</td>
                                        <td>
                                            @if ($import->warehouse_id && $import->warehouse)
                                                {{ $import->warehouse->name }}
                                            @else
                                                <span class="text-muted">{{ __('Multiple / see lines') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $labels = [
                                                    'queued' => __('Queued'),
                                                    'applied' => __('Applied'),
                                                    'apply_failed' => __('Apply failed'),
                                                    'recorded' => __('Recorded'),
                                                ];
                                                $label = $labels[$import->status] ?? $import->status;
                                            @endphp
                                            {{ $label }}
                                        </td>
                                        <td class="text-end">{{ number_format($import->line_count) }}</td>
                                        <td class="text-end">{{ number_format($import->error_count) }}</td>
                                        <td>{{ $import->user ? $import->user->name : '—' }}</td>
                                        <td>
                                            <a href="{{ route('warehouse.stock-count-imports.show', $import) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="tooltip" title="{{ __('View lines') }}">
                                                <i class="ti ti-list-details"></i> {{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">{{ __('No import records found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($imports->hasPages())
                        <div class="mt-3">
                            {{ $imports->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
