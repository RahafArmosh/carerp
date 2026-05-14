<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_number',
        'expense_date',
        'vendor_id',
        'tax_id',
        'currency_id',
        'exchange_rate',
        'attachment',
        'payment_status',
        'created_by',
    ];

    public static $paymentStatues = [
        'Unpaid',
        '',
        'Partially Paid',
        '',
        'Paid',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'vendor_id');
    }

    public function tax()
    {
        // tax_id can be comma-separated, so we'll get the first one for the relationship
        // For multiple taxes, use getTaxIds() method
        return $this->hasOne(Tax::class, 'id', 'tax_id');
    }

    public function getTaxIds()
    {
        return $this->tax_id ? explode(',', $this->tax_id) : [];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DirectExpenseItem::class, 'direct_expense_id');
    }

    public function payments()
    {
        return $this->hasMany(DirectExpensePayment::class, 'direct_expense_id');
    }

    public function getTotalAmount()
    {
        $total = 0;
        foreach ($this->items as $item) {
            // Load sub product with product service and category if not already loaded
            if (!$item->relationLoaded('subProduct')) {
                $item->load('subProduct.productService.category');
            }
            
            $subProduct = $item->subProduct;
            $categoryType = optional($subProduct?->productService?->category)->type;
            $qty = $item->qty ?? 1;
            $amount = $item->amount ?? 0;
            
            // Multiply qty * amount if category type is "Qty product"
            if ($categoryType === 'Qty product' && $qty > 0) {
                $total += $amount * $qty;
            } else {
                $total += $amount;
            }
        }
        return $total;
    }

    public function getTotalPaid()
    {
        return $this->payments->sum('amount');
    }

    public function getDueAmount()
    {
        return $this->getTotalAmount() - $this->getTotalPaid();
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getTotalCurrencyAmount()
    {
        $total = 0;
        foreach ($this->items as $item) {
            // Load sub product with product service and category if not already loaded
            if (!$item->relationLoaded('subProduct')) {
                $item->load('subProduct.productService.category');
            }
            
            $subProduct = $item->subProduct;
            $categoryType = optional($subProduct?->productService?->category)->type;
            $qty = $item->qty ?? 1;
            $currencyAmount = $item->currency_amount ?? 0;
            
            // Multiply qty * amount if category type is "Qty product"
            if ($categoryType === 'Qty product' && $qty > 0) {
                $total += $currencyAmount * $qty;
            } else {
                $total += $currencyAmount;
            }
        }
        return $total;
    }

    public function getTotalTaxAmount()
    {
        // Get total amount (already includes qty * amount for Qty product)
        $totalAmount = $this->getTotalAmount();
        
        // Calculate tax on the total amount
        $taxIds = $this->getTaxIds();
        $taxAmount = 0;
        
        if (!empty($taxIds)) {
            foreach ($taxIds as $taxId) {
                $tax = Tax::find($taxId);
                if ($tax) {
                    $taxAmount += ($tax->rate / 100) * $totalAmount;
                }
            }
        }
        
        return $taxAmount;
    }
    
    public function getTotalTaxAmountCurrency()
    {
        // Get total currency amount (already includes qty * amount for Qty product)
        $totalCurrencyAmount = $this->getTotalCurrencyAmount();
        
        // Calculate tax on the total currency amount
        $taxIds = $this->getTaxIds();
        $taxAmount = 0;
        
        if (!empty($taxIds)) {
            foreach ($taxIds as $taxId) {
                $tax = Tax::find($taxId);
                if ($tax) {
                    $taxAmount += ($tax->rate / 100) * $totalCurrencyAmount;
                }
            }
        }
        
        return $taxAmount;
    }
}
