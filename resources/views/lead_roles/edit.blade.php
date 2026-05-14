<form method="POST" action="{{ route('lead_roles.update', $leadRole->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body repeater">
        <div class="form-group mb-3">
            <label for="name">{{ __('Role Name') }}</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $leadRole->name }}" required>
        </div>

        <label class="form-label">Conditions</label>
        <div data-repeater-list="conditions">
            @foreach ($leadRole->conditions as $index => $condition)
                <div data-repeater-item class="border p-3 mb-3 condition-block">
                    <div class="row">
                        <div class="col-md-3 ">
                            <label>Lead Column</label>
                            <select name="lead_column" class="form-select" required>
                                <option value="">-- Select Column --</option>
                                @foreach ($leadColumns as $column)
                                    <option value="{{ $column }}"
                                        {{ $condition->lead_column == $column ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $column)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 ">
                            <label>Operation</label>
                            <select name="operation" class="form-select" required>
                                <option value="=" {{ $condition->operation == '=' ? 'selected' : '' }}>=
                                    (Equal)
                                </option>
                                <option value="!=" {{ $condition->operation == '!=' ? 'selected' : '' }}>≠
                                    (Not
                                    Equal)</option>
                                <option value="contains" {{ $condition->operation == 'contains' ? 'selected' : '' }}>
                                    Contains</option>
                                <option value="not_contains"
                                    {{ $condition->operation == 'not_contains' ? 'selected' : '' }}>Not Contains
                                </option>
                                <option value="starts_with"
                                    {{ $condition->operation == 'starts_with' ? 'selected' : '' }}>Starts With
                                </option>
                                <option value="ends_with" {{ $condition->operation == 'ends_with' ? 'selected' : '' }}>
                                    Ends With</option>
                                <option value="is_empty" {{ $condition->operation == 'is_empty' ? 'selected' : '' }}>Is
                                    Empty</option>
                                <option value="is_not_empty"
                                    {{ $condition->operation == 'is_not_empty' ? 'selected' : '' }}>Is Not
                                    Empty
                                </option>
                                <option value=">" {{ $condition->operation == '>' ? 'selected' : '' }}>>
                                    (Greater Than)
                                </option>
                                <option value="<" {{ $condition->operation == '<' ? 'selected' : '' }}>
                                    < (Less Than) </option>
                            </select>
                        </div>

                        <div class="col-md-3 ">
                            <label>Value</label>
                            <input type="text" name="value" class="form-control" value="{{ $condition->value }}">
                        </div>

                        <div class="col-md-2 ">
                            <label>Logic</label>
                            <select name="logical_operator" class="form-select">
                                <option value="">--</option>
                                <option value="AND" {{ $condition->connector == 'AND' ? 'selected' : '' }}>
                                    AND
                                </option>
                                <option value="OR" {{ $condition->connector == 'OR' ? 'selected' : '' }}>OR
                                </option>
                            </select>
                        </div>
                        <div class="col-md-1 mt-4">
                            <button type="button"
                                data-url="{{ route('lead_roles.condition.destroy', $condition->id) }}"
                                class="btn btn-sm btn-danger remove-condition" title="Delete Condition">
                                &times;
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="id" value="{{ $condition->id }}">
                </div>
            @endforeach
        </div>
        <button type="button" data-repeater-create class="btn btn-sm btn-outline-primary mt-2">+ Add
            Condition</button>

        <div class="form-group mt-4">
            <label>Assign to User</label>
            <select name="assigned_user_id" class="form-select select2" data-placeholder="{{ __('Select User') }}"
                required>
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}"
                        {{ $leadRole->assigned_user_id == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mt-3">
            <label>Status</label><br>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="status" name="active" value="1"
                    {{ $leadRole->active ? 'checked' : '' }}>
                <label class="form-check-label" for="status">Active</label>
            </div>
        </div>
        <div class="col-6 form-group">
            <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}<span class="text-danger">*</span></label>
            <select name="pipeline_id" id="default_pipeline" class="form-control select2">
                @foreach ($pipeline as $id => $value)
                    <option value="{{ $value->id }}"
                        {{ $leadRole->pipeline_id !== null && $leadRole->pipeline_id == $value->id ? 'selected' : '' }}>
                        {{ $value->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
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
                setTimeout(function() {
                    common_bind()
                }, 200);
            },
            function(setIndexes) {
                $dragAndDrop.on('drop', setIndexes);
            },
        });
    });
    $(document).on('shown.bs.modal', '.modal', function() {
        var modal = $(this);
        // Initialize select2 for select fields within this modal
        modal.find('.select2').select2({
            dropdownParent: modal
        });
    });
</script>

<script>
    // Event delegation to handle remove
    document.querySelectorAll('.remove-condition').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const url = this.dataset.url;
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger",
                },
                buttonsStyling: false,
            });
            swalWithBootstrapButtons.fire({
                title: "Are you sure?",
                text: "This action cannot be undone. Do you want to continue?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes",
                target: document.getElementById('commonModal'),
                cancelButtonText: "No",
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        dataType: 'JSON',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            if (data.success) {
                                btn.closest('.condition-block').remove();
                                Swal.fire({
                                    title: "Deleted!",
                                    text: data.message,
                                    icon: "success",
                                    target: document.getElementById(
                                        'commonModal'),
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: "Could not delete.",
                                    icon: "error",
                                    target: document.getElementById(
                                        'commonModal'),
                                });
                            }
                        },
                        error: function(err) {
                            console.error(err);
                            Swal.fire({
                                title: "Error!",
                                text: err.responseJSON.message ||
                                    "An error occurred while deleting the condition.",
                                icon: "error",
                                target: document.getElementById(
                                    'commonModal'),
                            });
                        }
                    });
                }
            });
        });
    });
    // SweetAlert mixin for confirming condition deletion
</script>
