<?php

namespace App\Http\Controllers;

use App\Exports\PriceRulesTemplateExport;
use App\Imports\PriceRulesImport;
use App\Models\PriceRule;
use App\Models\warehouse;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ProductService;
use App\Models\PosLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class PriceListRoleController extends Controller
{
    /**
     * Display a listing of the price rules.
     */
    public function index()
    {
        $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
        $priceRules = PriceRule::where('created_by', \Auth::user()->creatorId())
            ->with('warehouse')
            ->latest()
            ->paginate(10);
        return view('pricelist.index', compact('priceRules', 'warehouses'));
    }

    /**
     * Show the form for creating a new price rule.
     */
    public function create()
    {
        $warehouses = warehouse::where('created_by',\Auth::user()->creatorId())->get();
        return view('pricelist.create', compact('warehouses'));
    }

    /**
     * Store a newly created price rule in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'apply_to' => 'required|in:product,category,brand,sub_brand',
            'target_id' => 'required|integer',
            'price_mode' => 'required|in:discount,formula,fixed',
            'base_price_source' => 'required|string', 
            'value' => 'required|numeric|min:0'
        ]);

        $validated['created_by'] = \Auth::user()->creatorId();
        $validated['apply_99'] = $request->has('apply_99');

        $price_rule = PriceRule::create($validated);
        
        // Log price rule creation
        PosLog::logAction('create_price_list', [
            'type' => 'price_list',
            'reference_id' => $price_rule->id,
            'warehouse_id' => $validated['warehouse_id'],
            'new_value' => [
                'id' => $price_rule->id,
                'warehouse_id' => $price_rule->warehouse_id,
                'apply_to' => $price_rule->apply_to,
                'target_id' => $price_rule->target_id,
                'price_mode' => $price_rule->price_mode,
                'value' => $price_rule->value,
            ],
            'description' => "Created price rule ID {$price_rule->id} for {$price_rule->apply_to}",
        ]);

        $warehouseId = $validated['warehouse_id'];
        $applyTo = $validated['apply_to'];
        $targetId = $validated['target_id'];

        $subProductsQuery = \App\Models\SubProduct::query()
            ->where('warehouse_id', $warehouseId);

        // Filtering logic
        if ($applyTo === 'product') {
            $subProductsQuery->where('product_id', $targetId);
        } else {
            $subProductsQuery->whereHas('productService', function ($query) use ($applyTo, $targetId) {
                $column = match ($applyTo) {
                    'category' => 'category_id',
                    'brand' => 'brand_id',
                    'sub_brand' => 'sub_brand_id',
                    default => null,
                };
                if ($column) {
                    $query->where($column, $targetId);
                }
            });
        }

        $matchedSubProducts = $subProductsQuery->with('productService')->get();
        // dd($matchedSubProducts);
        foreach ($matchedSubProducts as $subProduct) {
            $subProduct->price_rule_id = $price_rule->id;
            if ($price_rule->price_mode=='fixed'){
                $subProduct->sale_price =$price_rule->value ;
            }
            $subProduct->save();
        }
        
        return redirect()->route('pricelist.index')->with('success', 'Price rule created and linked to relevant sub products.');

        // Prepare an array of calculated prices (without saving)
        // $calculatedPrices = [];


        // foreach ($matchedSubProducts as $subProduct) {
        //     $basePrice = $validated['base_price_source'] === 'purchase'
        //         ? $subProduct->purchase_price
        //         : $subProduct->sale_price;

        //     $newPrice = match ($validated['price_mode']) {
        //         'discount' => $basePrice * (1 - $validated['value'] / 100),
        //         'formula'  => $basePrice * (1 + $validated['value'] / 100),
        //         'fixed'    => $validated['value'],
        //     };

        //     if ($validated['apply_99']) {
        //         $newPrice = floor($newPrice) - 0.01;
        //     }

        //     $calculatedPrices[] = [
        //         'sub_product_id' => $subProduct->id,
        //         'original_price' => $basePrice,
        //         'calculated_price' => round($newPrice, 2),
        //     ];
        // }

        // You can log or pass this to the view/session if needed
        
        // return redirect()->route('pricelist.index')->with('success', 'Price rule created. Calculated prices prepared.');
    }

    
    public function getTargets($type)
    {
        switch ($type) {
            case 'product':
                $items = ProductService::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get();
                break;
            case 'category':
                $items = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get();
                break;
            case 'brand':
                $items = Brand::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get();
                break;
            case 'sub_brand':
                $items = VehicleModel::where('created_by', \Auth::user()->creatorId())
                    ->select('id', 'name')
                    ->get();
                break;
            default:
                $items = collect();
        }
        return response()->json($items);
    }

    // /**
    //  * Show the form for editing a price rule.
    //  */
    public function edit(string $id)
    {
        $priceRule = PriceRule::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
        $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->get();
        $categories = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get();
        $brands = Brand::where('created_by', \Auth::user()->creatorId())->get();
        $subBrands = VehicleModel::where('created_by', \Auth::user()->creatorId())->get();
        $products = ProductService::where('created_by', \Auth::user()->creatorId())->get();

        return view('pricelist.edit', compact('priceRule', 'warehouses', 'categories', 'brands', 'subBrands', 'products'));
    }

    /**
     * Update the specified price rule in storage.
     */
    public function update(Request $request, string $id)
    {
        $priceRule = PriceRule::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'apply_to' => 'required|in:product,category,brand,sub_brand',
            'target_id' => 'required|integer',
            'price_mode' => 'required|in:discount,formula,fixed',
            'value' => 'required|numeric|min:0',
            'apply_99' => 'nullable|boolean',
        ]);
        $validated['apply_99'] = $request->has('apply_99');
        
        // Store old values for logging
        $oldValues = [
            'warehouse_id' => $priceRule->warehouse_id,
            'apply_to' => $priceRule->apply_to,
            'target_id' => $priceRule->target_id,
            'price_mode' => $priceRule->price_mode,
            'value' => $priceRule->value,
        ];
        
        $priceRule->update($validated);
        
        // Log price rule update
        PosLog::logAction('update_price_list', [
            'type' => 'price_list',
            'reference_id' => $priceRule->id,
            'warehouse_id' => $priceRule->warehouse_id,
            'old_value' => $oldValues,
            'new_value' => [
                'id' => $priceRule->id,
                'warehouse_id' => $priceRule->warehouse_id,
                'apply_to' => $priceRule->apply_to,
                'target_id' => $priceRule->target_id,
                'price_mode' => $priceRule->price_mode,
                'value' => $priceRule->value,
            ],
            'description' => "Updated price rule ID {$priceRule->id}",
        ]);
        
        return redirect()->route('pricelist.index')->with('success', 'Price rule updated successfully.');
    }

    // /**
    //  * Remove the specified price rule from storage.
    //  */
    public function destroy(string $id)
    {
        $priceRule = PriceRule::where('created_by', \Auth::user()->creatorId())->findOrFail($id);
        
        // Log price rule deletion before deleting
        PosLog::logAction('delete_price_list', [
            'type' => 'price_list',
            'reference_id' => $priceRule->id,
            'warehouse_id' => $priceRule->warehouse_id,
            'old_value' => [
                'id' => $priceRule->id,
                'warehouse_id' => $priceRule->warehouse_id,
                'apply_to' => $priceRule->apply_to,
                'target_id' => $priceRule->target_id,
                'price_mode' => $priceRule->price_mode,
                'value' => $priceRule->value,
            ],
            'description' => "Deleted price rule ID {$priceRule->id}",
        ]);
        
        $priceRule->delete();

        return redirect()->route('pricelist.index')->with('success', 'Price rule deleted.');
    }

    public function templateForm()
    {
        return view('pricelist.template_form');
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new PriceRulesTemplateExport(),
            'price_rules_template.xlsx'
        );
    }

    public function uploadTemplate(Request $request)
    {
        \Log::info('Price rules import started', [
            'user_id' => \Auth::id(),
            'creator_id' => \Auth::user()?->creatorId(),
            'has_file' => $request->hasFile('file'),
            'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
            'file_size' => $request->file('file') ? $request->file('file')->getSize() : null,
        ]);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new PriceRulesImport((int) \Auth::user()->creatorId()), $request->file('file'));

            return redirect()
                ->route('pricelist.index')
                ->with('success', 'Price rules imported successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Catch import validation errors
            \Log::warning('Price rules import validation failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()?->creatorId(),
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'errors' => $e->errors(),
            ]);
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::warning('Price rules import excel validation failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()?->creatorId(),
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'errors' => $e->errors(),
            ]);
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Price rules import failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()?->creatorId(),
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
