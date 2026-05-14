<form action="{{ route('form.field.update', [$form->id, $form_field->id]) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row" id="frm_field_data">
            <div class="col-12 form-group">
                <label for="name" class="form-label">{{ __('Question Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $form_field->name }}" required>
            </div>
            <div class="col-12 form-group">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select2" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" {{ $form_field->type == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
