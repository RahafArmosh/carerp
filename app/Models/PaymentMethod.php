<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'name',
        'bank_account_id',
        'created_by',
    ];

    /**
     * Relationships
     */

    // Assuming you have Warehouse and BankAccount models
    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function chartAccount()
    {
        return $this->hasOneThrough(
            \App\Models\ChartOfAccount::class,
            \App\Models\BankAccount::class,
            'id',                 // BankAccount's PK
            'id',                 // ChartOfAccount's PK
            'bank_account_id',    // Foreign key on PaymentMethod
            'chart_account_id'    // Foreign key on BankAccount
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posPayments()
    {
        return $this->hasMany(PosPayment::class, 'payment_method_id');
    }

}
