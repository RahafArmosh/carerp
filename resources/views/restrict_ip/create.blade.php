<form method="POST" action="{{ route('create.ip') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                <label for="ip" class="col-form-label">{{ __('IP') }}</label>
                <input type="text" name="ip" id="ip" class="form-control">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
