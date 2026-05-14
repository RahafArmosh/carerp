<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\GeneralLedger;
use App\Models\Bill;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\BillStatusChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
class EmployeePaymentController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('manage employee')) {
            if (\Auth::user()->type == 'Employee') {
                $employees = Employee::where('user_id', '=', Auth::user()->id)->with(['designation', 'branch', 'department'])->get();
            } else {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->with(['designation', 'branch', 'department'])->get();
            }

            return view('employeepayment.index', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($ids)
    {
        try {
            $id       = \Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Employee Not Found.'));
        }

        $id     = \Crypt::decrypt($ids);
        $employee = Employee::find($id);
        $employeePyment = EmployeePayment::where('employee_id', '=', $employee->id)->get();
        return view('employeepayment.show', compact('employee','employeePyment'));
    }
    public function printemployeepayment($paymentId)
    {
        $payment = Payment::find($paymentId);

        // Load the payment details view and pass the payment data
        return view('employeepayment.print')->with('payment', $payment);
    }

}
