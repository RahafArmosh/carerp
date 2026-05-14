<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDailyLog extends Model
{
    protected $fillable = [
        'employee_id',
        'department_id',
        'manager_id',
        'log_date',
        'day_notes',
        'created_by',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function tasks()
    {
        return $this->hasMany(EmployeeDailyLogTask::class, 'employee_daily_log_id')->orderBy('display_order');
    }

    public function scopeForTenant($query, int $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public static function firstOrCreateForEmployee(Employee $employee, string $logDate, int $creatorId): self
    {
        return self::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'log_date' => $logDate,
            ],
            [
                'department_id' => $employee->department_id ?: null,
                'manager_id' => $employee->manager_id ?: null,
                'created_by' => $creatorId,
            ]
        );
    }

    public function getTotalDurationMinutesAttribute(): int
    {
        return (int) $this->tasks()->sum('duration_minutes');
    }
}
