<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StockMovement;

class StockMovementReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $request;
    protected $creatorId;
    protected $user;
    protected $stockMovements;

    public function __construct(Request $request, $creatorId)
    {
        $this->request = $request;
        $this->creatorId = $creatorId;
        $this->user = User::find($creatorId);
        $this->stockMovements = $this->getStockMovements();
    }

    /**
     * Get stock movements data
     */
    protected function getStockMovements()
    {
        // Replicate the logic from SubProductController::stockMovementReport
        $query = \App\Models\SubProduct::where('sub_products.created_by', '=', $this->creatorId)
            ->where('sub_products.flag', '!=', 2)
            ->with(['productService.brand', 'bill', 'invoice', 'pos', 'warehouse']);

        // Apply filters (same as controller)
        if ($this->request->filled('product_id')) {
            $query->where('sub_products.product_id', $this->request->product_id);
        }

        if ($this->request->filled('sub_product_id')) {
            $query->where('sub_products.id', $this->request->sub_product_id);
        }

        if ($this->request->filled('brand_id')) {
            $query->whereHas('productService', function($q) {
                $q->where('brand_id', $this->request->brand_id);
            });
        }

        if ($this->request->filled('barcode')) {
            $barcode = trim($this->request->barcode);
            $query->where('sub_products.chassis_no', 'LIKE', '%' . $barcode . '%');
        }

        if ($this->request->filled('customer_id')) {
            $query->whereHas('invoice', function($q) {
                $q->where('customer_id', $this->request->customer_id);
            });
        }

        if ($this->request->filled('vender_id')) {
            $query->whereHas('bill', function($q) {
                $q->where('vender_id', $this->request->vender_id);
            });
        }

        if ($this->request->filled('bill_id')) {
            $query->where('sub_products.bill_id', $this->request->bill_id);
        }

        if ($this->request->filled('invoice_id')) {
            $query->where('sub_products.invoice_id', $this->request->invoice_id);
        }

        if ($this->request->filled('date_from')) {
            $query->whereDate('sub_products.created_at', '>=', $this->request->date_from);
        }

        if ($this->request->filled('date_to')) {
            $query->whereDate('sub_products.created_at', '<=', $this->request->date_to);
        }

        if ($this->request->filled('activity')) {
            if ($this->request->activity == 'PURCHASE') {
                $query->whereNotNull('sub_products.bill_id');
            } elseif ($this->request->activity == 'SALES') {
                $query->where(function($q) {
                    $q->whereNotNull('sub_products.invoice_id')
                      ->orWhereNotNull('sub_products.pos_id');
                });
            }
        }

        $query->orderBy('sub_products.created_at', 'asc');
        $subProducts = $query->get();

        // Transform to stock movements format
        $runningStock = 0;
        $stockMovements = collect();
        
        foreach ($subProducts as $subProduct) {
            $activity = null;
            $qtyIn = 0;
            $qtyOut = 0;
            $customerSupplier = null;
            $soldPrice = 0;
            $invoiceProduct = null;
            $posProduct = null;
            
            if ($subProduct->bill_id) {
                $activity = 'PURCHASE';
                $qtyIn = $subProduct->quantity ?? 0;
                $qtyOut = 0;
                if ($subProduct->bill && $subProduct->bill->vender) {
                    $customerSupplier = $subProduct->bill->vender->name;
                }
            } elseif ($subProduct->invoice_id) {
                $activity = 'SALES';
                $qtyIn = 0;
                $invoiceProduct = \App\Models\InvoiceProduct::where('invoice_id', $subProduct->invoice_id)
                    ->where('sub_product_id', $subProduct->id)
                    ->first();
                $qtyOut = $invoiceProduct ? $invoiceProduct->quantity : 0;
                $soldPrice = $invoiceProduct
                    ? StockMovement::netUnitSoldPriceFromInvoiceProduct($invoiceProduct)
                    : 0;
                if ($subProduct->invoice && $subProduct->invoice->customer) {
                    $customerSupplier = $subProduct->invoice->customer->name;
                }
            } elseif ($subProduct->pos_id) {
                $activity = 'SALES';
                $qtyIn = 0;
                $posProduct = \App\Models\PosProduct::where('pos_id', $subProduct->pos_id)
                    ->where('sub_product_id', $subProduct->id)
                    ->first();
                $qtyOut = $posProduct ? $posProduct->quantity : 0;
                if ($posProduct) {
                    $soldPrice = StockMovement::netUnitSoldPriceFromPosProduct($posProduct);
                }
                if ($subProduct->pos && $subProduct->pos->customer) {
                    $customerSupplier = $subProduct->pos->customer->name;
                }
            }
            
            if (!$activity) {
                continue;
            }
            
            if ($this->request->filled('activity') && $activity != $this->request->activity) {
                continue;
            }
            
            $runningStock += $qtyIn - $qtyOut;
            
            $stockMovements->push((object)[
                'date' => $subProduct->created_at,
                'activity' => $activity,
                'product_name' => $subProduct->productService ? 
                    (($subProduct->productService->brand ? $subProduct->productService->brand->name . '/' : '') .
                     ($subProduct->productService->subBrand ? $subProduct->productService->subBrand->name . '/' : '') .
                     $subProduct->productService->name) : 'N/A',
                'item' => $subProduct->chassis_no ?? '-',
                'warehouse' => $subProduct->warehouse ? $subProduct->warehouse->name : '-',
                'customer_supplier' => $customerSupplier ?? '-',
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'stock' => $runningStock,
                'unit_value' => $subProduct->purchase_price ?? 0,
                'total_value' => ($subProduct->purchase_price ?? 0) * abs($qtyIn - $qtyOut),
                'sold_price' => $soldPrice,
                'bill' => $subProduct->bill && $this->user ? $this->user->billNumberFormat($subProduct->bill->bill_id) : '-',
                'invoice' => $subProduct->invoice && $this->user ? $this->user->invoiceNumberFormat($subProduct->invoice->invoice_id) : '-',
                'asn' => '-',
                'grn' => '-',
                'sales_order' => $subProduct->invoice && $subProduct->invoice->order_number ? $subProduct->invoice->order_number : '-',
            ]);
        }
        
        return $stockMovements;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->stockMovements;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'DATE',
            'ACTIVITY',
            'PRODUCT',
            'ITEM',
            'WAREHOUSE',
            'CUSTOMER/SUPPLIER',
            'QTY IN',
            'QTY OUT',
            'STOCK',
            'UNIT VALUE',
            'TOTAL VALUE',
            'SOLD PRICE',
            'BILL',
            'INVOICE',
            'ASN',
            'GRN',
            'SALES ORDER',
        ];
    }

    /**
     * @param mixed $movement
     * @return array
     */
    public function map($movement): array
    {
        return [
            \Carbon\Carbon::parse($movement->date)->format('Y-m-d'),
            $movement->activity,
            $movement->product_name,
            $movement->item,
            $movement->warehouse,
            $movement->customer_supplier,
            $movement->qty_in,
            $movement->qty_out,
            $movement->stock,
            $movement->unit_value,
            $movement->total_value,
            $movement->sold_price,
            $movement->bill,
            $movement->invoice,
            $movement->asn,
            $movement->grn,
            $movement->sales_order,
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
            'A' => 12,
            'B' => 15,
            'C' => 30,
            'D' => 15,
            'E' => 20,
            'F' => 25,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 12,
            'K' => 15,
            'L' => 12,
            'M' => 15,
            'N' => 15,
            'O' => 15,
            'P' => 15,
            'Q' => 15,
        ];
    }
}

