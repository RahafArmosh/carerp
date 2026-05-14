<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductServiceCategoryController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage constant category')) {
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('productServiceCategory.index', compact('categories'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if (\Auth::user()->can('show constant category')) {
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
            
            return view('productServiceCategory.show', compact('category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create constant category')) {
            $types = ProductServiceCategory::$catTypes;
            // $type = ['' => 'Select Category Type'];

            // $types = array_merge($type, $types);

            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chart_accounts->prepend('Select Account', '');

            $incomeChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            // ->where('chart_of_account_types.name' ,'income')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            // $incomeChartAccounts->prepend('Select Account', '');


            $expenseChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            // $expenseChartAccounts->prepend('Select Account', '');


            return view('productServiceCategory.create', compact('types', 'chart_accounts','incomeChartAccounts','expenseChartAccounts','chart_accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create constant category')) {

            $creatorId = \Auth::user()->creatorId();
            $validator = \Validator::make(
                $request->all(), [
                    'name' => [
                        'required',
                        'max:200',
                        Rule::unique('product_service_categories', 'name')
                            ->where('created_by', $creatorId)
                            ->whereNull('deleted_at'),
                    ],
                    'type' => 'required',
                ],
                [
                    'name.unique' => __('A category with this name already exists for your company.'),
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $category = new ProductServiceCategory();
            $category->name = $request->name;
            $category->type = $request->type;
            $category->purchase_account_id = !empty($request->purchase_account_id) ? $request->purchase_account_id : 0;
            $category->sale_account_id       = !empty($request->sale_account_id) ? $request->sale_account_id:0;
            $category->expense_account_id    = !empty($request->expense_account_id) ? $request->expense_account_id : 0;
            $category->is_manufacturer = $request->has('is_manufacturer') ? true : false;
            $category->cost_calculation_method = $request->cost_calculation_method ?? 'avg';
            $category->created_by = $creatorId;
            $category->save();

            return redirect()->route('product-category.index')->with('success', __('Category successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {

        if (\Auth::user()->can('edit constant category')) {
            $types = ProductServiceCategory::$catTypes;
            $category = ProductServiceCategory::find($id);
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chart_accounts->prepend('Select Account', '');

            $incomeChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'income')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            $incomeChartAccounts->prepend('Select Account', '');


            $expenseChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            $expenseChartAccounts->prepend('Select Account', '');


            return view('productServiceCategory.edit', compact('category', 'types', 'chart_accounts','incomeChartAccounts','expenseChartAccounts','chart_accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit constant category')) {
            $category = ProductServiceCategory::find($id);
            if ($category->created_by == \Auth::user()->creatorId()) {
                $creatorId = \Auth::user()->creatorId();
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => [
                            'required',
                            'max:200',
                            Rule::unique('product_service_categories', 'name')
                                ->where('created_by', $creatorId)
                                ->whereNull('deleted_at')
                                ->ignore($category->id),
                        ],
                        'type' => 'required',
                    ],
                    [
                        'name.unique' => __('A category with this name already exists for your company.'),
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $category->name = $request->name;
                $category->type = $request->type;
                $category->sale_account_id       = !empty($request->sale_account_id) ? $request->sale_account_id:0;
                $category->expense_account_id    = !empty($request->expense_account_id) ? $request->expense_account_id : 0;
                $category->purchase_account_id = !empty($request->purchase_account_id) ? $request->purchase_account_id : 0;
                $category->is_manufacturer = $request->has('is_manufacturer') ? true : false;
                $category->cost_calculation_method = $request->cost_calculation_method ?? 'avg';
                $category->save();

                return redirect()->route('product-category.index')->with('success', __('Category successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete constant category')) {
            $category = ProductServiceCategory::find($id);
            if ($category->created_by == \Auth::user()->creatorId()) {

                if ($category->type == 0) {
                    $categories = ProductService::where('category_id', $category->id)->first();
                } elseif ($category->type == 1) {
                    $categories = Invoice::where('category_id', $category->id)->first();
                } else {
                    $categories = Bill::where('category_id', $category->id)->first();
                }

                if (!empty($categories)) {
                    return redirect()->back()->with('error', __('this category is already assign so please move or remove this category related data.'));
                }

                $category->delete();

                return redirect()->route('product-category.index')->with('success', __('Category successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getProductCategories()
    {
        $cat = ProductServiceCategory::getallCategories();
        $all_products = ProductService::getallproducts()->count();

        $html = '<div class="mb-3 mr-2 zoom-in ">
                  <div class="card rounded-10 card-stats mb-0 cat-active overflow-hidden" data-id="0">
                     <div class="category-select" data-cat-id="0">
                        <button type="button" class="btn tab-btns btn-primary">' . __("All Categories") . '</button>
                     </div>
                  </div>
               </div>';
        foreach ($cat as $key => $c) {
            $dcls = 'category-select';
            $html .= ' <div class="mb-3 mr-2 zoom-in cat-list-btn">
                          <div class="card rounded-10 card-stats mb-0 overflow-hidden " data-id="' . $c->id . '">
                             <div class="' . $dcls . '" data-cat-id="' . $c->id . '">
                                <button type="button" class="btn tab-btns btn-primary">' . $c->name . '</button>
                             </div>
                          </div>
                       </div>';
        }
        return Response($html);
    }

    public function getAccount(Request $request)
    {

        $chart_accounts = [];
        if ($request->type == 'income') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Income')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } elseif ($request->type == 'expense') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Expenses')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } elseif ($request->type == 'asset') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Assets')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } elseif ($request->type == 'liability') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Liabilities')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } elseif ($request->type == 'equity') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Equity')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } elseif ($request->type == 'costs of good sold') {
            $chart_accounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
            ->leftjoin('chart_of_account_types', 'chart_of_account_types.id','chart_of_accounts.type')
            ->where('chart_of_account_types.name' ,'Costs of Goods Sold')
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
        } else {
            $chart_accounts = 0;
        }

        return response()->json($chart_accounts);

    }

    public function export()
    {
        try {
            $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->get();

            $filename = 'categories_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($categories) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers
                fputcsv($file, [
                    'ID',
                    'Category Name',
                    'Type',
                    'Created At',
                    'Updated At'
                ]);

                // Add data rows
                foreach ($categories as $category) {
                    fputcsv($file, [
                        $category->id,
                        $category->name,
                        $category->type,
                        $category->created_at->format('Y-m-d H:i:s'),
                        $category->updated_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }

}
