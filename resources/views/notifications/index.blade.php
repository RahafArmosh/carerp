@extends('layouts.admin')
@section('page-title')
    {{ __('Notifications') }} 
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Notifications') }}</li>
@endsection
@section('content')
    <div class="row">
        <div class="card">
        <div class="card-body p-0">
            @if($notifications->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($notifications as $note)
                        <li class="list-group-item d-flex justify-content-between align-items-start {{ $note->is_read ? '' : 'fw-bold' }}">
                            <div>
                                {{-- message --}}
                                {{ $note->data['message'] ?? 'Notification' }}

                                {{-- optional details --}}
                                @if($note->type === 'lead_converted')
                                    @php
                                        $lead = \App\Models\Lead::find($note->data['lead_id'] ?? null);
                                        $deal = \App\Models\Deal::find($note->data['deal_id'] ?? null);
                                        $client = \App\Models\User::find($note->data['client_id'] ?? null);
                                    @endphp

                                    <br><small class="text-muted">
                                        Lead: {{ $lead->name ?? '-' }},
                                        Deal: {{ $deal->name ?? '-' }},
                                        Client: {{ $client->name ?? '-' }}
                                    </small>
                               
                                @elseif($note->type === 'Deal_uploaded_file')
                                    <br>
                                    <small class="text-muted">
                                        Lead: {{ \App\Models\Lead::find($note->data['lead_id'])->name ?? '-' }},
                                        Deal: {{ \App\Models\Deal::find($note->data['deal_id'])->name ?? '-' }}
                                    </small>
                                @endif

                                {{-- creator info --}}
                                <br>
                                <small class="text-muted">
                                    by {{ $note->creator->name ?? 'System' }},
                                    {{ $note->created_at->diffForHumans() }}
                                </small>
                            </br>
                                <small class="text-muted">
                                    {{ $note->created_at }}
                                </small>
                            </div>
                            {{-- @if(!$note->is_read)
                                <form action="{{ route('notifications.read', $note->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">{{ __('Mark as Read') }}</button>
                                </form>
                            @endif --}}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="p-3 mb-0 text-muted">{{ __('No notifications found.') }}</p>
            @endif
        </div>
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
