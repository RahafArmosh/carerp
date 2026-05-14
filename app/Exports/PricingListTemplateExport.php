<?php

namespace App\Exports;

use App\Models\PricingListType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PricingListTemplateExport implements FromArray, WithHeadings
{
    protected $pricingTypes;

    public function __construct()
    {
        // Fetch pricing list types names only
        $this->pricingTypes = PricingListType::where('created_by', \Auth::user()->creatorId())->pluck('name')->toArray();
    }

    public function headings(): array
    {
        return array_merge(
            ['part_number'],
            $this->pricingTypes
        );
    }

    public function array(): array
    {
        // Example rows only
        return [
            array_merge(['ABC123'], array_fill(0, count($this->pricingTypes), '')),
            array_merge(['DEF456'], array_fill(0, count($this->pricingTypes), '')),
            array_merge(['XYZ789'], array_fill(0, count($this->pricingTypes), '')),
        ];
    }
}
