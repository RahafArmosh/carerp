<form method="POST" action="{{ url('warning') }}">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['warning']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            @if (\Auth::user()->type != 'Employee')
                <div class="form-group col-md-6 col-lg-6">
                    <label class="form-label" for="warning_by">{{ __('Warning By') }}</label>
                    <select class="form-control select" name="warning_by" required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group col-md-6 col-lg-6">
                <label class="form-label" for="warning_to">{{ __('Warning To') }}</label>
                <select class="form-control select" name="warning_to">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label class="form-label" for="subject">{{ __('Subject') }}</label>
                <input type="text" class="form-control" name="subject">
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label class="form-label" for="warning_date">{{ __('Warning Date') }}</label>
                <input type="date" class="form-control" name="warning_date">
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="description">{{ __('Description') }}</label>
                <textarea class="form-control" name="description" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
