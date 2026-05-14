<?php
namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\PosLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VouchersController extends Controller
{
    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view voucher'))
        {
            $vouchers = Voucher::where('created_by', \Auth::user()->creatorId())
                ->with(['customer', 'posRefund.pos'])
                ->paginate(10);
            return view('vouchers.index', compact('vouchers'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create voucher'))
        {
            $customers = Customer::where('created_by',\Auth::user()->creatorId())->get();
            $chart_of_accounts = ChartOfAccount::where('created_by',\Auth::user()->creatorId())->get();
            return view('vouchers.create', compact('customers','chart_of_accounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create voucher'))
        {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
                'amount' => 'required|numeric',
                'valid_until' => 'required|date',
                'active' => 'nullable|boolean',
            ]);
            
            if ($request->active){
                $validated['active'] = 1  ;
            }else{
                $validated['active'] = 0  ;
            }

            $validated['created_by'] = \Auth::user()->creatorId();
            $voucher = Voucher::create($validated);
            
            // Log voucher creation
            PosLog::logAction('create_voucher', [
                'type' => 'voucher',
                'reference_id' => $voucher->id,
                'customer_id' => $validated['customer_id'],
                'new_value' => [
                    'id' => $voucher->id,
                    'customer_id' => $voucher->customer_id,
                    'chart_of_account_id' => $voucher->chart_of_account_id,
                    'amount' => $voucher->amount,
                    'valid_until' => $voucher->valid_until,
                    'active' => $voucher->active,
                ],
                'description' => "Created voucher ID {$voucher->id} for customer ID {$voucher->customer_id}",
            ]);

            return redirect()->route('vouchers.index')->with('success', 'Voucher created!');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view voucher'))
        {
            $customers = Customer::where('created_by',\Auth::user()->creatorId())->get();
            $voucher = Voucher::where('created_by', \Auth::user()->creatorId())
                ->with(['posRefund.pos', 'customer'])
                ->findOrFail($id);
            $chart_of_accounts = ChartOfAccount::where('created_by',\Auth::user()->creatorId())->get();
            
            // Get logs related to this voucher
            $logs = PosLog::where('type', 'voucher')
                ->where('reference_id', $voucher->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            // Check if show view exists, otherwise use edit view
            if (view()->exists('vouchers.show')) {
                return view('vouchers.show', compact('voucher','customers','chart_of_accounts','logs'));
            } else {
                return view('vouchers.edit', compact('voucher','customers','chart_of_accounts','logs'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update voucher'))
        {
            $customers = Customer::where('created_by',\Auth::user()->creatorId())->get();
            $voucher = Voucher::where('created_by', \Auth::user()->creatorId())
                ->with(['posRefund.pos', 'customer'])
                ->findOrFail($id);
            $chart_of_accounts = ChartOfAccount::where('created_by',\Auth::user()->creatorId())->get();
            
            // Get logs related to this voucher
            $logs = PosLog::where('type', 'voucher')
                ->where('reference_id', $voucher->id)
                ->where('created_by', \Auth::user()->creatorId())
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('vouchers.edit', compact('voucher','customers','chart_of_accounts','logs'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update voucher'))
        {
            $voucher = Voucher::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
                'amount' => 'required|numeric',
                'valid_until' => 'required|date',
                'active' => 'nullable|boolean',
            ]);
            
            if ($request->active){
                $validated['active'] = 1  ;
            }else{
                $validated['active'] = 0  ;
            }

            // Store old values for logging
            $oldValues = [
                'customer_id' => $voucher->customer_id,
                'chart_of_account_id' => $voucher->chart_of_account_id,
                'amount' => $voucher->amount,
                'valid_until' => $voucher->valid_until,
                'active' => $voucher->active,
            ];

            $voucher->update($validated);
            
            // Log voucher update
            PosLog::logAction('update_voucher', [
                'type' => 'voucher',
                'reference_id' => $voucher->id,
                'customer_id' => $voucher->customer_id,
                'old_value' => $oldValues,
                'new_value' => [
                    'id' => $voucher->id,
                    'customer_id' => $voucher->customer_id,
                    'chart_of_account_id' => $voucher->chart_of_account_id,
                    'amount' => $voucher->amount,
                    'valid_until' => $voucher->valid_until,
                    'active' => $voucher->active,
                ],
                'description' => "Updated voucher ID {$voucher->id}",
            ]);

            return redirect()->route('vouchers.index')->with('success', 'Voucher updated!');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete voucher'))
        {
            $voucher = Voucher::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
            
            // Log voucher deletion before deleting
            PosLog::logAction('delete_voucher', [
                'type' => 'voucher',
                'reference_id' => $voucher->id,
                'customer_id' => $voucher->customer_id,
                'old_value' => [
                    'id' => $voucher->id,
                    'customer_id' => $voucher->customer_id,
                    'chart_of_account_id' => $voucher->chart_of_account_id,
                    'amount' => $voucher->amount,
                    'valid_until' => $voucher->valid_until,
                    'active' => $voucher->active,
                ],
                'description' => "Deleted voucher ID {$voucher->id}",
            ]);
            
            $voucher->delete();
            return redirect()->route('vouchers.index')->with('success', 'Voucher deleted!');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    
    public function check(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'customer_id' => 'required|integer'
        ]);

        // First check if voucher exists
        $voucher = Voucher::find($request->voucher_id);
        
        if (!$voucher) {
            return response()->json([
                'code' => 404,
                'status' => 'Error',
                'error' => __('This voucher does not exist!'),
                'error_type' => 'not_found'
            ], 404);
        }

        // Check if voucher is expired
        $validUntil = Carbon::parse($voucher->valid_until);
        $isExpired = $validUntil->isPast();
        
        if ($isExpired) {
            // Format expiry date for display
            $expiryDateFormatted = $validUntil->format('Y-m-d H:i:s');
            
            return response()->json([
                'code' => 404,
                'status' => 'Error',
                'error' => __('This voucher has expired! Expiry date: ') . $expiryDateFormatted,
                'error_type' => 'expired',
                'expiry_date' => $voucher->valid_until,
                'expiry_date_formatted' => $expiryDateFormatted
            ], 404);
        }

        // Check if voucher is inactive
        if (!$voucher->active) {
            return response()->json([
                'code' => 404,
                'status' => 'Error',
                'error' => __('This voucher is inactive!'),
                'error_type' => 'inactive'
            ], 404);
        }

        // Check if voucher is for the correct customer
        if ($voucher->customer_id != $request->customer_id) {
            return response()->json([
                'code' => 404,
                'status' => 'Error',
                'error' => __('This voucher is not for this customer!'),
                'error_type' => 'wrong_customer'
            ], 404);
        }

        // All checks passed, add voucher to session
        $vouchers = session()->get('vouchers', []);
        $vouchers[(string)$voucher->id] = ["amount" => $voucher->amount];
        session()->put('vouchers', $vouchers);
        
        $cart = session()->get('pos');

        return response()->json([
            'code' => 200,
            'status' => 'Success',
            'success' => $voucher->id . __(' added to cart successfully!'),
            'voucher' => $voucher,
            'all_vouchers' => $vouchers,
            'carttotal' => $cart
        ]);
    }

    public function clear(Request $request)
    {
        // Clear vouchers from session
        $vouchers = session()->get('vouchers');
        if (isset($vouchers) && count($vouchers) > 0) {
            session()->forget('vouchers');
        }
        
        return response()->json([
            'code' => 200,
            'status' => 'Success',
            'success' => __('Vouchers cleared successfully'),
        ]);
    }

    public function print($id){
        $barcode  = [
            'barcodeType' => 'code128',
            'barcodeFormat' =>'css',
        ];
        $voucher = Voucher::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
        
        $id = (string) $voucher->id;

        $customer = $voucher->customer;
        return view('vouchers.receipt', compact('voucher','customer','barcode','id'));
    }
}
