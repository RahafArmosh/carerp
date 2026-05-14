<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class InvoiceExpense extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['account_id', 'invoice_id', 'amount', 'created_by','description', 'currency_id', 'currency_rate', 'amount_in_currency'];

    // Relation with Account
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    // Relation with Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Relation with User (Created By)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation with Currency
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
