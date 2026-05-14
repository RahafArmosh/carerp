<form action="interview-schedule" method="post">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="candidate" class="form-label">Interview To</label>
                <select name="candidate" id="candidate" class="form-control select" required>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate }}">{{ $candidate }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="employee" class="form-label">Interviewer</label>
                <select name="employee" id="employee" class="form-control select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee }}">{{ $employee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Interview Date</label>
                <input type="date" name="date" id="date" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="time" class="form-label">Interview Time</label>
                <input type="time" name="time" id="time" class="form-control timepicker">
            </div>
            <div class="form-group col-md-12">
                <label for="comment" class="form-label">Comment</label>
                <textarea name="comment" id="comment" class="form-control"></textarea>
            </div>
            @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
                <div class="form-group col-md-12">
                    <label for="synchronize_type" class="form-label">Synchronize in Google Calendar?</label>
                    <div class="form-switch">
                        <input type="checkbox" name="synchronize_type" id="synchronize_type"
                            class="form-check-input mt-2" value="google_calendar">
                        <label for="synchronize_type" class="form-check-label"></label>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="Create" class="btn btn-primary">
    </div>
</form>
@if ($candidate != 0)
    <script>
        $('select#candidate').val({{ $candidate }}).trigger('change');
    </script>
@endif
