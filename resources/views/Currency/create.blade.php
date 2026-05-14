<form method="POST" action="{{ route('currency.store') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="code" class="form-label">{{ __('Currency code') }}</label>
                <input type="text" name="code" id="code" class="form-control" required>

            </div>
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Currency Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>

            </div>
            <div class="form-group col-md-12">
                <label for="symbol" class="form-label">{{ __('Currency Symbol') }}</label>
                <input type="text" name="symbol" id="symbol" class="form-control" required>

            </div>
            <div class="form-group col-md-6">
                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.01">
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
