<form method="POST" action="{{ route('projects.tasks.store', [$project_id, $stage_id]) }}" id="create_task">
    @csrf
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
                    <label for="name" class="form-label">{{ __('Task name') }}<span
                            class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="milestone_id" class="form-label">{{ __('Milestone') }}</label>
                    <select class="form-control select" name="milestone_id" id="milestone_id">
                        <option value="0" class="text-muted">{{ __('Select Milestone') }}</option>
                        @foreach ($project->milestones as $m_val)
                            <option value="{{ $m_val->id }}">{{ $m_val->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <small
                        class="form-text text-muted mb-2 mt-0">{{ __('This textarea will autosize while you type') }}</small>
                    <textarea name="description" class="form-control" rows="1" data-toggle="autosize"></textarea>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="estimated_hrs" class="form-label">{{ __('Estimated Hours') }}<span
                            class="text-danger">*</span></label>
                    <small
                        class="form-text text-muted mb-2 mt-0">{{ __('allocated total ') . $hrs['allocated'] . __(' hrs in other tasks') }}</small>
                    <input type="number" name="estimated_hrs" class="form-control" required min="0"
                        maxlength="8">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="priority" class="form-label">{{ __('Priority') }}</label>
                    <small class="form-text text-muted mb-2 mt-0">{{ __('Set Priority of your task') }}</small>
                    <select class="form-control select" name="priority" id="priority" required>
                        @foreach (\App\Models\ProjectTask::$priority as $key => $val)
                            <option value="{{ $key }}">{{ __($val) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control">
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
                                <div class="col-auto">
                                    <a href="#" class="avatar avatar-sm rounded-circle">
                                        <img class="wid-40 rounded-circle ml-3"
                                            data-original-title="{{ !empty($user) ? $user->name : '' }}"
                                            @if ($user->avatar) src="{{ asset('/storage/uploads/avatar/' . $user->avatar) }}" @else src="{{ asset('/storage/uploads/avatar/avatar.png') }}" @endif />
                                    </a>
                                </div>
                                <div class="col">
                                    <p class="d-block h6 text-sm mb-0">{{ $user->name }}</p>
                                    <p class="card-text text-sm text-muted mb-0">{{ $user->email }}</p>
                                </div>
                                <div class="col-auto text-end add_usr" data-id="{{ $user->id }}">
                                    <button type="button"
                                        class="btn btn-xs btn-animated btn-blue rounded-pill btn-animated-y mr-3">
                                        <span class="btn-inner--visible">
                                            <i class="ti ti-plus" id="usr_icon_{{ $user->id }}"></i>
                                        </span>
                                        <span class="btn-inner--hidden text-white"
                                            id="usr_txt_{{ $user->id }}">{{ __('Add') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="assign_to" value="">
        </div>
        @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
            <div class="form-group col-md-6">
                <label for="synchronize_type" class="form-label">{{ __('Synchronize in Google Calendar ?') }}</label>
                <div class="form-switch">
                    <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                        value="google_calender">
                    <label class="form-check-label" for="switch-shadow"></label>
                </div>
            </div>
        @endif
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
