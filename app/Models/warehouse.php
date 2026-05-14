<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'logo',
        'company_name',
        'address',
        'city',
        'city_zip',
        'country_id',
        'tax_id',
        'created_by',
    ];

    public static function warehouse_id($warehouse_name)
    {
        $warehouse = DB::table('warehouses')
        ->where('id', $warehouse_name)
        ->where('created_by', Auth::user()->creatorId())
        ->select('id')
        ->first();
        return ($warehouse != null) ? $warehouse->id : 0;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
    public function GetFreeQuantity($product_num)
    {
        return \App\Models\SubProduct::where('chassis_no', $product_num)
            ->where('warehouse_id', $this->id)
            ->sum('quantity');    
    }
   public function GetPrice($product_num)
    {
        $subProduct = \App\Models\SubProduct::where('chassis_no', $product_num)
            ->where('warehouse_id', $this->id)
            ->latest()
            ->first();

        return $subProduct->get_price_list_sale_price();
    }
    public function GetProduct_id($product_num)
    {
        $subProduct = \App\Models\SubProduct::where('chassis_no', $product_num)
            ->where('warehouse_id', $this->id)
            ->latest()
            ->first();
        return $subProduct->product_id;
    }

    public function subProducts()
    {
        return $this->hasMany(SubProduct::class, 'warehouse_id');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_warehouses', 'warehouse_id', 'user_id');
    }

}
