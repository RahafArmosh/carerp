<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Voucher extends Model
{

    protected $fillable = ['id',"customer_id", 'amount', 'valid_until','active','chart_of_account_id', 'created_by'];

    public function scopeActive($query){
        return $query->where('active', true);
    }

    public function scopeValid($query){
        return $query->where('valid_until', '>=', Carbon::now());
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }

    // Check if this voucher is from a POS refund
    public function posRefund(){
        return $this->hasOne(PosRefund::class, 'voucher_id');
    }

    // Helper method to check if voucher is from refund
    public function isFromRefund(){
        return $this->posRefund()->exists();
    }
}
