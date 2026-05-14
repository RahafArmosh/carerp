<style>
    /* Base switch size */
    .form-check-input.big-switch {
        width: 3rem;
        height: 1.5rem;
        background-color: #ccc;
        border-color: #ccc;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    /* Make the circle gray by default */
    .form-check-input.big-switch::before {
        background-color: #888 !important; /* Gray circle */
        transition: background-color 0.3s ease;
    }

    /* When checked: blue background + white circle */
    .form-check-input.big-switch:checked {
        background-color: #00ff37 !important;
        border-color: #00ff37 !important;
    }

    .form-check-input.big-switch:checked::before {
        background-color: #fff !important; /* White circle */
    }

    /* Remove purple focus shadow and styling */
    .form-check-input.big-switch:focus {
        box-shadow: none !important;
    }
</style>
<form method="POST" action="{{ route('leads.convert.to.deal', $lead->id) }}">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-6 form-group">
                <label for="name" class="form-label">{{ __('Deal Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $lead->subject }}"
                    required>
            </div>
            <div class="col-6 form-group">
                <label for="price" class="form-label">{{ __('Price') }}</label>
                <input type="number" name="price" id="price" class="form-control" min="0" value="0">
            </div>
            <div class="col-sm-12 col-md-12">
                <div class="d-flex radio-check">
                    <div class="form-check form-check-inline form-group col-md-6">
                        <input type="radio" name="client_check" value="new" id="new_client"
                            class="form-check-input" @if (empty($exist_client)) checked @endif>
                        <label class="form-check-label form-label" for="new_client">{{ __('New Client') }}</label>
                    </div>
                    <div class="form-check form-check-inline form-group col-md-6">
                        <input type="radio" name="client_check" value="exist" id="existing_client"
                            class="form-check-input" @if (!empty($exist_client)) checked @endif>
                        <label class="form-check-label form-label"
                            for="existing_client">{{ __('Existing Client') }}</label>
                    </div>
                </div>
            </div>
            <div class="col-6 exist_client d-none form-group">
                <label for="clients" class="form-label">{{ __('Client') }}</label>
                <select name="clients" id="clients" class="form-control select">
                    <option value="">{{ __('Select Client') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->email }}" @if ($lead->email == $client->email) selected @endif>
                            {{ $client->name }} ({{ $client->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 new_client form-group">
                <label for="client_name" class="form-label">{{ __('Client Name') }}</label>
                <input type="text" name="client_name" id="client_name" class="form-control"
                    value="{{ $lead->name }}" required>
            </div>
            <div class="col-6 new_client form-group">
                <label for="client_email" class="form-label">{{ __('Client Email') }}</label>
                <input type="email" name="client_email" id="client_email" class="form-control"
                    value="{{ $lead->email }}" required>
            </div>
            <div class="col-6 new_client form-group">
                <label for="client_password" class="form-label">{{ __('Client Password') }}</label>
                <input type="text" name="client_password" id="client_password" class="form-control" required>
            </div>
        </div>
        <div class="col-12 mb-4">
            <div class="form-check form-switch d-flex align-items-center" style="gap: 1rem;">
                <input class="form-check-input big-switch" type="checkbox" 
                       name="is_transfer[]" value="stage" id="is_transfer_stage">
                <label class="form-check-label fs-4 fw-bold" for="is_transfer_stage">
                    {{ __('No Stock') }}
                </label>
            </div>
        </div>    
        <div class="col-12 mb-4">
            <div class="form-check form-switch d-flex align-items-center" style="gap: 1rem;">
                <input class="form-check-input big-switch" type="checkbox" 
                       name="is_transfer[]" value="price" id="is_transfer_price">
                <label class="form-check-label fs-4 fw-bold" for="is_transfer_price">
                    {{ __('High Price') }}
                </label>
            </div>
        </div>     
        <div class="row px-3 text-sm">
            <div class="col-12 pl-0 pb-2 font-bold text-dark">{{ __('Copy To') }}</div>
            <div class="col-3 custom-control custom-checkbox form-switch">
                <input type="checkbox" name="is_transfer[]" value="products" id="is_transfer_products"
                    class="form-check-input" checked>
                <label class="custom-control-label form-label" for="is_transfer_products">{{ __('Products') }}</label>
            </div>
            <div class="col-3 custom-control custom-checkbox form-switch">
                <input type="checkbox" name="is_transfer[]" value="sources" id="is_transfer_sources"
                    class="form-check-input" checked>
                <label class="custom-control-label form-label" for="is_transfer_sources">{{ __('Sources') }}</label>
            </div>
            <div class="col-3 custom-control custom-checkbox form-switch">
                <input type="checkbox" name="is_transfer[]" value="files" id="is_transfer_files"
                    class="form-check-input" checked>
                <label class="custom-control-label form-label" for="is_transfer_files">{{ __('Files') }}</label>
            </div>
            <div class="col-3 custom-control custom-checkbox form-switch">
                <input type="checkbox" name="is_transfer[]" value="discussion" id="is_transfer_discussion"
                    class="form-check-input" checked>
                <label class="custom-control-label form-label"
                    for="is_transfer_discussion">{{ __('Discussion') }}</label>
            </div>
            <div class="col-3 custom-control custom-checkbox form-switch">
                <input type="checkbox" name="is_transfer[]" value="emails" id="is_transfer_emails"
                    class="form-check-input" checked>
                <label class="custom-control-label form-label" for="is_transfer_emails">{{ __('Emails') }}</label>
            </div>

        </div>
    </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>

</form>

<script>
    $(document).ready(function() {
        var is_client = $("input[name='client_check']:checked").val();
        $("input[name='client_check']").click(function() {
            is_client = $(this).val();

            if (is_client == "exist") {
                $('.exist_client').removeClass('d-none');
                $('#client_name').removeAttr('required');
                $('#client_email').removeAttr('required');
                $('#client_password').removeAttr('required');
                $('.new_client').addClass('d-none');
            } else {
                $('.new_client').removeClass('d-none');
                $('#client_name').attr('required', 'required');
                $('#client_email').attr('required', 'required');
                $('#client_password').attr('required', 'required');
                $('.exist_client').addClass('d-none');
            }
        });
        if (is_client == "exist") {
            $('.exist_client').removeClass('d-none');
            $('#client_name').removeAttr('required');
            $('#client_email').removeAttr('required');
            $('#client_password').removeAttr('required');
            $('.new_client').addClass('d-none');
        } else {
            $('.new_client').removeClass('d-none');
            $('#client_name').attr('required', 'required');
            $('#client_email').attr('required', 'required');
            $('#client_password').attr('required', 'required');
            $('.exist_client').addClass('d-none');
        }

        // Checkbox toggle logic: only one of "No Stock" or "High Price"
        const stageCheckbox = document.getElementById('is_transfer_stage');
        const priceCheckbox = document.getElementById('is_transfer_price');

        stageCheckbox.addEventListener('change', function () {
            if (this.checked) {
                priceCheckbox.checked = false;
            }
        });

        priceCheckbox.addEventListener('change', function () {
            if (this.checked) {
                stageCheckbox.checked = false;
            }
        });
    });
</script>
