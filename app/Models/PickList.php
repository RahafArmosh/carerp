<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_order_id',
        'customer_id',
        'packing_ref',
        'pick_list_date',
        'picked_by',
        'assigned_to',
        'assign_note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'pick_list_date' => 'date',
    ];

    /**
     * Get the sale order that owns the pick list
     */
    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'sales_order_id');
    }

    /**
     * Get the customer for the pick list
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the user who created the pick list
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who picked the items
     */
    public function picker()
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    /**
     * Get the user assigned to pick the items
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all items for the pick list
     */
    public function items()
    {
        return $this->hasMany(PickListItem::class, 'pick_list_id');
    }

    /**
     * Get the packing list if converted
     */
    public function packingList()
    {
        return $this->hasOne(PackingList::class, 'pick_list_id');
    }

    /**
     * Get status change logs for the pick list
     */
    public function statusLogs()
    {
        return $this->hasMany(PickListStatusLog::class, 'pick_list_id');
    }
}
