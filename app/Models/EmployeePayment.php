<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayment extends Model
{
    protected $table = 'employee_payment';
    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'chart_account_id',
        'employee_id',
        'bill_id',
        'payment_id',
        'description',
        'category_id',
        'payment_method',
        'reference',
        'created_by',
        'status'
    ];

    public static $statues = [
        'Draft',
        '',
        'Received'
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }

}
