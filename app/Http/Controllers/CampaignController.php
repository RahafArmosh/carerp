<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Source;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with('assignedUser')->latest()->get();
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $users = User::where('created_by',\Auth::user()->creatorId())->get();
        $sources        = Source::where('created_by', '=', \Auth::user()->creatorId())->orderBy('order')->get()->pluck('name', 'id');
        return view('campaigns.create', compact('users','sources'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'assigned_to' => 'required|exists:users,id',
            'status' => 'required|in:active,inactive',
            'url' => 'nullable',
        ]);

        Campaign::create($request->all());

        return redirect()->route('campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(Campaign $campaign)
    {
        $users = User::where('created_by',\Auth::user()->creatorId())->get();
        return view('campaigns.edit', compact('campaign', 'users'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'assigned_to' => 'required|exists:users,id',
            'status' => 'required|in:active,inactive',
            'url' => 'nullable',
        ]);

        $campaign->update($request->all());

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}
