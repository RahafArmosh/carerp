<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'from_warehouse',
        'to_warehouse',
        'status',
        'request_date',
        'notes',
        'attachment',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Generate unique request number
     */
    public static function generateRequestNumber()
    {
        $prefix = 'TR-';
        $year = date('Y');
        $lastRequest = self::where('request_number', 'like', $prefix . $year . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = intval(substr($lastRequest->request_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get transfers for this request
     */
    public function transfers()
    {
        return $this->hasMany(WarehouseTransfer::class, 'request_id');
    }

    /**
     * Get from warehouse
     */
    public function fromWarehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'from_warehouse');
    }

    /**
     * Get to warehouse
     */
    public function toWarehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'to_warehouse');
    }

    /**
     * Get creator user
     */
    public function creator()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_by');
    }

    /**
     * Get approver user
     */
    public function approver()
    {
        return $this->hasOne('App\Models\User', 'id', 'approved_by');
    }

    /**
     * Get logs for this request
     */
    public function logs()
    {
        return $this->hasMany('App\Models\PosLog', 'reference_id')
            ->where('type', 'transfer_request')
            ->orderBy('created_at', 'desc');
    }
}
