<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickListItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pick_list_id',
        'bin_location',
        'part_no',
        'description',
        'req_qty',
        'picked_qty',
        'tick',
    ];

    protected $casts = [
        'req_qty' => 'decimal:2',
        'picked_qty' => 'decimal:2',
        'tick' => 'boolean',
    ];

    /**
     * Get the pick list that owns the item
     */
    public function pickList()
    {
        return $this->belongsTo(PickList::class, 'pick_list_id');
    }
}
