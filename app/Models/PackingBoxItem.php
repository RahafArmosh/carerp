<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingBoxItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'packing_list_id',
        'box_no',
        'part_no',
        'description',
        'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    /**
     * Get the packing list that owns the box item
     */
    public function packingList()
    {
        return $this->belongsTo(PackingList::class, 'packing_list_id');
    }
}
