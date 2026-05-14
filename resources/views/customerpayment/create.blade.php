<form action="{{ route('customerpayment.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                <select name="customer_id" class="form-control select select2" required="required" id="vendor-select">
                    @foreach ($customers as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" id="date" name="date" class="form-control" required="required">
            </div>
            {{-- <div class="form-group col-md-6">
                <label for="invoice_id" class="form-label">{{ __('Invoice') }}</label>
                <select name="invoice_id" id="bill-select" class="form-control select">

                </select>
            </div> --}}

            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" id="amount" name="amount" class="form-control amount" required="required"
                    step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                        <option value="{{ $id }}">{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6 d-none" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control">
            </div>

            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                <select id="category_id" name="category_id" class="form-control select select2" required="required">
                    @foreach ($categories as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select id="account_id" name="account_id" class="form-control select select2" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="charge" class="form-label">{{ __('Charge Amount') }}</label>
                <input type="number" name="charge" class="form-control amount" required="required" step="0.01"
                    id="charge">
            </div>
            <div class="form-group col-md-6">
                <label for="bank_charge_account_id" class="form-label">{{ __('Charge Account') }}</label>
                <select name="bank_charge_account_id" id="bank_charge_account_id" class="form-control select select2"
                    required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" id="reference" name="reference" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <input type="file" id="add_receipt" name="add_receipt" class="form-control">
                <img id="image" class="mt-2" style="width:25%;" />
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>


<script>
    document.getElementById('add_receipt').onchange = function() {
        if (this.files && this.files[0]) {
            var src = URL.createObjectURL(this.files[0])
            document.getElementById('image').src = src
        }
    }
</script>
<script>
    $(document).ready(function() {
        $('#vendor-select').on('change', function() {
            var vendorId = $(this).val();
            console.log('Selected Vendor ID:', vendorId);
            $('#amount').val('');
            // Make an AJAX request to fetch bills for the selected vendor
            $.ajax({
                url: '/get-invoices/' + vendorId,
                type: 'GET',
                success: function(data) {
                    // Update the bill dropdown with the received data
                    var billSelect = $('#bill-select');
                    billSelect.empty();
                    // Add the "Select Bill" option
                    billSelect.append($('<option>', {
                        value: '',
                        text: 'Select Invoice'
                    }));

                    $.each(data, function(key, value) {
                        console.log(value);
                        var formattedBillId =
                            "{{ Auth::user()->invoiceNumberFormat('') }}" + value
                            .invoice_id;
                        console.log("formattedBillId" + formattedBillId);
                        billSelect.append($('<option>', {
                            value: value.id,
                            text: formattedBillId
                        }));
                    });
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });

    $('#bill-select').change(function() {
        var selectedBillId = $(this).val();
        console.log(selectedBillId);
        if (selectedBillId) {
            // Make an AJAX request to fetch bill details
            $.ajax({
                url: '/get-invoice-details/' + selectedBillId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Update the amount input with the due amount
                    console.log(data);
                    $('#amount').val(data.due_amount);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    // Handle errors if needed
                }
            });
        } else {
            // Clear the amount input if no bill is selected
            $('#amount').val('');
        }
    });
    $(document).on('change', '#currency_id', function() {
        const currencyId = $(this).val();

        if (currencyId) {
            $.ajax({
                url: '/currencies/' + currencyId + '/rate', // adjust route if needed
                method: 'GET',
                success: function(data) {
                    if (data && data.rate !== undefined) {
                        $('#currency_rate').val(data.rate);
                        $('#currency_rate_group').removeClass('d-none');
                    } else {
                        $('#currency_rate').val('');
                        $('#currency_rate_group').addClass('d-none');
                    }
                },
                error: function() {
                    alert('Unable to fetch exchange rate.');
                    $('#currency_rate').val('');
                    $('#currency_rate_group').addClass('d-none');
                }
            });
        } else {
            $('#currency_rate').val('');
            $('#currency_rate_group').addClass('d-none');
        }
    });
</script>
