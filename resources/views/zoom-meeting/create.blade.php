<form action="{{ route('zoom-meeting.store') }}" id="store-user" method="post">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['zoom meeting']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label for="title" class="form-label">{{ __('Title') }}</label>
                <input type="text" name="title" id="title" class="form-control"
                    placeholder="{{ __('Enter Meeting Title') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="projects" class="form-label">{{ __('Project') }}</label>
                <select name="project_id" id="project_select" class="form-control select project_select"
                    data-toggle="select">
                    @foreach ($projects as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="user_id" class="form-label">{{ __('Users') }}</label>
                <div id="user_div">
                    <select name="user_id[]" id="user_id" class="form-control select employee_select">
                        <option value="">{{ __('Select User') }}</option>
                    </select>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="start_date" class="form-label">{{ __('Start Date / Time') }}</label>
                <input type="datetime-local" name="start_date" id="start_date" class="form-control date"
                    placeholder="{{ __('Select Date/Time') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="duration" class="form-label">{{ __('Duration') }}</label>
                <input type="number" name="duration" id="duration" class="form-control"
                    placeholder="{{ __('Enter Duration') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="password" class="form-label">{{ __('Password ( Optional )') }}</label>
                <input type="password" name="password" id="password" class="form-control"
                    placeholder="{{ __('Enter Password') }}">
            </div>
            @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
                <div class="form-group col-md-6">
                    <label class="form-check-label"
                        for="switch-shadow">{{ __('Synchronize in Google Calendar ?') }}</label>
                    <div class="form-switch">
                        <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                            value="google_calender">
                        <label class="form-check-label" for="switch-shadow"></label>
                    </div>
                </div>
            @endif
            <div class="form-group col-md-6">
                <label class="form-check-label" for="client_id">{{ __('Invite Client For Zoom Meeting') }}</label>
                <div class="form-switch form-switch-right">
                    <input class="form-check-input" type="checkbox" name="client_id" id="client_id" checked>
                    <label class="form-check-label" for="client_id"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>



<script type="text/javascript">
    $(document).on('change', '.project_select', function() {

        var project_id = $(this).val();

        getparent(project_id);
    });

    function getparent(bid) {

        $.ajax({
            url: `{{ url('zoom-meeting/projects/select') }}/${bid}`,
            type: 'GET',
            success: function(data) {
                $("#user_div").html('');
                $('#user_div').append(
                    '<select class="form-control " id="user_id" name="user_id[]"  multiple></select>');

                $.each(data, function(i, item) {

                    $('#user_id').append('<option value="' + item.id + '">' + item.name +
                        '</option>');
                });

                var multipleCancelButton = new Choices('#user_id', {
                    removeItemButton: true,
                });

                if (data == '') {
                    $('#user_id').empty();
                }
            }
        });
    }
</script>
