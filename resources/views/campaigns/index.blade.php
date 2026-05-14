@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Lead Role') }}
@endsection
@push('script-page')
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Campaigns') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="md" data-url="{{ route('campaigns.create') }}" data-ajax-popup="true"
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
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Target Country</th>
                                    <th>Source</th>
                                    <th>URL</th>
                                    <th width="250px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $campaign)
                                    <tr class="font-style">
                                        <td>{{ $campaign->name }}</td>
                                        <td>{{ $campaign->status }}</td>
                                        <td>{{ $campaign->assignedUser->name ?? 'N/A' }}</td>
                                        <td>{{ $campaign->target_country }}</td>
                                        <td>{{ $campaign->source->name }}</td>
                                        <td>{{ $campaign->url }}</td>
                                        <td class="Action">
                                            <span>
                                                    @can('delete pipeline')
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('campaigns.destroy', $campaign->id) }}"
                                                                onsubmit="return confirm('{{ __('Are you sure you want to delete this campaign?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                        class="ti ti-trash text-white"></i></button>
                                                            </form>
                                                        </div>
                                                    @endcan

                                                @can('edit pipeline')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                            data-url="{{ route('campaigns.edit', $campaign->id) }}"
                                                            data-ajax-popup="true" data-size="md" data-bs-toggle="tooltip"
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
                                        <td colspan="6">No campaign found.</td>
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
