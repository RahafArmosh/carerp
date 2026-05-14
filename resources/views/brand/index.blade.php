@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Brands') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Brands') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create constant brand')
            <a href="#" data-url="{{ route('brand.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip"
                title="{{ __('Create') }}" title="{{ __('Create') }}" data-title="{{ __('Create New Brand') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('brand.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import brand file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a>
        <a href="{{ route('brand.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
             class="btn btn-sm btn-primary">
             <i class="ti ti-file-export"></i>
         </a>
        @can('edit constant brand')
            <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import from Excel') }}"
                 data-url="{{ route('brand.file.import') }}" data-ajax-popup="true"
                 data-title="{{ __('Import Brand from Excel') }}" class="btn btn-sm btn-primary">
                 <i class="ti ti-file-import"></i> {{ __('Import from Excel') }}
             </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
           
                @include('layouts.account_setup')
         
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <form method="GET" action="{{ route('brand.index') }}" class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                placeholder="{{ __('Search brand name...') }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                            <a href="{{ route('brand.index') }}" class="btn btn-light">{{ __('Reset') }}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th> {{ __('ID') }}</th>
                                    <th> {{ __('Brand') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($brands as $brand)
                                    <tr>
                                        <td class="font-style">{{ $brand->id }}</td>
                                        <td class="font-style">{{ $brand->name }}</td>
                                        <td class="font-style">
                                            @if ($brand->categories->isNotEmpty())
                                                <ul class="mb-0">
                                                    @foreach ($brand->categories as $category)
                                                        <li>{{ $category->name }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">{{ __('None') }}</span>
                                            @endif
                                        </td>
                                        <td class="Action">
                                            <span>
                                                @can('edit constant brand')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('brand.edit', $brand->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Brand') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete constant brand')
                                                <div class="action-btn bg-danger ms-2">
                                                    <form method="POST" action="{{ route('brand.destroy', $brand->id) }}"
                                                          id="delete-form-{{ $brand->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#"
                                                             class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                             data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                             data-original-title="{{ __('Delete') }}"
                                                             data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                             data-confirm-yes="document.getElementById('delete-form-{{ $brand->id }}').submit();">
                                                            <i class="ti ti-trash text-white text-white"></i>
                                                        </a>
                                                    </form>
                                                </div>
                                            @endcan
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $brands->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
