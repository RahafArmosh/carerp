<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'sub_product_id',
        'bill_id',
        'invoice_id',
        'pos_id',
        'warehouse_transfer_id',
        'qty_in',
        'qty_out',
        'avg_cost',
        'cost_price',
        'activity',
        'use_id',
        'item',
        'created_by'
    ];

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(ProductService::class);
    }

    public function Subproduct()
    {
        return $this->belongsTo(SubProduct::class, 'sub_product_id');
    }

    // Relationship with Bill (for stock in)
    public function bill()
    {
        return $this->belongsTo(Bill::class)->withTrashed();
    }

    // Relationship with Invoice (for stock out)
    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    // Relationship with POS (for POS sales)
    public function pos()
    {
        return $this->belongsTo(Pos::class)->withTrashed();
    }

    public function warehouseTransfer()
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with Customer (for SALES activity)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'use_id');
    }

    // Relationship with Vendor (for PURCHASE activity)
    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'use_id');
    }

    // Relationship with ASN via bill
    public function asn()
    {
        return $this->hasOneThrough(Asn::class, Bill::class, 'id', 'bill_id', 'bill_id', 'id');
    }

    /**
     * Net unit sale price for an invoice line (list price minus per-unit discount).
     */
    public static function netUnitSoldPriceFromInvoiceProduct($invoiceProduct): float
    {
        if (!$invoiceProduct) {
            return 0.0;
        }
        $price = (float) ($invoiceProduct->price ?? 0);
        $discount = (float) ($invoiceProduct->discount ?? 0);

        return max(0.0, $price - $discount);
    }

    /**
     * Net unit sale price for a POS line: use combo unit price when present, then apply line discount %.
     */
    public static function netUnitSoldPriceFromPosProduct($posProduct): float
    {
        if (!$posProduct) {
            return 0.0;
        }
        $base = $posProduct->combo_price !== null
            ? (float) $posProduct->combo_price
            : (float) ($posProduct->price ?? 0);
        $pct = (float) ($posProduct->discount ?? 0);
        $net = $base - ($base * $pct / 100.0);

        return max(0.0, $net);
    }
}
