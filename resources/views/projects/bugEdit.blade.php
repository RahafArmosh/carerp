<form method="POST" action="{{ route('task.bug.update', [$project_id, $bug->id]) }}">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['project bug']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-6">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="priority" class="form-label">{{ __('Priority') }}</label>
                <select name="priority" class="form-control select" required>
                    @foreach ($priority as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="form-label">{{ __('Bug Status') }}</label>
                <select name="status" class="form-control select" required>
                    @foreach ($status as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="assign_to" class="form-label">{{ __('Assigned To') }}</label>
                <select name="assign_to" class="form-control select" required>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
