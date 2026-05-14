<form action="{{ route('deals.client.permissions.store', [$deal->id, $client->id]) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <ul class="list-group">
            <div class="row">
                @foreach ($permissions as $key => $permission)
                    <div class="col-md-6 py-2 px-2">
                        <li class="list-group-item">
                            <div class="col-12 custom-control custom-checkbox mt-2 mb-2 p-0">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                    {{ in_array($permission, $selected) ? 'checked' : '' }} class="custom-control-input"
                                    id="permissions_{{ $key }}">
                                <label for="permissions_{{ $key }}"
                                    class="custom-control-label ml-4">{{ ucfirst($permission) }}</label>
                            </div>
                        </li>
                    </div>
                @endforeach
            </div>
        </ul>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
