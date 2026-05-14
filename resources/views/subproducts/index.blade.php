 @extends('layouts.admin')
 @section('page-title')
     {{ __('Manage Sub-Product') }}
 @endsection
 @push('script-page')
 @endpush
 @section('breadcrumb')
     <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
     <li class="breadcrumb-item"><a href="{{ route('productservice.index') }}">{{ __('Product & Services') }}</a></li>
     <li class="breadcrumb-item">{{ __('subProduct') }}</li>
 @endsection
 @section('action-btn')
     <div class="float-end">
         <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('subproduct.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import sub product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a>
         {{-- <a href="{{route('productservice.export')}}" data-bs-toggle="tooltip" title="{{__('Export')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a> --}}

         {{-- <a href="#" data-size="lg" data-url="{{ route('sub-product.create', $product_id) }}" data-ajax-popup="true"
             data-bs-toggle="tooltip" title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-plus"></i>
         </a> --}}

     </div>
 @endsection

 @section('content')
     <div class="row">
         <div class="col-sm-12">
             <div class=" mt-2 {{ isset($_GET['exterior_color_id']) ? 'show' : '' }}" id="multiCollapseExample1">
                 <div class="card">
                     <div class="card-body">
                         <form action="{{ route('subProducts', $product_id) }}" method="GET" id="product_id">
                             <div class="d-flex align-items-center justify-content-end">
                                 {{-- <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                     <div class="btn-box">
                                         <label for="exterior_color_id"
                                             class="form-label">{{ __('Exterior Color') }}</label>
                                         <select id="exterior_color_id" name="exterior_color_id" class="form-control select"
                                             required="required">
                                             <option value="">Select Color</option>
                                             @foreach ($colors as $colorId => $colorName)
                                                 <option value="{{ $colorId }}">{{ $colorName }}</option>
                                             @endforeach
                                         </select>
                                     </div>
                                 </div> --}}
                                 {{-- <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                     <div class="btn-box">
                                         <label for="interior_color_id"
                                             class="form-label">{{ __('Interior Color') }}</label>
                                         <select id="interior_color_id" name="interior_color_id" class="form-control select"
                                             required="required">
                                             <option value="">Select Color</option>
                                             @foreach ($colors as $colorId => $colorName)
                                                 <option value="{{ $colorId }}">{{ $colorName }}</option>
                                             @endforeach
                                         </select>

                                     </div>
                                 </div> --}}
                                 {{-- <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                     <div class="btn-box">
                                         <label for="location" class="form-label">{{ __('Location') }}</label>
                                         <select id="location" name="location" class="form-control select"
                                             required="required">
                                             <option value="">Select Location</option>
                                             @foreach ($countries as $countryId => $countryName)
                                                 <option value="{{ $countryId }}">{{ $countryName }}</option>
                                             @endforeach
                                         </select>

                                     </div>
                                 </div> --}}
                                 <div class="col-auto float-end ms-2 mt-4">
                                     <a href="#" class="btn btn-sm btn-primary"
                                         onclick="document.getElementById('product_id').submit(); return false;"
                                         data-bs-toggle="tooltip" title="{{ __('apply') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                     </a>
                                     <a href="{{ route('subProducts', $product_id) }}" class="btn btn-sm btn-danger"
                                         data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-trash-off "></i></span>
                                     </a>
                                 </div>

                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
     </div>
     <div class="row">
         <div class="col-xl-12">
             <div class="card">
                 <div class="card-body table-border-style">
                     <div class="table-responsive">
                         <table class="table datatable">
                             <thead>
                                 <tr>
                                     <th>{{ __('ID') }}</th>
                                     {{-- <th>{{ __('Chassis No') }}</th> --}}
                                     <th>{{ __('Product') }}</th>
                                    <th>{{ __('Product No') }}</th>
                                    {{-- <th>{{ __('Exterior Color') }}</th> --}}
                                    {{-- <th>{{ __('Interior Color') }}</th> --}}
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Sale Price') }}</th>
                                    <th>{{ __('Purchase Price') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Purchase Status') }}</th>
                                    <th>{{ __('Book Status') }}</th>
                                    <th> {{ __('Bill') }}</th>
                                    <th> {{ __('Invoice') }}</th>
                                     @foreach ($customFields as $customField)
                                         <th>{{ __($customField->name) }}</th>
                                     @endforeach
                                     <th>{{ __('Action') }}</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @foreach ($subProducts as $productService)
                                     @php
                                         if ($productService->bill_id != null) {
                                             $bill = \App\Models\Bill::where('id', $productService->bill_id)->first();
                                             // $warehouse = \App\Models\warehouse::where(
                                             //     'id',
                                             //     $bill->warehouse_id,
                                             // )->first();
                                         }

                                     @endphp
                                     <tr class="font-style">
                                         <td>{{ $productService->id }}</td>
                                         {{-- <td>{{ $productService->chassis_no }}</td> --}}
                                        <td>{{ $productService->productService->brand->name ?? '' }}{{ $productService->productService->brand && $productService->productService->subBrand ? '/' : '' }}{{ $productService->productService->subBrand->name ?? '' }}{{ ($productService->productService->brand || $productService->productService->subBrand) && optional($productService->productService)->name ? '/' : '' }}{{ optional($productService->productService)->name ?? '' }}</td>
                                        <td>{{ $productService->product_no }}</td>
                                        {{-- <td>{{ $productService->exteriorColor != null ? $productService->exteriorColor->name : '-' }}
                                        </td> --}}
                                        {{-- <td>{{ $productService->interiorColor != null ? $productService->interiorColor->name : '-' }}
                                        </td> --}}
                                        <td>{{ $productService->warehouse ? $productService->warehouse->name : '-' }}</td>
                                        <td>{{ \Auth::user()->priceFormat($productService->sale_price) }}</td>
                                         <td>{{ \Auth::user()->priceFormat($productService->purchase_price) }}</td>
                                         <td>{{ $productService->quantity }}</td>
                                         <td>
                                             @php
                                                 $flag = $productService->flag ?? 0;
                                                 $flagLabels = [
                                                     0 => 'Pending',
                                                     1 => 'Purchased',
                                                     2 => 'Cancelled',
                                                     3 => 'Consignment'
                                                 ];
                                             @endphp
                                             {{ $flagLabels[$flag] ?? 'Unknown' }}
                                         </td>

                                         @if ($productService->booked == 0)
                                             <td>Free</td>
                                         @elseif (
                                             $productService->booked == 1 &&
                                                 $productService->invoice_id != null &&
                                                 \App\Models\Invoice::where('id', $productService->invoice_id)->first()->type == 'rent')
                                             <td>Rented</td>
                                         @elseif ($productService->booked == 1)
                                             <td>Booked</td>
                                         @elseif($productService->booked == 2)
                                             <td>Sold</td>
                                         @elseif($productService->booked == 3)
                                             <td>Delivered</td>
                                         @endif
                                         <td class="Id">
                                             @if ($productService->bill_id != null)
                                                 <a href="{{ route('bill.show', \Crypt::encrypt($productService->bill_id)) }}"
                                                     class="btn btn-outline-primary">{{ optional($productService->bill)->bill_id ? AUth::user()->billNumberFormat(\App\Models\Bill::where('id', $productService->bill_id)->first()->bill_id) : __('N/A') }}</a>
                                             @else
                                                 -
                                             @endif
                                         </td>
                                         <td class="Id">
                                             @if ($productService->invoice_id != null)
                                                 <a href="{{ route('invoice.show', \Crypt::encrypt($productService->invoice_id)) }}"
                                                     class="btn btn-outline-primary">{{ AUth::user()->invoiceNumberFormat(\App\Models\Invoice::where('id', $productService->invoice_id)->first()->invoice_id) }}</a>
                                             @elseif($productService->pos_id != null)
                                                 <a href="{{ route('pos.show', \Crypt::encrypt($productService->pos_id)) }}"
                                                     class="btn btn-outline-primary">{{ '#' . __('POS') . sprintf('%05d', $productService->pos_id) }}</a>
                                             @else
                                                 -
                                             @endif
                                         </td>
                                         @foreach ($customFields as $customField)
                                             @php
                                                 // Ensure that we safely access the value
                                                 $value = isset(
                                                     $customFieldValues[$productService->id][$customField->id],
                                                 )
                                                     ? $customFieldValues[$productService->id][$customField->id]
                                                     : '';
                                             @endphp
                                             <td>
                                                 @if ($customField->type == 'text')
                                                     {{ $value }} <!-- Display as plain text -->
                                                 @elseif($customField->type == 'email')
                                                     {{ $value }} <!-- Display email as plain text -->
                                                 @elseif($customField->type == 'number')
                                                     {{ $value }} <!-- Display number as plain text -->
                                                 @elseif($customField->type == 'date')
                                                     {{ $value }} <!-- Display date as plain text -->
                                                 @elseif($customField->type == 'textarea')
                                                     {{ $value }} <!-- Display textarea content as plain text -->
                                                 @elseif($customField->type == 'dropdown')
                                                     @php
                                                         $options = json_decode($customField->options, true);
                                                     @endphp
                                                     {{ $value }}
                                                     <!-- Display selected dropdown value as plain text -->
                                                 @endif
                                             </td>
                                         @endforeach

                                         @if (Gate::check('edit sub-products') || Gate::check('delete sub-products'))
                                             <td class="Action">
                                                 @can('edit sub-products')
                                                     <div class="action-btn bg-warning ms-2">
                                                         <a href="{{ route('sub-product.expenses', $productService->id) }}"
                                                             class="mx-3 btn btn-sm  align-items-center"
                                                             data-bs-toggle="tooltip" title="{{ __('Expenses') }}"
                                                             data-title="{{ __('Product Expenses') }}">
                                                             <i class="ti ti-report-money text-white"></i>
                                                         </a>
                                                     </div>
                                                 @endcan
                                                 @can('edit sub-products')
                                                     <div class="action-btn bg-info ms-2">
                                                         <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                             data-url="{{ route('sub-product.edit', $productService->id) }}"
                                                             data-ajax-popup="true" data-size="lg " data-bs-toggle="tooltip"
                                                             title="{{ __('Edit') }}"
                                                             data-title="{{ __('Edit Product') }}">
                                                             <i class="ti ti-pencil text-white"></i>
                                                         </a>
                                                     </div>
                                                 @endcan
                                                 @can('delete sub-products')
                                                     <div class="action-btn bg-danger ms-2">
                                                         <form method="POST"
                                                             action="{{ route('sub-product.delete', $productService->id) }}"
                                                             id="delete-form-{{ $productService->id }}">
                                                             @method('DELETE')
                                                             @csrf
                                                             <a href="#"
                                                                 class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                 data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                     class="ti ti-trash text-white"></i></a>
                                                         </form>
                                                     </div>
                                                 @endcan
                                             </td>
                                         @endif
                                     </tr>
                                 @endforeach

                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
     </div>
     {{ $subProducts->appends(request()->query())->links() }}
 @endsection
