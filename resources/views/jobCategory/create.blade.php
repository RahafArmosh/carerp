<form method="POST" action="{{ url('job-category') }}">
    @csrf

    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="{{ __('Enter category title') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
