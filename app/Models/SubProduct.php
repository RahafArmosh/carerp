<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use App\Models\warehouse;
use App\Models\Pos;

class SubProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Flag constants
    const FLAG_ORDERED = 0;      // Ordered
    const FLAG_PURCHASED = 1;    // Purchased
    const FLAG_CANCELLED = 2;    // Cancelled
    const FLAG_CONSIGNMENT = 3;  // Consignment (converted from ASN without bill)

    protected $fillable = [
        'chassis_no',
        'product_id',
        'sale_price',
        'purchase_price',
        'created_by',
        'bill_id',
        'asn_id',
        'flag',
        'invoice_id',
        'pos_id',
        'sale_order_id',
        'so_qty_reserved',
        'quantity',
        'SP_sku',
        'warehouse_id',
        'booked',
        'price_multiplier',
        'price_rule_id',
        'note',
        'import_source'
    ];

    public function productService()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    /** @deprecated Use chassis_no; kept for forms and legacy code reading product_no */
    public function getProductNoAttribute(): ?string
    {
        return $this->attributes['chassis_no'] ?? null;
    }

    public function setProductNoAttribute(?string $value): void
    {
        $this->attributes['chassis_no'] = $value;
    }

    // public function bill_products() {
    //     return $this->belongsToMany(BillProduct::class, 'bill_sub_product');
    // }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function asn()
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    // public function exteriorColor()
    // {
    //     return $this->belongsTo(Color::class, 'exterior_color_id');
    // }

    // public function interiorColor()
    // {
    //     return $this->belongsTo(Color::class, 'interior_color_id');
    // }
    // public function country()
    // {
    //     return $this->belongsTo(Country::class);
    // }

    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, 'record_id'); // 'record_id' is the sub-product ID
    }

    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class, 'sub_product_id');
    }
    public function billProducts()
    {
        return $this->hasMany(BillProduct::class, 'sub_product_id');
    }
    public function priceRule()
    {
        return $this->belongsTo(PriceRule::class, 'price_rule_id');
    }

    public function get_price_list_sale_price()
    {
        $rule = $this->priceRule;
        if ($rule) {
            if ($rule->base_price_source == 'purchase') {
                $basePrice = ($this->productService->avg_cost ?? 0) > 0
                ? $this->productService->avg_cost
                : $this->productService->purchase_price;
            } else {
                $basePrice = $this->sale_price;
            }
            $newprice = match ($rule->price_mode) {
                'discount' => $basePrice * (1 - $rule->value / 100),
                'formula'  => $basePrice * (1 + $rule->value / 100),
                'fixed'    => $rule->value,
            };
            if ($rule->apply_99) {
                $newprice = round($newprice) - 0.01;
            }
        } else {
            $newprice = $this->sale_price;
        }

        return $newprice;
    }

    public function get_price_list_sale_price_with_Vat()
    {
        $newprice = $this->get_price_list_sale_price();
        $vat_rate = $this->productService->tax_id ? Tax::where('id', $this->productService->tax_id)->first()->rate : 0;
        $newprice = $newprice * (1 + $vat_rate / 100);

        return $newprice;
    }

    public function warehouse()
    {
        return $this->belongsTo(warehouse::class);
    }

    public function images()
    {
        return $this->hasMany(SubProductImage::class, 'sub_product_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Append gallery images from uploaded files (same storage as SubProductController gallery).
     *
     * @param  iterable<int|string, mixed>  $files
     */
    public function appendUploadedGalleryImages(iterable $files): void
    {
        $fileList = is_array($files) ? $files : iterator_to_array($files);
        $sortBase = (int) $this->images()->max('sort_order');
        $userId = Auth::user()?->creatorId() ?? (int) ($this->created_by ?? 0);
        if ($userId === 0) {
            return;
        }
        $dir = 'uploads/sub_product_image';

        foreach ($fileList as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $imageSize = $file->getSize();
            if (Utility::updateStorageLimit($userId, $imageSize) != 1) {
                continue;
            }
            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'image';
            $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg'));
            $fileName = 'sp_'.$this->id.'_'.uniqid('', true).'_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $base).'.'.$ext;

            $subRequest = Request::create('/', 'POST', [], [], ['pro_image' => $file]);
            $res = Utility::upload_file($subRequest, 'pro_image', $fileName, $dir, []);
            if (($res['flag'] ?? 0) != 1) {
                continue;
            }
            $sortBase++;
            SubProductImage::create([
                'sub_product_id' => $this->id,
                'file_name' => $fileName,
                'sort_order' => $sortBase,
            ]);
        }
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (SubProduct $subProduct) {
            $subProduct->images()->get()->each->delete();
        });
    }

    public function alternatives()
    {
        return $this->hasMany(
            AltPartNumber::class,
            'part_number',
            'part_number'
        )
        ->where('is_active', true)
        ->orderBy('priority');
    }


    /**
     * Check if this sub product is consignment (flag = 3)
     */
    public function isConsignment()
    {
        return $this->flag == self::FLAG_CONSIGNMENT;
    }

    /**
     * @deprecated Use isConsignment() instead
     */
    public function isInventory()
    {
        return $this->isConsignment();
    }

    /**
     * Get flag label
     */
    public function getFlagLabel()
    {
        return match($this->flag) {
            self::FLAG_ORDERED => 'Ordered',
            self::FLAG_PURCHASED => 'Purchased',
            self::FLAG_CANCELLED => 'Cancelled',
            self::FLAG_CONSIGNMENT => 'Consignment',
            default => 'Unknown',
        };
    }
}
