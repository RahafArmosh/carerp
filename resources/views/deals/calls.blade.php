@push('css-page')
    <link rel="stylesheet" href="{{ asset('assets/libs/summernote/summernote-bs4.css') }}">
@endpush
@push('script-page')
    <script src="{{ asset('assets/libs/summernote/summernote-bs4.js') }}"></script>
@endpush
@if (isset($call))
    <form action="{{ route('deals.calls.update', [$deal->id, $call->id]) }}" method="post">
        @csrf
        @method('PUT')
    @else
        <form action="{{ route('deals.calls.store', $deal->id) }}" method="post">
            @csrf
@endif
<div class="modal-body">
    {{-- start for ai module --}}
    @php
        $plan = \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['deal']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module --}}
    <div class="row">
        <div class="col-6 form-group">
            <label for="subject" class="form-label">{{ __('Subject') }}</label>
            <input type="text" id="subject" name="subject" class="form-control" required>
        </div>
        <div class="col-6 form-group">
            <label for="call_type" class="form-label">{{ __('Call Type') }}</label>
            <select name="call_type" id="choices-multiple1" class="form-control select2" required>
                <option value="outbound" @if (isset($call->call_type) && $call->call_type == 'outbound') selected @endif>{{ __('Outbound') }}
                </option>
                <option value="inbound" @if (isset($call->call_type) && $call->call_type == 'inbound') selected @endif>{{ __('Inbound') }}</option>
            </select>
        </div>
        <div class="col-6 form-group">
            <label for="duration" class="form-label">{{ __('Duration') }}</label>
            <small
                class="font-weight-bold">{{ __(' (Format h:m:s i.e 00:35:20 means 35 Minutes and 20 Sec)') }}</small>
            <input type="time" id="duration" name="duration" class="form-control" placeholder="00:35:20"
                step="2">
        </div>
        <div class="col-6 form-group">
            <label for="user_id" class="form-label">{{ __('Assignee') }}</label>
            <select name="user_id" id="choices-multiple2" class="form-control select2" required>
                @foreach ($users as $user)
                    <option value="{{ $user->getDealUser->id }}" @if (isset($call->user_id) && $call->user_id == $user->getDealUser->id) selected @endif>
                        {{ $user->getDealUser->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 form-group">
            <label for="description" class="form-label">{{ __('Description') }}</label>
            <textarea id="description" name="description" class="form-control"></textarea>
        </div>
        <div class="col-12 form-group">
            <label for="call_result" class="form-label">{{ __('Call Result') }}</label>
            <textarea id="call_result" name="call_result" class="summernote-simple"></textarea>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    @if (isset($call))
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    @else
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    @endif
</div>

</form>
