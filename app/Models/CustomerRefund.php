<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerRefund extends Model
{
    protected $fillable = [
        'date', 'amount', 'account_id', 'chart_account_id', 'customer_id', 'description', 'category_id','invoice_id', 'payment_id',
         'recurring', 'payment_method', 'reference', 'add_receipt', 'created_by', 'currency_id', 'currency_rate', 'amount_in_currency'
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function invoice()
    {
        return $this->hasOne('App\Models\Invoice', 'id', 'invoice_id');
    }
}
