<form action="{{ route('form.field.store', $formbuilder->id) }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row" id="frm_field_data">
            <div class="col-12 form-group">
                <label for="name" class="form-label">{{ __('Question Name') }}</label>
                <input type="text" name="name[]" id="name" class="form-control" required>
            </div>
            <div class="col-12 form-group">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type[]" id="type" class="form-control select2" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
