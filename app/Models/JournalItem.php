<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class JournalItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'journal',
        'account',
        'debit',
        'credit',
        'sub_product_id'
    ];

    public function accounts()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'account');
    }


}
