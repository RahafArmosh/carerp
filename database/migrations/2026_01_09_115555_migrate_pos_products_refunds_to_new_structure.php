<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Group existing refunds by voucher_id and refund_batch_id to create parent refunds
        $existingRefunds = \DB::table('pos_products_refunds')
            ->where(function($query) {
                $query->whereNotNull('voucher_id')
                      ->orWhereNotNull('refund_batch_id');
            })
            ->get()
            ->groupBy(function ($refund) {
                // Group by voucher_id first, then by refund_batch_id
                return ($refund->voucher_id ?? 'no_voucher') . '_' . ($refund->refund_batch_id ?? 'no_batch');
            });

        foreach ($existingRefunds as $groupKey => $refunds) {
            $firstRefund = $refunds->first();
            
            // Calculate total amount for this group
            $totalAmount = $refunds->sum('return_price');
            
            // Create parent refund
            $posRefundId = \DB::table('pos_refunds')->insertGetId([
                'pos_id' => $firstRefund->pos_id,
                'voucher_id' => $firstRefund->voucher_id,
                'total_amount' => $totalAmount,
                'description' => 'Migrated from pos_products_refunds',
                'created_by' => $firstRefund->created_by ?? 1,
                'created_at' => $firstRefund->created_at ?? now(),
                'updated_at' => $firstRefund->updated_at ?? now(),
            ]);
            
            // Create child items
            foreach ($refunds as $refund) {
                \DB::table('pos_refund_items')->insert([
                    'refund_id' => $posRefundId,
                    'pos_products_id' => $refund->pos_products_id,
                    'product_no' => $refund->product_no,
                    'quantity' => $refund->quantity,
                    'return_price' => $refund->return_price,
                    'combo_id' => $refund->combo_id,
                    'price_list_id' => $refund->price_list_id,
                    'created_at' => $refund->created_at ?? now(),
                    'updated_at' => $refund->updated_at ?? now(),
                ]);
            }
        }
        
        // Handle refunds without voucher_id or refund_batch_id (individual refunds)
        $individualRefunds = \DB::table('pos_products_refunds')
            ->whereNull('voucher_id')
            ->whereNull('refund_batch_id')
            ->get();
            
        foreach ($individualRefunds as $refund) {
            // Create parent refund for each individual refund
            $posRefundId = \DB::table('pos_refunds')->insertGetId([
                'pos_id' => $refund->pos_id,
                'voucher_id' => null,
                'total_amount' => $refund->return_price,
                'description' => $refund->description ?? 'Migrated from pos_products_refunds',
                'created_by' => $refund->created_by ?? 1,
                'created_at' => $refund->created_at ?? now(),
                'updated_at' => $refund->updated_at ?? now(),
            ]);
            
            // Create child item
            \DB::table('pos_refund_items')->insert([
                'refund_id' => $posRefundId,
                'pos_products_id' => $refund->pos_products_id,
                'product_no' => $refund->product_no,
                'quantity' => $refund->quantity,
                'return_price' => $refund->return_price,
                'combo_id' => $refund->combo_id,
                'price_list_id' => $refund->price_list_id,
                'created_at' => $refund->created_at ?? now(),
                'updated_at' => $refund->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is one-way - we keep both structures
        // If needed, we can reverse by recreating pos_products_refunds from pos_refunds and pos_refund_items
    }
};
