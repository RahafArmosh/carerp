@extends('layouts.admin')
@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Employee') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></div>
                    <div class="breadcrumb-item">{{ __('Employee') }}</div>
                </div>
            </div>
            <form method="post" action="{{ route('employee.store') }}" enctype="multipart/form-data">

                @csrf
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6 ">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('Personal Detail') }}</h4>
                                </div>
                                <div class="card-body">

                                    <div class="form-group">
                                        <label for="name">Name<span class="text-danger pl-1">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dob">Date of Birth</label>
                                                <input type="text" name="dob" class="form-control datepicker">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Gender<span class="text-danger pl-1">*</span></label><br>
                                                <input type="radio" name="gender" value="Male" checked class="mt-2">
                                                Male &nbsp&nbsp&nbsp
                                                <input type="radio" name="gender" value="Female"> Female
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone<span class="text-danger pl-1">*</span></label>
                                        <input type="number" name="phone" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" class="form-control"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger pl-1">*</span></label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Password<span class="text-danger pl-1">*</span></label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>


                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('Company Detail') }}</h4>
                                </div>
                                <div class="card-body">

                                    @csrf
                                    <div class="form-group">
                                        <label for="employee_id">Employee ID</label>
                                        <input type="text" name="employee_id" class="form-control"
                                            value="{{ \Illuminate\Support\Facades\Auth::user()->employeeIdFormat(1) }}"
                                            disabled>
                                    </div>

                                    <div class="form-group">
                                        <label for="branch_id">{{ __('Branch') }}</label>
                                        <select name="branch_id" class="form-control select2" required>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}"
                                                    {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="department_id">{{ __('Department') }}</label>
                                        <select name="department_id" class="form-control select2" id="department_id"
                                            required>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="designation_id">{{ __('Designation') }}</label>
                                        <select class="select2 form-control select2-multiple" id="designation_id"
                                            name="designation_id" data-toggle="select2"
                                            data-placeholder="{{ __('Select Designation ...') }}">
                                            <option value="">{{ __('Select any Designation') }}</option>
                                            @foreach ($designations as $designation)
                                                <option value="{{ $designation->id }}"
                                                    {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
                                                    {{ $designation->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="company_doj">Company Date Of Joining</label>
                                        <input type="text" name="company_doj" class="form-control datepicker" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="employee_id">Employee ID</label>
                                        <input type="text" name="employee_id" class="form-control"
                                            value="{{ \Illuminate\Support\Facades\Auth::user()->employeeIdFormat(1) }}"
                                            disabled>
                                    </div>

                                    <div class="form-group">
                                        <label for="branch_id">{{ __('Branch') }}</label>
                                        <select name="branch_id" class="form-control select2" required>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}"
                                                    {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="department_id">{{ __('Department') }}</label>
                                        <select name="department_id" class="form-control select2" id="department_id"
                                            required>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="designation_id">Designation</label>
                                        <select class="select2 form-control select2-multiple" id="designation_id"
                                            name="designation_id" data-toggle="select2"
                                            data-placeholder="{{ __('Select Designation ...') }}">
                                            <option value="">{{ __('Select any Designation') }}</option>

                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="company_doj">Company Date Of Joining</label>
                                        <input type="text" id="company_doj" name="company_doj"
                                            class="form-control datepicker" required>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 ">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('Document') }}</h4>
                                </div>
                                <div class="card-body">
                                    @foreach ($documents as $key => $document)
                                        <div class="row">
                                            <div class="form-group col-10">
                                                <div class="float-left">
                                                    <label for="document" class="float-left pt-1">{{ $document->name }}
                                                        @if ($document->is_required == 1)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                <div class="float-right">
                                                    <input
                                                        class="form-control float-right @error('document') is-invalid @enderror border-0"
                                                        @if ($document->is_required == 1) required @endif
                                                        name="document[{{ $document->id }}]" type="file"
                                                        id="document[{{ $document->id }}]" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('Bank Account Detail') }}</h4>
                                </div>
                                <div class="card-body">

                                    <div class="form-group">
                                        <label for="account_holder_name">Account Holder Name</label>
                                        <input type="text" id="account_holder_name" name="account_holder_name"
                                            class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="account_number">Account Number</label>
                                        <input type="text" id="account_number" name="account_number"
                                            class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" name="bank_name" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="bank_identifier_code">Bank Identifier Code</label>
                                        <input type="text" id="bank_identifier_code" name="bank_identifier_code"
                                            class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="branch_location">Branch Location</label>
                                        <input type="text" id="branch_location" name="branch_location"
                                            class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="tax_payer_id">Tax Payer Id</label>
                                        <input type="text" id="tax_payer_id" name="tax_payer_id"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg float-right">Save</button>
            </form>
        </section>
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

        $(function getDesignation(did) {
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
                        $('#designation_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                }
            });
        });
    </script>
@endpush
