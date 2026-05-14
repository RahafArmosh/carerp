<form method="POST" action="{{ route('job-category.update', $jobCategory->id) }}">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="{{ __('Enter category title') }}" value="{{ $jobCategory->title }}">
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
