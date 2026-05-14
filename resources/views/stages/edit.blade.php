<form action="{{ route('stages.update', $stage->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Stage Name') }}</label>
                <input type="text" name="name" value="{{ $stage->name }}" class="form-control" required>
            </div>
            <div class="form-group col-12">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}</label>
                <select name="pipeline_id" class="form-control select2" required>
                    @foreach ($pipelines as $id => $pipeline)
                        <option value="{{ $id }}"
                            {{ $id == $stage->pipeline_id ? 'selected' : '' }}>{{ $pipeline }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
