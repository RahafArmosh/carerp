<form action="{{ route('job.on.board.update', $jobOnBoard->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="joining_date" class="col-form-label">{{ __('Joining Date') }}</label>
                <input type="date" name="joining_date" id="joining_date" class="form-control d_week" autocomplete="off"
                    value="{{ $jobOnBoard->joining_date }}">
            </div>

            <div class="form-group col-md-6">
                <label for="days_of_week" class="col-form-label">{{ __('Days Of Week') }}</label>
                <input type="text" name="days_of_week" id="days_of_week" class="form-control" autocomplete="off"
                    value="{{ $jobOnBoard->days_of_week }}">
            </div>
            <div class="form-group col-md-6">
                <label for="salary" class="col-form-label">{{ __('Salary') }}</label>
                <input type="text" name="salary" id="salary" class="form-control" autocomplete="off"
                    value="{{ $jobOnBoard->salary }}">
            </div>
            <div class="form-group col-md-6">
                <label for="salary_type" class="col-form-label">{{ __('Salary Type') }}</label>
                <select name="salary_type" id="salary_type" class="form-control select">
                    @foreach ($salary_type as $key => $value)
                        <option value="{{ $key }}" {{ $jobOnBoard->salary_type == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="salary_duration" class="col-form-label">{{ __('Salary Duration') }}</label>
                <select name="salary_duration" id="salary_duration" class="form-control select">
                    @foreach ($salary_duration as $key => $value)
                        <option value="{{ $key }}"
                            {{ $jobOnBoard->salary_duration == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="job_type" class="col-form-label">{{ __('Job Type') }}</label>
                <select name="job_type" id="job_type" class="form-control select">
                    @foreach ($job_type as $key => $value)
                        <option value="{{ $key }}" {{ $jobOnBoard->job_type == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="col-form-label">{{ __('Status') }}</label>
                <select name="status" id="status" class="form-control select">
                    @foreach ($status as $key => $value)
                        <option value="{{ $key }}" {{ $jobOnBoard->status == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
