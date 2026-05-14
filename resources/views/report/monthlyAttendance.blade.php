@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Monthly Attendance') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Monthly Attendance') }}</li>
@endsection
@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 4,
                    dpi: 72,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'A2'
                }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

    <script>
        $(document).ready(function() {
            var b_id = $('#branch_id').val();
            // getDepartment(b_id);
        });
        $(document).on('change', 'select[name=branch_id]', function() {

            var branch_id = $(this).val();
            getDepartment(branch_id);
        });

        function getDepartment(bid) {

            $.ajax({
                url: '{{ route('report.attendance.getdepartment') }}',
                type: 'POST',
                data: {
                    "branch_id": bid,
                    "_token": "{{ csrf_token() }}",
                },

                success: function(data) {
                    //console.log(data);
                    $('#department_id').empty();
                    $("#department_div").html('');
                    $('#department_div').append(
                        '<label for="department" class="form-label">{{ __('Department') }}</label><select class="form-control" id="department_id" name="department_id[]"  ></select>'
                    );
                    $('#department_id').append('<option value="">{{ __('Select Department') }}</option>');
                    $('#department_id').append('<option value="0"> {{ __('All Department') }} </option>');
                    $.each(data, function(key, value) {
                        //console.log(key, value);
                        $('#department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    // var multipleCancelButton = new Choices('#department_id', {
                    //     removeItemButton: true,
                    // });


                }

            });
        }

        $(document).on('change', '#department_id', function() {
            var department_id = $(this).val();
            getEmployee(department_id);
        });

        function getEmployee(did) {
            $.ajax({
                url: '{{ route('report.attendance.getemployee') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    console.log(data);
                    $('#employee_id').empty();
                    $("#employee_div").html('');
                    // $('#employee_div').append('<select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>');
                    $('#employee_div').append(
                        '<label for="employee" class="form-label">{{ __('Employee') }}</label><select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>'
                    );
                    $('#employee_id').append('<option value="">{{ __('Select Employee') }}</option>');
                    $('#employee_id').append('<option value="0"> {{ __('All Employee') }} </option>');

                    $.each(data, function(key, value) {
                        $('#employee_id').append('<option value="' + key + '">' + value + '</option>');
                    });

                    var multipleCancelButton = new Choices('#employee_id', {
                        removeItemButton: true,
                    });
                }
            });
        }
    </script>
@endpush



@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()" data-bs-toggle="tooltip"
            title="{{ __('Download') }}" data-original-title="{{ __('Download') }}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>

               <a href="{{route('tracking.index')}}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Tracking')}}" data-original-title="{{__('Tracking')}}">
                   <span class="btn-inner--icon"><i class="ti ti-current-location"></i></span>
                </a>

    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('report.monthly.attendance') }}" method="GET"
                            id="report_monthly_attendance">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="month" class="form-label">{{ __('Month') }}</label>
                                                <input type="month" id="month" name="month"
                                                    value="{{ isset($_GET['month']) ? $_GET['month'] : date('Y-m') }}"
                                                    class="month-btn form-control">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                                                <select class="form-control select" name="branch_id" id="branch_id"
                                                    placeholder="Select Branch" required>
                                                    <option value="">{{ __('Select Branch') }}</option>
                                                    <option value="0">{{ __('All Branch') }}</option>
                                                    @foreach ($branch as $branchItem)
                                                        <option value="{{ $branchItem->id }}">{{ $branchItem->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box" id="department_div">
                                                <label for="department_id"
                                                    class="form-label">{{ __('Department') }}</label>
                                                <select class="form-control select" name="department_id[]"
                                                    id="department_id" required="required" placeholder="Select Department">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box" id="employee_div">
                                                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                                                <select class="form-control select" name="employee_id[]" id="employee_id"
                                                    placeholder="Select Employee">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="row">
                                        <div class="col-auto mt-4">
                                            <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                title="{{ __('Apply') }}" data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </button>
                                            <a href="{{ route('report.monthly.attendance') }}"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off"></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="printableArea">
            <div class="row">
                <div class="col">
                    <input type="hidden"
                        value="{{ $data['branch'] . ' ' . __('Branch') . ' ' . $data['curMonth'] . ' ' . __('Attendance Report of') . ' ' . $data['department'] . ' ' . 'Department' }}"
                        id="filename">
                    <div class="card p-4 mb-4">
                        <h6 class="mb-0">{{ __('Report') }} :</h6>
                        <h7 class="text-sm mb-0">{{ __('Attendance Summary') }}</h7>
                    </div>
                </div>
                @if ($data['branch'] != 'All')
                    <div class="col">
                        <div class="card p-4 mb-4">
                            <h6 class=" mb-0">{{ __('Branch') }} :</h6>
                            <h7 class="text-sm mb-0">{{ $data['branch'] }}</h7>
                        </div>
                    </div>
                @endif
                @if ($data['department'] != 'All')
                    <div class="col">
                        <div class="card p-4 mb-4">
                            <h6 class=" mb-0">{{ __('Department') }} :</h6>
                            <h7 class="text-sm mb-0">{{ $data['department'] }}</h7>
                        </div>
                    </div>
                @endif
                <div class="col">
                    <div class="card p-4 mb-4">
                        <h6 class=" mb-0">{{ __('Duration') }} :</h6>
                        <h7 class="text-sm mb-0">{{ $data['curMonth'] }}</h7>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-xl-3 col-md-6 col-lg-3">
                    <div class="card p-4 mb-4 ">
                        <div class="float-left">
                            <h6 class=" mb-0">{{ __('Attendance') }}</h6>
                            <h7 class="text-sm text-sm mb-0 float-start">{{ __('Total present') }}:
                                {{ $data['totalPresent'] }}</h7>
                            <h7 class="text-sm mb-0 float-end">{{ __('Total leave') }} : {{ $data['totalLeave'] }}</h7>
                        </div>

                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-lg-3">
                    <div class="card p-4 mb-4">
                        <h6 class=" mb-0">{{ __('Overtime') }}</h6>
                        <h7 class="text-sm mb-0">{{ __('Total overtime in hours') }} :
                            {{ number_format($data['totalOvertime'], 2) }}</h7>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-lg-3">
                    <div class="card p-4 mb-4">
                        <h6 class=" mb-0">{{ __('Early leave') }}</h6>
                        <h7 class="text-sm mb-0">{{ __('Total early leave in hours') }} :
                            {{ number_format($data['totalEarlyLeave'], 2) }}</h7>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-lg-3">
                    <div class="card p-4 mb-4">
                        <h6 class=" mb-0">{{ __('Employee late') }}</h6>
                        <h7 class="text-sm mb-0">{{ __('Total late in hours') }} :
                            {{ number_format($data['totalLate'], 2) }}</h7>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body table-border-style">
                            <div class="table-responsive py-4 attendance-table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="active">{{ __('Name') }}</th>
                                            @foreach ($dates as $date)
                                                <th>{{ $date }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($employeesAttendance as $attendance)
                                            <tr>
                                                <td>{{ $attendance['name'] }}</td>
                                                @foreach ($attendance['status'] as $status)
                                                    <td>
                                                        @if (Str::contains($status, 'P'))
                                                            {{--                                                    <i class="custom-badge badge-success ap">{{__('P')}}</i> --}}
                                                            <i
                                                                class="badge bg-success p-2 rounded">{{ __('P') }}</i>
                                                        @elseif($status == 'A')
                                                            <i class="badge bg-danger p-2 rounded">{{ __('A') }}</i>
                                                        @endif
                                                        @if (Str::contains($status, 'Check-In:'))
                                                            <br>
                                                            <a href="#" class="btn btn-info btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                                data-status="{{ $status }}">
                                                                {{ __('Details') }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    <!-- Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">{{ __('Attendance Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>{{ __('Check-In Status:') }}</strong> <span id="checkin-status"></span></p>
                    <p><strong>{{ __('Check-In Location:') }}</strong> <span id="checkin-location"></span></p>
                    <p><strong>{{ __('Check-Out Status:') }}</strong> <span id="checkout-status"></span></p>
                    <p><strong>{{ __('Check-Out Location:') }}</strong> <span id="checkout-location"></span></p>
                    <p><strong>{{ __('Required Location:') }}</strong> <span id="required-location"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        function getLocationName(latitude, longitude, callback) {
            const apiKey = 'AIzaSyChJsgIE5QHkcMRvLHKFkQ7JQk0BVZNs_o';
            const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latitude},${longitude}&key=${apiKey}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        const result = data.results[0];
                        const addressComponents = result.address_components;
                        let streetName = '';
                        let areaName = '';
                        let placeName = '';
                        let city = '';
                        let country = '';

                        addressComponents.forEach(component => {
                            if (component.types.includes('route')) {
                                streetName = component.long_name;
                            }
                            if (component.types.includes('sublocality') || component.types.includes(
                                    'neighborhood')) {
                                areaName = component.long_name;
                            }
                            if (component.types.includes('locality')) {
                                city = component.long_name;
                            }
                            if (component.types.includes('country')) {
                                country = component.long_name;
                            }
                            if (component.types.includes('point_of_interest') || component.types.includes(
                                    'establishment')) {
                                placeName = component.long_name;
                            }
                        });

                        // Use the formatted address if no specific place name is found
                        if (!placeName) {
                            placeName = result.formatted_address;
                        }

                        callback(`${streetName}, ${areaName}, ${placeName}, ${city}, ${country}`);
                    } else {
                        callback('Location not found');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    callback('Error retrieving location');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const detailsModal = document.getElementById('detailsModal');
            detailsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const status = button.getAttribute('data-status');

                const checkinStatusElement = detailsModal.querySelector('#checkin-status');
                const checkinLocationElement = detailsModal.querySelector('#checkin-location');
                const checkoutStatusElement = detailsModal.querySelector('#checkout-status');
                const checkoutLocationElement = detailsModal.querySelector('#checkout-location');
                const requiredLocationElement = detailsModal.querySelector('#required-location');

                const checkInStatusMatch = status.match(/Check-In: ([^,]+),/);
                const checkInLocationMatch = status.match(/Check-In Location: ([0-9.\-]+), ([0-9.\-]+)/);
                const checkOutStatusMatch = status.match(/Check-Out: ([^,]+),/);
                const checkOutLocationMatch = status.match(/Check-Out Location: ([0-9.\-]+), ([0-9.\-]+)/);
                const requiredLocationMatch = status.match(/Required Location: ([0-9.\-]+), ([0-9.\-]+)/);

                checkinStatusElement.textContent = checkInStatusMatch ? checkInStatusMatch[1].trim() :
                    'N/A';
                if (checkInLocationMatch) {
                    const checkInLatitude = checkInLocationMatch[1];
                    const checkInLongitude = checkInLocationMatch[2];
                    getLocationName(checkInLatitude, checkInLongitude, function(locationName) {
                        checkinLocationElement.textContent = locationName;
                    });
                } else {
                    checkinLocationElement.textContent = 'N/A';
                }

                checkoutStatusElement.textContent = checkOutStatusMatch ? checkOutStatusMatch[1].trim() :
                    'N/A';
                if (checkOutLocationMatch) {
                    const checkOutLatitude = checkOutLocationMatch[1];
                    const checkOutLongitude = checkOutLocationMatch[2];
                    getLocationName(checkOutLatitude, checkOutLongitude, function(locationName) {
                        checkoutLocationElement.textContent = locationName;
                    });
                } else {
                    checkoutLocationElement.textContent = 'N/A';
                }

                if (requiredLocationMatch) {
                    const requiredLatitude = requiredLocationMatch[1];
                    const requiredLongitude = requiredLocationMatch[2];
                    getLocationName(requiredLatitude, requiredLongitude, function(locationName) {
                        requiredLocationElement.textContent = locationName;
                    });
                } else {
                    requiredLocationElement.textContent = 'N/A';
                }
            });
        });
    </script>
