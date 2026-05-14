<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PosLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_type',
        'type',
        'reference_id',
        'pos_id',
        'user_id',
        'warehouse_id',
        'customer_id',
        'product_id',
        'product_no',
        'quantity',
        'old_value',
        'new_value',
        'description',
        'ip_address',
        'created_by',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    /**
     * Relationship to Pos model
     */
    public function pos()
    {
        return $this->belongsTo(Pos::class, 'pos_id');
    }

    /**
     * Relationship to User model
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship to Customer model
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relationship to warehouse model
     */
    public function warehouse()
    {
        return $this->belongsTo(warehouse::class, 'warehouse_id');
    }

    /**
     * Relationship to ProductService model
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }

    /**
     * Static method to log POS actions
     */
    public static function logAction($actionType, $data = [])
    {
        $logData = [
            'action_type' => $actionType,
            'type' => $data['type'] ?? null, // e.g., 'warehouse', 'payment_method', 'combo', 'voucher', 'price_list', 'transfer', 'pos', 'pos_refund'
            'reference_id' => $data['reference_id'] ?? null, // ID of the related model
            'user_id' => Auth::id() ?? $data['user_id'] ?? 0,
            'pos_id' => $data['pos_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'product_no' => $data['product_no'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'description' => $data['description'] ?? null,
            'ip_address' => request()->ip(),
            'created_by' => Auth::user()->creatorId() ?? $data['created_by'] ?? 0,
        ];

        return self::create($logData);
    }

    /**
     * Get badge color based on action type
     */
    public function getActionBadgeColor()
    {
        $colors = [
            'create_order' => 'success',
            'delete_order' => 'danger',
            'create_warehouse' => 'success',
            'update_warehouse' => 'warning',
            'delete_warehouse' => 'danger',
            'create_transfer' => 'info',
            'delete_transfer' => 'danger',
            'approve_transfer' => 'success',
            'update_transfer_quantity' => 'warning',
            'create_transfer_request' => 'success',
            'approve_transfer_request' => 'success',
            'create_payment_method' => 'success',
            'update_payment_method' => 'warning',
            'delete_payment_method' => 'danger',
            'create_price_list' => 'success',
            'update_price_list' => 'warning',
            'delete_price_list' => 'danger',
            'create_combo' => 'success',
            'update_combo' => 'warning',
            'delete_combo' => 'danger',
            'create_voucher' => 'success',
            'update_voucher' => 'warning',
            'delete_voucher' => 'danger',
            'create_pos_refund' => 'info',
            'stock_count' => 'primary',
            'stock_count_summary' => 'primary',
        ];

        return $colors[$this->action_type] ?? 'secondary';
    }
}
