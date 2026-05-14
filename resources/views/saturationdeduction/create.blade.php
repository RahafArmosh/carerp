<form method="POST" action="{{ url('saturationdeduction') }}">
    @csrf
    <div class="modal-body">
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="deduction_option" class="form-label">{{ __('Deduction Options') }}<span
                        class="text-danger">*</span></label>
                <select name="deduction_option" id="deduction_option" class="form-control select" required>
                    @foreach ($deduction_options as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select amount_type" required>
                    @foreach ($saturationdeduc as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
