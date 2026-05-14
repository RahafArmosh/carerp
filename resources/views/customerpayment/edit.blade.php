<form action="{{ route('customerpayment.update', $payment->id) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                <input type="hidden" name="old_customer_id" id="old-customer-id" value="{{ $payment->customer_id }}">
                @if (isset($hasLinkedInvoices) && $hasLinkedInvoices)
                    <select name="customer_id" id="vendor-select" class="form-control select" disabled="disabled">
                        @foreach ($customers as $key => $value)
                            <option value="{{ $key }}" @if ($key == $payment->customer_id) selected @endif>{{ $value }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="customer_id" value="{{ $payment->customer_id }}">
                    <small class="text-danger d-block mt-1">
                        <i class="ti ti-info-circle"></i>
                        {{ __('Customer cannot be changed because this payment is linked to one or more invoices.') }}
                    </small>
                @else
                    <select name="customer_id" id="vendor-select" class="form-control select select2" required="required">
                        @foreach ($customers as $key => $value)
                            <option value="{{ $key }}" @if ($key == $payment->customer_id) selected @endif>{{ $value }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" id="date" class="form-control" required="required"
                    value="{{ $payment->date }}">
            </div>

            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control amount" required="required"
                    step="0.01" value="{{ $payment->amount - $payment->charge }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                        <option value="{{ $id }}" @if ($payment->currency_id === $id) selected @endif>{{ $currency }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div
                class="form-group col-md-6{{ $payment->currency_rate ? '' : ' d-none' }}"
                id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control" value="{{ $payment->currency_rate }}">
            </div>

            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                <select name="category_id" id="category_id" class="form-control select select2" required="required">
                    @foreach ($categories as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->category_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="account_id" class="form-control select select2" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->account_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="charge" class="form-label">{{ __('Charge Amount') }}</label>
                <input type="number" name="charge" class="form-control amount" required="required" step="0.01"
                    id="charge" value="{{ $payment->charge }}">
            </div>
            <div class="form-group col-md-6">
                <label for="bank_charge_account_id" class="form-label">{{ __('Charge Account') }}</label>
                <select name="bank_charge_account_id" id="bank_charge_account_id"
                    class="form-control select select2" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}" @if ($key === $payment->bank_charge_account_id) selected @endif>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" id="reference" class="form-control"
                    value="{{ $payment->reference }}">
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <input type="file" name="add_receipt" id="files" class="form-control">
                @if (!empty($payment->add_receipt))
                    <img id="image" class="mt-2" src="{{ asset('uploads/customer_payment/' . $payment->add_receipt) }}"
                        style="width:25%;">
                @else
                    <img id="image" class="mt-2" style="width:25%;">
                @endif
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
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
            $('#amount').val("{{ $payment->amount }}");
            $.ajax({
                url: '/get-invoices/' + vendorId,
                type: 'GET',
                success: function(data) {
                    var billSelect = $('#bill-select');
                    billSelect.empty();
                    billSelect.append($('<option>', {
                        value: '',
                        text: 'Select Invoice'
                    }));

                    $.each(data, function(key, value) {
                        var formattedBillId =
                            "{{ Auth::user()->invoiceNumberFormat('') }}" + value
                            .invoice_id;
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
        $(document).on('change', '#currency_id', function() {
            const currencyId = $(this).val();

            if (currencyId) {
                $.ajax({
                    url: '/currencies/' + currencyId + '/rate',
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
    });
</script>
