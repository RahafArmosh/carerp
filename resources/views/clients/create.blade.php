<form action="{{ url('clients') }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control"
                    placeholder="{{ __('Enter client Name') }}" required>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="{{ __('Enter Client Email') }}" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input type="password" name="password" id="password" class="form-control"
                    placeholder="{{ __('Enter User Password') }}" required minlength="6">
                @error('password')
                    <small class="invalid-password" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>

            @if (!$customFields->isEmpty())
                @include('custom_fields.formBuilder')
            @endif

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
