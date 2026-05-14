<form action="{{ route('department.update', $department->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="branch_id">{{ __('Branch') }}</label>
                    <select name="branch_id" id="branch_id" class="form-control select"
                        placeholder="{{ __('Select Branch') }}">
                        @foreach ($branch as $id => $branchName)
                            <option value="{{ $id }}" @if ($department->branch_id == $id) selected @endif>
                                {{ $branchName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="name">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                        placeholder="{{ __('Enter Department Name') }}" value="{{ $department->name }}">
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
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
