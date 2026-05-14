<form action="{{ url('loan') }}" method="POST">
    @csrf
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="loan_option" class="form-label">{{ __('Loan Options') }}</label><span
                    class="text-danger">*</span>
                <select name="loan_option" id="loan_option" class="form-control select" required>
                    @foreach ($loan_options as $id => $option)
                        <option value="{{ $id }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select amount_type" required>
                    @foreach ($loan as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label amount_label">{{ __('Loan Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control" required step="0.01">
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="reason" class="form-label">{{ __('Reason') }}</label>
                    <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
