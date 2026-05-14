<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'customer_id',
        'issue_date',
        'due_date',
        'ref_number',
        'status',
        'category_id',
        'created_by',
        'salesman_id',
        'tax_id',
        'type',
        'currency_id',
        'exchange_rate',
        'bank_account_id',
        'discount_account_id',
        'send_date',
    ];

    public static $statues = [
        'Draft',
        'Send To Approve',
        'Approved',
        "",
        'Sent',
        "",
        'Delivered'
    ];
    public static $paymentstatues = [
        'Unpaid',
        '',
        'Partialy Paid',
        '',
        'Paid',
    ];
    protected static $dates = ['issue_date', 'due_date'];

    /**
     * Scope for searching invoices by various fields
     */
    public function scopeSearch($query, $search)
    {
        // Extract the numeric part
        $normalizedNumber = null;
        if (preg_match('/\d+/', $search, $matches)) {
            $normalizedNumber = ltrim($matches[0], '0');
            if ($normalizedNumber === '') {
                $normalizedNumber = null; // Avoid non-existent ID 0
            }
        }

        return $query->where(function ($q) use ($search, $normalizedNumber) {
            if ($normalizedNumber !== null) {
                $q->orWhere('invoice_id', 'like', "%{$normalizedNumber}%");
            }
            $q->orWhere('issue_date', 'like', "%{$search}%")
                ->orWhere('due_date', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('payment_status', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%");
                });
        });
    }



    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\InvoiceProduct', 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\InvoicePayment', 'invoice_id', 'id');
    }
    public function bankPayments()
    {
        return $this->hasMany('App\Models\InvoiceBankTransfer', 'invoice_id', 'id')->where('status', '!=', 'Approved');
    }
    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }
    public function driver()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'driver_id');
    }
    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'bank_account_id');
    }



    // private static $getTotal = NULL;
    // public static function getTotal(){
    //     if(self::$getTotal == null){
    //         $Invoice = new Invoice();
    //         self::$getTotal = $Invoice->invoiceTotal();
    //     }
    //     return self::$getTotal;
    // }

    public function getTotal()
    {
        if (!is_null($this->tax_id) && Tax::where('id', $this->tax_id)->exists()) {
            return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
        }

        // Default return value if tax_id is null or tax record not found
        return $this->getSubTotal() - $this->getTotalDiscount();
    }
    public function getDaysDifferenceAttribute()
    {
        if ($this->issue_date && $this->due_date) {
            $days = Carbon::parse($this->issue_date)->diffInDays(Carbon::parse($this->due_date), false);
            $days = max($days, 1) + 1;
            return $days;
        }
        return null; // Return null if dates are missing
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach ($this->items as $product) {

            $subTotal += ($product->price * $product->quantity);
        }
        // Add expenses to the subtotal
        $expenseTotal = \App\Models\InvoiceExpense::where('invoice_id', $this->id)->sum('amount');
        $subTotal += $expenseTotal;
        return $subTotal;
    }


    // public function getTotalTax()
    // {
    //     $totalTax = 0;
    //     foreach($this->items as $product)
    //     {
    //         $taxes = Utility::totalTaxRate($product->tax);


    //         $totalTax += ($taxes / 100) * ($product->price * $product->quantity - $product->discount) ;
    //     }

    //     return $totalTax;
    // }

    public function getTotalTax()
    {
        $taxData = Utility::getTaxData(); // All available tax definitions
        $totalTaxRate = 0;

        // Parse tax IDs safely
        $taxArr = explode(',', (string) $this->tax_id);

        foreach ($taxArr as $taxId) {
            $rate = isset($taxData[$taxId]['rate']) ? (float) $taxData[$taxId]['rate'] : 0;
            $totalTaxRate += $rate;
        }

        // Load related data
        $items = $this->items()->with('subProduct.productService.category')->get();
        $totalTax = 0;

        foreach ($items as $product) {
            $category = optional($product->subProduct->productService->category);
            $isQtyProduct = $category && $category->type === 'Qty product';

            $price = (float) $product->price;
            $qty = $isQtyProduct ? (float) $product->quantity : 1;
            $discount = $product->discount ?? 0;

            $lineTotal = max(($price * $qty) - ($discount * $qty), 0);

            // Apply tax
            $totalTax += ($totalTaxRate / 100) * $lineTotal;
        }

        return round($totalTax, 2);
    }
    public function getTotalDiscount()
    {
        // Eager load items if not already loaded
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items->sum('discount');
    }

    public function getDue()
    {
        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount');

        $result = round($this->getTotal() - $totalPaid - $this->invoiceTotalCreditNote(), 2);
        return $result == 0 ? 0 : $result;
    }

    public function getTotalPaid()
    {
        // If relation is already loaded, avoid re-querying
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        $totalPaid = $payments->sum('amount');

        return $totalPaid;
    }

    public static function change_status($invoice_id, $status)
    {

        $invoice = Invoice::find($invoice_id);
        $invoice->status = $status;
        $invoice->update();
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function creditNote()
    {

        return $this->hasMany('App\Models\CreditNote', 'invoice', 'id');
    }

    public function invoiceTotalCreditNote()
    {
        return $this->creditNote->sum('amount');
    }

    public function lastPayments()
    {
        return $this->hasOne('App\Models\InvoicePayment', 'id', 'invoice_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public function products()
    {
        return $this->hasMany(InvoiceProduct::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function accountingDocuments()
    {
        return $this->hasMany(AccountingDocument::class);
    }

    public function statusChanges()
    {
        return $this->hasMany(InvoiceStatusChange::class);
    }

    public function expenses()
    {
        return $this->hasMany(InvoiceExpense::class);
    }

    /**
     * Get the total amount before tax and discount (sum of all item prices, ignoring discount/tax/quantity)
     */
    public function getTotalItemPrice()
    {
        return $this->items->sum('price');
    }

    /**
     * Get the total expenses for this invoice
     */
    public function getTotalExpenses()
    {
        return $this->expenses->sum('amount');
    }

    /**
     * Get the total amount before tax and discount, including expenses (sum of all item prices + expenses)
     */
    public function getTotalAmount()
    {
        return $this->getTotalItemPrice() + $this->getTotalExpenses();
    }

    public function allocations()
    {
        return $this->hasMany(InvoicePayment::class);
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
        $debitNoteTotal = $this->invoiceTotalDebitNoteCurrency();

        // Get exchange rate, default to 1 if not valid
        $rate = ($this->exchange_rate && $this->exchange_rate > 0) ? $this->exchange_rate : 1;

        // Calculate due in bill currency
        $dueInDefaultCurrency = (($this->getTotal() / $rate) - $totalPaid) - $debitNoteTotal;

        return round($dueInDefaultCurrency, 2);
    }
    public function invoiceTotalDebitNoteCurrency()
    {
        $total = $this->creditNote->sum('amount_in_currency');

        return $total;
    }

    public function getTotalExchange()
    {
        return ($this->getSubTotalExchange() - $this->getTotalDiscountExchange()) + $this->getTotalTaxExchange();
    }
    public function getSubTotalExchange()
    {
        $subTotal = 0;

        // Eager load subProducts with productService and category
        $items = $this->items()->with(['subProduct.productService.category'])->get();

        foreach ($items as $product) {
            $subProduct = $product->subProduct;

            $isQtyProduct = optional($subProduct->productService->category)->type === 'Qty product';

            $price = $product->exchange_price;
            $qty = $isQtyProduct ? $product->quantity : 1;



            $lineTotal = max(($price * $qty), 0); // no negative subtotal
            $subTotal += $lineTotal;
        }

        return $subTotal;
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
            $isQtyProduct = optional($product->subProduct->productService->category)->type === 'Qty product';

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

    public function refunds()
    {
        return $this->hasMany('App\Models\CustomerRefund', 'invoice_id', 'id');
    }

    public function invoiceTotalRefund()
    {
        return $this->refunds()->sum('amount');
    }

    public function invoiceTotalRefundExchange()
    {
        return $this->refunds()->sum('amount_in_currency');
    }

    public function invoiceTotalRefundCurrency()
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

    public function invoiceTotalRefundInInvoiceCurrency()
    {
        $refunds = $this->refunds;
        $totalRefund = 0;
        
        foreach ($refunds as $refund) {
            if ($refund->currency_id == $this->currency_id) {
                // Same currency as invoice, use amount_in_currency
                $totalRefund += $refund->amount_in_currency;
            } else {
                // Different currency, convert AED amount to invoice currency
                if ($this->currency_id && $this->exchange_rate > 0) {
                    $totalRefund += $refund->amount / $this->exchange_rate;
                } else {
                    // No invoice currency or exchange rate, use AED amount
                    $totalRefund += $refund->amount;
                }
            }
        }
        
        return $totalRefund;
    }
}
