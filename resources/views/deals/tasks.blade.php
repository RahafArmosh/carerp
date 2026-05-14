@if (isset($task))
    <form action="{{ route('deals.tasks.update', [$deal->id, $task->id]) }}" method="post">
        @csrf
        @method('PUT')
    @else
        <form action="{{ route('deals.tasks.store', $deal->id) }}" method="post">
            @csrf
@endif
<div class="modal-body">
    <div class="row">
        <div class="col-12 form-group">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="col-6 form-group">
            <label for="date" class="form-label">{{ __('Date') }}</label>
            <input type="date" name="date" id="date" class="form-control" required>
        </div>
        <div class="col-6 form-group">
            <label for="time" class="form-label">{{ __('Time') }}</label>
            <input type="time" name="time" id="time" class="form-control" required>
        </div>
        <div class="col-6 form-group">
            <label for="priority" class="form-label">{{ __('Priority') }}</label>
            <select name="priority" id="priority" class="form-control select2" required>
                @foreach ($priorities as $key => $priority)
                    <option value="{{ $key }}" @if (isset($task) && $task->priority == $key) selected @endif>
                        {{ __($priority) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 form-group">
            <label for="status" class="form-label">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-control select2" required>
                @foreach ($status as $key => $st)
                    <option value="{{ $key }}" @if (isset($task) && $task->status == $key) selected @endif>
                        {{ __($st) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    @if (isset($task))
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    @else
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    @endif

</div>
</form>


<script>
    $('#date').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        },
        singleDatePicker: true,
    });
    $("#time").timepicker({
        icons: {
            up: 'ti ti-chevron-up',
            down: 'ti ti-chevron-down'
        }
    });
</script>
