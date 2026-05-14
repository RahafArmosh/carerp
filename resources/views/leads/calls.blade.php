@if (isset($call))
    <form method="POST" action="{{ route('leads.calls.update', ['lead' => $lead->id, 'call' => $call->id]) }}">
        @method('PUT')
    @else
        <form method="POST" action="{{ route('leads.calls.store', $lead->id) }}">
@endif
@csrf

<div class="modal-body">
    {{-- start for ai module --}}
    @php
        $plan = \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['lead']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module --}}
    <div class="row">
        <div class="col-6 form-group">
            <label for="subject" class="form-label">{{ __('Subject') }}</label>
            <input id="subject" type="text" name="subject" class="form-control" required>
        </div>
        <div class="col-6 form-group">
            <label for="call_type" class="form-label">{{ __('Call Type') }}</label>
            <select id="call_type" name="call_type" class="form-control" required>
                <option value="outbound" @if (isset($call->call_type) && $call->call_type == 'outbound') selected @endif>{{ __('Outbound') }}</option>
                <option value="inbound" @if (isset($call->call_type) && $call->call_type == 'inbound') selected @endif>{{ __('Inbound') }}</option>
            </select>
        </div>
        <div class="col-12 form-group">
            <label for="duration" class="form-label">{{ __('Duration') }} <small
                    class="font-weight-bold">{{ __(' (Format h:m:s i.e 00:35:20 means 35 Minutes and 20 Sec)') }}</small></label>
            <input id="duration" type="time" name="duration" class="form-control" placeholder="00:35:20"
                step="2">
        </div>
        <div class="col-12 form-group">
            <label for="user_id" class="form-label">{{ __('Assignee') }}</label>
            <select id="user_id" name="user_id" class="form-control" required>
                @foreach ($users as $user)
                    <option value="{{ $user->getLeadUser->id }}" @if (isset($call->user_id) && $call->user_id == $user->getLeadUser->id) selected @endif>
                        {{ $user->getLeadUser->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 form-group">
            <label for="description" class="form-label">{{ __('Description') }}</label>
            <textarea id="description" name="description" class="form-control"></textarea>
        </div>
        <div class="col-12 form-group">
            <label for="call_result" class="form-label">{{ __('Call Result') }}</label>
            <textarea id="call_result" name="call_result" class="summernote-simple" id="summernote"></textarea>
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    @if (isset($call))
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    @else
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    @endif
</div>
</form>
