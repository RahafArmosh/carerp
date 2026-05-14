@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Competencies') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Competencies') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('Create Competencies')
            <a href="#" data-url="{{ route('competencies.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create New Competencies') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="font-style">
                                @foreach ($competencies as $competency)
                                    <tr>
                                        <td>{{ $competency->name }}</td>
                                        <td>{{ !empty($competency->performance) ? $competency->performance->name : '' }}</td>



                                        <td class="Action">
                                            <span>
                                                @can('edit document type')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ URL::to('competencies/' . $competency->id . '/edit') }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Competencies') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('Delete Competencies')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form action="{{ route('competencies.destroy', $competency->id) }}"
                                                            method="POST" id="delete-form-{{ $competency->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#!"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                onclick="confirmDelete('{{ __('Are You Sure?') }}', 'delete-form-{{ $competency->id }}')">
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
