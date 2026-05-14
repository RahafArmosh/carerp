<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead; // We'll create this model next
use Illuminate\Support\Facades\Log;

class GoogleAdsLeadController extends Controller
{
    public function store(Request $request)
    {
        $key = $request->header('X-Goog-Signature', ''); // Google might not send this, so you can use custom verification if needed

        // Optional: verify your key if you set one
        $yourSecretKey = env('GOOGLE_LEAD_SECRET');
        if ($request->input('key') !== $yourSecretKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Log incoming data (check logs/laravel.log to see structure)
        Log::info('Google Lead Received:', $request->all());

        // Save the lead (customize fields based on what Google sends)

        $lead              = new Lead();
        $lead->name        = $request->input('full_name');
        $lead->email       = $request->input('email');
        $lead->phone       = $request->input('phone_number');
        $lead->subject     = '';
        $lead->user_id     = '';
        $lead->pipeline_id = 2;
        $lead->stage_id    = 21;
        $lead->created_by  = 31;
        $lead->date        = date('Y-m-d');
        $lead->save();

        return response()->json(['status' => 'success']);
    }
}
