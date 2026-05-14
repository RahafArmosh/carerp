<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterlistLeadger;
use App\Models\PricingListType;
use App\Models\ProductService;
use App\Models\ProItem;
use App\Models\warehouse;
use Illuminate\Support\Facades\DB;

class MasterlistLedgerController extends Controller
{

    public function stock(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $sku = $request->sku;



    // Get all pricing list types
        $priceTypes = PricingListType::where('created_by', \Auth::user()->creatorId())->get();
    //    $products = ProductService::query()
    //         ->where('type','product')
    //         ->where('created_by', \Auth::user()->creatorId())
    //         ->withSum(['ledgers as free_qty' => function ($q) use ($warehouseId) {
    //             $q->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
    //             ->where('movement_type','free');
    //         }],'qty')
    //         ->withSum(['ledgers as booked_qty' => function ($q) use ($warehouseId) {
    //             $q->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
    //             ->where('movement_type','booked');
    //         }],'qty')
    //         ->withSum(['ledgers as sold_qty' => function ($q) use ($warehouseId) {
    //             $q->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
    //             ->where('movement_type','sold');
    //         }],'qty')
    //         ->with(['category', 'brand'])
    //         ->with(['subProducts' => function($q) {
    //             $q->orderBy('id')->limit(1)->with('customFieldValues');
    //         }])
    //         ->paginate(20);

    // Base products query
    $netQty = 'qty - qty_out';

    $products = ProductService::query()
        ->where('type','product')
        ->where('created_by', \Auth::user()->creatorId())

        ->when($request->search, function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('name','like','%'.$request->search.'%')
                ->orWhere('sku','like','%'.$request->search.'%');
            });
        })

        ->select('*')

        ->selectSub(function ($q) use ($warehouseId) {
            $q->from('masterlist_leadger')
                ->selectRaw('COALESCE(SUM(qty - qty_out),0)')
                ->whereColumn('product_service_id','product_services.id')
                ->when($warehouseId, fn($q)=>$q->where('warehouse_id',$warehouseId))
                ->where('movement_type','free');
        }, 'free_qty')

        ->selectSub(function ($q) use ($warehouseId) {
            $q->from('masterlist_leadger')
                ->selectRaw('COALESCE(SUM(qty - qty_out),0)')
                ->whereColumn('product_service_id','product_services.id')
                ->when($warehouseId, fn($q)=>$q->where('warehouse_id',$warehouseId))
                ->where('movement_type','booked');
        }, 'booked_qty')

        ->selectSub(function ($q) use ($warehouseId) {
            $q->from('masterlist_leadger')
                ->selectRaw('COALESCE(SUM(qty - qty_out),0)')
                ->whereColumn('product_service_id','product_services.id')
                ->when($warehouseId, fn($q)=>$q->where('warehouse_id',$warehouseId))
                ->where('movement_type','sold');
        }, 'sold_qty')

        ->with([
            'category',
            'brand',
            'subProducts' => fn($q) => $q->orderBy('id')->limit(1)->with('customFieldValues')
        ])

        ->with([
            'pricingLists' => fn($q) => $q->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
        ])

        ->paginate(20);
        // dd($products);
    // return view('master-list-ledger.stock', compact('products','warehouseId','priceTypes'));
        return view('master-list-ledger.stock', compact('products', 'warehouseId', 'sku','priceTypes'));
    }

    public function index()
    {
        $warehouses = Warehouse::where('created_by', \Auth::user()->creatorId())->get();

        return view('master-list-ledger.index', compact('warehouses'));
    }
    public function records(Request $request)
    {

        $productId = $request->product;
        $movement = $request->movement;
        $warehouseId = $request->warehouse_id;

        $records = MasterlistLeadger::query()

            ->where('product_service_id',$productId)
            ->where('movement_type',$movement)

            ->when($warehouseId,function($q) use ($warehouseId){
                $q->where('warehouse_id',$warehouseId);
            })

            ->latest()
            ->paginate(20);

        return view('master-list-ledger.records',compact(
            'records',
            'movement'
        ));
    }

    public function onorder_details(Request $request)
    {
        $productId = $request->product;
        $warehouseId = $request->warehouse_id;

        $records = ProItem::query()

            ->where('product_id', $productId)

            ->with([
                'pro',
                'product',
            ])

            ->latest()
            ->paginate(20);

        return view('master-list-ledger.onorder_records', compact(
            'records'
        ));
    }
}