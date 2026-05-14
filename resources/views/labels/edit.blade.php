<form method="POST" action="{{ route('labels.update', $label->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Label Name') }}</label>
                <input type="text" name="name" value="{{ $label->name }}" class="form-control" required>
            </div>
            <div class="form-group col-12">
                <label for="pipeline_id" class="form-label">{{ __('Pipeline') }}</label>
                <select name="pipeline_id" class="form-control select2" required>
                    @foreach ($pipelines as $id => $pipeline)
                        <option value="{{ $id }}" @if ($label->pipeline_id == $id) selected @endif>
                            {{ $pipeline }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-12">
                <label for="color" class="form-label">{{ __('Color') }}</label>
                <div class="row gutters-xs">
                    @foreach ($colors as $color)
                        <div class="col-auto">
                            <label class="colorinput">
                                <input type="radio" name="color" value="{{ $color }}"
                                    @if ($label->color == $color) checked @endif class="colorinput-input">
                                <span class="colorinput-color bg-{{ $color }}"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
