<form action="{{ route('contractType.update', $contractType->id) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" class="form-control" required
                    value="{{ old('name', $contractType->name) }}" />
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
