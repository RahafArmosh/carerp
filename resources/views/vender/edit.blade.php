<form action="{{ route('vender.update', $vender->id) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">

        <h6 class="sub-title">{{ __('Basic Info') }}</h6>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" value="{{ $vender->name ?? '' }}" class="form-control"
                        required>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="contact" class="form-label">Contact</label>
                    <input type="number" name="contact" value="{{ $vender->contact ?? '' }}" class="form-control"
                        required>
                </div>
            </div>
            <div class="form-group col-md-12 account">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select class="form-control select select2" required name="chart_account" id="chart_account"
                    data-placeholder="{{ __('Select Account') }}">
                    <option value="" selected disabled>{{ __('Select Account') }}
                    </option>
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}" {{ $vender->chart_account_id == $id ? 'selected' : '' }}>
                            {{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="tax_number" class="form-label">Tax Number</label>
                    <input type="text" name="tax_number" value="{{ $vender->tax_number ?? '' }}"
                        class="form-control">
                </div>
            </div>


            <div class="form-group">
                <label for="document">Document:</label>
                <input type="file" class="form-control" id="documents" name="documents[]" multiple>
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
                    <input type="text" name="billing_name" value="{{ $vender->billing_name ?? '' }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_phone" class="form-label">Phone</label>
                    <input type="text" name="billing_phone" value="{{ $vender->billing_phone ?? '' }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="billing_address" class="form-label">Address</label>
                    <textarea name="billing_address" class="form-control" rows="3">{{ $vender->billing_address ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_city" class="form-label">City</label>
                    <input type="text" name="billing_city" value="{{ $vender->billing_city ?? '' }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_state" class="form-label">State</label>
                    <input type="text" name="billing_state" value="{{ $vender->billing_state ?? '' }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_country" class="form-label">Country</label>
                    <input type="text" name="billing_country" value="{{ $vender->billing_country ?? '' }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_zip" class="form-label">Zip Code</label>
                    <input type="text" name="billing_zip" value="{{ $vender->billing_zip ?? '' }}"
                        class="form-control">
                </div>
            </div>
        </div>


        @php
            $shippingDisplay = App\Models\Utility::getValByName('shipping_display');
        @endphp

        @if ($shippingDisplay == 'on')
            <div class="col-md-12 text-end">
                <input type="button" id="billing_data" value="{{ __('Shipping Same As Billing') }}"
                    class="btn btn-primary">
            </div>
            <h6 class="sub-title">{{ __('Shipping Address') }}</h6>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_name" class="form-label">Name</label>
                        <input type="text" name="shipping_name" value="{{ $vender->shipping_name ?? '' }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_phone" class="form-label">Phone</label>
                        <input type="text" name="shipping_phone" value="{{ $vender->shipping_phone ?? '' }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="shipping_address" class="form-label">Address</label>
                        <textarea name="shipping_address" class="form-control" rows="3">{{ $vender->shipping_address ?? '' }}</textarea>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_city" class="form-label">City</label>
                        <input type="text" name="shipping_city" value="{{ $vender->shipping_city ?? '' }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_state" class="form-label">State</label>
                        <input type="text" name="shipping_state" value="{{ $vender->shipping_state ?? '' }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_country" class="form-label">Country</label>
                        <input type="text" name="shipping_country" value="{{ $vender->shipping_country ?? '' }}"
                            class="form-control">
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="form-group">
                        <label for="shipping_zip" class="form-label">Zip Code</label>
                        <input type="text" name="shipping_zip" value="{{ $vender->shipping_zip ?? '' }}"
                            class="form-control">
                    </div>
                </div>
            </div>
        @endif


    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>

</form>
