<form action="{{ route('leavetype.update', $leavetype->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Leave Type') }}</label>
                    <input type="text" id="title" name="title" value="{{ $leavetype->title }}"
                        class="form-control" placeholder="{{ __('Enter Leave Type Name') }}">
                    @error('title')
                        <span class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="days" class="form-label">{{ __('Days Per Year') }}</label>
                    <input type="number" id="days" name="days" value="{{ $leavetype->days }}"
                        class="form-control" placeholder="{{ __('Enter Days / Year') }}">
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
