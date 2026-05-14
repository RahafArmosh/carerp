<form action="{{ route('custom-question.update', $customQuestion->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="question" class="form-label">{{ __('Question') }}</label>
                    <input type="text" name="question" class="form-control" placeholder="{{ __('Enter question') }}"
                        required />
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="is_required" class="form-label">{{ __('Is Required') }}</label>
                    <select name="is_required" class="form-control select" required>
                        @foreach ($is_required as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
