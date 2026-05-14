<form method="POST" action="{{ url('permissions') }}">
    @csrf
    <div class="modal-body">
        <div class="form-group">
            <label for="name">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control"
                placeholder="{{ __('Enter Permission Name') }}">
            @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group">
            @if (!$roles->isEmpty())
                <h6>{{ __('Assign Permission to Roles') }}</h6>
                @foreach ($roles as $role)
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="custom-control-input"
                            id="role{{ $role->id }}">
                        <label class="custom-control-label"
                            for="role{{ $role->id }}">{{ ucfirst($role->name) }}</label>
                    </div>
                @endforeach
            @endif
            @error('roles')
                <span class="invalid-roles" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
