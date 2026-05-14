<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsnItemBill extends Model
{
    protected $table = 'asn_item_bills';

    protected $fillable = [
        'asn_item_id',
        'bill_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function asnItem()
    {
        return $this->belongsTo(AsnItem::class, 'asn_item_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
