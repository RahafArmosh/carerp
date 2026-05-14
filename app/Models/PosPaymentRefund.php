<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPaymentRefund extends Model
{
    protected $fillable = ['id','pos_id','amount','description','creator_id'] ;
    
    // A refund belongs to a POS
    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }
    

}
