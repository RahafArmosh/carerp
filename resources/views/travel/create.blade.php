<form action="{{ url('travel') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['travel']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select id="employee_id" name="employee_id" class="form-control select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                <input id="start_date" type="date" class="form-control" name="start_date">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                <input id="end_date" type="date" class="form-control" name="end_date">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="purpose_of_visit" class="form-label">{{ __('Purpose of Trip') }}</label>
                <input id="purpose_of_visit" type="text" class="form-control" name="purpose_of_visit">
            </div>
            <div class="form-group col-md-6">
                <label for="place_of_visit" class="form-label">{{ __('Country') }}</label>
                <input id="place_of_visit" type="text" class="form-control" name="place_of_visit">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" class="form-control" name="description" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
