 @extends('layouts.admin')
 @section('page-title')
     {{ __('Manage Product & Services') }}
 @endsection
 @push('script-page')
     <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
 @endpush
 @push('css-page')
     <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
 @endpush
 @section('breadcrumb')
     <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
     <li class="breadcrumb-item">{{ __('Product & Services') }}</li>
 @endsection
 @section('action-btn')
    <div class="float-end">

        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import product') }}"
            data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import products (category, brand, model by name)') }}" class="btn btn-sm btn-primary">
            {{ __('Import product') }}
        </a>

        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import stock') }}"
            data-url="{{ route('productservice.stock.subproduct.import.file') }}" data-ajax-popup="true"
            data-title="{{ __('Import stock (parent product SKU, sub-product, initial rate & stock)') }}" class="btn btn-sm btn-danger">
            {{ __('Import stock') }}
        </a>

        <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export product') }}"
            class="btn btn-sm btn-primary">
            {{ __('Export product') }}
        </a>

        <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Add product') }}" class="btn btn-sm btn-primary">
            {{ __('Add product') }}
        </a>

    </div>
 @endsection

 @section('content')
     <div class="row">
         <div class="col-sm-12">
             <div class=" mt-2 {{ isset($_GET['category']) ? 'show' : '' }}" id="multiCollapseExample1">
                 <div class="card">
                     <div class="card-body">
                         <form action="{{ route('productservice.index') }}" method="GET" id="product_service">
                             <div class="d-flex align-items-center justify-content-end">
                                 <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                     <div class="btn-box">
                                         <label for="category" class="form-label">Category</label>
                                         <select name="category" class="form-control select2"
                                             data-placeholder="{{ __('Select Category') }}">
                                             <option value=""></option>
                                             @foreach ($category as $id => $cat)
                                                 <option value="{{ $id }}">{{ $cat }}</option>
                                             @endforeach
                                         </select>

                                     </div>
                                 </div>
                                 <div class="col mt-4 d-flex justify-content-end">
                                     <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                         title="{{ __('apply') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                     </button>
                                     <a href="{{ route('productservice.index') }}" class="btn btn-sm btn-danger"
                                         data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-trash-off "></i></span>
                                     </a>
                                 </div>
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
                         <table class="table" id="product-service-table">
                             <thead>
                                 <tr>
                                     <th>{{ __('ID') }}</th>
                                     <th>{{ __('Category') }}</th>
                                     <th>{{ __('Name') }}</th>
                                     {{-- <th>{{ __('Product') }}</th> --}}
                                     <th>{{ __('Sku') }}</th>
                                     <th>{{ __('Sale Price') }}</th>
                                     <th>{{ __('Purchase Price') }}</th>
                                     <th>{{ __('Avg Cost') }}</th>
                                     <th>{{ __('Unit') }}</th>
                                     <th>{{ __('Quantity') }}</th>
                                     <th>{{ __('Quantity Free') }}</th>
                                     <th>{{ __('Quantity Booked') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Model') }}</th>
                                     <th>{{ __('Action') }}</th>
                                 </tr>
                             </thead>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
     </div>
     @push('old-datatable-js')
         <script>
             const productServiceTable = new DataTable('#product-service-table', {
                 processing: true,
                 serverSide: true,
                 ajax: '{{ route('productservice.index') }}',
                 columns: [
                    {
                         data: 'id'
                     },
                     {
                         data: 'category'
                     },
                     {
                         data: 'name'
                     },
                     {
                         data: 'sku'
                     },
                     {
                         data: 'sale_price'
                     },
                     {
                         data: 'purchase_price'
                     },
                     {
                         data: 'avg_cost'
                     },
                     {
                         data: 'unit'
                     },
                     {
                         data: 'quantity'
                     },
                     {
                         data: 'quantity_free'
                     },
                     {
                         data: 'quantity_booked'
                     },
                    {
                        data: 'type'
                    },
                    {
                        data: 'model',
                        defaultContent: ''
                    },
                     {
                         data: 'action',
                         orderable: false,
                         searchable: false
                     }
                 ],
                 columnDefs: [{
                     targets: '_all'
                 }],
                 autoWidth: false,
                 order: [
                     [1, 'asc']
                 ],
                 pageLength: 50,
                 scrollY: 800,
                 scrollX: true,
                 scrollCollapse: true,
                 language: {
                     searchPlaceholder: "Search here...",
                     search: "",
                 }
             });
             $('#product_service').on('submit', function(e) {
                 e.preventDefault();
                 // Get filter values
                 let categoryId = $('select[name="category"]').val();

                 // Pass filters to DataTables and reload
                 productServiceTable.ajax.url(
                     '{{ route('productservice.index') }}' +
                     '?category=' + encodeURIComponent(categoryId)
                 ).load();
             });
         </script>
     @endpush
 @endsection
