<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tracking;
use App\Models\Employee;
use App\Services\GeocodingService;

class TrackingController extends Controller
{

    protected $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    public function index(Request $request)
    {
        // Fetch tracking records from the database with filtering
        $query = Tracking::query();

        if ($request->has('employee') && $request->employee != '') {
            $query->where('user_id', $request->employee);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        $query->orderBy('created_at', 'desc');
        $trackingRecords = $query->paginate(10);
        $employees = Employee::all();

        // Pass the records to the view
        return view('report.tracking', compact('trackingRecords', 'employees'));
    }

    public function store(Request $request)
    {
        $tracking = new Tracking;
        $tracking->user_id = $request->user_id;
        $tracking->latitude = $request->latitude;
        $tracking->longitude = $request->longitude;
        $tracking->timestamp = now();
        $tracking->save();

        return response()->json(['success' => true]);
    }

    public function show($userId)
    {
        $trackings = Tracking::where('user_id', $userId)->get();
        return response()->json($trackings);
    }

    public function getTrackingDataForUser($userId)
    {
        $trackingRecords = Tracking::where('user_id', $userId)->get();
        return response()->json($trackingRecords);
    }
}
