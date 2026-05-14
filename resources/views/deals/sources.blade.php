<form action="{{ route('deals.sources.update', $deal->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <div class="row gutters-xs">
                    @foreach ($sources as $source)
                        <div class="col-12 custom-control custom-checkbox mt-2 mb-2">
                            <input type="checkbox" name="sources[]" value="{{ $source->id }}" class="form-check-input"
                                id="sources_{{ $source->id }}" @if ($selected && array_key_exists($source->id, $selected)) checked @endif>
                            <label for="sources_{{ $source->id }}"
                                class="custom-control-label ml-4 text-sm font-weight-bold">{{ ucfirst($source->name) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
