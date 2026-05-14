<form action="{{ route('job.on.board.store', $id) }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            @if ($id == 0)
                <div class="form-group col-md-12">
                    <label for="application" class="col-form-label">{{ __('Interviewer') }}</label>
                    <select name="application" id="application" class="form-control select2" required>
                        @foreach ($applications as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group col-md-12">
                <label for="joining_date" class="col-form-label">{{ __('Joining Date') }}</label>
                <input type="date" name="joining_date" id="joining_date" class="form-control" autocomplete="off">
            </div>

            <div class="form-group col-md-6">
                <label for="days_of_week" class="col-form-label">{{ __('Days Of Week') }}</label>
                <input type="number" name="days_of_week" id="days_of_week" class="form-control" autocomplete="off"
                    min="0">
            </div>
            <div class="form-group col-md-6">
                <label for="salary" class="col-form-label">{{ __('Salary') }}</label>
                <input type="number" name="salary" id="salary" class="form-control" autocomplete="off"
                    min="0">
            </div>
            <div class="form-group col-md-6">
                <label for="salary_type" class="col-form-label">{{ __('Salary Type') }}</label>
                <select name="salary_type" id="salary_type" class="form-control select">
                    @foreach ($salary_type as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="salary_duration" class="col-form-label">{{ __('Salary Duration') }}</label>
                <select name="salary_duration" id="salary_duration" class="form-control select">
                    @foreach ($salary_duration as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="job_type" class="col-form-label">{{ __('Job Type') }}</label>
                <select name="job_type" id="job_type" class="form-control select">
                    @foreach ($job_type as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="col-form-label">{{ __('Status') }}</label>
                <select name="status" id="status" class="form-control select">
                    @foreach ($status as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
