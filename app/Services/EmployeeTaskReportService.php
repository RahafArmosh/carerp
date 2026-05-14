<?php

namespace App\Services;

use App\Models\EmployeeDailyLogTask;

class EmployeeTaskReportService
{
    public function totalTimePerDayPerEmployee(int $creatorId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = EmployeeDailyLogTask::query()
            ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
            ->join('employees', 'employees.id', '=', 'employee_daily_logs.employee_id')
            ->selectRaw('employee_daily_logs.employee_id, employees.name as employee_name, employee_daily_logs.log_date, SUM(employee_daily_log_tasks.duration_minutes) as total_minutes')
            ->where('employee_daily_logs.created_by', $creatorId)
            ->groupBy('employee_daily_logs.employee_id', 'employees.name', 'employee_daily_logs.log_date')
            ->orderBy('employee_daily_logs.log_date')
            ->orderBy('employees.name');

        if (!empty($fromDate)) {
            $query->whereDate('employee_daily_logs.log_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('employee_daily_logs.log_date', '<=', $toDate);
        }

        return $query->get();
    }

    public function timePerTask(int $creatorId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = EmployeeDailyLogTask::query()
            ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
            ->selectRaw('COALESCE(employee_daily_log_tasks.task_name, "Custom Task") as task_name, SUM(employee_daily_log_tasks.duration_minutes) as total_minutes')
            ->where('employee_daily_logs.created_by', $creatorId)
            ->groupBy('employee_daily_log_tasks.task_name')
            ->orderByDesc('total_minutes');

        if (!empty($fromDate)) {
            $query->whereDate('employee_daily_logs.log_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('employee_daily_logs.log_date', '<=', $toDate);
        }

        return $query->get();
    }

    public function departmentSummaries(int $creatorId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = EmployeeDailyLogTask::query()
            ->join('employee_daily_logs', 'employee_daily_logs.id', '=', 'employee_daily_log_tasks.employee_daily_log_id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_daily_logs.department_id')
            ->selectRaw('COALESCE(departments.name, "Unassigned") as department_name, COUNT(DISTINCT employee_daily_logs.employee_id) as employee_count, SUM(employee_daily_log_tasks.duration_minutes) as total_minutes')
            ->where('employee_daily_logs.created_by', $creatorId)
            ->groupBy('departments.name')
            ->orderByDesc('total_minutes');

        if (!empty($fromDate)) {
            $query->whereDate('employee_daily_logs.log_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('employee_daily_logs.log_date', '<=', $toDate);
        }

        return $query->get();
    }

    public function toHoursMinutes(int $totalMinutes): array
    {
        return [
            'hours' => intdiv($totalMinutes, 60),
            'minutes' => $totalMinutes % 60,
        ];
    }
}
