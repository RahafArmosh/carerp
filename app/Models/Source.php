<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $fillable = [
        'name',
        'order',
        'pipeline_id',
        'created_by',
    ];

    public function campaigns()
{
    return $this->hasMany(Campaign::class, 'source_id');
}
public function pipeline()
{
    return $this->belongsTo(Pipeline::class);
}
}
