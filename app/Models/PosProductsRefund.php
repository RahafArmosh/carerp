<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosProductsRefund extends Model
{
    protected $fillable = ['pos_id','pos_products_id','quantity','return_price','description','product_no','combo_id','price_list_id','voucher_id','refund_batch_id','created_by'];

     // A refund belongs to a POS
    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    // A refund belongs to a POS Product
    public function posProduct()
    {
        return $this->belongsTo(PosProduct::class, 'pos_products_id');
    }

    // A refund may belong to a Combo
    public function combo()
    {
        return $this->belongsTo(ComboOffer::class, 'combo_id');
    }

    // A refund may belong to a Price List
    public function priceList()
    {
        return $this->belongsTo(PriceRule::class, 'price_list_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get all refunds in the same batch
     */
    public function batchRefunds()
    {
        return $this->hasMany(PosProductsRefund::class, 'refund_batch_id', 'refund_batch_id');
    }

    /**
     * Scope to query refunds by batch ID
     */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('refund_batch_id', $batchId);
    }
}