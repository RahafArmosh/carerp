<form method="POST" action="{{ route('job-stage.update', $jobStage->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Title') }}</label>
                    <input id="title" type="text" name="title" class="form-control"
                        placeholder="{{ __('Enter stage title') }}" value="{{ $jobStage->title }}">
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
