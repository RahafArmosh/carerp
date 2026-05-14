@extends('layouts.admin')
@section('page-title')
    {{__('Manage Models')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Models')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create constant sub-brand')
            <a href="#" data-url="{{ route('sub-brand.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" title="{{__('Create')}}" data-title="{{__('Create New Model')}}"  class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import from Excel') }}"
             data-url="{{ route('sub_brand.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import Models from Excel') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i> {{ __('Import from Excel') }}
         </a>
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Update by ID from Excel') }}"
             data-url="{{ route('sub_brand.file.import') }}?update_by_id=1" data-ajax-popup="true"
             data-title="{{ __('Update Model by ID from Excel') }}" class="btn btn-sm btn-warning">
             <i class="ti ti-file-import"></i> {{ __('Update by ID') }}
         </a>
        <a href="{{ route('sub-brand.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
             class="btn btn-sm btn-primary">
             <i class="ti ti-file-export"></i>
         </a>
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
                    <form method="GET" action="{{ route('sub-brand.index') }}" class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                placeholder="{{ __('Search model name...') }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                            <a href="{{ route('sub-brand.index') }}" class="btn btn-light">{{ __('Reset') }}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th> {{__('ID')}}</th>
                                <th> {{__('Model')}}</th>
                                <th> {{__('Brand')}}</th>
                                <th width="10%"> {{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($brands as $subBrand)
                                <tr>
                                    <td class="font-style">{{ $subBrand->id }}</td>
                                    <td class="font-style">{{ $subBrand->name }}</td>
                                    <td class="font-style">
                                        @if($subBrand->brand)
                                            <span class="badge bg-primary">{{ $subBrand->brand->name }}</span>
                                            @if($subBrand->brand->categories->count() > 0)
                                                <br><small class="text-muted">({{ $subBrand->brand->categories->pluck('name')->implode(', ') }})</small>
                                            @endif
                                        @else
                                            <span class="text-danger">{{ __('Brand not found') }}</span>
                                        @endif
                                    </td>
                                    <td class="Action">
                                        <span>
                                        @can('edit constant sub-brand')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('sub-brand.edit',$subBrand->id) }}" data-ajax-popup="true" data-title="{{__('Edit Model')}}" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete constant sub-brand')
                                                <div class="action-btn bg-danger ms-2">
                                                    <form id="delete-form-{{ $subBrand->id }}" action="{{ route('sub-brand.destroy', $subBrand->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#"
                                                             class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                             data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                             data-original-title="{{ __('Delete') }}"
                                                             data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                             data-confirm-yes="document.getElementById('delete-form-{{ $subBrand->id }}').submit();">
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
