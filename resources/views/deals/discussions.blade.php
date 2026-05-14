<form action="{{ route('deals.discussion.store', $deal->id) }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <label for="comment" class="form-label">{{ __('Message') }}</label>
                <textarea name="comment" id="comment" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
