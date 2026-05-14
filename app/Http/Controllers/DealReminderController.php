<?php

namespace App\Http\Controllers;

use App\Models\DealReminder;
use App\Models\User;
use Illuminate\Http\Request;

class DealReminderController extends Controller
{
    public function index()
    {
        $user = \Auth::user();

        if ($user->type == 'company') {
            $reminders = DealReminder::query()
                ->whereIn('user_id', User::where('created_by', $user->id)->pluck('id'))
                ->orWhere('user_id', $user->id)
                ->latest()
                ->paginate(50);
        } elseif ($user->type == 'manager') {
            $managedUserIds = User::where('manager_id', $user->id)->pluck('id');
            $allUserIds = $managedUserIds->push($user->id);
            $reminders = DealReminder::whereIn('user_id', $allUserIds)
                ->latest()
                ->paginate(50);
        } else {
            $reminders = DealReminder::where('user_id', $user->id)
                ->latest()
                ->paginate(50);
        }

        return view('deals.reminders.index', compact('reminders'));
    }

    public function markDone($id)
    {
        $reminder = DealReminder::findOrFail($id);
        $user = \Auth::user();

        if ($user->type == 'company') {
            // allow
        } elseif ($user->type == 'manager') {
            $managedUserIds = User::where('manager_id', $user->id)->pluck('id')->push($user->id);
            if (!$managedUserIds->contains($reminder->user_id)) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            if ($reminder->user_id != $user->id) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }

        $reminder->is_done = true;
        $reminder->save();

        return redirect()->back()->with('success', __('Reminder marked as done.'));
    }
}


