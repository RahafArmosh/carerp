<form action="{{ route('leads.users.update', $lead->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <label for="users" class="form-label">{{ __('User') }}</label>
                <select name="users[]" id="choices-multiple3" class="form-control select2" multiple>
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}" @if (in_array($id, $lead->users->pluck('id')->toArray())) selected @endif>
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
