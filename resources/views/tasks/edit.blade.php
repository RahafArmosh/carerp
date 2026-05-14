<form action="{{ route('projects.tasks.update', [$project->id, $task->id]) }}" method="POST" id="edit_task">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-8">
            <div class="form-group">
                <label for="name" class="form-label">{{ __('Task name') }}</label>
                <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}"
                    required>
            </div>
        </div>
        <div class="col-4">
            <div class="form-group">
                <label for="milestone_id" class="form-label">{{ __('Milestone') }}</label>
                <select class="form-control" name="milestone_id" id="milestone_id">
                    <option value="0"></option>
                    @foreach ($project->milestones as $m_val)
                        <option value="{{ $m_val->id }}" {{ $task->milestone_id == $m_val->id ? 'selected' : '' }}>
                            {{ $m_val->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label for="description" class="form-label">{{ __('Description') }}</label>

                <small
                    class="form-text text-muted mb-2 mt-0">{{ __('This textarea will autosize while you type') }}</small>
                <textarea name="description" id="description" class="form-control" rows="1" data-toggle="autosize"></textarea>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="estimated_hrs" class="form-label">{{ __('Estimated Hours') }}</label>
                <small
                    class="form-text text-muted mb-2 mt-0">{{ __('Total hrs of project ') . $hrs['total'] . __(' & allocated total ') . $hrs['allocated'] . __(' hrs in other tasks') }}</small>
                <input type="number" name="estimated_hrs" id="estimated_hrs" class="form-control" required="required"
                    min="0" maxlength="8">
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="priority" class="form-label">{{ __('Priority') }}</label>
                <small class="form-text text-muted mb-2 mt-0">{{ __('Set Priority of your task') }}</small>
                <select class="form-control" name="priority" id="priority" required>
                    @foreach (\App\Models\ProjectTask::$priority as $key => $val)
                        <option value="{{ $key }}" {{ $key == $task->priority ? 'selected' : '' }}>
                            {{ __($val) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                <input type="date" id="start_date" name="start_date" class="form-control">
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                <input type="date" id="end_date" name="end_date" class="form-control">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">{{ __('Task members') }}</label>
        <small class="form-text text-muted mb-2 mt-0">{{ __('Below users are assigned in your project.') }}</small>
    </div>
    <div class="list-group list-group-flush mb-4">
        <div class="row">
            @foreach ($project->users as $user)
                <div class="col-6">
                    <div class="list-group-item px-0">
                        <div class="row align-items-center">
                            <div class="col-auto ml-3">
                                <a href="#" class="avatar avatar-sm rounded-circle">
                                    <img {{ $user->img_avatar }} />
                                </a>
                            </div>
                            <div class="col ml-n2">
                                <p class="d-block h6 text-sm mb-0">{{ $user->name }}</p>
                                <p class="card-text text-sm text-muted mb-0">{{ $user->email }}</p>
                            </div>
                            @php
                                $usrs = explode(',', $task->assign_to);
                            @endphp
                            <div class="col-auto text-end add_usr {{ in_array($user->id, $usrs) ? 'selected' : '' }}"
                                data-id="{{ $user->id }}">
                                <button type="button"
                                    class="btn btn-xs btn-animated btn-primary rounded-pill btn-animated-y mr-3">
                                    <span class="btn-inner--visible">
                                        <i class="ti ti-{{ in_array($user->id, $usrs) ? 'check' : 'plus' }} "
                                            id="usr_icon_{{ $user->id }}"></i>
                                    </span>
                                    <span class="btn-inner--hidden"
                                        id="usr_txt_{{ $user->id }}">{{ in_array($user->id, $usrs) ? __('Added') : __('Add') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <input type="hidden" name="assign_to" value="">
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-sm btn-primary rounded-pill">{{ __('Update') }}</button>
    </div>
</form>
