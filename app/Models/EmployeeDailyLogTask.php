<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDailyLogTask extends Model
{
    protected $fillable = [
        'employee_daily_log_id',
        'task_master_id',
        'task_name',
        'hours',
        'minutes',
        'duration_minutes',
        'notes',
        'display_order',
        'created_by',
    ];

    protected static function booted()
    {
        static::saving(function (self $task): void {
            $task->hours = max(0, (int) $task->hours);
            $task->minutes = max(0, min(59, (int) $task->minutes));
            $task->duration_minutes = ((int) $task->hours * 60) + (int) $task->minutes;

            if (empty($task->task_name) && !empty($task->task_master_id)) {
                $masterTaskName = TaskMaster::where('id', $task->task_master_id)->value('name');
                $task->task_name = $masterTaskName ?? 'Custom Task';
            }
        });
    }

    public function dailyLog()
    {
        return $this->belongsTo(EmployeeDailyLog::class, 'employee_daily_log_id');
    }

    public function taskMaster()
    {
        return $this->belongsTo(TaskMaster::class, 'task_master_id');
    }
}
