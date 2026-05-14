<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FacebookToken;
use App\Models\Lead;
use App\Models\User;
use App\Models\Pipeline;
use App\Models\LeadStage;
use App\Models\UserLead;
use App\Models\LeadRole;
use App\Models\LeadActivityLog;
use App\Models\Source;
use Illuminate\Support\Str;
class FetchFacebookLeads extends Command
{
    protected $signature = 'facebook:fetch-leads';
    protected $description = 'Fetch Meta leads from all active forms and save to DB';

    public function handle()
    {
        $this->info('📥 Fetching Facebook leads...');

        $tokenRecord = FacebookToken::latest()->first();
        if (!$tokenRecord || !$tokenRecord->page_token) {
            $this->error('❌ No valid page token found.');
            return 1;
        }

        $newUserToken = $tokenRecord->user_token;
        // $pageId = '750475655068357'; // Replace with your actual Page ID
        $pagePipelineMap = [
            '113127658500662' => 7,
            '102690996225302' => 7,
            '105565629264423' => 7,
            '605950529276789' => 7,
            '446267255239202' => 7,
            '101060858820181' => 10,
            '750475655068357' => 6,
            '491645577357431' => 11,
        ];
        foreach ($pagePipelineMap as $pageId => $pipelineId) {
            $pageResponse = Http::get("https://graph.facebook.com/v22.0/{$pageId}", [
                'fields' => 'access_token',
                'access_token' => $newUserToken,
            ]);
            $pageAccessToken = $pageResponse->json('access_token');
            $formResponse = Http::get("https://graph.facebook.com/v22.0/{$pageId}/leadgen_forms", [
                'access_token' => $pageAccessToken,
                'limit' => 500,
            ]);

            if (!$formResponse->successful()) {
                $this->error('❌ Failed to fetch forms: ' . $formResponse->body());
                return 1;
            }

            $forms = $formResponse->json('data') ?? [];
            if (empty($forms)) {
                $this->info('ℹ️ No forms found.');
                continue;
            }

            foreach ($forms as $form) {
                $formId = $form['id'];
                $formName = $form['name'];
                $this->info("📝 Processing form: $formName ($formId) ($pageId)");
                if (($form['status'] ?? '') === 'ARCHIVED') {
                    continue;
                }
                $leadResponse = Http::get("https://graph.facebook.com/v22.0/{$formId}/leads", [
                    'access_token' => $pageAccessToken,
                    'limit' => 500,
                ]);

                if (!$leadResponse->successful()) {
                    $this->warn("⚠️ Failed to fetch leads for form {$formId}: " . $leadResponse->body());
                    continue;
                }

                $leads = $leadResponse->json('data') ?? [];

                foreach ($leads as $lead) {
                    $excludedFields = ['full_name', 'email', 'phone_number', 'company_name', 'country'];
                    $fields = collect($lead['field_data'] ?? []);

                    // Skip test leads
                    // if ($fields->contains(fn($f) => str_contains(strtolower($f['name']), 'test lead'))) {
                    //     continue;
                    // }
                    \Log::info('Raw field_data:', $fields->toArray());
                    $leadInfo = [
                        'full_name' =>
                            ($fields->firstWhere('name', 'full_name')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'full name')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'nom_complet')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'nome_completo')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'الاسم_بالكامل')['values'][0] ?? ''),
                        'email' =>
                            ($fields->firstWhere('name', 'email')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'البريد_الإلكتروني')['values'][0] ?? '') ??
                            ($fields->firstWhere('name', 'e-mail')['values'][0] ?? ''),
                        'phone_number' =>
                            ($fields->firstWhere('name', 'phone_number')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'phone')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'telefone')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'رقم_الهاتف')['values'][0] ?? ''),
                        'company_name' =>
                            ($fields->firstWhere('name', 'company_name')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'nom_de_l’entreprise')['values'][0] ?? null) ??
                            ($fields->firstWhere('name', 'اسم_الشركة')['values'][0] ?? ''),
                        'country' => $fields->firstWhere('name', 'country')['values'][0] ?? '',
                    ];

                    $additionalMessage = $fields->reject(function ($item) use ($excludedFields) {
                        return in_array($item['name'], $excludedFields);
                    })->map(function ($item) {
                        $label = ucwords(str_replace(['_', '?'], [' ', ''], $item['name']));
                        $value = $item['values'][0] ?? '';
                        return "$label: $value";
                    })->implode("\n");
                    // Require at least name and phone/email
                    // if (!$leadInfo['full_name'] || (!$leadInfo['phone_number'] && !$leadInfo['email'])) {
                    //     \Log::info('Skipped incomplete lead:', $leadInfo);
                    //     continue;
                    // }
                    if (str_contains($leadInfo['full_name'], '<test lead')) {
                        continue; // skip test leads
                    }
                    $user = User::find(36); // Customize this as needed
                    $creatorId = $user->creatorId();

                    $pipeline = Pipeline::find($pipelineId);
                    $stage = LeadStage::where('pipeline_id', $pipeline?->id)->first();

                    $leadModel = new \App\Models\Lead(); // To access fillable column names if needed
                    $leadRoleUserId = null;

                    $allRoles = LeadRole::with('conditions')->where('active', 1)->where('pipeline_id', $pipeline?->id)->get();
                    \Log::info('👥 Total roles loaded: ' . $allRoles->count());

                    if (!$pipeline || !$stage) {
                        \Log::warning("⚠️ Pipeline or stage missing for creator: $creatorId");
                        continue;
                    }
                    $metaLeadId = isset($lead['id']) ? (string) $lead['id'] : null;
                    if ($metaLeadId && Lead::where('lead_id', $metaLeadId)->exists()) {
                        \Log::info('⛔ Duplicate Meta lead skipped (lead_id): ' . $metaLeadId);
                        continue;
                    }

                    // 🔍 Check for existing lead by phone (same form) if Meta id missing
                    $existingLead = Lead::where(function ($query) use ($leadInfo) {
                        $query->where('phone', $leadInfo['phone_number'])
                            ->orWhere('whatsapp', $leadInfo['phone_number']);
                    })
                        ->where('source_url', $formName)
                        ->first();

                    if ($existingLead) {
                        \Log::info("⛔ Duplicate lead skipped: " . json_encode($leadInfo));
                        continue; // Skip saving this lead
                    }
                    $leadBD = Lead::create([
                        'name' => $leadInfo['full_name'],
                        'email' => $leadInfo['email'],
                        'phone' => $leadInfo['phone_number'],
                        'subject' => $leadInfo['company_name'] ?? '',
                        'country' => $leadInfo['country'] ?? '',
                        'message' => $additionalMessage,
                        'source' => 'Meta Ads',
                        'source_url' => $formName,
                        'gclid' => '',
                        'lead_id' => $metaLeadId,
                        'user_id' => $user->id,
                        'pipeline_id' => $pipeline->id,
                        'stage_id' => $stage->id,
                        'created_by' => $creatorId,
                        'sources' => Source::whereRaw('LOWER(name) LIKE ?', ['%meta%'])->where('pipeline_id', $pipeline->id)->pluck('id')->implode(','),
                        'whatsapp' => $leadInfo['phone_number'],
                        'date' => \Carbon\Carbon::parse($lead['created_time'])->format('Y-m-d H:i:s'),
                    ]);
                    $leadBD->quantity = $leadBD->parseQuantityFromMessage();
                    $leadBD->save();
                    $campaigns = \App\Models\Campaign::where('status', 'active')->get();

                    foreach ($campaigns as $campaign) {
                        $urls = array_map('trim', explode(',', strtolower($campaign->url)));
                        // \Log::info("🔍 Split URLs: ", $campaign->source_id);
                        foreach ($urls as $url) {
                            if (Str::contains(strtolower($leadBD->source_url), strtolower($url))) {
                                $leadBD->sources = $campaign->source_id ?? $leadBD->sources;
                                $leadBD->country = $leadBD->country ?? $campaign->target_country;
                                $leadBD->save();

                                \Log::info("📌 Campaign matched. Updated lead {$leadBD->id} with source: {$leadBD->source}, country: {$leadBD->country}");
                                break; // Optional: stop after the first match
                            }
                        }
                    }
                    $leadRoleUserId = null;

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

                            // Normalize lead value once - remove non-breaking spaces and normalize
                            $leadValue = strtolower((string) ($leadBD->$column ?? ''));
                            $leadValue = str_replace(["\xc2\xa0", "\xC2\xA0"], ' ', $leadValue);
                            $leadValue = preg_replace('/\s+/', ' ', $leadValue); // Replace multiple spaces with single space
                            $leadValue = trim($leadValue);

                            foreach ($conditions as $condition) {
                                $operation = $condition->operation;
                                
                                if (in_array($operation, ['is_empty', 'is_not_empty'])) {
                                    $value = null;
                                } else {
                                    $value = strtolower(trim($condition->value));
                                    $value = str_replace(["\xc2\xa0", "\xC2\xA0"], ' ', $value);
                                    $value = preg_replace('/\s+/', ' ', $value); // Replace multiple spaces with single space
                                    $value = trim($value);
                                }

                                $match = match ($operation) {
                                    '=' => $leadValue === $value,
                                    '!=' => $leadValue !== $value,
                                    'contains' => str_contains($leadValue, $value),
                                    'not_contains' => !str_contains($leadValue, $value),
                                    'starts_with' => str_starts_with($leadValue, $value),
                                    'ends_with' => str_ends_with($leadValue, $value),
                                    'is_empty' => empty($leadValue),
                                    'is_not_empty' => !empty($leadValue),
                                    '>' => is_numeric($leadValue) && is_numeric($value) && $leadValue > $value,
                                    '<' => is_numeric($leadValue) && is_numeric($value) && $leadValue < $value,
                                    default => false,
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
                            $userName = User::find($leadRoleUserId)?->name ?? 'Unknown User';
                            LeadActivityLog::create(
                                [
                                    'user_id' => 36,
                                    'lead_id' => $leadBD->id,
                                    'log_type' => 'Role matched',
                                    'remark' => json_encode(
                                        [
                                            'role' => "✅ Role '{$role->name}' matched dynamically. Assigning to user  {$userName}",
                                        ]
                                    ),
                                ]
                            );
                        }
                    }
                    // $userDB = $leadRoleUserId ? $leadRoleUserId : User::find(36)->id;
                    if ($leadRoleUserId) {
                        UserLead::create(
                            [
                                'user_id' => $leadRoleUserId,
                                'lead_id' => $leadBD->id,
                            ]
                        );
                        LeadActivityLog::create(
                            [
                                'user_id' => 36,
                                'lead_id' => $leadBD->id,
                                'log_type' => 'Add user',
                                'remark' => json_encode(
                                    [
                                        'user' => User::find($leadRoleUserId)->name,
                                    ]
                                ),
                            ]
                        );
                    }

                }

                $this->info("✅ Finished processing form $formName.");
            }
        }

        $this->info('🎉 Done fetching and saving Facebook leads.');
        return 0;
    }
}
