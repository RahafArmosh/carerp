<form action="{{ url('contract') }}" method="post">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['contract']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label for="subject" class="form-label">{{ __('Subject') }}</label>
                <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="client_name" class="form-label">{{ __('Client') }}</label>
                <select name="client_name" id="client_select" class="form-control select client_select">
                    @foreach ($clients as $id => $client)
                        <option value="{{ $id }}">{{ $client }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="projects" class="form-label">{{ __('Projects') }}</label>
                <select class="form-control select project_select" id="project_id" name="project_id">
                    <option value="">{{ __('Select Project') }}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Contract Type') }}</label>
                <select name="type" id="type" class="form-control">
                    @foreach ($contractTypes as $id => $contractType)
                        <option value="{{ $id }}">{{ $contractType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="value" class="form-label">{{ __('Contract Value') }}</label>
                <input type="number" name="value" id="value" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn  btn-primary">{{ __('Create') }}</button>
    </div>
</form>

<script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
<script>
    if ($(".multi-select").length > 0) {
        $($(".multi-select")).each(function(index, element) {
            var id = $(element).attr('id');
            var multipleCancelButton = new Choices(
                '#' + id, {
                    removeItemButton: true,
                }
            );
        });
    }
</script>

<script type="text/javascript">
    $(".client_select").change(function() {

        var client_id = $(this).val();
        getparent(client_id);
    });

    function getparent(bid) {

        $.ajax({
            url: `{{ url('contract/clients/select') }}/${bid}`,
            type: 'GET',
            success: function(data) {
                console.log(data);
                $("#project_id").html('');
                $('#project_id').append(
                    '<select class="form-control" id="project_id" name="project_id[]"  ></select>');
                //var sdfdsfd = JSON.parse(data);
                $.each(data, function(i, item) {
                    console.log(item);
                    $('#project_id').append('<option value="' + item.id + '">' + item.name +
                        '</option>');
                });

                // var multipleCancelButton = new Choices('#project_id', {
                //     removeItemButton: true,
                // });

                if (data == '') {
                    $('#project_id').empty();
                }
            }
        });
    }
</script>
