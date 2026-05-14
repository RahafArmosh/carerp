<?php

namespace App\Exports;

use App\Models\InvoiceProduct;
use App\Models\PosProduct;
use App\Models\StockMovement;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class StockMovementExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomChunkSize
{
    protected $request;

    protected $creatorId;

    protected $user;

    /** @var array<string, float> */
    protected $soldPriceCache = [];

    public function __construct(Request $request, $creatorId)
    {
        $this->request = $request;
        $this->creatorId = $creatorId;
        $this->user = User::find($creatorId);
    }

    /**
     * Chunked export — avoids loading all rows into memory (timeouts/OOM on large datasets).
     */
    public function chunkSize(): int
    {
        return 2000;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\StockMovement>
     */
    public function query()
    {
        $query = StockMovement::query()
            ->with([
                'product.category',
                'product.brand',
                'product.subBrand',
                'bill',
                'invoice',
                'pos',
                'Subproduct.warehouse',
                'customer',
                'vendor',
                'user',
            ])
            ->where('created_by', $this->creatorId);

        if ($this->request->filled('barcode')) {
            $barcode = trim($this->request->barcode);
            $query->whereHas('Subproduct', function ($q) use ($barcode) {
                $q->where('chassis_no', 'LIKE', '%' . $barcode . '%');
            });
        }

        if ($this->request->filled('product_id')) {
            $query->where('product_id', $this->request->product_id);
        }

        if ($this->request->filled('sub_product_id')) {
            $query->where('sub_product_id', $this->request->sub_product_id);
        }

        if ($this->request->filled('brand_id')) {
            $query->whereHas('product', function ($q) {
                $q->where('brand_id', $this->request->brand_id);
            });
        }

        if ($this->request->filled('activity')) {
            $query->where('activity', 'LIKE', '%' . $this->request->activity . '%');
        }

        if ($this->request->filled('customer_id')) {
            $query->where(function ($q) {
                $q->whereHas('customer', function ($subQ) {
                    $subQ->where('id', $this->request->customer_id);
                })->orWhere('use_id', $this->request->customer_id);
            });
        }

        if ($this->request->filled('vender_id')) {
            $query->where(function ($q) {
                $q->whereHas('vendor', function ($subQ) {
                    $subQ->where('id', $this->request->vender_id);
                })->orWhere('use_id', $this->request->vender_id);
            });
        }

        if ($this->request->filled('bill_id')) {
            $query->where('bill_id', $this->request->bill_id);
        }

        if ($this->request->filled('invoice_id')) {
            $query->where('invoice_id', $this->request->invoice_id);
        }

        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }

        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }

        return $query->orderBy('created_at', 'desc');
    }

    protected function resolveSoldPrice(StockMovement $item): float
    {
        if ($item->invoice_id && $item->sub_product_id) {
            $key = 'inv:' . $item->invoice_id . ':' . $item->sub_product_id;
            if (!array_key_exists($key, $this->soldPriceCache)) {
                $invoiceProduct = InvoiceProduct::where('invoice_id', $item->invoice_id)
                    ->where('sub_product_id', $item->sub_product_id)
                    ->first();
                $this->soldPriceCache[$key] = StockMovement::netUnitSoldPriceFromInvoiceProduct($invoiceProduct);
            }

            return $this->soldPriceCache[$key];
        }

        if ($item->pos_id && $item->sub_product_id) {
            $key = 'pos:' . $item->pos_id . ':' . $item->sub_product_id;
            if (!array_key_exists($key, $this->soldPriceCache)) {
                $posProduct = PosProduct::where('pos_id', $item->pos_id)
                    ->where('sub_product_id', $item->sub_product_id)
                    ->first();
                $this->soldPriceCache[$key] = StockMovement::netUnitSoldPriceFromPosProduct($posProduct);
            }

            return $this->soldPriceCache[$key];
        }

        return 0.0;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'DATE',
            'PRODUCT',
            'SKU',
            'SUB PRODUCT',
            'WAREHOUSE',
            'ACTIVITY',
            'BILL',
            'INVOICE',
            'POS',
            'QTY IN',
            'QTY OUT',
            'COST PRICE',
            'AVG COST',
            'SOLD PRICE',
            'VENDOR/CUSTOMER',
            'CREATED BY',
        ];
    }

    /**
     * @param  StockMovement  $item
     */
    public function map($item): array
    {
        $productName = 'N/A';
        if ($item->product) {
            $parts = [];
            if ($item->product->category && $item->product->category->name) {
                $parts[] = $item->product->category->name;
            }
            if ($item->product->brand && $item->product->brand->name) {
                $parts[] = $item->product->brand->name;
            }
            if ($item->product->subBrand && $item->product->subBrand->name) {
                $parts[] = $item->product->subBrand->name;
            }
            if ($item->product->name) {
                $parts[] = $item->product->name;
            }
            $productName = !empty($parts) ? implode(' / ', $parts) : 'N/A';
        }

        $sku = '-';
        if ($item->product && !empty($item->product->sku)) {
            $sku = $item->product->sku;
        }

        $subProduct = $item->Subproduct ?? null;
        $subProductNo = '-';
        if ($subProduct && $subProduct->chassis_no) {
            $subProductNo = $subProduct->chassis_no;
        } elseif ($item->sub_product_id) {
            $subProductNo = 'Sub Product #' . $item->sub_product_id;
        }

        $warehouse = '-';
        if ($subProduct && $subProduct->warehouse) {
            $warehouse = $subProduct->warehouse->name;
        }

        $bill = '-';
        if ($item->bill_id && $item->bill && $this->user) {
            $bill = $this->user->billNumberFormat($item->bill->bill_id);
        } elseif ($item->bill_id) {
            $bill = 'Bill #' . $item->bill_id;
        }

        $invoice = '-';
        if ($item->invoice_id && $item->invoice && $this->user) {
            $invoice = $this->user->invoiceNumberFormat($item->invoice->invoice_id);
        } elseif ($item->invoice_id) {
            $invoice = 'Invoice #' . $item->invoice_id;
        }

        $pos = '-';
        if ($item->pos_id && $item->pos && $this->user) {
            $pos = $this->user->posNumberFormat($item->pos->pos_id);
        } elseif ($item->pos_id) {
            $pos = 'POS #' . $item->pos_id;
        }

        $activity = (string) ($item->activity ?? '');
        $isPurchase = strpos($activity, 'Purchase') !== false || strpos($activity, 'Profit') !== false;
        $isSale = strpos($activity, 'Sale') !== false || strpos($activity, 'Loss') !== false || strpos($activity, 'Return') !== false;

        $vendorCustomer = '-';
        if ($isPurchase && $item->vendor) {
            $vendorCustomer = $item->vendor->name;
        } elseif ($isSale && $item->customer) {
            $vendorCustomer = $item->customer->name;
        } elseif ($item->use_id) {
            $vendorCustomer = 'ID: ' . $item->use_id;
        }

        $createdBy = 'N/A';
        if ($item->user) {
            $createdBy = $item->user->name;
        }

        $soldPrice = $this->resolveSoldPrice($item);

        return [
            $this->user ? $this->user->dateFormat($item->created_at) : \Carbon\Carbon::parse($item->created_at)->format('Y-m-d'),
            $productName,
            $sku,
            $subProductNo,
            $warehouse,
            $item->activity ?? '-',
            $bill,
            $invoice,
            $pos,
            $item->qty_in > 0 ? $item->qty_in : '-',
            $item->qty_out > 0 ? $item->qty_out : '-',
            $item->cost_price ? ($this->user ? $this->user->priceFormat($item->cost_price) : number_format($item->cost_price, 2)) : '-',
            $item->avg_cost ? ($this->user ? $this->user->priceFormat($item->avg_cost) : number_format($item->avg_cost, 2)) : '-',
            $soldPrice > 0 ? ($this->user ? $this->user->priceFormat($soldPrice) : number_format($soldPrice, 2)) : '-',
            $vendorCustomer,
            $createdBy,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 10,
            'K' => 10,
            'L' => 15,
            'M' => 15,
            'N' => 15,
            'O' => 25,
            'P' => 20,
        ];
    }
}
