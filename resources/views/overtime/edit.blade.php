<form method="POST" action="{{ route('overtime.update', $overtime->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="title" class="form-label">{{ __('Title') }}</label>
                        <input type="text" id="title" name="title" class="form-control" required
                            value="{{ $overtime->title }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="number_of_days" class="form-label">{{ __('Number Of Days') }}</label>
                        <input type="text" id="number_of_days" name="number_of_days" class="form-control" required
                            value="{{ $overtime->number_of_days }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="hours" class="form-label">{{ __('Hours') }}</label>
                        <input type="text" id="hours" name="hours" class="form-control" required
                            value="{{ $overtime->hours }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="rate" class="form-label">{{ __('Rate') }}</label>
                        <input type="number" id="rate" name="rate" class="form-control" required
                            value="{{ $overtime->rate }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
