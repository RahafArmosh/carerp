<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pro extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Pro $pro) {
            if (!$pro->isForceDeleting()) {
                $pro->items()->delete();
            }
        });
    }

    protected $fillable = [
        'pro_no',
        'advance_sale_order_id',
        'supplier_id',
        'supplier_name',
        'supplier_code',
        'po_date',
        'supplier_proforma_no',
        'supplier_proforma_date',
        'our_order_ref',
        'supplier_ref',
        'eta_date',
        'currency_id',
        'exchange_rate',
        'status',
        'created_by',
    ];

    protected $dates = [
        'po_date',
        'supplier_proforma_date',
        'eta_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Vender::class, 'supplier_id');
    }

    public function advanceSaleOrder()
    {
        return $this->belongsTo(AdvanceSaleOrder::class, 'advance_sale_order_id');
    }

    public function items()
    {
        return $this->hasMany(ProItem::class, 'pro_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // Helper methods
    public function getTotalAmount()
    {
        return $this->items->sum('total_amount');
    }

    public function getTotalOrderQty()
    {
        return $this->items->sum('order_qty');
    }

    public function getTotalSuppliedQty()
    {
        return $this->items->sum('supplied_qty');
    }

    public function getTotalRemainingQty()
    {
        return $this->items->sum('remaining_qty');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'created', 'open' => 'badge bg-info',
            'partially_received' => 'badge bg-warning',
            'closed', 'delivered' => 'badge bg-success',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Check if status is open
     */
    public function isOpen()
    {
        return $this->status === 'open' || $this->status === 'created';
    }

    /**
     * Check if status is delivered
     */
    public function isDelivered()
    {
        return $this->status === 'delivered' || $this->status === 'closed';
    }

    /**
     * Compute and update status based on items' supplied vs order qty.
     * created: no items received
     * partially_received: some received but not all
     * closed: all received
     */
    public function updateStatusBasedOnItems(): void
    {
        $this->loadMissing('items');
        if ($this->items->isEmpty()) {
            $this->status = $this->status ?: 'created';
            $this->save();
            return;
        }

        $totalOrder = (float) $this->items->sum('order_qty');
        $totalSupplied = (float) $this->items->sum('supplied_qty');

        if ($totalSupplied <= 0) {
            $this->status = 'created';
        } elseif ($totalSupplied >= $totalOrder && $totalOrder > 0) {
            $this->status = 'closed';
        } else {
            $this->status = 'partially_received';
        }
        $this->save();
    }

    /**
     * Human-readable status label for UI.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'created' => 'Created',
            'partially_received' => 'Partially Received',
            'closed' => 'Closed / Completed',
            'open' => 'Created',
            'delivered' => 'Closed / Completed',
            default => ucfirst((string)$this->status),
        };
    }
}
