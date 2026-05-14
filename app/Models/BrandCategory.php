<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandCategory extends Pivot
{
    use SoftDeletes;

    protected $table = 'brand_category';

    public $incrementing = true;
}
