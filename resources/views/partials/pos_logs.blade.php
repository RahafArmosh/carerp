@if(isset($logs) && $logs->count() > 0)
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="ti ti-history"></i> {{ __('Activity Logs') }}
                <span class="badge bg-primary">{{ $logs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $logs->total() : $logs->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Date & Time') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Details') }}</th>
                            <th>{{ __('IP Address') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
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
                                    {{ $log->user ? $log->user->name : __('N/A') }}
                                </td>
                                <td>
                                    @if($log->description)
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($log->description, 80) }}</small>
                                    @endif
                                    @if($log->quantity)
                                        <br><small><strong>Qty:</strong> {{ $log->quantity }}</small>
                                    @endif
                                    @if($log->old_value || $log->new_value)
                                        <br>
                                        <button class="btn btn-xs btn-info mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#logDetails{{ $log->id }}" aria-expanded="false">
                                            <i class="ti ti-eye"></i> {{ __('View Changes') }}
                                        </button>
                                        <div class="collapse mt-2" id="logDetails{{ $log->id }}">
                                            <div class="card card-body p-2">
                                                {{-- @if($log->old_value)
                                                    <small><strong>{{ __('Old Values') }}:</strong></small>
                                                    <pre class="mb-1" style="font-size: 0.75rem;">{{ json_encode($log->old_value, JSON_PRETTY_PRINT) }}</pre>
                                                @endif --}}
                                                @if($log->new_value)
                                                    <small><strong>{{ __('New Values') }}:</strong></small>
                                                    <pre style="font-size: 0.75rem;">{{ json_encode($log->new_value, JSON_PRETTY_PRINT) }}</pre>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $log->ip_address ?? '-' }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@elseif(isset($logs) && $logs->count() == 0)
    <div class="card mt-4">
        <div class="card-body text-center text-muted">
            <i class="ti ti-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2 mb-0">{{ __('No activity logs found') }}</p>
        </div>
    </div>
@endif

