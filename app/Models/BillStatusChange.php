<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillStatusChange extends Model
{
    use HasFactory;
    protected $fillable = [
        'bill_id',
        'status',
        'payment_status',
        'changed_at'
    ];
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}
