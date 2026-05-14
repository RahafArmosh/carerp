@extends('layouts.admin')
@section('page-title')
    {{__('Manage Colors')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Colors')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create constant countries')
            <a href="#" data-url="{{ route('colors.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" title="{{__('Create')}}" data-title="{{__('Create New Color')}}"  class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
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
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th> {{__('Color')}}</th>
                                <th> {{__('Code')}}</th>
                                <th width="10%"> {{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($colors as $color)
                                <tr>
                                    <td class="font-style">{{ $color->name }}</td>
                                    <td class="font-style">{{ $color->code }}</td>
                                    <td class="Action">
                                        <span>
                                        @can('edit constant colors')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('colors.edit',$color->id) }}" data-ajax-popup="true" data-title="{{__('Edit Color')}}" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete constant colors')
                                                <div class="action-btn bg-danger ms-2">
                                                    <form method="POST" action="{{ route('colors.destroy', $color->id) }}" id="delete-form-{{ $color->id }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}" data-original-title="{{ __('Delete') }}" data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}" data-confirm-yes="document.getElementById('delete-form-{{ $color->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
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
                </div>
            </div>
        </div>
    </div>

@endsection
