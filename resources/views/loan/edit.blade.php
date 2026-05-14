<form action="{{ route('loan.update', $loan->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input type="text" id="title" name="title" value="{{ $loan->title }}"
                            class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="loan_option">{{ __('Loan Options') }}</label><span class="text-danger">*</span>
                        <select name="loan_option" id="loan_option" class="form-control select" required>
                            @foreach ($loan_options as $option)
                                <option value="{{ $option->id }}"
                                    {{ $option->id == $loan->loan_option ? 'selected' : '' }}>{{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="type" class="form-control select amount_type" required>
                            @foreach ($loans as $key => $value)
                                <option value="{{ $key }}" {{ $key == $loan->type ? 'selected' : '' }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="form-label amount_label">{{ __('Loan Amount') }}</label>
                        <input type="number" id="amount" name="amount" value="{{ $loan->amount }}"
                            class="form-control" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="reason">{{ __('Reason') }}</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required>{{ $loan->reason }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
