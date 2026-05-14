<form method="post" action="{{ route('appraisal.update', $appraisal->id) }}" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="col-form-label">{{ __('Branch*') }}</label>
                    <select name="branch" id="branch" required class="form-control ">
                        @foreach ($brances as $id => $value)
                            <option value="{{ id }}" @if ($appraisal->branch == $id) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="employees" class="col-form-label">{{ __('Employee*') }}</label>
                    <div class="employee_div">
                        <select name="employee" id="employee" class="form-control " required>

                        </select>
                    </div>

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="appraisal_date" class="col-form-label">{{ __('Select Month*') }}</label>
                    <input type="text" name="appraisal_date" id="appraisal_date" class="form-control d_filter" required>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="remark" class="col-form-label">{{ __('Remarks') }}</label>
                    <textarea name="remark" id="remark" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div class="row" id="stares">

        </div>

        <div class="modal-footer">
            <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
        </div>
    </form>



    <script>

        $('#employee').change(function(){

            var emp_id = $('#employee').val();
            $.ajax({
                url: "{{ route('empByStar') }}",
                type: "post",
                data:{
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
        var branch_ids = '{{ $appraisal->branch}}';
        var employee_id = '{{ $appraisal->employee}}';
        var appraisal_id = '{{ $appraisal->id}}';



        $( document ).ready(function() {
            $.ajax({
                url: "{{ route('getemployee') }}",
                type: "post",
                data:{
                    "branch_id": branch_ids,
                    "_token": "{{ csrf_token() }}",
                },

                cache: false,
                success: function(data) {

                    $('#employee').html('<option value="">Select Employee</option>');
                    $.each(data.employee, function (key, value) {
                        if(value.id == {{ $appraisal->employee }}){
                            $("#employee").append('<option  selected value="' + value.id + '">' + value.name + '</option>');
                        }else{
                            $("#employee").append('<option value="' + value.id + '">' + value.name + '</option>');
                        }
                    });
                }
            })

            $.ajax({
                url: "{{ route('empByStar1') }}",
                type: "post",
                data:{
                    "employee": employee_id,
                    "appraisal": appraisal_id,

                    "_token": "{{ csrf_token() }}",
                },

                cache: false,
                success: function(data) {

                    $('#stares').html(data.html);
                }
            })

        });

        $('#branch').on('change', function() {
            var branch_id = this.value;

            $.ajax({
                url: "{{ route('getemployee') }}",
                type: "post",
                data:{
                    "branch_id": branch_id,
                    "_token": "{{ csrf_token() }}",
                },

                cache: false,
                success: function(data) {

                    $('#employee').html('<option value="">Select Employee</option>');
                    $.each(data.employee, function (key, value) {
                        $("#employee").append('<option value="' + value.id + '">' + value.name + '</option>');
                    });

                }
            })
        });


    </script>
