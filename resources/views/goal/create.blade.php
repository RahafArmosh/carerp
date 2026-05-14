<form action="{{ url('goal') }}" method="POST">
    @csrf
    <div class="modal-body">
        <!-- form fields -->
        <div class="row">
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01">
            </div>
            <div class="form-group  col-md-12">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group  col-md-6">
                <label for="from" class="form-label">{{ __('From') }}</label>
                <input type="date" name="from" id="from" class="form-control" required>
            </div>
            <div class="form-group  col-md-6">
                <label for="to" class="form-label">{{ __('To') }}</label>
                <input type="date" name="to" id="to" class="form-control" required>
            </div>
            <div class="form-group col-md-12">
                <input class="form-check-input" type="checkbox" name="is_display" id="is_display" checked>
                <label class="custom-control-label form-label" for="is_display">{{ __('Display On Dashboard') }}</label>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
