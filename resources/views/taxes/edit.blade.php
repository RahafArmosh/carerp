<form action="{{ route('taxes.update', $tax->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Tax Rate Name') }}</label>
                <input id="name" type="text" name="name" class="form-control font-style" required
                    value="{{ $tax->name }}">
                @error('name')
                    <small class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="form-group col-md-6">
                <label for="rate" class="form-label">{{ __('Tax Rate %') }}</label>
                <input id="rate" type="number" name="rate" class="form-control" required step="0.01"
                    value="{{ $tax->rate }}">

                @error('rate')
                    <small class="invalid-rate" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="form-group col-md-12 account ">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>

                <select class="form-control select select2" name="chart_account" id="chart_account">
                    <option value="{{ $tax_chart_accounts->id }}">{{ $tax_chart_accounts->name }}</option>
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
