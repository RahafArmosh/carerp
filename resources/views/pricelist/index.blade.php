@extends('layouts.admin')

@section('page-title')
    {{ __('Warehouse Price Rules') }}
@endsection

@push('script-page')
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Warehouse Price Rules') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('pricelist.create') }}" data-size="lg" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Price Rule') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Warehouse') }}</th>
                                    <th>{{ __('Apply To') }}</th>
                                    <th>{{ __('Target') }}</th>
                                    <th>{{ __('Price Mode') }}</th>
                                    <th>{{ __('Value') }}</th>
                                    <th>{{ __('.99') }}</th>
                                    <th>{{ __('base_price_source') }}</th>
                                    <th>{{ __('Craete At') }}</th>
                                    {{-- <th>{{ __('Action') }}</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($priceRules as $rule)
                                    <tr>
                                        <td>{{ $rule->warehouse->name ?? '-' }}</td>
                                        <td>{{ ucfirst($rule->apply_to) }}</td>
                                        <td>{{ $rule->target_label ?? '-' }} 
                                        </td>
                                        <td>{{ ucfirst($rule->price_mode) }}</td>
                                        <td>{{ $rule->value }}</td>
                                        <td>
                                            @if ($rule->apply_99)
                                                <span class="badge bg-success">{{ __('Yes') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{$rule->base_price_source}}
                                        </td>
                                        <td>{{ $rule->created_at }}</td>
                                        <td class="Action">
                                             <div class="d-flex">
                                                {{-- <div class="action-btn bg-info ms-2">
                                                    <a href="{{ route('pricelist.edit', $rule->id) }}"
                                                        class="mx-3 btn btn-sm align-items-center"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Price Rule') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div> --}}
                                                <div class="action-btn bg-danger ms-2">
                                                    <form id="delete-form-{{ $rule->id }}"
                                                        action="{{ route('pricelist.destroy', $rule->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div> 
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-3">
                            {{ $priceRules->links() }} {{-- Pagination --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection

