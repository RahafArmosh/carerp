<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<form method="POST" action="{{ url('/taxes') }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="name" , class="form-label">{{ __('Tax Rate Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required />
                @error('name')
                    <small class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="form-group col-md-6">
                <label for="rate" class="form-label">{{ __('Tax Rate %') }}</label>
                <input type="number" id="rate" name="rate" class="form-control" required step="0.01">

                @error('rate')
                    <small class="invalid-rate" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="form-group col-md-12 account ">
                <label for="chart_account" class="form-label">{{ __('Account') }}</label>
                <select class="form-control select select2" required name="chart_account" id="chart_account">
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
