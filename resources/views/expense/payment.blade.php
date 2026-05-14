<form action="{{ route('expense.addPayment', $expense->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" id="date" name="date" class="form-control" required="required">
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" value="{{ $expense->getDue() }}" class="form-control" required="required" step="0.01" min="0">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="account_id" class="form-control select select2" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" id="reference" name="reference" class="form-control" placeholder="{{ __('Reference') }}">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="{{ __('Description') }}"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{__('Add')}}" class="btn  btn-primary">
        </div>
    </div>
</form>
