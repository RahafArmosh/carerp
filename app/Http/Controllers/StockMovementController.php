<?php
namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\ProductService;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Vender;
use App\Models\Brand;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    // Display a listing of the stock movements
    public function index(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view stock'))
        {
            $query = StockMovement::with([
                'product.category',
                'product.brand',
                'product.subBrand',
                'bill', 
                'invoice', 
                'pos',
                'Subproduct.warehouse',
                'customer',
                'vendor',
                'user'
            ])->where('created_by', \Auth::user()->creatorId());

            // Apply filters
            if ($request->filled('barcode')) {
                $barcode = trim($request->barcode);
                $query->whereHas('Subproduct', function($q) use ($barcode) {
                    $q->where('chassis_no', 'LIKE', '%' . $barcode . '%');
                });
            }

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->filled('sub_product_id')) {
                $query->where('sub_product_id', $request->sub_product_id);
            }

            if ($request->filled('brand_id')) {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('brand_id', $request->brand_id);
                });
            }

            if ($request->filled('activity')) {
                $query->where('activity', 'LIKE', '%' . $request->activity . '%');
            }

            if ($request->filled('customer_id')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('customer', function($subQ) use ($request) {
                        $subQ->where('id', $request->customer_id);
                    })->orWhere('use_id', $request->customer_id);
                });
            }

            if ($request->filled('vender_id')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('vendor', function($subQ) use ($request) {
                        $subQ->where('id', $request->vender_id);
                    })->orWhere('use_id', $request->vender_id);
                });
            }

            if ($request->filled('bill_id')) {
                $query->where('bill_id', $request->bill_id);
            }

            if ($request->filled('invoice_id')) {
                $query->where('invoice_id', $request->invoice_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Paginate results to avoid memory exhaustion (default 50 per page)
            $perPage = $request->get('per_page', 50);
            $stockMovements = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Get IDs for sold price lookup (only for paginated items)
            $movementIds = $stockMovements->pluck('id');
            $invoiceIds = $stockMovements->pluck('invoice_id')->filter()->unique()->values();
            $posIds = $stockMovements->pluck('pos_id')->filter()->unique()->values();
            $subProductIds = $stockMovements->pluck('sub_product_id')->filter()->unique()->values();

            // Pre-load sold prices in bulk to avoid N+1 queries
            $soldPrices = [];
            
            if ($invoiceIds->isNotEmpty() && $subProductIds->isNotEmpty()) {
                $invoiceProducts = \App\Models\InvoiceProduct::whereIn('invoice_id', $invoiceIds)
                    ->whereIn('sub_product_id', $subProductIds)
                    ->select('invoice_id', 'sub_product_id', 'price', 'discount')
                    ->get();
                
                foreach ($invoiceProducts as $ip) {
                    $key = "invoice_{$ip->invoice_id}_{$ip->sub_product_id}";
                    $soldPrices[$key] = StockMovement::netUnitSoldPriceFromInvoiceProduct($ip);
                }
            }
            
            if ($posIds->isNotEmpty() && $subProductIds->isNotEmpty()) {
                $posProducts = \App\Models\PosProduct::whereIn('pos_id', $posIds)
                    ->whereIn('sub_product_id', $subProductIds)
                    ->select('pos_id', 'sub_product_id', 'price', 'combo_price', 'discount')
                    ->get();
                
                foreach ($posProducts as $pp) {
                    $key = "pos_{$pp->pos_id}_{$pp->sub_product_id}";
                    $soldPrices[$key] = StockMovement::netUnitSoldPriceFromPosProduct($pp);
                }
            }

            // Add sold price to each movement using pre-loaded data
            foreach ($stockMovements as $movement) {
                $soldPrice = 0;
                
                if ($movement->invoice_id && $movement->sub_product_id) {
                    $key = "invoice_{$movement->invoice_id}_{$movement->sub_product_id}";
                    $soldPrice = $soldPrices[$key] ?? 0;
                } elseif ($movement->pos_id && $movement->sub_product_id) {
                    $key = "pos_{$movement->pos_id}_{$movement->sub_product_id}";
                    $soldPrice = $soldPrices[$key] ?? 0;
                }
                
                // Add sold_price as a dynamic attribute
                $movement->sold_price = $soldPrice;
            }

            // Get filter options
            $products = ProductService::where('created_by', \Auth::user()->creatorId())
                ->pluck('name', 'id');
            
            $brands = Brand::where('created_by', \Auth::user()->creatorId())
                ->orderBy('name', 'asc')
                ->pluck('name', 'id');
            
            $customers = Customer::where('created_by', \Auth::user()->creatorId())
                ->pluck('name', 'id');
            
            $vendors = Vender::where('created_by', \Auth::user()->creatorId())
                ->pluck('name', 'id');
            
            $bills = Bill::where('created_by', \Auth::user()->creatorId())
                ->orderBy('bill_date', 'desc')
                ->get();
            
            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())
                ->orderBy('issue_date', 'desc')
                ->get();

            return view('stockMovements.index', compact('stockMovements', 'products', 'brands', 'customers', 'vendors', 'bills', 'invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Show the form for creating a new stock movement
    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create stock'))
        {
            $products = Product::all();
            $bills = Bill::all();
            $invoices = Invoice::all();
            return view('stockMovements.create', compact('products', 'bills', 'invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Store a newly created stock movement in storage
    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create stock'))
        {
            $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_in' => 'nullable|integer|min:0',
            'qty_out' => 'nullable|integer|min:0',
            'avg_cost' => 'nullable|numeric|min:0',
            'bill_id' => 'nullable|exists:bills,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);
            $data = $request->all();
            $data['created_by'] = auth()->id();
            StockMovement::create($data);

            return redirect()->route('stock_movements.index')
                             ->with('success', 'Stock movement created successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Display the specified stock movement
    public function show(StockMovement $stockMovement)
    {
        return view('stockMovements.show', compact('stockMovement'));
    }

    // Show the form for editing the specified stock movement
    public function edit(StockMovement $stockMovement)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update stock'))
        {
            $products = Product::all();
            $bills = Bill::all();
            $invoices = Invoice::all();
            return view('stockMovements.edit', compact('stockMovement', 'products', 'bills', 'invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Update the specified stock movement in storage
    public function update(Request $request, StockMovement $stockMovement)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('update stock'))
        {
            $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_in' => 'nullable|integer|min:0',
            'qty_out' => 'nullable|integer|min:0',
            'avg_cost' => 'nullable|numeric|min:0',
            'bill_id' => 'nullable|exists:bills,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

            $stockMovement->update($request->all());

            return redirect()->route('stock_movements.index')
                             ->with('success', 'Stock movement updated successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // Remove the specified stock movement from storage
    public function destroy(StockMovement $stockMovement)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete stock'))
        {
            $stockMovement->delete();

            return redirect()->route('stock_movements.index')
                             ->with('success', 'Stock movement deleted successfully.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Export stock movements to Excel
     */
    public function export(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view stock'))
        {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StockMovementExport($request, \Auth::user()->creatorId()),
                'stock_movements_' . date('Y-m-d_His') . '.xlsx'
            );
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
