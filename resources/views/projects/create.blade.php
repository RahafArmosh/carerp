<form action="{{ url('projects') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate', ['project']) }}"
               data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="project_name" class="form-label">{{ __('Project Name') }}</label><span class="text-danger">*</span>
                    <input type="text" name="project_name" class="form-control" required value="{{ old('project_name') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-sm-12 col-md-12">
                <label for="project_image" class="form-label">{{ __('Project Image') }}</label><span class="text-danger">*</span>
                <div class="form-file mb-3">
                    <input type="file" class="form-control" name="project_image" required>
                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="client" class="form-label">{{ __('Client') }}</label><span class="text-danger">*</span>
                    <select name="client" class="form-control" required>
                        @foreach($clients as $id => $name)
                            <option value="{{ $id }}" {{ old('client') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="user" class="form-label">{{ __('User') }}</label><span class="text-danger">*</span>
                    <select name="user[]" class="form-control" required>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}" {{ old('user') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6 col-md-6">
                <div class="form-group">
                    <label for="budget" class="form-label">{{ __('Budget') }}</label>
                    <input type="number" name="budget" class="form-control" value="{{ old('budget') }}">
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="form-group">
                    <label for="estimated_hrs" class="form-label">{{ __('Estimated Hours') }}</label>
                    <input type="number" name="estimated_hrs" class="form-control" min="0" maxlength="8" value="{{ old('estimated_hrs') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="tag" class="form-label">{{ __('Tag') }}</label>
                    <input type="text" name="tag" class="form-control" data-toggle="tags" value="{{ old('tag') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="form-group">
                    <label for="status" class="form-label">{{ __('Status') }}</label>
                    <select name="status" id="status" class="form-control main-element">
                        @foreach(\App\Models\Project::$project_status as $key => $value)
                            <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>{{ __($value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
