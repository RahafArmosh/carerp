<form action="{{ route('trainer.update', $trainer->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch" class="form-control select" required>
                        @foreach ($branches as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="firstname" class="form-label">{{ __('First Name') }}</label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="lastname" class="form-label">{{ __('Last Name') }}</label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="contact" class="form-label">{{ __('Contact') }}</label>
                    <input type="text" name="contact" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>
            <div class="form-group col-lg-12">
                <label for="expertise" class="form-label">{{ __('Expertise') }}</label>
                <textarea name="expertise" class="form-control" placeholder="{{ __('Expertise') }}"></textarea>
            </div>
            <div class="form-group col-lg-12">
                <label for="address" class="form-label">{{ __('Address') }}</label>
                <textarea name="address" class="form-control" placeholder="{{ __('Address') }}"></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
