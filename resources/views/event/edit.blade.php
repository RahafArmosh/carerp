<form action="{{ route('event.update', $event->id) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['event']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Event Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control"
                        placeholder="{{ __('Enter Event Title') }}" value="{{ old('title') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date" class="form-label">{{ __('Event start Date') }}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                        value="{{ old('start_date') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date" class="form-label">{{ __('Event End Date') }}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                        value="{{ old('end_date') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="color" class="col-form-label d-block mb-3">{{ __('Event Select Color') }}</label>
                    <div class=" btn-group-toggle btn-group-colors event-tag" data-toggle="buttons">
                        <label
                            class="btn bg-info p-3 {{ $event->color == 'event-info'
                                ? 'custom_color_radio_button
                                                                                                                                                                                                                                                                                                '
                                : '' }} "><input
                                type="radio" name="color" class="d-none" value="event-info"
                                {{ $event->color == 'event-info' ? 'checked' : '' }}></label>

                        <label
                            class="btn bg-warning p-3 {{ $event->color == 'event-warning' ? 'custom_color_radio_button' : '' }}"><input
                                type="radio" class="d-none" name="color" value="event-warning"
                                {{ $event->color == 'event-warning' ? 'checked' : '' }}></label>

                        <label
                            class="btn bg-danger p-3 {{ $event->color == 'event-danger' ? 'custom_color_radio_button' : '' }}"><input
                                type="radio" name="color" class="d-none" value="event-danger"
                                {{ $event->color == 'event-danger' ? 'checked' : '' }}></label>


                        <label
                            class="btn bg-primary p-3 {{ $event->color == 'event-success' ? 'custom_color_radio_button' : '' }}"><input
                                type="radio" class="d-none" name="color" value="event-success"
                                {{ $event->color == 'event-success' ? 'checked' : '' }}></label>

                        <label
                            class="btn p-3 {{ $event->color == 'event-primary' ? 'custom_color_radio_button' : '' }}"
                            style="background-color: #51459d !important"><input type="radio" class="d-none"
                                name="color" value="event-primary"
                                {{ $event->color == 'event-primary' ? 'checked' : '' }}></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Event Description') }}</label>
                    <textarea id="description" name="description" class="form-control" placeholder="{{ __('Enter Event Description') }}"></textarea>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>

@push('script-page')
    <script>
        if ($(".datepicker").length) {
            $('.datepicker').daterangepicker({
                singleDatePicker: true,
                format: 'yyyy-mm-dd',
                locale: date_picker_locale,
            });
        }
    </script>
@endpush
