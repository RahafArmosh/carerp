<form method="POST" action="{{ route('project.milestone.store', $project->id) }}">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['project milestone']) }}" data-bs-placement="top"
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
                @error('title')
                    <span class="invalid-title" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-control select" required>
                    @foreach (\App\Models\Project::$project_status as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
                @error('status')
                    <span class="invalid-status" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
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
                <label for="cost" class="form-label">{{ __('Cost') }}</label>
                <input type="number" name="cost" class="form-control" required step="0.01">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
                @error('description')
                    <span class="invalid-description" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
