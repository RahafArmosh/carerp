<form action="{{ route('allowance.update', $allowance->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="allowance_option">{{ __('Allowance Options') }}<span class="text-danger">*</span></label>
                        <select name="allowance_option" id="allowance_option" class="form-control select" required>
                            @foreach ($allowance_options as $id =>  $option)
                                <option value="{{ $id }}" @if ($id === $allowance->allowance_option) selected @endif>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input type="text" name="title" id="title" class="form-control" required value="{{ $allowance->title }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="type" class="form-control select amount_type" required>
                            @foreach ($Allowancetypes as $id => $type)
                                <option value="{{ $id }}" @if ($id === $allowance->type) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="form-label">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="amount" class="form-control" required value="{{ $allowance->amount }}">
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
