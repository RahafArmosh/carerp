<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grn_no',
        'asn_id',
        'bill_id',
        'supplier_id',
        'supplier_name',
        'grn_date',
        'status',
        'notes',
        'created_by',
        'assigned_to',
    ];

    protected $dates = [
        'grn_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships
    public function asn()
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Vender::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class, 'grn_id');
    }

    public function serialNumbers()
    {
        return $this->belongsToMany(SerialNumber::class, 'grn_serial_numbers', 'grn_id', 'serial_number_id')
            ->withPivot('grn_item_id')
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    // Helper methods
    public function getTotalPrice()
    {
        return $this->items->sum('total_price');
    }

    public function getTotalQty()
    {
        return $this->items->sum('qty');
    }

    public function getTotalReceivedQty()
    {
        return $this->items->sum('received_qty');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'received' => 'Partially Received',
            'manually_received' => 'Manually Received',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst((string)$this->status),
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'draft' => 'badge bg-secondary',
            'received' => 'badge bg-info',
            'manually_received' => 'badge bg-success',
            'completed' => 'badge bg-success',
            'cancelled' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }
}
