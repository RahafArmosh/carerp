<form action="{{ route('award.update', $award->id) }}" method="post">
    @csrf
    @method('PUT')
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
                        <option value="{{ $employee->id }}"
                            {{ $award->employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="award_type" class="form-label">{{ __('Award Type') }}</label>
                <select name="award_type" id="award_type" class="form-control select" required>
                    @foreach ($awardtypes as $awardtype)
                        <option value="{{ $awardtype->id }}"
                            {{ $award->award_type == $awardtype->id ? 'selected' : '' }}>{{ $awardtype->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $award->date }}">
            </div>
            <div class="form-group col-md-6 col-lg-6">
                <label for="gift" class="form-label">{{ __('Gift') }}</label>
                <input type="text" name="gift" id="gift" class="form-control"
                    placeholder="{{ __('Enter Gift') }}" value="{{ $award->gift }}">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" placeholder="{{ __('Enter Description') }}">{{ $award->description }}</textarea>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
