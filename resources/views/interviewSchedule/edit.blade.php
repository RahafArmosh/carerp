<form action="{{ route('interview-schedule.update', $interviewSchedule->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="candidate" class="form-label">Interview To</label>
                <select name="candidate" id="candidate" class="form-control select" required>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate }}" @if ($candidate == $interviewSchedule->candidate) selected @endif>
                            {{ $candidate }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="employee" class="form-label">Interviewer</label>
                <select name="employee" id="employee" class="form-control select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee }}" @if ($employee == $interviewSchedule->employee) selected @endif>
                            {{ $employee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Interview Date</label>
                <input type="date" name="date" id="date" class="form-control"
                    value="{{ $interviewSchedule->date }}">
            </div>
            <div class="form-group col-md-6">
                <label for="time" class="form-label">Interview Time</label>
                <input type="time" name="time" id="time" class="form-control timepicker"
                    value="{{ $interviewSchedule->time }}">
            </div>
            <div class="form-group col-md-12">
                <label for="comment" class="form-label">Comment</label>
                <textarea name="comment" id="comment" class="form-control">{{ $interviewSchedule->comment }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
