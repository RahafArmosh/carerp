<div class="card bg-none card-box">
    <form action="{{ url('clients') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-6 form-group">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="col-6 form-group">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="form-group mt-4 mb-0">
                <input type="hidden" name="ajax" value="true">
                <button type="submit" class="btn-create badge-blue">{{ __('Create') }}</button>
            </div>
        </div>
    </form>
</div>
