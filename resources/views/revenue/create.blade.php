<form action="{{ url('revenue') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" class="form-control select2" required>
                    @foreach ($accounts as $id => $account)
                        <option value="{{ $id }}">{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="revenue_account" class="form-label">{{ __('Revenu Account') }}</label>
                <select name="revenue_account" class="form-control select2" required id="revenue_account">
                    @foreach ($chartAccounts as $id => $account)
                        <option value="{{ $id }}">{{ $account }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                <select name="customer_id" class="form-control select2" required>
                    @foreach ($customers as $id => $customer)
                        <option value="{{ $id }}">{{ $customer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                <select name="category_id" class="form-control select" required>
                    @foreach ($categories as $id => $category)
                        <option value="{{ $id }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="project_id" class="form-label">{{ __('Project') }}</label>
                <select name="project_id" class="form-control select">
                    @foreach ($projects as $id => $item)
                        <option value="{{ $id }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <input type="file" name="add_receipt" class="form-control" id="files">
                <img id="image" class="mt-3" style="width: 25%;">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
