<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'vender_id',
        'currency',
        'bill_date',
        'due_date',
        'bill_id',
        'order_number',
        'category_id',
        'created_by',
        'salesman_id',
        'tax_id',
        'currency_id',
        'exchange_rate',
        'status',
        'payment_status',
        'send_date',
        'type',
        'discount_account_id'
    ];

    public static $statues = [
        'Draft',
        'Send To Approve',
        'Approved',
        '',
        'Sent',
        '',
        'Received'
    ];
    public static $paymentstatues = [
        'Unpaid',
        '',
        'Partialy Paid',
        '',
        'Paid',
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'vender_id');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'vender_id');
    }

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }

    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }


    public function accounts()
    {
        return $this->hasMany('App\Models\BillAccount', 'ref_id', 'id');
    }



    public function payments()
    {
        return $this->hasMany('App\Models\BillPayment', 'bill_id', 'id');
    }

    public function accountingDocuments()
    {
        return $this->hasMany(AccountingDocument::class);
    }

    public function getSubTotal()
    {
        $subTotal = 0;

        // Eager load subProducts with productService and category
        $items = $this->items()->with(['subProduct.productService.category'])->get();

        foreach ($items as $product) {
            $subProduct = $product->subProduct;

            // Check if subProduct exists and has the required relationships before accessing
            $isQtyProduct = false;
            if ($subProduct && $subProduct->productService && $subProduct->productService->category) {
                $isQtyProduct = $subProduct->productService->category->type === 'Qty product';
            }

            $price = $product->price;
            $qty = $isQtyProduct ? $product->quantity : 1;



            $lineTotal = max(($price * $qty), 0); // no negative subtotal
            $subTotal += $lineTotal;
        }

        return $subTotal;
    }
    public function getSubTotalExchange()
    {
        $subTotal = 0;

        // Eager load subProducts with productService and category
        $items = $this->items()->with(['subProduct.productService.category'])->get();

        foreach ($items as $product) {
            $subProduct = $product->subProduct;

            // Check if subProduct exists and has the required relationships before accessing
            $isQtyProduct = false;
            if ($subProduct && $subProduct->productService && $subProduct->productService->category) {
                $isQtyProduct = $subProduct->productService->category->type === 'Qty product';
            }

            $price = $product->exchange_price;
            $qty = $isQtyProduct ? $product->quantity : 1;



            $lineTotal = max(($price * $qty), 0); // no negative subtotal
            $subTotal += $lineTotal;
        }

        return $subTotal;
    }



    public function items()
    {
        return $this->hasMany('App\Models\BillProduct', 'bill_id', 'id');
    }


    // public function getTotalTax()
    // {
    //     $totalTax = 0;
    //     foreach($this->items as $product)
    //     {
    //         $taxes = Utility::totalTaxRate($product->tax);
    //         $totalTax += ($taxes / 100) * ($product->price * $product->quantity - $product->discount) ;

    //     }

    //     return $totalTax ;
    // }

    public function getTotalTax()
    {
        $taxData = Utility::getTaxData(); // Assume this returns all tax info
        $totalTax = 0;

        // Convert tax IDs to an array
        $taxArr = explode(',', $this->tax_id);
        $taxRate = 0;

        // Calculate total tax rate
        foreach ($taxArr as $taxId) {
            $taxRate += !empty($taxData[$taxId]['rate']) ? $taxData[$taxId]['rate'] : 0;
        }

        // Eager load related data
        $items = $this->items()->with('subProduct.productService.category')->get();

        foreach ($items as $product) {
            // Check if subProduct exists and has the required relationships before accessing
            $isQtyProduct = false;
            if ($product->subProduct && $product->subProduct->productService && $product->subProduct->productService->category) {
                $isQtyProduct = $product->subProduct->productService->category->type === 'Qty product';
            }

            $price = $product->price;
            $qty = $isQtyProduct ? $product->quantity : 1;

            // Subtract discount if present (assumed as flat amount)
            $discount = $product->discount * $qty ?? 0;
            $lineTotal = max(($price * $qty) - $discount, 0); // ensure no negative value

            // Apply tax
            $totalTax += ($taxRate / 100) * $lineTotal;
        }

        return $totalTax;
    }

    public function getTotalTaxExchange()
    {
        $taxData = Utility::getTaxData(); // Assume this returns all tax info
        $totalTax = 0;

        // Convert tax IDs to an array
        $taxArr = explode(',', $this->tax_id);
        $taxRate = 0;

        // Calculate total tax rate
        foreach ($taxArr as $taxId) {
            $taxRate += !empty($taxData[$taxId]['rate']) ? $taxData[$taxId]['rate'] : 0;
        }

        // Eager load related data
        $items = $this->items()->with('subProduct.productService.category')->get();

        foreach ($items as $product) {
            // Check if subProduct exists and has the required relationships before accessing
            $isQtyProduct = false;
            if ($product->subProduct && $product->subProduct->productService && $product->subProduct->productService->category) {
                $isQtyProduct = $product->subProduct->productService->category->type === 'Qty product';
            }

            $price = $product->exchange_price;
            $qty = $isQtyProduct ? $product->quantity : 1;

            // Subtract discount if present (assumed as flat amount)
            $discount = $product->exchange_discount * $qty ?? 0;
            $lineTotal = max(($price * $qty) - $discount, 0); // ensure no negative value

            // Apply tax
            $totalTax += ($taxRate / 100) * $lineTotal;
        }

        return $totalTax;
    }



    public function getTotalDiscount()
    {
        // Eager load items if not already loaded
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $totalDiscount = 0;
        foreach ($items as $item) {
            $totalDiscount += $item->discount * $item->quantity;
        }
        return $totalDiscount;
    }
    public function getTotalDiscountExchange()
    {
        // Eager load items if not already loaded
        $items = $this->items()->get();
        $totalDiscount = 0;
        foreach ($items as $item) {
            $totalDiscount += $item->exchange_discount * $item->quantity;
        }
        return $totalDiscount;
    }


    public function getAccountTotal()
    {
        // Use relationLoaded check to avoid redundant DB query
        $accounts = $this->relationLoaded('accounts') ? $this->accounts : $this->accounts()->get();

        // Filter every second item (0, 2, 4, ...) and sum the price
        return $accounts->filter(function ($item, $index) {
            return $index % 2 === 0;
        })->sum('price');
    }


    public function getTotal()
    {
        return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
    }
    public function getTotalExchange()
    {
        return ($this->getSubTotalExchange() - $this->getTotalDiscountExchange()) + $this->getTotalTaxExchange();
    }

    public function getDue()
    {
        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount');

        return ($this->getTotal() - $totalPaid) - $this->billTotalDebitNote();
    }

    /**
     * Get due amount specifically for expenses
     * Expenses don't have debit notes, so we only calculate total - payments
     */
    public function getExpenseDue()
    {
        if ($this->type !== 'Expense') {
            return $this->getDue(); // Fallback to regular getDue for non-expenses
        }

        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount');

        return $this->getAccountTotal() - $totalPaid;
    }
    public function getDueExchange()
    {
        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount_in_currency');

        return ($this->getTotalExchange() - $totalPaid) - $this->billTotalDebitNoteExchange();
    }

    public function getTotalPaid()
    {
        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount');

        return $totalPaid;
    }


    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function debitNote()
    {
        return $this->hasMany('App\Models\DebitNote', 'bill', 'id');
    }

    public function billTotalDebitNote()
    {
        return $this->debitNote->sum('amount');
    }
    public function billTotalDebitNoteExchange()
    {
        return $this->debitNote->sum('amount_in_currency');
    }

    public function billTotalDebitNoteCurrency()
    {
        $total = $this->debitNote->sum('amount_in_currency');

        return $total;
    }
    public function lastPayments()
    {
        return $this->hasOne('App\Models\BillPayment', 'id', 'bill_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public function subProducts()
    {
        return $this->hasMany(SubProduct::class, 'bill_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function statusChanges()
    {
        return $this->hasMany(BillStatusChange::class);
    }

    /**
     * Bills linked to ASN (Advance Shipping Notice) via asn_bills pivot.
     */
    public function asnBills()
    {
        return $this->hasMany(AsnBill::class, 'bill_id');
    }

    /**
     * ASNs connected to this bill.
     */
    public function asns()
    {
        return $this->hasManyThrough(Asn::class, AsnBill::class, 'bill_id', 'id', 'id', 'asn_id');
    }

    public function getDueInCurrency()
    {
        // If no currency set, return the due in default currency
        if ($this->currency_id === null) {
            return $this->getDue();
        }

        // Use loaded payments if available
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        // Sum paid amount in default currency
        $totalPaid = $payments->sum('amount_in_currency');

        // Get debit note total in default currency
        $debitNoteTotal = $this->billTotalDebitNoteCurrency();

        // Get exchange rate, default to 1 if not valid
        $rate = ($this->exchange_rate && $this->exchange_rate > 0) ? $this->exchange_rate : 1;

        // Calculate due in bill currency
        $dueInDefaultCurrency = (($this->getTotal() / $rate) - $totalPaid) - $debitNoteTotal;

        return round($dueInDefaultCurrency, 2);
    }

    public function refunds()
    {
        return $this->hasMany('App\Models\Refund', 'bill_id', 'id');
    }

    public function billTotalRefund()
    {
        return $this->refunds()->sum('amount');
    }

    public function billTotalRefundExchange()
    {
        return $this->refunds()->sum('amount_in_currency');
    }

    public function billTotalRefundCurrency()
    {
        $refunds = $this->refunds;
        $totalRefund = 0;

        foreach ($refunds as $refund) {
            if ($refund->currency_id == $this->currency_id) {
                $totalRefund += $refund->amount_in_currency;
            } else {
                $totalRefund += $refund->amount;
            }
        }

        return $totalRefund;
    }
}
