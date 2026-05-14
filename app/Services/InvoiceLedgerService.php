<?php
namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\SubProduct;
use App\Models\Tax;
use App\Models\Utility;
use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\InvoiceExpense;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class InvoiceLedgerService
{
    public function regenerate($invoiceId, $sendDate, $creatorId = 30)
    {
        $invoice = Invoice::withTrashed()->where('id', $invoiceId)->firstOrFail();
        $user = User::find($creatorId);
        // If needed, filter by creator ID
        if ($creatorId && $invoice->created_by != $creatorId) {
            return false;
        }

        // Delete old ledger entries
        GeneralLedger::where('ref_id', $invoiceId)
            ->where('reference', 'Invoice')
            ->when($creatorId, fn($q) => $q->where('created_by', $creatorId))
            ->delete();

        $invoice = Invoice::withTrashed()->with('items.subProduct.bill')->find($invoiceId);





        
        // Get the latest 'vid' entry, if any exist
        $latestVoucher = GeneralLedger::orderBy('vid', 'desc')->first();
        // Extract the vid value from the last record and increment it
        if ($latestVoucher) {
            $lastVid = $latestVoucher->vid;
            $newVid = $lastVid + 1;
        } else {
            // If no record exists, start with 1
            $newVid = 1;
        }
        $existingRecord = GeneralLedger::where('vid', $newVid)->exists();

        $invoice_products = InvoiceProduct::withTrashed()->where('invoice_id', $invoice->id)->get();
        foreach ($invoice_products as $invoice_product) {
            $product = ProductService::find($invoice_product->product_id);

            $subproduct = SubProduct::find($invoice_product->sub_product_id);

            $itemAmount_purchase = 0;
            $totalTaxPrice = 0;
            $itemAmount = 0;
            $taxes = Utility::tax($invoice->tax_id);
            $QtyType = ProductServiceCategory::where('id', $product->category_id)->first()->type;
            foreach ($taxes as $tax) {
                if ($product->type === 'product') {
                    if ($QtyType === "Qty product") {
                        $taxPrice = Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                    } else {

                        $taxPrice = Utility::taxRate($tax->rate, $invoice_product->price, 1, $invoice_product->discount);

                    }
                    $totalTaxPrice += $taxPrice;
                }
            }
            $product_cost = $product->avg_cost;
            $itemAmount = ($invoice_product->price * $invoice_product->quantity) ;
            $itemAmount_purchase =  $product_cost * $invoice_product->quantity;
            // Retrieve the chart account ID for the category
            $categoryChartAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->sale_account_id;
            $customer = Customer::where('id', $invoice->customer_id)->first();
            // Add entries to General Ledger

            // Create a new entry for credit the category account
            $newEntryCategory = new GeneralLedger();
            $newEntryCategory->vid = $newVid;
            $newEntryCategory->account = $categoryChartAccountId;
            $newEntryCategory->type = $user->invoiceNumberFormat($invoice->id);
            $newEntryCategory->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
            $newEntryCategory->debit = 0;
            $newEntryCategory->credit = $itemAmount;
            $newEntryCategory->ref_id = $invoice->id;
            $newEntryCategory->user_id = 0;
            $newEntryCategory->created_by = $creatorId;
            $newEntryCategory->send_date = $sendDate;
            $newEntryCategory->reference = 'Invoice';
            $newEntryCategory->save();

            if ($invoice_product->discount != 0) {
                $discountAccount = $invoice->discount_account_id ? $invoice->discount_account_id : ChartOfAccount::where('created_by', $creatorId)->where('name', '=', 'Discounts Allowed')->first()->id;
                // Create a new entry for credit the category account
                $newEntryCategory = new GeneralLedger();
                $newEntryCategory->vid = $newVid;
                $newEntryCategory->account = $discountAccount;
                $newEntryCategory->type = $user->invoiceNumberFormat($invoice->id);
                $newEntryCategory->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
                $newEntryCategory->debit = $invoice_product->discount;
                $newEntryCategory->credit = 0;
                $newEntryCategory->ref_id = $invoice->id;
                $newEntryCategory->user_id = 0;
                $newEntryCategory->created_by = $creatorId;
                $newEntryCategory->send_date = $sendDate;
                $newEntryCategory->reference = 'Invoice';
                $newEntryCategory->save();
            }

            // Retrieve the chart account ID for the tax
            $taxChartAccountId = \App\Models\Tax::where('id', $invoice->tax_id)->first()->chart_account_id;

            // Create a new entry cedit for the tax account
            $newEntryTax = new GeneralLedger();
            $newEntryTax->vid = $newVid;
            $newEntryTax->account = $taxChartAccountId;
            $newEntryTax->type = $user->invoiceNumberFormat($invoice->id);
            $newEntryTax->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
            $newEntryTax->debit = 0;
            $newEntryTax->credit = $totalTaxPrice;
            $newEntryTax->ref_id = $invoice->id;
            $newEntryTax->user_id = 0;
            $newEntryTax->created_by = 30;
            $newEntryTax->send_date = $sendDate;
            $newEntryTax->reference = 'Invoice';
            $newEntryTax->save();


            // Retrieve the chart account ID for the customer
            $customerChartAccountId = $customer->chart_account_id;

            // Create a new entry debit for the customer account
            $newEntryCustomer = new GeneralLedger();
            $newEntryCustomer->vid = $newVid;
            $newEntryCustomer->account = $customerChartAccountId;
            $newEntryCustomer->type = $user->invoiceNumberFormat($invoice->id);
            $newEntryCustomer->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
            $newEntryCustomer->debit = ($itemAmount - $invoice_product->discount) + $totalTaxPrice;
            $newEntryCustomer->credit = 0;
            $newEntryCustomer->ref_id = $invoice->id;
            $newEntryCustomer->user_id = $customer->id;
            $newEntryCustomer->created_by = $creatorId;
            $newEntryCustomer->balance = $customer->balance;
            $newEntryCustomer->send_date = $sendDate;
            $newEntryCustomer->reference = 'Invoice';
            $newEntryCustomer->save();


            ///////////////////////////////////////
            // Add records if product type is 'product'
            if ($product->type == 'product' && $invoice->type == "regular") {
                // Retrieve the chart account ID for the purchase
                $purchaseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->purchase_account_id;

                // Create a new entry for the purchase account (credit)
                $newEntryCredit = new GeneralLedger();
                $newEntryCredit->vid = $newVid;
                $newEntryCredit->account = $purchaseAccountId;
                $newEntryCredit->type = $user->invoiceNumberFormat($invoice->id);
                $newEntryCredit->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
                $newEntryCredit->debit = 0;
                $newEntryCredit->credit = $itemAmount_purchase;
                $newEntryCredit->ref_id = $invoice->id;
                $newEntryCredit->user_id = 0;
                $newEntryCredit->created_by = $creatorId;
                $newEntryCredit->send_date = $sendDate;
                $newEntryCredit->reference = 'Invoice';
                $newEntryCredit->save();

                // Retrieve the chart account ID for the expense
                $expenseAccountId = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first()->expense_account_id;

                // Create a new entry for the expense account (debit)
                $newEntryDebit = new GeneralLedger();
                $newEntryDebit->vid = $newVid;
                $newEntryDebit->account = $expenseAccountId;
                $newEntryDebit->type = $user->invoiceNumberFormat($invoice->id);
                $newEntryDebit->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
                $newEntryDebit->debit = $itemAmount_purchase;
                $newEntryDebit->credit = 0;
                $newEntryDebit->ref_id = $invoice->id;
                $newEntryDebit->user_id = 0;
                $newEntryDebit->created_by = $creatorId;
                $newEntryDebit->send_date = $sendDate;
                $newEntryDebit->reference = 'Invoice';
                $newEntryDebit->save();
            }
        }
        // -------------------- Handle Expenses --------------------
        $invoice_expenses = InvoiceExpense::withTrashed()->where('invoice_id', $invoice->id)->get();
        foreach ($invoice_expenses as $expense) {
            $expenseAmount = $expense->amount;

            // Retrieve the expense account


            // Debit Expense Account
            $newEntryExpense = new GeneralLedger();
            $newEntryExpense->vid = $newVid;
            $newEntryExpense->account = $expense->account_id;
            $newEntryExpense->debit = 0;
            $newEntryExpense->credit = $expenseAmount;
            $newEntryExpense->type = $user->invoiceNumberFormat($invoice->id);
            $newEntryExpense->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
            $newEntryExpense->ref_id = $invoice->id;
            $newEntryExpense->user_id = 0;
            $newEntryExpense->created_by = $creatorId;
            $newEntryExpense->send_date = $sendDate;
            $newEntryExpense->reference = 'Invoice';
            $newEntryExpense->save();

            // Credit Tax Account (if applicable)
            $customerChartAccountId = $customer->chart_account_id;
            $newEntryExpenseTax = new GeneralLedger();
            $newEntryExpenseTax->vid = $newVid;
            $newEntryExpenseTax->account = $customerChartAccountId;
            $newEntryExpenseTax->debit = $expenseAmount;
            $newEntryExpenseTax->credit = 0;
            $newEntryExpenseTax->type = $user->invoiceNumberFormat($invoice->id);
            $newEntryExpenseTax->ref_number = $user->invoiceNumberFormat($invoice->invoice_id);
            $newEntryExpenseTax->ref_id = $invoice->id;
            $newEntryExpenseTax->user_id = $customer->id;
            $newEntryExpenseTax->created_by = $creatorId;
            $newEntryExpenseTax->send_date = $sendDate;
            $newEntryExpenseTax->reference = 'Invoice';
            $newEntryExpenseTax->save();
        }

        return true;
    }
}
