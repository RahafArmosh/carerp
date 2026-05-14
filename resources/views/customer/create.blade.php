<form action="{{ url('customer') }}" method="post" enctype="multipart/form-data" id="customerCreateForm">
    @csrf
    @if(isset($fromPos) && $fromPos)
        <input type="hidden" name="from_pos" value="1">
    @endif
    <div class="modal-body">

        <h6 class="sub-title">{{ __('Basic Info') }}</h6>
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="contact" class="form-label">{{ __('Contact') }}</label>
                    <input type="number" id="contact" name="contact" class="form-control" required>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
            </div>

            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="tax_number" class="form-label">{{ __('Tax Number') }}</label>
                    <input type="text" id="tax_number" name="tax_number" class="form-control">
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="form-group">
                    <label for="customer_trn_no" class="form-label">{{ __('Customer TRN No') }}</label>
                    <input type="text" id="customer_trn_no" name="customer_trn_no" class="form-control" placeholder="{{ __('Optional') }}">
                </div>
            </div>
            <div class="form-group col-md-12 account">
                <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                <select class="form-control select select2" name="chart_account" id="chart_account">
                    @foreach ($chart_accounts as $id => $codeName)
                        <option value="{{ $id }}">{{ $codeName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="documents">Document:</label>
                <input type="file" id="documents" name="documents[]" class="form-control" multiple>
            </div>
            <div class="col">
                <div class="form-check form-check-inline form-group">
                    <input type="radio" id="vendor_radio" value="vendor" name="vendor_radio" class="form-check-input"
                        onclick="uncheck1()">
                    <label class="form-check-label" for="vendor">{{ __('Vendor') }}</label>
                </div>
            </div>
            <div class="form-group col-md-12 account" style="display: none;" id="vendor_account_div">
                <label for="chart_account_id" class="form-label">{{ __('Vendor Account') }}</label>
                <select class="form-control select select2" name="chart_account_vendor" id="chart_account_vendor">
                    @foreach ($chart_accounts_vendor as $id => $codeName)
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
                    <label for="billing_name" class="form-label">{{__('Name')}}</label>
                    <input type="text" id="billing_name" name="billing_name" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_phone" class="form-label">{{__('Phone')}}</label>
                    <input type="text" id="billing_phone" name="billing_phone" class="form-control">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="billing_address" class="form-label">{{__('Address')}}</label>
                    <textarea id="billing_address" name="billing_address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_city" class="form-label">{{__('City')}}</label>
                    <input type="text" id="billing_city" name="billing_city" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_state" class="form-label">{{__('State')}}</label>
                    <input type="text" id="billing_state" name="billing_state" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_country" class="form-label">{{__('Country')}}</label>
                    <input type="text" id="billing_country" name="billing_country" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="billing_zip" class="form-label">{{__('Zip Code')}}</label>
                    <input type="text" id="billing_zip" name="billing_zip" class="form-control">
                </div>
            </div>
        </div>


        @if (App\Models\Utility::getValByName('shipping_display') == 'on')
        <div class="col-md-12 text-end">
            <input type="button" id="billing_data" value="{{ __('Shipping Same As Billing') }}" class="btn btn-primary">
        </div>
        <h6 class="sub-title">{{ __('Shipping Address') }}</h6>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" id="shipping_name" name="shipping_name" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_phone" class="form-label">{{ __('Phone') }}</label>
                    <input type="text" id="shipping_phone" name="shipping_phone" class="form-control">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="shipping_address" class="form-label">{{ __('Address') }}</label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_city" class="form-label">{{ __('City') }}</label>
                    <input type="text" id="shipping_city" name="shipping_city" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_state" class="form-label">{{ __('State') }}</label>
                    <input type="text" id="shipping_state" name="shipping_state" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_country" class="form-label">{{ __('Country') }}</label>
                    <input type="text" id="shipping_country" name="shipping_country" class="form-control">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    <label for="shipping_zip" class="form-label">{{ __('Zip Code') }}</label>
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
        var accountSelect = document.getElementById('chart_account_vendor');
        var customerAccountDiv = document.getElementById('vendor_account_div');
        if (checked) {
            document.getElementById("vendor_radio").checked = false;
            customerAccountDiv.style.display = 'none';
            checked = false;
            return;
        }
        else{
            customerAccountDiv.style.display = 'block';
        }
        checked = true;
    }

    // Handle form submission for POS context
    @if(isset($fromPos) && $fromPos)
    $(document).ready(function() {
        $('#customerCreateForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = new FormData(this);
            var submitBtn = form.find('input[type="submit"]');
            var originalText = submitBtn.val();
            
            // Disable submit button
            submitBtn.prop('disabled', true).val('{{ __("Creating...") }}');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    // Close modal
                    $('#commonModal').modal('hide');
                    
                    // Show success message
                    if (typeof show_toastr !== 'undefined') {
                        show_toastr('success', response.message || '{{ __("Customer created successfully") }}', 'success');
                    } else {
                        alert(response.message || '{{ __("Customer created successfully") }}');
                    }
                    
                    // Auto-select customer in POS
                    if (response.customer && typeof response.customer === 'object') {
                        var customer = response.customer;
                        var customerSearchInput = $('#customer_search');
                        var customerIdInput = $('#customer_id');
                        var customerNameHidden = $('#vc_name_hidden');
                        
                        if (customerSearchInput.length && customerIdInput.length) {
                            // Set the customer search input value
                            customerSearchInput.val(customer.name);
                            
                            // Set the hidden customer ID
                            customerIdInput.val(customer.id);
                            
                            // Set the hidden customer name
                            if (customerNameHidden.length) {
                                customerNameHidden.val(customer.name);
                            }
                            
                            // Trigger change event if needed
                            customerSearchInput.trigger('change');
                            customerIdInput.trigger('change');
                            
                            // Hide search results if visible
                            $('#customer_search_results').hide();
                        }
                    }
                },
                error: function(xhr) {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).val(originalText);
                    
                    var errorMessage = '{{ __("An error occurred") }}';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var firstError = Object.values(errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                    
                    if (typeof show_toastr !== 'undefined') {
                        show_toastr('error', errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });
    });
    @endif
</script>
