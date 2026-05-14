<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Document;
use App\Models\macRestrict;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AssignProject;
use App\Models\Project;
use App\Models\Utility;
use App\Models\Tag;
use App\Models\ProjectTask;
use App\Models\TimeTracker;
use App\Models\TrackPhoto;
use URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\LogoutLog;
use App\Models\warehouse;

class ApiController extends Controller
{
    //
    use ApiResponser;

    public function login(Request $request)
    {

        $attr = $request->validate([
            'email' => 'required|string|email|',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        $settings              = Utility::settings(auth()->user()->id);

        $settings = [
            'isIpEnabled' => true,
            'timeZone' => 'Asia/Dubai',
            'currencyCode' => 'USD',
            'dutySchedule' => [
                'startTime' => [
                    'hour' => 9,
                    'min' => 0,
                    'sec' => 0,
                ],
                'endTime' => [
                    'hour' => 18,
                    'min' => 0,
                    'sec' => 0,
                ],
            ],
        ];
        $user = User::where('id', auth()->user()->id)->first();
        if ($user->otp !== $request->otp) {
            return $this->error('OTP not match', 401);
        }
        $user->fcm_token = $request->fcm_token;
        $user->otp = Str::random(4);
        $user->save();
        // Revoke all previous tokens
        // $user->tokens()->delete();
        // $macAddress = macRestrict::where('user_id', $user->id)->first();
        // if($macAddress == null){
        $mac_restricts = macRestrict::create([
            'mac' => $request->macAddress,
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);
        // }

        return $this->success([
            'token' => auth()->user()->createToken('API Token')->plainTextToken,
            'id' => auth()->user()->id,
            'name' => Employee::where('user_id', auth()->user()->id)->first()->name,
            'email' => Employee::where('user_id', auth()->user()->id)->first()->email,
            'phone' => Employee::where('user_id', auth()->user()->id)->first()->phone,
            'avatar' => EmployeeDocument::where('employee_id', Employee::where('user_id', auth()->user()->id)->first()->id)->where("document_id", Document::where('name', 'avatar')->first()->id)->first() != null ? URL::to('/') . '/documents/employee/' . EmployeeDocument::where('employee_id', Employee::where('user_id', auth()->user()->id)->first()->id)->where("document_id", Document::where('name', 'avatar')->first()->id)->first()->document_value : '',
            'address' => Employee::where('user_id', auth()->user()->id)->first()->address,
            'isAdmin' => User::where('id', auth()->user()->id)->first()->type == "super admin" ? true : false,
            'isHr' => User::where('id', auth()->user()->id)->first()->type == "hr" ? true : false,
            'required_latitude' => Employee::where('user_id', auth()->user()->id)->first()->required_latitude,
            'required_longitude' => Employee::where('user_id', auth()->user()->id)->first()->required_longitude,
            'settings' => $settings,
        ], 'Login successfully.');
    }
    public function logout()
    {
        $user = Auth::user();

        // Log the logout
        LogoutLog::create([
            'user_id' => $user->id,
            'logged_out_at' => now(),
            'user_agent' => macRestrict::where('user_id', $user->id)->latest('created_at')->first()->mac,
        ]);

        // Proceed with the default logout
        // Auth::logout();
        auth()->user()->tokens()->delete();
        return $this->success([], 'Tokens Revoked');
    }


    public function getProjects(Request $request)
    {

        $user = auth()->user();

        if ($user->type != 'company') {
            $assign_pro_ids = ProjectUser::where('user_id', $user->id)->pluck('project_id');

            //            $project_s      = Project::with('tasks')->select(
            //                [
            //                    'project_name',
            //                    'id',
            //                    'client_id',
            //                ]
            //            )->whereIn('id', $assign_pro_ids)->get()->toArray();

            $project_s      = Project::with('tasks')->whereIn('id', $assign_pro_ids)->get()->toArray();
        } else {

            //            $project_s = Project::with('tasks')->select(
            //                [
            //                    'project_name',
            //                    'id',
            //                    'client_id',
            //                ]
            //            )->where('created_by', $user->id)->get()->toArray();

            $project_s = Project::with('tasks')->where('created_by', $user->id)->get()->toArray();
        }

        return $this->success([
            'projects' => $project_s,
        ], 'Get Project List successfully.');
    }


    public function addTracker(Request $request)
    {

        $user = auth()->user();
        if ($request->has('action') && $request->action == 'start') {

            $validatorArray = [
                'task_id' => 'required|integer',
            ];
            $validator      = \Validator::make(
                $request->all(),
                $validatorArray
            );
            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 401);
            }
            $task = ProjectTask::find($request->task_id);

            if (empty($task)) {
                return $this->error('Invalid task', 401);
            }

            $project_id = isset($task->project_id) ? $task->project_id : '';
            TimeTracker::where('created_by', '=', $user->id)->where('is_active', '=', 1)->update(['end_time' => date("Y-m-d H:i:s")]);

            $track['name']        = $request->has('workin_on') ? $request->input('workin_on') : '';
            $track['project_id']  = $project_id;
            $track['is_billable'] =  $request->has('is_billable') ? $request->is_billable : 0;
            $track['tag_id']      = $request->has('workin_on') ? $request->input('workin_on') : '';
            $track['start_time']  = $request->has('time') ?  date("Y-m-d H:i:s", strtotime($request->input('time'))) : date("Y-m-d H:i:s");
            $track['task_id']     = $request->has('task_id') ? $request->input('task_id') : '';
            $track['created_by']  = $user->id;
            $track                = TimeTracker::create($track);
            $track->action        = 'start';

            return $this->success($track, 'Track successfully create.');
        } else {
            $validatorArray = [
                'task_id' => 'required|integer',
                'traker_id' => 'required|integer',
            ];
            $validator      = Validator::make(
                $request->all(),
                $validatorArray
            );
            if ($validator->fails()) {
                return Utility::error_res($validator->errors()->first());
            }
            $tracker = TimeTracker::where('id', $request->traker_id)->first();
            // dd($tracker);
            if ($tracker) {
                $tracker->end_time   = $request->has('time') ?  date("Y-m-d H:i:s", strtotime($request->input('time'))) : date("Y-m-d H:i:s");
                $tracker->is_active  = 0;
                $tracker->total_time = Utility::diffance_to_time($tracker->start_time, $tracker->end_time);
                $tracker->save();
                return $this->success($tracker, 'Stop time successfully.');
            }
        }
    }
    public function uploadImage(Request $request)
    {
        $user = auth()->user();
        $image_base64 = base64_decode($request->img);
        $file = $request->imgName;
        if ($request->has('tracker_id') && !empty($request->tracker_id)) {
            $app_path = storage_path('uploads/traker_images/') . $request->tracker_id . '/';
            if (!file_exists($app_path)) {
                mkdir($app_path, 0777, true);
            }
        } else {
            $app_path = storage_path('uploads/traker_images/');
            if (is_dir($app_path)) {
                mkdir($app_path, 0777, true);
            }
        }
        $file_name =  $app_path . $file;
        file_put_contents($file_name, $image_base64);
        $new = new TrackPhoto();
        $new->track_id = $request->tracker_id;
        $new->user_id  = $user->id;
        $new->img_path  = 'uploads/traker_images/' . $request->tracker_id . '/' . $file;
        $new->time  = $request->time;
        $new->status  = 1;
        $new->save();
        return $this->success([], 'Uploaded successfully.');
    }

    /**
     * POS API Login - Generate token with company and warehouse information
     * This endpoint is specifically for POS API access
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function posApiLogin(Request $request)
    {
        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        $user = User::where('id', auth()->user()->id)->first();
        
        // Get company ID
        $companyId = $user->creatorId();
        
        // Get user's assigned warehouses (without IDs)
        $assignedWarehouses = $user->warehouses()
            ->select('warehouses.name')
            ->get()
            ->map(function($warehouse) {
                return [
                    'name' => $warehouse->name
                ];
            });

        // Get all company warehouses if user is company/admin (without IDs)
        $companyWarehouses = collect([]); // Initialize as Collection
        if ($user->type == 'company' || $user->type == 'super admin') {
            $companyWarehouses = warehouse::where('created_by', $companyId)
                ->select('name')
                ->get()
                ->map(function($warehouse) {
                    return [
                        'name' => $warehouse->name
                    ];
                });
        }

        // Create token with name indicating it's for POS API
        $token = $user->createToken('POS API Token')->plainTextToken;

        // Determine which warehouses to return
        $warehouses = $assignedWarehouses->isNotEmpty() 
            ? $assignedWarehouses 
            : $companyWarehouses;

        return $this->success([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type
            ],
            'company' => [
                'name' => $user->type == 'company' || $user->type == 'super admin' 
                    ? $user->name 
                    : User::find($companyId)->name ?? null
            ],
            'warehouses' => $warehouses->values()->all() // Convert to array for JSON response
        ], 'POS API login successful. Token generated.');
    }
}
