<form method="post" action="{{ route('appraisal.update', $appraisal->id) }}" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="branch" class="col-form-label">{{ __('Branch*') }}</label>
                    <select name="branch" id="branch" required class="form-control ">
                        @foreach ($brances as $value)
                            <option value="{{ $value->id }}" @if ($appraisal->branch == $value->id) selected @endif>
                                {{ $value->name }}</option>
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
                    <input type="text" name="appraisal_date" id="appraisal_date" class="form-control d_filter"
                        required>
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
