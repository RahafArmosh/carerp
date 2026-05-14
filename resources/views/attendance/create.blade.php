<form action="{{ url('attendanceemployee') }}" method="post">
    @csrf
    <div class="card-body p-0">
        <div class="row">
            <div class="form-group col-lg-6 col-md-6">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id" id="employee_id" class="form-control select2">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="text" name="date" id="date" class="form-control datepicker">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="clock_in" class="form-label">{{ __('Clock In') }}</label>
                <input type="time" name="clock_in" id="clock_in" class="form-control">
            </div>
            <div class="form-group col-lg-6 col-md-6">
                <label for="clock_out" class="form-label">{{ __('Clock Out') }}</label>
                <input type="time" name="clock_out" id="clock_out" class="form-control">
            </div>
        </div>
    </div>
    <div class="modal-footer pr-0">
        <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
