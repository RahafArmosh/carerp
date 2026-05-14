<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'packing_list_no',
        'customer_id',
        'sale_order_id',
        'pick_list_id',
        'packing_ref',
        'packing_list_date',
        'packed_by',
        'status',
        'created_by',
    ];

    protected $casts = [
        'packing_list_date' => 'date',
    ];

    /**
     * Get the customer that owns the packing list
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sale order if linked
     */
    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    /**
     * Get the pick list if linked
     */
    public function pickList()
    {
        return $this->belongsTo(PickList::class, 'pick_list_id');
    }

    /**
     * Get the user who packed the items
     */
    public function packer()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    /**
     * Get the user who created the packing list
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all items for the packing list
     */
    public function items()
    {
        return $this->hasMany(PackingListItem::class, 'packing_list_id');
    }
}
