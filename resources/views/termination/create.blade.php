<form action="{{ url('termination') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['termination']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-lg-6 col-md-6">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id" class="form-control select" required>
                    @foreach ($employees as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="termination_type" class="form-label">{{ __('Termination Type') }}</label>
                <select name="termination_type" class="form-control select" required>
                    @foreach ($terminationtypes as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="notice_date" class="form-label">{{ __('Notice Date') }}</label>
                <input type="date" name="notice_date" class="form-control">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="termination_date" class="form-label">{{ __('Termination Date') }}</label>
                <input type="date" name="termination_date" class="form-control">
            </div>
            <div class="form-group col-lg-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
