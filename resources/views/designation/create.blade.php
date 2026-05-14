<form action="{{ url('designation') }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="department_id" class="form-label">{{ __('Department') }}</label>
                    <select name="department_id" id="department_id" class="form-control select" required>
                        @foreach ($departments as $id => $departmentName)
                            <option value="{{ $id }}">{{ $departmentName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                        placeholder="{{ __('Enter Designation Name') }}">
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
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
