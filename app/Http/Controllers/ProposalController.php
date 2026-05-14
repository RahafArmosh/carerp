<?php

namespace App\Http\Controllers;

use App\Exports\ProposalExport;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Milestone;
use App\Models\Products;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Proposal;
use App\Models\ProposalProduct;
use App\Models\StockReport;
use App\Models\Task;
use App\Models\User;
use App\Models\Utility;
use App\Models\SubProduct;
use App\Models\BankAccount;
use App\Models\Tax;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProposalController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage proposal')) {

            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('All', '');

            $status = Proposal::$statues;

            $query = Proposal::where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->customer)) {
                $query->where('id', '=', $request->customer);
            }
            if (!empty($request->issue_date)) {
                $date_range = explode('to', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $proposals = $query->with(['category'])->get();

            return view('proposal.index', compact('proposals', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($customerId)
    {
        if (\Auth::user()->can('create proposal')) {
            $customFields    = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'proposal')->get();
            $proposalItemCustomFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'proposal_item')
                ->orderBy('id')
                ->get();
            $proposal_number = \Auth::user()->proposalNumberFormat($this->proposalNumber());
            $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            // $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())
                ->with(['brand', 'subBrand', 'category'])
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
            $product_services->prepend('--', '');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = \App\Models\Currency::get()->pluck('name', 'id');
            $currency->prepend('AED', '');
            $fullTax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
            return view('proposal.create', compact('customers', 'proposal_number', 'product_services', 'category', 'customFields', 'proposalItemCustomFields', 'customerId', 'accounts', 'currency', 'fullTax'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();

        return view('proposal.customer_detail', compact('customer'));
    }

    public function product(Request $request)
    {

        $data['product'] = $product = ProductService::find($request->product_id);

        $data['unit']    = (!empty($product->unit)) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;

        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;

        $salePrice           = $product->sale_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create proposal')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',
                    'tax_id' => ['required', 'array'],
                    'tax_id.*' => ['integer', 'exists:taxes,id']
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $status = Proposal::$statues;

            $proposal                 = new Proposal();
            $proposal->proposal_id    = $this->proposalNumber();
            $proposal->customer_id    = $request->customer_id;
            $proposal->status         = 0;
            $proposal->issue_date     = $request->issue_date;
            $proposal->category_id    = $request->category_id;
            $proposal->bank_account_id    = $request->bank_account_id;
            $proposal->created_by     = \Auth::user()->creatorId();
            $proposal->currency_id    = !empty($request->currency_id) ? $request->currency_id : null;
            $proposal->exchange_rate  = !empty($request->exchange_rate) ? $request->exchange_rate : 0;
            $proposal->description    = $request->descriptionProposal;
            $proposal->tax_id    = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
            $proposal->save();
            CustomField::saveData($proposal, $request->customField);
            $products = $request->items;

            for ($i = 0; $i < count($products); $i++) {
                $proposalProduct              = new ProposalProduct();
                $proposalProduct->proposal_id = $proposal->id;
                $proposalProduct->product_id  = $products[$i]['item'];
                $proposalProduct->quantity    = $products[$i]['quantity'];
                $proposalProduct->tax         = $products[$i]['tax'];
                $proposalProduct->discount    = $products[$i]['discount'];
                $proposalProduct->price       = $products[$i]['price'];
                $proposalProduct->description = $products[$i]['description'];
                // Exchange fields - save the base price/discount in original currency
                $basePrice = $products[$i]['price'];
                $baseDiscount = $products[$i]['discount'];
                $proposalProduct->exchange_price = $basePrice;
                $proposalProduct->exchange_discount = $baseDiscount;
                if (!empty($request->currency_id)) {
                    $curr = \App\Models\Currency::find($request->currency_id);
                    $exchangeRate = !empty($request->exchange_rate)
                        ? $request->exchange_rate
                        : ($curr ? $curr->exchange_rate : 1);
                    // Convert to AED for storage
                    $proposalProduct->price = $basePrice * $exchangeRate;
                    $proposalProduct->discount = $baseDiscount * $exchangeRate;
                }
                $proposalProduct->save();

                // Save proposal item custom fields (module = proposal_item) (record_id = proposal_products.id)
                $itemCustom = $products[$i]['proposal_item_custom_fields'] ?? null;
                // Support repeater-friendly input names: proposal_item_custom_fields_{fieldId}
                if (empty($itemCustom) || !is_array($itemCustom)) {
                    $itemCustom = [];
                    foreach (($products[$i] ?? []) as $k => $v) {
                        if (is_string($k) && str_starts_with($k, 'proposal_item_custom_fields_')) {
                            $fid = (int) str_replace('proposal_item_custom_fields_', '', $k);
                            if ($fid > 0) {
                                $itemCustom[$fid] = $v;
                            }
                        }
                    }
                }
                if (!empty($itemCustom) && is_array($itemCustom)) {
                    $creatorId = \Auth::user()->creatorId();
                    $allowedFieldIds = \App\Models\CustomField::where('created_by', $creatorId)
                        ->where('module', 'proposal_item')
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                    $allowedFieldIds = array_flip($allowedFieldIds);

                    $payload = [];
                    foreach ($itemCustom as $fieldId => $value) {
                        $fid = (int) $fieldId;
                        if ($fid > 0 && isset($allowedFieldIds[$fid])) {
                            $payload[$fid] = is_array($value) ? json_encode($value) : $value;
                        }
                    }

                    if (!empty($payload)) {
                        \App\Models\CustomField::saveData($proposalProduct, $payload);
                    }
                }
            }



            //For Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $customer = Customer::find($proposal->customer_id);
            $proposalNotificationArr = [
                'proposal_number' => \Auth::user()->proposalNumberFormat($proposal->proposal_id),
                'user_name' => \Auth::user()->name,
                'customer_name' => $customer->name,
                'proposal_issue_date' => $proposal->issue_date,
            ];
            //Twilio Notification
            if (isset($setting['twilio_proposal_notification']) && $setting['twilio_proposal_notification'] == 1) {
                Utility::send_twilio_msg($request->contact, 'new_proposal', $proposalNotificationArr);
            }



            return redirect()->route('proposal.index', $proposal->id)->with('success', __('Proposal successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {

        if (\Auth::user()->can('edit proposal')) {
            try {
                $id              = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Proposal Not Found.'));
            }

            $id              = Crypt::decrypt($ids);
            $proposal        = Proposal::find($id);
            $proposal_number = \Auth::user()->proposalNumberFormat($proposal->proposal_id);
            $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category        = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $proposal->customField = CustomField::getData($proposal, 'proposal');
            $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'proposal')->get();
            $proposalItemCustomFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'proposal_item')
                ->orderBy('id')
                ->get();

            $items = [];
            foreach ($proposal->items as $proposalItem) {
                $itemAmount               = $proposalItem->quantity * $proposalItem->price;
                $proposalItem->itemAmount = $itemAmount;
                $proposalItem->taxes      = Utility::tax($proposalItem->tax);
                $proposalItem->proposal_item_custom_fields = CustomField::getData($proposalItem, 'proposal_item');

                // Flatten values so jquery.repeater can prefill inputs named proposal_item_custom_fields_{fieldId}
                if ($proposalItemCustomFields->isNotEmpty()) {
                    foreach ($proposalItemCustomFields as $cf) {
                        $val = $proposalItem->proposal_item_custom_fields[$cf->id] ?? null;
                        if (is_array($val)) {
                            $val = implode(', ', $val);
                        }
                        $proposalItem->{'proposal_item_custom_fields_' . $cf->id} = $val;
                    }
                }
                $items[]                  = $proposalItem;
            }
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = \App\Models\Currency::get()->pluck('name', 'id');
            // $currency->prepend('AED', '');
            $fullTax = Tax::where('created_by', \Auth::user()->creatorId())->get();
            return view('proposal.edit', compact('customers', 'product_services', 'proposal', 'proposal_number', 'category', 'customFields', 'proposalItemCustomFields', 'items', 'accounts', 'currency', 'fullTax'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, Proposal $proposal)
    {
        if (\Auth::user()->can('edit proposal')) {
            if ($proposal->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'category_id' => 'required',
                        'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('proposal.index')->with('error', $messages->first());
                }
                $proposal->customer_id    = $request->customer_id;
                $proposal->issue_date     = $request->issue_date;
                $proposal->category_id    = $request->category_id;
                $proposal->bank_account_id    = $request->bank_account_id;
                $proposal->description    = $request->descriptionProposal;
                $proposal->currency_id    = !empty($request->currency_id) ? $request->currency_id : null;
                $proposal->exchange_rate  = !empty($request->exchange_rate) ? $request->exchange_rate : 0;
                //                $proposal->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $proposal->save();
                CustomField::saveData($proposal, $request->customField);
                $products = $request->items;

                for ($i = 0; $i < count($products); $i++) {
                    $proposalProduct = ProposalProduct::find($products[$i]['id']);
                    if ($proposalProduct == null) {
                        $proposalProduct              = new ProposalProduct();
                        $proposalProduct->proposal_id = $proposal->id;
                    }

                    if (isset($products[$i]['item'])) {
                        $proposalProduct->product_id = $products[$i]['item'];
                    }

                    $proposalProduct->quantity    = $products[$i]['quantity'];
                    $proposalProduct->tax         = $products[$i]['tax'];
                    $proposalProduct->discount    = $products[$i]['discount'];
                    $proposalProduct->price       = $products[$i]['price'];
                    $proposalProduct->description = $products[$i]['description'];
                    // Exchange fields - save the base price/discount in original currency
                    $basePrice = $products[$i]['price'];
                    $baseDiscount = $products[$i]['discount'];
                    $proposalProduct->exchange_price = $basePrice;
                    $proposalProduct->exchange_discount = $baseDiscount;
                    if (!empty($request->currency_id)) {
                        $curr = \App\Models\Currency::find($request->currency_id);
                        $exchangeRate = !empty($request->exchange_rate)
                            ? $request->exchange_rate
                            : ($curr ? $curr->exchange_rate : 1);
                        // Convert to AED for storage
                        $proposalProduct->price = $basePrice * $exchangeRate;
                        $proposalProduct->discount = $baseDiscount * $exchangeRate;
                    }
                    $proposalProduct->save();

                    // Save proposal item custom fields (module = proposal_item) (record_id = proposal_products.id)
                    $itemCustom = $products[$i]['proposal_item_custom_fields'] ?? null;
                    // Support repeater-friendly input names: proposal_item_custom_fields_{fieldId}
                    if (empty($itemCustom) || !is_array($itemCustom)) {
                        $itemCustom = [];
                        foreach (($products[$i] ?? []) as $k => $v) {
                            if (is_string($k) && str_starts_with($k, 'proposal_item_custom_fields_')) {
                                $fid = (int) str_replace('proposal_item_custom_fields_', '', $k);
                                if ($fid > 0) {
                                    $itemCustom[$fid] = $v;
                                }
                            }
                        }
                    }
                    if (!empty($itemCustom) && is_array($itemCustom)) {
                        $creatorId = \Auth::user()->creatorId();
                        $allowedFieldIds = \App\Models\CustomField::where('created_by', $creatorId)
                            ->where('module', 'proposal_item')
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all();
                        $allowedFieldIds = array_flip($allowedFieldIds);

                        $payload = [];
                        foreach ($itemCustom as $fieldId => $value) {
                            $fid = (int) $fieldId;
                            if ($fid > 0 && isset($allowedFieldIds[$fid])) {
                                $payload[$fid] = is_array($value) ? json_encode($value) : $value;
                            }
                        }

                        if (!empty($payload)) {
                            \App\Models\CustomField::saveData($proposalProduct, $payload);
                        }
                    }
                }

                return redirect()->route('proposal.index', $proposal->id)->with('success', __('Proposal successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function proposalNumber()
    {
        $latest = Proposal::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->proposal_id + 1;
    }

    public function show($ids)
    {
        if (\Auth::user()->can('show proposal')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Proposal Not Found.'));
            }
            $id       = Crypt::decrypt($ids);
            $proposal = Proposal::with(['items.product.unit'])->find($id);

            if ($proposal->created_by == \Auth::user()->creatorId()) {
                $customer = $proposal->customer;
                $iteams   = $proposal->items;
                $status   = Proposal::$statues;

                $proposal->customField = CustomField::getData($proposal, 'proposal');
                $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'proposal')->get();
                $proposalItemCustomFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'proposal_item')
                    ->orderBy('id')
                    ->get();

                return view('proposal.view', compact('proposal', 'customer', 'iteams', 'status', 'customFields', 'proposalItemCustomFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Proposal $proposal)
    {
        if (\Auth::user()->can('delete proposal')) {
            if ($proposal->created_by == \Auth::user()->creatorId()) {
                // Get the redirect URL before deleting
                $redirectUrl = route('proposal.index');
                $proposal->delete();
                ProposalProduct::where('proposal_id', '=', $proposal->id)->delete();

                return redirect($redirectUrl)->with('success', __('Proposal successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete proposal product')) {
            ProposalProduct::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('Proposal product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerProposal(Request $request)
    {
        if (\Auth::user()->can('manage customer proposal')) {

            $status = Proposal::$statues;

            $query = Proposal::where('customer_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->issue_date)) {
                $date_range = explode(' - ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $proposals = $query->get();

            return view('proposal.index', compact('proposals', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function customerProposalShow($ids)
    {
        if (\Auth::user()->can('show proposal')) {
            $proposal_id = \Crypt::decrypt($ids);
            $proposal    = Proposal::where('id', $proposal_id)->first();
            if ($proposal->created_by == \Auth::user()->creatorId()) {
                $customer = $proposal->customer;
                $iteams   = $proposal->items;

                return view('proposal.view', compact('proposal', 'customer', 'iteams'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {
        if (\Auth::user()->can('send proposal')) {
            $proposal            = Proposal::where('id', $id)->first();
            $proposal->send_date = date('Y-m-d');
            $proposal->status    = 1;
            $proposal->save();

            $customer           = Customer::where('id', $proposal->customer_id)->first();
            $proposal->name     = !empty($customer) ? $customer->name : '';
            $proposal->proposal = \Auth::user()->proposalNumberFormat($proposal->proposal_id);

            $proposalId    = Crypt::encrypt($proposal->id);
            $proposal->url = route('proposal.pdf', [$proposalId, 'quotation']);

            // Send Email
            $setings = Utility::settings();
            if ($setings['proposal_sent'] == 1 && !empty($customer->id)) {
                $customer           = Customer::where('id', $proposal->customer_id)->first();
                $proposal->name     = !empty($customer) ? $customer->name : '';
                $proposal->proposal = \Auth::user()->proposalNumberFormat($proposal->proposal_id);

                $proposalId    = Crypt::encrypt($proposal->id);
                $proposal->url = route('proposal.pdf', [$proposalId, 'quotation']);

                $proposalArr = [
                    'proposal_name' => $proposal->name,
                    'proposal_number' => $proposal->proposal,
                    'proposal_url' => $proposal->url,

                ];
                //                dd($proposalArr);
                $resp = \App\Models\Utility::sendEmailTemplate('proposal_sent', [$customer->id => $customer->email], $proposalArr);
                return redirect()->back()->with('success', __('Proposal successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->back()->with('success', __('Proposal successfully sent.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send proposal')) {
            $proposal = Proposal::where('id', $id)->first();

            $customer           = Customer::where('id', $proposal->customer_id)->first();
            $proposal->name     = !empty($customer) ? $customer->name : '';
            $proposal->proposal = \Auth::user()->proposalNumberFormat($proposal->proposal_id);

            $proposalId    = Crypt::encrypt($proposal->id);
            $proposal->url = route('proposal.pdf', [$proposalId, 'quotation']);

            // Send Email
            $setings = Utility::settings();
            if ($setings['proposal_sent'] == 1) {
                $customer           = Customer::where('id', $proposal->customer_id)->first();
                $proposal->name     = !empty($customer) ? $customer->name : '';
                $proposal->proposal = \Auth::user()->proposalNumberFormat($proposal->proposal_id);

                $proposalId    = Crypt::encrypt($proposal->id);
                $proposal->url = route('proposal.pdf', [$proposalId, 'quotation']);

                $proposalArr = [
                    'proposal_name' => $proposal->name,
                    'proposal_number' => $proposal->proposal,
                    'proposal_url' => $proposal->url,

                ];
                //                dd($proposalArr);
                $resp = \App\Models\Utility::sendEmailTemplate('proposal_sent', [$customer->id => $customer->email], $proposalArr);
                return redirect()->back()->with('success', __('Proposal successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->back()->with('success', __('Proposal successfully sent.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function shippingDisplay(Request $request, $id)
    {
        $proposal = Proposal::find($id);

        if ($request->is_display == 'true') {
            $proposal->shipping_display = 1;
        } else {
            $proposal->shipping_display = 0;
        }
        $proposal->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($proposal_id)
    {
        if (\Auth::user()->can('duplicate proposal')) {
            $proposal                       = Proposal::where('id', $proposal_id)->first();
            $duplicateProposal              = new Proposal();
            $duplicateProposal->proposal_id = $this->proposalNumber();
            $duplicateProposal->customer_id = $proposal['customer_id'];
            $duplicateProposal->issue_date  = date('Y-m-d');
            $duplicateProposal->send_date   = null;
            $duplicateProposal->category_id = $proposal['category_id'];
            $duplicateProposal->status      = 0;
            $duplicateProposal->created_by  = $proposal['created_by'];
            $duplicateProposal->save();

            if ($duplicateProposal) {
                $proposalProduct = ProposalProduct::where('proposal_id', $proposal_id)->get();
                foreach ($proposalProduct as $product) {
                    $duplicateProduct              = new ProposalProduct();
                    $duplicateProduct->proposal_id = $duplicateProposal->id;
                    $duplicateProduct->product_id  = $product->product_id;
                    $duplicateProduct->quantity    = $product->quantity;
                    $duplicateProduct->tax         = $product->tax;
                    $duplicateProduct->discount    = $product->discount;
                    $duplicateProduct->price       = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('Proposal duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function convert($proposal_id)
    {
        if (!\Auth::user()->can('convert invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Fetch Proposal
        $proposal = Proposal::find($proposal_id);
        if (!$proposal) {
            return redirect()->back()->with('error', __('Proposal not found.'));
        }

        // Convert Proposal to Invoice
        DB::beginTransaction();
        try {
            $proposal->is_convert = 1;
            $proposal->save();

            $convertInvoice = new Invoice();
            $convertInvoice->invoice_id = $this->invoiceNumber();
            $convertInvoice->customer_id = $proposal->customer_id;
            $convertInvoice->issue_date = now()->format('Y-m-d');
            $convertInvoice->due_date = now()->format('Y-m-d');
            $convertInvoice->send_date = null;
            $convertInvoice->category_id = $proposal->category_id;
            $convertInvoice->status = 0;
            $convertInvoice->created_by = $proposal->created_by;
            $convertInvoice->salesman_id = \Auth::user()->creatorId();
            $convertInvoice->tax_id = !empty($proposal->tax_id) ? $proposal->tax_id : '';
            $convertInvoice->currency_id = null;
            $convertInvoice->exchange_rate = 0;
            $convertInvoice->driver_id = null;
            $convertInvoice->save();

            // Link Invoice to Proposal
            $proposal->converted_invoice_id = $convertInvoice->id;
            $proposal->save();

            // Fetch Proposal Products
            $proposalProducts = ProposalProduct::where('proposal_id', $proposal_id)->get();

            foreach ($proposalProducts as $product) {
                $productA = ProductService::find($product->product_id);

                if (!$productA) {
                    continue; // Skip if product is not found
                }

                $qty = $product->quantity; // Required quantity

                if ($productA->category->type === "Qty product") {
                    // Fetch Available Sub-Products (FIFO)
                    $subProducts = SubProduct::where('product_id', $productA->id)
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'ASC')
                        ->get();

                    foreach ($subProducts as $subProduct) {
                        if ($qty <= 0) break;

                        $subProductItem = SubProduct::find($subProduct->id);
                        $quantityToDeduct = min($subProductItem->quantity, $qty);

                        // Create Invoice Product
                        InvoiceProduct::create([
                            'invoice_id' => $convertInvoice->id,
                            'product_id' => $productA->id,
                            'sub_product_id' => $subProduct->id,
                            'quantity' => $quantityToDeduct,
                            'tax' => $product->tax,
                            'discount' => $product->discount,
                            'price' => $product->price,
                            'description' => $product->description,
                        ]);

                        // Update Stock
                        $subProductItem->decrement('quantity', $quantityToDeduct);
                        $subProductItem->update(['invoice_id' => $convertInvoice->id, 'booked' => 1]);

                        // Reduce required quantity
                        $qty -= $quantityToDeduct;
                    }
                } else {
                    // Fetch Available Sub-Products (Non-Qty Products)
                    $subProducts = SubProduct::where('product_id', $productA->id)
                        ->where('flag', '!=', 2)
                        ->where('booked', 0)
                        ->limit($qty)
                        ->get();

                    if ($subProducts->count() != $qty) {
                        return redirect()->back()->with('error', __('Insufficient stock for ' . $productA->name . '. Available: ' . $subProducts->count()));
                    }

                    foreach ($subProducts as $subProduct) {
                        $subProduct->update(['invoice_id' => $convertInvoice->id, 'booked' => 1]);

                        InvoiceProduct::create([
                            'invoice_id' => $convertInvoice->id,
                            'product_id' => $productA->id,
                            'sub_product_id' => $subProduct->id,
                            'quantity' => 1, // Since we selected exactly $qty items
                            'tax' => $product->tax,
                            'discount' => $product->discount,
                            'price' => $product->price,
                            'description' => $product->description,
                        ]);
                    }
                }
            }

            // Remove previous Stock Reports
            StockReport::where('type', 'invoice')->where('type_id', $convertInvoice->id)->delete();

            DB::commit();

            return redirect()->back()->with('success', __('Proposal converted to invoice successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('Error: ' . $e->getMessage()));
        }
    }

    public function statusChange(Request $request, $id)
    {
        $status           = $request->status;
        $proposal         = Proposal::find($id);
        $proposal->status = $status;
        $proposal->save();

        return redirect()->back()->with('success', __('Proposal status changed successfully.'));
    }

    public function previewProposal($template, $color)
    {
        $template = Utility::resolveProposalTemplate($template);
        $objUser  = \Auth::user();
        $settings = Utility::settings();
        $proposal = new Proposal();

        $customer                   = new \stdClass();
        $customer->email            = '<Email>';
        $customer->shipping_name    = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state   = '<State>';
        $customer->shipping_city    = '<City>';
        $customer->shipping_phone   = '<Customer Phone Number>';
        $customer->shipping_zip     = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name     = '<Customer Name>';
        $customer->billing_country  = '<Country>';
        $customer->billing_state    = '<State>';
        $customer->billing_city     = '<City>';
        $customer->billing_phone    = '<Customer Phone Number>';
        $customer->billing_zip      = '<Zip>';
        $customer->billing_address  = '<Address>';

        $totalTaxPrice = 0;
        $taxesData     = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;
            $item->unit    = 1;
            $item->description    = 'Description for Item ' . $i;


            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $proposal->proposal_id = 1;
        $proposal->issue_date  = date('Y-m-d H:i:s');
        $proposal->due_date    = date('Y-m-d H:i:s');
        $proposal->itemData    = $items;

        $proposal->totalTaxPrice = 60;
        $proposal->totalQuantity = 3;
        $proposal->totalRate     = 300;
        $proposal->totalDiscount = 10;
        $proposal->taxesData     = $taxesData;
        $proposal->created_by     = $objUser->creatorId();

        $proposal->customField = [];
        $customFields          = [];

        $preview    = 1;
        $color      = '#' . $color;
        $font_color = Utility::getFontColor($color);

        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $proposal_logo = Utility::getValByName('proposal_logo');
        //        $company_logo = \App\Models\Utility::GetLogo();
        //        if(isset($proposal_logo) && !empty($proposal_logo))
        //        {
        //            $img          = asset(\Storage::url('proposal_logo').'/'. $proposal_logo);
        //        }
        //        else
        //        {
        //            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        //        }


        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $proposal_logo = Utility::getValByName('proposal_logo');
        if (isset($proposal_logo) && !empty($proposal_logo)) {
            $img = Utility::get_file('proposal_logo/') . $proposal_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('proposal.templates.' . $template, compact('proposal', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function proposal($proposal_id, $type)
    {
        $settings   = Utility::settings();
        $proposalId = Crypt::decrypt($proposal_id);
        $proposal   = Proposal::where('id', $proposalId)->first();

        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $proposal->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $proposal->customer;
        $items         = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];

        // Proposal item custom fields (module = proposal_item) (record_id = proposal_products.id)
        $proposalItemCustomFields = CustomField::where('created_by', (int) $proposal->created_by)
            ->where('module', 'proposal_item')
            ->orderBy('id')
            ->get(['id', 'name']);
        $proposalItemCustomValuesByRecord = collect();
        $proposalItemIds = $proposal->items->pluck('id')->filter()->unique()->values();
        if ($proposalItemCustomFields->isNotEmpty() && $proposalItemIds->isNotEmpty()) {
            $proposalItemCustomValuesByRecord = \App\Models\CustomFieldValue::whereIn('record_id', $proposalItemIds->all())
                ->whereIn('field_id', $proposalItemCustomFields->pluck('id')->all())
                ->whereNotNull('value')
                ->get(['record_id', 'field_id', 'value'])
                ->groupBy('record_id');
        }
        foreach ($proposal->items as $product) {
            $item              = new \stdClass();
            $item->name        = !empty($product->product) ? $product->product->brand->name . '/' . $product->product->subBrand->name . '/' . $product->product->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $product->tax;
            $item->unit        = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount    = $product->exchange_discount;
            $item->price       = $product->exchange_price;
            $item->description = $product->description;
            $item->proposal_product_id = $product->id;

            $pairs = [];
            if ($proposalItemCustomFields->isNotEmpty() && !empty($product->id)) {
                $vals = $proposalItemCustomValuesByRecord->get($product->id) ?? collect();
                foreach ($proposalItemCustomFields as $cf) {
                    $val = optional($vals->firstWhere('field_id', $cf->id))->value;
                    if ($val !== null && trim((string) $val) !== '') {
                        $pairs[] = ['name' => $cf->name, 'value' => $val];
                    }
                }
            }
            $item->proposal_item_custom_fields = $pairs;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);

            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceNumberFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[]      = $itemTax;


                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }
                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $proposal->itemData      = $items;
        $proposal->totalTaxPrice = $totalTaxPrice;
        $proposal->totalQuantity = $totalQuantity;
        $proposal->totalRate     = $totalRate;
        $proposal->totalDiscount = $totalDiscount;
        $proposal->taxesData     = $taxesData;
        $proposal->customField   = CustomField::getData($proposal, 'proposal');
        $proposal->created_by     = $proposal->created_by;

        $customFields            = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'proposal')->get();
        }

        //Set your logo
        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $company_logo = Utility::getValByName('company_logo_dark');
        //        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($proposal->created_by);
        $proposal_logo = $settings_data['proposal_logo'];
        if (isset($proposal_logo) && !empty($proposal_logo)) {
            $img = Utility::get_file('proposal_logo/') . $proposal_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($proposal) {
            $color      = '#' . $settings['proposal_color'];
            $font_color = Utility::getFontColor($color);
            $pdfTitle = $type == "quotation" ? 'Quotation' : 'Proforma Invoice';
            $proposalTemplate = Utility::resolveProposalTemplate($settings['proposal_template'] ?? null);

            return view('proposal.templates.' . $proposalTemplate, compact('proposal', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields', 'pdfTitle'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveProposalTemplateSettings(Request $request)
    {
        //        dd($request);
        $post = $request->all();
        unset($post['_token']);

        if (isset($post['proposal_template']) && (!isset($post['proposal_color']) || empty($post['proposal_color']))) {
            $post['proposal_color'] = "ffffff";
        }
        if (isset($post['proposal_template'])) {
            $post['proposal_template'] = Utility::resolveProposalTemplate($post['proposal_template']);
        }
        //        if($request->proposal_logo)
        //        {
        //            $validator = \Validator::make($request->all(), ['proposal_logo' => 'image|mimes:png|max:20480',]);
        //            if($validator->fails())
        //            {
        //                $messages = $validator->getMessageBag();
        //                return redirect()->back()->with('error', $messages->first());
        //            }
        //            $proposal_logo = \Auth::user()->id . '_proposal_logo.png';
        //            $path = $request->file('proposal_logo')->storeAs('proposal_logo', $proposal_logo);
        //            $post['proposal_logo'] = $proposal_logo;
        //        }

        if ($request->proposal_logo) {
            $dir = 'proposal_logo/';
            $proposal_logo = \Auth::user()->id . '_proposal_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'proposal_logo', $proposal_logo, $dir, $validation);

            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['proposal_logo'] = $proposal_logo;
        }


        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Proposal Setting updated successfully'));
    }

    function invoiceNumber()
    {
        $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->withTrashed()->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public function items(Request $request)
    {
        $items = ProposalProduct::where('proposal_id', $request->proposal_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function invoiceLink($proposalID)
    {

        try {
            $id       = Crypt::decrypt($proposalID);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Proposal Not Found.'));
        }

        $id             = Crypt::decrypt($proposalID);
        $proposal           = Proposal::find($id);
        if (!empty($proposal)) {
            $user_id        = $proposal->created_by;
            $user           = User::find($user_id);
            $customer = $proposal->customer;
            $iteams   = $proposal->items;
            $proposal->customField = CustomField::getData($proposal, 'proposal');
            $status   = Proposal::$statues;
            $customFields         = CustomField::where('module', '=', 'proposal')->get();

            return view('proposal.customer_proposal', compact('proposal', 'customer', 'iteams', 'customFields', 'status', 'user'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function export()
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = 'proposal_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ProposalExport(), $name . '.xlsx');

        return $data;
    }
}
