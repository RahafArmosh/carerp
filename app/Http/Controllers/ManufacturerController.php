<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use App\Models\Vender;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage manufacturer')) {



            $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income',])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');



            $manufacturers = Manufacturer::all();

            return view('manufacturers.index', compact('manufacturers', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create()
    {
        $product_services =  ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
            ->get()
            ->map(function ($productService) {
                $category = $productService->category->name ?? '';
                $brand = $productService->brand->name ?? '';
                $subBrand = $productService->subBrand->name ?? '';
                $productName = $productService->name;

                return [
                    'id' => $productService->id,
                    'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName,
                    'category_id' => $productService->category->id
                ];
            });
        // $product_services->prepend('Select Item', '');
        $product_services_spar =  ProductService::where('created_by', \Auth::user()->creatorId())->with(['brand', 'subBrand', 'category'])
            ->get()
            ->map(function ($productService) {
                $category = $productService->category->name ?? '';
                $brand = $productService->brand->name ?? '';
                $subBrand = $productService->subBrand->name ?? '';
                $productName = $productService->name;

                return [
                    'id' => $productService->id,
                    'name' => $category . '/' . $brand . '/' . $subBrand . '/' . $productName,
                ];
            })
            ->pluck('name', 'id');
        // $product_services_spar->prepend('Select Manufacture Item', '');
        $product_services_spar_full =  ProductService::where('created_by', \Auth::user()->creatorId())->get();
        $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
            ->whereNotIn('type', ['product & service', 'income',])
            ->get()->pluck('name', 'id');
        $category->prepend('Select Category', '');
        $tax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $fullTax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
        $venders     = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $venders->prepend('Select Vender', '');
        return view('manufacturers.create', compact('product_services', 'category', 'tax', 'fullTax', 'venders', 'product_services_spar','product_services_spar_full'));
    }

    public function store(Request $request)
    {
        dd($request->all());
        if (\Auth::user()->can('create manufacturer')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'item' => 'required',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages3 = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages3->first());
            }
            $manufacturer = new Manufacturer();
            $manufacturer->$manufacturer->products()->sync($request->product_ids);
            return redirect()->route('manufacturers.index')->with('success', 'Manufacturer created successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        $manufacturer = Manufacturer::with('products')->findOrFail($id);
        return view('manufacturers.show', compact('manufacturer'));
    }

    public function Tobill($id) {}

    public function getProductsByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');

        $products = ProductService::where('category_id', $categoryId)->pluck('name', 'id');

        return response()->json(['products' => $products]);
    }
}
