<form action="{{ route('product-unit.update', $unit->id) }}" method="POST">
    @csrf
    @method('PUT')
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            <label for="name" class="form-label">{{ __('Unit Name') }}</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $unit->name }}" required>
            @error('name')
            <small class="invalid-name" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </small>
            @enderror
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn btn-primary">
</div>
</form>
