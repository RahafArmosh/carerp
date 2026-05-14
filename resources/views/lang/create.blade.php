<form method="POST" action="{{ route('store.language') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="code" class="form-label">{{ __('Language Code') }}</label>
                <input id="code" type="text" name="code" class="form-control" required>
                @error('code')
                    <span class="invalid-code" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="full_name" class="form-label">{{ __('Language Name') }}</label>
                <input id="full_name" type="text" name="full_name" class="form-control" required>
                @error('full_name')
                    <span class="invalid-code" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
