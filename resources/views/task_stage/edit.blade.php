<form action="{{ route('project-task-stages.update', $taskStage->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Project Task Stage Title') }}</label>
                    <input type="text" name="name" class="form-control"
                        placeholder="{{ __('Enter project stage title') }}" value="{{ $taskStage->name }}" required>
                </div>
            </div>
            <div class="form-group col-12">
                <label for="color" class="form-label">{{ __('Color') }}</label>
                <input type="color" class="form-control" id="color" name="color" value="{{ $taskStage->color }}"
                    required>
                <small class="small">{{ __('For chart representation') }}</small>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
