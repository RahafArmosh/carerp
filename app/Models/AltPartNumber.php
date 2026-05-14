<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AltPartNumber extends Model
{
    protected $fillable = [
        'part_number',
        'alternative_part_number',
        'priority',
        'is_active',
        'created_by'
    ];

    public function part()
    {
        return $this->belongsTo(SubProduct::class, 'part_number', 'chassis_no');
    }

    public function alternativePart()
    {
        return $this->belongsTo(SubProduct::class, 'alternative_part_number', 'chassis_no');
    }
    
}
