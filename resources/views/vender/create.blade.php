<form action="{{ url('vender') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">

        <h6 class="sub-title">{{ __('Basic Info') }}</h6>
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>


                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="number" name="contact" id="contact" class="form-control" required>


                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control">


                </div>
            </div>


            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="tax_number" class="form-label">Tax Number</label>
                    <input type="text" name="tax_number" id="tax_number" class="form-control">


                </div>

            </div>
            <div class="form-group col-md-12 account">
                <label for="chart_account_id" class="form-label">Account</label>
                <select class="form-control select select2" name="chart_account" id="chart_account">
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="document">Document:</label>
                <input type="file" class="form-control" id="documents" name="documents[]" multiple>
            </div>
            <div class="col">
                <div class="form-check form-check-inline form-group">
                    <input type="radio" id="customer_radio" value="customer" name="customer_radio"
                        class="form-check-input" onclick="uncheck1()">
                    <label class="form-check-label" for="customer">{{ __('Customer') }}</label>
                </div>
            </div>
            <div class="form-group col-md-12" style="display: none;" id="customer_account_div">
                <label for="chart_account_id" class="form-label">{{ __('Customer Account') }}</label>
                <select class="form-control select select2" name="chart_account_customer" id="chart_account_customer">
                    @foreach ($chart_accounts_customer as $id => $codeName)
                        <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            @if (!$customFields->isEmpty())
                <div class="col-lg-4 col-md-4 col-sm-6">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif
        </div>
        <h6 class="sub-title">{{ __('Billing Address') }}</h6>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_name" class="form-label">Name</label>
                    <input type="text" id="billing_name" name="billing_name" class="form-control">


                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_phone" class="form-label">Phone</label>
                    <input type="text" id="billing_phone" name="billing_phone" class="form-control">


                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="billing_address" class="form-label">Address</label>
                    <textarea id="billing_address" name="billing_address" class="form-control" rows="3"></textarea>

                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_city" class="form-label">City</label>
                    <input type="text" id="billing_city" name="billing_city" class="form-control">

                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_state" class="form-label">State</label>
                    <input type="text" id="billing_state" name="billing_state" class="form-control">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_country" class="form-label">Country</label>
                    <input type="text" id="billing_country" name="billing_country" class="form-control">


                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_zip" class="form-label">Zip Code</label>
                    <input type="text" id="billing_zip" name="billing_zip" class="form-control">


                </div>
            </div>

        </div>

        @if (App\Models\Utility::getValByName('shipping_display') == 'on')
            <div class="col-md-12 text-end">
                <input type="button" id="billing_data" value="{{ __('Shipping Same As Billing') }}"
                    class="btn btn-primary">
            </div>
            <h6 class="sub-title">{{ __('Shipping Address') }}</h6>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_name" class="form-label">Name</label>
                        <input type="text" id="shipping_name" name="shipping_name" class="form-control">


                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_phone" class="form-label">Phone</label>
                        <input type="text" id="shipping_phone" name="shipping_phone" class="form-control">


                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="shipping_address" class="form-label">Address</label>
                        <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3"></textarea>

                    </div>
                </div>


                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_city" class="form-label">City</label>
                        <input type="text" id="shipping_city" name="shipping_city" class="form-control">


                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_state" class="form-label">State</label>
                        <input type="text" id="shipping_state" name="shipping_state" class="form-control">


                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_country" class="form-label">Country</label>
                        <input type="text" id="shipping_country" name="shipping_country" class="form-control">


                    </div>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_zip" class="form-label">Zip Code</label>
                        <input type="text" id="shipping_zip" name="shipping_zip" class="form-control">
                    </div>
                </div>

            </div>
        @endif

    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>
<script>
    var checked = false;

    function uncheck1() {
        var customerAccountDiv = document.getElementById('customer_account_div');
        if (checked) {
            document.getElementById("customer_radio").checked = false;
            customerAccountDiv.style.display = 'none';
            checked = false;
            return;
        }
        else{
            customerAccountDiv.style.display = 'block';
        }
        checked = true;
    }
</script>
