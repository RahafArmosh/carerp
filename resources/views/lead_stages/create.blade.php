<form method="POST" action="{{ url('lead_stages') }}">
    @csrf

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Lead Stage Name') }}</label>
                <input id="name" type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group col-12">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}</label>
                <select id="pipeline_id" name="pipeline_id" class="form-control select2" required>
                    @foreach ($pipelines as $id => $pipeline)
                        <option value="{{ $id }}">{{ $pipeline }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
    </div>
</form>
