<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsnBill extends Model
{
    protected $table = 'asn_bills';

    protected $fillable = [
        'asn_id',
        'bill_id',
    ];

    public function asn()
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
