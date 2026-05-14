<?php

namespace App\Jobs;

use App\Models\{Bill, BillAccount, BillProduct, BillStatusChange, ChartOfAccount, GeneralLedger, StockMovement, SubProduct, Tax, User, Vender};
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessBillSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $billId;
    protected $formattedBillNumber;
    public $maxExceptions = 1;
    public $tries = 1;
    public $timeout = 900; // 15 minutes
    public function __construct($billId, $formattedBillNumber)
    {
        $this->billId = $billId;
        $this->formattedBillNumber = $formattedBillNumber;
    }

    public function handle()
    {


        try {
            DB::beginTransaction();
            $bill = Bill::findOrFail($this->billId);

            $owner = User::find($bill->created_by);
            $creatorId = $owner ? $owner->creatorId() : (int) $bill->created_by;

            $latestVoucher = GeneralLedger::where('created_by', $creatorId)->orderBy('vid', 'desc')->first();
            $newVoucherId = $latestVoucher ? ((int) $latestVoucher->vid + 1) : 1;
            if (GeneralLedger::where('vid', $newVoucherId)->where('created_by', $creatorId)->exists()) {
                throw new \RuntimeException('Voucher id conflict; retry send.');
            }

            $bill->update(['send_date' => now(), 'status' => 4]);

            BillStatusChange::create([
                'bill_id' => $bill->id,
                'status' => 4,
                'payment_status' => -1,
                'changed_at' => now(),
            ]);

            SubProduct::where('bill_id', $bill->id)->update(['flag' => 1]);

            $vender = Vender::findOrFail($bill->vender_id);
            $vendorAccountId = ChartOfAccount::ensureExistsForCompany((int) $vender->chart_account_id, $creatorId, 'vendor');

            Utility::updateUserBalance('vendor', $bill->vender_id, $bill->getTotal(), 'credit');

            $taxAccountId = null;
            if ($bill->tax_id) {
                $taxRow = Tax::where('id', $bill->tax_id)->first();
                if ($taxRow && $taxRow->chart_account_id) {
                    $taxAccountId = ChartOfAccount::ensureExistsForCompany((int) $taxRow->chart_account_id, $creatorId, 'tax');
                } elseif ($taxRow && !$taxRow->chart_account_id) {
                    throw new \RuntimeException('Tax has no linked chart account.');
                }
            }
            $taxes = $bill->tax_id ? Utility::tax($bill->tax_id) : [];

            $allLedgerEntries = [];

            BillProduct::where('bill_id', $bill->id)
                ->with(['product.category'])
                ->chunk(100, function ($billProducts) use (&$allLedgerEntries, $bill, $vender, $vendorAccountId, $newVoucherId, $taxAccountId, $taxes, $creatorId) {

                    foreach ($billProducts as $billProduct) {
                        $product = $billProduct->product;
                        $category = $product->category;

                        if (!$category) {
                            throw new \Exception("Product {$product->id} has no category assigned");
                        }

                        if (!$category->purchase_account_id) {
                            throw new \Exception("Category {$category->id} has no purchase account assigned");
                        }
                        $purchaseAccountId = ChartOfAccount::ensureExistsForCompany((int) $category->purchase_account_id, $creatorId, 'product category (purchase)');

                        $qtyType = $category->type ?? 'Qty product';

                        // Calculate taxes
                        $totalTaxPrice = 0;
                        foreach ($taxes as $tax) {
                            $taxPrice = ($qtyType === "Qty product")
                                ? $tax->rate * ($billProduct->price * $billProduct->quantity) / 100
                                : $tax->rate * ($billProduct->price) / 100;
                            $totalTaxPrice += $taxPrice;
                        }

                        $itemAmount = ($qtyType === "Qty product")
                            ? ($billProduct->price * $billProduct->quantity) - $billProduct->discount
                            : $billProduct->price - $billProduct->discount;

                        // Handle inventory for quantity products
                        if ($qtyType === "Qty product") {
                            $avgCost = $this->calculateAverageCost($billProduct->product_id);

                            StockMovement::create([
                                'product_id' => $product->id,
                                'sub_product_id' => $billProduct->sub_product_id,
                                'bill_id' => $bill->id,
                                'qty_in' => $billProduct->quantity,
                                'qty_out' => 0,
                                'avg_cost' => $avgCost,
                                'cost_price' => $billProduct->price,
                                'activity' => 'PURCHASE',
                                'use_id' => $bill->vender_id, // vender_id for PURCHASE
                                'item' => $billProduct->sub_product_id, // sub_product_id
                            ]);

                            $product->update(['avg_cost' => $avgCost]);
                        }

                        // Create bill accounts
                        $this->createBillAccounts($bill, $billProduct, $vender, $totalTaxPrice, $totalTaxPrice);

                        // Prepare ledger entries (amounts validated against chart_of_accounts for this company)
                        $vendorCreditLine = $itemAmount + $totalTaxPrice;

                        if ($itemAmount != 0) {
                            $allLedgerEntries[] = [
                                'vid' => $newVoucherId,
                                'account' => $purchaseAccountId,
                                'type' => $this->formattedBillNumber,
                                'debit' => $itemAmount,
                                'credit' => 0,
                                'ref_id' => $bill->id,
                                'user_id' => 0,
                                'created_by' => $creatorId,
                                'send_date' => $bill->bill_date,
                                'reference' => 'Bill',
                                'balance' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        if ($totalTaxPrice > 0 && $taxAccountId) {
                            $allLedgerEntries[] = [
                                'vid' => $newVoucherId,
                                'account' => $taxAccountId,
                                'type' => $this->formattedBillNumber,
                                'debit' => $totalTaxPrice,
                                'credit' => 0,
                                'ref_id' => $bill->id,
                                'user_id' => 0,
                                'created_by' => $creatorId,
                                'send_date' => $bill->bill_date,
                                'reference' => 'Bill',
                                'balance' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        if ($vendorCreditLine != 0) {
                            $allLedgerEntries[] = [
                                'vid' => $newVoucherId,
                                'account' => $vendorAccountId,
                                'type' => $this->formattedBillNumber,
                                'debit' => 0,
                                'credit' => $vendorCreditLine,
                                'ref_id' => $bill->id,
                                'user_id' => $vender->id,
                                'created_by' => $creatorId,
                                'balance' => $vender->balance,
                                'send_date' => $bill->bill_date,
                                'reference' => 'Bill',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                });

            // Insert all ledger entries at once
            if (!empty($allLedgerEntries)) {
                GeneralLedger::insert($allLedgerEntries);
            } else {
                throw new \Exception('No ledger entries were generated');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in ProcessBillSend job: ' . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }

    protected function calculateAverageCost($productId)
    {
        $result = BillProduct::where('product_id', $productId)
            ->selectRaw('SUM(quantity * price) as total_cost, SUM(quantity) as total_quantity')
            ->first();

        return $result->total_quantity > 0 ? $result->total_cost / $result->total_quantity : 0;
    }

    protected function createBillAccounts($bill, $billProduct, $vender, $totalTaxPrice, $taxPrice)
    {
        $product = $billProduct->product;
        $category = $product->category;

        BillAccount::create([
            'chart_account_id' => $vender->chart_account_id,
            'price' => ($billProduct->price) - ($billProduct->discount) + $taxPrice,
            'description' => $billProduct->description,
            'type' => 'Bill Vender',
            'ref_id' => $bill->id,
        ]);

        if ($category) {
            BillAccount::create([
                'chart_account_id' => $category->purchase_account_id,
                'price' => $billProduct->price,
                'description' => $billProduct->description,
                'type' => 'Bill Category',
                'ref_id' => $bill->id,
            ]);
        }
    }
}
    