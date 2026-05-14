@extends('layouts.admin')

@section('page-title')
    {{ __('Create Employee') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('employee') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Employee') }}</li>
@endsection


@section('content')
    <div class="row">
        <div class="">
            <div class="">
                <div class="row">

                </div>
                <form action="{{ route('employee.store') }}" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card em-card">
                                <div class="card-header">
                                    <h5>{{ __('Personal Detail') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="name" class="form-label">Name</label><span
                                                class="text-danger pl-1">*</span>
                                            <input type="text" id="name" name="name" class="form-control"
                                                required placeholder="Enter employee name" value="{{ old('name') }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="phone" class="form-label">Phone</label><span
                                                class="text-danger pl-1">*</span>
                                            <input type="text" id="phone" name="phone" class="form-control"
                                                placeholder="Enter employee phone" value="{{ old('phone') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dob" class="form-label">Date of Birth</label><span
                                                    class="text-danger pl-1">*</span>
                                                <input type="date" id="dob" name="dob" class="form-control"
                                                    required autocomplete="off" placeholder="Select Date of Birth">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="gender" class="form-label">Gender</label><span
                                                    class="text-danger pl-1">*</span>
                                                <div class="d-flex radio-check">
                                                    <div class="custom-control custom-radio custom-control-inline">
                                                        <input type="radio" id="g_male" value="Male" name="gender"
                                                            class="form-check-input">
                                                        <label class="form-check-label" for="g_male">Male</label>
                                                    </div>
                                                    <div class="custom-control custom-radio ms-1 custom-control-inline">
                                                        <input type="radio" id="g_female" value="Female" name="gender"
                                                            class="form-check-input">
                                                        <label class="form-check-label" for="g_female">Female</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="email" class="form-label">Email</label><span
                                                class="text-danger pl-1">*</span>
                                            <input type="email" id="email" name="email" class="form-control"
                                                required placeholder="Enter employee email" value="{{ old('email') }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="password" class="form-label">Password</label><span
                                                class="text-danger pl-1">*</span>
                                            <input type="password" id="password" name="password" class="form-control"
                                                required placeholder="Enter employee new password">
                                        </div>
                                        <div class="form-group">
                                            <label for="address" class="form-label">Address</label><span
                                                class="text-danger pl-1">*</span>
                                            <textarea id="address" name="address" class="form-control" rows="2" placeholder="Enter employee address">{{ old('address') }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="mac_id" class="form-label">Mac Address</label>
                                            <input type="text" id="mac_id" name="mac_id" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="employee_device_id" class="form-label">{{ __('Employee Device ID') }}</label>
                                            <input type="text" id="employee_device_id" name="employee_device_id" class="form-control"
                                                value="{{ old('employee_device_id') }}" placeholder="{{ __('Mobile app / device identifier') }}">
                                            <small class="text-muted">{{ __('Optional. Used for attendance or mobile app identification.') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="chart_account_id" class="form-label">{{ __('Account') }}</label>
                                            <select class="form-control select select2" name="chart_account" id="chart_account">
                                                @foreach ($chart_accounts as $id => $codeName)
                                                    <option value="{{ $id }}">{{ $codeName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="required_startTime">Start Time</label>
                                            <input type="time" name="required_startTime" class="form-control"
                                                id="required_startTime" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="required_endTime">End Time</label>
                                            <input type="time" name="required_endTime" class="form-control"
                                                id="required_endTime" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="required_latitude">Required Latitude</label>
                                            <input type="text" name="required_latitude" class="form-control"
                                                id="required_latitude" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="required_longitude">Required Longitude</label>
                                            <input type="text" name="required_longitude" class="form-control"
                                                id="required_longitude" readonly>
                                        </div>

                                        <div id="map" style="height: 400px; width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card em-card">
                                    <div class="card-header">
                                        <h5>{{ __('Company Detail') }}</h5>
                                    </div>
                                    <div class="card-body employee-detail-create-body">
                                        <div class="row">
                                            @csrf
                                            <div class="form-group">
                                                <label for="employee_id" class="form-label">Employee ID</label>
                                                <input type="text" id="employee_id" name="employee_id"
                                                    class="form-control" disabled value="{{ $employeesId }}">
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label for="branch_id" class="form-label">Select Branch*</label>
                                                <div class="form-icon-user">
                                                    <select id="branch_id" name="branch_id" class="form-control select2"
                                                        required>
                                                        <option value="" selected>Select Branch</option>
                                                        <?php foreach($branches as $id => $branch) {?>
                                                        <option value="<?php echo $id; ?>"><?php echo $branch; ?></option>
                                                        <?php }?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label for="department_id" class="form-label">Select Department*</label>
                                                <div class="form-icon-user">
                                                    <select id="department_id" name="department_id"
                                                        class="form-control select2" required>
                                                        <option value="" selected>Select Department</option>
                                                        <?php foreach($departments as $id => $department) {?>
                                                        <option value="<?php echo $id; ?>"><?php echo $department; ?></option>
                                                        <?php }?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="designation_id" class="form-label">Select Designation</label>
                                                <div class="form-icon-user">
                                                    <select id="designation_id" name="designation_id"
                                                        class="form-control select2" required>
                                                        <option value="" selected>Select Designation</option>
                                                        <?php foreach($designations as $id => $designation) {?>
                                                        <option value="<?php echo $id; ?>"><?php echo $designation; ?></option>
                                                        <?php }?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="manager_id" class="form-label">{{ __('Reporting Manager') }}</label>
                                                <div class="form-icon-user">
                                                    <select id="manager_id" name="manager_id" class="form-control select2">
                                                        @foreach ($managers as $id => $manager)
                                                            <option value="{{ $id }}" {{ (string) old('manager_id') === (string) $id ? 'selected' : '' }}>
                                                                {{ $manager }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="company_doj" class="form-label">Company Date Of
                                                    Joining</label>
                                                <input type="date" id="company_doj" name="company_doj"
                                                    class="form-control" required autocomplete="off"
                                                    placeholder="Select company date of joining">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 ">
                                <div class="card em-card">
                                    <div class="card-header">
                                        <h5>{{ __('Document') }}</h6>
                                    </div>
                                    <div class="card-body employee-detail-create-body">
                                        @foreach ($documents as $key => $document)
                                            <div class="row">
                                                <div class="form-group col-12 d-flex">
                                                    <div class="float-left col-4">
                                                        <label for="document"
                                                            class="float-left pt-1 form-label">{{ $document->name }}
                                                            @if ($document->is_required == 1)
                                                                <span class="text-danger">*</span>
                                                            @endif
                                                        </label>
                                                    </div>
                                                    <div class="float-right col-8">
                                                        <input type="hidden" name="emp_doc_id[{{ $document->id }}]"
                                                            id="" value="{{ $document->id }}">
                                                        <div class="choose-files">
                                                            <label for="document[{{ $document->id }}]">
                                                                <div class=" bg-primary document "> <i
                                                                        class="ti ti-upload "></i>{{ __('Choose file here') }}
                                                                </div>
                                                                <input type="file"
                                                                    class="form-control file  d-none @error('document') is-invalid @enderror"
                                                                    @if ($document->is_required == 1) required @endif
                                                                    name="document[{{ $document->id }}]"
                                                                    id="document[{{ $document->id }}]"
                                                                    data-filename="{{ $document->id . '_filename' }}"
                                                                    onchange="document.getElementById('{{ 'blah' . $key }}').src = window.URL.createObjectURL(this.files[0])">
                                                            </label>
                                                            <img id="{{ 'blah' . $key }}" src=""
                                                                width="50%" />

                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="card em-card">
                                    <div class="card-header">
                                        <h5>{{ __('Bank Account Detail') }}</h5>
                                    </div>
                                    <div class="card-body employee-detail-create-body">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="account_holder_name">Account Holder
                                                    Name</label>
                                                <input type="text" class="form-control" id="account_holder_name"
                                                    name="account_holder_name" placeholder="Enter account holder name"
                                                    value="{{ old('account_holder_name') }}">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="account_number">Account Number</label>
                                                <input type="number" class="form-control" id="account_number"
                                                    name="account_number" placeholder="Enter account number"
                                                    value="{{ old('account_number') }}">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="bank_name">Bank Name</label>
                                                <input type="text" class="form-control" id="bank_name"
                                                    name="bank_name" placeholder="Enter bank name"
                                                    value="{{ old('bank_name') }}">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="bank_identifier_code">Bank Identifier
                                                    Code</label>
                                                <input type="text" class="form-control" id="bank_identifier_code"
                                                    name="bank_identifier_code" placeholder="Enter bank identifier code"
                                                    value="{{ old('bank_identifier_code') }}">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="branch_location">Branch Location</label>
                                                <input type="text" class="form-control" id="branch_location"
                                                    name="branch_location" placeholder="Enter branch location"
                                                    value="{{ old('branch_location') }}">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="tax_payer_id">Tax Payer Id</label>
                                                <input type="text" class="form-control" id="tax_payer_id"
                                                    name="tax_payer_id" placeholder="Enter tax payer id"
                                                    value="{{ old('tax_payer_id') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="float-end">
                        <button type="submit" class="btn  btn-primary">{{ 'Create' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('script-page')
        <script>
            $('input[type="file"]').change(function(e) {
                var file = e.target.files[0].name;
                var file_name = $(this).attr('data-filename');
                $('.' + file_name).append(file);
            });
        </script>
        <script>
            $(document).ready(function() {
                var d_id = $('.department_id').val();
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

                        $('.designation_id').empty();
                        var emp_selct = ` <select class="form-control  designation_id" name="designation_id" id="choices-multiple"
                                            placeholder="Select Designation" >
                                            </select>`;
                        $('.designation_div').html(emp_selct);

                        $('.designation_id').append('<option value="0"> {{ __('All') }} </option>');
                        $.each(data, function(key, value) {
                            $('.designation_id').append('<option value="' + key + '">' + value +
                                '</option>');
                        });
                        new Choices('#choices-multiple', {
                            removeItemButton: true,
                        });


                    }
                });
            }
        </script>

        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyChJsgIE5QHkcMRvLHKFkQ7JQk0BVZNs_o&callback=initMap"
            async defer></script>
        <script>
            function initMap() {
                // Create the map
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 15
                });

                // Try HTML5 geolocation
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        map.setCenter(pos);

                        // Create a draggable marker
                        var marker = new google.maps.Marker({
                            position: pos,
                            map: map,
                            draggable: true
                        });

                        // Update input fields when the marker is dragged
                        google.maps.event.addListener(marker, 'dragend', function(event) {
                            document.getElementById('required_latitude').value = event.latLng.lat();
                            document.getElementById('required_longitude').value = event.latLng.lng();
                        });

                        // Update input fields when the map is clicked
                        google.maps.event.addListener(map, 'click', function(event) {
                            marker.setPosition(event.latLng);
                            document.getElementById('required_latitude').value = event.latLng.lat();
                            document.getElementById('required_longitude').value = event.latLng.lng();
                        });
                    }, function() {
                        handleLocationError(true, map.getCenter());
                    });
                } else {
                    // Browser doesn't support Geolocation
                    handleLocationError(false, map.getCenter());
                }
            }

            function handleLocationError(browserHasGeolocation, pos) {
                var infoWindow = new google.maps.InfoWindow({
                    map: map
                });
                infoWindow.setPosition(pos);
                infoWindow.setContent(browserHasGeolocation ?
                    'Error: The Geolocation service failed.' :
                    'Error: Your browser doesn\'t support geolocation.');
            }
        </script>
    @endpush
