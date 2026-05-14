<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_no',
        'quotation_date',
        'customer_id',
        'warehouse_id',       
        'tax_id',
        'discount_type',
        'discount_value',
        'subtotal',
        'tax_amount',
        'total',
        'delivery_location',
        'price_group',        
        'created_by',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function priceGroup()
    {
        return $this->belongsTo(PricingListType::class, 'price_group');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    // Auto-generate quotation number
    protected static function booted()
    {
        static::creating(function ($quotation) {
            if (!empty($quotation->quotation_no)) {
                return;
            }

            do {
                $quotation->quotation_no = 'Q-' . strtoupper(Str::random(6));
            } while (
                self::where('quotation_no', $quotation->quotation_no)->exists()
            );
        });
    }
    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class, 'converted_quotation_id');
    }

    public function is_converted(): bool
    {
        return $this->saleOrders()->exists();
    }

}
