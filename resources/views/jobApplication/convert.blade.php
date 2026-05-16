@extends('layouts.admin')
@section('page-title')
    {{ __('Convert To Employee') }}
@endsection
@section('content')
    <div class="row">
        <form action="{{ route('job.on.board.convert.store', $jobOnBoard->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
    </div>
    <div class="row">
        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Personal Detail') }}</h6>
                </div>
                <div class="card-body ">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name" class="form-label">{{ __('Name') }}</label><span
                                class="text-danger pl-1">*</span>
                            <input type="text" name="name" id="name" class="form-control"
                                value="{{ !empty($jobOnBoard->applications) ? $jobOnBoard->applications->name : '' }}"
                                required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone" class="form-label">{{ __('Phone') }}</label><span
                                class="text-danger pl-1">*</span>
                            <input type="number" name="phone" id="phone" class="form-control"
                                value="{{ !empty($jobOnBoard->applications) ? $jobOnBoard->applications->phone : '' }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label for="dob" class="form-label">{{ __('Date of Birth') }}</label><span
                                class="text-danger pl-1">*</span>
                            <input type="date" name="dob" id="dob" class="form-control datepicker"
                                value="{{ !empty($jobOnBoard->applications) ? $jobOnBoard->applications->dob : '' }}">
                        </div>



                        <div class="form-group col-md-6 ">
                            <label for="gender" class="form-label">{{ __('Gender') }}</label>
                            <span class="text-danger pl-1">*</span>
                            <div class="d-flex radio-check mt-2">
                                <div class="form-check form-check-inline form-group">
                                    <input type="radio" id="g_male" value="Male" name="gender"
                                        class="form-check-input"
                                        {{ !empty($jobOnBoard->applications) && $jobOnBoard->applications->gender == 'Male' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="g_male">{{ __('Male') }}</label>
                                </div>
                                <div class="form-check form-check-inline form-group">
                                    <input type="radio" id="g_female" value="Female" name="gender"
                                        class="form-check-input"
                                        {{ !empty($jobOnBoard->applications) && $jobOnBoard->applications->gender == 'Female' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="g_female">{{ __('Female') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="email" class="form-label">{{ __('Email') }}</label><span
                                class="text-danger pl-1">*</span>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control"
                                required="required">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password" class="form-label">{{ __('Password') }}</label><span
                                class="text-danger pl-1">*</span>
                            <input type="password" name="password" class="form-control" required="required">
                        </div>

                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label">{{ __('Address') }}</label><span
                            class="text-danger pl-1">*</span>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Company Detail') }}</h6>
                </div>
                <div class="card-body employee-detail-create-body">
                    <div class="row">
                        @csrf
                        <div class="form-group col-md-12">
                            <label for="employee_id" class="form-label">{{ __('Employee ID') }}</label>
                            <input type="text" name="employee_id" value="{{ $employeesId }}" class="form-control"
                                disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">{{ __('Select Branch') }}</option>
                                @foreach ($branches as $branchId => $branchName)
                                    <option value="{{ $branchId }}"
                                        {{ !empty($jobOnBoard->applications) && !empty($jobOnBoard->applications->jobs) && $jobOnBoard->applications->jobs->branch == $branchId ? 'selected' : '' }}>
                                        {{ $branchName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="department_id" class="form-label">{{ __('Department') }}</label>
                            <select name="department_id" id="department_id" class="form-control" required>
                                <option value="">{{ __('Select Department') }}</option>
                                @foreach ($departments as $departmentId => $departmentName)
                                    <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-12">
                            <label for="designation_id" class="form-label">{{ __('Designation') }}</label>
                            <select name="designation_id" id="designation_id" class="form-control" data-toggle="select2"
                                data-placeholder="{{ __('Select Designation ...') }}">
                                <option value="">{{ __('Select any Designation') }}</option>
                            </select>
                        </div>

                        <div class="form-group col-md-12">
                            <label for="company_doj" class="form-label">{{ __('Company Date Of Joining') }}</label>
                            <input type="date" name="company_doj" value="{{ $jobOnBoard->joining_date }}"
                                class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Document') }}</h6>
                </div>
                <div class="card-body employee-detail-create-body">
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
                                <div class="float-right col-8">
                                    <input type="hidden" name="emp_doc_id[{{ $document->id }}]" id=""
                                        value="{{ $document->id }}">
                                    <div class="choose-file form-group">
                                        <label for="document[{{ $document->id }}]">
                                            <div>{{ __('Choose File') }}</div>
                                            <input class="form-control  @error('document') is-invalid @enderror border-0"
                                                @if ($document->is_required == 1) required @endif
                                                name="document[{{ $document->id }}]" type="file"
                                                id="document[{{ $document->id }}]"
                                                data-filename="{{ $document->id . '_filename' }}">
                                        </label>
                                        <p class="{{ $document->id . '_filename' }}"></p>
                                    </div>

                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Bank Account Detail') }}</h6>
                </div>
                <div class="card-body employee-detail-create-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="account_holder_name" class="form-label">{{ __('Account Holder Name') }}</label>
                            <input type="text" name="account_holder_name" value="{{ old('account_holder_name') }}"
                                class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="account_number" class="form-label">{{ __('Account Number') }}</label>
                            <input type="number" name="account_number" value="{{ old('account_number') }}"
                                class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="bank_name" class="form-label">{{ __('Bank Name') }}</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="bank_identifier_code" class="form-label">{{ __('Bank Identifier Code') }}</label>
                            <input type="text" name="bank_identifier_code" value="{{ old('bank_identifier_code') }}"
                                class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="branch_location" class="form-label">{{ __('Branch Location') }}</label>
                            <input type="text" name="branch_location" value="{{ old('branch_location') }}"
                                class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="tax_payer_id" class="form-label">{{ __('Tax Payer Id') }}</label>
                            <input type="text" name="tax_payer_id" value="{{ old('tax_payer_id') }}"
                                class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 text-end">
            <input type="submit" value="Create" class="btn btn-primary radius-10px">
            </form>

        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            var d_id = $('#department_id').val();
            getDesignation(d_id);
        });

        $(document).on('change', 'select[name=department_id]', function() {
            var department_id = $(this).val();
            getDesignation(department_id);
        });

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
                    $('#designation_id').append(
                        '<option value="">{{ __('Select any Designation') }}</option>');
                    $.each(data, function(key, value) {
                        $('#designation_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                }
            });
        }
    </script>
@endpush
