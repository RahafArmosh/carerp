<form method="POST" action="{{ route('saturationdeduction.update', $saturationdeduction->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="deduction_option">{{ __('Deduction Options') }}<span
                                class="text-danger">*</span></label>
                        <select name="deduction_option" id="deduction_option" class="form-control select" required>
                            @foreach ($deduction_options as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="type" class="form-control select amount_type" required>
                            @foreach ($saturationdeduc as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="amount" class="form-control" required
                            step="0.01">
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
