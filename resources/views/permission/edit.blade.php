<form method="POST" action="{{ route('permissions.update', $permission->id) }}">
    @csrf
    @method('PUT')
    <div class="card-body">
        <div class="form-group">
            <label for="name">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control"
                placeholder="{{ __('Enter Permission Name') }}" value="{{ $permission->name }}">
            @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn green">{{ __('Update') }}</button>
    </div>
</form>
