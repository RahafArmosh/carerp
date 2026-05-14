<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Exports\ProductServiceExport;
use App\Imports\ProductServiceImport;
use App\Imports\StockImport;
use App\Imports\SparePartsStockImport;
use App\Jobs\ImportStockFromExcelJob;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceImage;
use App\Models\ProductServiceUnit;
use App\Models\Tax;
use App\Models\User;
use App\Models\SubProduct;
use App\Models\Brand;
use App\Models\ComboOffer;
use App\Models\VehicleModel;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\warehouse;
use App\Models\WarehouseProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;



class ProductServiceController extends Controller
{
    public function index(Request $request)
    {
        if (\Auth::user()->can('manage product & service')) {
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $query = ProductService::where('created_by', '=', \Auth::user()->creatorId())
                ->with(['category', 'unit', 'brand', 'subBrand']);
            if (!empty($request->category)) {

                $query->where('category_id', $request->category);
            }

            // Handle search
            if (!empty($request->input('search.value'))) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('sku', 'like', "%{$searchValue}%");
                });
            }

            // Handle ordering
            $orderColumn = $request->order[0]['column'] ?? 1;
            $orderDir = $request->order[0]['dir'] ?? 'asc';
            $columns = ['category', 'id', 'name', 'sku', 'sale_price', 'purchase_price', 'avg_cost', 'rented', 'unit', 'quantity', 'quantity_free', 'quantity_booked', 'type'];

            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }

            $total = $query->count();
            $start = $request->start ?? 0;
            $length = $request->length ?? 50;

            $products = $query->skip($start)->take($length)->get();

            // Load product custom fields (module: product)
            $customFields = \App\Models\CustomField::where('module', 'product')
                ->where('created_by', \Auth::user()->creatorId())
                ->orderBy('id')
                ->get(['id', 'name']);

            $productIds = $products->pluck('id')->all();
            $customValues = \App\Models\CustomFieldValue::whereIn('record_id', $productIds)
                ->whereIn('field_id', $customFields->pluck('id')->all())
                ->get(['record_id', 'field_id', 'value'])
                ->groupBy('record_id');

            // Try to locate a specific "Model" custom field (case-insensitive)
            $modelField = $customFields->first(function ($f) {
                return strtolower($f->name) === 'model';
            });

            $data = [];
            foreach ($products as $product) {
                $totalQty = $product->subProducts()->sum('quantity');
                $unitAvgCost = $totalQty > 0 ? ($product->avg_cost / $totalQty) : $product->avg_cost;
                $row = [
                    'category' => $product->category->name,
                    'id' => $product->id,
                    'name' => $product->brand ?
                        $product->brand->name . '/' .
                        ($product->subBrand ? $product->subBrand->name . '/' : '') .
                        $product->name :
                        $product->name,
                    'sku' => $product->sku,
                    'sale_price' => \Auth::user()->priceFormat($product->sale_price),
                    'purchase_price' => \Auth::user()->priceFormat($product->purchase_price),
                    // Show average cost per unit (do not multiply by quantity)
                    'avg_cost' => \Auth::user()->priceFormat($unitAvgCost),
                    'unit' => $product->unit ? $product->unit->name : '',
                    'quantity' => $totalQty,
                    'quantity_free' => $product->getFreeQuantity(),
                    'quantity_booked' => $product->getBookedQuantity(),
                    'type' => ucwords($product->type),
                    'action' => view('productservice.action', compact('product'))->render()
                ];

                // Attach model custom field if present
                if ($modelField) {
                    $val = optional(optional($customValues->get($product->id))->firstWhere('field_id', $modelField->id))->value;
                    $row['model'] = $val ?? '';
                }

                // Optionally attach all custom field values keyed by field name
                if ($customFields->isNotEmpty()) {
                    $valuesForProduct = $customValues->get($product->id, collect());
                    foreach ($customFields as $field) {
                        $found = optional($valuesForProduct->firstWhere('field_id', $field->id))->value;
                        $row['cf_' . strtolower($field->name)] = $found ?? '';
                    }
                }
                $data[] = $row;
            }
            if ($request->ajax()) {

                return response()->json([
                    'draw' => $request->draw,
                    'recordsTotal' => $total,
                    'recordsFiltered' => $total,
                    'data' => $data,
                    // Expose custom fields so the frontend can render columns dynamically if needed
                    'custom_fields' => $customFields
                ]);
            }

            return view('productservice.index', compact('category', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create product & service')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
            $category     = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $brands     = Brand::where('created_by', '=', \Auth::user()->creatorId())->get();
            
            // Get brand_id from request if provided (for filtering models under that brand)
            $selectedBrandId = request()->input('brand_id');

            $subBrands = VehicleModel::where('created_by', '=', \Auth::user()->creatorId())
                ->when($selectedBrandId, function ($query) use ($selectedBrandId) {
                    $query->where('brand_id', '=', $selectedBrandId);
                })
                ->get();
            
            $unit         = ProductServiceUnit::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $tax          = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $fullTax      = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get();
            if ($categories->isEmpty()) {
                return response()->json(['error' => __('No categories found.')], 401);
            }
            return view('productservice.create', compact('category', 'unit', 'tax', 'customFields', 'brands', 'subBrands', 'categories', 'fullTax'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create product & service')) {

            $rules = [
                'name' => 'required',
                'sku' => [
                    'required',
                    Rule::unique('product_services')->where(function ($query) {
                        return $query->where('created_by', \Auth::user()->id);
                    })
                ],
                'sale_price' => 'required|numeric',
                'purchase_price' => 'required|numeric',
                'category_id' => 'required',
                'unit_id' => 'required',
                'type' => 'required',
                'brand_id' => 'required',
                'sub_brand_id' => 'required',
                'tax_id.*' => 'required|integer',
                'product_images' => 'nullable|array',
                'product_images.*' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('productservice.index')->with('error', $messages->first());
            }

            $productService                      = new ProductService();
            $productService->name                = $request->name;
            $productService->description         = $request->description;
            $productService->sku                 = $request->sku;
            $productService->sale_price          = $request->sale_price;
            $productService->sale_price_base     = $request->sale_price_base;
            $productService->purchase_price      = $request->purchase_price;
            $productService->tax_id              = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
            $productService->unit_id             = $request->unit_id;
            if (!empty($request->quantity)) {
                $productService->quantity        = $request->quantity;
            } else {
                $productService->quantity   = 0;
            }
            $productService->type                       = $request->type;
            // $productService->sale_chartaccount_id       = $request->sale_chartaccount_id;
            // $productService->expense_chartaccount_id    = $request->expense_chartaccount_id;
            $productService->category_id                = $request->category_id;
            $productService->brand_id                = !empty($request->brand_id) ? $request->brand_id : 0;
            $productService->sub_brand_id                = !empty($request->sub_brand_id) ? $request->sub_brand_id : 0;
            if (!empty($request->pro_image)) {
                //storage limit
                $image_size = $request->file('pro_image')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                if ($result == 1) {
                    if ($productService->pro_image) {
                        $path = public_path('storage/uploads/pro_image/' . $productService->pro_image);
                    }
                    $fileName = $request->pro_image->getClientOriginalName();
                    $productService->pro_image = $fileName;
                    $dir        = 'uploads/pro_image';
                    $path = Utility::upload_file($request, 'pro_image', $fileName, $dir, []);
                }
            }

            $productService->created_by       = \Auth::user()->creatorId();
            $productService->save();
            CustomField::saveData($productService, $request->customField);

            $this->appendProductGallery($request, $productService);
            $this->syncPrimaryImageFromGallery($productService);

            return redirect()->route('productservice.index')->with('success', __('Product successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if (! \Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $productService = ProductService::with(['images', 'category', 'unit', 'brand', 'subBrand'])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        $galleryItems = $this->productGalleryDisplayItems($productService);

        return view('productservice.show', compact('productService', 'galleryItems'));
    }

    /**
     * Download a product brochure as PDF (DomPDF).
     */
    public function brochurePdf($id)
    {
        if (! \Auth::user()->can('manage product & service')) {
            abort(403, __('Permission denied.'));
        }

        $productService = ProductService::with(['images', 'category', 'unit', 'brand', 'subBrand'])
            ->where('created_by', \Auth::user()->creatorId())
            ->findOrFail($id);

        $imageBlocks = [];
        foreach ($this->productGalleryDisplayItems($productService) as $item) {
            $dataUri = $this->productImageFileToDataUri($item['file_name'] ?? null);
            if ($dataUri) {
                $imageBlocks[] = [
                    'src' => $dataUri,
                    'caption' => $item['caption'] ?? '',
                ];
            }
        }

        $settings = Utility::settings();
        $tenantSettings = Utility::settingsById(\Auth::user()->creatorId());
        if (is_array($tenantSettings)) {
            $settings = array_merge($settings, $tenantSettings);
        }

        $logoDataUri = $this->resolveCompanyLogoDataUriForBrochure($settings);

        $pdf = Pdf::loadView('productservice.brochure_pdf', compact('productService', 'imageBlocks', 'settings', 'logoDataUri'))
            ->setPaper('a4', 'portrait');

        $safeSku = preg_replace('/[^A-Za-z0-9_-]+/', '_', $productService->sku) ?: 'product';

        return $pdf->download('Product-'.$safeSku.'-Brochure.pdf');
    }

    /**
     * @return array<int, array{file_name: string, caption: string, url: string}>
     */
    protected function productGalleryDisplayItems(ProductService $productService): array
    {
        $items = [];
        if (! empty($productService->pro_image)) {
            $items[] = [
                'file_name' => $productService->pro_image,
                'caption' => __('Primary image'),
                'url' => url('storage/uploads/pro_image/'.$productService->pro_image),
            ];
        }
        foreach ($productService->images as $img) {
            if (! empty($productService->pro_image) && $img->file_name === $productService->pro_image) {
                continue;
            }
            $items[] = [
                'file_name' => $img->file_name,
                'caption' => __('Gallery'),
                'url' => $img->url(),
            ];
        }

        return $items;
    }

    protected function productImageFileToDataUri(?string $fileName): ?string
    {
        if (empty($fileName)) {
            return null;
        }
        $path = storage_path('app/public/uploads/pro_image/'.$fileName);
        if (! is_readable($path)) {
            return null;
        }
        $mime = @mime_content_type($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * Company logo for DomPDF: same resolution order as reports (invoice logo, then documents/ company logos).
     */
    protected function resolveCompanyLogoDataUriForBrochure(array $settings): ?string
    {
        $invoiceLogo = (string) ($settings['invoice_logo'] ?? '');
        if ($invoiceLogo !== '') {
            foreach ([
                storage_path('app/public/invoice_logo/'.$invoiceLogo),
                public_path('storage/invoice_logo/'.$invoiceLogo),
            ] as $path) {
                if ($path && is_readable($path)) {
                    $uri = $this->absoluteFilePathToDataUri($path);
                    if ($uri !== null) {
                        return $uri;
                    }
                }
            }
            $url = Utility::get_file('invoice_logo/').$invoiceLogo;
            if (is_string($url) && str_starts_with($url, 'http')) {
                $content = @file_get_contents($url);
                if ($content !== false && $content !== '') {
                    $mime = $this->guessImageMimeFromFileName($invoiceLogo);

                    return 'data:'.$mime.';base64,'.base64_encode($content);
                }
            }
        }

        $companyLogoDark = $settings['company_logo_dark'] ?? '';
        $companyLogoLight = $settings['company_logo_light'] ?? '';
        if (($settings['cust_darklayout'] ?? '') == 'on') {
            $file = ! empty($companyLogoLight) ? $companyLogoLight : 'logo-dark.png';
        } else {
            $file = ! empty($companyLogoDark) ? $companyLogoDark : 'logo-dark.png';
        }
        $local = public_path('documents'.DIRECTORY_SEPARATOR.$file);
        if (is_readable($local)) {
            return $this->absoluteFilePathToDataUri($local);
        }

        return null;
    }

    protected function absoluteFilePathToDataUri(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }
        $mime = @mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    protected function guessImageMimeFromFileName(string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    public function edit($id)
    {
        $productService = ProductService::with('images')->find($id);

        if (\Auth::user()->can('edit product & service')) {
            if (! $productService) {
                return response()->json(['error' => __('Not found.')], 404);
            }
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get();
                $brands     = Brand::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $allbrands     = Brand::where('created_by', '=', \Auth::user()->creatorId())->get();
                // Get brand_id from product or request (for filtering)
                $selectedBrandId = $productService->brand_id ?? request()->input('brand_id');

                $allsubBrands = VehicleModel::where('created_by', '=', \Auth::user()->creatorId())
                    ->when($selectedBrandId, function ($query) use ($selectedBrandId) {
                        $query->where('brand_id', '=', $selectedBrandId);
                    })
                    ->get();
                
                // Create plucked version for dropdown
                $subBrands = $allsubBrands->pluck('name', 'id');
                $unit     = ProductServiceUnit::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $tax      = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $fullTax  = Tax::where('created_by', '=', \Auth::user()->creatorId())->get();

                $productService->customField = CustomField::getData($productService, 'product');
                $customFields                = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
                $productService->tax_id      = explode(',', $productService->tax_id);

                return view('productservice.edit', compact('category', 'unit', 'tax', 'productService', 'customFields', 'brands', 'subBrands', 'allbrands', 'allsubBrands', 'categories', 'fullTax'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit product & service')) {
            $productService = ProductService::with('images')->find($id);
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $rules = [
                    'name' => 'required',
                    'sku' => [
                        'required',
                        Rule::unique('product_services', 'sku')
                            ->where(function ($query) {
                                return $query->where('created_by', \Auth::user()->creatorId());
                            })
                            ->ignore($productService->id),
                    ],
                    'sale_price' => 'required|numeric',
                    'purchase_price' => 'required|numeric',
                    'category_id' => 'required',
                    'brand_id' => 'required',
                    'sub_brand_id' => 'required',
                    'unit_id' => 'required',
                    'type' => 'required',
                    'product_images' => 'nullable|array',
                    'product_images.*' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
                    'delete_image_ids' => 'nullable|array',
                    'delete_image_ids.*' => 'integer',
                ];

                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('productservice.index')->with('error', $messages->first());
                }

                $deleteIds = array_filter(array_map('intval', (array) $request->input('delete_image_ids', [])));
                if (! empty($deleteIds)) {
                    $productService->images()->whereIn('id', $deleteIds)->get()->each->delete();
                }

                $productService->name           = $request->name;
                $productService->description    = $request->description;
                $productService->sku            = $request->sku;
                $productService->brand_id       = $request->brand_id;
                $productService->sub_brand_id   = $request->sub_brand_id;
                $productService->sale_price_base   = $request->sale_price_base;
                $productService->sale_price   = $request->sale_price;
                $productService->purchase_price   = $request->purchase_price;
                $productService->tax_id         = $request->tax_id;
                $productService->unit_id        = $request->unit_id;

                if (!empty($request->quantity)) {
                    $productService->quantity   = $request->quantity;
                } else {
                    $productService->quantity   = 0;
                }
                $productService->type                       = $request->type;
                // $productService->sale_chartaccount_id       = $request->sale_chartaccount_id;
                // $productService->expense_chartaccount_id    = $request->expense_chartaccount_id;
                $productService->category_id                = $request->category_id;
                if (!empty($request->pro_image)) {
                    //storage limit
                    $file_path = '/uploads/pro_image/' . $productService->pro_image;
                    $image_size = $request->file('pro_image')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    if ($result == 1) {
                        if ($productService->pro_image) {
                            Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                            $path = storage_path('uploads/pro_image' . $productService->pro_image);
                            //                            if(file_exists($path))
                            //                            {
                            //                                \File::delete($path);
                            //                            }
                        }
                        $fileName = $request->pro_image->getClientOriginalName();
                        $productService->pro_image = $fileName;
                        $dir        = 'uploads/pro_image';
                        $path = Utility::upload_file($request, 'pro_image', $fileName, $dir, []);
                    }
                }

                $productService->created_by     = \Auth::user()->creatorId();
                $productService->save();
                CustomField::saveData($productService, $request->customField);

                $this->appendProductGallery($request, $productService);
                $this->syncPrimaryImageFromGallery($productService);

                return redirect()->route('productservice.index')->with('success', __('Product successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete product & service')) {
            $productService = ProductService::find($id);
            if ($productService->created_by == \Auth::user()->creatorId()) {
                // Prevent deletion if any related sub-product is booked
                $hasBookedSubProducts = \App\Models\SubProduct::where('product_id', $id)
                    ->whereNull('deleted_at')
                    ->whereIn('booked', [1, 2, 3])
                    ->exists();
                if ($hasBookedSubProducts) {
                    return redirect()->back()->with('error', __('Cannot delete product. One or more sub-products are booked.'));
                }

                // Check if product has stock
                $productQuantity = $productService->quantity ?? 0;
                
                // Check total quantity from SubProducts
                $subProductQuantity = \App\Models\SubProduct::where('product_id', $id)->sum('quantity') ?? 0;
                
                // Check total quantity from WarehouseProducts
                $warehouseProductQuantity = \App\Models\WarehouseProduct::where('product_id', $id)->sum('quantity') ?? 0;
                
                // Total stock
                $totalStock = $productQuantity + $subProductQuantity + $warehouseProductQuantity;
                
                // Prevent deletion if product has stock
                if ($totalStock > 0) {
                    return redirect()->back()->with('error', __('Cannot delete product. Product has stock available. Please remove all stock before deleting. (Stock: ' . number_format($totalStock, 2) . ')'));
                }
                
                if (!empty($productService->pro_image)) {
                    //storage limit
                    $file_path = '/uploads/pro_image/' . $productService->pro_image;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                }

                foreach ($productService->images as $image) {
                    $image->delete();
                }

                $productService->delete();

                return redirect()->route('productservice.index')->with('success', __('Product successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Save additional gallery files (product_images[]) using the same storage pipeline as pro_image.
     */
    protected function appendProductGallery(Request $request, ProductService $product): void
    {
        $files = $request->file('product_images', []);
        if ($files === null) {
            return;
        }
        if (! is_array($files)) {
            $files = [$files];
        }

        $sortBase = (int) $product->images()->max('sort_order');
        $userId = \Auth::user()->creatorId();
        $dir = 'uploads/pro_image';

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $imageSize = $file->getSize();
            if (Utility::updateStorageLimit($userId, $imageSize) != 1) {
                continue;
            }
            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'image';
            $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg'));
            $fileName = 'ps_'.$product->id.'_'.uniqid('', true).'_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $base).'.'.$ext;

            $subRequest = Request::create('/', 'POST', [], [], ['pro_image' => $file]);
            $res = Utility::upload_file($subRequest, 'pro_image', $fileName, $dir, []);
            if (($res['flag'] ?? 0) != 1) {
                continue;
            }
            $sortBase++;
            ProductServiceImage::create([
                'product_service_id' => $product->id,
                'file_name' => $fileName,
                'sort_order' => $sortBase,
            ]);
        }
    }

    /**
     * If no primary pro_image is set, use the first gallery file as the main thumbnail.
     */
    protected function syncPrimaryImageFromGallery(ProductService $product): void
    {
        $product->refresh();
        if (! empty($product->pro_image)) {
            return;
        }
        $first = $product->images()->orderBy('sort_order')->orderBy('id')->first();
        if ($first) {
            $product->pro_image = $first->file_name;
            $product->saveQuietly();
        }
    }

    public function export()
    {
        $name = 'product_service_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ProductServiceExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('productservice.import');
    }

    /**
     * Modal form: import sub-product stock using parent product SKU (posts to subproductservice.import).
     */
    public function stockSubProductImportFile()
    {
        if (! \Auth::user()->can('create product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('productservice.stock_subproduct_import');
    }

    /**
     * Sample XLSX for product import (category, brand, model by name).
     */
    public function downloadProductImportSample()
    {
        if (! \Auth::user()->can('manage product & service')) {
            abort(403, __('Permission denied.'));
        }

        $export = new class implements FromArray, WithHeadings
        {
            public function headings(): array
            {
                return [
                    'name',
                    'sku',
                    'sale_price',
                    'purchase_price',
                    'quantity',
                    'tax',
                    'category_name',
                    'brand_name',
                    'model_name',
                    'unit_name',
                    'type',
                    'description',
                ];
            }

            public function array(): array
            {
                return [
                    [
                        'Example product',
                        'SKU-EXAMPLE-001',
                        50000,
                        45000,
                        0,
                        'VAT 5%',
                        'Electronics',
                        'Toyota',
                        'Camry',
                        'Piece',
                        'product',
                        'Replace names with your category brand and model',
                    ],
                ];
            }
        };

        return Excel::download($export, 'sample-product-import.xlsx');
    }

    /**
     * Sample XLSX for sub-product / stock import (parent product_sku, initial rate & stock).
     */
    public function downloadStockSubproductImportSample()
    {
        if (! \Auth::user()->can('create product & service')) {
            abort(403, __('Permission denied.'));
        }

        $creatorId = Auth::user()->creatorId();

        $warehouseNameSample = warehouse::where('created_by', $creatorId)
            ->orderBy('id')
            ->value('name');

        $headings = [
            'product_sku',
            'product_no',
            'initial_rate',
            'initial_stock',
            'sale_price',
            'purchase_price',
            'warehouse_name',
        ];

        $row = [
            'REPLACE_WITH_PARENT_SKU',
            'SUB-PART-001',
            100,
            5,
            120,
            90,
            $warehouseNameSample !== null && $warehouseNameSample !== ''
                ? $warehouseNameSample
                : 'REPLACE_WITH_YOUR_WAREHOUSE_NAME',
        ];

        $subProductCustomFields = CustomField::where('created_by', $creatorId)
            ->where('module', 'sub-product')
            ->orderBy('name')
            ->get()
            ->unique(fn (CustomField $f) => strtolower(trim($f->name)));

        foreach ($subProductCustomFields as $field) {
            $headings[] = $field->name;
            $row[] = $this->sampleSubProductCustomFieldCellValue($field);
        }

        $export = new class($headings, $row) implements FromArray, WithHeadings
        {
            public function __construct(
                private array $headings,
                private array $row
            ) {
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function array(): array
            {
                return [$this->row];
            }
        };

        return Excel::download($export, 'sample-stock-subproduct.xlsx');
    }

    /**
     * Example cell for a sub-product custom field on the stock import sample sheet.
     */
    private function sampleSubProductCustomFieldCellValue(CustomField $field): string
    {
        switch ($field->type) {
            case 'number':
                return '0';
            case 'date':
                return now()->format('Y-m-d');
            case 'email':
                return 'example@example.com';
            case 'dropdown':
                return $this->firstDropdownOptionSampleForStockSheet($field->options);
            case 'textarea':
                return __('Short example text');
            default:
                return __('Example value');
        }
    }

    /**
     * @param  string|null  $optionsJson  JSON from custom_fields.options
     */
    private function firstDropdownOptionSampleForStockSheet(?string $optionsJson): string
    {
        if ($optionsJson === null || $optionsJson === '') {
            return '';
        }

        $decoded = json_decode($optionsJson, true);
        if (! is_array($decoded)) {
            return '';
        }

        $list = $decoded['options'] ?? null;
        if ($list === null && array_is_list($decoded)) {
            $list = $decoded;
        }

        if (! is_array($list) || $list === []) {
            return '';
        }

        $first = reset($list);

        return is_scalar($first) ? (string) $first : '';
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Log::info('Starting product import', [
                'user_id' => Auth::user()->id,
                'creator_id' => Auth::user()->creatorId(),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize()
            ]);

            // Run import synchronously for debugging
            Excel::import(new ProductServiceImport(Auth::user()->creatorId()), $request->file('file'));

            \Log::info('Product import completed successfully');

            return redirect()->back()->with('success', 'Products imported successfully!');
        } catch (\Exception $e) {
            \Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show stock import form
     */
    public function stockImportFile()
    {
        if (\Auth::user()->can('create product & service')) {
            return view('productservice.stock_import');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Import stock (brands, sub-brands, products, sub-products with custom fields)
     */
    public function stockImport(Request $request)
    {
        if (!\Auth::user()->can('create product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            // Check if user wants to use queue or process synchronously
            $useQueue = $request->has('use_queue') && $request->input('use_queue') == '1';
            
            \Log::info('Starting stock import', [
                'user_id' => Auth::user()->id,
                'creator_id' => Auth::user()->creatorId(),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize(),
                'use_queue' => $useQueue
            ]);

            // Create import instance
            try {
                $import = new StockImport(Auth::user()->creatorId());
                \Log::info('StockImport instance created successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to create StockImport instance', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Failed to initialize import: ' . $e->getMessage());
            }

            if ($useQueue) {
                // Check if queue connection is set to sync (which would process immediately)
                $queueConnection = config('queue.default');
                if ($queueConnection === 'sync') {
                    \Log::warning('Queue connection is set to sync, but user requested queue mode', [
                        'queue_connection' => $queueConnection
                    ]);
                    return redirect()->back()->with('error', __('Queue is set to sync mode. Please configure a proper queue connection (database, redis, etc.) in your .env file (QUEUE_CONNECTION) to use queue mode.'));
                }
                
                // Queue mode: Store file and process in background
                // Store the file temporarily so it can be accessed by the queue worker
                $filePath = $request->file('file')->store('imports', 'local');
                
                // Get the absolute path using Storage
                $fullPath = Storage::disk('local')->path($filePath);
                
                // Use realpath to get canonical absolute path (resolves symlinks and normalizes separators)
                $fullPath = realpath($fullPath);
                
                if (!$fullPath || !file_exists($fullPath)) {
                    // Fallback: construct path manually if realpath fails
                    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath));
                    
                    if (!file_exists($fullPath)) {
                        \Log::error('File not found for import', [
                            'file_path' => $filePath,
                            'storage_path' => storage_path('app'),
                            'constructed_path' => $fullPath,
                            'file_exists' => file_exists($fullPath)
                        ]);
                        throw new \Exception("File not found. Please try uploading again.");
                    }
                }

                \Log::info('File stored for queued import', [
                    'file_path' => $filePath,
                    'full_path' => $fullPath,
                    'file_size' => filesize($fullPath),
                    'file_exists' => file_exists($fullPath),
                    'queue_connection' => $queueConnection
                ]);

                // Queue the import job - it will process in the background
                try {
                    ImportStockFromExcelJob::dispatch($filePath, Auth::user()->creatorId())
                        ->onQueue('default');
                    
                    \Log::info('Stock import queued successfully', [
                        'file_path' => $filePath,
                        'creator_id' => Auth::user()->creatorId(),
                        'queue_connection' => $queueConnection
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to queue stock import job', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'file_path' => $filePath,
                        'creator_id' => Auth::user()->creatorId()
                    ]);
                    throw new \Exception('Failed to queue import: ' . $e->getMessage());
                }

                return redirect()->back()->with('success', __('Stock import has been queued and will process in the background. Make sure the queue worker is running: php artisan queue:work'));
            } else {
                // Sync mode: Process immediately
                // Increase memory limit and execution time for large imports
                ini_set('memory_limit', '2048M'); // 2GB memory limit for large imports
                set_time_limit(0); // No execution time limit
                ini_set('max_execution_time', '0'); // Ensure no timeout
                
                // Temporarily set queue to sync to ensure import runs synchronously
                $originalQueueConnection = config('queue.default');
                config(['queue.default' => 'sync']);
                
                \Log::info('Processing stock import synchronously', [
                    'creator_id' => Auth::user()->creatorId(),
                    'file_name' => $request->file('file')->getClientOriginalName()
                ]);

                try {
                    // Process import immediately - force sync mode
                    Excel::import($import, $request->file('file'));
                } finally {
                    // Restore original queue connection
                    config(['queue.default' => $originalQueueConnection]);
                }

                // Commit any pending batch transaction
                $import->commitPendingBatch();

                // Check for errors and throw if any exist
                $import->throwIfHasErrors();

                // If we reach here, import was successful
                $successMessage = __('Stock imported successfully!');
                if ($import->getSuccessCount() > 0) {
                    $successMessage .= ' ' . __('Successfully imported :count row(s).', ['count' => $import->getSuccessCount()]);
                }

                if ($import->getFailCount() > 0) {
                    $successMessage .= ' ' . __('Failed :count row(s).', ['count' => $import->getFailCount()]);
                }

                \Log::info('Stock import completed successfully', [
                    'success_count' => $import->getSuccessCount(),
                    'fail_count' => $import->getFailCount()
                ]);

                return redirect()->back()->with('success', $successMessage);
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Stock import validation failed', [
                'errors' => $e->failures()
            ]);

            $errorMessage = __('Import validation failed:') . "\n";
            foreach ($e->failures() as $failure) {
                $errorMessage .= "Row {$failure->row()}: " . implode(', ', $failure->errors()) . "\n";
            }

            return redirect()->back()->with('error', $errorMessage);
        } catch (\Maatwebsite\Excel\Exceptions\SheetNotFoundException $e) {
            \Log::error('Stock import - sheet not found', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect()->back()->with('error', __('Import failed: Sheet not found in the file.'));
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            \Log::error('Stock import - file read error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect()->back()->with('error', __('Import failed: Could not read the file. Please check the file format.'));
        } catch (\Exception $e) {
            \Log::error('Stock import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'use_queue' => $request->input('use_queue', 'not set')
            ]);

            $errorMessage = __('Import failed: ') . $e->getMessage();
            
            // If the exception has a previous exception, include its message
            if ($e->getPrevious()) {
                $errorMessage .= "\n" . __('Previous error: ') . $e->getPrevious()->getMessage();
            }
            
            // Add helpful message based on mode
            if ($request->has('use_queue') && $request->input('use_queue') == '1') {
                $errorMessage .= "\n\n" . __('Make sure the queue worker is running: php artisan queue:work');
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Show item master import form
     */
    public function sparePartsStockImportFile()
    {
        if (\Auth::user()->can('create product & service')) {
            return view('productservice.spare_parts_stock_import');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Generate and download sample item master Excel file with custom fields
     */
    public function downloadSparePartsStockSample()
    {
        if (!\Auth::user()->can('create product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $creatorId = \Auth::user()->creatorId();
            
            // Get custom fields for sub-product module (spare parts use sub-product custom fields)
            $customFields = CustomField::where('created_by', $creatorId)
                ->where('module', 'sub-product')
                ->orderBy('id', 'asc')
                ->get();
            
            // Also get product module custom fields and merge them
            $productCustomFields = CustomField::where('created_by', $creatorId)
                ->where('module', 'product')
                ->orderBy('id', 'asc')
                ->get();
            
            // Merge both sets of custom fields
            $customFields = $customFields->merge($productCustomFields);

            // Exclude from sample: custom fields named "Container NO" or "DEC NO"
            $excludedSampleNames = ['Container NO', 'DEC NO'];
            $customFieldsForSample = $customFields->filter(function ($field) use ($excludedSampleNames) {
                $name = trim($field->name ?? '');
                return !empty($name) && !in_array($name, $excludedSampleNames, true);
            })->values();
            
            \Log::info('Generating item master sample file', [
                'creator_id' => $creatorId,
                'custom_fields_count' => $customFields->count(),
                'custom_fields' => $customFields->pluck('name')->toArray(),
                'custom_fields_in_sample' => $customFieldsForSample->pluck('name')->toArray()
            ]);
            
            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers - standard columns first
            $headers = [
                'part no',
                'description',
                'GROUPE',
                'PARTS TYPE',
                'BRAND'
            ];
            
            // Add custom field headers (excluding Container NO & DEC NO)
            $customFieldNames = [];
            foreach ($customFieldsForSample as $field) {
                $fieldName = trim($field->name);
                if (!empty($fieldName)) {
                    $headers[] = $fieldName;
                    $customFieldNames[] = $fieldName;
                }
            }
            
            // If no custom fields to show in sample, add example columns for demonstration
            if ($customFieldsForSample->isEmpty()) {
                $headers[] = 'Model'; // Example custom field 1
                $headers[] = 'Color'; // Example custom field 2
                $customFieldNames = ['Model', 'Color'];
                \Log::info('No custom fields found, adding example columns: Model, Color');
            }
            
            // Log headers for debugging
            \Log::info('Sample file headers generated', [
                'headers' => $headers,
                'total_columns' => count($headers),
                'standard_columns' => 5,
                'custom_fields_count' => $customFieldsForSample->count(),
                'custom_field_names' => $customFieldNames
            ]);
            
            // Write headers
            $colIndex = 0;
            foreach ($headers as $header) {
                $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($col . '1', $header);
                $colIndex++;
            }
            
            // Get the last column letter for styling
            $lastColLetter = Coordinate::stringFromColumnIndex(count($headers));
            
            // Add sample data rows
            $sampleData = [
                [
                    'PN001',
                    'Brake Pad Set',
                    'Brake Parts',
                    'Standard',
                    'ACME'
                ],
                [
                    'PN002',
                    'Oil Filter',
                    'Engine Parts',
                    'Premium',
                    'ACME'
                ],
                [
                    'PN003',
                    'Air Filter',
                    'Engine Parts',
                    'Standard',
                    'ACME'
                ],
                [
                    'PN004',
                    'Spark Plug',
                    'Engine Parts',
                    'Premium',
                    'ACME'
                ],
                [
                    'PN005',
                    'Windshield Wiper',
                    'Body Parts',
                    'Standard',
                    'ACME'
                ],
            ];
            
            // Prepare sample data for custom fields (same set as headers: exclude Container NO & DEC NO)
            $customFieldSampleData = [];
            if ($customFieldsForSample->isEmpty()) {
                // Example data if no custom fields to show in sample
                $customFieldSampleData = [
                    ['BP-2024', 'Black'],
                    ['OF-2024', 'White'],
                    ['AF-2024', 'Red'],
                    ['SP-2024', 'Blue'],
                    ['WW-2024', 'Gray']
                ];
            } else {
                // Generate sample data for each custom field in sample
                for ($i = 0; $i < count($sampleData); $i++) {
                    $rowCustomData = [];
                    foreach ($customFieldsForSample as $field) {
                        // Add sample data based on field type or leave empty
                        $rowCustomData[] = ''; // Empty by default, user will fill
                    }
                    $customFieldSampleData[] = $rowCustomData;
                }
            }
            
            $row = 2;
            $dataIndex = 0;
            foreach ($sampleData as $data) {
                $colIndex = 0;
                // Write standard data (5 columns: part no, description, GROUPE, PARTS TYPE, BRAND)
                foreach ($data as $index => $value) {
                    $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                    $sheet->setCellValue($col . $row, $value);
                    $colIndex++;
                }
                
                // Add custom field values
                $customFieldCount = $customFieldsForSample->isEmpty() ? 2 : $customFieldsForSample->count(); // 2 example columns if none in sample
                if (isset($customFieldSampleData[$dataIndex])) {
                    foreach ($customFieldSampleData[$dataIndex] as $customValue) {
                        $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                        $sheet->setCellValue($col . $row, $customValue);
                        $colIndex++;
                    }
                } else {
                    // Fallback: add empty cells for custom fields
                    for ($i = 0; $i < $customFieldCount; $i++) {
                        $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                        $sheet->setCellValue($col . $row, '');
                        $colIndex++;
                    }
                }
                
                $dataIndex++;
                $row++;
            }
            
            // Auto-size columns
            for ($i = 1; $i <= count($headers); $i++) {
                $col = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Style header row
            $headerRange = 'A1:' . $lastColLetter . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Create writer and download
            $writer = new Xlsx($spreadsheet);
            $fileName = 'sample-spare-parts-stock-' . date('Y-m-d') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            \Log::error('Failed to generate item master sample file', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return redirect()->back()->with('error', __('Failed to generate sample file: ') . $e->getMessage());
        }
    }

    /**
     * Import item master
     */
    public function sparePartsStockImport(Request $request)
    {
        if (!\Auth::user()->can('create product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Log::info('Starting item master import', [
                'user_id' => Auth::user()->id,
                'creator_id' => Auth::user()->creatorId(),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize()
            ]);

            // Process import synchronously
            ini_set('memory_limit', '2048M');
            set_time_limit(0);
            ini_set('max_execution_time', '0');

            $import = new SparePartsStockImport(Auth::user()->creatorId());
            Excel::import($import, $request->file('file'));

            $successMessage = __('Item master imported successfully!');
            if ($import->getSuccessCount() > 0) {
                $successMessage .= ' ' . __('Successfully imported :count row(s).', ['count' => $import->getSuccessCount()]);
            }

            if ($import->getFailCount() > 0) {
                $successMessage .= ' ' . __('Failed :count row(s).', ['count' => $import->getFailCount()]);
                if ($import->hasErrors()) {
                    $allErrors = $import->getErrors();
                    \Log::warning('Spare parts import completed with errors', [
                        'errors' => $allErrors,
                        'error_count' => count($allErrors)
                    ]);
                    
                    // Format error message for display
                    $errorCount = count($allErrors);
                    $maxErrorsToShow = 20; // Show first 20 errors in toastr
                    
                    // Create HTML formatted error message for better display
                    $errorMessage = '<strong>' . __('The following errors occurred during import:') . '</strong><br><br>';
                    $errorMessage .= '<div style="max-height: 400px; overflow-y: auto; text-align: left;">';
                    $errorMessage .= '<ul style="margin-bottom: 0;">';
                    
                    foreach (array_slice($allErrors, 0, $maxErrorsToShow) as $error) {
                        $errorMessage .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($error) . '</li>';
                    }
                    
                    $errorMessage .= '</ul>';
                    
                    if ($errorCount > $maxErrorsToShow) {
                        $errorMessage .= '<p style="margin-top: 10px; font-weight: bold;">' . __('... and :count more error(s).', ['count' => $errorCount - $maxErrorsToShow]) . '</p>';
                    }
                    
                    $errorMessage .= '</div>';
                    
                    // Return with both success (partial) and error messages
                    return redirect()->back()
                        ->with('success', $successMessage)
                        ->with('error', $errorMessage)
                        ->with('import_errors', $allErrors) // Store all errors for detailed view if needed
                        ->with('import_error_count', $errorCount);
                }
            }

            \Log::info('Item master import completed', [
                'success_count' => $import->getSuccessCount(),
                'fail_count' => $import->getFailCount()
            ]);

            return redirect()->back()->with('success', $successMessage);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Item master import validation failed', [
                'errors' => $e->failures()
            ]);

            $errorMessage = __('Import validation failed:') . "\n";
            foreach ($e->failures() as $failure) {
                $errorMessage .= "Row {$failure->row()}: " . implode(', ', $failure->errors()) . "\n";
            }

            return redirect()->back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            \Log::error('Item master import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', __('Import failed: ') . $e->getMessage());
        }
    }

    public function warehouseDetail($id)
    {
        $products = WarehouseProduct::with(['warehouse'])->where('product_id', '=', $id)->where('created_by', '=', \Auth::user()->creatorId())->get();
        return view('productservice.detail', compact('products'));
    }

    public function searchProducts(Request $request)
    {

        $lastsegment = $request->session_key;
        // Allow access if user has 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission && $request->ajax() && isset($lastsegment) && !empty($lastsegment)) {

            $output = "";
            $WHID = $request->war_id;

            // Validate warehouse ID
            if (empty($WHID) || $WHID == '0' || $WHID == null) {
                // Try to get first warehouse for the user's company
                $firstWarehouse = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())->first();
                if ($firstWarehouse) {
                    $WHID = $firstWarehouse->id;
                } else {
                    // Fallback to 1 if no warehouse found (for backward compatibility)
                    $WHID = 1;
                }
            }
            
            // Ensure WHID is an integer
            $WHID = (int) $WHID;

            $search = $request->search;
            $cat_id = $request->cat_id;
            $creatorId = \Auth::user()->creatorId();
            
            // Verify warehouse exists and user has access to it
            $warehouse = \App\Models\warehouse::find($WHID);
            if (!$warehouse) {
                Log::warning('POS: Warehouse not found', ['warehouse_id' => $WHID]);
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("Warehouse not found") . '</h5>
                    </div>';
                return Response($output);
            }
            
            // Check if user has access to this warehouse
            $user = \Auth::user();
            $hasWarehouseAccess = false;
            
            // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
            // If a warehouse is explicitly assigned to a user, they should have access regardless of who created it
            $isAssigned = DB::table('user_warehouses')
                ->where('user_id', $user->id)
                ->where('warehouse_id', $WHID)
                ->exists();
            
            if ($isAssigned) {
                $hasWarehouseAccess = true;
                Log::info('POS: Warehouse access granted via assignment', [
                    'user_id' => $user->id,
                    'warehouse_id' => $WHID,
                    'warehouse_name' => $warehouse->name
                ]);
            }
            
            // Option 2: Warehouse belongs to the same company (if not assigned, check company ownership)
            if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                $hasWarehouseAccess = true;
                Log::info('POS: Warehouse access granted via company ownership', [
                    'user_id' => $user->id,
                    'warehouse_id' => $WHID,
                    'warehouse_created_by' => $warehouse->created_by,
                    'user_creator_id' => $creatorId
                ]);
            }
            
            // Option 3: User is company type (super admin/company) - they have access to all company warehouses
            if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                // Company users have access to all warehouses created by their company
                if ($warehouse->created_by == $creatorId) {
                    $hasWarehouseAccess = true;
                }
            }
            
            if (!$hasWarehouseAccess) {
                Log::warning('POS: User does not have access to warehouse, attempting to use first assigned warehouse', [
                    'requested_warehouse_id' => $WHID,
                    'warehouse_name' => $warehouse->name,
                    'warehouse_created_by' => $warehouse->created_by,
                    'user_id' => $user->id,
                    'user_creator_id' => $creatorId,
                    'user_type' => $user->type,
                    'assigned_warehouses' => $user->warehouses()->pluck('warehouses.id')->toArray()
                ]);
                
                // If user has assigned warehouses, use the first one instead
                $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouses)) {
                    $WHID = $assignedWarehouses[0];
                    $warehouse = \App\Models\warehouse::find($WHID);
                    if ($warehouse) {
                        Log::info('POS: Using first assigned warehouse instead', [
                            'user_id' => $user->id,
                            'new_warehouse_id' => $WHID,
                            'warehouse_name' => $warehouse->name
                        ]);
                        // Continue with the corrected warehouse
                    } else {
                        $output = '<div class="card card-body col-12 text-center">
                            <h5>' . __("You do not have access to this warehouse and no valid warehouse found") . '</h5>
                            </div>';
                        return Response($output);
                    }
                } else {
                    // No assigned warehouses - deny access
                    $output = '<div class="card card-body col-12 text-center">
                        <h5>' . __("You do not have access to this warehouse") . '</h5>
                        </div>';
                    return Response($output);
                }
            }
            
            // Debug: Log the query parameters
            Log::info('POS searchProducts', [
                'warehouse_id' => $WHID,
                'warehouse_name' => $warehouse->name,
                'creator_id' => $creatorId,
                'search' => $search,
                'cat_id' => $cat_id,
                'session_key' => $lastsegment
            ]);
            
            // Optimized: Get aggregated quantities and latest SubProduct IDs in a single query
            // Exclude products with 0 quantity and sub-products with flag = 0 (ordered / not yet purchased)
            $products = SubProduct::select(
                    'sub_products.chassis_no',
                    DB::raw('SUM(sub_products.quantity) as total_quantity'),
                    DB::raw('MAX(sub_products.id) as latest_id')
                )
                ->where('sub_products.warehouse_id', $WHID)
                ->where('sub_products.created_by', $creatorId)
                ->where('sub_products.flag', '!=', SubProduct::FLAG_ORDERED)
                ->groupBy('sub_products.chassis_no')
                ->havingRaw('SUM(sub_products.quantity) > 0') // Exclude products with 0 quantity
                ->get();

            // Debug: Log products found
            Log::info('POS SubProducts found', [
                'count' => $products->count(),
                'product_nos' => $products->pluck('product_no')->toArray()
            ]);

            // Get latest SubProduct IDs
            $latestIds = $products->pluck('latest_id')->filter()->unique();

            // If no products found, return early
            if ($latestIds->isEmpty()) {
                Log::info('POS: No SubProducts found for warehouse', ['warehouse_id' => $WHID, 'creator_id' => $creatorId]);
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("No Product Available") . '</h5>
                    </div>';
                return Response($output);
            }

            // Eager load all required relationships in one query
            // Don't filter productService in eager loading - filter after loading instead
            $latestSubProducts = SubProduct::with(['productService', 'productService.unit', 'priceRule'])
                ->whereIn('id', $latestIds)
                ->where('warehouse_id', $WHID)
                ->where('created_by', $creatorId)
                ->where('flag', '!=', SubProduct::FLAG_ORDERED)
                ->get()
                ->keyBy('id');

            // Build result array efficiently
            $grouped = $products->map(function ($item) use ($latestSubProducts, $creatorId) {
                $latestSubProduct = $latestSubProducts->get($item->latest_id);
                
                if (!$latestSubProduct || !$latestSubProduct->productService) {
                    return null;
                }
                
                // Ensure ProductService belongs to the same creator
                if ($latestSubProduct->productService->created_by != $creatorId) {
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

                // Exclude products with 0 quantity
                if ($item->total_quantity <= 0) {
                    return null;
                }

                return [
                    'product_no'     => $item->product_no,
                    'total_quantity' => $item->total_quantity,
                    'sale_price'     => $salePrice,
                    'product'        => $latestSubProduct->productService,
                ];
            })->filter();

            // Apply search filter if provided
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $grouped = $grouped->filter(function ($item) use ($searchLower) {
                    return $item && $item['product'] && str_contains(strtolower($item['product']->name), $searchLower);
                });
            }

            // Apply category filter if provided
            if ($cat_id != '0' && !empty($cat_id)) {
                $grouped = $grouped->filter(function ($item) use ($cat_id) {
                    return $item && $item['product'] && $item['product']->category_id == $cat_id;
                });
            }

            $grouped = $grouped->values();


            // return response()->json($subproducts);

            // Result is a collection of grouped, enriched data
            // return Response($final);
            // $wareH = warehouse::find(1);
            
            // Performance: Limit products displayed to improve performance
            $displayLimit = 200;
            $totalProducts = count($grouped);
            $grouped = $grouped->take($displayLimit);
            
            if (count($grouped) > 0) {
                foreach ($grouped as $key => $product) {
                    $quantity = $product['total_quantity'];
                    $productService = $product['product'];

                    // Unit is already loaded via eager loading, no need for another query
                    $unitName = $productService->unit ? $productService->unit->name : '';

                    // Optimized: Simplified HTML without images for better performance
                    $output .= '
                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-2">
                        <div class="toacart card h-100 cursor-pointer" data-url="' . url('add-to-cart/' . (string)$WHID . '/' . $product['product_no'] . '/' . $lastsegment) . '" style="transition: all 0.2s;">
                            <div class="card-body p-2">
                                <h6 class="mb-1 text-dark product-title-name" style="font-size: 0.9rem; line-height: 1.3; min-height: 2.6em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">' . e($productService->name) . '</h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-primary">' . Auth::user()->priceFormat($product['sale_price']) . '</span>
                                    <span class="badge bg-danger">' . $quantity . ' ' . e($unitName) . '</span>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
                
                // Show message if there are more products than the limit
                if ($totalProducts > $displayLimit) {
                    $output .= '<div class="col-12 mt-2 mb-2">
                        <div class="alert alert-info text-center">
                            <small>' . __('Showing') . ' ' . $displayLimit . ' ' . __('of') . ' ' . $totalProducts . ' ' . __('products. Use search to find specific products.') . '</small>
                        </div>
                    </div>';
                }

                return Response($output);
            } else {
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("No Product Available") . '</h5>
                    </div>';
                return Response($output);
            }
        } else {
            // Permission denied or invalid request
            Log::warning('POS searchProducts: Permission denied or invalid request', [
                'has_permission' => $hasPosPermission ?? false,
                'is_ajax' => $request->ajax(),
                'has_session_key' => isset($lastsegment) && !empty($lastsegment),
                'user_id' => \Auth::id()
            ]);
            $output = '<div class="card card-body col-12 text-center">
                <h5>' . __("Permission denied or invalid request") . '</h5>
                </div>';
            return Response($output);
        }
    }

    public function searchBarcode(Request $request)
    {

        $lastsegment = $request->session_key;
        // Allow access if user has 'manage pos' or 'add pos' permission
        $hasPosPermission = Auth::user()->can('manage pos') || Auth::user()->can('add pos');
        if ($hasPosPermission && $request->ajax() && isset($lastsegment) && !empty($lastsegment)) {

            $output = "";
            $WHID = $request->war_id;

            // Validate warehouse ID
            if (empty($WHID) || $WHID == '0' || $WHID == null) {
                // Try to get first warehouse for the user's company
                $firstWarehouse = \App\Models\warehouse::where('created_by', \Auth::user()->creatorId())->first();
                if ($firstWarehouse) {
                    $WHID = $firstWarehouse->id;
                } else {
                    // Fallback to 1 if no warehouse found (for backward compatibility)
                    $WHID = 1;
                }
            }
            
            // Ensure WHID is an integer
            $WHID = (int) $WHID;

            $search = $request->search;
            $creatorId = \Auth::user()->creatorId();
            
            // Verify warehouse exists and user has access to it
            $warehouse = \App\Models\warehouse::find($WHID);
            if (!$warehouse) {
                Log::warning('POS searchBarcode: Warehouse not found', ['warehouse_id' => $WHID]);
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("Warehouse not found") . '</h5>
                    </div>';
                return Response($output);
            }
            
            // Check if user has access to this warehouse
            $user = \Auth::user();
            $hasWarehouseAccess = false;
            
            // Option 1: Warehouse is assigned to the user via pivot table (highest priority)
            $isAssigned = DB::table('user_warehouses')
                ->where('user_id', $user->id)
                ->where('warehouse_id', $WHID)
                ->exists();
            
            if ($isAssigned) {
                $hasWarehouseAccess = true;
            }
            
            // Option 2: Warehouse belongs to the same company
            if (!$hasWarehouseAccess && $warehouse->created_by == $creatorId) {
                $hasWarehouseAccess = true;
            }
            
            // Option 3: User is company type
            if (!$hasWarehouseAccess && ($user->type == 'company' || $user->type == 'super admin')) {
                if ($warehouse->created_by == $creatorId) {
                    $hasWarehouseAccess = true;
                }
            }
            
            if (!$hasWarehouseAccess) {
                // If user has assigned warehouses, use the first one instead
                $assignedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
                if (!empty($assignedWarehouses)) {
                    $WHID = $assignedWarehouses[0];
                    $warehouse = \App\Models\warehouse::find($WHID);
                    if ($warehouse) {
                        Log::info('POS searchBarcode: Using first assigned warehouse instead', [
                            'user_id' => $user->id,
                            'new_warehouse_id' => $WHID
                        ]);
                    } else {
                        $output = '<div class="card card-body col-12 text-center">
                            <h5>' . __("You do not have access to this warehouse") . '</h5>
                            </div>';
                        return Response($output);
                    }
                } else {
                    $output = '<div class="card card-body col-12 text-center">
                        <h5>' . __("You do not have access to this warehouse") . '</h5>
                        </div>';
                    return Response($output);
                }
            }
            if ($search == '') {
                if ($request->cat_id == '0') {

                    $grouped = SubProduct::where('warehouse_id', $WHID)
                        ->where('created_by', $creatorId)
                        ->where('flag', '!=', SubProduct::FLAG_ORDERED)
                        ->select('chassis_no', DB::raw('SUM(quantity) as total_quantity'))
                        ->groupBy('chassis_no')
                        ->havingRaw('SUM(quantity) > 0') // Exclude products with 0 quantity
                        ->get()
                        ->map(function ($item) use ($WHID, $creatorId) {
                            // Exclude products with 0 quantity
                            if ($item->total_quantity <= 0) {
                                return null;
                            }
                            
                            // Get latest SubProduct for this product_no and warehouse
                            // Don't filter productService in eager loading - filter after loading
                            $latestSubProduct = SubProduct::with(['productService', 'productService.unit']) // eager load productService
                                ->where('warehouse_id', $WHID)
                                ->where('created_by', $creatorId)
                                ->where('flag', '!=', SubProduct::FLAG_ORDERED)
                                ->where('chassis_no', $item->chassis_no)
                                ->latest()
                                ->first();

                            // Ensure ProductService exists and belongs to the same creator
                            if (!$latestSubProduct || !$latestSubProduct->productService) {
                                return null;
                            }
                            
                            // Check creator after loading
                            if ($latestSubProduct->productService->created_by != $creatorId) {
                                return null;
                            }

                            return [
                                'product_no'     => $item->chassis_no,
                                'total_quantity' => $item->total_quantity,
                                'sale_price'     => $latestSubProduct->get_price_list_sale_price(),
                                'product' => $latestSubProduct->productService,

                            ];
                        })
                        ->filter();

                    // return response()->json($grouped);
                } else {
                    $cat_id = $request->cat_id;
                    // $products = ProductService::getallproducts()->whereIn('product_services.id',$sub_product_ids)->where('category_id', $request->cat_id)->with(['unit'])->get();
                    $grouped = SubProduct::where('warehouse_id', $WHID)
                        ->where('created_by', $creatorId)
                        ->where('flag', '!=', SubProduct::FLAG_ORDERED)
                        ->select('chassis_no', DB::raw('SUM(quantity) as total_quantity'))
                        ->groupBy('chassis_no')
                        ->havingRaw('SUM(quantity) > 0') // Exclude products with 0 quantity
                        ->get()
                        ->map(function ($item) use ($WHID, $creatorId) {
                            // Exclude products with 0 quantity
                            if ($item->total_quantity <= 0) {
                                return null;
                            }
                            
                            // Don't filter productService in eager loading - filter after loading
                            $subProduct = SubProduct::with(['productService', 'productService.unit'])
                                ->where('warehouse_id', $WHID)
                                ->where('created_by', $creatorId)
                                ->where('flag', '!=', SubProduct::FLAG_ORDERED)
                                ->where('chassis_no', $item->chassis_no)
                                ->latest()
                                ->first();
                            
                            if ($subProduct) {
                                $subProduct->append('total_quantity', $item->total_quantity);
                            }
                            
                            return $subProduct;
                        })
                        ->filter(function ($subProduct) use ($cat_id, $creatorId) {
                            return $subProduct && 
                                   $subProduct->productService && 
                                   $subProduct->productService->created_by == $creatorId &&
                                   $subProduct->productService->category_id == $cat_id;
                        })
                        ->map(function ($subProduct) {
                            // Double-check quantity is greater than 0
                            if ($subProduct->total_quantity <= 0) {
                                return null;
                            }
                            
                            return [
                                'product_no'     => $subProduct->chassis_no,
                                'total_quantity' => $subProduct->total_quantity,
                                'sale_price'     => $subProduct->get_price_list_sale_price(),
                                'product' => $subProduct->productService,
                            ];
                        })
                        ->filter()
                        ->values();

                    // return response()->json($grouped);

                }
            } else {
                // Optimized barcode search: Use direct database query instead of N+1 queries
                $cat_id = $request->cat_id;
                $search = trim($search);
                
                // Debug: Log barcode search parameters
                Log::info('POS searchBarcode: Searching for barcode', [
                    'barcode' => $search,
                    'warehouse_id' => $WHID,
                    'warehouse_name' => $warehouse->name ?? 'N/A',
                    'creator_id' => $creatorId,
                    'user_id' => $user->id
                ]);
                
                // Try exact match first, then fall back to LIKE
                // Search by product_no (SubProduct) or SKU (ProductService)
                // Exclude products with 0 quantity and flag = 0 (ordered / not yet purchased)
                $latestSubProductIds = SubProduct::select(DB::raw('MAX(sub_products.id) as id'))
                    ->join('product_services', 'sub_products.product_id', '=', 'product_services.id')
                    ->where('sub_products.warehouse_id', $WHID)
                    ->where('sub_products.created_by', $creatorId)
                    ->where('sub_products.flag', '!=', SubProduct::FLAG_ORDERED)
                    ->where('product_services.created_by', $creatorId)
                    ->where(function($query) use ($search) {
                        $query->where('sub_products.chassis_no', '=', $search) // Exact match first
                              ->orWhere('sub_products.chassis_no', 'LIKE', '%' . $search . '%') // Then partial match
                              ->orWhere('product_services.sku', '=', $search) // Or exact SKU match
                              ->orWhere('product_services.sku', 'LIKE', '%' . $search . '%'); // Or partial SKU match
                    })
                    ->whereNotNull('sub_products.chassis_no')
                    ->groupBy('sub_products.chassis_no')
                    ->havingRaw('SUM(sub_products.quantity) > 0') // Exclude products with 0 quantity
                    ->limit(50) // Limit results to prevent timeout
                    ->pluck('id');
                
                Log::info('POS searchBarcode: SubProduct IDs found', [
                    'count' => $latestSubProductIds->count(),
                    'ids' => $latestSubProductIds->toArray()
                ]);
                
                if ($latestSubProductIds->isEmpty()) {
                    Log::warning('POS searchBarcode: No SubProducts found for barcode', [
                        'barcode' => $search,
                        'warehouse_id' => $WHID,
                        'creator_id' => $creatorId
                    ]);
                    $grouped = collect([]);
                } else {
                    // Get quantities grouped by product_no (optimized)
                    // Match the same search criteria as above
                    // Exclude products with 0 quantity and flag = 0 (ordered / not yet purchased)
                    $quantities = SubProduct::select('sub_products.chassis_no', DB::raw('SUM(sub_products.quantity) as total_quantity'))
                        ->join('product_services', 'sub_products.product_id', '=', 'product_services.id')
                        ->where('sub_products.warehouse_id', $WHID)
                        ->where('sub_products.created_by', $creatorId)
                        ->where('sub_products.flag', '!=', SubProduct::FLAG_ORDERED)
                        ->where('product_services.created_by', $creatorId)
                        ->where(function($query) use ($search) {
                            $query->where('sub_products.chassis_no', '=', $search)
                                  ->orWhere('sub_products.chassis_no', 'LIKE', '%' . $search . '%')
                                  ->orWhere('product_services.sku', '=', $search)
                                  ->orWhere('product_services.sku', 'LIKE', '%' . $search . '%');
                        })
                        ->whereNotNull('sub_products.chassis_no')
                        ->groupBy('sub_products.chassis_no')
                        ->havingRaw('SUM(sub_products.quantity) > 0') // Exclude products with 0 quantity
                        ->pluck('total_quantity', 'chassis_no');
                    
                    // Eager load all relationships in a single query
                    // Don't filter productService in eager loading - filter after loading instead
                    $latestSubProducts = SubProduct::select('id', 'chassis_no', 'product_id', 'sale_price', 'purchase_price', 'price_rule_id')
                        ->with([
                            'productService:id,name,sku,category_id,created_by',
                            'productService.unit:id,name'
                        ])
                        ->whereIn('id', $latestSubProductIds)
                        ->where('created_by', $creatorId)
                        ->get()
                        ->keyBy('chassis_no');
                    
                    // Apply category filter if needed
                    if ($cat_id != '0') {
                        $latestSubProducts = $latestSubProducts->filter(function ($subProduct) use ($cat_id) {
                            return $subProduct->productService && $subProduct->productService->category_id == $cat_id;
                        });
                    }
                    
                    // Build grouped array efficiently
                    $grouped = $latestSubProducts->map(function ($subProduct) use ($quantities, $creatorId) {
                        if (!$subProduct->productService) {
                            Log::debug('POS searchBarcode: SubProduct has no productService', [
                                'subproduct_id' => $subProduct->id,
                                'product_no' => $subProduct->chassis_no
                            ]);
                            return null;
                        }
                        
                        // Ensure ProductService belongs to the same creator
                        if ($subProduct->productService->created_by != $creatorId) {
                            Log::debug('POS searchBarcode: ProductService creator mismatch', [
                                'subproduct_id' => $subProduct->id,
                                'product_no' => $subProduct->chassis_no,
                                'product_service_created_by' => $subProduct->productService->created_by,
                                'user_creator_id' => $creatorId
                            ]);
                            return null;
                        }
                        
                        // Get quantity and exclude if 0 or less
                        $quantity = $quantities[$subProduct->chassis_no] ?? 0;
                        if ($quantity <= 0) {
                            Log::debug('POS searchBarcode: Product has 0 quantity', [
                                'subproduct_id' => $subProduct->id,
                                'product_no' => $subProduct->chassis_no,
                                'quantity' => $quantity
                            ]);
                            return null;
                        }
                        
                        return [
                            'product_no'     => $subProduct->chassis_no,
                            'total_quantity' => $quantity,
                            'sale_price'     => $subProduct->get_price_list_sale_price(),
                            'product' => $subProduct->productService,
                        ];
                    })
                    ->filter()
                    ->values();
                    
                    Log::info('POS searchBarcode: Products found after filtering', [
                        'count' => $grouped->count(),
                        'product_nos' => $grouped->pluck('product_no')->toArray()
                    ]);
                }
            }


            // return response()->json($subproducts);

            // Result is a collection of grouped, enriched data
            // return Response($final);
            // $wareH = warehouse::find(1);
            
            // Performance: Limit products displayed to improve performance
            $displayLimit = 200;
            $totalProducts = count($grouped);
            $grouped = $grouped->take($displayLimit);
            
            if (count($grouped) > 0) {
                foreach ($grouped as $key => $product) {
                    $quantity = $product['total_quantity'];
                    $productService = $product['product']; // This is an object, not array
                    
                    // Get unit name from already loaded productService relationship (no additional query)
                    $unitName = '';
                    if ($productService && $productService->unit) {
                        $unitName = $productService->unit->name ?? '';
                    }

                    // Optimized: Simplified HTML without images for better performance
                    $output .= '
                    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-2">
                        <div class="toacart card h-100 cursor-pointer" data-url="' . url('add-to-cart/' . (string)$WHID . '/' . $product['product_no'] . '/' . $lastsegment) . '" style="transition: all 0.2s;">
                            <div class="card-body p-2">
                                <h6 class="mb-1 text-dark product-title-name" style="font-size: 0.9rem; line-height: 1.3; min-height: 2.6em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">' . e($productService->name) . '</h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-primary">' . Auth::user()->priceFormat($product['sale_price']) . '</span>
                                    <span class="badge bg-danger">' . $quantity . ' ' . e($unitName) . '</span>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
                
                // Show message if there are more products than the limit
                if ($totalProducts > $displayLimit) {
                    $output .= '<div class="col-12 mt-2 mb-2">
                        <div class="alert alert-info text-center">
                            <small>' . __('Showing') . ' ' . $displayLimit . ' ' . __('of') . ' ' . $totalProducts . ' ' . __('products. Use search to find specific products.') . '</small>
                        </div>
                    </div>';
                }

                return Response($output);
            } else {
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("No Product Available") . '</h5>
                    </div>';
                return Response($output);
            }
        }
    }

    public function generate_cart_html($P_num, $image_url, $productname, $productprice, $subtotal, $model_delete_id, $session_key, $compo_id, $tax_rate = 0, $priceWithVat = null)
    {
        // Use price_with_vat from sub_product if provided, otherwise calculate it
        if ($priceWithVat === null) {
            $priceWithVat = $productprice;
            if ($tax_rate > 0) {
                $priceWithVat = $productprice + ($productprice * ($tax_rate / 100));
            }
        }
        
        $html_code =  '<tr data-product-id="' . $P_num . '" id="product-id-' . $P_num . '">
                            <td class="cart-images">
                                <img alt="Image placeholder" src="' . asset(Storage::url($image_url)) . '" class="card-image avatar shadow hover-shadow-lg">
                            </td>
                            <td class="name">' . $productname . '</td>

                            <td class="">
                                   <span class="quantity buttons_added">
                                         <input type="button" value="-" class="minus">
                                         <input type="number" step="1" min="1"  name="quantity" title="' . __('Quantity') . '" class="input-number" size="4" data-url="' . url('update-cart/') . '" data-id="' . $P_num . '">
                                         <input type="button" value="+" class="plus">
                                   </span>
                            </td>

                            <td class="price text-right">
                                <div class="fw-bold">' . Auth::user()->priceFormat($productprice) . '</div>';
        // Always show VAT price as information if it's different from base price (VAT exists)
        if ($priceWithVat != $productprice && $priceWithVat > 0) {
            $html_code .= '<div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                <i class="ti ti-info-circle" style="font-size: 0.7rem;"></i> 
                                ' . Auth::user()->priceFormat($priceWithVat) . ' 
                                <span class="text-muted">(' . __('incl. VAT') . ')</span>
                            </div>';
        }
        $html_code .= '</td>
                            <td>
                                <span class="quantity buttons_added">
                                    <input type="number" step="1" min="0" max="100" name="discount" title="Discount"  class="input-number"  data-id="' . $P_num . '" size="4" value = "0" >
                                </span>
                            </td>

                            <td class="subtotal">' . Auth::user()->priceFormat($subtotal) . '</td>

                            <td class="">
                                 <a href="#" class="action-btn bg-danger bs-pass-para-pos" data-confirm="' . __("Are You Sure?") . '" data-text="' . __("This action can not be undone. Do you want to continue?") . '" data-confirm-yes=' . $model_delete_id . ' title="' . __('Delete') . '}" data-id="' . $P_num . '" title="' . __('Delete') . '"   >
                                   <span class=""><i class="ti ti-trash btn btn-sm text-white"></i></span>
                                 </a>
                                 <form method="post" action="' . url('remove-from-cart') . '"  accept-charset="UTF-8" id="' . $model_delete_id . '">
                                      <input name="_method" type="hidden" value="DELETE">
                                      <input name="_token" type="hidden" value="' . csrf_token() . '">
                                      <input type="hidden" name="session_key" value="' . $session_key . '">
                                      <input type="hidden" name="id" value="' . $P_num . '">
                                 </form>

                            </td>
                            <td class="combo">';
        if ($compo_id == 0) {
            $html_code .= '<span class="badge bg-secondary"> No combo </span>';
        } else {
            $html_code .= '<span class="badge bg-success">' . $compo_id . '</span>';
        }
        $html_code .= '</td></td>';
        return $html_code;
    }


    public function addToCart(Request $request, $W_id, $P_num, $session_key)
    {
        // Allow company users, users with 'manage product & service', or users with POS permissions
        $hasPermission = Auth::user()->type == 'company' || 
                        Auth::user()->can('manage product & service') || 
                        ($session_key == 'pos' && Auth::user()->can('manage pos'));
        
        if ($hasPermission && $request->ajax()) {
            // $product = ProductService::find($id);
            // $productquantity = 0;
            $wareH = warehouse::find($W_id);
            $productquantity = $wareH->getFreeQuantity($P_num);

            if (!$productquantity || ($session_key == 'pos' && $productquantity == 0)) {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }
            $subprod = SubProduct::where('warehouse_id', $W_id)
                ->where('chassis_no', $P_num)
                ->latest() // defaults to created_at desc
                ->first(); // executes the query and gets the latest record

            $product = ProductService::find($subprod->product_id);
            $productname = $product->name;

            if ($session_key == 'purchases') {
                $productprice = $product->purchase_price != 0 ? $product->purchase_price : 0;
            } else if ($session_key == 'pos') {
                // For POS, use sale_price from sub-product (sale_price WITHOUT VAT - e.g., 103.81)
                // This is the main selling price without VAT
                $productprice = $wareH->GetPrice($subprod->chassis_no);
            } else {
                $productprice = $wareH->GetPrice($subprod->chassis_no);
            }
            
            // Calculate price with VAT from product's tax_id
            // Start with base price (sale_price from sub-product)
            $priceWithVat = $productprice;
            
            // Calculate VAT from product's tax_id if it exists
            if ($product && !empty($product->tax_id) && $product->tax_id != '0') {
                $taxIds = explode(',', $product->tax_id);
                $totalVatRate = 0;
                foreach ($taxIds as $taxId) {
                    $taxId = trim($taxId);
                    if (!empty($taxId)) {
                        $tax = \App\Models\Tax::find($taxId);
                        if ($tax) {
                            $totalVatRate += (float) $tax->rate;
                        }
                    }
                }
                // Calculate price with VAT: basePrice * (1 + VAT/100)
                if ($totalVatRate > 0) {
                    $priceWithVat = $productprice * (1 + ($totalVatRate / 100));
                }
            }

            $originalquantity = (int)$productquantity;

            $taxes = Utility::tax($product->tax_id);

            $totalTaxRate = Utility::totalTaxRate($product->tax_id);

            $product_tax = '';
            $product_tax_id = [];
            foreach ($taxes as $tax) {
                $product_tax .= !empty($tax) ? "<span class='badge badge-primary'>" . $tax->name . ' (' . $tax->rate . '%)' . "</span><br>" : '';
                $product_tax_id[] = !empty($tax) ? $tax->id : 0;
            }

            if (empty($product_tax)) {
                $product_tax = "-";
            }
            $producttax = $totalTaxRate;
            $tax = ($productprice * $producttax) / 100;
            $tax = 0;
            $subtotal = $productprice;
            // dd($subtotal);
            $cart = session()->get($session_key);
            // return response()->json($cart);
            // $combos = session()->get('combos');
            // $image_url = (!empty($product->image) && Storage::exists($product->image)) ? $product->image : 'logo/placeholder.png';
            $image_url = (!empty($product->pro_image) && Storage::exists($product->pro_image)) ? $product->pro_image : 'uploads/pro_image/' . $product->pro_image;

            $model_delete_id = 'delete-form-' . $P_num;

            $carthtml = '';

            $carthtml .= $this->generate_cart_html($P_num, $image_url, $productname, $productprice, $subtotal, $model_delete_id, $session_key, 0, $totalTaxRate, $priceWithVat);

            // $compo_html = '';

            // if ($compo){
            //     if ($compo->type == 'bogo'){
            //         $compo_html .= '<tr data-product-id="' . $P_num . '" id="product-id-' . $P_num . '">
            //             <td class="cart-images">
            //                 <img alt="Image placeholder" src="' . asset(Storage::url($image_url)) . '" class="card-image avatar shadow hover-shadow-lg">
            //             </td>
            //             <td class="name">' . $productname . '</td>

            //             <td class="">
            //                 <span class="quantity buttons_added">
            //                     ' . $compo->get_quantity . '
            //                 </span>
            //             </td>

            //             <td class="price"> 0 </td>

            //             <td class="">
            //                 <span class="text-muted">' . __('Not editable') . '</span>
            //             </td>
            //         </tr>';
            //     }else{
            //         $compo_html .= '<tr data-product-id="compo_' . $P_num . '" id="compo_product_id_' . $P_num . '">
            //             <td class="cart-images">
            //                 <img alt="Image placeholder" src="' . asset(Storage::url($image_url)) . '" class="card-image avatar shadow hover-shadow-lg">
            //             </td>
            //             <td class="name">' . $productname . '</td>

            //             <td class="">
            //                 <span class="quantity buttons_added">
            //                     ' . $compo->buy_quantity . '
            //                 </span>
            //             </td>

            //             <td class="price">'. $compo->tiered_prices .'</td>

            //             <td class="">
            //                 <span class="text-muted">' . __('Not editable') . '</span>
            //             </td>
            //         </tr>';
            //     }
            // }

            // if cart is empty then this the first product
            if (!$cart) {
                $cart = [
                    $P_num => [
                        "name" => $productname,
                        "quantity" => 1,
                        "price" => $productprice,
                        "id" => $P_num,
                        "tax" => 0,
                        "discount" => 0,
                        "subtotal" => $subtotal,
                        "originalquantity" => $originalquantity,
                        "product_tax" => $product_tax,
                        "product_tax_id" => !empty($product_tax_id) ? implode(',', $product_tax_id) : 0,
                        "compo_id" => 0,
                    ],
                ];
                if ($originalquantity < $cart[$P_num]['quantity'] && $session_key == 'pos') {
                    return response()->json(
                        [
                            'code' => 404,
                            'status' => 'Error',
                            'error' => __('This product is out of stock!'),
                        ],
                        404
                    );
                }



                $creatorId = \Auth::user()->creatorId();
                // Check for combos: both old format (product_service_id) and new format (pivot table)
                $compo = ComboOffer::where('warehouse_id', $wareH->id)
                    ->where('created_by', $creatorId)
                    ->where('active', true)
                    ->whereRaw('(buy_quantity + COALESCE(get_quantity,0)) <= ?', [$cart[$P_num]["quantity"]])
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', Carbon::today());
                    })
                    ->where(function ($q) use ($product) {
                        // Old format: product_service_id matches
                        $q->where('product_service_id', $product->id)
                          // New format: product exists in pivot table
                          ->orWhereHas('products', function ($subQ) use ($product) {
                              $subQ->where('product_services.id', $product->id);
                          });
                    })
                    ->latest()
                    ->first();


                if ($compo) {
                    $cart[$P_num]['compo_id'] = $compo->id;
                    if ($compo->type == 'bogo') {
                        // $cart[$P_num]['quantity'] += $compo->get_quantity;
                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $cart[$P_num]['subtotal'] = $cart[$P_num]['quantity'] * $pprince;
                        // $cart[$P_num]['quantity'] += $compo->get_quantity;
                    } else {
                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                        $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                    }
                    // Get tax rate from product
                    $productForTax = ProductService::find($product->id);
                    $taxRateForCart = $productForTax ? Utility::totalTaxRate($productForTax->tax_id) : 0;
                    // Get price_with_vat from cart if available, otherwise null (will be calculated)
                    $priceWithVatForCombo = isset($cart[$P_num]['price_with_vat']) ? $cart[$P_num]['price_with_vat'] : null;
                    $carthtml = $this->generate_cart_html($P_num, $image_url, $productname, $cart[$P_num]['price'], $cart[$P_num]['subtotal'], $model_delete_id, $session_key, $compo->id, $taxRateForCart, $priceWithVatForCombo);
                }

                session()->put($session_key, $cart);

                $vouchers = session()->get('vouchers');
                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'Success',
                        'success' => $productname . __(' added to cart successfully!'),
                        'product' => $cart[$P_num],
                        'carthtml' => $carthtml,
                        'cart' => $cart,
                        'vouchers' => $vouchers,
                        // return response()->json($cart);

                    ]
                );
            }
            // return response()->json($cart);
            // if cart not empty then check if this product exist then increment quantity
            if (isset($cart[$P_num])) {
                $prev_quantity = $cart[$P_num]['quantity'];
                $cart[$P_num]['quantity']++;
                $cart[$P_num]['id'] = $P_num;
                $subtotal = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100))) * $cart[$P_num]["quantity"];
                $tax = 0;

                $cart[$P_num]["subtotal"] = $subtotal;
                $cart[$P_num]["originalquantity"] = $originalquantity;

                if ($originalquantity < $cart[$P_num]['quantity'] && $session_key == 'pos') {
                    return response()->json(
                        [
                            'code' => 404,
                            'status' => 'Error',
                            'error' => __('This product is out of stock!'),
                        ],
                        404
                    );
                }
                if ($cart[$P_num]['compo_id'] != 0) {

                    $compo = ComboOffer::find($cart[$P_num]['compo_id']);
                    if ($compo->type == 'bogo') {
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);

                        // if ($compo->buy_quantity == $not_combos && $originalquantity> $cart[$P_num]['quantity'] + $compo->get_quantity){
                        //     $cart[$P_num]['quantity'] += $compo->get_quantity;
                        // }
                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $composed = (int)($cart[$P_num]['quantity'] / ($compo->get_quantity + $compo->buy_quantity));
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);
                        $cart[$P_num]['subtotal'] = $composed * $compo->buy_quantity * $pprince + $not_combos * $pprince;
                    } else {
                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                        $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                    }
                } else {


                    $creatorId = \Auth::user()->creatorId();
                    // Check for combos: both old format (product_service_id) and new format (pivot table)
                    $compo = ComboOffer::where('warehouse_id', $wareH->id)
                        ->where('created_by', $creatorId)
                        ->where('active', true)
                        ->whereRaw('(buy_quantity + COALESCE(get_quantity,0)) <= ?', [$cart[$P_num]["quantity"]])
                        ->where(function ($q) {
                            $q->whereNull('valid_until')
                                ->orWhereDate('valid_until', '>=', Carbon::today());
                        })
                        ->where(function ($q) use ($product) {
                            // Old format: product_service_id matches
                            $q->where('product_service_id', $product->id)
                              // New format: product exists in pivot table
                              ->orWhereHas('products', function ($subQ) use ($product) {
                                  $subQ->where('product_services.id', $product->id);
                              });
                        })
                        ->latest()
                        ->first();


                    if ($compo) {
                        $cart[$P_num]['compo_id'] = $compo->id;

                        if ($compo->type == 'bogo') {
                            // if ($originalquantity >= $cart[$P_num]['quantity']){
                            //     // $cart[$P_num]['quantity'] += $compo->get_quantity;

                            //     $cart[$P_num]['quantity'] += $compo->get_quantity;
                            //     // $cart[$P_num]['subtotal'] = $not_combos * $pprince ;
                            // }

                            $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                            $composed = (int)($cart[$P_num]['quantity'] / ($compo->get_quantity + $compo->buy_quantity));
                            $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);
                            $cart[$P_num]['subtotal'] = $composed * $compo->buy_quantity * $pprince + $not_combos * $pprince;
                        } else {
                            $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                            $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                            $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                            $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                        }
                    }
                }

                session()->put($session_key, $cart);
                $vouchers = session()->get('vouchers');
                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'Success',
                        'success' => $productname . __(' added to cart successfully!'),
                        'product' => $cart[$P_num],
                        'carttotal' => $cart,
                        'vouchers' => $vouchers,
                    ]
                );
            }

            // if item not exist in cart then add to cart with quantity = 1
            $cart[$P_num] = [
                "name" => $productname,
                "quantity" => 1,
                "price" => $productprice,
                "price_with_vat" => $priceWithVat, // Store price with VAT from product_services.sale_price
                "tax" => 0,
                "discount" => 0,
                "subtotal" => $subtotal,
                "id" => $P_num,
                "originalquantity" => $originalquantity,
                "product_tax" => $product_tax,
                "product_tax_id" => !empty($product_tax_id) ? implode(',', $product_tax_id) : '0',
                "compo_id" => 0
            ];

            if ($originalquantity < $cart[$P_num]['quantity'] && $session_key == 'pos') {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }
            $creatorId = \Auth::user()->creatorId();
            // Check for combos: both old format (product_service_id) and new format (pivot table)
            // Note: Don't check quantity requirement here - let combo apply when quantity is updated
            // Multi-product combos are handled separately via checkMultiProduct method
            $compo = ComboOffer::where('warehouse_id', $wareH->id)
                ->where('created_by', $creatorId)
                ->where('active', true)
                ->where(function ($q) {
                    $q->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', Carbon::today());
                })
                ->where(function ($q) use ($product) {
                    // Old format: product_service_id matches
                    $q->where('product_service_id', $product->id)
                      // New format: product exists in pivot table
                      ->orWhereHas('products', function ($subQ) use ($product) {
                          $subQ->where('product_services.id', $product->id);
                      });
                })
                ->latest()
                ->first();
            
            // Only apply combo if quantity requirement is met (for single-product combos)
            // Multi-product combos are handled separately via checkMultiProduct method
            if ($compo && $cart[$P_num]["quantity"] >= ($compo->buy_quantity + ($compo->get_quantity ?? 0))) {
                $cart[$P_num]['compo_id'] = $compo->id;

                if ($compo->type == 'bogo') {
                    if ($originalquantity >= $cart[$P_num]['quantity'] + $compo->get_quantity) {
                        // $cart[$P_num]['quantity'] += $compo->get_quantity;
                    }

                    $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                    $composed = (int)($cart[$P_num]['quantity'] / ($compo->get_quantity + $compo->buy_quantity));
                    $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);
                    $cart[$P_num]['subtotal'] = $composed * $compo->buy_quantity * $pprince + $not_combos * $pprince;
                } else {
                    $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                    $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                    $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                    $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                }
                
                // Get tax rate from product
                $productForTax = ProductService::find($product->id);
                $taxRateForCart = $productForTax ? Utility::totalTaxRate($productForTax->tax_id) : 0;
                // Get price_with_vat from cart if available, otherwise null (will be calculated)
                $priceWithVatForCombo = isset($cart[$P_num]['price_with_vat']) ? $cart[$P_num]['price_with_vat'] : null;
                $carthtml = $this->generate_cart_html($P_num, $image_url, $productname, $cart[$P_num]['price'], $cart[$P_num]['subtotal'], $model_delete_id, $session_key, $compo->id, $taxRateForCart, $priceWithVatForCombo);
            } else {
                // No combo, generate cart HTML with tax
                $productForTax = ProductService::find($product->id);
                $taxRateForCart = $productForTax ? Utility::totalTaxRate($productForTax->tax_id) : 0;
                // Get price_with_vat from cart if available, otherwise null (will be calculated)
                $priceWithVatForCombo = isset($cart[$P_num]['price_with_vat']) ? $cart[$P_num]['price_with_vat'] : null;
                $carthtml = $this->generate_cart_html($P_num, $image_url, $productname, $cart[$P_num]['price'], $cart[$P_num]['subtotal'], $model_delete_id, $session_key, 0, $taxRateForCart, $priceWithVatForCombo);
            }


            session()->put($session_key, $cart);
            $vouchers = session()->get('vouchers');
            return response()->json(
                [
                    'code' => 200,
                    'status' => 'Success',
                    'success' => $productname . __(' added to cart successfully!'),
                    'product' => $cart[$P_num],
                    'carthtml' => $carthtml,
                    'carttotal' => $cart,
                    'vouchers' => $vouchers,
                ]
            );
        } else {
            // Check if it's a permission issue or AJAX issue
            if (!$request->ajax()) {
                return response()->json(
                    [
                        'code' => 400,
                        'status' => 'Error',
                        'error' => __('Invalid request.'),
                    ],
                    400
                );
            }
            
            return response()->json(
                [
                    'code' => 403,
                    'status' => 'Error',
                    'error' => __('Permission denied. You do not have permission to add products to cart.'),
                ],
                403
            );
        }
    }

    public function updateCart(Request $request)
    {
        $id = $request->id;
        $warehouseID = $request->warehouse;
        $quantity = $request->quantity;
        $discount = $request->discount;
        $session_key = $request->session_key;
        $W_h = warehouse::find($warehouseID);
        // return response()->json($W_h);
        // $producrQTY=$product->subProducts()->where('flag','!=', SubProduct::FLAG_CONSIGNMENT)->where('booked', 0)->count();
        $producrQTY = $W_h->GetFreeQuantity($id);
        
        // Get sub-product to use sale_price (price WITHOUT VAT)
        $subprod = SubProduct::where('warehouse_id', $warehouseID)
            ->where('chassis_no', $id)
            ->latest()
            ->first();
        
        // For POS, use sale_price from sub-product (price WITHOUT VAT)
        // Fallback to GetPrice if sub-product not found
        if ($session_key == 'pos' && $subprod && isset($subprod->sale_price)) {
            $producr_sale_price = $subprod->sale_price;
        } else {
            $producr_sale_price = $W_h->GetPrice($id);
        }
        
        $P_id = $W_h->GetProduct_id($id);
        $product = ProductService::find($P_id);
        if ($producrQTY < $quantity) {
            return response()->json(
                [
                    'code' => 404,
                    'status' => 'Error',
                    'error' => __('This product is out of stock!'),
                ],
                404
            );
        }
        // Allow company users, users with 'manage product & service', or users with POS permissions
        $hasPermission = Auth::user()->type == 'company' || 
                        Auth::user()->can('manage product & service') || 
                        ($session_key == 'pos' && Auth::user()->can('manage pos'));
        
        if ($hasPermission && $request->ajax() && isset($id) && !empty($id) && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);
            
            // Store old values for logging (POS only)
            $oldQuantity = isset($cart[$id]) ? $cart[$id]["quantity"] : 0;
            $oldSubtotal = isset($cart[$id]) ? $cart[$id]["subtotal"] : 0;

            if (isset($cart[$id]) && $quantity == 0) {
                // Log removal if POS
                if ($session_key == 'pos' && isset($cart[$id])) {
                    PosLog::logAction('remove_from_cart', [
                        'warehouse_id' => $warehouseID,
                        'product_id' => $P_id,
                        'product_no' => $id,
                        'old_value' => [
                            'quantity' => $oldQuantity,
                            'subtotal' => $oldSubtotal,
                        ],
                        'description' => "Product #{$id} removed from POS cart (quantity set to 0)",
                    ]);
                }
                unset($cart[$id]);
            }

            if ($quantity && $producrQTY >=  $quantity) {
                $cart[$id]["quantity"] = $quantity;

                // $producttax = isset($cart[$id]) ? $cart[$id]["tax"]:0;
                $productprice = $producr_sale_price;

                $taxes = Utility::tax($product->tax_id);

                $totalTaxRate = Utility::totalTaxRate($product->tax_id);

                $product_tax = '';
                $product_tax_id = [];
                foreach ($taxes as $tax) {
                    $product_tax .= !empty($tax) ? "<span class='badge badge-primary'>" . $tax->name . ' (' . $tax->rate . '%)' . "</span><br>" : '';
                    $product_tax_id[] = !empty($tax) ? $tax->id : 0;
                }

                if (empty($product_tax)) {
                    $product_tax = "-";
                }
                $producttax = $totalTaxRate;


                // $tax = ((($productprice - ($productprice * ($cart[$id]["discount"]/100)))* $request->quantity) * $producttax) / 100;
                $tax = 0;
                $subtotal = ($productprice - ($productprice * ($cart[$id]["discount"] / 100))) * $request->quantity;
                // $tax      = ($subtotal * $producttax) / 100;

                $cart[$id]["subtotal"] = $subtotal + $tax;
            }

            if (isset($cart[$id]) && isset($cart[$id]["originalquantity"]) < $cart[$id]['quantity'] && $session_key == 'pos') {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }

            $subtotal = array_sum(array_column($cart, 'subtotal'));
            $discount = $request->discount;
            $total = $subtotal - $discount;
            $totalDiscount = User::priceFormats($total);
            $discount = $totalDiscount;
            $P_num = $id;
            if ($cart[$P_num]['compo_id'] != 0) {

                $compo = ComboOffer::find($cart[$P_num]['compo_id']);
                if ($cart[$P_num]['quantity'] < ($compo->buy_quantity + ($compo->get_quantity ?? 0))) {
                    $cart[$P_num]['compo_id'] = 0;
                } elseif ($compo->type == 'bogo') {
                    $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);

                    if ($compo->buy_quantity == $not_combos && $producrQTY > $cart[$P_num]['quantity'] + $compo->get_quantity) {
                        // $cart[$P_num]['quantity'] += $compo->get_quantity;
                    }
                    $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                    $composed = (int)($cart[$P_num]['quantity'] / ($compo->get_quantity + $compo->buy_quantity));
                    $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);
                    $cart[$P_num]['subtotal'] = $composed * $compo->buy_quantity * $pprince + $not_combos * $pprince;
                } else {
                    $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                    $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                    $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                    $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                }
            } else {
                $creatorId = \Auth::user()->creatorId();
                // Check for combos: both old format (product_service_id) and new format (pivot table)
                $compo = ComboOffer::where('warehouse_id', $W_h->id)
                    ->where('created_by', $creatorId)
                    ->where('active', true)
                    ->whereRaw('(buy_quantity + COALESCE(get_quantity,0)) <= ?', [$cart[$P_num]["quantity"]])
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', Carbon::today());
                    })
                    ->where(function ($q) use ($product) {
                        // Old format: product_service_id matches
                        $q->where('product_service_id', $product->id)
                          // New format: product exists in pivot table
                          ->orWhereHas('products', function ($subQ) use ($product) {
                              $subQ->where('product_services.id', $product->id);
                          });
                    })
                    ->latest()
                    ->first();

                if ($compo) {
                    $cart[$P_num]['compo_id'] = $compo->id;

                    if ($compo->type == 'bogo') {
                        if ($producrQTY >= $cart[$P_num]['quantity'] + $compo->get_quantity) {
                            // $cart[$P_num]['quantity'] += $compo->get_quantity;

                            // $cart[$P_num]['quantity'] += $compo->get_quantity;
                            // $cart[$P_num]['subtotal'] = $not_combos * $pprince ;
                        }

                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $composed = (int)($cart[$P_num]['quantity'] / ($compo->get_quantity + $compo->buy_quantity));
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->get_quantity + $compo->buy_quantity);
                        $cart[$P_num]['subtotal'] = $composed * $compo->buy_quantity * $pprince + $not_combos * $pprince;
                    } else {
                        $pprince = ($cart[$P_num]["price"] - ($cart[$P_num]["price"] * ($cart[$P_num]["discount"] / 100)));
                        $composed_item = (int)($cart[$P_num]['quantity'] / $compo->buy_quantity);
                        $not_combos = $cart[$P_num]['quantity'] % ($compo->buy_quantity);
                        $cart[$P_num]['subtotal'] = $composed_item * $compo->tiered_price + $not_combos * $pprince;
                    }
                }
            }
            session()->put($session_key, $cart);

            $vouchers = session()->get('vouchers');
            return response()->json(
                [
                    'code' => 200,
                    'success' => __('Cart updated successfully!'),
                    'product' => $cart,
                    'prod' => $cart[$P_num],
                    'discount' => $discount,
                    'vouchers' => $vouchers,
                ]
            );
        } else {
            return response()->json(
                [
                    'code' => 404,
                    'status' => 'Error',
                    'error' => __('This Product is not found!'),
                ],
                404
            );
        }
    }

    public function emptyCart(Request $request)
    {
        $session_key = $request->session_key;

        // Allow company users, users with 'manage product & service', or users with POS permissions
        $hasPermission = Auth::user()->type == 'company' || 
                        Auth::user()->can('manage product & service') || 
                        ($session_key == 'pos' && Auth::user()->can('manage pos'));

        if ($hasPermission && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);
            $vouchers = session()->get('vouchers');
            $payment_customer = session()->get('total_customer_pay');
            $tax_id = session()->get('tax_id');

            if (isset($cart) && count($cart) > 0) {
                session()->forget($session_key);
            }
            if (isset($vouchers) && count($vouchers) > 0) {
                session()->forget('vouchers');
            }
            if (isset($payment_customer)) {
                session()->forget('total_customer_pay');
            }
            if (isset($tax_id)) {
                session()->forget('tax_id');
            }

            return redirect()->back()->with('error', __('Cart is empty!'));
        } else {
            return redirect()->back()->with('error', __('Cart cannot be empty!.'));
        }
    }

    public function warehouseemptyCart(Request $request)
    {
        $session_key = $request->session_key;

        // Clear cart session
        $cart = session()->get($session_key);
        if (isset($cart) && count($cart) > 0) {
            session()->forget($session_key);
        }
        
        // Clear all other POS-related sessions
        $vouchers = session()->get('vouchers');
        if (isset($vouchers) && count($vouchers) > 0) {
            session()->forget('vouchers');
        }
        
        $payment_customer = session()->get('total_customer_pay');
        if (isset($payment_customer)) {
            session()->forget('total_customer_pay');
        }
        
        $tax_id = session()->get('tax_id');
        if (isset($tax_id)) {
            session()->forget('tax_id');
        }

        return response()->json(['success' => true, 'message' => 'Cart and all sessions cleared']);
    }

    public function removeFromCart(Request $request)
    {
        $id          = $request->id;
        $session_key = $request->session_key;
        
        // Allow company users, users with 'manage product & service', or users with POS permissions
        $hasPermission = Auth::user()->type == 'company' || 
                        Auth::user()->can('manage product & service') || 
                        ($session_key == 'pos' && Auth::user()->can('manage pos'));
        
        if ($hasPermission && isset($id) && !empty($id) && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put($session_key, $cart);
            }

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'code' => 200,
                    'success' => __('Product removed from cart!'),
                    'product' => $cart, // Return remaining cart items
                ]);
            }

            return redirect()->back()->with('error', __('Product removed from cart!'));
        } else {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'code' => 404,
                    'status' => 'Error',
                    'error' => __('This Product is not found!'),
                ], 404);
            }

            return redirect()->back()->with('error', __('This Product is not found!'));
        }
    }


    // Function to save main products and sub-products
    public function saveProducts(Request $request)
    {
        // Validate the request data as needed


        $mainProduct = $request->input('item');
        SubProduct::where('product_id', $mainProduct)->where('flag', 0)->delete();

        // Save sub-products associated with the main product
        $subProducts = $request->input('subProducts');
        foreach ($subProducts as $subProductData) {
            $subProduct = new SubProduct();
            $subProduct->name = $subProductData['name'];
            $subProduct->number = $subProductData['number'];
            $subProduct->sale_price = $subProductData['sale_price'];
            $subProduct->sale_price_base = $subProductData['sale_price_base'];
            $subProduct->purchase_price = $subProductData['purchase_price'];
            $subProduct->product_id = $mainProduct; // Associate sub-product with the main product
            $subProduct->created_by     = \Auth::user()->creatorId();
            $subProduct->save();

            // Dynamically handle custom fields
            foreach ($subProductData as $fieldKey => $fieldValue) {
                // Check if the fieldKey is for a custom field (e.g., starts with 'custom_field_')
                if (strpos($fieldKey, 'custom_field_') === 0) {
                    // Extract the custom field ID
                    $customFieldId = substr($fieldKey, strlen('custom_field_'));

                    // Save the custom field ID and value to the database or perform other actions
                    \DB::insert(
                        'insert into custom_field_values (`record_id`, `field_id`,`value`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`),`updated_at` = VALUES(`updated_at`) ',
                        [
                            $subProduct->id,
                            $customFieldId,
                            $fieldValue,
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }
        }

        // You can return a response or redirect as needed
        return response()->json(['message' => 'Products saved successfully']);
    }

    public function getProductPrices($id)
    {
        $product = ProductService::find($id);
        if ($product) {
            return response()->json([
                'sale_price' => $product->sale_price,
                'sale_price_base' => $product->sale_price_base,
                'purchase_price' => $product->purchase_price
            ]);
        } else {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    /**
     * Fetch vehicle models (sub_brands rows) for the selected brand.
     */
    public function fetchSubBrands(Request $request)
    {
        $brandId = $request->input('brand_id');
        
        if (!$brandId) {
            return response()->json(['sub_brands' => []]);
        }

        $subBrands = VehicleModel::where('created_by', '=', \Auth::user()->creatorId())
            ->where('brand_id', '=', $brandId)
            ->get();

        $formattedSubBrands = $subBrands->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'brand_id' => $model->brand_id,
            ];
        });

        return response()->json(['sub_brands' => $formattedSubBrands]);
    }
}
