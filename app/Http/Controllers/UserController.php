<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Employee;
use App\Models\ExperienceCertificate;
use App\Models\GenerateOfferLetter;
use App\Models\JoiningLetter;
use App\Models\LoginDetail;
use App\Models\NOC;
use App\Models\User;
use App\Models\Pipeline;
use App\Models\UserCompany;
use App\Models\warehouse;
use Auth;
use File;
use App\Models\Utility;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserToDo;
use Database\Seeders\CompanyCurrencyCountryDefaultsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Session;
use Spatie\Permission\Models\Role;
use Lab404\Impersonate\Impersonate;


class UserController extends Controller
{

    public function index()
    {
        $user = \Auth::user();
        if(\Auth::user()->can('manage user'))
        {
            if(\Auth::user()->type == 'super admin')
            {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get();
            }
            else
            {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '!=', 'client')->with(['currentPlan'])->get();
            }

            return view('user.index')->with('users', $users);
        }
        else
        {
            return redirect()->back();
        }

    }

    public function create()
    {

        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();
        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->where('name','!=','client')->get()->pluck('name', 'id');
        $users =  User::where('created_by',\Auth::user()->creatorId())->where('type','manager')->pluck('name', 'id');;
        $pipeline = Pipeline::where('created_by', \Auth::user()->ownerId())->get();
        if(\Auth::user()->can('create user'))
        {
            // Available company categories to seed on creation (multi-select)
            $availableCompanyCategories = [
                'cars' => 'Cars',
                'spare_parts' => 'Spare Parts',
                'accessories' => 'Accessories',
                'cloth' => 'Cloth',
            ];

            return view('user.create', compact('roles', 'customFields','users','pipeline','availableCompanyCategories'));
        }
        else
        {
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('create user'))
        {
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->where('created_by', '=', \Auth::user()->creatorId())->first();
            $objUser    = \Auth::user()->creatorId();

            if(\Auth::user()->type == 'super admin')
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:120',
                                       'email' => 'required|email|unique:users',
                                       'password' => 'required|min:6',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $user               = new User();
                $user['name']       = $request->name;
                $user['email']      = $request->email;
                $user['manager_id']      = $request->manager_id;
                $psw                = $request->password;
                $user['password']   = Hash::make($request->password);
                $user['type']       = 'company';
                $user['default_pipeline'] = $request->default_pipeline;
                $user['plan'] = 1;
                $user['lang']       = !empty($default_language) ? $default_language->value : 'en';
                $user['created_by'] = \Auth::user()->creatorId();
                $user['plan']       = Plan::first()->id;
                $user['email_verified_at'] = date('Y-m-d H:i:s');

                $user->save();
                $role_r = Role::findByName('company');
                $user->assignRole($role_r);
                CompanyCurrencyCountryDefaultsSeeder::seedForCompany($user->id);
                $user->userDefaultDataRegister($user->id);
                $user->userWarehouseRegister($user->id);

                //default bank account for new company
                $user->userDefaultBankAccount($user->id);

                Utility::chartOfAccountTypeData($user->id);
                // Utility::chartOfAccountData($user);
                // default chart of account for new company
                Utility::chartOfAccountData1($user->id);

                Utility::pipeline_lead_deal_Stage($user->id);
                Utility::project_task_stages($user->id);
                Utility::labels($user->id);
                Utility::sources($user->id);
                Utility::jobStage($user->id);
                GenerateOfferLetter::defaultOfferLetterRegister($user->id);
                ExperienceCertificate::defaultExpCertificatRegister($user->id);
                JoiningLetter::defaultJoiningLetterRegister($user->id);
                NOC::defaultNocCertificateRegister($user->id);
                
                // Seed selected categories and custom fields for the new company (super admin flow)
                $selectedCategories = (array) $request->input('company_categories', []);
                self::seedDefaultCategoriesAndCustomFields($user->id, $selectedCategories);
            }
            else
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:120',
                                       'email' => 'required|email|unique:users',
                                    //    'email' => 'required|email|unique:users,email,NULL,id,created_by,' . $objUser,
                                       'password' => 'required|min:6',
                                       'role' => 'required',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }


                $objUser =User::find($objUser);
                $user = User::find(\Auth::user()->created_by);
                $total_user = $objUser->countUsers();
                $plan       = Plan::find($objUser->plan);
                if($total_user < $plan->max_users || $plan->max_users == -1)
                {
                    $role_r                = Role::findById($request->role);
                    $psw                   = $request->password;
                    $request['password']   = Hash::make($request->password);
                    $request['type']       = $role_r->name;
                    $request['lang']       = !empty($default_language) ? $default_language->value : 'en';
                    $request['created_by'] = \Auth::user()->creatorId();
                    $request['email_verified_at'] = date('Y-m-d H:i:s');

                    $user = User::create($request->all());
                    $user->assignRole($role_r);
                    if($request['type'] != 'client')
                      \App\Models\Utility::employeeDetails($user->id,\Auth::user()->creatorId());
                }
                else
                {
                    return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
                }
            }
            // Send Email
            $setings = Utility::settings();
            if($setings['new_user'] == 1)
            {

                $user->password = $psw;
                $user->type = $role_r->name;
                $user->userDefaultDataRegister($user->id);

                $userArr = [
                    'email' => $user->email,
                    'password' => $user->password,
                ];
                $resp = Utility::sendEmailTemplate('new_user', [$user->id => $user->email], $userArr);


                return redirect()->route('users.index')->with('success', __('User successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            return redirect()->route('users.index')->with('success', __('User successfully created.'));

        }
        else
        {
            return redirect()->back();
        }

    }
    public function show()
    {
        return redirect()->route('user.index');
    }

    public function edit($id)
    {
        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->where('name','!=','client')->get()->pluck('name', 'id');
        $pipeline = Pipeline::where('created_by', 36)->get();
        if(\Auth::user()->can('edit user'))
        {
            $user              = User::findOrFail($id);
            $user->customField = CustomField::getData($user, 'user');
            $customFields      = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();
            $users =  User::where('created_by',\Auth::user()->creatorId())->where('type','manager')->pluck('name', 'id');
            $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            return view('user.edit', compact('user', 'roles', 'customFields','users','pipeline', 'warehouses'));
        }
        else
        {
            return redirect()->back();
        }

    }


    public function update(Request $request, $id)
    {

        if(\Auth::user()->can('edit user'))
        {
            if(\Auth::user()->type == 'super admin')
            {
                $user = User::findOrFail($id);
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:120',
                                       'email' => 'required|email|unique:users,email,' . $id,
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                //                $role = Role::findById($request->role);
                $role = Role::findByName('company');
                $input = $request->all();
                $input['type'] = $role->name;

                $user->fill($input)->save();
                CustomField::saveData($user, $request->customField);

                $roles[] = $role->id;
                $user->roles()->sync($roles);
                
                // Sync warehouses
                if ($request->has('warehouses')) {
                    $user->warehouses()->sync($request->warehouses);
                } else {
                    // If no warehouses selected, remove all assignments
                    $user->warehouses()->sync([]);
                }

                return redirect()->route('users.index')->with(
                    'success', 'User successfully updated.'
                );
            }
            else
            {
                $user = User::findOrFail($id);
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $id,
                        // 'email' => 'required|email|unique:users,email,' . $id . ',id,created_by,' . \Auth::user()->creatorId(),
                        'role' => 'required',
                    ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }


                $role          = Role::findById($request->role);
                $input         = $request->all();
                $input['type'] = $role->name;
                $user['default_pipeline'] = $request->default_pipeline;
                $user->fill($input)->save();
                Utility::employeeDetailsUpdate($user->id,\Auth::user()->creatorId());
                CustomField::saveData($user, $request->customField);

                $roles[] = $request->role;
                $user->roles()->sync($roles);
                
                // Sync warehouses
                if ($request->has('warehouses')) {
                    $user->warehouses()->sync($request->warehouses);
                } else {
                    // If no warehouses selected, remove all assignments
                    $user->warehouses()->sync([]);
                }

                return redirect()->route('users.index')->with(
                    'success', 'User successfully updated.'
                );
            }
        }
        else
        {
            return redirect()->back();
        }
    }


    public function destroy($id)
    {

        if(\Auth::user()->can('delete user'))
        {
            $user = User::find($id);
            if($user)
            {
                if(\Auth::user()->type == 'super admin')
                {
                    if($user->delete_status == 0)
                    {
                        $user->delete_status = 1;
                    }
                    else
                    {
                        $user->delete_status = 0;
                    }
                    $user->save();
                }
                if(\Auth::user()->type == 'company')
                {
                    $employee = Employee::where(['user_id' => $user->id])->delete();
                    if($employee){
                        $delete_user = User::where(['id' => $user->id])->delete();
                        if($delete_user){
                            return redirect()->route('users.index')->with('success', __('User successfully deleted .'));
                        }else{
                            return redirect()->back()->with('error', __('Something is wrong.'));
                        }
                    }else{
                        return redirect()->back()->with('error', __('Something is wrong.'));
                    }
                }

                return redirect()->route('users.index')->with('success', __('User successfully deleted .'));
            }
            else
            {
                return redirect()->back()->with('error', __('Something is wrong.'));
            }
        }
        else
        {
            return redirect()->back();
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'user');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

        return view('user.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = User::findOrFail($userDetail['id']);

        $validator = \Validator::make(
            $request->all(), [
                               'name' => 'required|max:120',
                               'email' => 'required|email|unique:users,email,' . $userDetail['id'],
                           ]
        );
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        if($request->hasFile('profile'))
        {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $settings = Utility::getStorageSetting();
            if($settings['storage_setting']=='local')
            {
                $dir        = 'uploads/avatar/';
            }
            else{
                $dir        = 'uploads/avatar';
            }

            $image_path = $dir . $userDetail['avatar'];

            if(File::exists($image_path))
            {
                File::delete($image_path);
            }


            $url = '';
            $path = Utility::upload_file($request,'profile',$fileNameToStore,$dir,[]);
            if($path['flag'] == 1)
            {
                $url = $path['url'];
            }else{
                return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
            }

//            $dir        = storage_path('uploads/avatar/');
//            $image_path = $dir . $userDetail['avatar'];
//
//            if(File::exists($image_path))
//            {
//                File::delete($image_path);
//            }
//
//            if(!file_exists($dir))
//            {
//                mkdir($dir, 0777, true);
//            }
//            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);

        }

        if(!empty($request->profile))
        {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name']  = $request['name'];
        $user['email'] = $request['email'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->route('dashboard')->with(
            'success', 'Profile successfully updated.'
        );
    }

    public function updatePassword(Request $request)
    {

        if(Auth::Check())
        {

            $validator = \Validator::make(
                $request->all(),                 [
                    'old_password' => 'required',
                    'password' => 'required|min:6',
                    'password_confirmation' => 'required|same:password',
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $objUser          = Auth::user();
            $request_data     = $request->All();
            $current_password = $objUser->password;
            if(Hash::check($request_data['old_password'], $current_password))
            {
                $user_id            = Auth::User()->id;
                $obj_user           = User::find($user_id);
                $obj_user->password = Hash::make($request_data['password']);;
                $obj_user->save();

                return redirect()->route('profile', $objUser->id)->with('success', __('Password successfully updated.'));
            }
            else
            {
                return redirect()->route('profile', $objUser->id)->with('error', __('Please enter correct current password.'));
            }
        }
        else
        {
            return redirect()->route('profile', \Auth::user()->id)->with('error', __('Something is wrong.'));
        }
    }
    // User To do module
  public function todo_store(Request $request)
  {
      $request->validate(
          ['title' => 'required|max:120']
      );

      $post            = $request->all();
      $post['user_id'] = Auth::user()->id;
      $todo            = UserToDo::create($post);


      $todo->updateUrl = route(
          'todo.update', [
                           $todo->id,
                       ]
      );
      $todo->deleteUrl = route(
          'todo.destroy', [
                            $todo->id,
                        ]
      );

      return $todo->toJson();
  }

  public function todo_update($todo_id)
  {
      $user_todo = UserToDo::find($todo_id);
      if($user_todo->is_complete == 0)
      {
          $user_todo->is_complete = 1;
      }
      else
      {
          $user_todo->is_complete = 0;
      }
      $user_todo->save();
      return $user_todo->toJson();
  }

  public function todo_destroy($id)
  {
      $todo = UserToDo::find($id);
      $todo->delete();

      return true;
  }

  // change mode 'dark or light'
  public function changeMode()
  {
      $usr = \Auth::user();
      if($usr->mode == 'light')
      {
          $usr->mode      = 'dark';
          $usr->dark_mode = 1;
      }
      else
      {
          $usr->mode      = 'light';
          $usr->dark_mode = 0;
      }
      $usr->save();

      return redirect()->back();
  }

  public function upgradePlan($user_id)
    {
        $user = User::find($user_id);
        $plans = Plan::get();
        $admin_payment_setting = Utility::getAdminPaymentSetting();

        return view('user.plan', compact('user', 'plans' , 'admin_payment_setting'));
    }
    public function activePlan($user_id, $plan_id)
    {

        $user       = User::find($user_id);
        $assignPlan = $user->assignPlan($plan_id);
        $plan       = Plan::find($plan_id);
        if($assignPlan['is_success'] == true && !empty($plan))
        {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            Order::create(
                [
                    'order_id' => $orderID,
                    'name' => null,
                    'card_number' => null,
                    'card_exp_month' => null,
                    'card_exp_year' => null,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'price_currency' => isset(\Auth::user()->planPrice()['currency']) ? \Auth::user()->planPrice()['currency'] : '',
                    'txn_id' => '',
                    'payment_status' => 'success',
                    'receipt' => null,
                    'user_id' => $user->id,
                ]
            );

            return redirect()->back()->with('success', 'Plan successfully upgraded.');
        }
        else
        {
            return redirect()->back()->with('error', 'Plan fail to upgrade.');
        }

    }

    public function userPassword($id)
    {
        $eId        = \Crypt::decrypt($id);
        $user = User::find($eId);

        return view('user.reset', compact('user'));

    }

    public function userPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(), [
                               'password' => 'required|confirmed|same:password_confirmation',
                           ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $user                 = User::where('id', $id)->first();
        $user->forceFill([
                             'password' => Hash::make($request->password),
                         ])->save();

        return redirect()->route('users.index')->with(
            'success', 'User Password successfully updated.'
        );


    }


    //start for user login details
    public function userLog(Request $request)
    {
        $filteruser = User::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $filteruser->prepend('Select User', '');

        $query = DB::table('login_details')
            ->join('users', 'login_details.user_id', '=', 'users.id')
            ->select(DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
            ->where(['login_details.created_by' => \Auth::user()->id]);

        if(!empty($request->month))
        {
            $query->whereMonth('date', date('m',strtotime($request->month)));
            $query->whereYear('date', date('Y',strtotime($request->month)));
        }else{
            $query->whereMonth('date', date('m'));
            $query->whereYear('date', date('Y'));
        }

        if(!empty($request->users))
        {
            $query->where('user_id', '=', $request->users);
        }
        $userdetails = $query->get();
        $last_login_details = LoginDetail::where('created_by', \Auth::user()->creatorId())->get();

        return view('user.userlog', compact( 'userdetails','last_login_details','filteruser'));
    }

    public function userLogView($id)
    {
        $users = LoginDetail::find($id);

        return view('user.userlogview', compact('users'));
    }

    public function userLogDestroy($id)
    {
        $users = LoginDetail::where('user_id', $id)->delete();
        return redirect()->back()->with('success', 'User successfully deleted.');
    }

    public function LoginWithCompany(Request $request, User $user,  $id)
    {
        $user = User::find($id);
        if ($user && auth()->check()) {
            Impersonate::take($request->user(), $user);
            return redirect('/account-dashboard');
        }
    }

    public function ExitCompany(Request $request)
    {
        \Auth::user()->leaveImpersonation($request->user());
        return redirect('/dashboard');
    }

    public function companyInfo(Request $request , $id)
    {
        $userData = User::where('created_by',$id)->where('type','!=','client')->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

        return view('user.company_info',compact('userData'  ,'id'));
    }


    public function userUnable(Request $request)
    {
        User::where('id', $request->id)->update(['is_disable' => $request->is_disable]);
        $userData = User::where('created_by',$request->company_id)->where('type','!=','client')->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

        if($request->is_disable == 1){

            return response()->json(['success' => __('User successfully unable.'),'userData' => $userData]);

        }else
        {
            return response()->json(['success' => __('User successfully disable.'),'userData' => $userData]);
        }
    }

    /**
     * Seed default categories and custom fields for new company
     */
    private static function seedDefaultCategoriesAndCustomFields($companyId, array $selectedCategories = [])
    {
        // Import required models
        $productServiceCategory = \App\Models\ProductServiceCategory::class;
        $customField = \App\Models\CustomField::class;
        $brand = \App\Models\Brand::class;
        $subBrand = \App\Models\VehicleModel::class;
        $chartOfAccount = \App\Models\ChartOfAccount::class;

        // Get account IDs dynamically from chart of accounts
        $saleAccount = $chartOfAccount::where('created_by', $companyId)
            ->where('name', 'like', '%Sales Income%')
            ->first();
        
        $expenseAccount = $chartOfAccount::where('created_by', $companyId)
            ->where('name', 'like', '%Cost of Sales%')
            ->first();
        
        $purchaseAccount = $chartOfAccount::where('created_by', $companyId)
            ->where('name', 'like', '%Inventory%')
            ->first();

        // Normalize selected categories; if none passed, do nothing
        $selectedCategories = array_unique(array_filter(array_map('strtolower', $selectedCategories)));

        $createdCategoryIds = [];

        // Helper to create a product service category
        $createCategory = function(string $name, string $type) use ($productServiceCategory, $companyId, $saleAccount, $expenseAccount, $purchaseAccount, &$createdCategoryIds) {
            $cat = $productServiceCategory::create([
                'name' => $name,
                'type' => $type,
                'created_by' => $companyId,
                'sale_account_id' => $saleAccount ? $saleAccount->id : null,
                'expense_account_id' => $expenseAccount ? $expenseAccount->id : null,
                'purchase_account_id' => $purchaseAccount ? $purchaseAccount->id : null,
            ]);
            $createdCategoryIds[$name] = $cat->id;
            return $cat;
        };

        // Process each selected category independently to ensure all get created
        // Cars
        if (in_array('cars', $selectedCategories, true)) {
            try {
                $carsCategory = $createCategory('Cars', 'product');
                
                // Cars custom fields (sub-product)
                $carFields = [
                    ['name' => 'interior_color', 'type' => 'text', 'options' => null],
                    ['name' => 'exterior_color', 'type' => 'text', 'options' => null],
                    ['name' => 'engine_model', 'type' => 'text', 'options' => null],
                ];
                foreach ($carFields as $f) {
                    try {
                        $customField::create([
                            'name' => $f['name'],
                            'type' => $f['type'],
                            'module' => 'sub-product',
                            'created_by' => $companyId,
                            'category_id' => $carsCategory->id,
                            'options' => $f['options'],
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating custom field ' . $f['name'] . ' for Cars category: ' . $e->getMessage());
                    }
                }
                \Log::info('Successfully created Cars category and custom fields for company: ' . $companyId);
            } catch (\Exception $e) {
                \Log::error('Error creating Cars category: ' . $e->getMessage());
            }
        }

        // Spare Parts
        if (in_array('spare_parts', $selectedCategories, true)) {
            try {
                $sparePartsCategory = $createCategory('Spare Parts', 'Qty product');
                $spareFields = [
                    ['name' => 'unit_weight', 'type' => 'number'],
                    ['name' => 'total_weight', 'type' => 'number'],
                    ['name' => 'hs_code', 'type' => 'text'],
                    ['name' => 'container_no', 'type' => 'text'],
                    ['name' => 'dec_no', 'type' => 'text'],
                    ['name' => 'dec_date', 'type' => 'date'],
                    ['name' => 'origin', 'type' => 'text'],
                    ['name' => 'bin_location', 'type' => 'text'],
                    ['name' => 'qty_bin', 'type' => 'number'],
                ];
                foreach ($spareFields as $f) {
                    try {
                        $customField::create([
                            'name' => $f['name'],
                            'type' => $f['type'],
                            'module' => 'sub-product',
                            'created_by' => $companyId,
                            'category_id' => $sparePartsCategory->id,
                            'options' => null,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating custom field ' . $f['name'] . ' for Spare Parts category: ' . $e->getMessage());
                    }
                }
                \Log::info('Successfully created Spare Parts category and custom fields for company: ' . $companyId);
            } catch (\Exception $e) {
                \Log::error('Error creating Spare Parts category: ' . $e->getMessage());
            }
        }

        // Accessories
        if (in_array('accessories', $selectedCategories, true)) {
            try {
                $accessoriesCategory = $createCategory('Accessories', 'Qty product');
                $accFields = [
                    ['name' => 'color', 'type' => 'text'],
                    ['name' => 'width', 'type' => 'number'],
                ];
                foreach ($accFields as $f) {
                    try {
                        $customField::create([
                            'name' => $f['name'],
                            'type' => $f['type'],
                            'module' => 'sub-product',
                            'created_by' => $companyId,
                            'category_id' => $accessoriesCategory->id,
                            'options' => null,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating custom field ' . $f['name'] . ' for Accessories category: ' . $e->getMessage());
                    }
                }
                \Log::info('Successfully created Accessories category and custom fields for company: ' . $companyId);
            } catch (\Exception $e) {
                \Log::error('Error creating Accessories category: ' . $e->getMessage());
            }
        }

        // Cloth
        if (in_array('cloth', $selectedCategories, true)) {
            try {
                $clothingCategory = $createCategory('Clothing', 'Qty product');
                $clothFields = [
                    ['name' => 'color', 'type' => 'dropdown', 'options' => json_encode(["White","Black","Blue","Green","Red","Beige"])],
                    ['name' => 'size', 'type' => 'dropdown', 'options' => json_encode(["XS","S","M","L","XL","XXL"])],
                    ['name' => 'style', 'type' => 'dropdown', 'options' => json_encode(["Casual","Formal","Sport","Vintage","Modern"])],
                    ['name' => 'gender', 'type' => 'dropdown', 'options' => json_encode(["Men","Women"])],
                    ['name' => 'model', 'type' => 'text', 'options' => null],
                ];
                foreach ($clothFields as $f) {
                    try {
                        $customField::create([
                            'name' => $f['name'],
                            'type' => $f['type'],
                            'module' => 'sub-product',
                            'created_by' => $companyId,
                            'category_id' => $clothingCategory->id,
                            'options' => $f['options'] ?? null,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Error creating custom field ' . $f['name'] . ' for Clothing category: ' . $e->getMessage());
                    }
                }
                \Log::info('Successfully created Clothing category and custom fields for company: ' . $companyId);
            } catch (\Exception $e) {
                \Log::error('Error creating Clothing category: ' . $e->getMessage());
            }
        }
        
        // Log summary
        if (!empty($selectedCategories)) {
            \Log::info('Completed seeding categories for company ' . $companyId . '. Selected categories: ' . implode(', ', $selectedCategories));
        }
    }


}
