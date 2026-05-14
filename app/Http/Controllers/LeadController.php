<?php

namespace App\Http\Controllers;

use App\Mail\SendLeadEmail;
use App\Models\ClientDeal;
use App\Models\Deal;
use App\Models\DealCall;
use App\Models\DealDiscussion;
use App\Models\DealEmail;
use App\Models\DealFile;
use App\Models\Label;
use App\Models\Lead;
use App\Models\LeadActivityLog;
use App\Models\LeadCall;
use App\Models\LeadDiscussion;
use App\Models\LeadEmail;
use App\Models\LeadFile;
use App\Models\LeadStage;
use App\Models\Pipeline;
use App\Models\ProductService;
use App\Models\Source;
use App\Models\Stage;
use App\Models\User;
use App\Models\UserDeal;
use App\Models\UserLead;
use App\Models\Utility;
use App\Models\LeadProduct;
use App\Models\DealProduct;
use App\Models\Notification;
use App\Models\WebhookSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeadExport;
use App\Imports\LeadImport;
// use App\Services\FacebookLeadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    private function canManageCrmAdmin($user = null): bool
    {
        $user = $user ?: \Auth::user();

        return $user && $user->can('manage crm admin');
    }

    private function canManageLead($user = null): bool
    {
        $user = $user ?: \Auth::user();

        return $user && ($user->can('manage lead') || $this->canManageCrmAdmin($user));
    }

    private function canLeadAction(string $permission, $user = null): bool
    {
        $user = $user ?: \Auth::user();

        return $user && ($user->can($permission) || $this->canManageCrmAdmin($user));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // protected $facebookLeadService;

    // public function __construct(FacebookLeadService $facebookLeadService)
    // {
    //     $this->facebookLeadService = $facebookLeadService;
    // }
    public function index(Request $request)
    {
        if ($this->canManageLead()) {
            if (\Auth::user()->default_pipeline) {
                $pipeline = Pipeline::where('created_by', '=', \Auth::user()->creatorId())->where('id', '=', \Auth::user()->default_pipeline)->first();
                if (!$pipeline) {
                    $pipeline = Pipeline::where('created_by', '=', \Auth::user()->creatorId())->first();
                }
            } else {
                $pipeline = Pipeline::where('created_by', '=', \Auth::user()->creatorId())->first();
            }

            $pipelines = Pipeline::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $users = User::where('created_by', \Auth::user()->creatorId())
                ->where('type', '!=', 'client')
                ->pluck('name', 'id');
            // if (!empty($request->user)) {
            //     $query->where('vender_id', '=', $request->vender);
            // }
            return view('leads.index', compact('pipelines', 'pipeline', 'users'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function lead_list(Request $request)
    {
        $usr = \Auth::user();
        $hasCrmAdminAccess = $this->canManageCrmAdmin($usr);

        if ($this->canManageLead($usr)) {
            if (!empty($request->default_pipeline_id)) {
                $pipeline = Pipeline::find($request->default_pipeline_id);
            } else {
                if ($usr->default_pipeline) {
                    $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->where('id', '=', $usr->default_pipeline)->first();
                    if (!$pipeline) {
                        $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->first();
                    }
                } else {
                    $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->first();
                }
            }
            if (!$pipeline) {
                return redirect()->back()->with('error', __('Please create a pipeline first.'));
            }
            $columns = [
                'checkbox',
                'name',
                'subject',
                'stage',
                'date',
                'qty',
                'payment',
                'notes',
                'source',
                'source_url',
                'whatsapp',
                'users',
                'action'
            ];
            $pipelines = Pipeline::where('created_by', '=', $usr->creatorId())->get()->pluck('name', 'id');
            $userId = !empty($request->user_id) ? $request->user_id : $usr->id;
            $draw = $request->get('draw');
            $perPage = 50; // You can adjust the number of items per page
            $leadsQuery = Lead::query()->select('leads.*')->where('leads.pipeline_id', $pipeline->id);
            // Total records before filtering

            // Pagination
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 100);
            if (\Auth::user()->type == 'company' || $hasCrmAdminAccess) {
                $leadsQuery->where('leads.created_by', \Auth::user()->creatorId());
                $users =  User::where('created_by', \Auth::user()->creatorId())->where('type', '!=', 'client')->pluck('name', 'id');
            } elseif (\Auth::user()->type == 'manager') {
                $leadsQuery->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')
                    ->whereIn('user_leads.user_id', function ($query) {
                        $query->select('id')
                            ->from('users')
                            ->where('manager_id', \Auth::id())
                            ->orWhere('id', \Auth::id());
                    });
                $users =  User::where('manager_id', \Auth::user()->id)->where('type', '!=', 'client')->pluck('name', 'id');
            } else { // return only authenticated user leads and leads assigned to authenticated user selected user_id when filter
                $leadsQuery->join('user_leads', 'user_leads.lead_id', 'leads.id')
                    ->where('leads.created_by', \Auth::user()->creatorId())
                    ->where('user_leads.user_id', $userId);

                $users =  User::where('id', \Auth::user()->id)->where('type', '!=', 'client')->pluck('name', 'id');
            }
            // If user_id is passed as a request filter, override the above

            if (!empty($request->user_id)) {
                $leadsQuery = Lead::select('leads.*')
                    ->join('user_leads', 'user_leads.lead_id', 'leads.id')
                    ->where('leads.created_by', \Auth::user()->creatorId())
                    ->where('user_leads.user_id', $request->user_id)
                    ->where('leads.pipeline_id', $pipeline->id);
            }

            // Filter by stage_id if provided
            if (!empty($request->stage_id)) {
                $leadsQuery->where('leads.stage_id', $request->stage_id);
            }

            // Filter by date range if provided
            if (!empty($request->from_date)) {
                $leadsQuery->where('leads.date', '>=', $request->from_date);
            }

            if (!empty($request->to_date)) {
                $leadsQuery->where('leads.date', '<=', $request->to_date);
            }

            $totalRecords = $leadsQuery->count();

            // Global search
            if (!empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $leadsQuery->where(function ($q) use ($search) {
                    $q->where('leads.name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('stage', function ($subQ) use ($search) {
                            $subQ->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('date', 'like', "%{$search}%")
                        // Handle 'bulk' search for quantity
                        ->orWhere(function ($subQ) use ($search) {
                            if (strtolower(trim($search)) === 'bulk') {
                                $subQ->where('quantity', '>=', 3);
                            } else {
                                $subQ->whereRaw('CAST(quantity AS CHAR) LIKE ?', ["%{$search}%"]);
                            }
                        })
                        ->orWhere('payment', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('source_url', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhereHas('users', function ($subQ) use ($search) {
                            $subQ->where('users.name', 'like', "%{$search}%");
                        });
                    // Add more fields as needed
                });
            }

            // Total records for the authenticated user
            // Ordering

            $shownLeads = $leadsQuery->count();
            // Now apply pagination
            if (!empty($request->input('order.0.column'))) {
                $orderColIdx = $request->input('order.0.column');
                $orderCol = $columns[$orderColIdx];
                $orderDir = $request->input('order.0.dir');

                // Map DataTables column to database field
                switch ($orderCol) {
                    case 'name':
                        $leadsQuery->orderBy('name', $orderDir);
                        break;
                    case 'subject':
                        $leadsQuery->orderBy('subject', $orderDir);
                        break;
                    case 'stage':
                        // Join with lead_stages table to order by stage name
                        $leadsQuery->leftJoin('lead_stages', 'leads.stage_id', '=', 'lead_stages.id')
                            ->orderBy('lead_stages.name', $orderDir)
                            ->select('leads.*');
                        break;
                    case 'date':
                        $leadsQuery->orderBy('date', $orderDir);
                        break;
                    case 'qty':
                        // Order by quantity field
                        $leadsQuery->orderBy('quantity', $orderDir);
                        break;
                    case 'payment':
                        $leadsQuery->orderBy('payment', $orderDir);
                        break;
                    case 'notes':
                        $leadsQuery->orderBy('notes', $orderDir);
                        break;
                    case 'source':
                        $leadsQuery->orderBy('source', $orderDir);
                        break;
                    case 'source_url':
                        $leadsQuery->orderBy('source_url', $orderDir);
                        break;
                    case 'whatsapp':
                        $leadsQuery->orderBy('whatsapp', $orderDir);
                        break;
                    case 'users':
                        // Order by first user's name (if exists)
                        $leadsQuery->leftJoin('user_leads as ul', 'leads.id', '=', 'ul.lead_id')
                            ->leftJoin('users as u', 'ul.user_id', '=', 'u.id')
                            ->orderBy('u.name', $orderDir)
                            ->select('leads.*');
                        break;
                    case 'checkbox':
                    case 'action':
                    default:
                        $leadsQuery->orderBy('date', 'desc');
                        break;
                }
            } else {
                // Default ordering by date if no specific order is requested
                $leadsQuery->orderBy('date', 'desc');
            }
            $leads = $leadsQuery->skip($start)->take($length)->get();
            // Total records for the filtered and paginated leads

            if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
                $data = [];
                foreach ($leads as $lead) {
                    $data[] = [
                        'checkbox' => '<input type="checkbox" class="lead-checkbox" value="' . $lead->id . '">',
                        'name' => '<div><a href="' . route('leads.show', $lead->id) . '" data-bs-toggle="tooltip" title="' . strip_tags($lead->name) . '">' . \Illuminate\Support\Str::limit(strip_tags($lead->name), 30) . '</a></div>',
                        'subject' => '<div><span data-bs-toggle="tooltip" title="' . strip_tags($lead->subject) . '">' . \Illuminate\Support\Str::limit(strip_tags($lead->subject), 30) . '</span></div>',
                        'stage' => $lead->stage ? $lead->stage->name : '-',
                        'date' => $lead->date ?: '-',
                        'qty' => $lead->display_quantity,
                        'payment' => $lead->payment ?: '-',
                        'notes' => $lead->notes
                            ? '<p data-bs-toggle="tooltip" data-bs-placement="top" title="' . strip_tags($lead->notes) . '">' . \Illuminate\Support\Str::limit(strip_tags($lead->notes), 30) . '</p>'
                            : '-',
                        'source' => $lead->sources()->pluck('name')->implode(', '),
                        'source_url' => '<div><span data-bs-toggle="tooltip" title="' . str_replace(' ', '&nbsp;', html_entity_decode(strip_tags($lead->source_url))) . '">' . \Illuminate\Support\Str::limit(str_replace(' ', '&nbsp;', html_entity_decode(strip_tags($lead->source_url))), 30) . '</span></div>',
                        'whatsapp' => $lead->whatsapp
                            ? (function () use ($lead) {
                                // Remove everything except numbers
                                $whatsapp = preg_replace('/\D+/', '', $lead->whatsapp);

                                // Only process if we have a valid number
                                if (empty($whatsapp)) {
                                    return e($lead->whatsapp);
                                }

                                // If number already starts with country code (971), use as is
                                // If number starts with 0, replace with country code (UAE = 971)
                                // Otherwise, assume it's already in correct format
                                if (substr($whatsapp, 0, 3) === '971') {
                                    // Already has country code, use as is
                                    $formattedNumber = $whatsapp;
                                } elseif (substr($whatsapp, 0, 1) === '0') {
                                    // Local number starting with 0, replace with country code
                                    $formattedNumber = '971' . substr($whatsapp, 1);
                                } else {
                                    // Assume it's already in international format or local without 0
                                    $formattedNumber = $whatsapp;
                                }

                                // Ensure we have a valid number (at least 7 digits total)
                                if (strlen($formattedNumber) < 7) {
                                    return e($lead->whatsapp);
                                }

                                return '<a href="https://wa.me/' . $formattedNumber . '" class="whatsapp-link" target="_blank" rel="noopener" data-id="' . $lead->id . '">' . e($lead->whatsapp) . '</a>';
                            })()
                            : '-',
                        'users' => $lead->users->pluck('name')->implode(', '),
                        'action' => view('leads.partials.actions', compact('lead'))->render(),
                    ];
                }
                return response()->json([
                    'draw' => intval($draw),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $shownLeads,
                    'data' => $data
                ]);
            }

            $sources = Source::where('created_by', '=', \Auth::user()->creatorId())->where('pipeline_id', $pipeline->id)->orderBy('order')->get()->pluck('name', 'id');
            return view('leads.list', compact('pipelines', 'pipeline', 'users', 'sources'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if ($this->canLeadAction('create lead')) {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('manage crm admin')) {
                $users = User::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('type', ['client', 'company'])
                    ->where('id', '!=', \Auth::user()->id)
                    ->get()
                    ->pluck('name', 'id');
                $sources        = Source::where('created_by', '=', \Auth::user()->creatorId())->where('pipeline_id', \Auth::user()->default_pipeline)->orderBy('order')->get()->pluck('name', 'id');
                //  $pipeline = Pipeline::where('id', '=', \Auth::user()->default_pipeline)->first();
                $stageCnt      = LeadStage::where('pipeline_id', '=', \Auth::user()->default_pipeline)->get();
            } else {
                $users = collect([\Auth::user()->id => \Auth::user()->name]); // wrap in a collection
                $pipeline = Pipeline::where('id', '=', \Auth::user()->default_pipeline)->first();
                $stageCnt      = LeadStage::where('pipeline_id', '=', $pipeline->id)->get();
                $sources        = Source::where('created_by', '=', \Auth::user()->creatorId())->where('pipeline_id', '=', \Auth::user()->default_pipeline)->orderBy('order')->get()->pluck('name', 'id');
            }

            $users->prepend(__('Select User'), '');

            return view('leads.create', compact('users', 'sources', 'stageCnt'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $usr = \Auth::user();
        if ($this->canLeadAction('create lead', $usr)) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'subject' => 'required',
                    'name' => 'required',
                    'gclid' => 'nullable|string',
                    'email' => 'required|email',

                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            // Default Field Value
            if ($usr->default_pipeline) {
                $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->where('id', '=', $usr->default_pipeline)->first();
                if (!$pipeline) {
                    $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->first();
                }
            } else {
                $pipeline = Pipeline::where('created_by', '=', $usr->creatorId())->first();
            }

            $stage = LeadStage::where('pipeline_id', '=', $pipeline->id)->first();
            // End Default Field Value

            if (empty($stage)) {
                return redirect()->back()->with('error', __('Please Create Stage for This Pipeline.'));
            } else {
                $lead              = new Lead();
                $lead->name        = $request->name;
                $lead->email       = $request->email;
                $lead->phone       = $request->phone;
                $lead->whatsapp       = $request->whatsapp;
                $lead->subject     = $request->subject;
                $lead->user_id     = $request->user_id;
                $lead->pipeline_id = $pipeline->id;
                $lead->stage_id    = $stage->id;
                $lead->created_by  = $usr->creatorId();
                $lead->date        = date('Y-m-d H:i:s');
                $lead->quantity       = $request->qty;
                $lead->stage_id    = $request->stage_id;
                $lead->notes       = $request->notes;
                $lead->payment       = $request->payment;
                if (!empty($request->sources)) {
                    $lead->sources     = implode(",", array_filter($request->sources));
                }
                $lead->save();

                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Create Lead',
                        'remark' => json_encode(
                            [
                                'create' => $lead->name,
                            ]
                        ),
                    ]
                );
                // if ($request->user_id != \Auth::user()->id) {
                //     $usrLeads = [
                //         $usr->id,
                //         $request->user_id,
                //     ];
                // } else {
                $usrLeads = [
                    $request->user_id,
                ];
                // }

                foreach ($usrLeads as $usrLead) {
                    UserLead::create(
                        [
                            'user_id' => $usrLead,
                            'lead_id' => $lead->id,
                        ]
                    );
                    LeadActivityLog::create(
                        [
                            'user_id' => \Auth::user()->id,
                            'lead_id' => $lead->id,
                            'log_type' => 'Add user',
                            'remark' =>  json_encode(
                                [
                                    'user' => User::find($usrLead)->name,
                                ]
                            ),
                        ]
                    );
                }

                $leadArr = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'updated_by' => $usr->id,
                ];
                $lArr    = [
                    'lead_name' => $lead->name,
                    'lead_email' => $lead->email,
                    'lead_pipeline' => $pipeline->name,
                    'lead_stage' => $stage->name,
                ];

                $usrEmail = User::find($request->user_id);

                $lArr    = [
                    'lead_name' => $lead->name,
                    'lead_email' => $lead->email,
                    'lead_pipeline' => $pipeline->name,
                    'lead_stage' => $stage->name,
                ];

                // Send Email
                $setings = Utility::settings();
                if ($setings['lead_assigned'] == 1) {
                    $usrEmail = User::find($request->user_id);
                    $leadAssignArr = [
                        'lead_name' => $lead->name,
                        'lead_email' => $lead->email,
                        'lead_subject' => $lead->subject,
                        'lead_pipeline' => $pipeline->name,
                        'lead_stage' => $stage->name,
                    ];
                    $resp = Utility::sendEmailTemplate('lead_assigned', [$usrEmail->id => $usrEmail->email], $leadAssignArr);
                }

                //For Notification
                $setting  = Utility::settings(\Auth::user()->creatorId());
                $leadArr = [
                    'user_name' => \Auth::user()->name,
                    'lead_name' => $lead->name,
                    'lead_email' => $lead->email,
                ];
                //Slack Notification
                if (isset($setting['lead_notification']) && $setting['lead_notification'] == 1) {
                    Utility::send_slack_msg('new_lead', $leadArr);
                }

                //Telegram Notification
                if (isset($setting['telegram_lead_notification']) && $setting['telegram_lead_notification'] == 1) {
                    Utility::send_telegram_msg('new_lead', $leadArr);
                }

                //webhook
                $module = 'New Lead';
                $webhook =  Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($lead);
                    // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->back()->with('success', __('Lead successfully created!') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }
                return redirect()->back()->with('success', __('Lead successfully created!') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Lead $lead
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Lead $lead)
    {
        if ($lead->is_active) {
            $calenderTasks = [];
            $deal          = Deal::where('id', '=', $lead->is_converted)->first();
            $stageCnt      = LeadStage::where('pipeline_id', '=', $lead->pipeline_id)->get();
            $i             = 0;
            foreach ($stageCnt as $stage) {
                $i++;
                if ($stage->id == $lead->stage_id) {
                    break;
                }
            }
            $precentage = number_format(($i * 100) / count($stageCnt));

            return view('leads.show', compact('lead', 'calenderTasks', 'deal', 'precentage'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Lead $lead
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Lead $lead)
    {
        if ($this->canLeadAction('edit lead')) {
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $pipelines = Pipeline::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $pipelines->prepend(__('Select Pipeline'), '');
                $sources        = Source::where('created_by', '=', \Auth::user()->creatorId())->where('pipeline_id', '=', \Auth::user()->default_pipeline)->orderBy('order')->get()->pluck('name', 'id');
                $products = ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
                    ->get()
                    ->map(function ($productService) {
                        $category = $productService->category->name ?? '';
                        $brand = $productService->brand->name ?? '';
                        $subBrand = $productService->subBrand->name ?? '';
                        $productName = $productService->name;
                        $productSku = $productService->sku;

                        return [
                            'id' => $productService->id,
                            'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productSku,
                        ];
                    })
                    ->pluck('name', 'id');
                $users          = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'company')->where('id', '!=', \Auth::user()->id)->get()->pluck('name', 'id');
                $lead->sources  = explode(',', $lead->sources);
                $lead->products = explode(',', $lead->products);
                $stageCnt = LeadStage::where('pipeline_id', $lead->pipeline_id)
                    ->where('created_by', $lead->created_by)
                    ->where('name', 'not like', '%deal%')
                    ->get();
                return view('leads.edit', compact('lead', 'pipelines', 'sources', 'products', 'users', 'stageCnt'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Lead $lead
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lead $lead)
    {
        if ($this->canLeadAction('edit lead')) {
            // if ($lead->created_by == \Auth::user()->creatorId()) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'subject' => 'required',
                    'name' => 'required',
                    'email' => 'required|email',
                    'pipeline_id' => 'required',
                    // 'user_id' => 'required',
                    'stage_id' => 'required',
                    'sources' => 'required',
                    'notes' => 'required',
                ]
            );
            $lead->name        = $request->name;
            $lead->email       = $request->email;
            $lead->phone       = $request->phone;
            $lead->whatsapp       = $request->whatsapp;
            $lead->subject     = $request->subject;
            $lead->quantity     = $request->qty;
            $lead->payment       = $request->payment;
            // $lead->user_id     = $request->user_id;
            // $lead->pipeline_id = $request->pipeline_id;
            if ($request->stage_id != $lead->stage_id) {
                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Update stage',
                        'remark' => json_encode(
                            [
                                'stage' => LeadStage::find($request->stage_id)->name,
                            ]
                        ),
                    ]
                );
            }
            $lead->stage_id    = $request->stage_id;
            if (!empty($request->sources)) {
                $lead->sources     = implode(",", array_filter($request->sources));
            }
            if (!empty($request->products)) {
                $lead->products    = implode(",", array_filter($request->products));
            }
            if ($request->notes !== $lead->notes) {
                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Update Note',
                        'remark' => json_encode(
                            [
                                'note' => $request->notes,
                            ]
                        ),
                    ]
                );
            }
            $lead->notes       = $request->notes;
            $lead->save();

            return redirect()->back()->with('success', __('Lead successfully updated!'));
            // } else {
            //     return redirect()->back()->with('error', __('Permission Denied.'));
            // }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Lead $lead
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lead $lead)
    {
        if ($this->canLeadAction('delete lead')) {
            if ($lead->created_by == \Auth::user()->creatorId()) {
                LeadDiscussion::where('lead_id', '=', $lead->id)->delete();
                LeadFile::where('lead_id', '=', $lead->id)->delete();
                UserLead::where('lead_id', '=', $lead->id)->delete();
                LeadActivityLog::where('lead_id', '=', $lead->id)->delete();
                $lead->delete();

                return redirect()->back()->with('success', __('Lead successfully deleted!'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function json(Request $request)
    {
        $lead_stages = new LeadStage();
        if ($request->pipeline_id && !empty($request->pipeline_id)) {


            $lead_stages = $lead_stages->where('pipeline_id', '=', $request->pipeline_id);
            $lead_stages = $lead_stages->get()->pluck('name', 'id');
        } else {
            $lead_stages = [];
        }

        return response()->json($lead_stages);
    }

    public function fileUpload($id, Request $request)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {

                //storage limit
                $image_size = $request->file('file')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                $file_name = $request->file->getClientOriginalName();
                $file_path = $request->lead_id . "_" . md5(time()) . "_" . $request->file->getClientOriginalName();

                $file                 = LeadFile::create(
                    [
                        'lead_id' => $request->lead_id,
                        'file_name' => $file_name,
                        'file_path' => $file_path,
                    ]
                );
                if ($result == 1) {
                    $request->file->storeAs('lead_files', $file_path);
                    $return               = [];
                    $return['is_success'] = true;
                    $return['download']   = route(
                        'leads.file.download',
                        [
                            $lead->id,
                            $file->id,
                        ]
                    );
                    $return['delete']     = route(
                        'leads.file.delete',
                        [
                            $lead->id,
                            $file->id,
                        ]
                    );
                } else {
                    $return               = [];
                    $return['is_success'] = true;
                    $return['status'] = 1;
                    $return['success_msg'] = ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '');
                }

                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Upload File',
                        'remark' => json_encode(['file_name' => $file_name]),
                    ]
                );

                return response()->json($return);
            } else {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ],
                    401
                );
            }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function fileDownload($id, $file_id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $file = LeadFile::find($file_id);
                if ($file) {
                    $file_path = storage_path('app/public/lead_files/' . $file->file_path);
                    $filename  = $file->file_name;

                    return \Response::download(
                        $file_path,
                        $filename,
                        [
                            'Content-Length: ' . filesize($file_path),
                        ]
                    );
                } else {
                    return redirect()->back()->with('error', __('File is not exist.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function fileDelete($id, $file_id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $file = LeadFile::find($file_id);
                if ($file) {

                    //storage limit
                    $file_path = 'lead_files/' . $file->file_path;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

                    $path = storage_path('lead_files/' . $file->file_path);
                    if (file_exists($path)) {
                        \File::delete($path);
                    }
                    $file->delete();

                    return response()->json(['is_success' => true], 200);
                } else {
                    return response()->json(
                        [
                            'is_success' => false,
                            'error' => __('File is not exist.'),
                        ],
                        200
                    );
                }
            } else {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ],
                    401
                );
            }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function noteStore($id, Request $request)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            // if ($lead->created_by == \Auth::user()->creatorId()) {
            $lead->notes = $request->notes;
            $lead->save();
            LeadActivityLog::create(
                [
                    'user_id' => \Auth::user()->id,
                    'lead_id' => $lead->id,
                    'log_type' => 'Update Note',
                    'remark' => json_encode(
                        [
                            'note' => $request->notes,
                        ]
                    ),
                ]
            );
            $is_deal = Deal::where('lead_id', $lead->id)->first();
            if ($is_deal) {
                $is_deal->notes = $request->notes;
                $is_deal->save();
            }
            return response()->json(
                [
                    'is_success' => true,
                    'success' => __('Note successfully saved!'),
                ],
                200
            );
            // } else {
            //     return response()->json(
            //         [
            //             'is_success' => false,
            //             'error' => __('Permission Denied.'),
            //         ],
            //         401
            //     );
            // }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function labels($id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $labels   = Label::where('pipeline_id', '=', $lead->pipeline_id)->where('created_by', \Auth::user()->creatorId())->get();
                $selected = $lead->labels();
                if ($selected) {
                    $selected = $selected->pluck('name', 'id')->toArray();
                } else {
                    $selected = [];
                }

                return view('leads.labels', compact('lead', 'labels', 'selected'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function labelStore($id, Request $request)
    {
        if ($this->canLeadAction('edit lead')) {
            $leads = Lead::find($id);
            if ($leads->created_by == \Auth::user()->creatorId()) {
                if ($request->labels) {
                    $leads->labels = implode(',', $request->labels);
                } else {
                    $leads->labels = $request->labels;
                }
                $leads->save();

                return redirect()->back()->with('success', __('Labels successfully updated!'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function userEdit($id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);

            if ($lead->created_by == \Auth::user()->creatorId()) {
                $users = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'company')->whereNOTIn(
                    'id',
                    function ($q) use ($lead) {
                        $q->select('user_id')->from('user_leads')->where('lead_id', '=', $lead->id);
                    }
                )->get();


                $users = $users->pluck('name', 'id');

                return view('leads.users', compact('lead', 'users'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function userUpdate($id, Request $request)
    {
        if ($this->canLeadAction('edit lead')) {
            $usr  = \Auth::user();
            $lead = Lead::find($id);

            if ($lead->created_by == $usr->creatorId()) {
                if (!empty($request->users)) {
                    $users   = array_filter($request->users);
                    $leadArr = [
                        'lead_id' => $lead->id,
                        'name' => $lead->name,
                        'updated_by' => $usr->id,
                    ];

                    foreach ($users as $user) {
                        UserLead::create(
                            [
                                'lead_id' => $lead->id,
                                'user_id' => $user,
                            ]
                        );
                        LeadActivityLog::create(
                            [
                                'user_id' => \Auth::user()->id,
                                'lead_id' => $lead->id,
                                'log_type' => 'Add user',
                                'remark' =>  json_encode(
                                    [
                                        'user' => User::find($user)->name,
                                    ]
                                ),
                            ]
                        );
                    }
                }

                if (!empty($users) && !empty($request->users)) {
                    return redirect()->back()->with('success', __('Users successfully updated!'));
                } else {
                    return redirect()->back()->with('error', __('Please Select Valid User!'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function userDestroy($id, $user_id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                UserLead::where('lead_id', '=', $lead->id)->where('user_id', '=', $user_id)->delete();
                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Remove User',
                        'remark' => json_encode(
                            [
                                'user' => User::find($user_id)->name,
                            ]
                        ),
                    ]
                );
                return redirect()->back()->with('success', __('User successfully deleted!'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function productEdit($id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $products = ProductService::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('id', explode(',', $lead->products))
                    ->with(['brand', 'subBrand', 'category'])
                    ->get()
                    ->map(function ($productService) {
                        $category = $productService->category->name ?? '';
                        $brand = $productService->brand->name ?? '';
                        $subBrand = $productService->subBrand->name ?? '';
                        $productName = $productService->name;
                        $productSku = $productService->sku;

                        return [
                            'id' => $productService->id,
                            'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName . '/' . $productSku,
                        ];
                    })
                    ->pluck('name', 'id');


                return view('leads.products', compact('lead', 'products'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function productUpdate($id, Request $request)
    {
        if (!$this->canLeadAction('edit lead')) {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'products');
        }

        $user = \Auth::user();
        $lead = Lead::find($id);

        if (!$lead || $lead->created_by != $user->creatorId()) {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'products');
        }

        if (empty($request->products) || !is_array($request->products)) {
            return redirect()->back()->with('error', __('Please select valid products.'))->with('status', 'products');
        }

        $newProductIds = [];

        foreach ($request->products as $productData) {
            if (isset($productData['id']) && isset($productData['quantity']) && $productData['quantity'] > 0) {
                $productId = $productData['id'];
                $quantity = $productData['quantity'];
                $price = $productData['price'];

                // Save to lead_products table
                LeadProduct::create([
                    'lead_id'    => $lead->id,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'price'   => $price,
                    'created_by' => $user->id,
                ]);

                // Save ID for later use in lead table
                $newProductIds[] = $productId;

                // Log activity (optional)
                $productName = \App\Models\ProductService::find($productId)->name ?? 'Unknown';
                LeadActivityLog::create([
                    'user_id'  => $user->id,
                    'lead_id'  => $lead->id,
                    'log_type' => 'Add Product',
                    'remark'   => json_encode(['title' => $productName]),
                ]);
            }
        }

        // Merge with existing products in lead table
        $existingProductIds = !empty($lead->products) ? explode(',', $lead->products) : [];
        $allProductIds = array_unique(array_merge($existingProductIds, $newProductIds));
        $lead->products = implode(',', $allProductIds);
        $lead->save();

        return redirect()->back()->with('success', __('Products successfully updated!'))->with('status', 'products');
    }


    public function productDestroy($id, $product_id)
    {
        if (!$this->canLeadAction('edit lead')) {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'products');
        }

        $lead = Lead::find($id);
        if (!$lead || $lead->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'products');
        }

        // Remove from lead_products table
        LeadProduct::where('lead_id', $id)
            ->where('product_id', $product_id)
            ->delete();

        // Update the comma-separated field in the leads table
        $productIds = !empty($lead->products) ? explode(',', $lead->products) : [];
        $productIds = array_filter($productIds, function ($id) use ($product_id) {
            return (int)$id !== (int)$product_id;
        });

        $lead->products = implode(',', $productIds);
        $lead->save();

        return redirect()->back()->with('success', __('Product successfully deleted!'))->with('status', 'products');
    }


    public function sourceEdit($id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $sources = Source::where('created_by', '=', \Auth::user()->creatorId())->where('pipeline_id', '=', $lead->pipeline_id)->orderBy('order')->get();

                $selected = $lead->sources();
                if ($selected) {
                    $selected = $selected->pluck('name', 'id')->toArray();
                }

                return view('leads.sources', compact('lead', 'sources', 'selected'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function sourceUpdate($id, Request $request)
    {
        if ($this->canLeadAction('edit lead')) {
            $usr        = \Auth::user();
            $lead       = Lead::find($id);
            $lead_users = $lead->users->pluck('id')->toArray();

            if ($lead->created_by == \Auth::user()->creatorId()) {
                if (!empty($request->sources) && count($request->sources) > 0) {
                    $lead->sources = implode(',', $request->sources);
                } else {
                    $lead->sources = "";
                }

                $lead->save();

                LeadActivityLog::create(
                    [
                        'user_id' => $usr->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Update Sources',
                        'remark' => json_encode(['title' => 'Update Sources']),
                    ]
                );

                $leadArr = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'updated_by' => $usr->id,
                ];

                return redirect()->back()->with('success', __('Sources successfully updated!'))->with('status', 'sources');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'sources');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'sources');
        }
    }

    public function sourceDestroy($id, $source_id)
    {
        if ($this->canLeadAction('edit lead')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $sources = explode(',', $lead->sources);
                foreach ($sources as $key => $source) {
                    if ($source_id == $source) {
                        unset($sources[$key]);
                    }
                }
                $lead->sources = implode(',', $sources);
                $lead->save();

                return redirect()->back()->with('success', __('Sources successfully deleted!'))->with('status', 'sources');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'sources');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'sources');
        }
    }

    public function discussionCreate($id)
    {
        $lead = Lead::find($id);
        if ($lead->created_by == \Auth::user()->creatorId()) {
            return view('leads.discussions', compact('lead'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function discussionStore($id, Request $request)
    {
        $usr        = \Auth::user();
        $lead       = Lead::find($id);
        $lead_users = $lead->users->pluck('id')->toArray();

        if ($lead->created_by == $usr->creatorId()) {
            $discussion             = new LeadDiscussion();
            $discussion->comment    = $request->comment;
            $discussion->lead_id    = $lead->id;
            $discussion->created_by = $usr->id;
            $discussion->save();

            $leadArr = [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'updated_by' => $usr->id,
            ];

            return redirect()->back()->with('success', __('Message successfully added!'))->with('status', 'discussion');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'discussion');
        }
    }

    public function order(Request $request)
    {
        if (\Auth::user()->can('move lead')) {
            $usr        = \Auth::user();
            $post       = $request->all();
            $lead       = $this->lead($post['lead_id']);
            $lead_users = $lead->users->pluck('email', 'id')->toArray();

            if ($lead->stage_id != $post['stage_id']) {
                $newStage = LeadStage::find($post['stage_id']);

                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Move',
                        'remark' => json_encode(
                            [
                                'title' => $lead->name,
                                'old_status' => $lead->stage->name,
                                'new_status' => $newStage->name,
                            ]
                        ),
                    ]
                );

                $leadArr = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'updated_by' => $usr->id,
                    'old_status' => $lead->stage->name,
                    'new_status' => $newStage->name,
                ];

                $lArr = [
                    'lead_name' => $lead->name,
                    'lead_email' => $lead->email,
                    'lead_pipeline' => $lead->pipeline->name,
                    'lead_stage' => $lead->stage->name,
                    'lead_old_stage' => $lead->stage->name,
                    'lead_new_stage' => $newStage->name,
                ];

                // Send Email
                Utility::sendEmailTemplate('Move Lead', $lead_users, $lArr);
            }

            foreach ($post['order'] as $key => $item) {
                $lead           = $this->lead($item);
                $lead->order    = $key;
                $lead->stage_id = $post['stage_id'];
                $lead->save();
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    private static $leadData = NULL;

    public function lead($item)
    {
        if (self::$leadData == null) {
            $lead = Lead::find($item);

            self::$leadData = $lead;
        }
        return self::$leadData;
    }

    public function showConvertToDeal($id)
    {

        $lead         = Lead::findOrFail($id);
        $exist_client = User::where('type', '=', 'client')->where('email', '=', $lead->email)->where('created_by', '=', \Auth::user()->creatorId())->first();
        // $clients      = User::where('type', '=', 'client')->where('created_by', '=', \Auth::user()->creatorId())->get();
        $user = \Auth::user();
        if ($user->type == 'company' || $user->can('manage crm admin')) {
            $creatorId = $user->creatorId();

            $clients = User::where('type', 'client')
                ->where(function ($query) use ($creatorId) {
                    $query->where('created_by', $creatorId)
                        ->orWhereIn('created_by', function ($subQuery) use ($creatorId) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('created_by', $creatorId);
                        });
                })
                ->get();
        } elseif ($user->type == 'Sales') {
            $clients = User::where('type', 'client')
                ->whereIn('id', function ($query) use ($user) {
                    $query->select('client_deals.client_id')
                        ->from('client_deals')
                        ->join('user_deals', 'client_deals.deal_id', '=', 'user_deals.deal_id')
                        ->where('user_deals.user_id', $user->id);
                })
                ->get();
        } elseif ($user->type == 'manager') {
            $clients = \App\Models\User::where('type', 'client')
                ->whereIn('id', function ($query) use ($user) {
                    $query->select('client_deals.client_id')
                        ->from('client_deals')
                        ->join('user_deals', 'client_deals.deal_id', '=', 'user_deals.deal_id')
                        ->whereIn('user_deals.user_id', function ($subQuery) use ($user) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('manager_id', $user->id)
                                ->orWhere('id', $user->id); // include the manager (auth user)
                        });
                })
                ->get();
        } else {
            // fallback: empty collection if no conditions match
            $clients = collect();
        }
        return view('leads.convert', compact('lead', 'exist_client', 'clients'));
    }

    public function convertToDeal($id, Request $request)
    {
        $lead = Lead::findOrFail($id);
        $usr  = \Auth::user();

        // 1) Resolve or create the client
        if ($request->client_check == 'exist') {
            // Try to resolve by selected dropdown value first (email),
            // otherwise fall back to the lead email if it matches an existing client.
            $clientQuery = User::where('type', 'client')
                ->where('created_by', $usr->creatorId());

            if (!empty($request->clients)) {
                $clientQuery->where('email', $request->clients);
            } else {
                $clientQuery->where('email', $lead->email);
            }

            $client = $clientQuery->first();

            if (empty($client)) {
                return redirect()->back()->with('error', __('Client is not available now.'));
            }
        } else {
            $validator = \Validator::make(
                $request->all(),
                [
                    'client_name' => 'required',
                    'client_email' => 'required|email|unique:users,email',
                    'client_password' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $role   = Role::findByName('client');
            $client = User::create(
                [
                    'name' => $request->client_name,
                    'email' => $request->client_email,
                    'password' => \Hash::make($request->client_password),
                    'type' => 'client',
                    'lang' => 'en',
                    'created_by' => $usr->creatorId(),
                ]
            );
            $client->assignRole($role);

            // Send Email to client if they are newly created.
            $cArr = [
                'email' => $request->client_email,
                'password' => $request->client_password,
            ];
            Utility::sendEmailTemplate('New User', [$client->id => $client->email], $cArr);
        }

        // 2) Log the conversion (for both existing and new clients)
        LeadActivityLog::create(
            [
                'user_id' => $usr->id,
                'lead_id' => $lead->id,
                'log_type' => 'Convert Lead to deal',
                'remark' => json_encode(['title' => 'Convert Lead to deal']),
            ]
        );

        // 3) Create the deal and transfer data (shared for both paths)
        $stage = Stage::where('pipeline_id', '=', $lead->pipeline_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%in progress%'])
            ->first();
        if (empty($stage)) {
            return redirect()->back()->with('error', __('Please Create Stage for This Pipeline.'));
        }

        $stageLead = LeadStage::where('pipeline_id', $lead->pipeline_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%deals%'])
            ->first();

        if (!$stageLead) {
            return redirect()->back()->with('error', __('No "deals" stage found in the selected pipeline. Please create a stage containing "deals" in its name.'));
        }

        $lead->stage_id = $stageLead->id;
        $lead->save();

        $deal              = new Deal();
        $deal->name        = $request->name;
        $deal->price       = empty($request->price) ? 0 : $request->price;
        $deal->pipeline_id = $lead->pipeline_id;
        $deal->phone       = $lead->phone;

        $no_stock_stage = Stage::where('pipeline_id', $lead->pipeline_id)
            ->where('name', 'No Stock')
            ->first();
        $high_price_stage = Stage::where('pipeline_id', $lead->pipeline_id)
            ->where('name', 'High Price')
            ->first();

        $deal->stage_id = (is_array($request->is_transfer) && in_array('stage', $request->is_transfer) && $no_stock_stage)
            ? $no_stock_stage->id
            : $stage->id;
        $deal->stage_id = (is_array($request->is_transfer) && in_array('price', $request->is_transfer) && $high_price_stage)
            ? $high_price_stage->id
            : $stage->id;

        $deal->sources  = in_array('sources', $request->is_transfer) ? $lead->sources : '';
        $deal->products = in_array('products', $request->is_transfer) ? $lead->products : '';
        $deal->notes    = in_array('notes', $request->is_transfer) ? $lead->notes : '';
        $deal->labels   = $lead->labels;
        $deal->status   = 'Active';
        $deal->created_by = $lead->created_by;
        $deal->lead_id    = $lead->id;
        $deal->payment    = $lead->payment;
        $deal->save();

        if (in_array('products', $request->is_transfer)) {
            $leadProducts = LeadProduct::where('lead_id', $lead->id)->get();

            foreach ($leadProducts as $leadProduct) {
                $existing = DealProduct::where('deal_id', $deal->id)
                    ->where('product_id', $leadProduct->product_id)
                    ->first();

                if (!$existing) {
                    DealProduct::create([
                        'deal_id'    => $deal->id,
                        'product_id' => $leadProduct->product_id,
                        'quantity'   => $leadProduct->quantity,
                        'price'      => $leadProduct->price,
                    ]);
                }
            }
        }

        ClientDeal::create(
            [
                'deal_id' => $deal->id,
                'client_id' => $client->id,
            ]
        );

        $dealArr = [
            'deal_id' => $deal->id,
            'name' => $deal->name,
            'updated_by' => $usr->id,
        ];

        $pipeline = Pipeline::find($lead->pipeline_id);
        $dArr     = [
            'deal_name' => $deal->name,
            'deal_pipeline' => $pipeline->name,
            'deal_stage' => $stage->name,
            'deal_status' => $deal->status,
            'deal_price' => $usr->priceFormat($deal->price),
        ];
        Utility::sendEmailTemplate('Assign Deal', [$client->id => $client->email], $dArr);

        $leadUsers = UserLead::where('lead_id', '=', $lead->id)->get();
        foreach ($leadUsers as $leadUser) {
            UserDeal::create(
                [
                    'user_id' => $leadUser->user_id,
                    'deal_id' => $deal->id,
                ]
            );
        }

        if (in_array('discussion', $request->is_transfer)) {
            $discussions = LeadDiscussion::where('lead_id', '=', $lead->id)->where('created_by', '=', $usr->creatorId())->get();
            if (!empty($discussions)) {
                foreach ($discussions as $discussion) {
                    DealDiscussion::create(
                        [
                            'deal_id' => $deal->id,
                            'comment' => $discussion->comment,
                            'created_by' => $discussion->created_by,
                        ]
                    );
                }
            }
        }

        if (in_array('files', $request->is_transfer)) {
            $files = LeadFile::where('lead_id', '=', $lead->id)->get();
            if (!empty($files)) {
                foreach ($files as $file) {
                    $source = 'lead_files/' . $file->file_path;
                    $destination = 'deal_files/' . $file->file_path;
                    if (Storage::disk('public')->exists($source)) {
                        Storage::disk('public')->copy($source, $destination);
                        DealFile::create(
                            [
                                'deal_id' => $deal->id,
                                'file_name' => $file->file_name,
                                'file_path' => $file->file_path,
                            ]
                        );
                    } else {
                        dd("File not found: $source");
                    }
                }
            }
        }

        if (in_array('calls', $request->is_transfer)) {
            $calls = LeadCall::where('lead_id', '=', $lead->id)->get();
            if (!empty($calls)) {
                foreach ($calls as $call) {
                    DealCall::create(
                        [
                            'deal_id' => $deal->id,
                            'subject' => $call->subject,
                            'call_type' => $call->call_type,
                            'duration' => $call->duration,
                            'user_id' => $call->user_id,
                            'description' => $call->description,
                            'call_result' => $call->call_result,
                        ]
                    );
                }
            }
        }

        if (in_array('emails', $request->is_transfer)) {
            $emails = LeadEmail::where('lead_id', '=', $lead->id)->get();
            if (!empty($emails)) {
                foreach ($emails as $email) {
                    DealEmail::create(
                        [
                            'deal_id' => $deal->id,
                            'to' => $email->to,
                            'subject' => $email->subject,
                            'description' => $email->description,
                        ]
                    );
                }
            }
        }

        $lead->is_converted = $deal->id;
        $lead->save();

        $setting  = Utility::settings(\Auth::user()->creatorId());
        $leadUsers = Lead::where('id', '=', $lead->id)->first();
        $leadUserArr = [
            'lead_user_name' => $leadUsers->name,
            'lead_name' => $lead->name,
            'lead_email' => $lead->email,
        ];
        if (isset($setting['leadtodeal_notification']) && $setting['leadtodeal_notification'] == 1) {
            Utility::send_slack_msg('lead_to_deal_conversion', $leadUserArr);
        }
        if (isset($setting['telegram_leadtodeal_notification']) && $setting['telegram_leadtodeal_notification'] == 1) {
            Utility::send_telegram_msg('lead_to_deal_conversion', $leadUserArr);
        }

        try {
            Notification::create([
                'user_id'    => $usr->manager_id ?? $usr->creatorId(),
                'type'       => 'lead_converted',
                'created_by' => $usr->id,
                'data'       => [
                    'lead_id' => $lead->id,
                    'deal_id' => $deal->id,
                    'client_id' => $client->id,
                    'message' => "{$usr->name} converted the Lead {$lead->name}  to a Deal {$deal->name}.",
                ],
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        // 4) Webhook callback
        $module = 'Lead to Deal Conversion';
        $webhook =  Utility::webhookSetting($module);
        if ($webhook) {
            $parameter = json_encode($lead);
            $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
            if ($status == true) {
                return redirect()->back()->with('success', __('Lead successfully converted!'));
            }
            return redirect()->back()->with('error', __('Webhook call failed.'));
        }

        return redirect()->back()->with('success', __('Lead successfully converted'));
    }

    // Lead Calls
    public function callCreate($id)
    {
        if (\Auth::user()->can('create lead call')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $users = UserLead::where('lead_id', '=', $lead->id)->get();

                return view('leads.calls', compact('lead', 'users'));
            } else {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ],
                    401
                );
            }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function callStore($id, Request $request)
    {
        if (\Auth::user()->can('create lead call')) {
            $usr  = \Auth::user();
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'subject' => 'required',
                        'call_type' => 'required',
                        'user_id' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $leadCall = LeadCall::create(
                    [
                        'lead_id' => $lead->id,
                        'subject' => $request->subject,
                        'call_type' => $request->call_type,
                        'duration' => $request->duration,
                        'user_id' => $request->user_id,
                        'description' => $request->description,
                        'call_result' => $request->call_result,
                    ]
                );

                LeadActivityLog::create(
                    [
                        'user_id' => $usr->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'create lead call',
                        'remark' => json_encode(['title' => 'Create new Lead Call']),
                    ]
                );

                $leadArr = [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'updated_by' => $usr->id,
                ];

                return redirect()->back()->with('success', __('Call successfully created!'))->with('status', 'calls');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'calls');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'calls');
        }
    }

    public function callEdit($id, $call_id)
    {
        if (\Auth::user()->can('edit lead call')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $call  = LeadCall::find($call_id);
                $users = UserLead::where('lead_id', '=', $lead->id)->get();

                return view('leads.calls', compact('call', 'lead', 'users'));
            } else {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ],
                    401
                );
            }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function callUpdate($id, $call_id, Request $request)
    {
        if (\Auth::user()->can('edit lead call')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'subject' => 'required',
                        'call_type' => 'required',
                        'user_id' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $call = LeadCall::find($call_id);

                $call->update(
                    [
                        'subject' => $request->subject,
                        'call_type' => $request->call_type,
                        'duration' => $request->duration,
                        'user_id' => $request->user_id,
                        'description' => $request->description,
                        'call_result' => $request->call_result,
                    ]
                );

                return redirect()->back()->with('success', __('Call successfully updated!'))->with('status', 'calls');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'calls');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'tasks');
        }
    }

    public function callDestroy($id, $call_id)
    {
        if (\Auth::user()->can('delete lead call')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                $task = LeadCall::find($call_id);
                $task->delete();

                return redirect()->back()->with('success', __('Call successfully deleted!'))->with('status', 'calls');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'calls');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'calls');
        }
    }

    // Lead email
    public function emailCreate($id)
    {
        if (\Auth::user()->can('create lead email')) {
            $lead = Lead::find($id);
            if ($lead->created_by == \Auth::user()->creatorId()) {
                return view('leads.emails', compact('lead'));
            } else {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ],
                    401
                );
            }
        } else {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('Permission Denied.'),
                ],
                401
            );
        }
    }

    public function emailStore($id, Request $request)
    {

        if (\Auth::user()->can('create lead email')) {
            $lead = Lead::find($id);

            if ($lead->created_by == \Auth::user()->creatorId()) {
                $settings  = Utility::settings();
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'to' => 'required|email',
                        'subject' => 'required',
                        'description' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $leadEmail = LeadEmail::create(
                    [
                        'lead_id' => $lead->id,
                        'to' => $request->to,
                        'subject' => $request->subject,
                        'description' => $request->description,
                    ]
                );

                $leadEmail =
                    [
                        'lead_name' => $lead->name,
                        'to' => $request->to,
                        'subject' => $request->subject,
                        'description' => $request->description,
                    ];


                try {
                    Mail::to($request->to)->send(new SendLeadEmail($leadEmail, $settings));
                } catch (\Exception $e) {

                    $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                }
                //

                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'create lead email',
                        'remark' => json_encode(['title' => 'Create new Deal Email']),
                    ]
                );

                return redirect()->back()->with('success', __('Email successfully created!') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''))->with('status', 'emails');
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'emails');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'))->with('status', 'emails');
        }
    }

    public function export(Request $request)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $userId = $request->get('user');
        $name = 'Lead_' . date('Y-m-d i:h:s');
        $filters = [
            'lead_ids' => $request->get('leads_ids'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'stage_id' => $request->get('stage_id'),
            'default_pipeline_id' => $request->get('default_pipeline_id'),
            'userId' => $userId,
        ];

        $data = Excel::download(new LeadExport($filters), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('leads.import');
    }

    public function import(Request $request)
    {

        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $leads = (new LeadImport())->toArray(request()->file('file'))[0];

        $totalLead = count($leads) - 1;
        $errorArray    = [];
        for ($i = 1; $i <= count($leads) - 1; $i++) {
            $lead = $leads[$i];

            $leadByEmail = Lead::where('email', $lead[1])->first();
            if (!empty($leadByEmail)) {
                $leadData = $leadByEmail;
            } else {
                $leadData = new Lead();
            }

            $user = User::where('name', $lead[4])->where('created_by', \Auth::user()->creatorId())->first();
            $pipeline = PipeLine::where('name', $lead[5])->where('created_by', \Auth::user()->creatorId())->first();
            $stage = LeadStage::where('name', $lead[6])->where('created_by', \Auth::user()->creatorId())->first();

            $leadData->name      = $lead[0];
            $leadData->email             = $lead[1];
            $leadData->phone            = $lead[2];
            $leadData->subject          = $lead[3];
            $leadData->user_id     = !empty($user) ? $user->id : 3;
            $leadData->pipeline_id  = !empty($pipeline) ? $pipeline->id : 1;
            $leadData->stage_id    = !empty($stage) ? $stage->id : 1;
            $leadData->created_by       = \Auth::user()->creatorId();

            if (empty($leadData)) {
                $errorArray[] = $leadData;
            } else {
                $leadData->save();

                $userData = new UserLead();
                $userData->user_id = \Auth::user()->creatorId();
                $userData->lead_id = $leadData->id;
                $userData->save();
                LeadActivityLog::create(
                    [
                        'user_id' => \Auth::user()->id,
                        'lead_id' => $lead->id,
                        'log_type' => 'Add user',
                        'remark' =>  json_encode(
                            [
                                'user' => User::find(\Auth::user()->creatorId())->name,
                            ]
                        ),
                    ]
                );
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalLead . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    // public function fetchLeads()
    // {
    //     $pageId = '101060858820181'; // Facebook Page ID
    //     $formId = '1478326866380430';  // Facebook Lead Form ID

    //     $leads = $this->facebookLeadService->getLeads($pageId, $formId);

    //     // Log the raw response for debugging
    //     \Log::info('Facebook Leads Response:', $leads);

    //     if (isset($leads['data'])) {
    //         foreach ($leads['data'] as $leadData) {
    //             try {
    //                 $lead              = new Lead();
    //                 $lead->name        = $leadData['field_data'][0]['values'][0] ?? 'N/A';
    //                 $lead->email       = $leadData['field_data'][2]['values'][0] ?? 'N/A';
    //                 $lead->phone       = $leadData['field_data'][3]['values'][0] ?? 'N/A';
    //                 $lead->subject     = '';
    //                 $lead->user_id     = '';
    //                 $lead->pipeline_id = 2;
    //                 $lead->stage_id    = 21;
    //                 $lead->created_by  = 31;
    //                 $lead->date        = date('Y-m-d');
    //                 $lead->save();
    //             } catch (\Exception $e) {
    //                 \Log::error('Error saving lead: ' . $e->getMessage());
    //                 \Log::error('Lead data: ', $leadData);
    //             }
    //         }
    //         return response()->json(['message' => 'Leads fetched successfully']);
    //     }

    //     // Check if there is an error in the $leads array
    //     if (isset($leads['error'])) {
    //         return response()->json([
    //             'error' => 'Facebook API Error',
    //             'message' => $leads['error']
    //         ], 500);
    //     }

    //     return response()->json(['error' => 'Failed to fetch leads. Unexpected response.'], 500);
    // }
    public function updateStage(Request $request, Lead $lead)
    {
        $stage =  LeadStage::where('pipeline_id', $lead->pipeline_id)
            ->orderBy('order') // adjust to match your stage ordering logic
            ->skip(1)
            ->first();
        $lead->stage_id = $stage->id;
        $lead->save();

        return response()->json(['status' => 'success']);
    }

    public function getStages($id)
    {
        $stages = LeadStage::where('pipeline_id', $id)->get(['id', 'name']);
        return response()->json($stages);
    }

    public function assign(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:leads,id',
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {
                $userIds = array_unique($validated['user_ids']);
                $leadIds = array_unique($validated['lead_ids']);

                // Get existing assignments
                $existing = UserLead::whereIn('lead_id', $leadIds)
                    ->whereIn('user_id', $userIds)
                    ->get()
                    ->map(fn($item) => "{$item->lead_id}_{$item->user_id}")
                    ->flip();

                // Get user names for logs
                $users = User::whereIn('id', $userIds)
                    ->pluck('name', 'id');

                // Prepare bulk data
                $assignments = [];
                $logs = [];
                $timestamp = now();

                foreach ($leadIds as $leadId) {
                    foreach ($userIds as $userId) {
                        // Skip existing assignments
                        if ($existing->has("{$leadId}_{$userId}")) {
                            continue;
                        }

                        $assignments[] = [
                            'lead_id' => $leadId,
                            'user_id' => $userId,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];

                        $logs[] = [
                            'user_id' => auth()->id(),
                            'lead_id' => $leadId,
                            'log_type' => 'Add user',
                            'remark' => json_encode(['user' => $users[$userId]]),
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }
                }

                // Bulk insert
                if (!empty($assignments)) {
                    UserLead::insert($assignments);
                    LeadActivityLog::insert($logs);
                }

                return count($assignments);
            });

            // Success response
            $message = $result > 0
                ? "Successfully assigned leads. New assignments: {$result}"
                : 'All leads were already assigned to selected users.';

            return redirect()->back()->with(
                $result > 0 ? 'success' : 'info',
                $message
            );
        } catch (\Exception $e) {
            Log::error('Lead assignment failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to assign leads. Please try again.')
                ->withInput();
        }
    }


    public function bulkDelete(Request $request)
    {
        $leadIds = $request->input('lead_ids', []);

        if (!empty($leadIds)) {
            Lead::whereIn('id', $leadIds)->delete();
            return redirect()->back()->with('success', 'Selected leads deleted successfully.');
        }

        return redirect()->back()->with('error', 'No leads selected.');
    }

    public function assignStage(Request $request)
    {
        $leadIds = $request->input('lead_ids', []); // Array of selected lead IDs
        $stageId = $request->input('stage_id');

        Lead::whereIn('id', $leadIds)->update(['stage_id' => $stageId]);

        return redirect()->back()->with('success', 'Stage assigned successfully.');
    }
    public function assignSource(Request $request)
    {
        $leadIds = $request->input('lead_ids', []); // Array of selected lead IDs
        $sourceId = $request->input('source_id');

        // Make sure there are selected leads and a valid source ID
        if (count($leadIds) > 0 && $sourceId) {
            Lead::whereIn('id', $leadIds)->update(['sources' => $sourceId]);
        }

        return redirect()->back()->with('success', 'Source assigned successfully.');
    }
    public function changePipeline(Request $request)
    {
        $user                   = \Auth::user();
        $user->default_pipeline = $request->default_pipeline_id;
        $user->save();

        return redirect()->back();
    }
}
