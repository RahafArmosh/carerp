<form action="{{ route('sources.update', $source->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Source Name') }}</label>
                <input type="text" name="name" value="{{ $source->name }}" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-12">
                <label for="order" class="form-label">{{ __('Source Order') }}</label>
                <input type="text" name="order" value="{{ $source->order }}" class="form-control" required>
            </div>
        </div>
        <div class="col-6 form-group">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}<span class="text-danger">*</span></label>
                <select name="pipeline_id" id="default_pipeline" class="form-control select2">
                    @foreach ($pipeline as $id => $value)
                        <option value="{{ $value->id }}" {{ ($source->pipeline_id !== null && $source->pipeline_id == $value->id) ? 'selected' : '' }}>{{ $value->name }}</option>
                    @endforeach
                </select>
            </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
