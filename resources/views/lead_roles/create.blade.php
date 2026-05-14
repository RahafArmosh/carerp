<form class="" method="POST" action="{{ url('lead_roles') }}">
    @csrf
    <div class="modal-body repeater">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Role Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
        </div>
        <div class="form-group" data-repeater-list="conditions">
            <div class="condition row mb-3" data-repeater-item>
                <div class="col-md-4">
                    <select name="lead_column" class="form-select" required>
                        <option value="">Select Column</option>
                        @foreach ($leadColumns as $column)
                            <option value="{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="operation" class="form-select" required>
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value="contains">Contains</option>
                        <option value="not_contains">Does Not Contain</option>
                        <option value="starts_with">Starts With</option>
                        <option value="ends_with">Ends With</option>
                        <option value="is_empty">Is Empty</option>
                        <option value="is_not_empty">Is Not Empty</option>
                        <option value=">">></option>
                        <option value="<"><</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="value" class="form-control" placeholder="Value">
                </div>
                <div class="col-md-2">
                    <select name="logical_operator" class="form-select">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-secondary" data-repeater-create>+ Add Condition</button>
        <div class="mt-3">
            <label for="assigned_user_id" class="form-label">Assign to User</label>
            <select name="assigned_user_id" id="assigned_user_id" class="form-select select2" required>
                <option value="">-- Select User --</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label><br>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="status" name="active" value="1" checked>
                <label class="form-check-label" for="status">Active</label>
            </div>
        </div>
        <div class="col-6 form-group">
            <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}<span class="text-danger">*</span></label>
            <select name="pipeline_id" id="default_pipeline" class="form-control select2">
                @foreach ($pipeline as $id => $value)
                    <option value="{{ $value->id }}">{{ $value->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
<script>
    $(document).ready(function() {
        var $repeater = $('.repeater').repeater({
            initEmpty: false,
            defaultValues: {
                'status': 1
            },
            show: function() {
                $(this).slideDown();
                console.log('Condition added');
                // Reinitialize Select2 when a new repeater row is added
                setTimeout(function() {
                    common_bind()
                }, 200);
            },
            function(setIndexes) {
                $dragAndDrop.on('drop', setIndexes);
            },
            isFirstItemUndeletable: true
        });
    });
</script>
