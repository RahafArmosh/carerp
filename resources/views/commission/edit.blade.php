<form action="{{ route('commission.update', $commission->id) }}" method="post">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="title" class="form-label">{{ __('Title') }}</label>
                        <input type="text" name="title" id="title" class="form-control" required
                            value="{{ $commission->title }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="type" class="form-control select amount_type" required>
                            <option value="">{{ __('Select Commission Type') }}</option>
                            @foreach ($commissions as $key => $value)
                                <option value="{{ $key }}" {{ $commission->type == $key ? 'elected' : '' }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="amount" class="form-control" required step="0.01"
                            value="{{ $commission->amount }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
