<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Delegates to {@see AttendanceEmployeeController::sync()} for POST /api/attendance-sync.
     */
    public function sync(Request $request)
    {
        return app(AttendanceEmployeeController::class)->sync($request);
    }
}
