<form action="{{ url('earlyleave/changeaction') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <table class="table modal-table">
                    <tr role="row">
                        <th>{{ __('Employee') }}</th>
                        <td>{{ !empty($employee->name) ? $employee->name : '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Appplied On') }}</th>
                        <td>{{ \Auth::user()->dateFormat($leave->date) }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Time') }}</th>
                        <td>{{ $leave->time }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Leave Reason') }}</th>
                        <td>{{ !empty($leave->reason) ? $leave->reason : '' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Status') }}</th>
                        <td>{{ !empty($leave->status) ? $leave->status : '' }}</td>
                    </tr>
                    <input type="hidden" value="{{ $leave->id }}" name="leave_id">
                </table>
            </div>
        </div>
    </div>
    @if (\Auth::user()->type == 'company')
        <div class="modal-footer">
            <input type="submit" value="{{ __('Approval') }}" class="btn btn-success" data-bs-dismiss="modal"
                name="status">
            <input type="submit" value="{{ __('Reject') }}" class="btn btn-danger" name="status">
        </div>
    @endif
</form>
