<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosRefund extends Model
{
    protected $fillable = [
        'pos_id',
        'voucher_id',
        'total_amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    // A refund belongs to a POS
    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    // A refund has a voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    // A refund has many items
    public function items()
    {
        return $this->hasMany(PosRefundItem::class, 'refund_id');
    }

    // Creator user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
