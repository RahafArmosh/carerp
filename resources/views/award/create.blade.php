<form action="{{ url('award') }}" method="post">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['award']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}

        <div class="row">
            <div class="form-group col-md-6 col-lg-6">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id" id="employee_id" class="form-control select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="award_type" class="form-label">{{ __('Award Type') }}</label>
                <select name="award_type" id="award_type" class="form-control select" required>
                    @foreach ($awardtypes as $awardtype)
                        <option value="{{ $awardtype->id }}">{{ $awardtype->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control">
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="gift" class="form-label">{{ __('Gift') }}</label>
                <input type="text" name="gift" id="gift" class="form-control"
                    placeholder="{{ __('Enter Gift') }}">
            </div>
            <div class="form-group col-md-12">
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
