@extends('layouts.admin')
@section('page-title')
    {{ __('POS Logs') }}
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pos.report') }}">{{ __('POS Summary') }}</a></li>
    <li class="breadcrumb-item">{{ __('POS Logs') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('POS Activity Logs') }}</h5>
                    <small class="text-muted">{{ __('Track all POS-related activities') }}</small>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('pos.logs') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Action Type') }}</label>
                                <select name="action_type" class="form-select">
                                    <option value="">{{ __('All Actions') }}</option>
                                    @foreach($actionTypes as $type)
                                        <option value="{{ $type }}" {{ request('action_type') == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Type') }}</label>
                                <select name="type" class="form-select">
                                    <option value="">{{ __('All Types') }}</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Reference ID') }}</label>
                                <input type="number" name="reference_id" class="form-control" value="{{ request('reference_id') }}" placeholder="{{ __('ID') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Warehouse') }}</label>
                                <select name="warehouse_id" class="form-select">
                                    <option value="">{{ __('All Warehouses') }}</option>
                                    @foreach($warehouses as $id => $name)
                                        <option value="{{ $id }}" {{ request('warehouse_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('User') }}</label>
                                <select name="user_id" class="form-select">
                                    <option value="">{{ __('All Users') }}</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Date From') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Date To') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ti ti-filter"></i> {{ __('Filter') }}
                                    </button>
                                    <a href="{{ route('pos.logs') }}" class="btn btn-secondary btn-sm">
                                        <i class="ti ti-x"></i> {{ __('Clear') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                                <thead>
                                <tr>
                                    <th>{{ __('Date & Time') }}</th>
                                    <th>{{ __('Action') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Reference ID') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                {{ Auth::user()->dateFormat($log->created_at) }}<br>
                                                {{ Auth::user()->timeFormat($log->created_at) }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $log->getActionBadgeColor() }}">
                                                {{ ucfirst(str_replace('_', ' ', $log->action_type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($log->type)
                                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $log->type)) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->reference_id)
                                                <a href="#" class="text-primary" onclick="filterByReference('{{ $log->type }}', {{ $log->reference_id }}); return false;" title="{{ __('Filter by this ID') }}">
                                                    #{{ $log->reference_id }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->user ? $log->user->name : __('N/A') }}</td>
                                        <td>
                                            @if($log->warehouse)
                                                <span class="badge bg-info">{{ $log->warehouse->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->customer)
                                                {{ $log->customer->name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->product)
                                                <small>{{ $log->product->name }}</small>
                                                @if($log->product_no)
                                                    <br><span class="badge bg-secondary">#{{ $log->product_no }}</span>
                                                @endif
                                            @elseif($log->product_no)
                                                <span class="badge bg-secondary">#{{ $log->product_no }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->description)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($log->description, 50) }}</small>
                                            @endif
                                            @if($log->quantity)
                                                <br><small><strong>Qty:</strong> {{ $log->quantity }}</small>
                                            @endif
                                            @if($log->pos_id)
                                                <br><small><strong>POS ID:</strong> {{ $log->pos_id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $log->ip_address ?? '-' }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ti ti-inbox" style="font-size: 3rem;"></i>
                                                <p class="mt-2">{{ __('No logs found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        function filterByReference(type, referenceId) {
            const form = document.querySelector('form[action="{{ route('pos.logs') }}"]');
            if (form) {
                form.querySelector('select[name="type"]').value = type;
                form.querySelector('input[name="reference_id"]').value = referenceId;
                form.submit();
            }
        }

        $(document).ready(function() {
            $('.datatable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 50,
                "language": {
                    "search": "{{ __('Search') }}:",
                    "lengthMenu": "{{ __('Show') }} _MENU_ {{ __('entries') }}",
                    "info": "{{ __('Showing') }} _START_ {{ __('to') }} _END_ {{ __('of') }} _TOTAL_ {{ __('entries') }}",
                    "infoEmpty": "{{ __('Showing') }} 0 {{ __('to') }} 0 {{ __('of') }} 0 {{ __('entries') }}",
                    "infoFiltered": "({{ __('filtered from') }} _MAX_ {{ __('total entries') }})",
                    "paginate": {
                        "previous": "{{ __('Previous') }}",
                        "next": "{{ __('Next') }}"
                    }
                }
            });
        });
    </script>
@endpush

