<?php

namespace App\Http\Controllers;

use App\Models\MasterlistLeadger;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductServiceLedgerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $creator_id = \Auth::user()->creatorId();

        $query = ProductService::query()
            ->where('created_by', $creator_id)
            ->with('category')
            ->select('product_services.*')

            ->leftJoin('masterlist_leadger as ml', 'ml.product_service_id', '=', 'product_services.id')

            ->selectRaw("
                COALESCE(SUM(CASE WHEN ml.movement_type = 'free' THEN ml.qty END),0) as total_free,
                COALESCE(SUM(CASE WHEN ml.movement_type = 'booked' THEN ml.qty END),0) as total_booked,
                COALESCE(SUM(CASE WHEN ml.movement_type = 'sold' THEN ml.qty END),0) as total_sold,
                COALESCE(SUM(CASE WHEN ml.movement_type = 'on_order' THEN ml.qty END),0) as total_on_order
            ")

            ->groupBy('product_services.id');

        // Filters
        if ($request->filled('warehouse_id')) {
            $query->where('ml.warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $query->where('product_services.name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate($perPage)->withQueryString();

        return view('product_services.ledger', compact('products'));
    }
    
}