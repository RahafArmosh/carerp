<form action="{{ route('meeting.update', $meeting->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['meeting']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Meeting Title') }}</label>
                    <input type="text" id="title" name="title" class="form-control"
                        placeholder="{{ __('Enter Meeting Title') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date" class="form-label">{{ __('Meeting Date') }}</label>
                    <input type="date" id="date" name="date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time" class="form-label">{{ __('Meeting Time') }}</label>
                    <input type="time" id="time" name="time" class="form-control timepicker">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="note" class="form-label">{{ __('Meeting Note') }}</label>
                    <textarea id="note" name="note" class="form-control" placeholder="{{ __('Enter Meeting Note') }}"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
