<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalikAccount;
use App\Models\CustomField;
use App\Models\ChartOfAccount;
use App\Models\Utility;
class SalikAccountController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage constant salik-account')) {
            $salikAccounts = SalikAccount::where('created_by', '=', \Auth::user()->creatorId())->with(['chartAccount'])->get();

            return view('salik-account.index', compact('salikAccounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create constant salik-account')) {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chart_accounts->prepend('Select Account', '');


        return view('salik-account.create', compact('chart_accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create constant salik-account')) {

            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|max:200',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $account = new SalikAccount();
            $account->name = $request->name;
            $account->chart_of_account_id = $request->chart_account_id;
            $account->balance = $request->balance;
            $account->created_by = \Auth::user()->creatorId();
            $account->save();

            // Utility::bankAccountBalance($request->chart_account_id, $request->balance, 'debit');

            return redirect()->route('salik-account.index')->with('success', __('Account successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {

        if (\Auth::user()->can('edit constant salik-account')) {
            $account = SalikAccount::find($id);
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chart_accounts->prepend('Select Account', '');

            return view('salik-account.edit', compact('account', 'chart_accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit constant salik-account')) {
            $account = SalikAccount::find($id);
            if ($account->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:200',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $account->name = $request->name;
                $account->brand_id = $request->balance;
                $account->chart_of_account_id = $request->chart_account_id;
                $account->save();


            return redirect()->route('salik-account.index')->with('success', __('Account successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete constant salik-account')) {
            $account = SalikAccount::find($id);
            if ($account->created_by == \Auth::user()->creatorId()) {

                $account->delete();

                return redirect()->route('salik-account.index')->with('success', __('Account successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
