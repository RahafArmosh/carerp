<form action="{{ url('leave') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['leave']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'HR')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                        <select name="employee_id" id="employee_id" class="form-control select"
                            placeholder="{{ __('Select Employee') }}">
                            <option value="">{{ __('Select Employee') }}</option>
                            @foreach ($employees as $id => $employee)
                                <option value="{{ $id }}">{{ $employee }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="leave_type_id" class="form-label">{{ __('Leave Type') }}</label>
                    <select name="leave_type_id" id="leave_type_id" class="form-control select"
                        placeholder="{{ __('Select Leave Type') }}">
                        <option value="">{{ __('Select Leave Type') }}</option>
                        @foreach ($leavetypes as $leave)
                            <option value="{{ $leave->id }}">{{ $leave->title }} (<p class="float-right pr-5">
                                    {{ $leave->days }}</p>)</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" id="start_date" name="start_date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                    <input type="date" id="end_date" name="end_date" class="form-control">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="leave_reason" class="form-label">{{ __('Leave Reason') }}</label>
                    <textarea id="leave_reason" name="leave_reason" class="form-control" placeholder="{{ __('Leave Reason') }}"></textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm text-right"
                    data-ajax-popup-over="true" id="grammarCheck" data-url="{{ route('grammar', ['grammar']) }}"
                    data-bs-placement="top" data-title="{{ __('Grammar check with AI') }}">
                    <i class="ti ti-rotate"></i> <span>{{ __('Grammar check with AI') }}</span>
                </a>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="remark" class="form-label">{{ __('Remark') }}</label>
                    <textarea id="remark" name="remark" class="form-control grammer_textarea" placeholder="{{ __('Leave Remark') }}"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
