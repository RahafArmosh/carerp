<?php

namespace App\Models;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'model_id',
        'sku',
        'sale_price',
        'sale_price_base',
        'purchase_price',
        'tax_id',
        'category_id',
        'unit_id',
        'type',
        'created_by',
        'brand_id',
        'sub_brand_id',
    ];

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id')->first();
    }

    public function unit()
    {
        return $this->hasOne('App\Models\ProductServiceUnit', 'id', 'unit_id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function tax($taxes)
    {
        $taxArr = explode(',', $taxes);

        $taxes  = [];
        foreach($taxArr as $tax)
        {
            $taxes[] = Tax::find($tax);
        }

        return $taxes;
    }

    public function taxRate($taxes)
    {
        $taxArr  = explode(',', $taxes);
        $taxRate = 0;
        foreach($taxArr as $tax)
        {
            $tax     = Tax::find($tax);
            $taxRate += $tax->rate;
        }

        return $taxRate;
    }

    public static function taxData($taxes)
    {
        $taxArr = explode(',', $taxes);

        $taxes = [];
        foreach($taxArr as $tax)
        {
            $taxesData = Tax::find($tax);
            $taxes[]   = !empty($taxesData) ? $taxesData->name : '';
        }

        return implode(',', $taxes);
    }

    public static function getallproducts()
    {
        return ProductService::select('product_services.*', 'c.name as categoryname')
            ->where('product_services.type', '=', 'product')
            ->leftjoin('product_service_categories as c', 'c.id', '=', 'product_services.category_id')
            ->where('product_services.created_by', '=', Auth::user()->creatorId())
            ->orderBy('product_services.id', 'DESC');
    }

    public function getTotalProductQuantity()
    {
        $totalquantity = $purchasedquantity = $posquantity = 0;
        $authuser = Auth::user();
        $product_id = $this->id;
        $purchases = Bill::where('created_by', $authuser->creatorId());

        if ($authuser->isUser())
        {
            $purchases = $purchases->where('warehouse_id', $authuser->warehouse_id);
        }

        foreach($purchases->get() as $purchase)
        {
            $purchaseditem = BillProduct::select('quantity')->where('bill_id', $purchase->id)->where('product_id', $product_id)->first();

            $purchasedquantity += $purchaseditem != null ? $purchaseditem->quantity : 0;

        }

        $poses = Pos::where('created_by', $authuser->creatorId());

        if ($authuser->isUser())
        {
            $pos = $poses->where('warehouse_id', $authuser->warehouse_id);
        }

        foreach($poses->get() as $pos)
        {
            $positem = PosProduct::select('quantity')->where('pos_id', $pos->id)->where('product_id', $product_id)->first();
            $posquantity += $positem != null ? $positem->quantity : 0;
        }

        $totalquantity = $purchasedquantity - $posquantity;

        //        dd($totalquantity);

        return $totalquantity;
    }

    public static function tax_id($product_id)
    {
        $tax = DB::table('product_services')
        ->where('id', $product_id)
        ->where('created_by', Auth::user()->creatorId())
        ->select('tax_id')
        ->first();

        return ($tax != null) ? $tax->tax_id : 0;
    }

    public function warehouseProduct($product_id,$warehouse_id)
    {

        $product=WarehouseProduct::where('warehouse_id',$warehouse_id)->where('product_id',$product_id)->first();

        return !empty($product)?$product->quantity:0;
    }

    public function subProducts() {
        return $this->hasMany(SubProduct::class,'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductServiceImage::class, 'product_service_id')->orderBy('sort_order');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subBrand()
    {
        return $this->belongsTo(VehicleModel::class, 'sub_brand_id');
    }


    // Set up the cascade delete in the boot method
    protected static function boot() {
        parent::boot();

        // Cascade delete sub-products when a product is deleted
        static::deleting(function($product) {
            // When soft-deleting a ProductService, do NOT delete sub-products (they are not soft-deletable).
            // Only cascade on force delete.
            if (method_exists($product, 'isForceDeleting') && $product->isForceDeleting()) {
                $product->subProducts()->withTrashed()->forceDelete();
                foreach ($product->images as $img) {
                    $img->delete();
                }
            }
        });
    }

    public function categories()
    {
        return $this->hasManyThrough(ProductServiceCategory::class, SubProduct::class);
    }

    public function manufacturers()
    {
        return $this->belongsToMany(Manufacturer::class);
    }


    public function childProducts()
    {
        return $this->belongsToMany(Product::class, 'manufacturer_product_services', 'product_id', 'subproduct_id');
    }

    public function parentProducts()
    {
        return $this->belongsToMany(Product::class, 'manufacturer_product_services', 'subproduct_id', 'product_id');
    }

    public function getBookedQuantity()
    {
        // Get the IDs of booked sub-products (status 1, 2, or 3)
        $subProductIds = SubProduct::where('product_id', $this->id)
            ->whereIn('booked', [1, 2, 3])
            ->pluck('id')
            ->toArray();

        // If no booked sub-products, return 0
        if (empty($subProductIds)) {
            return 0;
        }

        // Get the sum of quantities from booked sub-products
        $subProductQty = SubProduct::where('product_id', $this->id)
            ->whereIn('booked', [1, 2, 3])
            ->sum('quantity');

        // Get quantities from invoices for these booked sub-products
        $invoiceQty = InvoiceProduct::whereIn('sub_product_id', $subProductIds)->sum('quantity');
        
        // Get quantities from POS for these booked sub-products
        $posQty = PosProduct::whereIn('sub_product_id', $subProductIds)->sum('quantity');
        
        // Get quantities from manufacturer requests for these booked sub-products
        $manufacturerQty = CarAccessoryRequestItem::whereIn('accessory_id', $subProductIds)->sum('quantity');

        return $subProductQty + $invoiceQty + $posQty + $manufacturerQty;
    }
    public function getFreeQuantity()
    {
        // $totalSubProductQty = $this->subProducts()->where('warehouse_id', $warehouse_id)->sum('quantity');

        $subProductIds = SubProduct::where('product_id', $this->id)->where('booked', 0)->sum('quantity');
        return $subProductIds;
    }

    public function warehousePriceLists()
    {
        return $this->hasMany(WarehouseProductPriceList::class, 'productservice_id');
    }

    public function getWarehouseSalePrice($warehouseId)
    {
        $warehousePrice = $this->warehousePriceLists()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $warehousePrice ? $warehousePrice->sale_price : $this->sale_price;
    }

    public function getTotalSubProducts(){
        $subProductIds = SubProduct::where('product_id', $this->id)->count();
        return $subProductIds;
    }
    public function ledgers()
    {
        return $this->hasMany(MasterlistLeadger::class, 'product_service_id');
    }
    public function setWarehouse($warehouseId)
    {
        $this->warehouse_id = $warehouseId;
        return $this;
    }

    public function getTotalFreeAttribute()
    {
        return $this->ledgers()
            ->when($this->warehouse_id, function ($q) {
                $q->where('warehouse_id', $this->warehouse_id);
            })
            ->where('movement_type', 'free')
            ->where('qty', '>', 0)
            ->sum('qty');
    }
    public function getTotalBookedAttribute()
    {
        return $this->ledgers()
            ->when($this->warehouse_id, function ($q) {
                $q->where('warehouse_id', $this->warehouse_id);
            })
            ->where('movement_type', 'booked')
            ->where('qty', '>', 0)
            ->sum('qty');
    }

    public function getTotalSoldAttribute()
    {
        return $this->ledgers()
            ->when($this->warehouse_id, function ($q) {
                $q->where('warehouse_id', $this->warehouse_id);
            })
            ->where('movement_type', 'sold')
            ->where('qty', '>', 0)
            ->sum('qty');
    }

    public function getAvailableAttribute()
    {
        return $this->total_free + $this->total_booked;
    }

    public function Master_list_ledgers()
    {
        return $this->hasMany(MasterlistLeadger::class, 'product_service_id');
    }
    public function pricingLists()
    {
        return $this->hasMany(PricingList::class, 'product_service_id');
    }
    
    public function proItems()
    {
        return $this->hasMany(\App\Models\ProItem::class, 'product_id');
    }

    public function remainingOrderedQuantity()
    {
        return $this->proItems->sum(fn($item) => $item->calculateRemainingQty());
    }

}
