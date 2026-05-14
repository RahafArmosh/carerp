<?php
namespace App\Http\Controllers;

use App\Models\ProductService;
use App\Models\warehouse;
use App\Models\WarehouseProductPriceList;
use App\Models\PosLog;
use Illuminate\Http\Request;

use function Termwind\render;

class WarehousePriceListController extends Controller
{
    
    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view price list'))
        {
            $priceLists = \App\Models\WarehouseProductPriceList::with(['productService', 'warehouse'])
                ->whereHas('warehouse', function($query) {
                    $query->where('created_by', \Auth::user()->creatorId());
                })
                ->get();

            return view('warehouse_price_lists.index', compact('priceLists'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create price list'))
        {
            $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
            $products = ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
            return view('warehouse_price_lists.create', compact('warehouses', 'products'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create price list'))
        {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'productservice_id' => 'required|exists:product_services,id',
                'sale_price' => 'nullable|numeric',
            ]);

            // Verify warehouse belongs to user
            $warehouse = warehouse::where('id', $validated['warehouse_id'])
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $priceList = WarehouseProductPriceList::updateOrCreate(
                [
                    'warehouse_id' => $validated['warehouse_id'],
                    'productservice_id' => $validated['productservice_id'],
                ],
                [
                    'sale_price' => $validated['sale_price'],
                ]
            );
            
            // Log price list creation/update
            $isUpdate = $priceList->wasRecentlyCreated === false;
            PosLog::logAction($isUpdate ? 'update_price_list' : 'create_price_list', [
                'type' => 'price_list',
                'reference_id' => $priceList->id,
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['productservice_id'],
                'new_value' => [
                    'id' => $priceList->id,
                    'warehouse_id' => $priceList->warehouse_id,
                    'productservice_id' => $priceList->productservice_id,
                    'sale_price' => $priceList->sale_price,
                ],
                'description' => ($isUpdate ? 'Updated' : 'Created') . " price list for product ID {$validated['productservice_id']} in warehouse",
            ]);

            return redirect()->route('warehouse-price-list.index');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    
    public function show(string $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view price list'))
        {
            $priceList = WarehouseProductPriceList::with(['productService', 'warehouse'])
                ->whereHas('warehouse', function($query) {
                    $query->where('created_by', \Auth::user()->creatorId());
                })
                ->findOrFail($id);
            return response()->json($priceList);
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    
        
    public function edit($id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update price list'))
        {
            $entry = WarehouseProductPriceList::whereHas('warehouse', function($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })->findOrFail($id);
            $warehouses = warehouse::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
            $products = ProductService::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');

            return view('warehouse_price_lists.edit', compact('entry', 'warehouses', 'products'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update price list'))
        {
            $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'productservice_id' => 'required|exists:product_services,id',
                'sale_price' => 'nullable|numeric',
            ]);

            // Verify warehouse belongs to user
            $warehouse = warehouse::where('id', $request->warehouse_id)
                ->where('created_by', \Auth::user()->creatorId())
                ->firstOrFail();

            $entry = WarehouseProductPriceList::whereHas('warehouse', function($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })->findOrFail($id);
            
            // Store old values for logging
            $oldValues = [
                'warehouse_id' => $entry->warehouse_id,
                'productservice_id' => $entry->productservice_id,
                'sale_price' => $entry->sale_price,
            ];
            
            $entry->update($request->only(['warehouse_id', 'productservice_id', 'sale_price']));
            
            // Log price list update
            PosLog::logAction('update_price_list', [
                'type' => 'price_list',
                'reference_id' => $entry->id,
                'warehouse_id' => $entry->warehouse_id,
                'product_id' => $entry->productservice_id,
                'old_value' => $oldValues,
                'new_value' => [
                    'id' => $entry->id,
                    'warehouse_id' => $entry->warehouse_id,
                    'productservice_id' => $entry->productservice_id,
                    'sale_price' => $entry->sale_price,
                ],
                'description' => "Updated price list entry ID {$entry->id}",
            ]);

            return redirect()->back()->with('success', __('Price entry updated successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    
    public function destroy(string $id)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete price list'))
        {
            $priceList = WarehouseProductPriceList::whereHas('warehouse', function($query) {
                $query->where('created_by', \Auth::user()->creatorId());
            })->findOrFail($id);
            
            // Log price list deletion before deleting
            PosLog::logAction('delete_price_list', [
                'type' => 'price_list',
                'reference_id' => $priceList->id,
                'warehouse_id' => $priceList->warehouse_id,
                'product_id' => $priceList->productservice_id,
                'old_value' => [
                    'id' => $priceList->id,
                    'warehouse_id' => $priceList->warehouse_id,
                    'productservice_id' => $priceList->productservice_id,
                    'sale_price' => $priceList->sale_price,
                ],
                'description' => "Deleted price list entry ID {$priceList->id}",
            ]);
            
            $priceList->delete();

            return redirect()->back()->with('success', __('Price List entry Deleted successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
