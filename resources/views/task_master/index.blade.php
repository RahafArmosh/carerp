@extends('layouts.admin')

@section('page-title')
    {{ __('Task Master') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Task Master') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create task master')
            <a href="#" data-url="{{ route('task-master.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create Task') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
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
                                    <th>{{ __('Department') }}</th>
                                    <th>{{ __('Predefined') }}</th>
                                    <th>{{ __('Active') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="font-style">
                                @foreach ($taskMasters as $task)
                                    <tr>
                                        <td>{{ $task->name }}</td>
                                        <td>{{ !empty($task->department) ? $task->department->name : __('All') }}</td>
                                        <td>{{ $task->is_predefined ? __('Yes') : __('No') }}</td>
                                        <td>{{ $task->is_active ? __('Yes') : __('No') }}</td>
                                        <td class="Action">
                                            <span>
                                                @can('edit task master')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#"
                                                            data-url="{{ URL::to('task-master/' . $task->id . '/edit') }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Task') }}"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i></a>
                                                    </div>
                                                @endcan
                                                @can('delete task master')
                                                    <div class="action-btn bg-danger ms-2">
                                                        <form action="{{ route('task-master.destroy', $task->id) }}"
                                                            method="POST" id="delete-form-task-master-{{ $task->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                onclick="confirmDelete('{{ __('Are You Sure?') }}', 'delete-form-task-master-{{ $task->id }}')">
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
