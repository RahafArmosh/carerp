<form action="{{ route('task-master.update', $taskMaster->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                        value="{{ old('name', $taskMaster->name) }}" required>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $taskMaster->description) }}</textarea>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="department_id" class="form-label">{{ __('Department') }}</label>
                    <select name="department_id" id="department_id" class="form-control select">
                        @foreach ($departments as $id => $label)
                            <option value="{{ $id === '' ? '' : $id }}"
                                {{ (string) old('department_id', $taskMaster->department_id) === (string) ($id === '' ? '' : $id) ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group form-check">
                    <input type="checkbox" name="is_predefined" id="is_predefined" class="form-check-input" value="1"
                        {{ old('is_predefined', $taskMaster->is_predefined) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_predefined">{{ __('Predefined task') }}</label>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                        {{ old('is_active', $taskMaster->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
