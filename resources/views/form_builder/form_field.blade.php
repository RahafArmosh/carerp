<form action="{{ route('form.bind.store', $form->id) }}" method="POST">
    @csrf
    <div class="modal-body">
        <!-- form fields -->
        <div class="row">
            <div class="col-12 pb-3">
                <span
                    class="text-xs"><b>{{ __('It will auto convert from response on lead based on below setting. It will not convert old response.') }}</b></span>
            </div>
        </div>
        <div class="row px-2">
            <div class="col-4">
                <div class="form-group">
                    <label for="active" class="form-label">{{ __('Active') }}</label>
                </div>
            </div>
            <div class="col-8">
                <div class="d-flex radio-check">
                    <div class="form-check form-check-inline">
                        <input type="radio" id="on" value="1" name="is_lead_active"
                            class="form-check-input lead_radio" {{ $form->is_lead_active == 1 ? 'checked' : '' }}>
                        <label class="custom-control-label form-label" for="on">{{ __('On') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" id="off" value="0" name="is_lead_active"
                            class="form-check-input lead_radio" {{ $form->is_lead_active == 0 ? 'checked' : '' }}>
                        <label class="custom-control-label form-label" for="off">{{ __('Off') }}</label>
                    </div>
                </div>
            </div>
        </div>
        <div id="lead_activated" class="d-none">
            <!-- more form fields -->
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>


<script>
    $(document).ready(function() {
        var lead_active = {{ $form->is_lead_active }};
        if (lead_active == 1) {
            $('#lead_activated').removeClass('d-none');
        }
    });
    $(document).on('click', function() {
        $('.lead_radio').on('click', function() {
            var inputValue = $(this).attr("value");
            if (inputValue == 1) {
                $('#lead_activated').removeClass('d-none');
            } else {
                $('#lead_activated').addClass('d-none');
            }
            $('.lead_radio').removeAttr('checked');
            $(this).prop("checked", true);
        })
    });
</script>
