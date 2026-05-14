<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Lead;

class FacebookLeadController extends Controller
{
    // Verification for webhook setup (GET request)
    public function verify(Request $request)
    {
        $verify_token = 'falcons123token'; // Must match the one in Meta app dashboard

        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === $verify_token
        ) {
            return response($request->get('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Invalid verification token', 403);
    }

    // Handles the actual webhook data (POST request)
    public function webhook(Request $request)
    {
        $entries = $request->input('entry');

        foreach ($entries as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'leadgen') {
                    $leadId = $change['value']['leadgen_id'];

                    // Retrieve full lead data using Facebook Graph API
                    $leadData = Http::get("https://graph.facebook.com/v22.0/$leadId", [
                        'access_token' => "EAAUEjY4IjiEBO05zZCEZCRLpBF6CapUzZAoDyVHGryz42A1Bn38ixVLyXpTb122OFupFVgQMlBOgFZBOa4ABeG8H7A3DEqyjMBncsicgnXQ6lZBpHBvxp6utcK0D2T3NkT1ztxsmfseAkw8oxAOgZB3kcLPhhBcXUbReoXzd9ZCDImLJaIb6A7ATRVl",
                    ])->json();
                    \Log::info('Webhook received', $request->all());
                    // Store in DB
                    $lead              = new Lead();
                    $lead->name        = $this->getFieldValue($leadData, 'full_name');
                    $lead->email       = $this->getFieldValue($leadData, 'email');
                    $lead->phone       =  $this->getFieldValue($leadData, 'phone_number');
                    $lead->subject     = '';
                    $lead->user_id     = '';
                    $lead->pipeline_id = 2;
                    $lead->stage_id    = 21;
                    $lead->created_by  = 31;
                    $lead->date        = date('Y-m-d');
                    $lead->save();
                }
            }
        }

        return response('Webhook received', 200);
    }

    // Helper function to extract field values
    private function getFieldValue($leadData, $field)
    {
        foreach ($leadData['field_data'] as $data) {
            if ($data['name'] === $field) {
                return $data['values'][0] ?? null;
            }
        }

        return null;
    }
}
