<div class="card bg-none card-box">
    <form method="POST" action="{{ route('vender.bill.send.mail', $bill_id) }}">
        @csrf
        <div class="row">
            <div class="form-group col-md-12">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
                @error('email')
                    <span class="invalid-email" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="col-md-12 px-0">
            <input type="submit" value="{{ __('Create') }}" class="btn-create badge-blue">
            <input type="button" value="{{ __('Cancel') }}" class="btn-create bg-gray" data-dismiss="modal">
        </div>
    </form>

</div>
