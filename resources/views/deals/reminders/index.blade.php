@extends('layouts.admin')

@section('page-title')
    {{ __('Deal Reminders') }}
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Reminders') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Deal') }}</th>
                                <th>{{ __('Message') }}</th>
                                <th>{{ __('Assigned To') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created At') }}</th>
                                <th class="text-end">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reminders as $reminder)
                                <tr>
                                    <td>
                                        <a href="{{ route('deals.show', $reminder->deal_id) }}">#{{ $reminder->deal_id }}</a>
                                    </td>
                                    <td>{{ $reminder->message }}</td>
                                    <td>{{ optional($reminder->user)->name }}</td>
                                    <td>
                                        @if($reminder->is_done)
                                            <span class="badge bg-success">{{ __('Done') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $reminder->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        @if(!$reminder->is_done)
                                            <a href="{{ route('deal-reminders.done', $reminder->id) }}" class="btn btn-sm btn-success">{{ __('Mark Done') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('No reminders found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $reminders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection


