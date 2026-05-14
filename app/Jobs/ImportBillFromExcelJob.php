<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\CustomField;
use App\Models\Bill;
use App\Models\SubProduct;
use App\Models\ProductService;
use App\Models\WarehouseProduct;
use App\Models\BillProduct;
use App\Models\CustomFieldValue;
use App\Models\Currency;
use App\Models\BillAccount;
use App\Models\Vender;
use App\Models\Tax;
use App\Models\ProductServiceCategory;
use Throwable;
class ImportBillFromExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    public $tries = 1;
    public $timeout = 900; // 15 minutes

    public function __construct($filePath, $userId)
{
    $this->filePath = $filePath;
    $this->userId = $userId;
}

    public function handle()
    {
        $filePath = storage_path('app/' . $this->filePath);

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: " . $filePath);
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'xlsx':
                $firstSheet = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::XLSX);
                break;
            case 'csv':
                $firstSheet = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::CSV);
                break;
            default:
                throw new \Exception('Unsupported file type.');
        }
        $data = $firstSheet[0];
        $billHeader = $data[0];
        $billdata = $data[1];
        $subProductHeader  = $data[2];
        $subProductRows  = array_slice($data, 3); // The rest are data rows
        $lastBillId = Bill::where('created_by', '=', $this->userId)->where('bill_id', 'not like', '%#EXP%')->withTrashed()->latest()->first();
        if ($lastBillId != null) {
            $bill_number = $lastBillId->bill_id + 1;
        } else {
            $bill_number = 1;
        }

        try {
            DB::beginTransaction();
            
            // Validate required data
            if (empty($billHeader) || empty($billdata) || empty($subProductHeader)) {
                throw new \Exception('Invalid file format. Missing required headers or data.');
            }
            
            // Validate vendor exists
            $vendorId = $billdata[array_search('vender_id', $billHeader)];
            $vendor = Vender::where('id', $vendorId)->where('created_by', $this->userId)->first();
            if (!$vendor) {
                throw new \Exception('Vendor not found or not accessible.');
            }
            
            // Map the bill data to your database fields
            $bill = new Bill();
            foreach ($billHeader as $index => $header) {
                if ($header == 'vender_id') {
                    $bill->vender_id = $billdata[$index];
                } elseif ($header == 'bill_date') {
                    $bill->bill_date = Date::excelToDateTimeObject($billdata[$index]);
                } elseif ($header == 'due_date') {
                    $bill->due_date = Date::excelToDateTimeObject($billdata[$index]);
                } elseif ($header == 'warehouse_id') {
                    $bill->warehouse_id = $billdata[$index];
                } elseif ($header == 'category_id') {
                    $bill->category_id = $billdata[$index];
                } elseif ($header == 'order_number') {
                    $bill->order_number = $billdata[$index];
                } elseif ($header == 'salesman_id') {
                    $bill->salesman_id = $billdata[$index];
                } elseif ($header == 'tax_id') {
                    $bill->tax_id = $billdata[$index];
                } elseif ($header == 'currency_id') {
                    if (!empty($billdata[$index])) {
                        $currencyExists = \DB::table('currencies')->where('id', $billdata[$index])->exists();
                        $bill->currency_id = $currencyExists ? $billdata[$index] : null;
                    } else {
                        $bill->currency_id = null; // Explicitly set to null if the value is not provided
                    }
                } elseif ($header == 'exchange_rate') {
                    $bill->exchange_rate = $billdata[$index] != null ? $billdata[$index] : 0;
                }
            }
            $bill->bill_id = $bill_number;
            $bill->created_by = $this->userId;
            $bill->type = 'Bill';
            $bill->user_type = 'vendor';
            $bill->save();
            
            // Use the validated vendor
            $vendor = Vender::find($bill->vender_id);

            foreach (array_chunk($subProductRows, 50) as $chunk) {
                foreach ($chunk as $subProductRow) {
                    try {
                        $subProduct = new SubProduct();
                        $final_price = 0;

                        foreach ($subProductHeader as $index => $header) {
                            if ($header == 'product_id') {
                                $subProduct->product_id = $subProductRow[$index];
                            } elseif ($header == 'quantity') {
                                $subProduct->quantity = $subProductRow[$index];
                            } elseif ($header == 'sale_price') {
                                $subProduct->sale_price = $subProductRow[$index];
                            } elseif ($header == 'purchase_price') {
                                // Process purchase price logic
                                $quantity = $subProductRow[array_search('quantity', $subProductHeader)];
                                $price = $subProductRow[$index];
                                // Handle currency and exchange rate logic as you have in your existing code
                                $subProduct->purchase_price = $price;
                                $final_price = $price;
                            } elseif ($header == 'product_no') {
                                $subProduct->chassis_no = $subProductRow[$index];
                            }
                        }

                        // Save the sub-product
                        $subProduct->bill_id = $bill->id;
                        $subProduct->flag = 0;
                        $subProduct->created_by = $this->userId;
                        $subProduct->save();

                        // Update Product Quantity
                        $product = ProductService::where('id', $subProduct->product_id)->first();
                        if ($product) {
                            $product->quantity += $subProduct->quantity;
                            $product->save();
                        }

                        // Create custom fields if necessary
                        $customFields = [];
                        foreach ($subProductHeader as $index => $header) {
                            if (in_array(strtolower($header), array_map('strtolower', ['Gender', 'Color', 'Size', 'Style', 'Number Size', 'Internal Reference']))) {
                                $customFields[$header] = $subProductRow[$index];
                            }
                        }

                        foreach ($customFields as $fieldName => $fieldValue) {
                            $customField = CustomField::whereRaw('LOWER(name) = ?', strtolower($fieldName))
                                ->where('module', 'sub-product')
                                ->where('category_id', $product->category->id)
                                ->where('created_by', $this->userId)
                                ->first();

                            if ($customField) {
                                CustomFieldValue::create([
                                    'field_id' => $customField->id,
                                    'record_id' => $subProduct->id,
                                    'value' => $fieldValue,
                                ]);
                            }
                        }

                        // Create BillProduct
                        $bill_product = BillProduct::create([
                            'bill_id' => $bill->id,
                            'product_id' => $subProduct->product_id,
                            'sub_product_id' => $subProduct->id,
                            'quantity' => $subProduct->quantity,
                            'tax' => $bill->tax_id,
                            'discount' => 0,
                            'price' => $final_price,
                            'description' => '',
                            'created_by' => $this->userId,
                        ]);
                        $tax = Tax::where('id',$bill->tax_id)->first();
                        $billAccount                    = new BillAccount();
                        $billAccount->chart_account_id  = $vendor->chartAccount->id;
                        $billAccount->price             = ($bill_product->price) - ($bill_product->discount) + ($tax->rate * ($bill_product->price * $bill_product->quantity) / 100);
                        $billAccount->description       = $bill_product->description;
                        $billAccount->type              = 'Bill Vender';
                        $billAccount->ref_id            = $bill->id;
                        $billAccount->created_by        = $this->userId;
                        $billAccount->save();

                        $billAccount                    = new BillAccount();
                        $billAccount->chart_account_id  =  ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;
                        $billAccount->price             = $bill_product->price;
                        $billAccount->description       = $bill_product->description;
                        $billAccount->type              = 'Bill Category';
                        $billAccount->ref_id            = $bill->id;
                        $billAccount->created_by        = $this->userId;
                        $billAccount->save();

                        // Check if warehouse product exists and update
                        if (!empty($billdata[array_search('warehouse_id', $billHeader)])) {
                            $isWarehouseProduct = WarehouseProduct::where('product_id', $subProduct->product_id)
                                ->where('warehouse_id', $bill->warehouse_id)
                                ->first();

                            if ($isWarehouseProduct) {
                                $isWarehouseProduct->quantity += $subProduct->quantity;
                                $isWarehouseProduct->created_by = $this->userId;
                                $isWarehouseProduct->save();
                            } else {
                                $transfer = new WarehouseProduct();
                                $transfer->warehouse_id = $bill->warehouse_id;
                                $transfer->product_id = $subProduct->product_id;
                                $transfer->quantity = $subProduct->quantity;
                                $transfer->created_by = $this->userId;
                                $transfer->save();
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error processing sub-product row', [
                            'error' => $e->getMessage(),
                            'row_data' => $subProductRow,
                            'user_id' => $this->userId
                        ]);
                        throw $e; // Re-throw to trigger transaction rollback
                    }
                }
            }


            DB::commit();
            unlink($filePath);
            return back()->with('success', 'Bill successfully created.');
        } catch (Throwable $e) {
            DB::rollBack();
            \Log::error('Bill import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
                'file_path' => $this->filePath
            ]);
            
            // Clean up file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->fail($e);
        }
    }
}
