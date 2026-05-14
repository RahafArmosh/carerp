<form method="POST" action="{{ route('customerrefund.update', $payment->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="customer_id" class="form-label">Customer</label>
                <input type="hidden" name="old_customer_id" id="old-customer-id" value="{{ $payment->customer_id }}">
                <select name="customer_id" class="form-control select" id="vendor-select" disabled="disabled">
                    @foreach ($customers as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->customer_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required="required"
                    value="{{ $payment->date }}">

            </div>

            @if ($payment->invoice_id)
                <div class="form-group col-md-6">
                    <label for="invoice_id" class="form-label">Invoice</label>
                    <select name="invoice_id" class="form-control select" id="bill-select">
                        @if ($payment->invoice_id)
                            <option value="{{ $payment->invoice_id }}">
                                {{ Auth::user()->billNumberFormat($payment->invoice_id) }}</option>
                        @else
                            <option value="">Select Invoice</option>
                            @foreach ($invoices->prepend('Select Invoice', '')->pluck('id') as $id)
                                <option value="{{ $id }}">
                                    {{ $id ? Auth::user()->billNumberFormat($id) : 'Select Bill' }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            @else
                <div class="form-group col-md-6">
                    <label for="invoice_id" class="form-label">Invoice</label>
                    <select name="invoice_id" class="form-control select" id="bill-select">
                        <option value="">Select Invoice</option>
                        @foreach ($invoices->prepend('Select Invoice', '')->pluck('id') as $id)
                            <option value="{{ $id }}">
                                {{ $id ? Auth::user()->billNumberFormat($id) : 'Select Invoice' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group col-md-6">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" name="amount" class="form-control amount" required="required" step="0.01"
                    id="amount" value="{{ $payment->amount }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">Currency</label>
                <select name="currency_id" id="currency_id" class="form-control select" onchange="updateCurrencyRate()">
                    <option value="">{{ __('Select Currency') }}</option>
                    @foreach ($currencies as $key => $value)
                        <option value="{{ $key }}" data-rate="{{ $value['rate'] ?? 1 }}" 
                            @if ($key == $payment->currency_id) selected @endif>
                            {{ $value['name'] ?? $key }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="currency_rate" class="form-label">Currency Rate</label>
                <input type="number" name="currency_rate" id="currency_rate" class="form-control" step="0.0001" 
                    value="{{ $payment->currency_rate ?? 1 }}" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="amount_in_currency" class="form-label">Amount in Currency</label>
                <input type="number" name="amount_in_currency" id="amount_in_currency" class="form-control" step="0.01" 
                    value="{{ $payment->amount_in_currency ?? '' }}" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" class="form-control select" required="required">
                    @foreach ($categories as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->category_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">Account</label>
                <select name="account_id" id="account_id" class="form-control select" required>
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->account_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            {{--        <div class="form-group col-md-6"> --}}
            {{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }} --}}
            {{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }} --}}
            {{--        </div> --}}
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">Reference</label>
                <input type="text" name="reference" id="reference" class="form-control" value="{{ $payment->reference }}">

            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">Payment Receipt</label>
                <input type="file" name="add_receipt" id="files" class="form-control">
                <img id="image" class="mt-2"
                    src="{{ asset(Storage::url('uploads/payment')) . '/' . $payment->add_receipt }}"
                    style="width:25%;" />
            </div>
            <div class="form-group  col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ $payment->description }}</textarea>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>


    <script>
        document.getElementById('files').onchange = function() {
            var src = URL.createObjectURL(this.files[0])
            document.getElementById('image').src = src
        }

        function updateCurrencyRate() {
            var currencySelect = document.getElementById('currency_id');
            var currencyRateInput = document.getElementById('currency_rate');
            var amountInput = document.getElementById('amount');
            var amountInCurrencyInput = document.getElementById('amount_in_currency');
            var invoiceSelect = document.getElementById('bill-select');
            
            var selectedOption = currencySelect.options[currencySelect.selectedIndex];
            var selectedCurrencyId = selectedOption.value;
            var invoiceId = invoiceSelect.value;
            
            if (selectedCurrencyId && invoiceId) {
                // Make AJAX request to get exchange rate between selected currency and invoice currency
                $.ajax({
                    url: '/get-currency-rate/' + selectedCurrencyId + '/' + invoiceId,
                    type: 'GET',
                    success: function(data) {
                        var rate = data.rate || 1; // Rate between selected currency and AED
                        var amountRate = data.amount_rate || 1; // Rate for amount conversion
                        
                        currencyRateInput.value = rate;
                        
                        // Calculate amount in currency when amount changes
                        if (amountInput.value) {
                            amountInCurrencyInput.value = (parseFloat(amountInput.value) / parseFloat(amountRate)).toFixed(2);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching currency rate:', error);
                        // Fallback to default rate
                        var rate = selectedOption.getAttribute('data-rate') || 1;
                        currencyRateInput.value = rate;
                        
                        if (amountInput.value) {
                            amountInCurrencyInput.value = (parseFloat(amountInput.value) / parseFloat(rate)).toFixed(2);
                        }
                    }
                });
            } else {
                // No currency or invoice selected, use default rate
                var rate = selectedOption.getAttribute('data-rate') || 1;
                currencyRateInput.value = rate;
                
                if (amountInput.value) {
                    amountInCurrencyInput.value = (parseFloat(amountInput.value) / parseFloat(rate)).toFixed(2);
                }
            }
        }

        // Update amount in currency when amount changes
        document.getElementById('amount').addEventListener('input', function() {
            var currencySelect = document.getElementById('currency_id');
            var invoiceSelect = document.getElementById('bill-select');
            var amountInCurrencyInput = document.getElementById('amount_in_currency');
            
            var selectedCurrencyId = currencySelect.value;
            var invoiceId = invoiceSelect.value;
            
            if (selectedCurrencyId && invoiceId && this.value) {
                // Get the amount rate for conversion
                $.ajax({
                    url: '/get-currency-rate/' + selectedCurrencyId + '/' + invoiceId,
                    type: 'GET',
                    success: function(data) {
                        var amountRate = data.amount_rate || 1;
                        amountInCurrencyInput.value = (parseFloat(document.getElementById('amount').value) / parseFloat(amountRate)).toFixed(2);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching amount rate:', error);
                        // Fallback calculation
                        var currencyRate = document.getElementById('currency_rate').value;
                        if (currencyRate) {
                            amountInCurrencyInput.value = (parseFloat(document.getElementById('amount').value) / parseFloat(currencyRate)).toFixed(2);
                        }
                    }
                });
            }
        });

        // Initialize currency calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrencyRate();
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#vendor-select').on('change', function() {
                var vendorId = $(this).val();
                $('#amount').val("{{ $payment->amount }}");
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
                                .id;
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
                        
                        // Update currency rate when invoice changes
                        updateCurrencyRate();
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        // Handle errors if needed
                    }
                });
            } else {
                // Clear the amount input if no bill is selected
                $('#amount').val('');
                // Update currency rate when invoice is cleared
                updateCurrencyRate();
            }
        });
    </script>
