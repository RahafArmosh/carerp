<form action="{{ route('deals.users.update', $deal->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <label for="users" class="form-label">{{ __('User') }}</label>
                <select name="users[]" id="users" class="form-control select2" multiple required>
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}"
                            {{ in_array($id, $deal->users->pluck('id')->toArray()) ? 'selected' : '' }}>
                            {{ $user }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
