<form method="POST" action="{{ route('leads.emails.store', $lead->id) }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-6 form-group">
                <label for="to" class="form-label">{{ __('Mail To') }}</label>
                <input type="email" name="to" id="to" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="subject" class="form-label">{{ __('Subject') }}</label>
                <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="col-12 form-group">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="emails-summernote" class="summernote-simple form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
