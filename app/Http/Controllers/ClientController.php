<?php

namespace App\Http\Controllers;

use App\Models\ClientDeal;
use App\Models\ClientPermission;
use App\Models\Contract;
use App\Models\CustomField;
use App\Models\Estimation;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\Utility;
use http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(
    //         [
    //             'auth',
    //             'XSS',
    //         ]
    //     );
    // }

    public function index()
    {
        if (\Auth::user()->can('manage client')) {
            $user = \Auth::user();

            if ($user->type == 'company') {
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

            return view('clients.index', compact('clients'));
        } else {

            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create(Request $request)
    {

        if (\Auth::user()->can('create client')) {
            if ($request->ajax) {
                return view('clients.createAjax');
            } else {
                $customFields = CustomField::where('module', '=', 'client')->get();

                return view('clients.create', compact('customFields'));
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create client')) {
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->where('created_by', '=', \Auth::user()->creatorId())->first();

            $user      = \Auth::user();
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users',
                    'password' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                if ($request->ajax) {
                    return response()->json(['error' => $messages->first()], 401);
                } else {
                    return redirect()->back()->with('error', $messages->first());
                }
            }
            $objCustomer    = \Auth::user();
            $creator        = User::find($objCustomer->creatorId());
            $total_client = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', 'client')->count();
            //             dd($total_client);
            $plan           = Plan::find($creator->plan);
            if ($total_client < $plan->max_clients || $plan->max_clients == -1) {
                $role = Role::findByName('client');
                $client = User::create(
                    [
                        'name' => $request->name,
                        'email' => $request->email,
                        'job_title' => $request->job_title,
                        'password' => Hash::make($request->password),
                        'type' => 'client',
                        'lang' => !empty($default_language) ? $default_language->value : 'en',
                        'created_by' => $user->creatorId(),
                        'email_verified_at' => date('Y-m-d H:i:s'),
                    ]
                );

                //Send Email
                $setings = Utility::settings();

                if ($setings['new_client'] == 1) {
                    $role_r = Role::findByName('client');
                    $client->assignRole($role_r);
                    $client->password = $request->password;

                    $clientArr = [
                        'client_name' => $client->name,
                        'client_email' => $client->email,
                        'client_password' =>  $client->password,
                    ];
                    $resp = Utility::sendEmailTemplate('new_client', [$client->email], $clientArr);
                    return redirect()->route('clients.index')->with('success', __('Client successfully added.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                }
                return redirect()->route('clients.index')->with('success', __('Client successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            }
        } else {
            if ($request->ajax) {
                return response()->json(['error' => __('Permission Denied.')], 401);
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }
    }

    public function show(User $client)
    {
        $usr = Auth::user();
        if (!empty($client) && $usr->id == $client->creatorId() && $client->id != $usr->id && $client->type == 'client') {
            // For Estimations
            $estimations = $client->clientEstimations()->orderByDesc('id')->get();
            $curr_month  = $client->clientEstimations()->whereMonth('issue_date', '=', date('m'))->get();
            $curr_week   = $client->clientEstimations()->whereBetween(
                'issue_date',
                [
                    \Carbon\Carbon::now()->startOfWeek(),
                    \Carbon\Carbon::now()->endOfWeek(),
                ]
            )->get();
            $last_30days = $client->clientEstimations()->whereDate('issue_date', '>', \Carbon\Carbon::now()->subDays(30))->get();
            // Estimation Summary
            $cnt_estimation                = [];
            $cnt_estimation['total']       = Estimation::getEstimationSummary($estimations);
            $cnt_estimation['this_month']  = Estimation::getEstimationSummary($curr_month);
            $cnt_estimation['this_week']   = Estimation::getEstimationSummary($curr_week);
            $cnt_estimation['last_30days'] = Estimation::getEstimationSummary($last_30days);

            $cnt_estimation['cnt_total']       = $estimations->count();
            $cnt_estimation['cnt_this_month']  = $curr_month->count();
            $cnt_estimation['cnt_this_week']   = $curr_week->count();
            $cnt_estimation['cnt_last_30days'] = $last_30days->count();

            // For Contracts
            $contracts   = $client->clientContracts()->orderByDesc('id')->get();
            $curr_month  = $client->clientContracts()->whereMonth('start_date', '=', date('m'))->get();
            $curr_week   = $client->clientContracts()->whereBetween(
                'start_date',
                [
                    \Carbon\Carbon::now()->startOfWeek(),
                    \Carbon\Carbon::now()->endOfWeek(),
                ]
            )->get();
            $last_30days = $client->clientContracts()->whereDate('start_date', '>', \Carbon\Carbon::now()->subDays(30))->get();

            // Contracts Summary
            $cnt_contract                = [];
            $cnt_contract['total']       = Contract::getContractSummary($contracts);
            $cnt_contract['this_month']  = Contract::getContractSummary($curr_month);
            $cnt_contract['this_week']   = Contract::getContractSummary($curr_week);
            $cnt_contract['last_30days'] = Contract::getContractSummary($last_30days);

            $cnt_contract['cnt_total']       = $contracts->count();
            $cnt_contract['cnt_this_month']  = $curr_month->count();
            $cnt_contract['cnt_this_week']   = $curr_week->count();
            $cnt_contract['cnt_last_30days'] = $last_30days->count();

            return view('clients.show', compact('client', 'estimations', 'cnt_estimation', 'contracts', 'cnt_contract'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function edit(User $client)
    {
        if (\Auth::user()->can('edit client')) {
            $user = \Auth::user();
            if ($client->created_by == $user->creatorId()) {
                $client->customField = CustomField::getData($client, 'client');
                $customFields        = CustomField::where('module', '=', 'client')->get();

                return view('clients.edit', compact('client', 'customFields'));
            } else {
                return response()->json(['error' => __('Invalid Client.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }

    public function update(User $client, Request $request)
    {
        if (\Auth::user()->can('edit client')) {
            $user = \Auth::user();
            if ($client->created_by == $user->creatorId()) {
                $validation = [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email,' . $client->id,
                ];

                $post         = [];
                $post['name'] = $request->name;
                if (!empty($request->password)) {
                    $validation['password'] = 'required';
                    $post['password']       = Hash::make($request->password);
                }

                $validator = \Validator::make($request->all(), $validation);
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $post['email'] = $request->email;

                $client->update($post);

                CustomField::saveData($client, $request->customField);

                return redirect()->back()->with('success', __('Client Updated Successfully!'));
            } else {
                return redirect()->back()->with('error', __('Invalid Client.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy(User $client)
    {
        $user = \Auth::user();
        if ($client->created_by == $user->creatorId()) {
            $estimation = Estimation::where('client_id', '=', $client->id)->first();
            if (empty($estimation)) {
                /*  ClientDeal::where('client_id', '=', $client->id)->delete();
                    ClientPermission::where('client_id', '=', $client->id)->delete();*/
                $client->delete();
                return redirect()->back()->with('success', __('Client Deleted Successfully!'));
            } else {
                return redirect()->back()->with('error', __('This client has assigned some estimation.'));
            }
        } else {
            return redirect()->back()->with('error', __('Invalid Client.'));
        }
    }

    public function clientPassword($id)
    {
        $eId        = \Crypt::decrypt($id);
        $user = User::find($eId);
        $client = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'client')->first();


        return view('clients.reset', compact('user', 'client'));
    }

    public function clientPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $user                 = User::where('id', $id)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()->route('clients.index')->with(
            'success',
            'Client Password successfully updated.'
        );
    }

    function client_deals($id)
    {
        // Find client by ID
        $client = User::with('clientDeals')->findOrFail($id);
        $usr = \Auth::user();
        // Optionally, send client deals directly if you want
        $deals = $client->clientDeals()->paginate(100);
        // dd($deals);
        $id_deals = $deals->pluck('id');
        $pipelines = Pipeline::where('created_by', '=', $usr->ownerId())->get()->pluck('name', 'id');
        if ($usr->default_pipeline) {
            $pipeline = Pipeline::where('created_by', '=', $usr->ownerId())->where('id', '=', $usr->default_pipeline)->first();
            if (!$pipeline) {
                $pipeline = Pipeline::where('created_by', '=', $usr->ownerId())->first();
            }
        } else {
            $pipeline = Pipeline::where('created_by', '=', $usr->ownerId())->first();
        }
        $curr_month  = Deal::whereIn('id', $id_deals)->where('pipeline_id', '=', $pipeline->id)->whereMonth('created_at', '=', date('m'))->get();
        $curr_week   = Deal::whereIn('id', $id_deals)->where('pipeline_id', '=', $pipeline->id)->whereBetween(
            'created_at',
            [
                \Carbon\Carbon::now()->startOfWeek(),
                \Carbon\Carbon::now()->endOfWeek(),
            ]
        )->get();
        $last_30days = Deal::whereIn('id', $id_deals)->where('pipeline_id', '=', $pipeline->id)->whereDate('created_at', '>', \Carbon\Carbon::now()->subDays(30))->get();
        // Deal Summary
        $cnt_deal                = [];
        $cnt_deal['total']       = Deal::getDealSummary($deals);
        $cnt_deal['this_month']  = Deal::getDealSummary($curr_month);
        $cnt_deal['this_week']   = Deal::getDealSummary($curr_week);
        $cnt_deal['last_30days'] = Deal::getDealSummary($last_30days);
        $users =  User::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');;
        // Return to a view with the data
        return view('deals.list', compact('pipelines', 'pipeline', 'deals', 'cnt_deal', 'users'));
    }
}
