 @extends('layouts.admin')
 @section('page-title')
     {{ __('Rent Report') }}
 @endsection
 @push('script-page')
 @endpush
 @section('breadcrumb')
     <li class="breadcrumb-item">
         <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
     </li>
     <li class="breadcrumb-item">{{ __('Rent Report') }}</li>
 @endsection
 @section('action-btn')
     <div class="float-end">
         {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a> --}}
         {{-- <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
             class="btn btn-sm btn-primary">
             <i class="ti ti-file-export"></i>
         </a> --}}

         {{-- <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
             data-bs-toggle="tooltip" title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-plus"></i>
         </a> --}}

     </div>
 @endsection

 @section('content')
     <div class="row">
         <div class="col-sm-12">
             <div class=" mt-2 {{ isset($_GET['category']) ? 'show' : '' }}" id="multiCollapseExample1">
                 <div class="card">
                     <div class="card-body">
                         {{-- <form action="{{ route('productservice.index') }}" method="GET" id="product_service">
                             <div class="d-flex align-items-center justify-content-end">
                                 <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                     <div class="btn-box">
                                         <label for="category" class="form-label">Category</label>
                                         <select name="category" id="choices-multiple" class="form-control select" required>
                                             @foreach ($category as $id => $cat)
                                                 <option value="{{ $id }}">{{ $cat }}</option>
                                             @endforeach
                                         </select>

                                     </div>
                                 </div>
                                 <div class="col-auto float-end ms-2 mt-4">
                                     <a href="#" class="btn btn-sm btn-primary"
                                         onclick="document.getElementById('product_service').submit(); return false;"
                                         data-bs-toggle="tooltip" title="{{ __('apply') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                     </a>
                                     <a href="{{ route('productservice.index') }}" class="btn btn-sm btn-danger"
                                         data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                         <span class="btn-inner--icon"><i class="ti ti-trash-off "></i></span>
                                     </a>
                                 </div>

                             </div>
                         </form> --}}
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
                                    <th>Main Product</th>
                                    <th>Sub Product No</th>
                                    <th>Times Rented</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rentCounts as $item)
                                    <tr>
                                        <td>{{ $item['main_product_name'] }}</td>
                                        <td>{{ $item['sub_product_no'] }}</td>
                                        <td>{{ $item['times_rented'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 @endsection
