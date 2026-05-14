<div class="card bg-none card-box">
    <form action="{{ route('chart-of-account-type.update', $chartOfAccountType->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row" style="margin: auto;">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" id="name" name="name" class="form-control" required="required"
                    value="{{ $chartOfAccountType->name }}">
                @error('name')
                    <small class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>

        </div>


        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
        </div>
    </form>
</div>
