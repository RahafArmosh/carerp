<form action="{{ route('attendanceemployee.update', $attendanceEmployee->id) }}" method="post">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-lg-6">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id" id="employee_id" class="form-control select">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}"
                            {{ $attendanceEmployee->employee_id == $employee->id ? 'elected' : '' }}>
                            {{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control"
                    value="{{ $attendanceEmployee->date }}">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-6">
                <label for="clock_in" class="form-label">{{ __('Clock In') }}</label>
                <input type="time" name="clock_in" id="clock_in" class="form-control"
                    value="{{ $attendanceEmployee->clock_in }}">
            </div>
            <div class="form-group col-lg-6">
                <label for="clock_out" class="form-label">{{ __('Clock Out') }}</label>
                <input type="time" name="clock_out" id="clock_out" class="form-control"
                    value="{{ $attendanceEmployee->clock_out }}">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
