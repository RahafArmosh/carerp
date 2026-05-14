<form action="{{ url('earlyleave') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['earlyleave']) }}" data-bs-placement="top"
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
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date" class="form-label">{{ __('Date') }}</label>
                    <input type="date" id="date" name="date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time" class="form-label">{{ __('Time') }}</label>
                    <input type="time" id="time" name="time" class="form-control">
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
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
