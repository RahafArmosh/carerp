<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;

class StockOverviewExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $filters;
    protected $user;

    public function __construct($userId, $filters = [])
    {
        $this->userId = $userId;
        $this->filters = $filters;
        $this->user = \App\Models\User::find($userId);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $creatorId = $this->userId;
        
        // Build query
        $query = \App\Models\SubProduct::where('sub_products.created_by', $creatorId)
            ->join('product_services', 'sub_products.product_id', '=', 'product_services.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'sub_products.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('countries', 'warehouses.country_id', '=', 'countries.id');

        // Apply filters
        if (!empty($this->filters)) {
            // Search filter
            if (!empty($this->filters['q'])) {
                $q = trim($this->filters['q']);
                $query->where(function($subQ) use ($q) {
                    $subQ->where('product_services.name', 'like', "%{$q}%")
                         ->orWhere('product_services.sku', 'like', "%{$q}%")
                         ->orWhere('brands.name', 'like', "%{$q}%")
                         ->orWhere('sub_brands.name', 'like', "%{$q}%");
                });
            }

            // Category filter
            if (!empty($this->filters['category_id'])) {
                $query->where('product_services.category_id', $this->filters['category_id']);
            }

            // Product filter
            if (!empty($this->filters['product_id'])) {
                $query->where('product_services.id', $this->filters['product_id']);
            }

            // Warehouse filter
            if (!empty($this->filters['warehouse_id'])) {
                $query->where('sub_products.warehouse_id', $this->filters['warehouse_id']);
            }
        }
        
        // Get stock data grouped by product
        $stockData = $query->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(sub_products.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT sub_products.id) as sub_product_count'),
                DB::raw('SUM(CASE WHEN sub_products.booked = 0 THEN sub_products.quantity ELSE 0 END) as free_quantity'),
                DB::raw('SUM(CASE WHEN sub_products.booked != 0 THEN sub_products.quantity ELSE 0 END) as booked_quantity'),
                DB::raw('AVG(sub_products.sale_price) as avg_sale_price'),
                DB::raw('AVG(sub_products.purchase_price) as avg_purchase_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->orderBy('product_services.name')
            ->get();

        // Sell qty: for each product, sum quantity sold from POS + Invoices (optionally filtered by warehouse)
        $productIds = $stockData->pluck('product_id')->unique();
        $posSold = collect();
        $invoiceSold = collect();
        if ($productIds->isNotEmpty()) {
            $posQuery = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
                ->where('pos.created_by', $creatorId)
                ->whereNull('pos.deleted_at')
                ->whereIn('pos_products.product_id', $productIds)
                ->groupBy('pos_products.product_id')
                ->selectRaw('pos_products.product_id, SUM(pos_products.quantity) as qty');
            if (!empty($this->filters['warehouse_id'])) {
                $posQuery->where('pos.warehouse_id', $this->filters['warehouse_id']);
            }
            $posSold = $posQuery->get()->keyBy('product_id');

            $invoiceQuery = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->join('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
                ->where('invoices.created_by', $creatorId)
                ->whereNull('invoice_products.deleted_at')
                ->whereNull('invoices.deleted_at')
                ->whereIn('invoice_products.product_id', $productIds)
                ->groupBy('invoice_products.product_id')
                ->selectRaw('invoice_products.product_id, SUM(invoice_products.quantity) as qty');
            if (!empty($this->filters['warehouse_id'])) {
                $invoiceQuery->where('sub_products.warehouse_id', $this->filters['warehouse_id']);
            }
            $invoiceSold = $invoiceQuery->get()->keyBy('product_id');
        }

        foreach ($stockData as $item) {
            $item->sell_qty = ($posSold->get($item->product_id)?->qty ?? 0) + ($invoiceSold->get($item->product_id)?->qty ?? 0);
        }

        return $stockData;
    }

    public function headings(): array
    {
        return [
            'Product',
            'SKU',
            'Category',
            'Brand',
            'Sell Qty',
            'Total Quantity',
            'Free Quantity',
            'Booked Quantity',
            'Sub Products Count',
            'Avg Sale Price',
            'Avg Purchase Price',
        ];
    }

    public function map($stock): array
    {
        // Build product name similar to blade view
        $parts = array_filter([
            $stock->category_name ?? null,
            $stock->sub_brand_name ?? null,
            $stock->product_name ?? null
        ]);
        $productName = !empty($parts) ? implode('/', $parts) : ($stock->product_name ?? '-');
        
        // Add SKU if available
        if (!empty($stock->sku)) {
            $productName = $stock->sku . ' - ' . $productName;
        }

        $avgSalePrice = $this->user ? $this->user->priceFormat($stock->avg_sale_price ?? 0) : number_format($stock->avg_sale_price ?? 0, 2);
        $avgPurchasePrice = $this->user ? $this->user->priceFormat($stock->avg_purchase_price ?? 0) : number_format($stock->avg_purchase_price ?? 0, 2);

        return [
            $productName,
            $stock->sku ?? '-',
            $stock->category_name ?? '-',
            $stock->brand_name ?? '-',
            number_format($stock->sell_qty ?? 0, 2),
            number_format($stock->total_quantity, 2),
            number_format($stock->free_quantity, 2),
            number_format($stock->booked_quantity, 2),
            $stock->sub_product_count,
            $avgSalePrice,
            $avgPurchasePrice,
        ];
    }
}

