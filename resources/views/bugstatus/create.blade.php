<form action="{{ url('bugstatus') }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="title" class="form-label">{{ __('Bug Status Title') }}</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
