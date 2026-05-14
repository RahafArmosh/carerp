<?php
namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return Auth::user()->notifications;
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'data' => 'required|string',
        ]);

        $notification = Auth::user()->notifications()->create($request->all());

        return response()->json($notification, 201);
    }

    public function show(Notification $notification)
    {
        $this->authorize('view', $notification);

        return $notification;
    }

    public function update(Request $request, Notification $notification)
    {
        $this->authorize('update', $notification);

        $request->validate([
            'title' => 'string|max:255',
            'body' => 'string',
            'is_read' => 'boolean',
        ]);

        $notification->update($request->all());

        return response()->json($notification, 200);
    }

    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(null, 204);
    }
        public function index_web()
    {
        if (\Auth::user()->type == 'company') {
            // Get notifications created by users who were created by the company user OR by the company user themselves
            $notifications = Notification::where(function($query) {
                $query->whereIn('created_by', function($subQuery) {
                    $subQuery->select('id')
                          ->from('users')
                          ->where('created_by', \Auth::user()->id);
                })->orWhere('created_by', \Auth::user()->id);
            })->latest()->paginate(20);
        } elseif (\Auth::user()->type == 'manager') {
            // Get notifications created by users whose manager is the authenticated user OR by the manager themselves
            $notifications = Notification::where(function($query) {
                $query->whereIn('created_by', function($subQuery) {
                    $subQuery->select('id')
                          ->from('users')
                          ->where('manager_id', \Auth::user()->id);
                })->orWhere('created_by', \Auth::user()->id);
            })->latest()->paginate(20);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Mark all notifications as read
        if ($notifications->count() > 0) {
            $notificationIds = $notifications->pluck('id')->toArray();
            Notification::whereIn('id', $notificationIds)->update(['is_read' => true]);
        }

        return view('notifications.index', compact('notifications'));
    }

}
