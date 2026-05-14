<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asn_no',
        'supplier_id',
        'supplier_name',
        'supplier_code',
        'supplier_inv_no',
        'container_no',
        'dec_no',
        'boe_number',
        'dec_date',
        'asn_date',
        'warehouse_id',
        'currency_id',
        'exchange_rate',
        'status',
        'bill_id',
        'created_by',
    ];

    protected $dates = [
        'dec_date',
        'asn_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Vender::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(AsnItem::class, 'asn_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /**
     * All bills created from this ASN (one ASN can have many bills).
     */
    public function asnBills()
    {
        return $this->hasMany(AsnBill::class, 'asn_id');
    }

    public function bills()
    {
        return $this->hasManyThrough(Bill::class, AsnBill::class, 'asn_id', 'id', 'id', 'bill_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }

    public function grns()
    {
        return $this->hasMany(Grn::class, 'asn_id');
    }

    public function subProducts()
    {
        return $this->hasMany(SubProduct::class, 'asn_id');
    }

    // Helper methods
    /**
     * Only root ASN lines count toward shipment totals (split bill lines are excluded).
     */
    protected function rootItemsForTotals()
    {
        $this->loadMissing('items');

        return $this->items->whereNull('split_from_asn_item_id');
    }

    public function getTotalPrice()
    {
        return $this->rootItemsForTotals()->sum('total_price');
    }

    public function getTotalQty()
    {
        return $this->rootItemsForTotals()->sum('qty');
    }

    public function getTotalReceivedQty()
    {
        return $this->rootItemsForTotals()->sum('received_qty');
    }

    public function getTotalWeight()
    {
        return $this->rootItemsForTotals()->sum('total_weight');
    }

    /**
     * Update status based on received quantities.
     * created | sent | partially_received | fully_received
     * (does not override custom statuses like manually_received)
     */
    public function updateStatusBasedOnItems(): void
    {
        $this->loadMissing('items');
        if ($this->items->isEmpty()) {
            $this->status = $this->status ?: 'created';
            $this->save();
            return;
        }

        // If status was set manually to a non-automatic state, keep it
        if ($this->status === 'manually_received') {
            return;
        }

        $rootItems = $this->items->whereNull('split_from_asn_item_id');
        $totalQty = (float) $rootItems->sum('qty');
        $totalReceived = (float) $rootItems->sum('received_qty');

        if ($totalReceived <= 0) {
            $this->status = 'created';
        } elseif ($totalReceived >= $totalQty && $totalQty > 0) {
            $this->status = 'fully_received';
        } else {
            $this->status = 'partially_received';
        }
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'created' => 'Created',
            'sent' => 'Sent',
            'partially_received' => 'Partially Received',
            'fully_received' => 'Fully Received',
            'manually_received' => 'Manually Received',
            default => ucfirst((string)$this->status),
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'created' => 'badge bg-info',
            'sent' => 'badge bg-primary',
            'partially_received' => 'badge bg-warning',
            'fully_received' => 'badge bg-success',
            'manually_received' => 'badge bg-success',
            default => 'badge bg-secondary',
        };
    }
}
