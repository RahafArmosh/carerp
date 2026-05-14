<form action="{{ route('webhook.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="module" class="form-label">{{ __('Module') }}</label>
                    <select name="module" id="module" class="form-control select" required>
                        @foreach ($modules as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="url" class="form-label">{{ __('Url') }}</label>
                    <input type="text" name="url" id="url" class="form-control"
                        placeholder="{{ __('Enter Webhook Url') }}">
                    @error('url')
                        <span class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label for="method" class="form-label">{{ __('Method') }}</label>
                    <select name="method" id="method" class="form-control select" required>
                        @foreach ($methods as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
