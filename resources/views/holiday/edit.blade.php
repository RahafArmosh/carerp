<form action="{{ route('holiday.update', $holiday->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body">
        <!-- start for ai module -->
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['holiday']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        <!-- end for ai module -->
        <div class="row">
            <div class="form-group col-md-12">
                <label for="occasion" class="form-label">Occasion</label>
                <input type="text" name="occasion" class="form-control" value="{{ $holiday->occasion }}">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="date" class="form-label">Start Date</label>
                <input type="date" name="date" class="form-control" value="{{ $holiday->date }}">
            </div>
            <div class="form-group col-md-6">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $holiday->end_date }}">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>


<script>
    if ($(".datepicker").length) {
        $('.datepicker').daterangepicker({
            singleDatePicker: true,
            format: 'yyyy-mm-dd',
            locale: date_picker_locale,
        });
    }
</script>
