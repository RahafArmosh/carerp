<form method="POST" action="{{ route('performanceType.update', $performanceType->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="form-group">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control" required
                value="{{ $performanceType->name }}">
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
