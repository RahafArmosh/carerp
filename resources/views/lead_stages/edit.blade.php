<form method="POST" action="{{ route('lead_stages.update', $leadStage->id) }}">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Stage Name') }}</label>
                <input id="name" type="text" name="name" value="{{ $leadStage->name }}" class="form-control" required>
            </div>
            <div class="form-group col-12">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}</label>
                <select id="pipeline_id" name="pipeline_id" class="form-control select2" required>
                    @foreach($pipelines as $id => $pipeline)
                        <option value="{{ $id }}" {{ $id == $leadStage->pipeline_id ? 'selected' : '' }}>
                            {{ $pipeline }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>
</form>
