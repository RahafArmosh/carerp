<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    use HasFactory;
    protected $table = 'general_ledger';
   protected $fillable = ['vid', 'account', 'type', 'debit', 'credit', 'ref_id', 'user_id', 'created_by', 'balance', 'send_date', 'reference','payment_id','direct_expense_payment_id','sub_product_id','deleted_qty','user_type','ref_number'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
