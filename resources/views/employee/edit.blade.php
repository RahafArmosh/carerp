@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Employee') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
    </li>
    <li class="breadcrumb-item"><a href="{{ route('employee.index') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ $employeesId }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="col-12">
            <form method="POST" action="{{ route('employee.update', $employee->id) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 ">
            <div class="card emp_details">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Personal Detail') }}</h6>
                </div>
                <div class="card-body employee-detail-edit-body">

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name" class="form-label">Name</label><span class="text-danger pl-1">*</span>
                            <input type="text" id="name" name="name" class="form-control" required
                                value="{{ $employee->name }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone" class="form-label">Phone</label><span class="text-danger pl-1">*</span>
                            <input type="number" id="phone" name="phone" class="form-control"
                                value="{{ $employee->phone }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label><span
                                class="text-danger pl-1">*</span>
                            <input type="date" id="dob" name="dob" class="form-control"
                                value="{{ $employee->dob }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gender" class="form-label">Gender</label><span class="text-danger pl-1">*</span>
                            <div class="d-flex radio-check mt-2">
                                <div class="form-check form-check-inline form-group">
                                    <input type="radio" id="g_male" value="Male" name="gender"
                                        class="form-check-input" {{ $employee->gender == 'Male' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="g_male">Male</label>
                                </div>
                                <div class="form-check form-check-inline form-group">
                                    <input type="radio" id="g_female" value="Female" name="gender"
                                        class="form-check-input" {{ $employee->gender == 'Female' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="g_female">Female</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label><span class="text-danger pl-1">*</span>
                        <textarea id="address" name="address" class="form-control" rows="2">{{ $employee->address }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="employee_device_id" class="form-label">{{ __('Employee Device ID') }}</label>
                        <input type="text" id="employee_device_id" name="employee_device_id" class="form-control"
                            value="{{ old('employee_device_id', $employee->employee_device_id) }}" placeholder="{{ __('Mobile app / device identifier') }}">
                        <small class="text-muted">{{ __('Optional. Used for attendance or mobile app identification.') }}</small>
                    </div>
                    <div class="form-group">
                        <label for="required_startTime">Start Time</label>
                        <input type="time" name="required_startTime" class="form-control"
                            id="required_startTime" value="{{ isset($employee->startTime) ? \Carbon\Carbon::createFromFormat('h:i A', $employee->startTime)->format('H:i') : '' }}">
                    </div>
                    <div class="form-group">
                        <label for="required_endTime">End Time</label>
                        <input type="time" name="required_endTime" class="form-control"
                            id="required_endTime" value="{{ isset($employee->endTime) ? \Carbon\Carbon::createFromFormat('h:i A', $employee->endTime)->format('H:i') : '' }}">
                    </div>
                    <div class="form-group col-md-12 account">
                        <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                        <select class="form-control select" name="chart_account_id" id="chart_account_id">
                            @foreach ($chart_accounts as $id => $codeName)
                                <option value="{{ $id }}" {{ $employee->chart_account_id == $id ? 'selected' : '' }}>
                                    {{ $codeName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (\Auth::user()->type == 'employee')
                        <button type="submit" class="btn-create btn-xs badge-blue radius-10px float-right">Update</button>
                    @endif

                </div>
            </div>
        </div>
        @if (\Auth::user()->type != 'Employee')
            <div class="col-md-6 ">
                <div class="card emp_details">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Company Detail') }}</h6>
                    </div>
                    <div class="card-body employee-detail-edit-body">
                        <div class="row">
                            @csrf
                            <div class="form-group col-md-12">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" id="employee_id" name="employee_id" class="form-control"
                                    value="{{ $employeesId }}" disabled value="{{ $employee->employee_id }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id" class="form-control select" required>
                                    @foreach ($branches as $key => $val)
                                        <option value="{{ $key }}"
                                            @if ($key === $employee->branch_id) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="department_id" class="form-label">Department</label>
                                <select id="department_id" name="department_id" class="form-control select" required>
                                    @foreach ($departments as $key => $val)
                                        <option value="{{ $key }}"
                                            @if ($key === $employee->department_id) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="designation_id" class="form-label">Designation</label>
                                <select id="designation_id" name="designation_id" class="form-control select">
                                    @foreach ($designations as $key => $val)
                                        <option value="{{ $key }}"
                                            @if ($key === $employee->designation_id) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="manager_id" class="form-label">{{ __('Reporting Manager') }}</label>
                                <select id="manager_id" name="manager_id" class="form-control select">
                                    @foreach ($managers as $key => $val)
                                        <option value="{{ $key }}"
                                            @if ((string) old('manager_id', $employee->manager_id) === (string) $key) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="company_doj" class="form-label">Company Date Of Joining</label>
                                <input type="date" id="company_doj" name="company_doj" class="form-control" required
                                    value="{{ $employee->company_doj }}">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-md-6 ">
                <div class="employee-detail-wrap ">
                    <div class="card emp_details">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('Company Detail') }}</h6>
                        </div>
                        <div class="card-body employee-detail-edit-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Branch') }}</strong>
                                        <span>{{ !empty($employee->branch) ? $employee->branch->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info font-style">
                                        <strong>{{ __('Department') }}</strong>
                                        <span>{{ !empty($employee->department) ? $employee->department->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info font-style">
                                        <strong>{{ __('Designation') }}</strong>
                                        <span>{{ !empty($employee->designation) ? $employee->designation->name : '' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Date Of Joining') }}</strong>
                                        <span>{{ \Auth::user()->dateFormat($employee->company_doj) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if (\Auth::user()->type != 'Employee')
        <div class="row">
            <div class="col-md-6 ">
                <div class="card emp_details">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Document') }}</h6>
                    </div>
                    <div class="card-body employee-detail-edit-body">
                        @php
                            $employeedoc = $employee->documents()->pluck('document_value', __('document_id'));
                        @endphp

                        @foreach ($documents as $key => $document)
                            <div class="row">
                                <div class="form-group col-12">
                                    <div class="float-left col-4">
                                        <label for="document" class="float-left pt-1 form-label">{{ $document->name }}
                                            @if ($document->is_required == 1)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                    </div>
                                    <div class="float-right col-4">
                                        <input type="hidden" name="emp_doc_id[{{ $document->id }}]" id=""
                                            value="{{ $document->id }}">
                                        <div class="choose-file form-group">
                                            <label for="document[{{ $document->id }}]">
                                                <input
                                                    class="form-control @if (!empty($employeedoc[$document->id])) float-left @endif @error('document') is-invalid @enderror border-0"
                                                    @if ($document->is_required == 1 && empty($employeedoc[$document->id])) required @endif
                                                    name="document[{{ $document->id }}]"
                                                    onchange="document.getElementById('{{ 'blah' . $key }}').src = window.URL.createObjectURL(this.files[0])"
                                                    type="file" data-filename="{{ $document->id . '_filename' }}">
                                            </label>
                                            <p class="{{ $document->id . '_filename' }}"></p>

                                            @php
                                                $logo = \App\Models\Utility::get_file('uploads/document/');
                                            @endphp

                                            {{--                                            <img id="{{'blah'.$key}}" src=""  width="25%" /> --}}
                                            <img id="{{ 'blah' . $key }}"
                                                src="{{ isset($employeedoc[$document->id]) && !empty($employeedoc[$document->id]) ? $logo . '/' . $employeedoc[$document->id] : '' }}"
                                                width="25%" />

                                        </div>


                                        {{--                                        @if (!empty($employeedoc[$document->id])) --}}
                                        {{--                                            <br> <span class="text-xs"><a href="{{ (!empty($employeedoc[$document->id])?asset(Storage::url('uploads/document')).'/'.$employeedoc[$document->id]:'') }}" target="_blank">{{ (!empty($employeedoc[$document->id])?$employeedoc[$document->id]:'') }}</a> --}}
                                        {{--                                                    </span> --}}
                                        {{--                                        @endif --}}
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card emp_details">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Bank Account Detail') }}</h6>
                    </div>
                    <div class="card-body employee-detail-edit-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="account_holder_name" class="form-label">Account Holder Name</label>
                                <input type="text" id="account_holder_name" name="account_holder_name"
                                    class="form-control" value="{{ $employee->account_holder_name }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="account_number" class="form-label">Account Number</label>
                                <input type="number" id="account_number" name="account_number" class="form-control"
                                    value="{{ $employee->account_number }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bank_name" class="form-label">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" class="form-control"
                                    value="{{ $employee->bank_name }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bank_identifier_code" class="form-label">Bank Identifier Code</label>
                                <input type="text" id="bank_identifier_code" name="bank_identifier_code"
                                    class="form-control" value="{{ $employee->bank_identifier_code }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="branch_location" class="form-label">Branch Location</label>
                                <input type="text" id="branch_location" name="branch_location" class="form-control"
                                    value="{{ $employee->branch_location }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tax_payer_id" class="form-label">Tax Payer Id</label>
                                <input type="text" id="tax_payer_id" name="tax_payer_id" class="form-control"
                                    value="{{ $employee->tax_payer_id }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-6 ">
                <div class="employee-detail-wrap">
                    <div class="card emp_details">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('Document Detail') }}</h6>
                        </div>
                        <div class="card-body employee-detail-edit-body">
                            <div class="row">
                                @php
                                    $employeedoc = $employee->documents()->pluck('document_value', __('document_id'));
                                @endphp
                                @foreach ($documents as $key => $document)
                                    <div class="col-md-12">
                                        <div class="info">
                                            <strong>{{ $document->name }}</strong>
                                            <span><a href="{{ !empty($employeedoc[$document->id]) ? URL::to('/') . '/documents/employee/' . $employeedoc[$document->id] : '' }}"
                                                    target="_blank">{{ !empty($employeedoc[$document->id]) ? $employeedoc[$document->id] : '' }}</a></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 ">
                <div class="employee-detail-wrap">
                    <div class="card emp_details">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('Bank Account Detail') }}</h6>
                        </div>
                        <div class="card-body employee-detail-edit-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Account Holder Name') }}</strong>
                                        <span>{{ $employee->account_holder_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info font-style">
                                        <strong>{{ __('Account Number') }}</strong>
                                        <span>{{ $employee->account_number }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info font-style">
                                        <strong>{{ __('Bank Name') }}</strong>
                                        <span>{{ $employee->bank_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Bank Identifier Code') }}</strong>
                                        <span>{{ $employee->bank_identifier_code }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Branch Location') }}</strong>
                                        <span>{{ $employee->branch_location }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info">
                                        <strong>{{ __('Tax Payer Id') }}</strong>
                                        <span>{{ $employee->tax_payer_id }}</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (\Auth::user()->type != 'employee')
        <div class="row">
            <div class="col-12">
                <input type="submit" value="{{ __('Update') }}" class="btn btn-primary float-end">
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-12">
            </form>
        </div>
    </div>
@endsection

@push('script-page')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2();
        });
        $(document).on('change', '#branch_id', function() {
            var branch_id = $(this).val();
            getDepartment(branch_id);
        });

        function getDepartment(branch_id) {
            var data = {
                "branch_id": branch_id,
                "_token": "{{ csrf_token() }}",
            }

            $.ajax({
                url: '{{ route('employee.getdepartment') }}',
                method: 'POST',
                data: data,
                success: function(data) {
                    $('#department_id').empty();
                    $('#department_id').append(
                        '<option value="" disabled>{{ __('Select any Department') }}</option>');

                    $.each(data, function(key, value) {
                        $('#department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    $('#department_id').val('');
                }
            });
        }
    </script>
    <script type="text/javascript">
        function getDesignation(did) {
            $.ajax({
                url: '{{ route('employee.json') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#designation_id').empty();
                    $('#designation_id').append('<option value="">Select any Designation</option>');
                    $.each(data, function(key, value) {
                        var select = '';
                        if (key == '{{ $employee->designation_id }}') {
                            select = 'selected';
                        }

                        $('#designation_id').append('<option value="' + key + '"  ' + select + '>' +
                            value + '</option>');
                    });
                }
            });
        }

        $(document).ready(function() {
            var d_id = $('#department_id').val();
            var designation_id = '{{ $employee->designation_id }}';
            getDesignation(d_id);
        });

        $(document).on('change', 'select[name=department_id]', function() {
            var department_id = $(this).val();
            getDesignation(department_id);
        });
    </script>
@endpush
