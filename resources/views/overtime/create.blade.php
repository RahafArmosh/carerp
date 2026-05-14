<form method="POST" action="{{ url('overtime') }}">
    @csrf
    <div class="modal-body">
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">

        <div class="row">
            <div class="form-group col-md-6">
                <label for="title" class="form-label">{{ __('Overtime Title') }}<span
                        class="text-danger">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="number_of_days" class="form-label">{{ __('Number of days') }}</label>
                <input type="number" id="number_of_days" name="number_of_days" class="form-control" required
                    step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="hours" class="form-label">{{ __('Hours') }}</label>
                <input type="number" id="hours" name="hours" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="rate" class="form-label">{{ __('Rate') }}</label>
                <input type="number" id="rate" name="rate" class="form-control" required step="0.01">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
