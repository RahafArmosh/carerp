@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Attendance List') }}
@endsection
<style>
    .text-blue {
        color: blue;
    }

    .text-yellow {
        color: orange;
    }
</style>
@push('script-page')
    <script>
        $('input[name="type"]:radio').on('change', function(e) {
            var type = $(this).val();

            if (type == 'monthly') {
                $('.month').addClass('d-block');
                $('.month').removeClass('d-none');
                $('.date').addClass('d-none');
                $('.date').removeClass('d-block');
            } else {
                $('.date').addClass('d-block');
                $('.date').removeClass('d-none');
                $('.month').addClass('d-none');
                $('.month').removeClass('d-block');
            }
        });

        $('input[name="type"]:radio:checked').trigger('change');
    </script>
    <script>
        function exportAttendance() {
            let type = document.querySelector('input[name="type"]:checked').value;
            let month = document.getElementById('month').value;
            let date = document.getElementById('date').value;
            let branch = document.getElementById('branch') ? document.getElementById('branch').value : '';
            let employee = document.getElementById('employee') ? document.getElementById('employee').value : '';
            let department = document.getElementById('department') ? document.getElementById('department').value : '';

            let queryParams = new URLSearchParams({
                type: type,
                month: type === 'monthly' ? month : '',
                date: type === 'daily' ? date : '',
                branch: branch,
                employee: employee,
                department: department
            });

            window.location.href = "{{ route('attendance.export') }}?" + queryParams.toString();
        }
    </script>

@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance') }}</li>
@endsection

{{-- @section('action-btn') --}}
{{--    <div class="float-end"> --}}
{{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}"> --}}
{{--            <i class="ti ti-filter"></i> --}}
{{--        </a> --}}
{{--    </div> --}}
{{-- @endsection --}}
@section('content')
    @php
        $roleNames = method_exists(\Auth::user(), 'getRoleNames')
            ? \Auth::user()->getRoleNames()->map(function ($name) {
                return strtolower((string) $name);
            })->all()
            : [];
        $isHrUser = strtolower((string) \Auth::user()->type) === 'hr' || in_array('hr', $roleNames, true);
        $canEditAttendanceNote = \Auth::user()->can('edit attendance')
            || \Auth::user()->type === 'company'
            || $isHrUser;
    @endphp
    <div class="row">
        <div class="col-sm-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('status'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('status') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('attendanceemployee.index') }}" method="get"
                            id="attendanceemployee_filter">
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-3">
                                            <label class="form-label">{{ __('Type') }}</label> <br>

                                            <div class="form-check form-check-inline form-group">
                                                <input type="radio" id="monthly" value="monthly" name="type"
                                                    class="form-check-input"
                                                    {{ isset($_GET['type']) && $_GET['type'] == 'monthly' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="monthly">{{ __('Monthly') }}</label>
                                            </div>
                                            <div class="form-check form-check-inline form-group">
                                                <input type="radio" id="daily" value="daily" name="type"
                                                    class="form-check-input"
                                                    {{ !isset($_GET['type']) || $_GET['type'] == 'daily' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="daily">{{ __('Daily') }}</label>
                                            </div>

                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                                            <div class="btn-box">
                                                <label for="month" class="form-label">{{ __('Month') }}</label>
                                                <input type="month" id="month" name="month"
                                                    value="{{ isset($_GET['month']) ? $_GET['month'] : date('Y-m') }}"
                                                    class="month-btn form-control month-btn">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 date">
                                            <div class="btn-box">
                                                <label for="date" class="form-label">{{ __('Date') }}</label>
                                                <input type="date" id="date" name="date"
                                                    value="{{ isset($_GET['date']) ? $_GET['date'] : date('Y-m-d') }}"
                                                    class="form-control month-btn">
                                            </div>
                                        </div>
                                        @if ($showAttendanceFilters ?? (\Auth::user()->type != 'employee'))
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                                                    <select id="branch" name="branch" class="form-control select2">
                                                        @foreach ($branch as $key => $value)
                                                            <option value="{{ $key }}"
                                                                {{ isset($_GET['branch']) && $_GET['branch'] == $key ? 'selected' : '' }}>
                                                                {{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <label for="employee">{{ __('Employee') }}</label>
                                                <select id="employee" name="employee" class="form-control select2">
                                                    <option value="">{{ __('Select Employee') }}</option>
                                                    @foreach ($employees as $employee)
                                                        <option value="{{ $employee->user_id }}"
                                                            {{ request('employee') == $employee->user_id ? 'selected' : '' }}>
                                                            {{ $employee->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    <label for="department"
                                                        class="form-label">{{ __('Department') }}</label>
                                                    <select id="department" name="department" class="form-control select2">
                                                        @foreach ($department as $key => $value)
                                                            <option value="{{ $key }}"
                                                                {{ isset($_GET['department']) && $_GET['department'] == $key ? 'selected' : '' }}>
                                                                {{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <div class="row">
                                        <div class="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('attendanceemployee_filter').submit(); return false;"
                                                data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                data-original-title="{{ __('apply') }}">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('attendanceemployee.index') }}"
                                                class="btn btn-sm btn-danger " data-bs-toggle="tooltip"
                                                title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                                <span class="btn-inner--icon"><i
                                                        class="ti ti-trash-off text-white-off "></i></span>
                                            </a>
                                            <a href="#" data-size="md" data-bs-toggle="tooltip"
                                                title="{{ __('Import') }}"
                                                data-url="{{ route('attendance.file.import') }}" data-ajax-popup="true"
                                                data-title="{{ __('Import employee CSV file') }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="ti ti-file-import"></i>
                                            </a>
                                            <a href="#" onclick="exportAttendance()" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="ti ti-file-export"></i>
                                            </a>

                                        </div>

                                    </div>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        @if (($missingCheckInEmployees ?? collect())->isNotEmpty())
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            {{ __('Missing Check-in List') }}
                            @if (!empty($missingCheckInDate))
                                <span class="text-muted small">({{ \Auth::user()->dateFormat($missingCheckInDate) }})</span>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee') }}</th>
                                        <th>{{ __('Branch') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($missingCheckInEmployees as $missingEmployee)
                                        <tr>
                                            <td>{{ $missingEmployee->name }}</td>
                                            <td>{{ $branch[$missingEmployee->branch_id] ?? __('N/A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    @if (\Auth::user()->type != 'Employee')
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Clock In') }}</th>
                                    <th>{{ __('Clock Out') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th>{{ __('Late') }}</th>
                                    <th>{{ __('Early Leaving') }}</th>
                                    <th>{{ __('Overtime') }}</th>
                                    <th>{{ __('Checkin Location') }}</th>
                                    <th>{{ __('Checkout Location') }}</th>
                                    @if ($canEditAttendanceNote || Gate::check('delete attendance'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($attendanceEmployee as $attendance)
                                    <tr>
                                        {{-- @if (\Auth::user()->type != 'Employee') --}}
                                        <td>{{ optional($attendance->employee)->name }}
                                        </td>
                                        {{-- @endif --}}
                                        <td>{{ \Auth::user()->dateFormat($attendance->date) }}</td>
                                        <td>{{ $attendance->status }}</td>
                                        <td>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '00:00' }}
                                        </td>
                                        <td>{{ $attendance->clock_out != '00:00:00' ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '00:00' }}
                                        </td>
                                        <td>
                                            <div class="text-break small" style="max-width: 22rem;"
                                                @if($attendance->note && mb_strlen($attendance->note) > 100) title="{{ e($attendance->note) }}" @endif>
                                                {{ $attendance->note ? \Illuminate\Support\Str::limit($attendance->note, 100) : '—' }}
                                            </div>
                                        </td>
                                        <td class="{{ $attendance->late  ? 'text-danger' : '' }}">
                                            {{ $attendance->late }}</td>
                                        <td class="{{ $attendance->early_leaving  ? 'text-yellow' : '' }}">
                                            {{ $attendance->early_leaving }}</td>
                                        <td class="{{ $attendance->overtime  ? 'text-blue' : '' }}">
                                            {{ $attendance->overtime }}</td>
                                        <td>
                                            @if ($attendance->status == 'office Device')
                                                Location : {{ $attendance->locationIn }}
                                            @else
                                                Lattitude : {{ $attendance->latitudeIn }}
                                                <br> Longitude : {{ $attendance->longitudeIn }}
                                                <br> Location : {{ $attendance->locationIn }}
                                                <br>
                                                @php
                                                    $mapsInUrl =
                                                        'https://www.google.com/maps/search/?api=1&query=' .
                                                        urlencode($attendance->latitudeIn . ',' . $attendance->longitudeIn);
                                                @endphp
                                                <a href="{{ $mapsInUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="text-primary small text-break d-inline-block">{{ $mapsInUrl }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            @if ( $attendance->status == 'office Device')
                                            Location : {{$attendance->locationOut}}
                                            @else
                                                Lattitude : {{ $attendance->latitudeOut }}
                                                <br> Longitude : {{ $attendance->longitudeOut }}
                                                <br> Location : {{ $attendance->locationOut }}
                                                <br>
                                                @php
                                                    $mapsOutUrl =
                                                        'https://www.google.com/maps/search/?api=1&query=' .
                                                        urlencode($attendance->latitudeOut . ',' . $attendance->longitudeOut);
                                                @endphp
                                                <a href="{{ $mapsOutUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="text-primary small text-break d-inline-block">{{ $mapsOutUrl }}</a>
                                            @endif
                                        </td>
                                       
                                        @if ($canEditAttendanceNote || Gate::check('delete attendance'))
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($canEditAttendanceNote)
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#attendanceNoteModal"
                                                            data-attendance-id="{{ $attendance->id }}"
                                                            data-note-json='@json($attendance->note)'
                                                            title="{{ __('Edit note') }}">
                                                            <i class="ti ti-pencil"></i>
                                                        </button>
                                                    @endif
                                                    @can('delete attendance')
                                                        <div class="action-btn bg-danger">
                                                            <form
                                                                action="{{ route('attendanceemployee.destroy', $attendance->id) }}"
                                                                method="post" id="delete-form-{{ $attendance->id }}">
                                                                @csrf
                                                                @method('DELETE')

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $attendance->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i></a>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                    
                                </tbody>
                               
                            </table>
                             {{ $attendanceEmployee->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade" id="attendanceNoteModal" tabindex="-1" aria-labelledby="attendanceNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="attendanceNoteForm" method="post" action="#">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="attendanceNoteModalLabel">{{ __('Edit note') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <label for="attendanceNoteTextarea" class="form-label">{{ __('Note') }}</label>
                        <textarea name="note" id="attendanceNoteTextarea" class="form-control" rows="5" maxlength="2000" placeholder="{{ __('Optional') }}"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

    @push('script-page')
        <script>
            $(document).ready(function() {
                $('.daterangepicker').daterangepicker({
                    format: 'yyyy-mm-dd',
                    locale: {
                        format: 'YYYY-MM-DD'
                    },
                });
            });
        </script>
        <script>
            (function () {
                const noteModal = document.getElementById('attendanceNoteModal');
                const noteForm = document.getElementById('attendanceNoteForm');
                const noteTextarea = document.getElementById('attendanceNoteTextarea');
                const baseUrl = @json(url('attendanceemployee'));

                if (!noteModal || !noteForm || !noteTextarea) {
                    return;
                }

                noteModal.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn || !btn.getAttribute('data-attendance-id')) {
                        return;
                    }
                    const id = btn.getAttribute('data-attendance-id');
                    const raw = btn.getAttribute('data-note-json');
                    let note = '';
                    try {
                        const v = JSON.parse(raw);
                        note = v === null || v === undefined ? '' : String(v);
                    } catch (e) {
                        note = '';
                    }
                    noteForm.action = baseUrl + '/' + encodeURIComponent(id) + '/note';
                    noteTextarea.value = note;
                });
            })();
        </script>
    @endpush
