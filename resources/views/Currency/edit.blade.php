<form action="{{ route('currency.update', $currency->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="code" class="form-label">{{ __('Currency code') }}</label>
                <input type="text" name="code" id="code" class="form-control" value="{{ $currency->code  }}">

            </div>
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Currency Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $currency->name  }}">

            </div>
            <div class="form-group col-md-12">
                <label for="symbol" class="form-label">{{ __('Currency Symbol') }}</label>
                <input type="text" name="symbol" id="symbol" class="form-control" value="{{ $currency->symbol  }}">

            </div>
            <div class="form-group col-md-6">
                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.01" value="{{ $currency->exchange_rate  }}">
            </div>


        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
