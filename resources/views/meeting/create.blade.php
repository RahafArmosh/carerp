<form action="{{ url('meeting') }}" method="POST">
    @csrf
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
                    <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                    <select class="form-control select" name="branch_id" id="branch_id" placeholder="Select Branch">
                        <option value="">{{ __('Select Branch') }}</option>
                        <option value="0">{{ __('All Branch') }}</option>
                        @foreach ($branch as $branchItem)
                            <option value="{{ $branchItem->id }}">{{ $branchItem->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group" id="department_div">
                    <label for="department_id" class="form-label">{{ __('Department') }}</label>
                    <select class="form-control select" name="department_id[]" id="department_id"
                        placeholder="Select Department">
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group" id="employee_div">
                    <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                    <select class="form-control select" name="employee_id[]" id="employee_id"
                        placeholder="Select Employee">
                    </select>
                </div>
            </div>
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

            @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
                <div class="form-group col-md-6 ">
                    <label for="synchronize_type"
                        class="form-label">{{ __('Synchronize in Google Calendar ?') }}</label>
                    <div class="form-switch">
                        <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                            value="google_calender">
                        <label class="form-check-label" for="switch-shadow"></label>
                    </div>
                </div>
            @endif

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
