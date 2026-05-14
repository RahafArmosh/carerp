<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MissingProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $missingProducts;

    public function __construct(Collection $missingProducts)
    {
        $this->missingProducts = $missingProducts;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->missingProducts;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Row Number',
            'SKU',
            'Product Name',
            'Brand Name',
            'Sub Brand Name',
            'Category Name',
            'Quantity',
            'Sale Price',
            'Purchase Price',
            'Discount',
            'Product No',
            'Status Message',
            'Custom Fields',
        ];
    }

    /**
     * @param mixed $product
     * @return array
     */
    public function map($product): array
    {
        // custom_fields is already cast to array in the model
        $customFields = is_array($product->custom_fields) 
            ? $product->custom_fields 
            : (json_decode($product->custom_fields, true) ?? []);
        
        return [
            $product->row_number,
            $product->sku,
            $product->product_name,
            $product->brand_name ?? '',
            $product->sub_brand_name ?? '',
            $product->category_name ?? '',
            $product->quantity,
            $product->sale_price,
            $product->purchase_price,
            $product->discount,
            $product->product_no,
            $product->status_message,
            json_encode($customFields),
        ];
    }
}

