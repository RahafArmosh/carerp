<form action="{{ route('competencies.update', $competencies->id) }}" method="post">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                        value="{{ old('name', $competencies->name) }}">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="type" class="form-label">{{ __('Type') }}</label>
                    <select name="type" id="type" class="form-control select">
                        <option value="">{{ __('Select Type') }}</option>
                        @foreach ($performance as $item)
                            <option value="{{ $item->id }}"
                                {{ old('type', $competencies->type) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
