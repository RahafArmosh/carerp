<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();
            $creatorId = method_exists($user, 'creatorId') ? $user->creatorId() : (int) $user->id;

            $builder->where('countries.created_by', $creatorId);
        });
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

}
