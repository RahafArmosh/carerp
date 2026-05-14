<form action="{{ url('event') }}" method="post">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['event']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="branch_id" class="col-form-label">{{ __('Branch') }}</label>
                    <select class="form-control select" name="branch_id" id="branch_id"
                        placeholder="{{ __('Select Branch') }}">
                        <option value="">{{ __('Select Branch') }}</option>
                        <option value="0">{{ __('All Branch') }}</option>
                        @foreach ($branch as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="department_id" class="col-form-label">{{ __('Department') }}</label>
                    <div class="department_div">
                        <select class="form-control department_id" name="department_id[]"
                            placeholder="Select Designation">
                            <option value="">{{ __('Select Designation') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="employee_id" class="col-form-label">{{ __('Employee') }}</label>
                    <div class="employee_div">
                        <select class="form-control employee_id" name="employee_id[]" placeholder="Select Employee">
                            <option value="">{{ __('Select Employee') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <div class="form-group">
                    <label for="title" class="col-form-label">{{ __('Event Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="{{ __('Enter Event Title') }}" value="{{ old('title') }}">
                </div>
            </div>
            <div class="col-md-6 col-sm-12 col-lg-6 col-xl-6">
                <div class="form-group">
                    <label for="start_date" class="col-form-label">{{ __('Event start Date') }}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control datetime-local"
                        autocomplete="off" value="{{ old('start_date') }}">
                </div>
            </div>
            <div class="col-md-6 col-sm-12 col-lg-6 col-xl-6">
                <div class="form-group">
                    <label for="end_date" class="col-form-label">{{ __('Event End Date') }}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control datetime-local"
                        autocomplete="off" value="{{ old('end_date') }}">
                </div>
            </div>
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <div class="form-group">
                    <label for="color" class="col-form-label d-block mb-3">{{ __('Event Select Color') }}</label>
                    <div class="btn-group-toggle btn-group-colors event-tag" data-toggle="buttons">
                        <label class="btn bg-info active p-3"><input type="radio" name="color" value="event-info"
                                checked class="d-none"></label>
                        <label class="btn bg-warning p-3"><input type="radio" name="color" value="event-warning"
                                class="d-none"></label>
                        <label class="btn bg-danger p-3"><input type="radio" name="color" value="event-danger"
                                class="d-none"></label>
                        <label class="btn bg-primary p-3"><input type="radio" name="color" value="event-success"
                                class="d-none"></label>
                        <label class="btn p-3" style="background-color: #51459d !important"><input type="radio"
                                name="color" class="d-none" value="event-primary"></label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="col-form-label">{{ __('Event Description') }}</label>
                <textarea name="description" id="description" class="form-control"
                    placeholder="{{ __('Enter Event Description') }}" rows="5">{{ old('description') }}</textarea>
            </div>
            @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
                <div class="form-group col-md-6">
                    <label for="synchronize_type"
                        class="form-label">{{ __('Synchronize in Google Calendar ?') }}</label>
                    <div class=" form-switch">
                        <input type="checkbox" class="form-check-input mt-2" name="synchronize_type"
                            id="switch-shadow" value="google_calender">
                        <label class="form-check-label" for="switch-shadow"></label>
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
        </div>
</form>
