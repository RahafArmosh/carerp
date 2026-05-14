<form method="POST" action="{{ url('resignation') }}">
    @csrf
    <div class="modal-body">
        <!-- start for ai module -->
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['resignation']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        <!-- end for ai module -->
        <div class="row">
            @if (\Auth::user()->type != 'Employee')
                <div class="form-group col-lg-12">
                    <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                    <select name="employee_id" id="employee_id" class="form-control select" required>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group col-lg-6 col-md-6">
                <label for="notice_date" class="form-label">{{ __('Notice Date') }}</label>
                <input type="date" name="notice_date" id="notice_date" class="form-control">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="resignation_date" class="form-label">{{ __('Resignation Date') }}</label>
                <input type="date" name="resignation_date" id="resignation_date" class="form-control">
            </div>
            <div class="form-group col-lg-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" placeholder="{{ __('Enter Description') }}"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
