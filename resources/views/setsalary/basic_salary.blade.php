<form method="POST" action="{{ route('employee.salary.update', $employee->id) }}">
    @csrf
    @method('POST')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="salary_type" class="form-label">{{ __('Payslip Type') }}<span
                        class="text-danger">*</span></label>
                <select name="salary_type" id="salary_type" class="form-control select" required>
                    @foreach ($payslip_type as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="salary" class="form-label">{{ __('Salary') }}</label>
                <input type="number" name="salary" id="salary" class="form-control" required>
            </div>
            <div class="form-group col-md-12">
                <label for="account" class="form-label">{{ __('Bank Account') }}<span
                        class="text-danger">*</span></label>
                <select name="account" id="account" class="form-control select" required>
                    @foreach ($account as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Save Change') }}" class="btn  btn-primary">
    </div>
</form>
