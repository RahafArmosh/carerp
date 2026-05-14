<form action="{{ route('contract.copy.store', $contract->id) }}" method="post">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="subject" class="form-label">{{ __('Subject') }}</label>
                <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="client" class="form-label">{{ __('Client') }}</label>
                <select name="client" id="client_select" class="form-control select client_select">
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label for="project" class="form-label">{{ __('Project') }}</label>
                <div class="project-div">
                    <select name="project_id[]" id="project_id" class="form-control select project_select" multiple>
                        @foreach ($project as $projectOption)
                            <option value="{{ $projectOption->id }}">{{ $projectOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Contract Type') }}</label>
                <select name="type" id="type" class="form-control" required>
                    @foreach ($contractTypes as $contractType)
                        <option value="{{ $contractType->id }}">{{ $contractType->name }}</option>
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
    <div class="modal-footer pr-0">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="submit" class="btn  btn-primary">{{ __('Copy') }}</button>
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
                    //console.log(item.name);
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
