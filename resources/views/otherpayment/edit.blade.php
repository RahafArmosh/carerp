<form method="POST" action="{{ route('otherpayment.update', $otherpayment->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input type="text" id="title" name="title" class="form-control" required
                            value="{{ $otherpayment->title }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
                        <select id="type" name="type" class="form-control select amount_type" required>
                            @foreach ($otherpaytypes as $type)
                                <option value="{{ $type }}"
                                    {{ $otherpayment->type == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="form-label amount_label">{{ __('Amount') }}</label>
                        <input type="number" id="amount" name="amount" class="form-control" required step="0.01"
                            value="{{ $otherpayment->amount }}">
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
