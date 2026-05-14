<form method="POST" action="{{ url('otherpayment') }}">
    @csrf
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select id="type" name="type" class="form-control select amount_type" required>
                    @foreach ($otherpaytype as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control" required step="0.01">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
