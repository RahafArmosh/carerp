<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if ($user->type === 'super admin') {
                return;
            }

            $creatorId = method_exists($user, 'creatorId') ? $user->creatorId() : (int) $user->id;

            $builder->where(function (Builder $q) use ($creatorId) {
                $q->where('currencies.created_by', $creatorId)
                    ->orWhere('currencies.created_by', 0);
            });
        });
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bankTransfers()
    {
        return $this->hasMany(BankTransfer::class);
    }

    public function isSystemCurrency(): bool
    {
        return (int) $this->created_by === 0;
    }
}
