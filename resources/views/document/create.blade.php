<form action="{{ url('document') }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control"
                    placeholder="{{ __('Enter Document Name') }}">
                @error('name')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group col-12">
                <label for="is_required" class="form-label">{{ __('Required Field') }}</label>
                <select class="form-control select2" name="is_required" required>
                    <option value="0">{{ __('Not Required') }}</option>
                    <option value="1">{{ __('Is Required') }}</option>
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
