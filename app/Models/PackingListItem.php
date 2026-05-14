<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingListItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'packing_list_id',
        'box_no',
        'part_no',
        'description',
        'packed_qty',
        'box_l',
        'box_w',
        'box_h',
        'box_weight',
    ];

    protected $casts = [
        'packed_qty' => 'decimal:2',
        'box_l' => 'decimal:2',
        'box_w' => 'decimal:2',
        'box_h' => 'decimal:2',
        'box_weight' => 'decimal:2',
    ];

    /**
     * Get the packing list that owns the item
     */
    public function packingList()
    {
        return $this->belongsTo(PackingList::class, 'packing_list_id');
    }
}
