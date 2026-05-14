<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CompanyCurrencyCountryDefaultsSeeder
{
    /**
     * Default countries for a new company (tenant-scoped via created_by).
     */
    public static function defaultCountryNames(): array
    {
        return [
            'United Arab Emirates',
            'United States',
            'United Kingdom',
            'Canada',
            'Saudi Arabia',
            'Qatar',
            'Kuwait',
            'Oman',
            'Bahrain',
        ];
    }

    /**
     * Clone system currencies (created_by = 0), seed default countries, and set company currency settings.
     */
    public static function seedForCompany(int $companyUserId): void
    {
        $systemCurrencies = Currency::withoutGlobalScopes()
            ->where('created_by', 0)
            ->get();

        foreach ($systemCurrencies as $row) {
            Currency::withoutGlobalScopes()->firstOrCreate(
                ['created_by' => $companyUserId, 'code' => $row->code],
                [
                    'name' => $row->name,
                    'symbol' => $row->symbol,
                    'exchange_rate' => $row->exchange_rate,
                ]
            );
        }

        foreach (self::defaultCountryNames() as $name) {
            Country::withoutGlobalScopes()->firstOrCreate(
                ['created_by' => $companyUserId, 'name' => $name]
            );
        }

        $defaultCode = 'AED';
        $defaultSymbol = 'Dhs';

        $tenantAed = Currency::withoutGlobalScopes()
            ->where('created_by', $companyUserId)
            ->where('code', $defaultCode)
            ->first();

        if ($tenantAed) {
            $defaultSymbol = $tenantAed->symbol ?: $defaultSymbol;
        } else {
            $first = Currency::withoutGlobalScopes()
                ->where('created_by', $companyUserId)
                ->orderBy('id')
                ->first();
            if ($first) {
                $defaultCode = $first->code;
                $defaultSymbol = $first->symbol ?: $first->code;
            }
        }

        $now = now();
        foreach ([
            'site_currency' => $defaultCode,
            'site_currency_symbol' => $defaultSymbol,
            'site_currency_symbol_position' => 'post',
        ] as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['name' => $name, 'created_by' => $companyUserId],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
