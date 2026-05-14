<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\GrnItem;
use App\Models\Grn;

class AsnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asn_id',
        'split_from_asn_item_id',
        'sub_product_id',
        'inventory_converted_at',
        'inventory_reversed_qty',
        'bill_id',
        'box_no',
        'supplier_po_no',
        'our_pro_id',
        'our_pro_no',
        'order_ref',
        'part_no',
        'description',
        'qty',
        'received_qty',
        'converted_qty',
        'discrepancy',
        'unit_price',
        'total_price',
        'unit_weight',
        'total_weight',
        'hs_code',
        'container_no',
        'dec_no',
        'dec_date',
        'origin',
    ];

    protected $casts = [
        'inventory_converted_at' => 'datetime',
    ];

    // Relationships
    public function asn()
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function splitFromItem()
    {
        return $this->belongsTo(AsnItem::class, 'split_from_asn_item_id');
    }

    public function splitChildItems()
    {
        return $this->hasMany(AsnItem::class, 'split_from_asn_item_id');
    }

    /**
     * Root ASN lines only (excludes rows created for partial bill splits).
     */
    public function scopeRootLines($query)
    {
        return $query->whereNull('split_from_asn_item_id');
    }

    public function pro()
    {
        return $this->belongsTo(Pro::class, 'our_pro_id');
    }

    public function grnItems()
    {
        return $this->hasMany(GrnItem::class, 'asn_item_id');
    }

    public function subProduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /**
     * Per-bill conversion records (how much of this item went to each bill).
     */
    public function asnItemBills()
    {
        return $this->hasMany(AsnItemBill::class, 'asn_item_id');
    }

    /**
     * Check if this ASN item is assigned to any GRN
     */
    public function isAssignedToGrn()
    {
        return $this->grnItems()->exists();
    }

    /**
     * Get the GRN(s) this item is assigned to
     */
    public function assignedGrns()
    {
        return $this->hasManyThrough(Grn::class, GrnItem::class, 'asn_item_id', 'id', 'id', 'grn_id');
    }

    // Automatically calculate fields when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate discrepancy: received_qty - qty
            $item->discrepancy = $item->received_qty - $item->qty;
            
            // Calculate total_price: received_qty * unit_price
            $item->total_price = $item->received_qty * $item->unit_price;
            
            // Calculate total_weight: received_qty * unit_weight
            $item->total_weight = $item->received_qty * $item->unit_weight;
        });
    }
}
