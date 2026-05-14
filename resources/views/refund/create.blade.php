<form action="{{ url('refund') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="vender_id" class="form-label">Vendor</label>
                <select name="vender_id" id="vendor-select" class="form-control select2" required>
                    @foreach ($venders as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" id="date" class="form-control" required>

            </div>
            <div class="form-group col-md-6">
                <label for="bill_id" class="form-label">Bill</label>
                <select name="bill_id" id="bill-select" class="form-control select"></select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" name="amount" id="amount" class="form-control amount" required="required"
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

            <div class="form-group col-md-6 d-none" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency"
                        name="amount_in_currency" class="form-control">
                    <span class="input-group-text" id="currency_symbol_span"></span>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" id="category_id" class="form-control select" required="required">
                    @foreach ($categories as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">Account</label>
                <select name="account_id" id="account_id" class="form-control select" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>

            </div>
            {{--        <div class="form-group col-md-6"> --}}
            {{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }} --}}
            {{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }} --}}
            {{--        </div> --}}
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">Reference</label>
                <input type="text" name="reference" id="reference" class="form-control" value="">
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">Payment Receipt</label>
                <input type="file" name="add_receipt" id="files" class="form-control">
                <img id="image" class="mt-2" style="width:25%;" />
            </div>
            <div class="form-group  col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>

</form>


<script>
    document.getElementById('files').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
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
                url: '/get-bills/' + vendorId,
                type: 'GET',
                success: function(data) {
                    // Update the bill dropdown with the received data
                    var billSelect = $('#bill-select');
                    billSelect.empty();
                    // Add the "Select Bill" option
                    billSelect.append($('<option>', {
                        value: '',
                        text: 'Select Bill'
                    }));

                    $.each(data, function(key, value) {
                        console.log(value);
                        var formattedBillId =
                            "{{ Auth::user()->billNumberFormat('') }}" + value
                            .bill_id;
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
        if (selectedBillId) {
            $.ajax({
                url: '/get-bill-details/' + selectedBillId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Show extra currency fields
                    $('#currency_rate_group').removeClass('d-none');
                    $('#amount_currency_group').removeClass('d-none');

                    // Update amount in base currency
                    $('#amount').val(data.due_amount);

                    // Optionally fill amount_in_currency with the bill amount
                    $('#amount_in_currency').val(data.due_amount_currency.toFixed(2));
                    $('#currency_symbol_span').text(data.currency_symbol);

                    // Optional, clear it instead if needed
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        } else {
            $('#amount').val('');
            $('#currency_rate_group').addClass('d-none');
            $('#amount_currency_group').addClass('d-none');
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
