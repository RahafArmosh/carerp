<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\VehicleModel;
use App\Models\CustomField;
use App\Models\Brand;
use App\Models\ProductService;
use App\Models\Invoice;
use App\Models\Bill;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubBrandImport;

class SubBrandController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage constant sub-brand')) {
            $search = trim((string) request('q', ''));

            $brands = VehicleModel::query()
                ->select(['id', 'name', 'brand_id', 'created_by'])
                ->where('created_by', \Auth::user()->creatorId())
                ->with([
                    'brand:id,name',
                    'brand.categories:id,name',
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->latest('id')
                ->paginate(25)
                ->withQueryString();

            return view('sub-brand.index', compact('brands'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create constant sub-brand')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-brand')->get();
            $brands = Brand::where('created_by', \Auth::user()->creatorId())
            ->with('categories')
            ->get()
            ->mapWithKeys(function ($brand) {
                $categoryNames = $brand->categories->pluck('name')->implode(', '); // Get category names and join them with a comma
                return [$brand->id => $brand->name . ' (' . $categoryNames . ')'];
            });

        return view('sub-brand.create', compact('brands', 'customFields'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create constant sub-brand')) {

            $creatorId = \Auth::user()->creatorId();
            $validator = \Validator::make(
                $request->all(), [
                    'name' => [
                        'required',
                        'max:200',
                        Rule::unique('sub_brands', 'name')
                            ->where('created_by', $creatorId)
                            ->where('brand_id', (int) $request->brand_id)
                            ->whereNull('deleted_at'),
                    ],
                    'brand_id' => [
                        'required',
                        Rule::exists('brands', 'id')->where('created_by', $creatorId),
                    ],
                ],
                [
                    'name.unique' => __('A model with this name already exists for the selected brand.'),
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $subBrand = new VehicleModel();
            $subBrand->name = $request->name;
            $subBrand->brand_id = $request->brand_id;
            $subBrand->created_by = $creatorId;
            $subBrand->save();

            CustomField::saveData($subBrand, $request->customField);

            return redirect()->route('sub-brand.index')->with('success', __('Model successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {

        if (\Auth::user()->can('edit constant sub-brand')) {
            $sub_brand = VehicleModel::find($id);
            $brands = Brand::where('created_by', \Auth::user()->creatorId())
            ->with('categories')
            ->get()
            ->mapWithKeys(function ($brand) {
                $categoryNames = $brand->categories->pluck('name')->implode(', ');
                return [$brand->id => $brand->name . ' (' . $categoryNames . ')'];
            });

            $sub_brand->customField = CustomField::getData($sub_brand, 'sub-brand');
            $customFields       = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'sub-brand')->get();

            return view('sub-brand.edit', compact('sub_brand', 'brands','customFields'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit constant sub-brand')) {
            $subBrand = VehicleModel::find($id);
            if ($subBrand->created_by == \Auth::user()->creatorId()) {
                $creatorId = \Auth::user()->creatorId();
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => [
                            'required',
                            'max:200',
                            Rule::unique('sub_brands', 'name')
                                ->where('created_by', $creatorId)
                                ->where('brand_id', (int) $request->brand_id)
                                ->whereNull('deleted_at')
                                ->ignore($subBrand->id),
                        ],
                        'brand_id' => [
                            'required',
                            Rule::exists('brands', 'id')->where('created_by', $creatorId),
                        ],
                    ],
                    [
                        'name.unique' => __('A model with this name already exists for the selected brand.'),
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $subBrand->name = $request->name;
                $subBrand->brand_id = $request->brand_id;
                $subBrand->save();

                CustomField::saveData($subBrand, $request->customField);

            return redirect()->route('sub-brand.index')->with('success', __('Model updated successfully'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete constant sub-brand')) {
            $brand = VehicleModel::find($id);
            if ($brand->created_by == \Auth::user()->creatorId()) {
                $related = ProductService::where('sub_brand_id', $brand->id)->first();


                if (!empty($related)) {
                    return redirect()->back()->with('error', __('This model is already assigned. Please move or remove related data first.'));
                }

                if (!empty($categories)) {
                    return redirect()->back()->with('error', __('this category is already assign so please move or remove this category related data.'));
                }


                $brand->delete();

                return redirect()->route('sub-brand.index')->with('success', __('Model deleted successfully'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function importFile(Request $request)
    {
        $updateById = $request->has('update_by_id') && $request->update_by_id == '1';
        return view('sub-brand.import', compact('updateById'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            // Disable Laravel Debugbar to prevent memory issues
            if (class_exists('\Barryvdh\Debugbar\Facades\Debugbar')) {
                \Barryvdh\Debugbar\Facades\Debugbar::disable();
            }
            if (config('debugbar.enabled')) {
                config(['debugbar.enabled' => false]);
            }
            
            // Increase execution time limit for large imports (up to 30 minutes)
            set_time_limit(1800);
            
            // Increase memory limit for large imports (2GB)
            ini_set('memory_limit', '2048M');
            
            // Disable query logging for performance
            \DB::connection()->disableQueryLog();
            
            // Increase MySQL settings for large imports
            \DB::statement('SET SESSION wait_timeout=28800');
            \DB::statement('SET SESSION interactive_timeout=28800');

        Excel::import(new SubBrandImport, $request->file('file'));

        return back()->with('success', __('Models imported successfully.'));
        } catch (\Exception $e) {
            \Log::error('Sub Brand import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => \Auth::user()->id
            ]);

            return back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            $subBrands = VehicleModel::where('created_by', \Auth::user()->creatorId())
                ->with(['brand.categories'])
                ->get();

            $filename = 'sub_brands_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($subBrands) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'ID',
                    'Model Name',
                    'Brand ID',
                    'Brand Name',
                    'Brand Categories',
                    'Brand Categories ID',
                    'Created At',
                    'Updated At'
                ]);

                foreach ($subBrands as $subBrand) {
                    fputcsv($file, [
                        $subBrand->id,
                        $subBrand->name,
                        $subBrand->brand_id,
                        $subBrand->brand ? $subBrand->brand->name : 'N/A',
                        $subBrand->brand ? $subBrand->brand->categories->pluck('name')->implode(', ') : 'N/A',
                        $subBrand->brand ? $subBrand->brand->categories->pluck('id')->implode(', ') : 'N/A',
                        $subBrand->created_at->format('Y-m-d H:i:s'),
                        $subBrand->updated_at->format('Y-m-d H:i:s')
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
