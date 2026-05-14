<?php

namespace App\Exports;

use App\Models\CustomField;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ProductServiceCategory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BillSampleExport implements FromArray, WithStyles
{
    protected $creatorId;

    public function __construct($creatorId)
    {
        $this->creatorId = $creatorId;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        // Row 0: Bill Headers
        $billHeader = [
            'vender_id',
            'bill_date',
            'due_date',
            'warehouse_id',
            'category_id',
            'order_number',
            'salesman_id',
            'tax_id',
            'currency_id',
            'exchange_rate'
        ];

        // Row 1: Bill Data (sample values)
        $billData = [
            1,                          // vender_id (replace with actual vendor ID)
            '2026-01-06',               // bill_date
            '2026-02-06',               // due_date
            1,                          // warehouse_id (replace with actual warehouse ID)
            1,                          // category_id (replace with actual category ID)
            'PO-001',                   // order_number
            null,                       // salesman_id (optional)
            1,                          // tax_id (replace with actual tax ID)
            null,                       // currency_id (optional, null for base currency)
            0                           // exchange_rate (0 for base currency)
        ];

        // Get custom fields for sub-product module
        $customFields = CustomField::where('created_by', $this->creatorId)
            ->where('module', 'sub-product')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        // Row 2: Product Headers (SKU, product_name, brand_name, sub_brand_name, category_name, and custom fields)
        $productHeader = [
            'sku',                      // SKU for product matching (REQUIRED for staging import)
            'product_name',             // Product name (REQUIRED for creating new products)
            'brand_name',               // Brand name (REQUIRED for creating new products)
            'sub_brand_name',          // Sub-brand name (REQUIRED for creating new products)
            'category_name',           // Category name (REQUIRED for creating new products)
            'quantity',                 // Quantity
            'sale_price',               // Sale price
            'purchase_price',           // Purchase price
            'discount',                 // Discount
            'product_no',               // Product number/barcode
        ];

        // Add custom field headers
        $productHeader = array_merge($productHeader, $customFields);

        // Get sample brand, sub-brand, and category names
        $sampleBrand = Brand::where('created_by', $this->creatorId)->first();
        $sampleSubBrand = VehicleModel::where('created_by', $this->creatorId)->first();
        $sampleCategory = ProductServiceCategory::where('created_by', $this->creatorId)->first();

        $sampleBrandName = $sampleBrand ? $sampleBrand->name : 'Sample Brand';
        $sampleSubBrandName = $sampleSubBrand ? $sampleSubBrand->name : 'Sample Model';
        $sampleCategoryName = $sampleCategory ? $sampleCategory->name : 'Sample Category';

        // Row 3+: Sample Product Data
        $productRows = [
            // Product 1 - Example with SKU that exists in system (will be FOUND)
            [
                'SKU001',                // sku - This should match an existing product SKU in your system
                'Sample Product 1',      // product_name
                $sampleBrandName,        // brand_name
                $sampleSubBrandName,     // sub_brand_name
                $sampleCategoryName,     // category_name
                10,                      // quantity
                100.00,                  // sale_price
                80.00,                   // purchase_price
                0,                       // discount
                'PROD001',               // product_no
                // Add custom field values here (empty for sample)
                ...array_fill(0, count($customFields), '')
            ],
            // Product 2 - Example with SKU that doesn't exist (will be MISSING)
            [
                'NEW-SKU-001',          // sku - This SKU doesn't exist, will be flagged as MISSING
                'New Product to Create', // product_name
                $sampleBrandName,        // brand_name
                $sampleSubBrandName,     // sub_brand_name
                $sampleCategoryName,     // category_name
                5,                       // quantity
                150.00,                  // sale_price
                120.00,                  // purchase_price
                10.00,                   // discount
                'PROD002',               // product_no
                // Add custom field values here
                ...array_fill(0, count($customFields), '')
            ],
            // Product 3 - Another example
            [
                'SKU002',                // sku
                'Sample Product 2',      // product_name
                $sampleBrandName,        // brand_name
                $sampleSubBrandName,     // sub_brand_name
                $sampleCategoryName,     // category_name
                15,                      // quantity
                200.00,                  // sale_price
                160.00,                  // purchase_price
                5.00,                    // discount
                'PROD003',               // product_no
                // Add custom field values here
                ...array_fill(0, count($customFields), '')
            ],
        ];

        // Combine all rows
        return [
            $billHeader,
            $billData,
            $productHeader,
            ...$productRows
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Bill header row
            2 => ['font' => ['bold' => true]], // Product header row
        ];
    }
}
