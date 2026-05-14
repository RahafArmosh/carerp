<form action="{{ route('customer.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">

        <h6 class="sub-title">{{ __('Basic Info') }}</h6>
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" id="name" name="name" class="form-control" required
                        value="{{ $customer->name }}">

                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="contact" class="form-label">{{ __('Contact') }}</label>
                    <input type="number" id="contact" name="contact" class="form-control" required
                        value="{{ $customer->contact }}">

                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input type="text" id="email" name="email" class="form-control"
                        value="{{ $customer->email }}">

                </div>
            </div>
            <div class="form-group col-md-12 account">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select class="form-control select select2" required name="chart_account" id="chart_account"
                    data-placeholder="{{ __('Select Account') }}">
                    <option value="" selected disabled>{{ __('Select Account') }}
                    </option>
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}"
                            {{ $customer->chart_account_id == $id ? 'selected' : '' }}>{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="tax_number" class="form-label">{{ __('Tax Number') }}</label>
                    <input type="text" id="tax_number" name="tax_number" class="form-control"
                        value="{{ $customer->tax_number }}">
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="customer_trn_no" class="form-label">{{ __('Customer TRN No') }}</label>
                    <input type="text" id="customer_trn_no" name="customer_trn_no" class="form-control"
                        value="{{ $customer->customer_trn_no ?? '' }}" placeholder="{{ __('Optional') }}">
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
                    <label for="billing_name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" id="billing_name" name="billing_name" class="form-control"
                        value="{{ $customer->billing_name }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_phone" class="form-label">{{ __('Phone') }}</label>
                    <input type="text" id="billing_phone" name="billing_phone" class="form-control"
                        value="{{ $customer->billing_phone }}">

                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="billing_address" class="form-label">{{ __('Address') }}</label>
                    <textarea id="billing_address" name="billing_address" class="form-control" rows="3"> {{ $customer->billing_address }} </textarea>

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_city" class="form-label">{{ __('City') }}</label>
                    <input type="text" id="billing_city" name="billing_city" class="form-control"
                        value="{{ $customer->billing_city }}">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_state" class="form-label">{{ __('State') }}</label>
                    <input type="text" id="billing_state" name="billing_state" class="form-control"
                        value="{{ $customer->billing_state }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_country" class="form-label">{{ __('Country') }}</label>
                    <input type="text" id="billing_country" name="billing_country" class="form-control"
                        value="{{ $customer->billing_country }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_zip" class="form-label">{{ __('Zip Code') }}</label>
                    <input type="text" id="billing_zip" name="billing_zip" class="form-control"
                        value="{{ $customer->billing_zip }}">

                </div>
            </div>
        </div>


        <?php if (App\Models\Utility::getValByName('shipping_display') == 'on'): ?>
        <div class="col-md-12 text-end">
            <input type="button" id="billing_data" value="<?php echo e(__('Shipping Same As Billing')); ?>" class="btn btn-primary">
        </div>
        <h6 class="sub-title"><?php echo e(__('Shipping Address')); ?></h6>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_name" class="form-label"><?php echo e(__('Name')); ?></label>
                    <input type="text" id="shipping_name" name="shipping_name" class="form-control"
                        value="{{ $customer->shipping_name }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_phone" class="form-label"><?php echo e(__('Phone')); ?></label>
                    <input type="text" id="shipping_phone" name="shipping_phone" class="form-control"
                        value="{{ $customer->shipping_phone }}">

                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="shipping_address" class="form-label"><?php echo e(__('Address')); ?></label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3">{{ $customer->shipping_address }}</textarea>

                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_city" class="form-label"><?php echo e(__('City')); ?></label>
                    <input type="text" id="shipping_city" name="shipping_city" class="form-control"
                        value="{{ $customer->shipping_city }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_state" class="form-label"><?php echo e(__('State')); ?></label>
                    <input type="text" id="shipping_state" name="shipping_state" class="form-control"
                        value="{{ $customer->shipping_state }}">

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_country" class="form-label"><?php echo e(__('Country')); ?></label>
                    <input type="text" id="shipping_country" name="shipping_country" class="form-control"
                        value="{{ $customer->shipping_country }}">

                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_zip" class="form-label"><?php echo e(__('Zip Code')); ?></label>
                    <input type="text" id="shipping_zip" name="shipping_zip" class="form-control"
                        value="{{ $customer->shipping_zip }}">
                </div>
            </div>

        </div>
        <?php endif; ?>


    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
