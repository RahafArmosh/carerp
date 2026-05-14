<?php

namespace App\Exports;

use App\Models\AttendanceEmployee;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $type, $month, $date, $branch, $employee, $department;
    protected $employeesCache = [];
    protected $departmentsCache = [];
    protected $user;

    public function __construct($type, $month, $date, $branch, $employee, $department)
    {
        $this->type = $type;
        $this->month = $month;
        $this->date = $date;
        $this->branch = $branch;
        $this->employee = $employee;
        $this->department = $department;
        $this->user = \Auth::user();
        
        // Pre-load all employees and departments to avoid N+1 queries
        $this->preloadData();
    }

    /**
     * Pre-load employees and departments into memory for fast lookup
     */
    protected function preloadData()
    {
        $creatorId = $this->user->creatorId();
        
        // Pre-load all employees that might be needed
        $employeeQuery = Employee::where('created_by', $creatorId);
        
        if (!empty($this->branch)) {
            $employeeQuery->where('branch_id', $this->branch);
        }
        
        if (!empty($this->department)) {
            $employeeQuery->where('department_id', $this->department);
        }
        
        if (!empty($this->employee)) {
            $employeeQuery->where('user_id', $this->employee);
        }
        
        $employees = $employeeQuery->get(['id', 'employee_id', 'name', 'department_id', 'startTime', 'endTime']);
        
        // Cache employees by ID for O(1) lookup
        foreach ($employees as $emp) {
            $this->employeesCache[$emp->id] = $emp;
        }
        
        // Pre-load all departments that might be needed
        $departmentIds = $employees->pluck('department_id')->filter()->unique();
        if ($departmentIds->isNotEmpty()) {
            $departments = Department::whereIn('id', $departmentIds)
                ->get(['id', 'name']);
            
            // Cache departments by ID for O(1) lookup
            foreach ($departments as $dept) {
                $this->departmentsCache[$dept->id] = $dept->name;
            }
        }
    }

    protected function query()
    {
        $query = AttendanceEmployee::query()
            ->with('employee') // Eager load employee relationship
            ->select('employee_id', 'date', 'status', 'clock_in', 'clock_out', 'late', 'early_leaving', 'overtime', 'latitudeIn', 'longitudeIn', 'latitudeOut', 'longitudeOut', 'locationIn', 'locationOut', 'note');

        // Add user permission filtering - only show employees created by the current user's creator
        $query->whereHas('employee', function ($q) {
            $q->where('created_by', $this->user->creatorId());
        });

        if ($this->type === 'monthly' && !empty($this->month)) {
            // Parse year and month explicitly to avoid any parsing issues
            // This matches the approach used in AttendanceEmployeeController::index()
            list($year, $month) = explode('-', $this->month);
            $start_date = \Carbon\Carbon::create((int)$year, (int)$month, 1)->startOfMonth()->format('Y-m-d');
            $end_date = \Carbon\Carbon::create((int)$year, (int)$month, 1)->copy()->endOfMonth()->format('Y-m-d');
            
            $query->whereBetween(
                'date',
                [
                    $start_date,
                    $end_date,
                ]
            );
        }

        if ($this->type === 'daily' && !empty($this->date)) {
            $query->whereDate('date', $this->date);
        }

        if (!empty($this->branch)) {
            $query->whereHas('employee', function ($q) {
                $q->where('branch_id', $this->branch);
            });
        }

        if (!empty($this->employee)) {
            $query->whereHas('employee', function ($q) {
                $q->where('user_id', $this->employee);
            });
        }

        if (!empty($this->department)) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->department);
            });
        }

        return $query->orderBy('date')->orderBy('employee_id');
    }

    protected function resolveMissingCheckInDate(): ?string
    {
        if ($this->type === 'monthly') {
            return null;
        }

        return !empty($this->date) ? $this->date : Carbon::today()->toDateString();
    }

    protected function missingCheckInRows(): Collection
    {
        $missingDate = $this->resolveMissingCheckInDate();
        if ($missingDate === null) {
            return collect();
        }

        $missingEmployeesQuery = Employee::query()
            ->where('created_by', $this->user->creatorId())
            ->whereNotNull('branch_id');

        if (!empty($this->branch)) {
            $missingEmployeesQuery->where('branch_id', $this->branch);
        }

        if (!empty($this->department)) {
            $missingEmployeesQuery->where('department_id', $this->department);
        }

        if (!empty($this->employee)) {
            $missingEmployeesQuery->where('user_id', $this->employee);
        }

        $eligibleEmployeeIds = (clone $missingEmployeesQuery)->pluck('id');
        $checkedInEmployeeIds = AttendanceEmployee::query()
            ->whereDate('date', $missingDate)
            ->whereIn('employee_id', $eligibleEmployeeIds)
            ->whereNotNull('clock_in')
            ->where('clock_in', '!=', '00:00:00')
            ->pluck('employee_id');

        $missingEmployees = (clone $missingEmployeesQuery)
            ->whereNotIn('id', $checkedInEmployeeIds)
            ->get(['id']);

        return $missingEmployees->map(function ($employee) use ($missingDate) {
            $attendance = new AttendanceEmployee();
            $attendance->employee_id = $employee->id;
            $attendance->date = $missingDate;
            $attendance->status = 'Missing Check-in';
            $attendance->clock_in = '00:00:00';
            $attendance->clock_out = '00:00:00';
            $attendance->late = '00:00:00';
            $attendance->early_leaving = '00:00:00';
            $attendance->overtime = '00:00:00';
            $attendance->locationIn = '';
            $attendance->locationOut = '';
            $attendance->latitudeIn = '';
            $attendance->longitudeIn = '';
            $attendance->latitudeOut = '';
            $attendance->longitudeOut = '';
            $attendance->note = '';

            return $attendance;
        });
    }

    public function collection(): Collection
    {
        $attendanceRows = $this->query()->get();
        $missingRows = $this->missingCheckInRows();

        return $attendanceRows
            ->concat($missingRows)
            ->sortBy([
                ['date', 'asc'],
                ['employee_id', 'asc'],
            ])
            ->values();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee',
            'Department',
            'Date',
            'Status',
            'Clock In',
            'Clock Out',
            'Late',
            'Early Leaving',
            'Overtime',
            'Checkin Location',
            'Check-in Map Link',
            'Checkout Location',
            'Check-out Map Link',
            'Total Time',
            'Check In Report',
            'Check Out Report',
            'Remarsks',
            'Note',
        ];
    }

    /**
     * Google Maps search URL for coordinates (empty if missing or office device).
     */
    protected function googleMapsUrl(?string $lat, ?string $lng): string
    {
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim((string) $lat) . ',' . trim((string) $lng));
    }

    /**
     * Format clock_in / clock_out for Excel export in 24-hour notation (HH:mm:ss).
     */
    protected function formatTime24hForExport(?string $clock): string
    {
        if ($clock === null || $clock === '' || $clock === '00:00:00') {
            return '00:00:00';
        }

        try {
            return Carbon::parse($clock)->format('H:i:s');
        } catch (\Throwable $e) {
            return (string) $clock;
        }
    }

    public function map($attendance): array
    {
        // Use cached employee data instead of querying database
        $emp = $this->employeesCache[$attendance->employee_id] ?? null;
        
        if (!$emp) {
            // Fallback: if employee not in cache, return empty row (shouldn't happen with proper filtering)
            return array_fill(0, 19, '');
        }
        
        $checkInMapLink = '';
        $checkOutMapLink = '';

        // Handle location data
        if ($attendance->status == "office Device") {
            $locationNameIN = $attendance->locationIn ?? '';
            $locationNameOut = $attendance->locationOut ?? '';
        } else {
            $checkInMapLink = $this->googleMapsUrl($attendance->latitudeIn, $attendance->longitudeIn);
            $checkOutMapLink = $this->googleMapsUrl($attendance->latitudeOut, $attendance->longitudeOut);
            // Use cached geocoding results
            // $locationNameIN = Cache::remember(
            //     "location_{$attendance->latitudeIn}_{$attendance->longitudeIn}",
            //     60 * 60 * 24,
            //     function () use ($attendance) {
            //         return GeocodingHelper::getLocationName($attendance->latitudeIn, $attendance->longitudeIn);
            //     }
            // );
            $locationNameIN = $attendance->locationIn;

            // $locationNameOut = Cache::remember(
            //     "location_{$attendance->latitudeOut}_{$attendance->longitudeOut}",
            //     60 * 60 * 24,
            //     function () use ($attendance) {
            //         return GeocodingHelper::getLocationName($attendance->latitudeOut, $attendance->longitudeOut);
            //     }
            // );
            $locationNameOut = $attendance->locationOut;
        }
        
        // Get department name from cache
        $departmentName = $this->departmentsCache[$emp->department_id] ?? '';
        
        // Calculate time differences
        $check_in_diff = '';
        $check_out_diff = '';
        $total_time = '00:00:00';
        $Remarsks = 'Absent';

        if ($attendance->clock_in && $attendance->clock_out) {
            try {
                $check_in = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                $check_out = Carbon::createFromFormat('H:i:s', $attendance->clock_out);
                
                if ($emp->startTime) {
                    $check_in_end = Carbon::createFromFormat('h:i A', $emp->startTime);
                    $isNegative = $check_in_end->lessThan($check_in);
                    $check_in_diff = $check_in_end->diff($check_in);
                    $check_in_diff = ($isNegative ? '-' : '+') . $check_in_diff->format('%H:%I:%S');
                }

                if ($emp->endTime) {
                    $check_out_end = Carbon::createFromFormat('h:i A', $emp->endTime);
                    $isNegative = $check_out->lessThan($check_out_end);
                    $check_out_diff = $check_out_end->diff($check_out);
                    $check_out_diff = ($isNegative ? '-' : '+') . $check_out_diff->format('%H:%I:%S');
                }
                
                $total_time = $check_out->diff($check_in);
                $total_time = $total_time->format('%H:%I:%S');
                $Remarsks = 'Present';
            } catch (\Exception $e) {
                // Handle invalid time format gracefully
                $Remarsks = 'Invalid Time';
            }
        }
        
        return [
            $emp->employee_id ?? '',
            $emp->name ?? '',
            $departmentName,
            $this->user->dateFormat($attendance->date),
            $attendance->status ?? '',
            $attendance->clock_in != '00:00:00' && $attendance->clock_in ? $this->formatTime24hForExport($attendance->clock_in) : '00:00:00',
            $attendance->clock_out != '00:00:00' && $attendance->clock_out ? $this->formatTime24hForExport($attendance->clock_out) : '00:00:00',
            $attendance->late ?? '',
            $attendance->early_leaving ?? '',
            $attendance->overtime ?? '',
            $locationNameIN ?? '',
            $checkInMapLink,
            $locationNameOut ?? '',
            $checkOutMapLink,
            $total_time,
            $check_in_diff,
            $check_out_diff,
            $Remarsks,
            $attendance->note ?? '',
        ];
    }

}
