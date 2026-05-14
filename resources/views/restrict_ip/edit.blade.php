<form method="POST" action="{{ route('edit.ip', $ip->id) }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                <label for="ip" class="col-form-label">{{ __('IP') }}</label>
                <input type="text" name="ip" id="ip" value="{{ old('ip', $ip->ip) }}" class="form-control">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
