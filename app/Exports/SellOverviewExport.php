<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SellOverviewExport implements FromCollection, WithHeadings, WithMapping
{
    protected $creatorId;
    protected $filters;
    protected $user;

    public function __construct($creatorId, $filters = [])
    {
        $this->creatorId = $creatorId;
        $this->filters = $filters;
        $this->user = Auth::user();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        // Get POS products sold
        $posQuery = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
            ->join('product_services', 'pos_products.product_id', '=', 'product_services.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'pos.warehouse_id', '=', 'warehouses.id')
            ->where('pos.created_by', $this->creatorId)
            ->where('product_services.created_by', $this->creatorId)
            ->whereNull('pos.deleted_at');

        // Get Invoice products sold
        $invoiceQuery = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('product_services', 'invoice_products.product_id', '=', 'product_services.id')
            ->leftJoin('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
            ->leftJoin('product_service_categories', 'product_services.category_id', '=', 'product_service_categories.id')
            ->leftJoin('brands', 'product_services.brand_id', '=', 'brands.id')
            ->leftJoin('sub_brands', 'product_services.sub_brand_id', '=', 'sub_brands.id')
            ->leftJoin('warehouses', 'sub_products.warehouse_id', '=', 'warehouses.id')
            ->where('invoices.created_by', $this->creatorId)
            ->where('product_services.created_by', $this->creatorId)
            ->whereNull('invoice_products.deleted_at')
            ->whereNull('invoices.deleted_at');

        // Apply filters
        if (!empty($this->filters['q'])) {
            $q = trim($this->filters['q']);
            $posQuery->where(function($subQ) use ($q) {
                $subQ->where('product_services.name', 'like', "%{$q}%")
                     ->orWhere('product_services.sku', 'like', "%{$q}%")
                     ->orWhere('brands.name', 'like', "%{$q}%")
                     ->orWhere('sub_brands.name', 'like', "%{$q}%");
            });
            $invoiceQuery->where(function($subQ) use ($q) {
                $subQ->where('product_services.name', 'like', "%{$q}%")
                     ->orWhere('product_services.sku', 'like', "%{$q}%")
                     ->orWhere('brands.name', 'like', "%{$q}%")
                     ->orWhere('sub_brands.name', 'like', "%{$q}%");
            });
        }

        if (!empty($this->filters['category_id'])) {
            $posQuery->where('product_services.category_id', $this->filters['category_id']);
            $invoiceQuery->where('product_services.category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['product_id'])) {
            $posQuery->where('product_services.id', $this->filters['product_id']);
            $invoiceQuery->where('product_services.id', $this->filters['product_id']);
        }

        if (!empty($this->filters['warehouse_id'])) {
            $posQuery->where('pos.warehouse_id', $this->filters['warehouse_id']);
            $invoiceQuery->where('sub_products.warehouse_id', $this->filters['warehouse_id']);
        }

        // Date range filter
        if (!empty($this->filters['date_from'])) {
            $posQuery->where('pos.pos_date', '>=', $this->filters['date_from']);
            $invoiceQuery->where('invoices.issue_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $posQuery->where('pos.pos_date', '<=', $this->filters['date_to']);
            $invoiceQuery->where('invoices.issue_date', '<=', $this->filters['date_to']);
        }

        // Get POS sold data grouped by product
        $posData = $posQuery->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(pos_products.quantity) as pos_sell_qty'),
                DB::raw('COUNT(DISTINCT pos_products.sub_product_id) as pos_sub_product_count'),
                DB::raw('COUNT(DISTINCT pos_products.pos_id) as pos_count'),
                DB::raw('CASE 
                    WHEN SUM(pos_products.quantity) > 0 THEN
                        SUM(
                            CASE 
                                WHEN (pos_products.compo_id IS NOT NULL AND pos_products.compo_id != 0 AND pos_products.compo_id != "0" AND pos_products.combo_price IS NOT NULL)
                                THEN (pos_products.combo_price - (pos_products.combo_price * COALESCE(pos_products.discount, 0) / 100)) * pos_products.quantity
                                ELSE (pos_products.price - (pos_products.price * COALESCE(pos_products.discount, 0) / 100)) * pos_products.quantity
                            END
                        ) / SUM(pos_products.quantity)
                    ELSE 0
                END as avg_pos_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->get()
            ->keyBy('product_id');

        // Get Invoice sold data grouped by product
        $invoiceData = $invoiceQuery->select(
                'product_services.id as product_id',
                'product_services.name as product_name',
                'product_services.sku',
                'product_service_categories.name as category_name',
                'brands.name as brand_name',
                'sub_brands.name as sub_brand_name',
                DB::raw('SUM(invoice_products.quantity) as invoice_sell_qty'),
                DB::raw('COUNT(DISTINCT invoice_products.sub_product_id) as invoice_sub_product_count'),
                DB::raw('COUNT(DISTINCT invoice_products.invoice_id) as invoice_count'),
                DB::raw('CASE 
                    WHEN SUM(invoice_products.quantity) > 0 THEN
                        SUM(
                            (invoice_products.price - (invoice_products.price * COALESCE(invoice_products.discount, 0) / 100)) * invoice_products.quantity
                        ) / SUM(invoice_products.quantity)
                    ELSE 0
                END as avg_invoice_price')
            )
            ->groupBy(
                'product_services.id',
                'product_services.name',
                'product_services.sku',
                'product_service_categories.name',
                'brands.name',
                'sub_brands.name'
            )
            ->get()
            ->keyBy('product_id');

        // Merge POS and Invoice data
        $allProductIds = $posData->keys()->merge($invoiceData->keys())->unique();
        $mergedData = collect();

        foreach ($allProductIds as $productId) {
            $posItem = $posData->get($productId);
            $invoiceItem = $invoiceData->get($productId);

            $totalCostQty = 0;
            $posQty = $posItem?->pos_sell_qty ?? 0;
            $invoiceQty = $invoiceItem?->invoice_sell_qty ?? 0;
            $totalQty = $posQty + $invoiceQty;

            // Get POS products sold - use avg_cost from stock_movements (activity = 'Sale via POS'), fallback to purchase_price
            $posProducts = \App\Models\PosProduct::join('pos', 'pos_products.pos_id', '=', 'pos.id')
                ->join('sub_products', 'pos_products.sub_product_id', '=', 'sub_products.id')
                ->leftJoin('stock_movements', function($join) use ($productId) {
                    $join->on('stock_movements.pos_id', '=', 'pos.id')
                         ->on('stock_movements.sub_product_id', '=', 'pos_products.sub_product_id')
                         ->on('stock_movements.product_id', '=', 'pos_products.product_id')
                         ->where('stock_movements.activity', '=', 'Sale via POS')
                         ->where('stock_movements.created_by', '=', $this->creatorId);
                })
                ->where('pos_products.product_id', $productId)
                ->where('pos.created_by', $this->creatorId)
                ->whereNull('pos.deleted_at');
            
            // Apply same filters as main query
            if (!empty($this->filters['warehouse_id'])) {
                $posProducts->where('pos.warehouse_id', $this->filters['warehouse_id']);
            }
            if (!empty($this->filters['date_from'])) {
                $posProducts->where('pos.pos_date', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $posProducts->where('pos.pos_date', '<=', $this->filters['date_to']);
            }
            
            $posProducts = $posProducts->select(
                    'pos_products.quantity',
                    'stock_movements.avg_cost as pos_avg_cost',
                    'sub_products.purchase_price'
                )
                ->get();
            
            foreach ($posProducts as $posProduct) {
                $itemQty = $posProduct->quantity ?? 0;
                if ($itemQty > 0) {
                    $itemCost = 0;
                    // For POS products, use avg_cost from stock movements
                    if (isset($posProduct->pos_avg_cost) && $posProduct->pos_avg_cost > 0) {
                        $itemCost = $posProduct->pos_avg_cost;
                    } elseif (isset($posProduct->purchase_price) && $posProduct->purchase_price > 0) {
                        // Fallback to purchase_price if avg_cost not available
                        $itemCost = $posProduct->purchase_price;
                    }
                    
                    if ($itemCost > 0) {
                        $totalCostQty += ($itemCost * $itemQty);
                    }
                }
            }
            
            // Get Invoice products sold - use avg_cost from stock_movements (activity = 'Sale via Invoice'), fallback to purchase_price
            $invoiceProducts = \App\Models\InvoiceProduct::join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->join('sub_products', 'invoice_products.sub_product_id', '=', 'sub_products.id')
                ->leftJoin('stock_movements', function($join) use ($productId) {
                    $join->on('stock_movements.invoice_id', '=', 'invoices.id')
                         ->on('stock_movements.sub_product_id', '=', 'invoice_products.sub_product_id')
                         ->on('stock_movements.product_id', '=', 'invoice_products.product_id')
                         ->where('stock_movements.activity', '=', 'Sale via Invoice')
                         ->where('stock_movements.created_by', '=', $this->creatorId);
                })
                ->where('invoice_products.product_id', $productId)
                ->where('invoices.created_by', $this->creatorId)
                ->whereNull('invoice_products.deleted_at')
                ->whereNull('invoices.deleted_at');
            
            // Apply same filters as main query
            if (!empty($this->filters['warehouse_id'])) {
                $invoiceProducts->where('sub_products.warehouse_id', $this->filters['warehouse_id']);
            }
            if (!empty($this->filters['date_from'])) {
                $invoiceProducts->where('invoices.issue_date', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $invoiceProducts->where('invoices.issue_date', '<=', $this->filters['date_to']);
            }
            
            $invoiceProducts = $invoiceProducts->select(
                    'invoice_products.quantity',
                    'stock_movements.avg_cost as invoice_avg_cost',
                    'sub_products.purchase_price'
                )
                ->get();
            
            foreach ($invoiceProducts as $invoiceProduct) {
                $itemQty = $invoiceProduct->quantity ?? 0;
                if ($itemQty > 0) {
                    $itemCost = 0;
                    // For Invoice products, use avg_cost from stock movements
                    if (isset($invoiceProduct->invoice_avg_cost) && $invoiceProduct->invoice_avg_cost > 0) {
                        $itemCost = $invoiceProduct->invoice_avg_cost;
                    } elseif (isset($invoiceProduct->purchase_price) && $invoiceProduct->purchase_price > 0) {
                        // Fallback to purchase_price if avg_cost not available
                        $itemCost = $invoiceProduct->purchase_price;
                    }
                    
                    if ($itemCost > 0) {
                        $totalCostQty += ($itemCost * $itemQty);
                    }
                }
            }
            
            $avgCost = ($totalQty > 0) ? ($totalCostQty / $totalQty) : 0;

            // Calculate weighted average selling price
            $avgPosPrice = $posItem?->avg_pos_price ?? 0;
            $avgInvoicePrice = $invoiceItem?->avg_invoice_price ?? 0;
            
            $weightedAvgPrice = 0;
            if ($totalQty > 0) {
                $weightedAvgPrice = (($posQty * $avgPosPrice) + ($invoiceQty * $avgInvoicePrice)) / $totalQty;
            }
            
            // Calculate totals
            $totalSell = $totalQty * $weightedAvgPrice;
            $totalCost = $totalQty * $avgCost;
            $profit = $totalSell - $totalCost;

            // Available qty: sum of sub_products.quantity for this product (remaining stock)
            $availableQtyQuery = \App\Models\SubProduct::where('product_id', $productId)
                ->where('created_by', $this->creatorId);
            if (!empty($this->filters['warehouse_id'])) {
                $availableQtyQuery->where('warehouse_id', $this->filters['warehouse_id']);
            }
            $available_qty = (float) $availableQtyQuery->sum('quantity');

            $mergedItem = (object)[
                'product_id' => $productId,
                'available_qty' => $available_qty,
                'product_name' => $posItem?->product_name ?? $invoiceItem?->product_name ?? null,
                'sku' => $posItem?->sku ?? $invoiceItem?->sku ?? null,
                'category_name' => $posItem?->category_name ?? $invoiceItem?->category_name ?? null,
                'brand_name' => $posItem?->brand_name ?? $invoiceItem?->brand_name ?? null,
                'sub_brand_name' => $posItem?->sub_brand_name ?? $invoiceItem?->sub_brand_name ?? null,
                'pos_sell_qty' => $posQty,
                'invoice_sell_qty' => $invoiceQty,
                'total_sell_qty' => $totalQty,
                'pos_sub_product_count' => $posItem?->pos_sub_product_count ?? 0,
                'invoice_sub_product_count' => $invoiceItem?->invoice_sub_product_count ?? 0,
                'total_sub_product_count' => ($posItem?->pos_sub_product_count ?? 0) + ($invoiceItem?->invoice_sub_product_count ?? 0),
                'pos_count' => $posItem?->pos_count ?? 0,
                'invoice_count' => $invoiceItem?->invoice_count ?? 0,
                'avg_pos_price' => $avgPosPrice,
                'avg_invoice_price' => $avgInvoicePrice,
                'avg_cost' => $avgCost,
                'total_sell' => $totalSell,
                'total_cost' => $totalCost,
                'profit' => $profit,
            ];

            $mergedData->push($mergedItem);
        }

        return $mergedData->sortBy('product_name')->values();
    }

    public function headings(): array
    {
        return [
            'Product',
            'SKU',
            'Brand',
            'Available Qty',
            'POS Sell Qty',
            'Invoice Sell Qty',
            'Total Sell Qty',
            'Sub Products Sold',
            'POS Count',
            'Invoice Count',
            'Avg POS Price',
            'Avg Invoice Price',
            'Avg Cost',
            'Total Sell',
            'Cost Per Qty',
            'Profit',
        ];
    }

    public function map($sell): array
    {
        // Build product name
        $parts = array_filter([
            $sell->category_name ?? null,
            $sell->sub_brand_name ?? null,
            $sell->product_name ?? null
        ]);
        $productDisplay = !empty($parts) ? implode('/', $parts) : ($sell->product_name ?? '-');

        // Get SKU - same format as StockMovementExport
        $sku = '-';
        if (!empty($sell->sku)) {
            $sku = $sell->sku;
        }

        // Calculate cost per quantity: avg_cost * POS Sell Qty
        $avgCost = $sell->avg_cost ?? 0;
        $posSellQty = $sell->pos_sell_qty ?? 0;
        $costPerQty = $avgCost * $posSellQty;

        // Calculate profit using the new cost per quantity
        $totalSell = $sell->total_sell ?? 0;
        $newTotalCost = $costPerQty;
        $profit = $totalSell - $newTotalCost;

        return [
            $productDisplay,
            $sku,
            $sell->brand_name ?? '-',
            number_format($sell->available_qty ?? 0, 2),
            number_format($sell->pos_sell_qty, 2),
            number_format($sell->invoice_sell_qty, 2),
            number_format($sell->total_sell_qty, 2),
            $sell->total_sub_product_count,
            $sell->pos_count,
            $sell->invoice_count,
            $this->user ? $this->user->priceFormat($sell->avg_pos_price ?? 0) : number_format($sell->avg_pos_price ?? 0, 2),
            $this->user ? $this->user->priceFormat($sell->avg_invoice_price ?? 0) : number_format($sell->avg_invoice_price ?? 0, 2),
            ($sell->avg_cost && $sell->avg_cost > 0) 
                ? ($this->user ? $this->user->priceFormat($sell->avg_cost) : number_format($sell->avg_cost, 2))
                : '-',
            $this->user ? $this->user->priceFormat($totalSell) : number_format($totalSell, 2),
            $this->user ? $this->user->priceFormat($costPerQty) : number_format($costPerQty, 2),
            $this->user ? $this->user->priceFormat($profit) : number_format($profit, 2),
        ];
    }

}
