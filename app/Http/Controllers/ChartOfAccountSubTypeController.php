<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use Illuminate\Http\Request;

class ChartOfAccountSubTypeController extends Controller
{

    public function index()
    {
        // if(\Auth::user()->can('manage constant chart of account type'))
        // {
        $types = ChartOfAccountSubType::query()
            ->select(['id', 'name', 'type'])
            ->whereHas('accountType', function ($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })
            ->with(['accountType:id,name'])
            ->latest('id')
            ->get();

        return view('chartOfAccountSubType.index', compact('types'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }


    public function create()
    {
        $accountTypes = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get(); // Fetch all account types
        return view('chartOfAccountSubType.create', compact('accountTypes'));
    }


    public function store(Request $request)
    {
        // if(\Auth::user()->can('create constant chart of account type'))
        // {
        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $account             = new ChartOfAccountSubType();
        $account->name       = $request->name;
        $account->type       = $request->type;
        // $account->created_by = \Auth::user()->creatorId();
        $account->save();

        return redirect()->route('chart-of-account-sub-type.index')->with('success', __('Chart of account type successfully created.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }


    public function show(ChartOfAccountSubType $chartOfAccountType)
    {
        //
    }


    public function edit(ChartOfAccountSubType $chartOfAccountSubType)
    {
        $accountTypes = ChartOfAccountType::all(); // Fetch all account types
        return view('chartOfAccountSubType.edit', compact('chartOfAccountSubType','accountTypes'));
    }


    public function update(Request $request, ChartOfAccountSubType $chartOfAccountSubType)
    {
        // if(\Auth::user()->can('edit constant chart of account type'))
        // {
        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $chartOfAccountSubType->name = $request->name;
        $chartOfAccountSubType->type = $request->type;
        $chartOfAccountSubType->save();

        return redirect()->route('chart-of-account-sub-type.index')->with('success', __('Chart of account type successfully updated.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }


    public function destroy(ChartOfAccountSubType $chartOfAccountSubType)
    {
        // if(\Auth::user()->can('delete constant chart of account type'))
        // {
        $chartOfAccountSubType->delete();

        return redirect()->route('chart-of-account-sub-type.index')->with('success', __('Chart of account type successfully deleted.'));
        // }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }
}
