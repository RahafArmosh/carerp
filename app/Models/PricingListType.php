<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingListType extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function pricingLists()
    {
        return $this->hasMany(PricingList::class);
    }
}
