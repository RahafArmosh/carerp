<form action="{{ route('leads.sources.update', $lead->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <div class="row gutters-xs">
                    @foreach ($sources as $source)
                        <div class="col-12 custom-control custom-checkbox mt-2 mb-2">
                            <input type="checkbox" name="sources[]" id="sources_{{ $source->id }}"
                                value="{{ $source->id }}" class="form-check-input"
                                {{ $selected && array_key_exists($source->id, $selected) ? 'checked' : '' }}>
                            <label for="sources_{{ $source->id }}"
                                class="form-check-label">{{ ucfirst($source->name) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
