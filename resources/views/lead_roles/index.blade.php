@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Lead Role') }}
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Lead Roles') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="lg" data-url="{{ route('lead_roles.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New Role') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.crm_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="datatable table">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Conditions') }}</th>
                                    {{-- <th>{{ __('Operation') }}</th> --}}
                                    <th>{{ __('Pipeline') }}</th>
                                    <th>{{ __('Assigned User') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th width="250px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leadRoles as $role)
                                    <tr class="font-style">
                                        <td>{{ $role->name }}</td>
                                        <td>
                                            @forelse($role->conditions as $condition)
                                                <div>
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $condition->lead_column)) }}</strong>
                                                    {{ $condition->operation }}
                                                    <em>{{ $condition->value }}</em>
                                                    @if (!empty($condition->connector))
                                                        ({{ $condition->connector }})
                                                    @endif
                                                </div>
                                            @empty
                                                <span class="text-muted">No conditions</span>
                                            @endforelse
                                        </td>
                                        {{-- <td>{{ $role->operation }}</td> --}}
                                        <td>{{ $role->pipeline ? $role->pipeline->name : '-' }}</td>
                                        <td>{{ $role->user->name ?? 'N/A' }}</td>
                                        <td>{{ $role->active == 1 ? 'Active' : 'Not Active' }}</td>
                                        <td class="Action">
                                            <span>
                                                @if (count($leadRoles) > 1)
                                                    @can('delete lead role')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('lead_roles.destroy', $role->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button"
                                                                    class="btn btn-sm align-items-center bs-pass-para mx-3"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                        class="ti ti-trash text-white"></i></button>
                                                            </form>
                                                        </div>
                                                    @endcan
                                                @endif
                                                @can('edit lead role')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#"
                                                            class="btn btn-sm d-inline-flex align-items-center mx-3"
                                                            data-url="{{ URL::to('lead_roles/' . $role->id . '/edit') }}"
                                                            data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip"
                                                            title="{{ __('Edit') }}" data-title="{{ __('Edit Role') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No roles found.</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
