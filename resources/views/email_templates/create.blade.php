<form method="POST" action="{{ url('email_template') }}">
    @csrf
    <div class="row">
        <div class="form-group col-md-12">
            <label for="name">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control font-style" required>
        </div>
        <div class="form-group col-md-12 text-end">
            <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
        </div>
    </div>
</form>
