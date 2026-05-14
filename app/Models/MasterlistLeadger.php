<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterlistLeadger extends Model
{
    use HasFactory;

    protected $table = 'masterlist_leadger';

    protected $fillable = [
        'product_service_id',
        'warehouse_id',
        'qty',
        'qty_out',
        'movement_type',
        'document_type',
        'document_id',
        'created_by'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function productService()
    {
        return $this->belongsTo(ProductService::class, 'product_service_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Helper Methods for Quantities
    |--------------------------------------------------------------------------
    */

    public static function quantitiesByType($productServiceId, $movementType, $warehouseId = null)
    {
        return self::query()
            ->select('document_type', 'document_id', 'movement_type')
            ->selectRaw('SUM(qty) as total_qty')
            ->where('product_service_id', $productServiceId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('movement_type', $movementType)
            ->groupBy('document_type', 'document_id', 'movement_type')
            ->get();
    }

    public static function addFree($product_service_id,$warehouse_id,$qtyToAdd,$document_type,$document_id,$created_by = null) 
    {
        // $remaining = $qtyToAdd;

        // // 1️⃣ Reduce on_order for this PRO
        // $onOrderEntries = self::where('product_service_id', $product_service_id)
        //     ->where('warehouse_id', $warehouse_id)
        //     ->where('movement_type', 'on_order')
        //     ->where('document_type', 'PRO')
        //     ->where('document_id', $pro_id)
        //     ->where('qty', '>', 0)
        //     ->orderBy('created_at', 'asc') // FIFO
        //     ->get();

        // foreach ($onOrderEntries as $entry) {

        //     if ($remaining <= 0) {
        //         break;
        //     }

        //     if ($entry->qty >= $remaining) {

        //         $entry->qty -= $remaining;
        //         $entry->save();

        //         $remaining = 0;

        //     } else {

        //         $remaining -= $entry->qty;

        //         $entry->qty = 0;
        //         $entry->save();
        //     }
        // }

        $ledger = self::firstOrCreate(
            [
                'product_service_id' => $product_service_id,
                'warehouse_id' => $warehouse_id,
                'movement_type' => 'free',
                'document_type' => $document_type,
                'document_id' => $document_id,
                'created_by' => $created_by
            ],
            [
                'qty' => 0
            ]
        );
        $ledger->increment('qty', $qtyToAdd);

        return $ledger;
    }

    public static function addBooked($product_service_id, $warehouse_id, $qtyToBook, $document_type, $document_id, $created_by = null,$target_document_type,$target_document)
    {
        $remaining = $qtyToBook;

        // 1️⃣ Get free entries for this product and warehouse (FIFO)
        $freeEntries = self::where('product_service_id', $product_service_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('created_by', $created_by)
            ->where('movement_type', 'free')
            ->where('document_type',$target_document_type)
            ->where('document_id',$target_document)
            ->where('qty', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($freeEntries as $entry) {
            if ($remaining <= 0) break;

            if (($entry->qty - $entry->qty_out) >= $remaining) {
                // Reduce current free entry
                $entry->qty_out += $remaining;
                $entry->save();
                $remaining = 0;
            } else {
                // Consume entire entry
                $remaining -= $entry->qty;
                $entry->qty_out = $entry->qty;
                $entry->save();
            }
        }

        // if ($remaining > 0) {
        //     throw new \Exception("Not enough free quantity to book. Remaining: {$remaining}");
        // }

        $ledger = self::firstOrCreate(
            [
                'product_service_id' => $product_service_id,
                'warehouse_id' => $warehouse_id,
                'movement_type' => 'booked',
                'document_type' => $document_type,
                'document_id' => $document_id,
                'created_by' => $created_by
            ],
            [
                'qty' => 0,
            ]
        );

        $ledger->increment('qty', $qtyToBook);
        return $ledger;
    }

    public static function addSold($product_service_id, $warehouse_id, $qtyToSell, $so_type,$so_id, $document_type, $document_id, $created_by = null)
    {
        // this is only for the so 


        $remaining = $qtyToSell;

        // ]Get booked entries for this product, warehouse, and specific SO (FIFO)
        $bookedEntries = self::where('product_service_id', $product_service_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('movement_type', 'booked')
            ->where('document_type', $so_type)        // Only booked from SO
            ->where('document_id', $so_id)        // Only booked from this SO
            ->where('qty', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();
        // dd($bookedEntries);
        foreach ($bookedEntries as $entry) {
            if ($remaining <= 0) break;

            if (($entry->qty - $entry->qty_out) >= $remaining) {
                // Reduce current booked entry
                $entry->qty_out += $remaining;
                $entry->save();
                $remaining = 0;
            } else {
                // Consume entire entry
                $remaining -= $entry->qty;
                $entry->qty_out += $entry->qty;
                $entry->save();
            }
        }

        // if ($remaining > 0) {
        //     throw new \Exception("Not enough booked quantity from SO#{$so_id} to sell. Remaining: {$remaining}");
        // }

        $ledger = self::firstOrCreate(
            [
                'product_service_id' => $product_service_id,
                'warehouse_id' => $warehouse_id,
                'movement_type' => 'sold',
                'document_type' => $document_type,
                'document_id' => $document_id,
                'created_by' => $created_by
            ],
            [
                'qty' => 0,
            ]
        );

        $ledger->increment('qty', $qtyToSell);

        return $ledger;

    }


    // public static function returnSoldToBooked($product_service_id, $warehouse_id, $qty, $sold_doc_type, $sold_doc_id, $created_by = null)
    // {
    //     $remaining = $qty;

    //     $soldEntries = self::where('product_service_id', $product_service_id)
    //         ->where('warehouse_id', $warehouse_id)
    //         ->where('movement_type', 'sold')
    //         ->where('document_type', $sold_doc_type)
    //         ->where('document_id', $sold_doc_id)
    //         ->where('qty', '>', 0)
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     foreach ($soldEntries as $entry) {

    //         if ($remaining <= 0) break;

    //         if ($entry->qty >= $remaining) {
    //             $entry->qty -= $remaining;
    //             $entry->save();
    //             $remaining = 0;
    //         } else {
    //             $remaining -= $entry->qty;
    //             $entry->qty = 0;
    //             $entry->save();
    //         }
    //     }

    //     if ($remaining > 0) {
    //         throw new \Exception("Not enough sold quantity to return.");
    //     }

    //     // add back to booked
    //     return self::addBooked(
    //         $product_service_id,
    //         $warehouse_id,
    //         $qty,
    //         $sold_doc_type,
    //         $sold_doc_id,
    //         $created_by
    //     );
    // }

    public static function returnBookedToFree(
        $product_service_id,
        $warehouse_id,
        $qty,
        $book_type,
        $book_document_id,
        $free_type,
        $free_document_id,
        $created_by
    ) {

        $remaining = $qty;

        // Get booked entries created by the same user
        $bookedEntries = self::where('product_service_id', $product_service_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('movement_type', 'booked')
            ->where('document_type', $book_type)
            ->where('document_id', $book_document_id)
            ->where('created_by', $created_by)
            ->get();

        foreach ($bookedEntries as $entry) {

            if ($remaining <= 0) {
                break;
            }

            if ($entry->qty >= $remaining) {

                $entry->qty -= $remaining;
                $entry->save();
                $remaining = 0;

            } else {

                $remaining -= $entry->qty;
                $entry->qty = 0;
                $entry->save();
            }
        }

        // if ($remaining > 0) {
        //     throw new \Exception("Not enough booked quantity to release. Remaining: {$remaining}");
        // }

        // Add back to free
        $ledger = self::firstOrCreate(
            [
                'product_service_id' => $product_service_id,
                'warehouse_id' => $warehouse_id,
                'movement_type' => 'free',
                'document_type' => $free_type,
                'document_id' => $free_document_id,
                'created_by' => $created_by
            ],
            [
                'qty' => 0
            ]
        );

        $ledger->increment('qty', $qty);

        return $ledger;
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        return $query;
    }
    public function getNetQtyAttribute()
    {
        return $this->qty - $this->qty_out;
    }

    
}