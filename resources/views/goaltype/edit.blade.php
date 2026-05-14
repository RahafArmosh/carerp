<form action="{{ route('goaltype.update', $goalType->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control"
                        placeholder="{{ __('Enter Goal Type Name') }}">
                    @error('name')
                        <span class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
    <form>
