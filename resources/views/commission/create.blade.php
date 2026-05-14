<form action="{{ url('commission') }}" method="post">
    @csrf
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" name="title" id="title" class="form-control" required
                    value="{{ old('title') }}">
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select amount_type" required>
                    <option value="">{{ __('Select Commission Type') }}</option>
                    @foreach ($commissions as $key => $value)
                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01"
                    value="{{ old('amount') }}">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
