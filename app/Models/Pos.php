<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;


class Pos extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'pos_id',
        'customer_id',
        'warehouse_id',
        'pos_date',
        'category_id',
        'status',
        'shipping_display',
        'created_by',
        'user_id',
        'tax_id',
        'discount',
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }
    public function warehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'warehouse_id');
    }

    public function posPayment()
    {
        return $this->hasOne('App\Models\PosPayment','pos_id','id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\PosProduct', 'pos_id', 'id');
    }
    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }
    
    public function products()
    {
        return $this->hasMany(PosProduct::class, 'pos_id');
    }

    public function refunds()
    {
        return $this->hasMany(PosRefund::class, 'pos_id');
    }

    // Legacy relationship for backward compatibility
    public function refundItems()
    {
        return $this->hasManyThrough(PosRefundItem::class, PosRefund::class, 'pos_id', 'refund_id');
    }

    public function payments()
    {
        return $this->hasMany(PosPayment::class, 'pos_id');
    }

    public function paymentRefunds()
    {
        return $this->hasMany(PosPaymentRefund::class, 'pos_id');
    }

    /**
     * Relationship to User (cashier who made this POS transaction)
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get raw subtotal BEFORE any discounts (price * quantity only)
     * This is the base price sum before discount and tax
     */
    public function getRawSubTotal()
    {
        $subTotal = 0;
        
        // Load items if not already loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // Return 0 if no items or items collection is empty
        if (!$this->items || $this->items->isEmpty()) {
            return $subTotal;
        }
        
        foreach($this->items as $product)
        {
            // Use combo_price if combo exists, otherwise use regular price
            $basePrice = $product->price;
            if ($product->compo_id != 0 && $product->compo_id != '0' && $product->combo_price !== null) {
                $basePrice = $product->combo_price;
            }
            
            // Raw subtotal: price * quantity (no discount applied)
            $subTotal += ($basePrice * $product->quantity);
        }

        return $subTotal;
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        
        // Load items if not already loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // Return 0 if no items or items collection is empty
        if (!$this->items || $this->items->isEmpty()) {
            return $subTotal;
        }
        
        foreach($this->items as $product)
        {
            // Use combo_price if combo exists, otherwise use regular price
            $basePrice = $product->price;
            if ($product->compo_id != 0 && $product->compo_id != '0' && $product->combo_price !== null) {
                $basePrice = $product->combo_price;
            }
            
            $subTotal += (($basePrice - ($basePrice*($product->discount/100))) * $product->quantity);
        }

        return $subTotal;
    }

    public function getTotalDiscount()
    {
        // Return the discount stored in the pos table
        return (float)($this->discount ?? 0);
    }
    
    /**
     * Get total discount amount from all products in this POS
     * This calculates the actual discount amount (not percentage) from product-level discounts
     */
    public function getProductDiscountTotal()
    {
        // Load items if not already loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // Return 0 if no items or items collection is empty
        if (!$this->items || $this->items->isEmpty()) {
            return 0;
        }
        
        $totalProductDiscount = 0;
        
        foreach($this->items as $product)
        {
            // Use combo_price if combo exists, otherwise use regular price
            $basePrice = $product->price;
            if ($product->compo_id != 0 && $product->compo_id != '0' && $product->combo_price !== null) {
                $basePrice = $product->combo_price;
            }
            
            // Calculate discount amount per item: basePrice * (discount/100)
            $discountPerItem = $basePrice * ($product->discount / 100);
            
            // Multiply by quantity to get total discount for this product
            $totalProductDiscount += $discountPerItem * $product->quantity;
        }
        
        return $totalProductDiscount;
    }
    
    public function getVoucherTotal()
    {
        // Get voucher total from GeneralLedger entries
        $voucherTotal = 0;
        $voucherLedgerEntries = \App\Models\GeneralLedger::where('ref_id', $this->id)
            ->where('reference', 'POS Payment')
            ->where('type', 'LIKE', '%Voucher Payment_%')
            ->where('debit', '>', 0)
            ->get();
        
        foreach($voucherLedgerEntries as $entry) {
            $voucherTotal += (float)$entry->debit;
        }
        
        return $voucherTotal;
    }

    // public function getTotalTax()
    // {

    //     $totalTax = 0;
    //     foreach($this->items as $product)
    //     {

    //         $taxes = Utility::totalTaxRate($product->tax);

    //         $totalTax += ($taxes / 100) * ($product->price * $product->quantity) ;

    //     }

    //     return $totalTax;
    // }


    public function getTotalTax()
    {
        // Load items if not already loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // Return 0 if no tax_id or no items
        if (empty($this->tax_id) || !$this->items || $this->items->isEmpty()) {
            return 0;
        }
        
        // Calculate tax on subtotal AFTER discount (matching user requirement)
        // Formula: (Raw Subtotal - Total Discount) * Tax Rate
        $rawSubtotal = $this->getRawSubTotal();
        $productDiscount = $this->getProductDiscountTotal();
        $overallDiscount = $this->getTotalDiscount();
        $totalDiscount = $productDiscount + $overallDiscount;
        
        // Subtotal after discount
        $subtotalAfterDiscount = $rawSubtotal - $totalDiscount;
        
        // Get tax rate from tax_id
        $taxData = Utility::getTaxData();
        $taxArr = explode(',', $this->tax_id);
        $taxRate = 0;
        foreach ($taxArr as $tax) {
            $tax = trim($tax);
            if (!empty($tax) && !empty($taxData[$tax]['rate'])) {
                $taxRate += $taxData[$tax]['rate'];
            }
        }
        
        // Calculate tax on subtotal after discount
        $totalTax = $subtotalAfterDiscount * ($taxRate / 100);
        
        // Round tax amount to 2 decimal places (normal rounding)
        $totalTax = round($totalTax, 2);
        
        return $totalTax;
    }
    //pos dashboard

    public function getTotal()
    {
        // Calculate total: (Raw Subtotal - Discount) + Tax - Vouchers
        // Formula: (Subtotal Before Discount - Total Discount) + Tax - Vouchers
        $rawSubtotal = $this->getRawSubTotal();
        $productDiscount = $this->getProductDiscountTotal();
        $overallDiscount = $this->getTotalDiscount();
        $totalDiscount = $productDiscount + $overallDiscount;
        $tax = $this->getTotalTax();
        $vouchers = $this->getVoucherTotal();
        
        // Total = (Raw Subtotal - Discount) + Tax - Vouchers
        $total = ($rawSubtotal - $totalDiscount) + $tax - $vouchers;
        
        // Normalize to 2 decimal places (no rounding to whole number)
        return round($total, 2);
    }
    
    /**
     * Get total discount (product discount + overall discount)
     */
    public function getTotalDiscountAmount()
    {
        $productDiscount = $this->getProductDiscountTotal();
        $overallDiscount = $this->getTotalDiscount();
        return $productDiscount + $overallDiscount;
    }

    public static function getPosProductsData($month = '')
    {
        if ($month == 'true') {
            $posProducts = \DB::table('pos_products')
                ->select(
                    'pos_products.id as pos_product_id',
                    \DB::raw('SUM(pos_products.quantity) as quantity'),
                    \DB::raw('SUM(pos_products.discount) as total_discount'),
                    \DB::raw('pos_products.tax as tax'),
                    \DB::raw('SUM(pos_products.price) as price')
                )
                ->leftJoin('pos', 'pos_products.pos_id', 'pos.id')
                ->where(\DB::raw('MONTH(pos.created_at)'), '=', [date('m')])
            ->where('pos.created_by', \Auth::user()->creatorId())
                ->groupBy('pos_products.id')
                ->get()
                ->keyBy('pos_product_id');
        } else {
            $posProducts = \DB::table('pos_products')
                ->select(
                    'pos_products.id as pos_product_id',
                    \DB::raw('SUM(pos_products.quantity) as quantity'),
                    \DB::raw('SUM(pos_products.discount) as total_discount'),
                    \DB::raw('pos_products.tax as tax'),
                    \DB::raw('SUM(pos_products.price) as price')
                )
                ->leftJoin('pos', 'pos_products.pos_id', 'pos.id')
            ->where('pos.created_by', \Auth::user()->creatorId())
                ->groupBy('pos_products.id')
                ->get()
                ->keyBy('pos_product_id');
        }
        $total = 0;

        foreach($posProducts as $pos)
        {
            $getTaxData = Utility::getTaxData();
            $totalTaxPrice = 0;
            if (!empty($pos->tax)) {
                foreach (explode(',', $pos->tax) as $tax) {
                    $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $pos->price, $pos->quantity , $pos->total_discount);
                    $totalTaxPrice += $taxPrice;
                }
            }

            $total += ($pos->price  * $pos->quantity) + $totalTaxPrice - $pos->total_discount;

        }
        return $total;

    }

    public static function totalPosAmount($month = false)
    {
        $posAmount = self::getPosProductsData($month);
        return Auth::user()->priceFormat($posAmount);
    }
    // public static function totalPosAmount($month = false)
    // {

    //     $poses = new Pos();
    //     $poses = $poses->where('created_by', '=', Auth::user()->creatorId());
    //     if($month)
    //     {
    //         $poses = $poses->whereRaw('MONTH(created_at) = ?', [date('m')]);
    //     }

    //     $posAmount = 0;

    //     foreach($poses->get() as $key => $pos)
    //     {
    //         $posAmount += $pos->getTotal();
    //     }

    //     return Auth::user()->priceFormat($posAmount);
    // }



    public static function getPosReportChart()
    {
        $poses = Pos::whereDate('created_at', '>', Carbon::now()->subDays(10))
            ->where('created_by', '=', Auth::user()->creatorId())->orderBy('created_at')
            ->get()->groupBy(
            function ($val){
                return Carbon::parse($val->created_at)->format('dm');
            }
        );
        $total = [];
        if(!empty($poses) && count($poses) > 0)
        {
            foreach($poses as $day => $onesale)
            {
                $totals = 0;
                foreach($onesale as $pos)
                {
                    $totals += $pos->getTotal();
                }
                $total[$day] = $totals;
            }
        }
        $m = date("m");
        $d = date("d");
        $y = date("Y");
        for($i = 0; $i <= 9; $i++)
        {
            $date                  = date('Y-m-d', mktime(0, 0, 0, $m, ($d - $i), $y));
            $posesArray['label'][] = $date;
            $date                  = date('dm', strtotime($date));
            $posesArray['value'][] = array_key_exists($date, $total) ? $total[$date] : 0;;
        }

        return $posesArray;
    }




}



