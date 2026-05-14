<?php

namespace App\Http\Controllers;

use App\Exports\PricingListTemplateExport;
use App\Imports\PricingListImport;
use App\Models\PricingList;
use App\Models\PricingListType;
use App\Models\ProductService;
use App\Models\warehouse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PricingListsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $creatorId = \Auth::user()->creatorId();

        $pricingTypes = \App\Models\PricingListType::where('created_by', $creatorId)->get();

        $products = \App\Models\ProductService::where('created_by', $creatorId)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sku')
            ->paginate(150)
            ->withQueryString();

        $warehouses = \App\Models\warehouse::where('created_by', $creatorId)->get();

        $pricingLists = \App\Models\PricingList::where('created_by', $creatorId)->get();
        
        /**
         * pricingIndex structure:
         * [product_id][warehouse_id][pricing_type_id] => PricingList|null
         */
        $pricingIndex = [];

        foreach ($pricingLists as $pricing) {
            $pricingIndex
                [(int) $pricing->product_service_id]
                [(int) $pricing->warehouse_id]
                [(int) $pricing->pricing_list_type_id]
                = $pricing;
        }
        

        $rows = [];

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {

                $prices = [];

                foreach ($pricingTypes as $type) {
                    $prices[$type->id] =
                        $pricingIndex[$product->id][$warehouse->id][$type->id]
                        ?? null;
                }

                $rows[] = [
                    'product_id'   => $product->id,
                    'sku'          => $product->sku,
                    'warehouse_id' => $warehouse->id,
                    'warehouse'    => $warehouse->name,
                    'prices'       => $prices,
                ];
            }
        }
        // dd($rows,$pricingIndex);

        return view('pricing_lists.index', [
            'rows'         => $rows,
            'products'     => $products, // for pagination links
            'pricingTypes' => $pricingTypes,
            'warehouses'   => $warehouses,
            'search'       => $search,
        ]);
    }


    public function create()
    {
        return view('pricing_lists.create', [
            'types'      => PricingListType::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(),
            'products'   => ProductService::where('created_by', \Auth::user()->creatorId())->orderBy('sku')->get(),
            'warehouses' => warehouse::where('created_by', \Auth::user()->creatorId())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        
        $data = $request->validate([
            'pricing_list_type_id' => 'required|exists:pricing_list_types,id',
            'product_service_id'   => 'required|exists:product_services,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'current_price'                => 'required|numeric|min:0',
        ]);

        PricingList::updateOrCreate(
            [
                'pricing_list_type_id' => $data['pricing_list_type_id'],
                'product_service_id'   => $data['product_service_id'],
                'warehouse_id'         => $data['warehouse_id'],
            ],
            [
                'current_price'      => $data['current_price'],
                'created_by' => \Auth::user()->creatorId(),
            ]
        );

        return redirect()
            ->route('pricing-lists.index')
            ->with('success', __('Price saved successfully'));
    }

    /**
     * Edit page
     */
    public function edit(PricingList $pricingList)
    {
        return view('pricing_lists.edit', [
            'pricingList' => $pricingList,
            'types'       => PricingListType::orderBy('name')->get(),
            'products'    => ProductService::orderBy('sku')->get(),
            'warehouses'  => warehouse::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PricingList $pricingList)
    {
        $data = $request->validate([
            'pricing_list_type_id' => 'required|exists:pricing_list_types,id',
            'product_service_id'   => 'required|exists:product_services,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'current_price'                => 'required|numeric|min:0',
        ]);

        // prevent duplicates
        PricingList::where('id', '!=', $pricingList->id)
            ->where('pricing_list_type_id', $data['pricing_list_type_id'])
            ->where('product_service_id', $data['product_service_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->delete();

        $pricingList->update([
            ...$data,
            'created_by' =>  \Auth::user()->creatorId(),
        ]);

        return redirect()
            ->route('pricing-lists.index')
            ->with('success', __('Price updated successfully'));
    }

    public function destroy(PricingList $pricingList)
    {
        $pricingList->delete();

        return redirect()
            ->route('pricing-lists.index')
            ->with('success', __('Price deleted'));
    }
    public function export(Request $request)
    {
        return Excel::download(
            new PricingListTemplateExport(),
            'pricing-list-template.xlsx'
        );
    }
    public function import(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'file'         => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(
                new PricingListImport((int) $request->warehouse_id, (int) \Auth::user()->creatorId()),
                $request->file('file')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Pricing list import validation failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()->creatorId(),
                'warehouse_id' => (int) $request->warehouse_id,
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'errors' => $e->errors(),
            ]);
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::warning('Pricing list import excel validation failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()->creatorId(),
                'warehouse_id' => (int) $request->warehouse_id,
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'errors' => $e->errors(),
            ]);
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            \Log::error('Pricing list import failed', [
                'user_id' => \Auth::id(),
                'creator_id' => \Auth::user()->creatorId(),
                'warehouse_id' => (int) $request->warehouse_id,
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->with('error', __('Import failed. Please check the logs.'))
                ->withInput();
        }

        return redirect()
            ->route('pricing-lists.index')
            ->with('success', 'Pricing list imported successfully.');
    }
}
