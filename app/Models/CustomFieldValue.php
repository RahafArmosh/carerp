<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'record_id',
        'field_id',
        'value',
    ];

    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'field_id'); // 'field_id' links to the custom field
    }
}
