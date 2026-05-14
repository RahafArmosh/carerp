<form action="{{ route('clients.update', $client->id) }}" method="post">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control"
                    placeholder="{{ __('Enter Client Name') }}" required value="{{ $client->name }}">
            </div>
            <div class="form-group">
                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="{{ __('Enter Client Email') }}" required value="{{ $client->email }}">
            </div>

            @if (!$customFields->isEmpty())
                @include('custom_fields.formBuilder')
            @endif

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
