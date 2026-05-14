<form action="{{ route('revenue.update', $revenue->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" class="form-control" value="{{ $revenue->date }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" value="{{ old('amount', $revenue->amount) }}" class="form-control"
                    required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" class="form-control select" required>
                    @foreach ($accounts as $id => $account)
                        <option value="{{ $id }}" {{ $revenue->account_id == $id ? 'selected' : '' }}>
                            {{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="revenue_account" class="form-label">{{ __('Revenu Account') }}</label>
                <select name="revenue_account" class="form-control select" required>
                    @foreach ($chartAccounts as $id => $account)
                        <option value="{{ $id }}" {{ $revenue->revenue_account == $id ? 'selected' : '' }}>
                            {{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                <select name="customer_id" class="form-control select" required>
                    @foreach ($customers as $id => $customer)
                        <option value="{{ $id }}" {{ $revenue->customer_id == $id ? 'selected' : '' }}>
                            {{ $customer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $revenue->description) }}</textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                <select name="category_id" class="form-control select" required>
                    @foreach ($categories as $id => $category)
                        <option value="{{ $id }}" {{ $revenue->category_id == $id ? 'selected' : '' }}>
                            {{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" value="{{ old('reference', $revenue->reference) }}"
                    class="form-control">
            </div>

            <div class="form-group col-md-6">
                <label for="add_receipt" class="col-form-label">{{ __('Payment Receipt') }}</label>
                <input type="file" name="add_receipt" class="form-control" id="files">
                <img id="image" src="{{ asset(Storage::url('uploads/revenue')) . '/' . $revenue->add_receipt }}"
                    class="mt-2" style="width: 25%;">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>

<script>
    document.getElementById('files').onchange = function() {
        var src = URL.createObjectURL(this.files[0]);
        document.getElementById('image').src = src;
    }
</script>
