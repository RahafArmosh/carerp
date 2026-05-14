<form action="{{ route('refund.update', $refund->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="vender_id" class="form-label">Vendor</label>
                <input type="hidden" name="old_vender_id" value="{{ $refund->vender_id }}" id="old-vendor-id">
                <select name="vender_id" class="form-control select" id="vendor-select" disabled>
                    @foreach ($venders as $id => $vender)
                        <option value="{{ $id }}" @if ($id === $refund->vender_id) selected @endif>{{ $vender }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required value="{{ $refund->date }}">

            </div>
            @if ($refund->bill_id)
                <div class="form-group col-md-6">
                    <label for="bill_id" class="form-label">Bill</label>
                    <select name="bill_id" class="form-control select" id="bill-select">
                        <option value="">Select Bill</option>
                        @foreach ($bills as $id => $bill)
                            <option value="{{ $bill->id }}" @if ($refund->bill_id == $bill->id) selected @endif>
                                {{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-group col-md-6">
                    <label for="bill_id" class="form-label">Bill</label>
                    <select name="bill_id" class="form-control select" id="bill-select">
                        <option value="">Select Bill</option>
                        @foreach ($bills as $id => $bill)
                            <option value="{{ $bill->id }}">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif


             <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control amount" required="required"
                    step="0.01" value="{{ $refund->currency_id ? $refund->amount / $refund->currency_rate : $refund->amount }}">
            </div>
             <div class="form-group col-md-6">
                <label for="currency_id" class="form-label">{{ __('Payment Currency') }}</label>
                <select name="currency_id" id="currency_id" class="form-control select select2">
                    @foreach ($currencies as $id => $currency)
                    <option value="{{ $id }}" @if( $refund->currency_id === $id ) selected @endif>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6" id="currency_rate_group">
                <label for="currency_rate" class="form-label">{{ __('Currency Rate') }}</label>
                <input type="number" step="0.0001" min="0" id="currency_rate" name="currency_rate"
                    class="form-control" value="{{ $refund->currency_rate  }}">
            </div>

            <div class="form-group col-md-6" id="amount_currency_group">
                <label for="amount_in_currency" class="form-label">{{ __('Amount in Bill Currency') }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" id="amount_in_currency" name="amount_in_currency" class="form-control" value="{{ $refund->amount_in_currency  }}">
                    <span class="input-group-text" id="currency_symbol_span"></span>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" id="category_id" class="form-control select" required="required">
                    @foreach ($categories as $key => $value)
                        <option value="{{ $key }}" @if ($key === $refund->category_id) selected @endif>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="account_id" class="form-label">Account</label>
                <select name="account_id" id="account_id" class="form-control select" required="required">
                    @foreach ($accounts as $key => $value)
                        <option value="{{ $key }}" @if ($key === $refund->account_id) selected @endif
                            >{{ $value }}</option>
                    @endforeach
                </select>

            </div>
            {{--        <div class="form-group col-md-6"> --}}
            {{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }} --}}
            {{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }} --}}
            {{--        </div> --}}
            <div class="form-group col-md-6">
                <label for="reference" class="form-label">Reference</label>
                <input type="text" name="reference" id="reference" class="form-control" value="{{ $refund->reference }}">

            </div>
            <div class="form-group col-md-6">
                <label for="add_receipt" class="form-label">Refund Receipt</label>
                <input type="file" name="add_receipt" id="files" class="form-control">
                <img id="image" class="mt-2"
                    src="{{ asset(Storage::url('uploads/refund')) . '/' . $refund->add_receipt }}"
                    style="width:25%;" />
            </div>
            <div class="form-group  col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ $refund->description }}</textarea>
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
            $('#amount').val("{{ $refund->amount }}");
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
                            "{{ Auth::user()->billNumberFormat('') }}" + value.id;
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
                url: '/get-bill-details/' + selectedBillId,
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
</script>
