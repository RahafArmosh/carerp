<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'created_by',
    ];

    public function branch(){
        return $this->hasOne('App\Models\Branch','id','branch_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function dailyLogs()
    {
        return $this->hasMany(EmployeeDailyLog::class, 'department_id');
    }

    public function taskMasters()
    {
        return $this->hasMany(TaskMaster::class, 'department_id');
    }
}
