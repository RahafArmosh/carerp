<form action="{{ route('projects.update', $project->id) }}" method="POST" enctype="multipart/form-data">
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
                    data-url="{{ route('generate', ['projects']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="project_name" class="form-label">{{ __('Project Name') }}</label><span
                        class="text-danger">*</span>
                    <input type="text" name="project_name" class="form-control"
                        value="{{ old('project_name', $project->project_name) }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control"
                        value="{{ old('start_date', $project->start_date) }}">
                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control"
                        value="{{ old('end_date', $project->end_date) }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="client" class="form-label">{{ __('Client') }}</label><span
                        class="text-danger">*</span>
                    <select name="client" class="form-control select2" id="choices-multiple1" required>
                        @foreach ($clients as $id => $name)
                            <option value="{{ $id }}" {{ $project->client_id == $id ? 'selected' : '' }}>
                                {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="budget" class="form-label">{{ __('Budget') }}</label>
                    <input type="number" name="budget" class="form-control"
                        value="{{ old('budget', $project->budget) }}">
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="form-group">
                    <label for="estimated_hrs" class="form-label">{{ __('Estimated Hours') }}</label>
                    <input type="number" name="estimated_hrs" class="form-control" min="0" maxlength="8"
                        value="{{ old('estimated_hrs', $project->estimated_hrs) }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $project->description) }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="tag" class="form-label">{{ __('Tag') }}</label>
                    <input type="text" name="tag" class="form-control" data-toggle="tags"
                        value="{{ old('tag', $project->tags) }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="status" class="form-label">{{ __('Status') }}</label>
                    <select name="status" id="status" class="form-control main-element select2">
                        @foreach (\App\Models\Project::$project_status as $key => $value)
                            <option value="{{ $key }}" {{ $project->status == $key ? 'selected' : '' }}>
                                {{ __($value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <label for="project_image" class="form-label">{{ __('Project Image') }}</label><span
                    class="text-danger">*</span>
                <div class="form-file mb-3">
                    <input type="file" class="form-control" name="project_image">
                </div>
                <img src="{{ $project->img_image }}" class="avatar avatar-xl" alt="">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
