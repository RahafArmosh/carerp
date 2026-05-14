<form method="POST" action="{{ route('invoice-expense.store', ['id' => $id]) }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">

        <div class="row">
            <div class="form-group  col-md-6">
                <label for="product_id" class="form-label">{{ __('Account') }}<span class="text-danger">*</span></label>
                <select name="account_id" class="form-control select2" required>
                    @foreach ($chartAccounts as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" class="form-control"></textarea>
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
