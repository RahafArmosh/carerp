<?php

namespace App\Http\Controllers;

use App\Models\ComboOffer;
use App\Models\Pos;
use App\Models\PosProduct;
use App\Models\PosProductsRefund;
use App\Models\GeneralLedger;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\Utility;
use App\Models\PosLog;
use App\Models\SubProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PosProductsRefundController extends Controller
{
    public function index()
    {
        if(\Auth::user()->type == 'company' || Auth::user()->can('view pos refund'))
        {
            $refunds = \App\Models\PosRefund::with(['pos.customer', 'items.posProduct.sub_product.productService', 'items.posProduct.product', 'voucher'])
                ->whereHas('pos', function($query) {
                    $query->where('created_by', Auth::user()->creatorId());
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return view('pos_refund.index', compact('refunds'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    function canRefund(int $wanted, array $product): array
    {
        $already   = $product['already_refunded'];
        $normalQty = $product['normal_qty'];
        $comboQty  = $product['combo_qty'];

        // remaining available
        $remainingNormal = max(0, $normalQty - min($already, $normalQty));
        $remainingCombo  = max(0, $comboQty - max(0, $already - $normalQty));

        $maxRefundable = $remainingNormal + $remainingCombo;

        if ($wanted > $maxRefundable) {
            return [
                'allowed' => false,
                'refundable_value_qty' => 0,
            ];
        }

        if ($wanted <= $remainingNormal) {
            return [
                'allowed' => true,
                'refundable_value_qty' => $wanted,
            ];
        }

        // if they want more than normal, combos get consumed but no value
        return [
            'allowed' => true,
            'refundable_value_qty' => $remainingNormal,
        ];
    }


    public function create(Request $request){
        // dd($request);
        if(\Auth::user()->type == 'company' || Auth::user()->can('create pos refund'))
        {
            // Base set of POS eligible for refund (time window, status, no combos, no vouchers)
            $poss = Pos::where('created_at', '>=', Carbon::now()->subMonth())
                ->whereIn('status', ['active', 'partial_refund'])
                ->where('created_by', Auth::user()->creatorId())
                ->whereDoesntHave('products', function($query) {
                    $query->whereNotNull('compo_id');
                })
                ->whereDoesntHave('payments', function($query) {
                    $query->whereNotNull('voucher_id');
                })
                ->get();

            // Filter out POS that have no refundable quantity left in pos_products_refunds
            $poss = $poss->filter(function (Pos $pos) {
                $row = \DB::table('pos_products as pp')
                    ->leftJoin('pos_products_refunds as r', 'r.pos_products_id', '=', 'pp.id')
                    ->where('pp.pos_id', $pos->id)
                    ->selectRaw('SUM(pp.quantity) as total_bought, COALESCE(SUM(r.quantity), 0) as total_refunded')
                    ->first();

                $totalBought = (int) ($row->total_bought ?? 0);
                $totalRefunded = (int) ($row->total_refunded ?? 0);
                $available = $totalBought - $totalRefunded;

                return $available > 0;
            })->values();

            return view('pos_refund.create',compact('poss'));
        }else{
             return response()->json(
                [
                    'error' => __('Add some products to cart!'),
                ],
                '404'
            );
        }
    }
    public function refundableItems($posId)
    {
        try {
            $items = \DB::table('pos_products as pp')
                ->join('sub_products as sp', 'pp.sub_product_id', '=', 'sp.id')
                ->join('product_services as p', 'pp.product_id', '=', 'p.id')
                ->leftJoin('pos_products_refunds as r', 'r.pos_products_id', '=', 'pp.id')
                ->select(
                    'sp.chassis_no as product_no',
                    'p.name as product_name',
                    \DB::raw('AVG(pp.price) as unit_price'), // price per unit
                    \DB::raw('SUM(pp.quantity) as total_bought'),
                    \DB::raw('COALESCE(SUM(r.quantity), 0) as total_refunded'),
                    \DB::raw('(SUM(pp.quantity) - COALESCE(SUM(r.quantity), 0)) as available_to_refund'),
                    \DB::raw('SUM(pp.quantity * pp.price) as total_paid') // total money spent on this product
                )
                ->where('pp.pos_id', $posId)
                ->groupBy('sp.chassis_no as product_no', 'p.name')
                ->get();

            // return for API
            if (request()->wantsJson()) {
                return response()->json([
                    'code' => 200,
                    'data' => $items
                ]);
            }

            // return for blade
            return view('pos_refund.refundables', compact('items'));

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function get_products_to_refund(Request $request)
    {
        $pos = Pos::where('id',$request->Pos_id)->with([
            'products.sub_product',     // sold products
            'refunds.items.posProduct.sub_product',  // refunded products
            'payments',
            'paymentRefunds'
        ])->first();

        // Group pos_products by product_no (product number on sub_product)
        $productsGrouped = $pos->products->groupBy(fn($pp) => optional($pp->sub_product)->product_no);

        // Get tax data for POS
        $taxRate = 0;
        if (!empty($pos->tax_id)) {
            $getTaxData = \App\Models\Utility::getTaxData();
            $taxes = explode(',', $pos->tax_id);
            foreach ($taxes as $tax) {
                if (isset($getTaxData[$tax])) {
                    $taxRate += (float)$getTaxData[$tax]['rate'];
                }
            }
        }

        $results = $productsGrouped->map(function ($posProducts, $productNo) use ($pos, $taxRate) {
            // total bought qty
            $totalBought = $posProducts->sum('quantity');

            // Calculate total paid amount with discount/combo + tax for each item
            $totalPaidWithTax = 0.0;
            $paidUnitsTotal = 0;

            // Process each POS product to calculate correct price after discount/combo + tax
            foreach ($posProducts as $pp) {
                $qty = (int)($pp->quantity ?? 0);
                
                // Calculate unit price: use combo_price if combo exists, otherwise price after discount
                $unitPrice = 0;
                if (!empty($pp->compo_id) && $pp->compo_id != '0' && $pp->combo_price !== null) {
                    // Use combo price
                    $unitPrice = (float)($pp->combo_price ?? 0);
                } else {
                    // Use regular price after discount
                    $basePrice = (float)($pp->price ?? 0);
                    $discount = (float)($pp->discount ?? 0);
                    
                    // Calculate price after discount: basePrice * (1 - discount/100)
                    if ($discount > 0 && $discount < 100) {
                        $unitPrice = $basePrice * (1 - ($discount / 100));
                    } else {
                        $unitPrice = $basePrice;
                    }
                }
                
                // Calculate tax per unit on price after discount/combo
                $taxPerUnit = 0;
                if ($taxRate > 0) {
                    $taxPerUnit = $unitPrice * ($taxRate / 100);
                }
                
                // Total price per unit: price after discount/combo + tax
                $itemTotalPricePerUnit = $unitPrice + $taxPerUnit;
                
                // Add to total paid (price after discount/combo + tax) * quantity
                $totalPaidWithTax += $itemTotalPricePerUnit * $qty;
                $paidUnitsTotal += $qty;
            }

            // Refunds already made for this product_no
            // Get all refund items for this POS and filter by product_no
            $refundedQty = 0;
            $refundedAmount = 0;
            
            foreach ($pos->refunds as $refund) {
                foreach ($refund->items as $item) {
                    if ($item->product_no === $productNo) {
                        $refundedQty += $item->quantity;
                        $refundedAmount += $item->return_price;
                    }
                }
            }

            // derived numbers
            $availableToRefund = max(0, $totalBought - $refundedQty);
            
            // Skip items that are fully refunded (no available quantity to refund)
            if ($availableToRefund <= 0) {
                return null;
            }
            
            $unitPriceAvg = $totalBought > 0 ? ($totalPaidWithTax / $totalBought) : 0.0;  // avg price after discount/combo + tax
            $avgPaidPerItem = $unitPriceAvg; // Same as unit price avg (includes tax)

            return [
                'product_no'            => $productNo,
                'product_name'          => optional($posProducts->first()->sub_product)->product_no ?: null, // fallback
                'total_bought'          => (int)$totalBought,
                'unit_price_avg'        => round($unitPriceAvg, 2),  // Price after discount/combo + tax
                'paid_units'            => (int)$paidUnitsTotal,
                'total_paid'            => round($totalPaidWithTax, 2),  // Total paid after discount/combo + tax
                'avg_paid_per_item'     => round($avgPaidPerItem, 2),  // Avg price per item (after discount/combo + tax)
                'refunded_quantity'     => (int)$refundedQty,
                'refunded_amount'       => round($refundedAmount, 2),
                'available_to_refund'   => (int)$availableToRefund,
            ];
        })->filter()->values(); // Filter out null values (fully refunded items)

        // send to blade
        return view('pos_refund.products', [
            'pos' => $pos,
            'products' => $results,
        ]);
    }
   public function store_products_refund(Request $request)
    {
        if(\Auth::user()->type == 'company' || Auth::user()->can('create pos refund'))
        {
            $request->validate([
                'pos_id' => 'required|integer|exists:pos,id',
                'refunds' => 'nullable|array', // keys are sub_product.product_no
            ]);

            // Ensure at least one refund quantity > 0 was provided
            $refundsInputRaw = $request->input('refunds', []);
            $hasPositiveQty = false;
            if (is_array($refundsInputRaw)) {
                foreach ($refundsInputRaw as $qty) {
                    if ((int)$qty > 0) {
                        $hasPositiveQty = true;
                        break;
                    }
                }
            }
            if (!$hasPositiveQty) {
                // Redirect to the create screen (GET route) instead of back to POST URL
                return redirect()
                    ->route('pos_product_refund.create')
                    ->with('error', __('You must enter a refund quantity for at least one item.'));
            }

            $pos = Pos::where('id', $request->pos_id)
                ->with(['products.sub_product', 'refunds.items.posProduct.sub_product'])
                ->firstOrFail();

            $refundsInput = $refundsInputRaw;

            // Get all relevant combo offers
            $comboMap = ComboOffer::whereIn(
                'id',
                $pos->products->pluck('compo_id')->filter()
            )->get()->keyBy('id');

            $result = [];

        // Helper function to decide if a wanted quantity is allowed
        $computeCanRefund = function (int $wanted, int $remainingNormal, array $availableComboInstances) {
            $usedNormal = min($wanted, $remainingNormal);
            $remainingWanted = $wanted - $usedNormal;

            $refundableValue = $usedNormal; // all normal units are paid

            if ($remainingWanted === 0) {
                return [
                    'allowed' => true,
                    'refundable_value_qty' => $refundableValue,
                    'used_normal' => $usedNormal,
                    'used_combos' => [],
                ];
            }

            // Check if remainingWanted exactly matches any combo instance
            foreach ($availableComboInstances as $idx => $inst) {
                if ($inst['count'] === $remainingWanted) {
                    // full combo instance can be refunded
                    $refundableValue += min($inst['paid'], $inst['count']);
                    return [
                        'allowed' => true,
                        'refundable_value_qty' => $refundableValue,
                        'used_normal' => $usedNormal,
                        'used_combos' => [$idx],
                    ];
                }
            }

            // Cannot match remainingWanted → not allowed
            return [
                'allowed' => false,
                'refundable_value_qty' => $refundableValue,
                'used_normal' => $usedNormal,
                'used_combos' => [],
            ];
        };

        // Group POS products by sub_product product_no
        $pos_products_groups = $pos->products
        ->groupBy(fn($p) => optional($p->sub_product)->product_no ?? $p->product_id)
        ->map(function ($group) {
            return [
                'total_quantity' => $group->sum('quantity'),
                'sub_product_ids' => $group->pluck('sub_product_id')->unique()->values()->all(),
                'pos_products_ids' => $group->pluck('id')->unique()->values()->all(),
                
            ];
            });
         $pos_refunded_products_groups = $pos->refunds
        ->groupBy(fn($p) =>$p->product_no )
        ->map(function ($group) {
            return [
                'total_quantity' => $group->sum('quantity'),
                'pos_products_ids' => $group->pluck('pos_products_id')->unique()->values()->all(),
            ];
        });
        // dd(compact(['pos','refundsInput','comboMap','pos_products_groups','pos_refunded_products_groups']));
       
        $groped_by_ponum = $pos->products->groupBy(fn($p) => optional($p->sub_product)->product_no ?? $p->product_id)->all();
        foreach ($groped_by_ponum as $productNo => $lines) {
            $normalQty = (int) $lines->whereNull('compo_id')->sum('quantity');

            $comboInstances = [];
            foreach ($lines->whereNotNull('compo_id')->groupBy('compo_id') as $compoId => $instLines) {
                if (!isset($comboMap[$compoId])) continue;

                $combo = $comboMap[$compoId];
                $instTotalCount = $combo->buy_quantity + $combo->get_quantity; // total units in combo
                $instPaidCount = $combo->buy_quantity; // paid units only

                $comboInstances[] = [
                    'compo_id' => $compoId,
                    'count' => $instTotalCount,
                    'paid' => $instPaidCount,
                    'lines' => $instLines,
                ];
            }

            // already refunded for this product
            $refundsForProduct = $pos->refunds->filter(fn($r) =>
                $r->posProduct && optional($r->posProduct->sub_product)->product_no == $productNo
            );

            $alreadyRefundedNormal = (int) $refundsForProduct->filter(fn($r) => is_null($r->posProduct->compo_id))->sum('quantity');

            $alreadyRefundedPerInstance = [];
            $alreadyRefundedPaidPerInstance = [];
            foreach ($refundsForProduct as $r) {
                $pp = $r->posProduct;
                $compoId = $pp->compo_id;
                if ($compoId) {
                    $alreadyRefundedPerInstance[$compoId] = ($alreadyRefundedPerInstance[$compoId] ?? 0) + (int) $r->quantity;
                    if ($pp->price > 0) {
                        $alreadyRefundedPaidPerInstance[$compoId] = ($alreadyRefundedPaidPerInstance[$compoId] ?? 0) + (int) $r->quantity;
                    }
                }
            }

            $remainingNormal = max(0, $normalQty - $alreadyRefundedNormal);

            // available combo instances after already refunded
            $availableComboInstances = [];
            foreach ($comboInstances as $idx => $inst) {
                $compoId = $inst['compo_id'];
                $remCount = max(0, $inst['count'] - ($alreadyRefundedPerInstance[$compoId] ?? 0));
                $remPaid = max(0, $inst['paid'] - ($alreadyRefundedPaidPerInstance[$compoId] ?? 0));
                if ($remCount > 0) {
                    $availableComboInstances[] = [
                        'compo_id' => $compoId,
                        'count' => $remCount,
                        'paid' => $remPaid,
                        'pos_product_ids' => $inst['lines']->pluck('id')->values()->all(),
                    ];
                }
            }

            $wanted = (int) ($refundsInput[$productNo] ?? 0);

            // compute refund possibility
            $check = $computeCanRefund($wanted, $remainingNormal, $availableComboInstances);

            // map used combo indexes to IDs
            $usedCompoIds = [];
            if ($check['allowed'] && !empty($check['used_combos'])) {
                foreach ($check['used_combos'] as $usedIdx) {
                    if (isset($availableComboInstances[$usedIdx])) {
                        $usedCompoIds[] = $availableComboInstances[$usedIdx]['compo_id'];
                    }
                }
            }

            // Get the actual unit price from the POS product
            // Use combo_price if combo exists, otherwise use price after discount
            $firstLine = $lines->first();
            $unitPrice = 0;
            
            if ($firstLine) {
                // Check if there's a combo (compo_id is not null and not 0)
                if (!empty($firstLine->compo_id) && $firstLine->compo_id != '0' && $firstLine->combo_price !== null) {
                    // Use combo price
                    $unitPrice = (float)($firstLine->combo_price ?? 0);
                } else {
                    // Use regular price after discount
                    $basePrice = (float)($firstLine->price ?? 0);
                    $discount = (float)($firstLine->discount ?? 0);
                    
                    // Calculate price after discount: basePrice * (1 - discount/100)
                    if ($discount > 0 && $discount < 100) {
                        $unitPrice = $basePrice * (1 - ($discount / 100));
                    } else {
                        $unitPrice = $basePrice;
                    }
                }
            }
            
            $refundAmountEstimate = $check['refundable_value_qty'] * (float)$unitPrice;
            
            // Log calculation details for debugging
            \Log::info('Refund calculation', [
                'product_no' => $productNo,
                'unit_price' => $unitPrice,
                'combo_price' => $firstLine->combo_price ?? null,
                'regular_price' => $firstLine->price ?? null,
                'discount' => $firstLine->discount ?? null,
                'compo_id' => $firstLine->compo_id ?? null,
                'refundable_value_qty' => $check['refundable_value_qty'],
                'refund_amount_estimate' => $refundAmountEstimate
            ]);
            $posProductIds = $lines->pluck('id')->unique()->values()->all();

            $result[$productNo] = [
                'product_no' => $productNo,
                'pos_product_ids'=> $posProductIds,
                'normal_qty' => $normalQty,
                'combo_instances_total' => count($comboInstances),
                'combo_instances' => array_map(fn($c) => ['compo_id'=>$c['compo_id'],'count'=>$c['count'],'paid'=>$c['paid']], $comboInstances),
                'already_refunded_normal' => $alreadyRefundedNormal,
                'already_refunded_per_instance' => $alreadyRefundedPerInstance,
                'remaining_normal' => $remainingNormal,
                'available_combo_instances' => $availableComboInstances,
                'wanted' => $wanted,
                'allowed' => $check['allowed'],
                'used_normal' => $check['used_normal'],
                'used_compo_ids' => $usedCompoIds,
                'refundable_value_qty' => $check['refundable_value_qty'],
                'refund_amount_estimate' => round($refundAmountEstimate,2),
            ];
        }
        // Get the latest 'vid' entry for General Ledger
        $latestVoucher = GeneralLedger::where('created_by', \Auth::user()->creatorId())->orderBy('vid', 'desc')->first();
        if ($latestVoucher) {
            $lastVid = $latestVoucher->vid;
            $newVid = $lastVid + 1;
        } else {
            $newVid = 1;
        }

        $existingRecord = GeneralLedger::where('vid', $newVid)->where('created_by', \Auth::user()->creatorId())->exists();
        if ($existingRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ]);
        }

        $refundItemsData = []; // Store refund item data for processing
        $totalRefundAmount = 0; // Total amount for single voucher
        
        // Get customer once
        $customer = Customer::where('id', $pos->customer_id)->first();

        // First pass: Calculate refund amounts for each item (price after discount/combo + tax)
        foreach ($result as $productNo => $refundData) {
            if (! $refundData['allowed'] || $refundData['wanted'] <= 0) {
                continue; // skip if not allowed or nothing wanted
            }

            $qtyToRefund = $refundData['wanted'];
            
            // Get the POS product to calculate correct refund price
            $posProduct = PosProduct::find($refundData['pos_product_ids'][0]);
            if (!$posProduct) {
                continue;
            }
            
            // Calculate unit price: use combo_price if combo exists, otherwise price after discount
            $unitPrice = 0;
            if (!empty($posProduct->compo_id) && $posProduct->compo_id != '0' && $posProduct->combo_price !== null) {
                // Use combo price
                $unitPrice = (float)($posProduct->combo_price ?? 0);
            } else {
                // Use regular price after discount
                $basePrice = (float)($posProduct->price ?? 0);
                $discount = (float)($posProduct->discount ?? 0);
                
                // Calculate price after discount: basePrice * (1 - discount/100)
                if ($discount > 0 && $discount < 100) {
                    $unitPrice = $basePrice * (1 - ($discount / 100));
                } else {
                    $unitPrice = $basePrice;
                }
            }
            
            // Calculate price after discount/combo * quantity
            $itemSubtotal = $unitPrice * $qtyToRefund;
            
            // Calculate tax amount on the item subtotal
            $itemTaxAmount = 0;
            if (!empty($pos->tax_id)) {
                $getTaxData = \App\Models\Utility::getTaxData();
                $taxes = explode(',', $pos->tax_id);
                foreach ($taxes as $tax) {
                    if (isset($getTaxData[$tax])) {
                        // Calculate tax on price after discount/combo
                        $itemTaxAmount += \App\Models\Utility::taxRate(
                            $getTaxData[$tax]['rate'],
                            $unitPrice,
                            $qtyToRefund
                        );
                    }
                }
            }
            
            // Total refund amount for this item: (price after discount/combo + tax) * quantity
            $itemTotalRefundAmount = $itemSubtotal + $itemTaxAmount;
            $totalRefundAmount += $itemTotalRefundAmount;
            
            // Store refund item data for second pass
            $refundItemsData[] = [
                'refundData' => $refundData,
                'qtyToRefund' => $qtyToRefund,
                'unitPrice' => $unitPrice,
                'itemSubtotal' => $itemSubtotal,
                'itemTaxAmount' => $itemTaxAmount,
                'itemTotalRefundAmount' => $itemTotalRefundAmount,
                'posProduct' => $posProduct,
            ];
        }

        // Create ONE voucher for all refund items
        $voucher = null;
        if ($totalRefundAmount > 0 && $customer) {
            $voucher = Voucher::create([
                'customer_id' => $pos->customer_id,
                'amount' => round($totalRefundAmount, 2),
                'valid_until' => now()->addMonth(),
                'active' => 1,
                'chart_of_account_id' => $customer->chart_account_id ?? null,
                'created_by' => \Auth::user()->creatorId(),
            ]);
        }

        // Create parent refund record
        $posRefund = \App\Models\PosRefund::create([
            'pos_id' => $pos->id,
            'voucher_id' => $voucher ? $voucher->id : null,
            'total_amount' => round($totalRefundAmount, 2),
            'description' => 'Refund processed',
            'created_by' => \Auth::user()->creatorId(),
        ]);

        // Second pass: Create refund item records (children)
        foreach ($refundItemsData as $itemData) {
            $refundData = $itemData['refundData'];
            $qtyToRefund = $itemData['qtyToRefund'];
            $itemTotalRefundAmount = $itemData['itemTotalRefundAmount'];
            $posProduct = $itemData['posProduct'];
            $product = $posProduct ? \App\Models\ProductService::find($posProduct->product_id) : null;

            // ✅ Create a refund item record
            $refundItem = \App\Models\PosRefundItem::create([
                'refund_id' => $posRefund->id,
                'product_no' => $refundData['product_no'],
                'pos_products_id' => $refundData['pos_product_ids'][0] ?? null,
                'quantity' => $qtyToRefund,
                'return_price' => $itemTotalRefundAmount, // Price after discount/combo + tax
                'combo_id' => $refundData['used_compo_ids'][0] ?? null,
                'price_list_id' => $posProduct->price_list_id ?? null,
            ]);

            // Log POS refund item creation
            PosLog::logAction('create_pos_refund', [
                'type' => 'pos_refund_item',
                'reference_id' => $refundItem->id,
                'pos_id' => $pos->id,
                'warehouse_id' => $pos->warehouse_id,
                'customer_id' => $pos->customer_id,
                'product_id' => $product ? $product->id : null,
                'product_no' => $refundData['product_no'],
                'quantity' => $qtyToRefund,
                'new_value' => [
                    'refund_id' => $posRefund->id,
                    'refund_item_id' => $refundItem->id,
                    'pos_id' => $pos->id,
                    'return_price' => $itemTotalRefundAmount,
                    'voucher_id' => $voucher ? $voucher->id : null,
                ],
                'description' => "Created POS refund item ID {$refundItem->id} for refund ID {$posRefund->id}, POS ID {$pos->id}, product #{$refundData['product_no']}, quantity: {$qtyToRefund}",
            ]);

            // Calculate amounts for different entries
            $itemRefundAmount = $itemData['itemSubtotal']; // Base refund amount without tax
            $refundTaxAmount = $itemData['itemTaxAmount']; // Tax amount calculated above

            // General Ledger entries for refund
            // 1. Credit Customer Account (customer gets money back) - includes tax
            if ($customer) {
                $newEntryCustomer = new GeneralLedger();
                $newEntryCustomer->vid = $newVid;
                $newEntryCustomer->account = $customer->chart_account_id;
                $newEntryCustomer->type = "POS Refund for " . \Auth::user()->posNumberFormat($pos->pos_id);
                $newEntryCustomer->debit = 0;
                $newEntryCustomer->credit = $itemTotalRefundAmount; // Item + tax
                $newEntryCustomer->ref_id = $posRefund->id;
                $newEntryCustomer->user_id = $customer->id;
                $newEntryCustomer->sub_product_id = $posProduct->sub_product_id ?? null;
                $newEntryCustomer->created_by = \Auth::user()->creatorId();
                $newEntryCustomer->balance = $customer->balance;
                $newEntryCustomer->send_date = now();
                $newEntryCustomer->reference = 'POS Refund';
                $newEntryCustomer->save();
            }

            // 2. Debit Category Sale Account (revenue reduction) - item amount only
            if ($product && $product->category_id) {
                $category = \App\Models\ProductServiceCategory::where('id', $product->category_id)->first();
                if ($category && $category->sale_account_id) {
                    $categoryChartAccountId = $category->sale_account_id;
                    $newEntryCategory = new GeneralLedger();
                    $newEntryCategory->vid = $newVid;
                    $newEntryCategory->account = $categoryChartAccountId;
                    $newEntryCategory->type = "POS Refund for " . \Auth::user()->posNumberFormat($pos->pos_id);
                    $newEntryCategory->debit = $itemRefundAmount; // Item amount only
                    $newEntryCategory->credit = 0;
                    $newEntryCategory->ref_id = $posRefund->id;
                    $newEntryCategory->user_id = 0;
                    $newEntryCategory->sub_product_id = $posProduct->sub_product_id ?? null;
                    $newEntryCategory->created_by = \Auth::user()->creatorId();
                    $newEntryCategory->send_date = now();
                    $newEntryCategory->reference = 'POS Refund';
                    $newEntryCategory->save();
                }
            }

            // 3. Tax entry (if POS has tax)
            if (!empty($pos->tax_id) && $refundTaxAmount > 0) {
                // Get tax rate from the original POS
                $tax = \App\Models\Tax::find($pos->tax_id);
                if ($tax) {
                    // Debit Tax Account (tax liability reduction)
                    $newEntryTax = new GeneralLedger();
                    $newEntryTax->vid = $newVid;
                    $newEntryTax->account = $tax->chart_account_id;
                    $newEntryTax->type = "POS Refund for " . \Auth::user()->posNumberFormat($pos->pos_id);
                    $newEntryTax->debit = $refundTaxAmount;
                    $newEntryTax->credit = 0;
                    $newEntryTax->ref_id = $posRefund->id;
                    $newEntryTax->user_id = 0;
                    $newEntryTax->sub_product_id = $posProduct->sub_product_id ?? null;
                    $newEntryTax->created_by = \Auth::user()->creatorId();
                    $newEntryTax->send_date = now();
                    $newEntryTax->reference = 'POS Refund';
                    $newEntryTax->save();
                }
            }

            // 4. If product type is 'product', add inventory entries
            if ($product && $product->type == 'product' && $product->category) {
                // Get purchase price from product
                $purchasePrice = $product->purchase_price ?? 0;
                
                // Calculate purchase amount (purchase price * qty refund)
                $purchaseRefundAmount = $purchasePrice * $qtyToRefund;
                
                // Get account IDs from category
                $purchaseAccountId = $product->category->purchase_account_id ?? null;
                $expenseAccountId = $product->category->expense_account_id ?? null;
                
                if ($purchaseAccountId && $expenseAccountId) {
                    // Credit Purchase Account (inventory increase)
                    $newEntryPurchase = new GeneralLedger();
                    $newEntryPurchase->vid = $newVid;
                    $newEntryPurchase->account = $purchaseAccountId;
                    $newEntryPurchase->type = "POS Refund for " . \Auth::user()->posNumberFormat($pos->pos_id);
                    $newEntryPurchase->debit = $purchaseRefundAmount;
                    $newEntryPurchase->credit = 0; // Purchase price * qty refund
                    $newEntryPurchase->ref_id = $posRefund->id;
                    $newEntryPurchase->user_id = 0;
                    $newEntryPurchase->sub_product_id = $posProduct->sub_product_id ?? null;
                    $newEntryPurchase->created_by = \Auth::user()->creatorId();
                    $newEntryPurchase->send_date = now();
                    $newEntryPurchase->reference = 'POS Refund';
                    $newEntryPurchase->save();

                    // Debit Expense Account (cost of goods sold reduction)
                    $newEntryExpense = new GeneralLedger();
                    $newEntryExpense->vid = $newVid;
                    $newEntryExpense->account = $expenseAccountId;
                    $newEntryExpense->type = "POS Refund for " . \Auth::user()->posNumberFormat($pos->pos_id);
                    $newEntryExpense->debit = 0; // Purchase price * qty refund
                    $newEntryExpense->credit = $purchaseRefundAmount;
                    $newEntryExpense->ref_id = $posRefund->id;
                    $newEntryExpense->user_id = 0;
                    $newEntryExpense->sub_product_id = $posProduct->sub_product_id ?? null;
                    $newEntryExpense->created_by = \Auth::user()->creatorId();
                    $newEntryExpense->send_date = now();
                    $newEntryExpense->reference = 'POS Refund';
                    $newEntryExpense->save();
                }
            }

            // ✅ Return products to stock
            // Find sub-product - use posProduct's sub_product_id for exact match (posProduct is already defined above)
            $subProduct = null;
            if ($posProduct && $posProduct->sub_product_id) {
                $subProduct = SubProduct::where('id', $posProduct->sub_product_id)
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();
            }
            
            // Fallback: Find by product_no if not found via posProduct
            if (!$subProduct) {
                $subProduct = SubProduct::where('chassis_no', $refundData['product_no'])
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();
            }

            if ($subProduct && $product) {
                // Get category to check if it's a Qty product type
                $category = $product->category;

                // Get old quantity before returning (needed for average cost calculation)
                $oldQuantity = $subProduct->quantity;

                // Calculate average cost for stock movement
                $avgCost = 0;
                if ($category && $category->type === "Qty product") {
                    // For Qty product type, calculate average cost from stock movements
                    $oldTotalCost = $oldQuantity * ($product->avg_cost ?? 0);
                    
                    // Get the original stock movement for this sale to get the cost price
                    $originalStockMovement = StockMovement::where('pos_id', $pos->id)
                        ->where('sub_product_id', $subProduct->id)
                        ->where('qty_out', '>', 0)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $returnedPricePerUnit = $originalStockMovement ? ($originalStockMovement->cost_price ?? $subProduct->purchase_price ?? 0) : ($subProduct->purchase_price ?? 0);
                    $returnedItemTotalCost = $qtyToRefund * $returnedPricePerUnit;
                    
                    // Calculate remaining quantity and cost after returning
                    $remainingQuantity = $oldQuantity + $qtyToRefund;
                    $remainingTotalCost = $oldTotalCost + $returnedItemTotalCost;
                    
                    // Calculate average cost from remaining items
                    if ($remainingQuantity > 0) {
                        $avgCost = $remainingTotalCost / $remainingQuantity;
                    } else {
                        $avgCost = 0;
                    }
                } else {
                    // Use actual cost (purchase price from subproduct)
                    $avgCost = $subProduct->purchase_price ?? 0;
                }

                // Return quantity to sub-product
                $subProduct->quantity += $qtyToRefund;
                
                // Remove POS reference from sub-product (set pos_id to null if it matches this POS)
                $wasLinkedToThisPos = ($subProduct->pos_id == $pos->id);
                if ($wasLinkedToThisPos) {
                    $subProduct->pos_id = null;
                }
                
                // Also update booked status if needed (unbook the product when POS reference is removed)
                if ($wasLinkedToThisPos && $subProduct->booked > 0) {
                    // Unbook the product when it's returned to stock
                    $subProduct->booked = 0;
                }
                
                $subProduct->save();

                // Create stock movement record for returning sold quantity
                $stockMovement = new StockMovement();
                $stockMovement->product_id = $product->id;
                $stockMovement->sub_product_id = $subProduct->id;
                $stockMovement->invoice_id = null;
                $stockMovement->bill_id = null;
                $stockMovement->pos_id = $pos->id;
                $stockMovement->qty_in = $qtyToRefund; // Return sold qty
                $stockMovement->qty_out = 0; // No stock out for return
                $stockMovement->avg_cost = $avgCost;
                $stockMovement->cost_price = $subProduct->purchase_price ?? 0;
                $stockMovement->activity = 'Return from POS Refund';
                $stockMovement->use_id = $pos->customer_id; // customer_id for SALES
                $stockMovement->item = $subProduct->id; // sub_product_id
                $stockMovement->created_by = \Auth::user()->creatorId();
                $stockMovement->save();

                // Update product average cost if category type is Qty product
                if ($category && $category->type === "Qty product") {
                    $product->avg_cost = $avgCost;
                    $product->save();
                }
            }

            // ✅ Update status based on remaining quantity (original qty - refunded qty)
            // Don't reduce pos_products.quantity - keep original quantity unchanged
            // Calculate remaining quantity as: original quantity - total refunded quantity
            $posProducts = PosProduct::whereIn('id', $refundData['pos_product_ids'])->get();

            foreach ($posProducts as $pp) {
                // Get total refunded quantity for this pos_product from refund items table
                $totalRefundedQty = \App\Models\PosRefundItem::where('pos_products_id', $pp->id)
                    ->sum('quantity');
                
                // Calculate remaining quantity: original quantity - total refunded
                $remainingQty = $pp->quantity - $totalRefundedQty;
                
                // Update status based on remaining quantity
                if ($remainingQty <= 0) {
                    $pp->status = 'refunded'; // Fully refunded
                } else {
                    $pp->status = 'active'; // Partially refunded or not refunded, still active
                }
                
                // Save status update (quantity remains unchanged)
                $pp->save();
            }
        }

        // Update customer balance once with total refund amount (credit for refund) - includes tax
        if ($customer && $totalRefundAmount > 0) {
            Utility::updateUserBalance('customer', $customer->id, $totalRefundAmount, 'credit');
        }

        // Update POS status based on refunded items
        $this->updatePosStatus($pos);

        return redirect()->route('pos_product_refund.index')->with('success', __('Refund processed successfully'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Print POS refund
     */
    public function print($id)
    {
        if(\Auth::user()->type == 'company' || Auth::user()->can('view pos refund'))
        {
            $refund = \App\Models\PosRefund::with([
                'pos.customer', 
                'items.posProduct.sub_product.productService', 
                'items.posProduct.product', 
                'voucher'
            ])
                ->whereHas('pos', function($query) {
                    $query->where('created_by', Auth::user()->creatorId());
                })
                ->findOrFail($id);
            
            return view('pos_refund.print', compact('refund'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update POS status based on refunded items
     */
    private function updatePosStatus($pos)
    {
        // Get all products for this POS
        $posProducts = PosProduct::where('pos_id', $pos->id)->get();
        
        $totalProducts = $posProducts->count();
        $refundedProducts = $posProducts->where('status', 'refunded')->count();
        $activeProducts = $posProducts->where('status', 'active')->count();
        
        // Determine POS status
        if ($refundedProducts == $totalProducts) {
            // All items are refunded - mark POS as cancelled
            $pos->status = 'cancelled';
        } elseif ($refundedProducts > 0) {
            // Some items are refunded - mark POS as partial refund
            $pos->status = 'partial_refund';
        } else {
            // No items are refunded - keep as active
            $pos->status = 'active';
        }
        
        $pos->save();
    }

    /**
     * Show accounting ledger for refund
     */
    public function refund_ledger($refund_id)
    {
        try {
            if (\Auth::user()->can('ledger report')) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
                $chart_accounts = \App\Models\ChartOfAccount::where('created_by', \Auth::user()->creatorId())->get();
                $accounts = $chart_accounts->pluck('name', 'id');
                $generalLedgerData = \App\Models\GeneralLedger::selectRaw('vid, account, ref_id , type,user_id, SUM(credit) as total_credit, SUM(debit) as total_debit ,created_at,updated_at,send_date,deleted_qty,sub_product_id,user_type')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('ref_id', $refund_id)
                    ->where('reference', 'POS Refund')
                    ->groupBy('vid', 'account')
                    ->orderBy('id', 'ASC')
                    ->get();

                $balance = 0;
                $debit = 0;
                $credit = 0;
                $filter['balance'] = $balance;
                $filter['credit'] = $credit;
                $filter['debit'] = $debit;
                $filter['startDateRange'] = $start;
                $filter['endDateRange'] = $end;
                
                return view('report.general_ledger', compact('filter', 'chart_accounts', 'accounts', 'generalLedgerData'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }
}
