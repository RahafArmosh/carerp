@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Custom Field') }}
@endsection
@push('css-page')
    <style>
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }

        .tag {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }

        .tag .remove {
            margin-left: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .tag .remove:hover {
            color: #ffcccc;
        }
    </style>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Custom Field') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create constant custom field')
            <a href="#" data-url="{{ route('custom-field.create') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                data-ajax-popup="true" data-title="{{ __('Create New Custom Field') }}" class="btn btn-sm btn-primary">
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
                                    <th> {{ __('Custom Field') }}</th>
                                    <th> {{ __('Type') }}</th>
                                    <th> {{ __('Field Type') }}</th>
                                    <th> {{ __('Module') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Show In Bill') }}</th>
                                    <th> {{ __('Show In Invoice') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($custom_fields as $field)
                                    <tr>
                                        <td>{{ $field->name }}</td>
                                        <td>{{ $field->type }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ ($field->field_type ?? 'constant') === 'variable' ? 'info' : 'secondary' }}">
                                                {{ ucfirst($field->field_type ?? 'constant') }}
                                            </span>
                                        </td>
                                        <td>{{ $field->module }}</td>
                                        <td>
                                            @if($field->categories->count() > 0)
                                                @foreach($field->categories as $category)
                                                    <span class="badge bg-primary">{{ $category->name }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">{{ __('No categories') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $field->show_in_bill == 0 ? 'No' : 'Yes' }}</td>
                                        <td>{{ $field->show_in_invoice == 0 ? 'No' : 'Yes' }}</td>
                                        @if (Gate::check('edit constant custom field') || Gate::check('delete constant custom field') || $field->type === 'dropdown')
                                            <td class="Action">
                                                <span>
                                                    @if($field->type === 'dropdown')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="{{ route('custom-field.export-options', $field->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center"
                                                                data-bs-toggle="tooltip" title="{{ __('Export Options') }}"
                                                                data-original-title="{{ __('Export Options') }}">
                                                                <i class="ti ti-download text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                    @can('edit constant custom field')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('custom-field.edit', $field->id) }}"
                                                                data-ajax-popup="true"
                                                                data-title="{{ __('Edit Custom Field') }}"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete constant custom field')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('custom-field.destroy', $field->id) }}"
                                                                id="delete-form-{{ $field->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                {{-- <div class="action-btn bg-danger ms-2"> --}}
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $field->id }}').submit();">
                                                                    <i class="ti ti-trash text-white text-white"></i>
                                                                </a>
                                                                {{-- </div> --}}
                                                            </form>
                                                        </div>
                                                    @endcan
                                                </span>
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
@endsection
