<form method="POST" action="{{ route('projects.tasks.update', [$project->id, $task->id]) }}" id="edit_task">
    @csrf
    @method('POST')
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['project task']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Task name') }}</label><span
                        class="text-danger">*</span>
                    <input type="text" name="name" class="form-control" value="{{ $task->name }}" required>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="milestone_id" class="form-label">{{ __('Milestone') }}</label>
                    <select class="form-control select" name="milestone_id" id="milestone_id">
                        <option value="0" class="text-muted">{{ __('Select Milestone') }}</option>
                        @foreach ($project->milestones as $m_val)
                            <option value="{{ $m_val->id }}"
                                {{ $task->milestone_id == $m_val->id ? 'selected' : '' }}>{{ $m_val->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <!-- Other fields and inputs omitted for brevity -->
        </div>
        <!-- Task members section omitted for brevity -->
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
