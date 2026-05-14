@extends('layouts.admin')

@section('page-title')
    {{ __('Alternative Parts') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
    </li>
    <li class="breadcrumb-item">{{ __('Alternative Parts') }}</li>
    {{-- @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif --}}

@endsection
@section('action-btn')
<div class="float-end d-flex gap-2">

    {{-- Import Button --}}
    <a href="#"
       data-url="{{ route('alternatives.import.form') }}"
       data-ajax-popup="true"
       data-size="md"
       data-title="{{ __('Import Alternative Parts') }}"
       class="btn btn-sm btn-success">
        <i class="ti ti-upload"></i> {{ __('Import') }}
    </a>


</div>
@endsection


@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Part Number') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th width="10%">{{ __('Action') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($parts as $part)
                            <tr>
                                <td>{{ $part->sku }}</td>
                                <td>{{ $part->name ?? '-' }}</td>
                                <td class="Action">
                                    <div class="action-btn bg-primary ms-2">
                                        <a href="{{ route('sub-products.alternatives.index', $part->sku) }}"
                                           class="btn btn-sm align-items-center"
                                           data-bs-toggle="tooltip"
                                           title="{{ __('Manage Alternatives') }}">
                                            <i class="ti ti-list text-white"></i>
                                        </a>
                                    </div>
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
