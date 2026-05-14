<?php

// app/Http/Controllers/BrandController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Brand;
use App\Models\ProductServiceCategory;
use App\Models\CustomField;
use App\Models\ProductService;
use App\Models\Invoice;
use App\Models\Bill;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BrandImport;

class BrandController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage constant brand')) {
            $search = trim((string) request('q', ''));

            $brands = Brand::query()
                ->select(['id', 'name', 'created_by'])
                ->where('created_by', \Auth::user()->creatorId())
                ->with(['categories:id,name'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->latest('id')
                ->paginate(25)
                ->withQueryString();

            return view('brand.index', compact('brands'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create constant brand')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'brand')->get();
            $category     = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

        return view('brand.create', compact('category', 'customFields'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create constant brand')) {

            $creatorId = \Auth::user()->creatorId();
            $validator = \Validator::make(
                $request->all(), [
                    'name' => [
                        'required',
                        'max:200',
                        Rule::unique('brands', 'name')
                            ->where('created_by', $creatorId)
                            ->whereNull('deleted_at'),
                    ],
                    'category_id.*' => 'exists:product_service_categories,id',
                ],
                [
                    'name.unique' => __('A brand with this name already exists for your company.'),
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $brand = new Brand();
            $brand->name = $request->name;

            $brand->created_by = $creatorId;
            $brand->save();
            $categoryIds = ! empty($request->category_id) ? (array) $request->category_id : [];
            $brand->syncCategories($categoryIds);
            CustomField::saveData($brand, $request->customField);

            return redirect()->route('brand.index')->with('success', __('Brand successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {

        if (\Auth::user()->can('edit constant brand')) {
            $brand = Brand::find($id);
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $brand->customField = CustomField::getData($brand, 'brand');
            $customFields       = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'brand')->get();

            return view('brand.edit', compact('category', 'brand','customFields'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit constant brand')) {
            $brand = Brand::find($id);
            if ($brand->created_by == \Auth::user()->creatorId()) {
                $creatorId = \Auth::user()->creatorId();
                $validator = \Validator::make(
                    $request->all(), [
                        'name' => [
                            'required',
                            'max:200',
                            Rule::unique('brands', 'name')
                                ->where('created_by', $creatorId)
                                ->whereNull('deleted_at')
                                ->ignore($brand->id),
                        ],
                    ],
                    [
                        'name.unique' => __('A brand with this name already exists for your company.'),
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $brand->name = $request->name;
                $brand->save();
                $categoryIds = ! empty($request->category_id) ? (array) $request->category_id : [];
                $brand->syncCategories($categoryIds);
                CustomField::saveData($brand, $request->customField);

            return redirect()->route('brand.index')->with('success', __('Brand updated successfully'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete constant brand')) {
            $brand = Brand::find($id);
            if ($brand->created_by == \Auth::user()->creatorId()) {

                $related = ProductService::where('brand_id', $brand->id)->first();


                if (!empty($related)) {
                    return redirect()->back()->with('error', __('This Brand is already assigned. Please move or remove related data first.'));
                }


                $brand->delete();

                return redirect()->route('brand.index')->with('success', __('Brand deleted successfully'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function getBrandByCategory($categoryId){
            // Fetch brands for the given category
            $brands = Brand::where('category_id', $categoryId)->get();

            return response()->json(['brands' => $brands]);
    }

    public function fetchBrands(Request $request)
    {
        $category_id = $request->input('category_id');
        $brands = Brand::whereHas('categories', function ($query) use ($category_id) {
            $query->where('product_service_category_id', $category_id);
        })->get();

        return response()->json(['brands' => $brands]);
    }

    public function importFile()
    {
        return view('brand.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new BrandImport, $request->file('file'));

        return back()->with('success', 'Brands imported successfully.');
    }

    public function export()
    {
        try {
            $brands = Brand::where('created_by', \Auth::user()->creatorId())
                ->with('categories')
                ->get();

            $filename = 'brands_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($brands) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers
                fputcsv($file, [
                    'ID',
                    'Brand Name',
                    'Categories',
                    'Created At',
                    'Updated At'
                ]);

                // Add data rows
                foreach ($brands as $brand) {
                    $categories = $brand->categories->pluck('name')->implode(', ');
                    
                    fputcsv($file, [
                        $brand->id,
                        $brand->name,
                        $categories,
                        $brand->created_at->format('Y-m-d H:i:s'),
                        $brand->updated_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Export failed: ') . $e->getMessage());
        }
    }

    /**
     * Show form to update brand by ID
     */
    public function updateByIdForm()
    {
        if (\Auth::user()->can('edit constant brand')) {
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            return view('brand.update-by-id', compact('category'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Get brand data by ID for update form
     */
    public function getBrandById($id)
    {
        if (\Auth::user()->can('edit constant brand')) {
            $brand = Brand::with('categories')->find($id);
            
            if (!$brand) {
                return response()->json(['error' => __('Brand not found.')], 404);
            }
            
            if ($brand->created_by != \Auth::user()->creatorId()) {
                return response()->json(['error' => __('Permission denied.')], 403);
            }
            
            return response()->json([
                'id' => $brand->id,
                'name' => $brand->name,
                'category_ids' => $brand->categories->pluck('id')->toArray()
            ]);
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update brand by ID
     */
    public function updateById(Request $request)
    {
        if (\Auth::user()->can('edit constant brand')) {
            $validator = \Validator::make(
                $request->all(), [
                    'brand_id' => 'required|integer|exists:brands,id',
                    'name' => 'required|max:200',
                    'category_id.*' => 'exists:product_service_categories,id',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $brand = Brand::find($request->brand_id);
            
            if (!$brand) {
                return redirect()->back()->with('error', __('Brand not found.'));
            }

            if ($brand->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $brand->name = $request->name;
            $brand->save();

            $categoryIds = ! empty($request->category_id) ? (array) $request->category_id : [];
            $brand->syncCategories($categoryIds);

            return redirect()->route('brand.index')->with('success', __('Brand updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
