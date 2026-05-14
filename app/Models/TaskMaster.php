<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskMaster extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_predefined',
        'created_by_employee_id',
        'department_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_predefined' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function creatorEmployee()
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function dailyLogTasks()
    {
        return $this->hasMany(EmployeeDailyLogTask::class, 'task_master_id');
    }

    public function scopeForCreator($query, int $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeVisibleToEmployee($query, Employee $employee)
    {
        return $query->where('created_by', $employee->created_by)
            ->where('is_active', true)
            ->where(function ($innerQuery) use ($employee) {
                $innerQuery->where('is_predefined', true)
                    ->orWhere('created_by_employee_id', $employee->id);
            });
    }

    /**
     * Task Master rows selectable on daily logs: active, same tenant,
     * department matches employee (or global), plus employee-specific custom masters.
     */
    public function scopeForDailyLogDropdown($query, Employee $employee)
    {
        return $query->where('created_by', $employee->created_by)
            ->where('is_active', true)
            ->where(function ($q) use ($employee) {
                $q->where(function ($q2) use ($employee) {
                    $q2->whereNull('department_id');
                    if (!empty($employee->department_id)) {
                        $q2->orWhere('department_id', $employee->department_id);
                    }
                })
                    ->orWhere('created_by_employee_id', $employee->id);
            })
            ->orderBy('name');
    }
}
