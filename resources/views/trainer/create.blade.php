<form action="{{ url('trainer') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch" id="branch" class="form-control select" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="firstname" class="form-label">{{ __('First Name') }}</label>
                    <input type="text" name="firstname" id="firstname" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="lastname" class="form-label">{{ __('Last Name') }}</label>
                    <input type="text" name="lastname" id="lastname" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="contact" class="form-label">{{ __('Contact') }}</label>
                    <input type="text" name="contact" id="contact" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input type="text" name="email" id="email" class="form-control" required>
                </div>
            </div>
            <div class="form-group col-lg-12">
                <label for="expertise" class="form-label">{{ __('Expertise') }}</label>
                <textarea name="expertise" id="expertise" class="form-control" placeholder="{{ __('Expertise') }}"></textarea>
            </div>
            <div class="form-group col-lg-12">
                <label for="address" class="form-label">{{ __('Address') }}</label>
                <textarea name="address" id="address" class="form-control" placeholder="{{ __('Address') }}"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
