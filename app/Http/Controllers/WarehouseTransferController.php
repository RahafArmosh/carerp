<?php

namespace App\Http\Controllers;

use App\Models\ProductService;
use App\Models\Purchase;
use App\Models\SubProduct;
use App\Models\Utility;
use App\Models\warehouse;
use App\Models\WarehouseProduct;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferRequest;
use App\Models\StockMovement;
use App\Models\PosLog;
use App\Models\ProductServiceCategory;
use App\Models\Brand;
use App\Models\AsnItem;
use App\Models\CustomFieldValue;
use App\Imports\WarehouseTransferImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Illuminate\Support\Facades\DB;

class WarehouseTransferController extends Controller
{

    public function index()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view transfer'))
        {
            $user = \Auth::user();
            $companyUserIds = $this->getCompanyUserIds($user);
            $requestsQuery = WarehouseTransferRequest::whereIn('created_by', $companyUserIds)
                ->with(['fromWarehouse', 'toWarehouse', 'transfers.product', 'transfers.fromWarehouse', 'transfers.toWarehouse', 'creator']);

            if ($user->type !== 'company') {
                $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
                $userId = (int) $user->id;

                $requestsQuery->where(function ($q) use ($userWarehouseId, $userId) {
                    $q->whereHas('transfers', function ($tq) use ($userId) {
                        $tq->where('created_by', $userId);
                    });

                    if ($userWarehouseId) {
                        $q->orWhere('to_warehouse', $userWarehouseId);
                    }
                });
            }

            // Get all transfer requests with their transfers (paginated)
            $requests = $requestsQuery
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            // Also get transfers without requests (for backward compatibility)
            // Limit to a reasonable number to avoid rendering thousands of rows
            // which can make the Warehouse Transfer index page unresponsive.
            $transfersWithoutRequestQuery = WarehouseTransfer::query()
                ->whereNull('request_id')
                ->with(['product','fromWarehouse','toWarehouse']);

            if ($user->type === 'company') {
                $transfersWithoutRequestQuery->whereIn('created_by', $companyUserIds);
            } else {
                $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
                $userId = (int) $user->id;
                $transfersWithoutRequestQuery->where(function ($q) use ($userWarehouseId, $userId) {
                    $q->where('created_by', $userId);
                    if ($userWarehouseId) {
                        $q->orWhere('to_warehouse', $userWarehouseId);
                    }
                });
            }

            $transfersWithoutRequest = $transfersWithoutRequestQuery
                ->orderBy('date', 'desc')
                ->limit(200)
                ->get();
            
            return view('warehouse-transfer.index', compact('requests', 'transfersWithoutRequest'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create transfer'))
        {
            $user = \Auth::user();
            $from_warehouses = warehouse::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
            $to_warehouses = warehouse::where('created_by', $user->creatorId())->get()->pluck('name', 'id');

            if ($user->type !== 'company') {
                $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
                if (!$userWarehouseId) {
                    return redirect()->back()->with('error', __('No warehouse assigned to your user.'));
                }

                $from_warehouses = warehouse::where('id', $userWarehouseId)->pluck('name', 'id');
            }

            $to_warehouses->prepend('Select Warehouse', '');
            $ware_pro= WarehouseProduct::join('product_services', 'warehouse_products.product_id', '=', 'product_services.id')
                                    ->where('warehouse_products.created_by', '=', $user->creatorId())
                                    ->pluck('name','product_id');
            $ware_pro->prepend('Select products', '');

            return view('warehouse-transfer.create',compact('from_warehouses','to_warehouses','ware_pro'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('create transfer'))
        {
            $user = \Auth::user();
            // Filter out zero quantities before validation
            $quantities = $request->input('quantities', []);
            $quantities = array_filter($quantities, function($qty) {
                return !empty($qty) && (int)$qty > 0;
            });
            
            // Merge filtered quantities back to request
            $request->merge(['quantities' => $quantities]);
            
             $validated = $request->validate([
                'from_warehouse' => ['required', 'different:to_warehouse', 'exists:warehouses,id'],
                'to_warehouse'   => ['required', 'different:from_warehouse', 'exists:warehouses,id'],
                'quantities'     => ['required', 'array', 'min:1'],
                'quantities.*'   => ['required', 'integer', 'min:1'], // each qty must be ≥1
                'date'           => ['required', 'date'],
                'notes'          => ['nullable', 'string', 'max:1000'],
            ], [
                // Custom messages
                'from_warehouse.required' => __('Please select a source warehouse.'),
                'to_warehouse.required'   => __('Please select a destination warehouse.'),
                'to_warehouse.different'  => __('From and To warehouse cannot be the same.'),
                'quantities.required'     => __('Please select at least one product and enter quantities.'),
                'quantities.min'          => __('Please select at least one product and enter quantities.'),
                'quantities.*.required'   => __('Each selected product must have a quantity.'),
                'quantities.*.min'        => __('Each product must have a quantity greater than 0.'),
            ]);

            if ($user->type !== 'company') {
                $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
                if (!$userWarehouseId) {
                    return redirect()->back()->with('error', __('No warehouse assigned to your user.'))->withInput();
                }

                if ((int) $validated['from_warehouse'] !== (int) $userWarehouseId) {
                    return redirect()->back()->with('error', __('You can only transfer from your assigned warehouse.'))->withInput();
                }
            }
            
            $From_warehouse = warehouse::where('id', $validated['from_warehouse'])->first();
            
            // Validate stock availability
            foreach ($validated['quantities'] as $productNo => $quan) {
                if ($From_warehouse->GetFreeQuantity($productNo) < $quan) {
                    return redirect()->back()->with('error', __('Not enough stock available for product :product_no. Available: :available, Required: :required', [
                        'product_no' => $productNo,
                        'available' => $From_warehouse->GetFreeQuantity($productNo),
                        'required' => $quan
                    ]))->withInput();
                }
            }
            
            $actorUserId = (int) \Auth::id();

            DB::transaction(function () use ($validated, $actorUserId) {
                // Create transfer request
                $transferRequest = WarehouseTransferRequest::create([
                    'request_number' => WarehouseTransferRequest::generateRequestNumber(),
                    'from_warehouse' => $validated['from_warehouse'],
                    'to_warehouse'   => $validated['to_warehouse'],
                    'request_date'   => $validated['date'],
                    'status'         => 'draft',
                    'notes'          => $validated['notes'] ?? null,
                    'created_by'     => $actorUserId,
                ]);

                // Log request creation
                PosLog::logAction('create_transfer_request', [
                    'type' => 'transfer_request',
                    'reference_id' => $transferRequest->id,
                    'warehouse_id' => $validated['from_warehouse'],
                    'new_value' => [
                        'request_id' => $transferRequest->id,
                        'request_number' => $transferRequest->request_number,
                        'from_warehouse' => $validated['from_warehouse'],
                        'to_warehouse' => $validated['to_warehouse'],
                        'status' => 'draft',
                    ],
                    'description' => "Warehouse transfer request created: {$transferRequest->request_number}",
                ]);

                // Create transfer items
                foreach ($validated['quantities'] as $productNo => $quan) {
                    $transferQty = $quan ?? 0;
                    if ($transferQty <= 0) continue;

                    // Get product ID from subproducts
                    $subProduct = SubProduct::where('chassis_no', $productNo)
                        ->where('warehouse_id', $validated['from_warehouse'])
                        ->where('quantity', '>', 0)
                        ->first();

                    $productId = $subProduct->product_id ?? null;

                    // Create transfer record as DRAFT (stock not moved yet)
                    $transfer = WarehouseTransfer::create([
                        'request_id'     => $transferRequest->id,
                        'from_warehouse' => $validated['from_warehouse'],
                        'to_warehouse'   => $validated['to_warehouse'],
                        'product_id'     => $productId,
                        'product_no'     => $productNo,
                        'quantity'       => $transferQty,
                        'date'           => $validated['date'],
                        'status'         => 'draft',
                        'created_by'     => $actorUserId,
                    ]);
                    
                    // Log transfer creation
                    PosLog::logAction('create_transfer', [
                        'type' => 'transfer',
                        'reference_id' => $transfer->id,
                        'warehouse_id' => $validated['from_warehouse'],
                        'product_id' => $productId,
                        'product_no' => $productNo,
                        'quantity' => $transferQty,
                        'new_value' => [
                            'transfer_id' => $transfer->id,
                            'request_id' => $transferRequest->id,
                            'from_warehouse' => $validated['from_warehouse'],
                            'to_warehouse' => $validated['to_warehouse'],
                            'date' => $validated['date'],
                            'status' => 'draft',
                        ],
                        'description' => "Transfer item added to request {$transferRequest->request_number}: {$transferQty} units of product #{$productNo}",
                    ]);
                }
            });

            return redirect()->route('warehouse-transfer-request.index')->with('success', __('Warehouse Transfer Request successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Approve all draft transfers at once
     */
    public function approveAll(Request $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer'))
        {
            // Get all draft transfers for this user
            $draftTransfers = WarehouseTransfer::where('status', 'draft')
                ->where('created_by', \Auth::user()->creatorId())
                ->get();

            if ($draftTransfers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No draft transfers found to approve.')
                ], 404);
            }

            if (\Auth::user()->type !== 'company') {
                return response()->json([
                    'success' => false,
                    'message' => __('Please approve each transfer from the transfer request page and upload an attachment first.'),
                ], 403);
            }

            $approvedCount = 0;
            $failedCount = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($draftTransfers as $transfer) {
                    try {
                        $productNo = $transfer->product_no;
                        $transferQty = $transfer->quantity;

                        // Fetch stock batches in from_warehouse (FIFO: oldest first)
                        $subProducts = SubProduct::where('chassis_no', $productNo)
                            ->where('warehouse_id', $transfer->from_warehouse)
                            ->where('quantity', '>', 0)
                            ->with('customFieldValues')
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();

                        $totalTransferred = 0;

                        foreach ($subProducts as $sub) {
                            if ($transferQty <= 0) break;

                            $available = $sub->quantity;
                            $deduct = min($available, $transferQty);

                            // Ensure customFieldValues relationship is loaded
                            if (!$sub->relationLoaded('customFieldValues')) {
                                $sub->load('customFieldValues');
                            }

                            // Reduce from source warehouse
                            $sub->decrement('quantity', $deduct);

                            // Add record in destination warehouse
                            $newSubProduct = SubProduct::create([
                                'product_no'      => $sub->product_no,
                                'product_id'      => $sub->product_id,
                                'sale_price'      => $sub->sale_price,
                                'purchase_price'  => $sub->purchase_price,
                                'created_by'      => \Auth::user()->creatorId(),
                                'bill_id'         => $sub->bill_id,
                                'asn_id'          => $sub->asn_id,
                                'invoice_id'      => $sub->invoice_id,
                                'pos_id'          => $sub->pos_id,
                                'quantity'        => $deduct,
                                'SP_sku'          => $sub->SP_sku,
                                'warehouse_id'    => $transfer->to_warehouse,
                                'booked'          => $sub->booked,
                                'flag'          => $sub->flag,
                                'price_rule_id'   => $sub->price_rule_id,
                            ]);

                            // Copy custom fields from the original sub-product to the new sub-product
                            if ($sub->customFieldValues && $sub->customFieldValues->isNotEmpty()) {
                                foreach ($sub->customFieldValues as $customFieldValue) {
                                    CustomFieldValue::create([
                                        'record_id' => $newSubProduct->id,
                                        'field_id' => $customFieldValue->field_id,
                                        'value' => $customFieldValue->value,
                                    ]);
                                }
                            }

                            // If source sub-product is ASN-linked, split ASN item qty and attach to destination sub-product.
                            $this->splitAsnItemForTransfer($sub, $newSubProduct, (float) $deduct);

                            // If the original sub-product has a bill_id, add the new sub-product to the bill
                            if ($sub->bill_id) {
                                $originalBillProduct = \App\Models\BillProduct::where('bill_id', $sub->bill_id)
                                    ->where('sub_product_id', $sub->id)
                                    ->first();
                                
                                if ($originalBillProduct) {
                                    \App\Models\BillProduct::create([
                                        'bill_id'         => $sub->bill_id,
                                        'product_id'      => $sub->product_id,
                                        'sub_product_id'  => $newSubProduct->id,
                                        'quantity'        => $deduct,
                                        'tax'             => $originalBillProduct->tax,
                                        'discount'        => $originalBillProduct->discount,
                                        'price'           => $originalBillProduct->price,
                                        'exchange_price'  => $originalBillProduct->exchange_price,
                                        'exchange_discount' => $originalBillProduct->exchange_discount,
                                        'chart_account_id' => $originalBillProduct->chart_account_id,
                                        'total'           => $originalBillProduct->price * $deduct,
                                    ]);
                                }
                            }

                            // If the original sub-product has an invoice_id, add the new sub-product to the invoice
                            if ($sub->invoice_id) {
                                $originalInvoiceProduct = \App\Models\InvoiceProduct::where('invoice_id', $sub->invoice_id)
                                    ->where('sub_product_id', $sub->id)
                                    ->first();
                                
                                if ($originalInvoiceProduct) {
                                    \App\Models\InvoiceProduct::create([
                                        'invoice_id'      => $sub->invoice_id,
                                        'product_id'      => $sub->product_id,
                                        'sub_product_id'  => $newSubProduct->id,
                                        'quantity'        => $deduct,
                                        'tax'             => $originalInvoiceProduct->tax,
                                        'discount'        => $originalInvoiceProduct->discount,
                                        'price'           => $originalInvoiceProduct->price,
                                        'exchange_price'  => $originalInvoiceProduct->exchange_price,
                                        'exchange_discount' => $originalInvoiceProduct->exchange_discount,
                                        'description'     => $originalInvoiceProduct->description,
                                    ]);
                                }
                            }

                            $this->recordWarehouseTransferStockMovements(
                                $transfer,
                                $sub,
                                $newSubProduct,
                                $deduct,
                                \Auth::user()->creatorId()
                            );

                            $transferQty -= $deduct;
                            $totalTransferred += $deduct;
                        }

                        if ($transferQty > 0) {
                            throw new \Exception("Not enough stock available for product {$productNo} in warehouse {$transfer->from_warehouse}");
                        }

                        // Update transfer status to approved
                        $transfer->status = 'approved';
                        $transfer->save();

                        // Log transfer approval
                        PosLog::logAction('approve_transfer', [
                            'type' => 'transfer',
                            'reference_id' => $transfer->id,
                            'warehouse_id' => $transfer->from_warehouse,
                            'product_id' => $transfer->product_id,
                            'product_no' => $productNo,
                            'quantity' => $totalTransferred,
                            'old_value' => [
                                'status' => 'draft',
                            ],
                            'new_value' => [
                                'status' => 'approved',
                                'transfer_id' => $transfer->id,
                                'from_warehouse' => $transfer->from_warehouse,
                                'to_warehouse' => $transfer->to_warehouse,
                            ],
                            'description' => "Warehouse transfer APPROVED (bulk): {$totalTransferred} units of product #{$productNo} moved from warehouse {$transfer->from_warehouse} to {$transfer->to_warehouse}",
                        ]);

                        $approvedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = "Transfer #{$transfer->id}: " . $e->getMessage();
                        \Log::error('Bulk approve transfer failed', [
                            'transfer_id' => $transfer->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                DB::commit();

                if ($failedCount > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Approved :approved transfers. :failed transfers failed.', ['approved' => $approvedCount, 'failed' => $failedCount]),
                        'approved' => $approvedCount,
                        'failed' => $failedCount,
                        'errors' => $errors
                    ], 207); // 207 Multi-Status
                }

                return response()->json([
                    'success' => true,
                    'message' => __('Successfully approved :count transfer(s).', ['count' => $approvedCount]),
                    'approved' => $approvedCount
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Bulk approve transfers failed', [
                    'error' => $e->getMessage(),
                    'user_id' => \Auth::user()->creatorId()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to approve transfers: :error', ['error' => $e->getMessage()])
                ], 500);
            }
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => __('Permission denied.')
            ], 403);
        }
    }

    public function show()
    {
        return redirect()->route('warehouse-transfer.index');
    }

    /**
     * List all transfer requests
     */
    public function requestIndex()
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view transfer'))
        {
            $user = \Auth::user();
            $companyUserIds = $this->getCompanyUserIds($user);
            $requestsQuery = WarehouseTransferRequest::whereIn('created_by', $companyUserIds)
                ->with(['fromWarehouse', 'toWarehouse', 'transfers'])
                ->orderBy('created_at', 'desc');

            if ($user->type !== 'company') {
                $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
                $userId = (int) $user->id;

                $requestsQuery->where(function ($q) use ($userWarehouseId, $userId) {
                    $q->whereHas('transfers', function ($tq) use ($userId) {
                        $tq->where('created_by', $userId);
                    });

                    if ($userWarehouseId) {
                        $q->orWhere('to_warehouse', $userWarehouseId);
                    }
                });
            }

            $requests = $requestsQuery->paginate(20);
            
            return view('warehouse-transfer-request.index', compact('requests'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Delete a warehouse transfer request (and its draft transfers)
     */
    public function destroyRequest(WarehouseTransferRequest $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete transfer'))
        {
            if ($request->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Only allow deletion of draft or pending requests
            if (!in_array($request->status, ['draft', 'pending'])) {
                return redirect()->back()->with('error', __('Only draft or pending requests can be deleted.'));
            }

            try {
                DB::transaction(function () use ($request) {
                    $request->load('transfers');

                    // Ensure all transfers are still in draft status before deletion
                    foreach ($request->transfers as $transfer) {
                        if ($transfer->status !== 'draft') {
                            throw new \Exception(__('Cannot delete request that contains non-draft transfers.'));
                        }
                    }

                    // Log and delete all draft transfers under this request
                    foreach ($request->transfers as $transfer) {
                        PosLog::logAction('delete_transfer', [
                            'type' => 'transfer',
                            'reference_id' => $transfer->id,
                            'warehouse_id' => $transfer->from_warehouse,
                            'product_id' => $transfer->product_id,
                            'product_no' => $transfer->product_no,
                            'quantity' => $transfer->quantity,
                            'old_value' => [
                                'transfer_id' => $transfer->id,
                                'request_id' => $request->id,
                                'from_warehouse' => $transfer->from_warehouse,
                                'to_warehouse' => $transfer->to_warehouse,
                                'date' => $transfer->date,
                                'status' => $transfer->status,
                            ],
                            'description' => __("Draft warehouse transfer deleted as part of deleting request :request_number", [
                                'request_number' => $request->request_number,
                            ]),
                        ]);

                        $transfer->delete();
                    }

                    // Log request deletion
                    PosLog::logAction('delete_transfer_request', [
                        'type' => 'transfer_request',
                        'reference_id' => $request->id,
                        'warehouse_id' => $request->from_warehouse,
                        'old_value' => [
                            'request_id' => $request->id,
                            'request_number' => $request->request_number,
                            'from_warehouse' => $request->from_warehouse,
                            'to_warehouse' => $request->to_warehouse,
                            'request_date' => $request->request_date,
                            'status' => $request->status,
                        ],
                        'description' => "Warehouse transfer request deleted: {$request->request_number}",
                    ]);

                    $request->delete();
                });

                return redirect()->route('warehouse-transfer-request.index')->with('success', __('Warehouse transfer request deleted successfully.'));
            } catch (\Exception $e) {
                \Log::error('Failed to delete warehouse transfer request', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'user_id' => \Auth::user()->id,
                ]);

                return redirect()->back()->with('error', __('Failed to delete warehouse transfer request: :message', [
                    'message' => $e->getMessage(),
                ]));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show transfer request details
     */
    public function showRequest(WarehouseTransferRequest $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view transfer'))
        {
            if (!$this->canAccessTransferRequest(\Auth::user(), $request)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $request->load(['fromWarehouse', 'toWarehouse', 'transfers.product', 'transfers.fromWarehouse', 'transfers.toWarehouse', 'creator', 'approver']);
            
            // Get logs for this request
            $transferIds = $request->transfers->pluck('id')->toArray();
            $logs = PosLog::where(function($query) use ($request, $transferIds) {
                    $query->where(function($q) use ($request) {
                        $q->where('type', 'transfer_request')
                          ->where('reference_id', $request->id);
                    });
                    if (!empty($transferIds)) {
                        $query->orWhere(function($q) use ($transferIds) {
                            $q->where('type', 'transfer')
                              ->whereIn('reference_id', $transferIds);
                        });
                    }
                })
                ->with(['user:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            $canApproveRequest = $this->canUserApproveTransferRequest(\Auth::user(), $request);
            return view('warehouse-transfer-request.show', compact('request', 'logs', 'canApproveRequest'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Print-ready transfer request form
     */
    public function printRequest(WarehouseTransferRequest $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('view transfer'))
        {
            if (!$this->canAccessTransferRequest(\Auth::user(), $request)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $request->load(['fromWarehouse', 'toWarehouse', 'transfers.product', 'creator']);

            return view('warehouse-transfer-request.print', compact('request'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    private function isPosTransferUser($user): bool
    {
        if (!$user) {
            return false;
        }

        if (\in_array((string) $user->type, ['pos', 'cashier'], true)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('pos')
                || $user->hasRole('POS')
                || $user->hasRole('cashier')
                || $user->hasRole('Cashier');
        }

        return false;
    }

    private function getUserPrimaryWarehouseId($user): ?int
    {
        $warehouseId = (int) DB::table('user_warehouses')
            ->where('user_id', $user->id)
            ->orderBy('id', 'asc')
            ->value('warehouse_id');

        return $warehouseId > 0 ? $warehouseId : null;
    }

    private function getMainWarehouseId($user): ?int
    {
        $mainWarehouseId = (int) warehouse::where('created_by', $user->creatorId())
            ->orderBy('id', 'asc')
            ->value('id');

        return $mainWarehouseId > 0 ? $mainWarehouseId : null;
    }

    /**
     * Company-scoped user IDs used for created_by filtering.
     * created_by is now stored as the actual actor user id.
     */
    private function getCompanyUserIds($user): array
    {
        $creatorId = (int) $user->creatorId();
        $ids = DB::table('users')
            ->where('created_by', $creatorId)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        // Ensure the company/creator user id is included as well.
        $ids[] = $creatorId;

        return array_values(array_unique(array_filter($ids)));
    }

    private function canAccessTransferRequest($user, WarehouseTransferRequest $request): bool
    {
        // created_by is stored as the actor user id.
        // For company users: allow access to any request created by a user in their company.
        if ($user->type === 'company') {
            return in_array((int) $request->created_by, $this->getCompanyUserIds($user), true);
        }

        // For non-company users: allow viewing requests they created, or requests sent to their warehouse,
        // or requests that contain at least one transfer line created by them.
        $userId = (int) $user->id;
        if ((int) $request->created_by === $userId) {
            return true;
        }

        $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
        if ($userWarehouseId && (int) $request->to_warehouse === (int) $userWarehouseId) {
            return true;
        }

        return $request->transfers()
            ->where('created_by', $userId)
            ->exists();
    }

    private function canUserApproveTransferRequest($user, WarehouseTransferRequest $request): bool
    {
        if ($user->type === 'company') {
            return in_array((int) $request->created_by, $this->getCompanyUserIds($user), true);
        }

        $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
        if (!$userWarehouseId) {
            return false;
        }

        return (int) $request->to_warehouse === (int) $userWarehouseId;
    }

    private function canUserApproveTransfer($user, WarehouseTransfer $transfer): bool
    {
        if ($user->type === 'company') {
            return in_array((int) $transfer->created_by, $this->getCompanyUserIds($user), true);
        }

        $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
        if (!$userWarehouseId) {
            return false;
        }

        return (int) $transfer->to_warehouse === (int) $userWarehouseId;
    }

    /**
     * Upload or replace transfer request attachment
     */
    public function uploadRequestAttachment(Request $httpRequest, WarehouseTransferRequest $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer'))
        {
            if (!$this->canAccessTransferRequest(\Auth::user(), $request)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            if (!in_array($request->status, ['draft', 'pending'])) {
                return redirect()->back()->with('error', __('Attachment can only be updated for draft or pending requests.'));
            }

            $validated = $httpRequest->validate([
                'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt|max:10240',
            ]);

            $file = $validated['attachment'];
            $uploadDir = public_path('uploads/warehouse_transfer_attachments');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = time() . '_' . $request->id . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());
            $file->move($uploadDir, $newFileName);

            if (!empty($request->attachment)) {
                $oldPath = public_path($request->attachment);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $request->attachment = 'uploads/warehouse_transfer_attachments/' . $newFileName;
            $request->save();

            PosLog::logAction('update_transfer_request_attachment', [
                'type' => 'transfer_request',
                'reference_id' => $request->id,
                'warehouse_id' => $request->from_warehouse,
                'description' => "Attachment updated for transfer request {$request->request_number}",
            ]);

            return redirect()->back()->with('success', __('Attachment uploaded successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update transfer quantity
     */
    public function updateTransferQuantity(Request $request, WarehouseTransfer $transfer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer'))
        {
            if ($transfer->created_by != \Auth::user()->creatorId()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Only allow editing if request is draft or pending
            if ($transfer->request && !in_array($transfer->request->status, ['draft', 'pending'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => __('Cannot edit transfer that is already approved or rejected.')], 400);
                }
                return redirect()->back()->with('error', __('Cannot edit transfer that is already approved or rejected.'));
            }

            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            $oldQuantity = $transfer->quantity;
            $newQuantity = $validated['quantity'];

            // Check stock availability
            $availableStock = SubProduct::where('chassis_no', $transfer->product_no)
                ->where('warehouse_id', $transfer->from_warehouse)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            if ($availableStock < $newQuantity) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false, 
                        'message' => __('Not enough stock available. Available: :available, Required: :required', [
                            'available' => $availableStock,
                            'required' => $newQuantity
                        ])
                    ], 400);
                }
                return redirect()->back()->with('error', __('Not enough stock available. Available: :available, Required: :required', [
                    'available' => $availableStock,
                    'required' => $newQuantity
                ]));
            }

            $transfer->quantity = $newQuantity;
            $transfer->save();

            // Log quantity update
            PosLog::logAction('update_transfer_quantity', [
                'type' => 'transfer',
                'reference_id' => $transfer->id,
                'warehouse_id' => $transfer->from_warehouse,
                'product_id' => $transfer->product_id,
                'product_no' => $transfer->product_no,
                'quantity' => $newQuantity,
                'old_value' => ['quantity' => $oldQuantity],
                'new_value' => ['quantity' => $newQuantity],
                'description' => "Transfer quantity updated from {$oldQuantity} to {$newQuantity} for product #{$transfer->product_no}",
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Transfer quantity updated successfully.'),
                    'quantity' => $newQuantity
                ]);
            }

            return redirect()->back()->with('success', __('Transfer quantity updated successfully.'));
        }
        else
        {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Approve transfer request
     */
    public function approveRequest(WarehouseTransferRequest $request)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer'))
        {
            if (!$this->canUserApproveTransferRequest(\Auth::user(), $request)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            if ($request->status !== 'draft' && $request->status !== 'pending') {
                return redirect()->back()->with('error', __('Only draft or pending requests can be approved.'));
            }

            $transfers = $request->transfers()->where('status', 'draft')->get();

            if ($transfers->isEmpty()) {
                return redirect()->back()->with('error', __('No draft transfers found in this request.'));
            }

            $attachmentRedirect = $this->redirectIfNonCompanyMissingTransferAttachment($request);
            if ($attachmentRedirect !== null) {
                return $attachmentRedirect;
            }

            try {
                DB::transaction(function () use ($request, $transfers) {
                    foreach ($transfers as $transfer) {
                        $productNo = $transfer->product_no;
                        $transferQty = $transfer->quantity;

                        // Check stock availability
                        $availableStock = SubProduct::where('chassis_no', $productNo)
                            ->where('warehouse_id', $transfer->from_warehouse)
                            ->where('quantity', '>', 0)
                            ->sum('quantity');

                        if ($availableStock < $transferQty) {
                            throw new \Exception("Not enough stock available for product {$productNo} in warehouse {$transfer->from_warehouse}. Available: {$availableStock}, Required: {$transferQty}");
                        }

                        // Fetch stock batches in from_warehouse (FIFO: oldest first)
                        $subProducts = SubProduct::where('chassis_no', $productNo)
                            ->where('warehouse_id', $transfer->from_warehouse)
                            ->where('quantity', '>', 0)
                            ->with('customFieldValues')
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();

                        $totalTransferred = 0;

                        foreach ($subProducts as $sub) {
                            if ($transferQty <= 0) break;

                            $available = $sub->quantity;
                            $deduct = min($available, $transferQty);

                            // Ensure customFieldValues relationship is loaded
                            if (!$sub->relationLoaded('customFieldValues')) {
                                $sub->load('customFieldValues');
                            }

                            // Reduce from source warehouse
                            $sub->decrement('quantity', $deduct);

                            // Add record in destination warehouse
                            $newSubProduct = SubProduct::create([
                                'product_no'      => $sub->product_no,
                                'product_id'      => $sub->product_id,
                                'sale_price'      => $sub->sale_price,
                                'purchase_price'  => $sub->purchase_price,
                                'created_by'      => \Auth::user()->creatorId(),
                                'bill_id'         => $sub->bill_id,
                                'asn_id'          => $sub->asn_id,
                                'invoice_id'      => $sub->invoice_id,
                                'pos_id'          => $sub->pos_id,
                                'quantity'        => $deduct,
                                'SP_sku'          => $sub->SP_sku,
                                'warehouse_id'    => $transfer->to_warehouse,
                                'booked'          => $sub->booked,
                                'flag'            => $sub->flag,
                                'price_rule_id'   => $sub->price_rule_id,
                            ]);

                            // Copy custom fields from the original sub-product to the new sub-product
                            if ($sub->customFieldValues && $sub->customFieldValues->isNotEmpty()) {
                                foreach ($sub->customFieldValues as $customFieldValue) {
                                    CustomFieldValue::create([
                                        'record_id' => $newSubProduct->id,
                                        'field_id' => $customFieldValue->field_id,
                                        'value' => $customFieldValue->value,
                                    ]);
                                }
                            }

                            // If source sub-product is ASN-linked, split ASN item qty and attach to destination sub-product.
                            $this->splitAsnItemForTransfer($sub, $newSubProduct, (float) $deduct);

                            $this->recordWarehouseTransferStockMovements(
                                $transfer,
                                $sub,
                                $newSubProduct,
                                $deduct,
                                \Auth::user()->creatorId()
                            );

                            $transferQty -= $deduct;
                            $totalTransferred += $deduct;
                        }

                        // Update transfer status to approved
                        $transfer->status = 'approved';
                        $transfer->save();

                        // Log transfer approval
                        PosLog::logAction('approve_transfer', [
                            'type' => 'transfer',
                            'reference_id' => $transfer->id,
                            'warehouse_id' => $transfer->from_warehouse,
                            'product_id' => $transfer->product_id,
                            'product_no' => $productNo,
                            'quantity' => $totalTransferred,
                            'old_value' => ['status' => 'draft'],
                            'new_value' => [
                                'status' => 'approved',
                                'transfer_id' => $transfer->id,
                                'from_warehouse' => $transfer->from_warehouse,
                                'to_warehouse' => $transfer->to_warehouse,
                            ],
                            'description' => "Transfer approved: {$totalTransferred} units of product #{$productNo} moved from warehouse {$transfer->from_warehouse} to {$transfer->to_warehouse}",
                        ]);
                    }

                    // Update request status
                    $request->status = 'approved';
                    $request->approved_by = \Auth::user()->id;
                    $request->approved_at = now();
                    $request->save();

                    // Log request approval
                    PosLog::logAction('approve_transfer_request', [
                        'type' => 'transfer_request',
                        'reference_id' => $request->id,
                        'warehouse_id' => $request->from_warehouse,
                        'old_value' => ['status' => $request->getOriginal('status')],
                        'new_value' => [
                            'status' => 'approved',
                            'request_id' => $request->id,
                            'request_number' => $request->request_number,
                            'approved_by' => \Auth::user()->id,
                            'approved_at' => $request->approved_at,
                        ],
                        'description' => "Transfer request {$request->request_number} approved",
                    ]);
                });
            } catch (\Throwable $e) {
                \Log::error('Transfer request approval failed', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'user_id' => \Auth::id(),
                ]);

                return redirect()->back()->with('error', __($e->getMessage()));
            }

            return redirect()->route('warehouse-transfer-request.show', $request->id)->with('success', __('Transfer request approved and stock moved successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Approve a draft transfer (actually move the stock)
     */
    public function approve(WarehouseTransfer $warehouseTransfer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('edit transfer'))
        {
            if ($warehouseTransfer->status !== 'draft') {
                return redirect()->back()->with('error', __('Only draft transfers can be approved.'));
            }

            if (!$this->canUserApproveTransfer(\Auth::user(), $warehouseTransfer)) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            if (\Auth::user()->type !== 'company' && $warehouseTransfer->request_id) {
                $parentRequest = WarehouseTransferRequest::find($warehouseTransfer->request_id);
                if (!$parentRequest || empty(trim((string) $parentRequest->attachment))) {
                    return redirect()->back()->with('error', __('Please upload an attachment on the transfer request before approving.'));
                }
            }

            $productNo = $warehouseTransfer->product_no;
            $transferQty = $warehouseTransfer->quantity;

            // Check stock availability before processing
            $availableStock = SubProduct::where('chassis_no', $productNo)
                ->where('warehouse_id', $warehouseTransfer->from_warehouse)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            if ($availableStock < $transferQty) {
                $fromWarehouse = warehouse::find($warehouseTransfer->from_warehouse);
                $warehouseName = $fromWarehouse ? $fromWarehouse->name : __('Warehouse') . ' #' . $warehouseTransfer->from_warehouse;
                
                return redirect()->back()->with('error', __('Not enough stock available for product :product_no in warehouse :warehouse. Available: :available, Required: :required', [
                    'product_no' => $productNo,
                    'warehouse' => $warehouseName,
                    'available' => $availableStock,
                    'required' => $transferQty
                ]));
            }

            DB::transaction(function () use ($warehouseTransfer) {
                $productNo = $warehouseTransfer->product_no;
                $transferQty = $warehouseTransfer->quantity;

                // Fetch stock batches in from_warehouse (FIFO: oldest first)
                $subProducts = SubProduct::where('chassis_no', $productNo)
                    ->where('warehouse_id', $warehouseTransfer->from_warehouse)
                    ->where('quantity', '>', 0)
                    ->with('customFieldValues')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $totalTransferred = 0;

                foreach ($subProducts as $sub) {
                    if ($transferQty <= 0) break;

                    $available = $sub->quantity;
                    $deduct = min($available, $transferQty);

                    // Ensure customFieldValues relationship is loaded
                    if (!$sub->relationLoaded('customFieldValues')) {
                        $sub->load('customFieldValues');
                    }

                    // Reduce from source warehouse
                    $sub->decrement('quantity', $deduct);

                    // Add record in destination warehouse
                    $newSubProduct = SubProduct::create([
                        'product_no'      => $sub->product_no,
                        'product_id'      => $sub->product_id,
                        'sale_price'      => $sub->sale_price,
                        'purchase_price'  => $sub->purchase_price,
                        'created_by'      => \Auth::user()->creatorId(),
                        'bill_id'         => $sub->bill_id,
                        'asn_id'          => $sub->asn_id,
                        'invoice_id'      => $sub->invoice_id,
                        'pos_id'          => $sub->pos_id,
                        'quantity'        => $deduct,
                        'SP_sku'          => $sub->SP_sku,
                        'warehouse_id'    => $warehouseTransfer->to_warehouse,
                        'booked'          => $sub->booked,
                        'flag'            => $sub->flag,
                        'price_rule_id'   => $sub->price_rule_id,
                    ]);

                    // Copy custom fields from the original sub-product to the new sub-product
                    if ($sub->customFieldValues && $sub->customFieldValues->isNotEmpty()) {
                        foreach ($sub->customFieldValues as $customFieldValue) {
                            CustomFieldValue::create([
                                'record_id' => $newSubProduct->id,
                                'field_id' => $customFieldValue->field_id,
                                'value' => $customFieldValue->value,
                            ]);
                        }
                    }

                    // If source sub-product is ASN-linked, split ASN item qty and attach to destination sub-product.
                    $this->splitAsnItemForTransfer($sub, $newSubProduct, (float) $deduct);

                    $this->recordWarehouseTransferStockMovements(
                        $warehouseTransfer,
                        $sub,
                        $newSubProduct,
                        $deduct,
                        \Auth::user()->creatorId()
                    );

                    $transferQty -= $deduct;
                    $totalTransferred += $deduct;
                }

                // Update transfer status to approved
                $warehouseTransfer->status = 'approved';
                $warehouseTransfer->save();

                // Log transfer approval
                PosLog::logAction('approve_transfer', [
                    'type' => 'transfer',
                    'reference_id' => $warehouseTransfer->id,
                    'warehouse_id' => $warehouseTransfer->from_warehouse,
                    'product_id' => $warehouseTransfer->product_id,
                    'product_no' => $productNo,
                    'quantity' => $totalTransferred,
                    'old_value' => [
                        'status' => 'draft',
                    ],
                    'new_value' => [
                        'status' => 'approved',
                        'transfer_id' => $warehouseTransfer->id,
                        'from_warehouse' => $warehouseTransfer->from_warehouse,
                        'to_warehouse' => $warehouseTransfer->to_warehouse,
                    ],
                    'description' => "Warehouse transfer APPROVED: {$totalTransferred} units of product #{$productNo} moved from warehouse {$warehouseTransfer->from_warehouse} to {$warehouseTransfer->to_warehouse}",
                ]);
            });

            return redirect()->route('warehouse-transfer.index')->with('success', __('Transfer approved and stock moved successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(WarehouseTransfer $warehouseTransfer)
    {
        if(\Auth::user()->type == 'company' || \Auth::user()->can('delete transfer'))
        {
            // Only allow deletion of draft transfers
            if ($warehouseTransfer->status !== 'draft') {
                return redirect()->back()->with('error', __('Only draft transfers can be deleted.'));
            }

            if ($warehouseTransfer->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            DB::transaction(function () use ($warehouseTransfer) {
                $transfer = $warehouseTransfer;

                // Log transfer deletion before deleting (draft transfers don't have stock moved, so no reversal needed)
                PosLog::logAction('delete_transfer', [
                    'type' => 'transfer',
                    'reference_id' => $transfer->id,
                    'warehouse_id' => $transfer->from_warehouse,
                    'product_id' => $transfer->product_id,
                    'product_no' => $transfer->product_no,
                    'quantity' => $transfer->quantity,
                    'old_value' => [
                        'transfer_id' => $transfer->id,
                        'from_warehouse' => $transfer->from_warehouse,
                        'to_warehouse' => $transfer->to_warehouse,
                        'date' => $transfer->date,
                        'status' => $transfer->status,
                    ],
                    'description' => "Draft warehouse transfer deleted: {$transfer->quantity} units of product #{$transfer->product_no}",
                ]);

                // Delete the draft transfer record (no stock movement needed)
                $transfer->delete();
               
            });
             return redirect()->route('warehouse-transfer.index')->with('success', __('Draft transfer deleted successfully.'));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Get categories for a warehouse
     */
    public function getCategories(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $creatorId = \Auth::user()->creatorId();

        if (!$warehouseId) {
            return response()->json(['categories' => []]);
        }

        try {
            // Get unique product IDs that have sub-products in this warehouse
            $productIds = SubProduct::where('warehouse_id', $warehouseId)
                ->where('created_by', $creatorId)
                ->where('quantity', '>', 0)
                ->distinct()
                ->pluck('product_id')
                ->filter()
                ->unique();

            if ($productIds->isEmpty()) {
                return response()->json(['categories' => []]);
            }

            // Get unique categories from those products using a join
            $categoryIds = ProductService::whereIn('id', $productIds)
                ->where('created_by', $creatorId)
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id')
                ->filter()
                ->unique();

            if ($categoryIds->isEmpty()) {
                return response()->json(['categories' => []]);
            }

            // Get categories
            $categories = ProductServiceCategory::whereIn('id', $categoryIds)
                ->where('created_by', $creatorId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json(['categories' => $categories]);
        } catch (\Exception $e) {
            \Log::error('Error getting categories for warehouse transfer', [
                'error' => $e->getMessage(),
                'warehouse_id' => $warehouseId,
                'user_id' => $creatorId
            ]);
            return response()->json(['categories' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get brands for a warehouse filtered by category (category is required)
     */
    public function getBrands(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $categoryId = $request->category_id;
        $creatorId = \Auth::user()->creatorId();

        if (!$warehouseId || !$categoryId) {
            return response()->json(['brands' => []]);
        }

        // Get brands that have products in this category AND have sub-products in this warehouse
        $brands = Brand::whereHas('products', function($q) use ($categoryId, $warehouseId, $creatorId) {
            $q->where('category_id', $categoryId)
              ->whereHas('subProducts', function($subQ) use ($warehouseId, $creatorId) {
                  $subQ->where('warehouse_id', $warehouseId)
                       ->where('created_by', $creatorId)
                       ->where('quantity', '>', 0);
              });
        })
        ->where('created_by', $creatorId)
        ->select('id', 'name')
        ->distinct()
        ->orderBy('name')
        ->get();

        return response()->json(['brands' => $brands]);
    }

    /**
     * Get products for a warehouse filtered by category AND brand (both are required)
     */
    public function getProducts(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $categoryId = $request->category_id;
        $brandId = $request->brand_id;
        $creatorId = \Auth::user()->creatorId();

        if (!$warehouseId || !$categoryId || !$brandId) {
            return response()->json(['products' => []]);
        }

        // Get products that match category AND brand AND have sub-products in this warehouse
        $products = ProductService::where('category_id', $categoryId)
            ->where('brand_id', $brandId)
            ->whereHas('subProducts', function($q) use ($warehouseId, $creatorId) {
                $q->where('warehouse_id', $warehouseId)
                  ->where('created_by', $creatorId)
                  ->where('quantity', '>', 0);
            })
            ->where('created_by', $creatorId)
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Get sub-products for a warehouse filtered by specific product (product is required)
     */
    public function getSubProducts(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $productId = $request->product_id;
        $creatorId = \Auth::user()->creatorId();

        if (!$warehouseId || !$productId) {
            return response()->json(['sub_products' => []]);
        }

        // Get sub-products for this specific product in this warehouse
        $subProducts = SubProduct::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('created_by', $creatorId)
            ->where('quantity', '>', 0)
            ->with(['productService:id,name,sku'])
            ->get()
            ->groupBy('product_no')
            ->map(function($group) {
                $first = $group->first();
                return [
                    'product_no' => $first->product_no,
                    'product_id' => $first->product_id,
                    'product_name' => $first->productService->name ?? 'N/A',
                    'total_quantity' => $group->sum('quantity'),
                    'sale_price' => $first->sale_price ?? 0,
                    'purchase_price' => $first->purchase_price ?? 0,
                ];
            })
            ->values();

        return response()->json(['sub_products' => $subProducts]);
    }

    /**
     * Get sub-products for a warehouse filtered by category (category is required)
     */
    public function getSubProductsByCategory(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $categoryId = $request->category_id;
        $creatorId = \Auth::user()->creatorId();

        if (!$warehouseId || !$categoryId) {
            return response()->json(['sub_products' => []]);
        }

        try {
            // Get product IDs that belong to this category
            $productIds = ProductService::where('category_id', $categoryId)
                ->where('created_by', $creatorId)
                ->pluck('id');

            if ($productIds->isEmpty()) {
                return response()->json(['sub_products' => []]);
            }

            // Get all sub-products for these products in this warehouse
            $subProducts = SubProduct::where('warehouse_id', $warehouseId)
                ->whereIn('product_id', $productIds)
                ->where('created_by', $creatorId)
                ->where('quantity', '>', 0)
                ->with(['productService:id,name,sku,category_id'])
                ->get()
                ->groupBy('product_no')
                ->map(function($group) {
                    $first = $group->first();
                    return [
                        'product_no' => $first->product_no,
                        'product_id' => $first->product_id,
                        'product_name' => $first->productService->name ?? 'N/A',
                        'total_quantity' => $group->sum('quantity'),
                        'sale_price' => $first->sale_price ?? 0,
                        'purchase_price' => $first->purchase_price ?? 0,
                    ];
                })
                ->values();

            return response()->json(['sub_products' => $subProducts]);
        } catch (\Exception $e) {
            \Log::error('Error getting sub-products by category', [
                'error' => $e->getMessage(),
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId,
                'user_id' => $creatorId
            ]);
            return response()->json(['sub_products' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function getProduct(Request $request)
    {
        $WHID = $request->warehouse_id;
        $creatorId = \Auth::user()->creatorId();

        // Optimized: Use a single query with subquery to get latest SubProduct for each product_no
        // This eliminates N+1 query problem by fetching all data in minimal queries
        $products = SubProduct::select(
                'sub_products.chassis_no',
                DB::raw('SUM(sub_products.quantity) as total_quantity'),
                DB::raw('MAX(sub_products.id) as latest_id')
            )
            ->where('sub_products.warehouse_id', $WHID)
            ->where('sub_products.created_by', '=', $creatorId)
            ->groupBy('sub_products.chassis_no')
            ->get();

        // Get latest SubProduct IDs
        $latestIds = $products->pluck('latest_id')->filter()->unique();

        // Eager load all required relationships in one query
        $latestSubProducts = SubProduct::with(['productService', 'priceRule'])
            ->whereIn('id', $latestIds)
            ->where('warehouse_id', $WHID)
            ->where('created_by', '=', $creatorId)
            ->get()
            ->keyBy('id');

        // Build result array
        $result = $products->map(function ($item) use ($latestSubProducts) {
            $latestSubProduct = $latestSubProducts->get($item->latest_id);
            
            if (!$latestSubProduct) {
                return null;
            }

            // Calculate price (same logic as get_price_list_sale_price but optimized)
            $salePrice = $latestSubProduct->sale_price;
            if ($latestSubProduct->priceRule) {
                $rule = $latestSubProduct->priceRule;
                $basePrice = ($rule->base_price_source == 'purchase' && $latestSubProduct->productService)
                    ? $latestSubProduct->productService->purchase_price
                    : $latestSubProduct->sale_price;
                
                $salePrice = match ($rule->price_mode) {
                    'discount' => $basePrice * (1 - $rule->value / 100),
                    'formula'  => $basePrice * (1 + $rule->value / 100),
                    'fixed'    => $rule->value,
                    default    => $basePrice,
                };
                
                if ($rule->apply_99) {
                    $salePrice = round($salePrice) - 0.01;
                }
            }

            return [
                'product_no'     => $item->product_no,
                'total_quantity' => $item->total_quantity,
                'sale_price'     => $salePrice,
                'product'        => $latestSubProduct->productService?->name ?? '',
            ];
        })->filter()->values();

        return response()->json([
            'products' => $result,
            'to_warehouses' => warehouse::where('id', '!=', $WHID)
                ->where('created_by', '=', $creatorId)
                ->pluck('name', 'id'),
        ]);
    }


    public function getquantity(Request $request)
    {
        if($request->product_id == 0)
        {
            $pro_qty = WarehouseProduct::where('created_by', '=', \Auth::user()->creatorId())
                ->get()->pluck('quantity', 'product_id')->toArray();
        }
        else
        {
            $pro_qty = WarehouseProduct::where('created_by', '=', \Auth::user()->creatorId())
                        ->where('product_id', $request->product_id)
                        ->get()->pluck('quantity');

        }
        return response()->json($pro_qty);
    }

    /**
     * Same access as manual warehouse transfer create: company or "create transfer".
     */
    private function userCanCreateWarehouseTransfer(): bool
    {
        $user = \Auth::user();

        return $user->type == 'company' || $user->can('create transfer');
    }

    /**
     * Show import form
     */
    public function importFile()
    {
        if (!$this->userCanCreateWarehouseTransfer()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('warehouse-transfer.import');
    }

    /**
     * Handle Excel import
     */
    public function import(Request $request)
    {
        if (!$this->userCanCreateWarehouseTransfer()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            $user = \Auth::user();
            $import = new WarehouseTransferImport(
                $user->creatorId(),
                (int) $user->id,
                $user->type !== 'company' ? $this->getUserPrimaryWarehouseId($user) : null
            );
            
            // Always attempt import - catch all exceptions and continue
            try {
                Excel::import($import, $request->file('file'));
            } catch (\Exception $importException) {
                // Log the exception but don't stop - check for partial success
                \Log::warning('Warehouse Transfer import exception caught', [
                    'error' => $importException->getMessage(),
                    'user_id' => \Auth::user()->creatorId()
                ]);
            }

            // Check if there were errors stored in cache (always check, even if exception occurred)
            $errorKey = \App\Imports\WarehouseTransferImport::$lastImportErrors['key'] ?? null;
            if ($errorKey && \Cache::has($errorKey)) {
                $errorData = \Cache::get($errorKey);
                
                $successCount = $errorData['success_count'] ?? 0;
                $errors = $errorData['errors'] ?? [];
                
                // Always show success if any items were imported, even with errors
                if ($successCount > 0) {
                    $message = '<div style="text-align: left;">';
                    $message .= '<div class="alert alert-success mb-3">';
                    $message .= '<strong>' . __('Success:') . '</strong> ' . __('Successfully imported :count transfer(s).', ['count' => $successCount]);
                    $message .= '</div>';
                    
                    if (count($errors) > 0) {
                        $message .= '<div class="alert alert-info mb-3">';
                        $message .= '<strong>' . __('Information:') . '</strong> ' . __(':count error(s) occurred during import, but the rest of the file was imported successfully.', ['count' => count($errors)]);
                        $message .= '</div>';
                        
                        $message .= '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">';
                        $message .= '<strong>Error Details:</strong><br>';
                        $message .= '<ul style="margin-bottom: 0; padding-left: 20px; margin-top: 10px;">';
                        foreach (array_slice($errors, 0, 100) as $error) {
                            $message .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($error) . '</li>';
                        }
                        if (count($errors) > 100) {
                            $message .= '<li style="margin-bottom: 5px; color: #666;"><em>' . __('... and :more more error(s).', ['more' => count($errors) - 100]) . '</em></li>';
                        }
                        $message .= '</ul></div>';
                    }
                    $message .= '</div>';

                    return redirect()->route('warehouse-transfer.index')
                        ->with('success', __('Successfully imported :count transfer(s).', ['count' => $successCount]))
                        ->with('error', $message); // use 'error' key so message stays visible
                } else {
                    // No successful imports - show detailed error message in UI
                    $errorMessage = '<div style="text-align: left;">';
                    $errorMessage .= '<div class="alert alert-warning mb-3">';
                    $errorMessage .= '<strong>' . __('Import completed with errors:') . '</strong><br>';
                    $errorMessage .= '<ul style="margin-bottom: 0; padding-left: 20px; margin-top: 10px;">';
                    foreach (array_slice($errors, 0, 50) as $error) {
                        $errorMessage .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($error) . '</li>';
                    }
                    if (count($errors) > 50) {
                        $errorMessage .= '<li style="margin-bottom: 5px; color: #666;"><em>' . __('... and :more more error(s).', ['more' => count($errors) - 50]) . '</em></li>';
                    }
                    $errorMessage .= '</ul></div></div>';

                    return redirect()->route('warehouse-transfer.index')
                        ->with('error', $errorMessage);
                }
            }

            // No errors found - complete success
            return redirect()->route('warehouse-transfer.index')->with('success', __('Warehouse transfers imported successfully!'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Warehouse Transfer import validation failed', [
                'errors' => $e->failures(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            $errorMessage = '<strong>' . __('Import validation failed:') . '</strong><br><br>';
            $errorMessage .= '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';
            $errorMessage .= '<ul style="margin-bottom: 0; padding-left: 20px;">';
            foreach ($e->failures() as $failure) {
                $rowErrors = implode(', ', $failure->errors());
                $errorMessage .= '<li style="margin-bottom: 5px;">Row ' . htmlspecialchars($failure->row()) . ': ' . htmlspecialchars($rowErrors) . '</li>';
            }
            $errorMessage .= '</ul></div>';

            return redirect()->back()->with('error', $errorMessage)->withInput();
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            \Log::error('Warehouse Transfer import - file read error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            $errorMessage = '<strong>' . __('Import failed: Could not read the file. Please check the file format.') . '</strong><br><br>';
            $errorMessage .= '<div style="text-align: left;">' . __('Error details: ') . htmlspecialchars($e->getMessage()) . '</div>';

            return redirect()->back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            \Log::error('Warehouse Transfer import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => \Auth::user()->creatorId()
            ]);

            // Format error message for better display (HTML format for toastr)
            $errorMessage = '<strong>' . __('Import failed:') . '</strong><br><br>';
            
            // Check if error message contains multiple errors (from import class)
            if (strpos($e->getMessage(), 'Import completed with errors:') !== false) {
                // Extract the detailed errors - format as HTML list
                $errorLines = explode("\n", $e->getMessage());
                $errorMessage .= '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';
                $errorMessage .= '<ul style="margin-bottom: 0; padding-left: 20px;">';
                foreach ($errorLines as $line) {
                    $line = trim($line);
                    if (!empty($line) && $line !== 'Import completed with errors:') {
                        $errorMessage .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($line) . '</li>';
                    }
                }
                $errorMessage .= '</ul></div>';
            } else {
                // Single error or general exception - format with line breaks
                $errorText = htmlspecialchars($e->getMessage());
                $errorText = nl2br($errorText); // Convert newlines to <br>
                $errorMessage .= '<div style="text-align: left;">' . $errorText . '</div>';
                
                // Add helpful message
                if (strpos($e->getFile(), 'WarehouseTransferImport') !== false) {
                    $errorMessage .= '<br><br><small>' . __('Please check your file format and data. Make sure all required columns are present and data is valid.') . '</small>';
                }
            }

            // If there's a previous exception, include it
            if ($e->getPrevious()) {
                $prevError = htmlspecialchars($e->getPrevious()->getMessage());
                $prevError = nl2br($prevError);
                $errorMessage .= '<br><br><strong>' . __('Previous error:') . '</strong><br><div style="text-align: left;">' . $prevError . '</div>';
            }

            // Store error message with HTML formatting
            return redirect()->back()->with('error', $errorMessage)->withInput();
        }
    }

    /**
     * Download sample Excel file for warehouse transfer import
     */
    public function downloadSample()
    {
        if (!$this->userCanCreateWarehouseTransfer()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $fromWarehouseSample = '1';
        $toWarehouseSample = '2';

        if ($user->type !== 'company') {
            $userWarehouseId = $this->getUserPrimaryWarehouseId($user);
            if (!$userWarehouseId) {
                return redirect()->back()->with('error', __('No warehouse assigned to your user.'));
            }
            $fromWarehouseSample = (string) $userWarehouseId;
            $otherWarehouseId = warehouse::where('created_by', $user->creatorId())
                ->where('id', '!=', $userWarehouseId)
                ->orderBy('id', 'asc')
                ->value('id');
            $toWarehouseSample = $otherWarehouseId
                ? (string) $otherWarehouseId
                : (string) ($this->getMainWarehouseId($user) ?: $userWarehouseId);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['from_warehouse', 'to_warehouse', 'product_no', 'quantity', 'date'];
        $sheet->fromArray([$headers], null, 'A1');

        // Add sample data row
        $sampleData = [
            $fromWarehouseSample,
            $toWarehouseSample,
            '123456789', // product_no
            '10', // quantity
            date('Y-m-d') // date
        ];
        $sheet->fromArray([$sampleData], null, 'A2');

        // Style header row
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and save to temporary file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'warehouse_transfer_sample_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'warehouse_transfer_sample');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Non-company users must attach a file to the transfer request before approving.
     */
    private function redirectIfNonCompanyMissingTransferAttachment(WarehouseTransferRequest $transferRequest): ?\Illuminate\Http\RedirectResponse
    {
        if (\Auth::user()->type === 'company') {
            return null;
        }

        if (empty(trim((string) $transferRequest->attachment))) {
            return redirect()->back()->with('error', __('Please upload an attachment before approving this transfer request.'));
        }

        return null;
    }

    /**
     * Persist paired stock movements for each FIFO slice when a warehouse transfer is approved.
     */
    private function recordWarehouseTransferStockMovements(
        WarehouseTransfer $transfer,
        SubProduct $sourceSub,
        SubProduct $destSub,
        int $qty,
        int $creatorId
    ): void {
        if ($qty <= 0) {
            return;
        }

        $product = ProductService::find($sourceSub->product_id ?? $transfer->product_id);
        $avgCost = ($product && $product->avg_cost > 0)
            ? (float) $product->avg_cost
            : (float) ($sourceSub->purchase_price ?? 0);
        $costPrice = (float) ($sourceSub->purchase_price ?? 0);

        $out = new StockMovement();
        $out->product_id = $sourceSub->product_id;
        $out->sub_product_id = $sourceSub->id;
        $out->bill_id = null;
        $out->invoice_id = null;
        $out->pos_id = null;
        $out->warehouse_transfer_id = $transfer->id;
        $out->qty_in = 0;
        $out->qty_out = $qty;
        $out->avg_cost = $avgCost;
        $out->cost_price = $costPrice;
        $out->activity = 'Warehouse Transfer Out';
        $out->use_id = null;
        $out->item = null;
        $out->created_by = $creatorId;
        $out->save();

        $in = new StockMovement();
        $in->product_id = $destSub->product_id;
        $in->sub_product_id = $destSub->id;
        $in->bill_id = null;
        $in->invoice_id = null;
        $in->pos_id = null;
        $in->warehouse_transfer_id = $transfer->id;
        $in->qty_in = $qty;
        $in->qty_out = 0;
        $in->avg_cost = $avgCost;
        $in->cost_price = $costPrice;
        $in->activity = 'Warehouse Transfer In';
        $in->use_id = null;
        $in->item = null;
        $in->created_by = $creatorId;
        $in->save();
    }

    /**
     * Split ASN line quantity for warehouse transfer and link transferred qty to destination sub-product.
     *
     * Rules:
     * - Only applies when source sub-product has asn_id.
     * - Prefer exact ASN item mapped to source sub-product.
     * - Partial transfer: reduce parent ASN line and create a split child line for transferred qty.
     * - Full transfer: move ASN line linkage directly to destination sub-product.
     */
    private function splitAsnItemForTransfer(SubProduct $sourceSub, SubProduct $destSub, float $transferQty): void
    {
        if (empty($sourceSub->asn_id) || $transferQty <= 0) {
            return;
        }

        $asnItem = AsnItem::where('asn_id', $sourceSub->asn_id)
            ->where('sub_product_id', $sourceSub->id)
            ->orderBy('id', 'asc')
            ->first();

        // Fallback to any root ASN line under this ASN for the same part no.
        if (!$asnItem) {
            $asnItem = AsnItem::where('asn_id', $sourceSub->asn_id)
                ->whereNull('split_from_asn_item_id')
                ->where('part_no', $sourceSub->product_no)
                ->orderBy('id', 'asc')
                ->first();
        }

        if (!$asnItem) {
            return;
        }

        $lineQty = (float) ($asnItem->qty ?? 0);
        $lineReceived = (float) ($asnItem->received_qty ?? 0);
        $lineConverted = (float) ($asnItem->converted_qty ?? 0);
        $qtyToMove = min($transferQty, max($lineQty, $lineReceived));

        if ($qtyToMove <= 0) {
            return;
        }

        // Full move of this ASN line: just re-link to destination sub-product.
        if (round($qtyToMove, 4) >= round(max($lineQty, $lineReceived), 4)) {
            $asnItem->sub_product_id = $destSub->id;
            $asnItem->save();
            return;
        }

        // Partial move: reduce source ASN line and create split child for moved qty.
        $childConverted = min($lineConverted, $qtyToMove);

        $asnItem->qty = max(0, $lineQty - $qtyToMove);
        $asnItem->received_qty = max(0, $lineReceived - $qtyToMove);
        if ($asnItem->converted_qty !== null) {
            $asnItem->converted_qty = max(0, $lineConverted - $childConverted);
        }
        $asnItem->save();

        $splitAsnItem = $asnItem->replicate([
            'id',
            'deleted_at',
        ]);
        $splitAsnItem->split_from_asn_item_id = $asnItem->id;
        $splitAsnItem->sub_product_id = $destSub->id;
        $splitAsnItem->qty = $qtyToMove;
        $splitAsnItem->received_qty = $qtyToMove;
        $splitAsnItem->converted_qty = $asnItem->converted_qty !== null ? $childConverted : null;
        $splitAsnItem->save();
    }
}
