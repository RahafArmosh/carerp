<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\User;
use App\Models\UserLead;
use App\Models\Pipeline;
use App\Models\LeadStage;
use App\Models\Utility;
use App\Models\LeadRole;
use App\Models\Source;
use App\Models\LeadActivityLog;
use Illuminate\Support\Str;

class ExternalLeadApiController extends Controller
{
    public function store(Request $request)
    {
        // ✅ Secure the API with a Bearer Token
        // $token = $request->header('Authorization');
        // if ($token !== 'Bearer your-secret-token') {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        // ✅ Validate request
        $validated = $request->validate([
            'subject' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'gclid' => 'nullable|string',
            'message' => 'nullable|string',
            'source' => 'nullable|string',
            'source_url' => 'nullable|url',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $creatorId = $user->creatorId();

        // Check for duplicate email for the creator
        // if (Lead::where('email', $request->email)->where('created_by', $creatorId)->exists()) {
        //     return response()->json(['message' => 'Email already exists'], 422);
        // }

        // Find pipeline and stage
        $pipeline = Pipeline::where('created_by', $creatorId)->first();
        $stage = LeadStage::where('pipeline_id', $pipeline->id)->first();

        if (!$pipeline || !$stage) {
            return response()->json(['message' => 'Pipeline or stage missing.'], 400);
        }
        $existingLead = Lead::where(function ($query) use ($request) {
            $query->where('phone', $request->phone)
                ->orWhere('whatsapp', $request->phone);
        })
            ->where('source_url', $request->source_url)
            ->first();

        if ($existingLead) {
            \Log::info("⛔ Duplicate lead skipped: " . json_encode($request->toArray()));
            return response()->json([
                'message' => 'Duplicate lead detected.',
                'lead_id' => $existingLead->id,
            ], 409); // 409 = Conflict
        }
        // ✅ Create the lead
        $lead = Lead::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'source' => $request->source,
            'source_url' => $request->source_url,
            'gclid' => $request->gclid,
            'user_id' => $request->user_id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'created_by' => $creatorId,
            'whatsapp' => $request->whatsapp,
            'lead_name' => $request->lead_name,
            'country' => $request->country,
            'sources'      => Source::whereRaw('LOWER(name) LIKE ?', ['%meta%'])->where('pipeline_id',$pipeline->id)->pluck('id')->implode(','),
            'date' => now()->toDateTimeString(),
        ]);
        $lead->quantity = $lead->parseQuantityFromMessage();
        $lead->save();
        $campaigns = \App\Models\Campaign::where('status', 'active')->get();

        $campaigns = \App\Models\Campaign::where('status', 'active')->get();

        foreach ($campaigns as $campaign) {
            $urls = array_map('trim', explode(',', strtolower($campaign->url)));
            // \Log::info("🔍 Split URLs: ", $campaign->source_id);
            foreach ($urls as $url) {
                if (Str::contains(strtolower($lead->source_url), strtolower($url))) {
                    $lead->sources = $campaign->source_id ?? $lead->sources;
                    $lead->country = $lead->country ?? $campaign->target_country;
                    $lead->save();

                    \Log::info("📌 Campaign matched. Updated lead {$lead->id} with source: {$lead->source}, country: {$lead->country}");
                    break; // Optional: stop after the first match
                }
            }
        }
        $leadRoleUserId = null;
        $allRoles = LeadRole::with('conditions')->where('active', 1)->get();
        \Log::info('👥 Total roles loaded: ' . $allRoles->count());

        foreach ($allRoles as $role) {
            $result = null;

            $columnGroups = [];

            // Step 1: Group conditions by lead_column
            foreach ($role->conditions as $condition) {
                $columnGroups[$condition->lead_column][] = $condition;
            }

            $overallMatch = true;

            foreach ($columnGroups as $column => $conditions) {
                $groupMatch = false;

                $leadValue = strtolower((string) ($lead->$column ?? ''));

                foreach ($conditions as $condition) {
                    $operation = $condition->operation;
                    $value = in_array($operation, ['is_empty', 'is_not_empty']) ? null : strtolower(trim($condition->value));

                    $match = match ($operation) {
                        '='             => $leadValue === $value,
                        '!='            => $leadValue !== $value,
                        'contains'      => str_contains($leadValue, $value),
                        'not_contains'  => !str_contains($leadValue, $value),
                        'starts_with'   => str_starts_with($leadValue, $value),
                        'ends_with'     => str_ends_with($leadValue, $value),
                        'is_empty'      => empty($leadValue),
                        'is_not_empty'  => !empty($leadValue),
                        '>' => is_numeric($leadValue) && is_numeric($value) && $leadValue > $value,
                        '<' => is_numeric($leadValue) && is_numeric($value) && $leadValue < $value,
                        default         => false,
                    };

                    if ($match) {
                        $groupMatch = true;
                        break; // Any one match in the group is enough
                    }
                }

                // If any group fails, the whole role doesn't match
                if (!$groupMatch) {
                    $overallMatch = false;
                    break;
                }
            }

            if ($overallMatch) {
                \Log::info("✅ Role '{$role->name}' matched dynamically. Assigning to user ID {$role->assigned_user_id}");
                $leadRoleUserId = $role->assigned_user_id;
                $userName = User::find($role->assigned_user_id)?->name ?? 'Unknown User';
                LeadActivityLog::create(
                            [
                                'user_id' => 36,
                                'lead_id' => $lead->id,
                                'log_type' => 'Role matched',
                                'remark' =>  json_encode(
                                    [
                                        'role' => "✅ Role '{$role->name}' matched dynamically. Assigning to user  {$userName}",
                                    ]
                                ),
                            ]
                        );
            }
        }

        $userDB = $leadRoleUserId ?: $request->user_id;
        if ($leadRoleUserId) {
            // Link lead to user
            UserLead::create([
                'user_id' => $leadRoleUserId,
                'lead_id' => $lead->id,
            ]);
            LeadActivityLog::create(
                    [
                        'user_id' => 36,
                        'lead_id' => $lead->id,
                        'log_type' => 'Add user',
                        'remark' =>  json_encode(
                            [
                                'user' => User::find($leadRoleUserId)->name,
                            ]
                        ),
                    ]
                );
        }


        // Optional: Send email or Slack/Telegram notification if needed
        // Optional: Trigger webhooks if needed

        return response()->json([
            'message' => 'Lead successfully created!',
            'lead_id' => $lead->id
        ], 201);
    }
}
