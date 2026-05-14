<form action="{{ route('payment.update', $payment->id) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="vender_id" class="form-label">{{ __('Vendor') }}</label>
                <input type="hidden" name="old_vender_id" id="old-vendor-id" value="{{ $payment->vender_id }}">
                <select name="vender_id" id="vendor-select" class="form-control select" disabled="disabled">
                    @foreach ($venders as $id => $vendor)
                        <option value="{{ $id }}" {{ $id == $payment->vender_id ? 'selected' : '' }}>
                            {{ $vendor }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">{{ __('Date') }}</label>
                <input type="date" name="date" class="form-control" required="required"
                    value="{{ $payment->date }}">

            </div>
            {{-- @if ($payment->bill_id)
                <div class="form-group col-md-6">
                    <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                    <select name="bill_id" class="form-control select" id="bill-select">
                        @if ($payment->bill_id)
                            <option value="{{ $payment->bill_id }}">
                                {{ Auth::user()->billNumberFormat(App\Models\Bill::where('id',$payment->bill_id)->first()->bill_id) }}</option>
                        @else
                            <option value="">Select Bill</option>
                        @endif
                        @foreach ($bills as $bill)
                            <option value="{{ $bill->id }}">
                                {{ $bill->id ? Auth::user()->billNumberFormat($bill->bill_id) : 'Select Bill' }}</option>
                        @endforeach
                    </select>

                </div>
            @else
                <div class="form-group col-md-6">
                    <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                    <select name="bill_id" id="bill-select" class="form-control select">
                        <option value="">Select Bill</option>
                        @foreach ($bills as $bill)
                            <option value="{{ $bill->id }}">
                                {{ $bill->id ? Auth::user()->billNumberFormat($bill->bill_id) : 'Select Bill' }}</option>
                        @endforeach
                    </select>
                </div>

            @endif --}}


            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control amount" required="required"
                    step="0.01"
                    value="{{ $payment->currency_id ? $payment->amount / $payment->currency_rate : $payment->amount }}">
            </div>
            <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                        <option value="{{ $id }}" @if ($payment->currency_id === $id) selected @endif>
                            {{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6 d-none" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control" value="{{ $payment->currency_rate }}">
            </div>

            {{-- <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency" class="form-control" value="{{ $payment->amount_in_currency  }}">
                    <span class="input-group-text" id="currency_symbol_span"></span>
                </div>
            </div> --}}

            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">{{ __('Category') }}</label>
                <select name="category_id" id="category_id" class="form-control select" required="required">
                    @foreach ($categories as $categoryId => $category)
                        <option value="{{ $categoryId }}" @if ($categoryId === $payment->category_id) selected @endif>
                            {{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="account_id" class="form-control select" required="required">
                    @foreach ($accounts as $accountId => $account)
                        <option value="{{ $accountId }}" @if ($accountId === $payment->account_id) selected @endif>
                            {{ $account }}</option>
                    @endforeach
                </select>
            </div>

            {{--        <div class="form-group col-md-6"> --}}
            {{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }} --}}
            {{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }} --}}
            {{--        </div> --}}
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                <input type="text" name="reference" id="reference" class="form-control"
                    value="{{ $payment->reference }}">
            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">{{ __('Payment Receipt') }}</label>
                <input type="file" name="add_receipt" id="files" class="form-control">
                <img id="image" class="mt-2" src="{{ asset('uploads/payment') . '/' . $payment->add_receipt }}"
                    style="width:25%;">
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
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>
<script>
    $(document).ready(function() {
        $('#vendor-select').on('change', function() {
            var vendorId = $(this).val();
            $('#amount').val("{{ $payment->amount }}");
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
    });

    // $('#bill-select').change(function() {
    //     var selectedBillId = $(this).val();
    //     console.log(selectedBillId);
    //     if (selectedBillId) {
    //         // Make an AJAX request to fetch bill details
    //         $.ajax({
    //             url: '/get-bill-details/' + selectedBillId,
    //             type: 'GET',
    //             dataType: 'json',
    //             success: function(data) {
    //                 // Update the amount input with the due amount
    //                 console.log(data);
    //                 $('#amount').val(data.due_amount);
    //             },
    //             error: function(xhr, status, error) {
    //                 console.error(xhr.responseText);
    //                 // Handle errors if needed
    //             }
    //         });
    //     } else {
    //         // Clear the amount input if no bill is selected
    //         $('#amount').val('');
    //     }
    // });
</script>
