<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\warehouse;
use App\Models\BankAccount;
use App\Models\PosPayment;
use App\Models\PosLog;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the payment methods.
     */
    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view payment method'))
        {
            $paymentMethods = PaymentMethod::where('created_by', \Auth::user()->creatorId())
                ->with(['warehouse', 'bankAccount'])
                ->latest()
                ->paginate(10);
            
            // Get payment counts for each payment method
            $paymentCounts = [];
            foreach ($paymentMethods as $method) {
                $paymentCounts[$method->id] = PosPayment::where('payment_method_id', $method->id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->count();
            }
            
            return view('payment_methods.index', compact('paymentMethods', 'paymentCounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new payment method.
     */
    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create payment method'))
        {
            $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
            $bankAccounts = BankAccount::where('created_by',\Auth::user()->creatorId())->get();
            return view('payment_methods.create', compact('warehouses', 'bankAccounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Store a newly created payment method in storage.
     */
    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create payment method'))
        {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'name' => 'required|string|max:255',
                'bank_account_id' => 'required|exists:bank_accounts,id',
            ]);

            $validated['created_by'] = \Auth::user()->creatorId();
            $paymentMethod = PaymentMethod::create($validated);
            
            // Log payment method creation
            PosLog::logAction('create_payment_method', [
                'type' => 'payment_method',
                'reference_id' => $paymentMethod->id,
                'warehouse_id' => $validated['warehouse_id'],
                'new_value' => [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                    'warehouse_id' => $paymentMethod->warehouse_id,
                    'bank_account_id' => $paymentMethod->bank_account_id,
                ],
                'description' => "Payment method '{$paymentMethod->name}' created for warehouse",
            ]);

            return redirect()->route('payment-methods.index')->with('success', 'Payment method created successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing a payment method.
     */
    public function edit($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update payment method'))
        {
            $paymentMethod = PaymentMethod::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
            $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
            $bankAccounts = BankAccount::where('created_by',\Auth::user()->creatorId())->get();
            
            // Get logs related to this payment method
            $logs = PosLog::where('type', 'payment_method')
                ->where('reference_id', $paymentMethod->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return view('payment_methods.edit', compact('paymentMethod', 'warehouses', 'bankAccounts', 'logs'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update the specified payment method in storage.
     */
    public function update(Request $request, $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update payment method'))
        {
            $paymentMethod = PaymentMethod::where('created_by', \Auth::user()->creatorId())->findOrFail($id);

            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'name' => 'required|string|max:255',
                'bank_account_id' => 'required|exists:bank_accounts,id',
            ]);

            // Store old values for logging
            $oldValues = [
                'name' => $paymentMethod->name,
                'warehouse_id' => $paymentMethod->warehouse_id,
                'bank_account_id' => $paymentMethod->bank_account_id,
            ];

            $paymentMethod->update($validated);
            
            // Log payment method update
            PosLog::logAction('update_payment_method', [
                'type' => 'payment_method',
                'reference_id' => $paymentMethod->id,
                'warehouse_id' => $validated['warehouse_id'],
                'old_value' => $oldValues,
                'new_value' => [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                    'warehouse_id' => $paymentMethod->warehouse_id,
                    'bank_account_id' => $paymentMethod->bank_account_id,
                ],
                'description' => "Payment method '{$paymentMethod->name}' updated",
            ]);

            return redirect()->route('payment-methods.index')->with('success', 'Payment method updated successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified payment method from storage.
     */
    public function destroy($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete payment method'))
        {
            $paymentMethod = PaymentMethod::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
            
            // Check if there are any payments using this payment method
            $paymentsCount = PosPayment::where('payment_method_id', $id)
                ->where('created_by', \Auth::user()->creatorId())
                ->count();
            
            if ($paymentsCount > 0) {
                return redirect()->route('payment-methods.index')
                    ->with('error', __('Cannot delete payment method. There are :count payment(s) associated with this method.', ['count' => $paymentsCount]));
            }
            
            // Log payment method deletion before deleting
            PosLog::logAction('delete_payment_method', [
                'type' => 'payment_method',
                'reference_id' => $paymentMethod->id,
                'warehouse_id' => $paymentMethod->warehouse_id,
                'old_value' => [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                    'warehouse_id' => $paymentMethod->warehouse_id,
                    'bank_account_id' => $paymentMethod->bank_account_id,
                ],
                'description' => "Payment method '{$paymentMethod->name}' deleted",
            ]);
            
            $paymentMethod->delete();

            return redirect()->route('payment-methods.index')->with('success', 'Payment method deleted.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
