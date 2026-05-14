<?php

namespace App\Exports;

use App\Models\Bill;
use App\Models\BillProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BillProductExport implements FromCollection, WithHeadings, WithMapping
{
    protected $billId;

    public function __construct($billId)
    {
        $this->billId = $billId;
    }

    public function collection(): Collection
    {
        $bill = Bill::with([
            'items.product.unit',
            'items.product.brand',
            'items.product.subBrand',
            'items.subProduct.productService.category',
            'items.taxObject',
            'currency'
        ])->find($this->billId);

        if (!$bill) {
            return collect();
        }

        return $bill->items->map(function ($item) use ($bill) {
            $product = $item->product;
            $subProduct = $item->subProduct;
            $categoryType = $subProduct->productService->category->type ?? null;
            $isQtyProduct = $categoryType == 'Qty product';
            $quantity = $isQtyProduct ? $item->quantity : 1;

            return [
                'sub_product_id' => $item->sub_product_id,
                'product_name' => $product->brand 
                    ? ($product->brand->name ?? 'No Brand') . '/' . ($product->subBrand->name ?? 'No Model') . '/' . $product->name . '/' . $product->sku
                    : ($product->name ?? '-'),
                'sub_product_no' => $subProduct->chassis_no ?? '-',
                'quantity' => $quantity,
                'unit' => $product->unit->name ?? '-',
                'rate' => $bill->currency_id != null 
                    ? number_format($item->exchange_price, 2)
                    : number_format($item->price, 2),
                'discount' => $bill->currency_id != null 
                    ? number_format($item->exchange_discount, 2)
                    : number_format($item->discount, 2),
                'tax_amount' => $bill->currency_id != null 
                    ? number_format($item->getTaxPriceExchangeAttribute(), 2)
                    : number_format($item->getTaxPriceAttribute(), 2),
                'tax_name' => $item->tax_name ?? '-',
                'tax_rate' => $item->taxObject->rate ?? 0,
                'chart_of_account' => $product->category->purchaseAccount->name ?? '-',
                'total_price' => $bill->currency_id != null 
                    ? number_format($isQtyProduct 
                        ? ($item->exchange_price * $quantity - $item->exchange_discount * $quantity + $item->getTaxPriceExchangeAttribute())
                        : ($item->exchange_price - $item->exchange_discount + $item->getTaxPriceExchangeAttribute()), 2)
                    : number_format($isQtyProduct 
                        ? ($item->price * $quantity - $item->discount * $quantity + $item->getTaxPriceAttribute())
                        : ($item->price - $item->discount + $item->getTaxPriceAttribute()), 2),
                'currency' => $bill->currency ? $bill->currency->name : 'AED',
                'currency_symbol' => $bill->currency ? $bill->currency->symbol : 'AED',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sub Product ID',
            'Product Name',
            'Sub Product No',
            'Quantity',
            'Unit',
            'Rate',
            'Discount',
            'Tax Amount',
            'Tax Name',
            'Tax Rate (%)',
            'Chart Of Account',
            'Total Price',
            'Currency',
            'Currency Symbol',
        ];
    }

    public function map($item): array
    {
        return [
            $item['sub_product_id'],
            $item['product_name'],
            $item['sub_product_no'],
            $item['quantity'],
            $item['unit'],
            $item['rate'],
            $item['discount'],
            $item['tax_amount'],
            $item['tax_name'],
            $item['tax_rate'],
            $item['chart_of_account'],
            $item['total_price'],
            $item['currency'],
            $item['currency_symbol'],
        ];
    }
}
