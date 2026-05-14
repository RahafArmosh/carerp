<form action="{{ route('deals.labels.store', $deal->id) }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12 form-group">
                <div class="row gutters-xs">
                    @foreach ($labels as $label)
                        <div class="col-12 custom-control custom-checkbox mt-2 mb-2">
                            <input type="checkbox" name="labels[]" value="{{ $label->id }}"
                                {{ array_key_exists($label->id, $selected) ? 'checked' : '' }} class="form-check-input"
                                id="labels_{{ $label->id }}">
                            <label for="labels_{{ $label->id }}"
                                class="custom-control-label ml-4 text-white p-2 px-3 rounded badge bg-{{ $label->color }}">{{ ucfirst($label->name) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Save') }}" class="btn  btn-primary">
    </div>
</form>
