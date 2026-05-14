<form action="{{ url('appraisal') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch*') }}</label>

                    <select name="branch" id="branch" required class="form-control ">
                        <option selected disabled value="0">{{ __('Select Branch') }}</option>

                        @foreach ($brances as $value)
                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>


            <div class="col-md-6 mt-2">
                <div class="form-group">
                    <label for="employee" class="form-label">{{ __('Employee*') }}</label>


                    <div class="employee_div">

                        <select name="employee" id="employee" class="form-control " required>
                        </select>
                    </div>
                </div>
            </div>


            <div class="col-md-6">
                <div class="form-group">
                    <label for="appraisal_date" class="form-label">{{ __('Select Month*') }}</label>
                    <input type="month" name="appraisal_date" id="appraisal_date" class="form-control"
                        autocomplete="off" required>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="remark" class="col-form-label">{{ __('Remarks') }}</label>
                    <textarea name="remark" id="remark" class="form-control" rows="3" placeholder="Enter remark"></textarea>
                </div>
            </div>
        </div>
        <div class="row" id="stares">
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>



    <script>
        $('#employee').change(function() {

            var emp_id = $('#employee').val();
            $.ajax({
                url: "{{ route('empByStar') }}",
                type: "post",
                data: {
                    "employee": emp_id,
                    "_token": "{{ csrf_token() }}",
                },

                cache: false,
                success: function(data) {
                    $('#stares').html(data.html);
                }
            })
        });
    </script>

    <script>
        $('#branch').on('change', function() {
            var branch_id = this.value;

            $.ajax({
                url: "{{ route('getemployee') }}",
                type: "post",
                data: {
                    "branch_id": branch_id,
                    "_token": "{{ csrf_token() }}",
                },

                cache: false,
                success: function(data) {

                    $('#employee').html('<option value="">Select Employee</option>');
                    $.each(data.employee, function(key, value) {
                        $("#employee").append('<option value="' + value.id + '">' + value.name +
                            '</option>');
                    });

                }
            })


        });
    </script>
