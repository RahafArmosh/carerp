@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Quotations') }}
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            // Optional: add datatable initialization if not already initialized globally
            $('.datatable').DataTable();
        });
    </script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Quotations') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
            <a href="{{ route('quotations.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style mt-2">
                    <h5>{{ __('All Quotations') }}</h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Quotation No') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Subtotal') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th>{{ __('Total') }}</th>
                                    <th>{{ __('Created By') }}</th>
                                    <th width="10%">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quotations as $quotation)
                                    <tr>
                                        <td>
                                            <a href="{{ route('quotations.show', $quotation->id) }}" >
                                                {{ $quotation->quotation_no }}
                                            </a>
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($quotation->quotation_date) }}</td>
                                        <td>{{ $quotation->customer?->name ?? '-' }}</td>
                                        <td>{{ \Auth::user()->priceFormat($quotation->subtotal) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($quotation->tax_amount) }}</td>
                                        <td>{{ \Auth::user()->priceFormat($quotation->total) }}</td>
                                        <td>{{ $quotation->created_by }}</td>
                                        <td>
                                            @can('view quotation')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" data-url="{{ route('quotations.show', $quotation->id) }}"
                                                       data-ajax-popup="true" data-title="{{ __('Quotation Details') }}"
                                                       class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip"
                                                       title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan

                                            <a class="action-btn bg-primary ms-2" href="{{ route('quotations.edit', $quotation->id) }}" title="{{ __('Edit') }}">
                                                <i class="ti ti-pencil text-white"></i>
                                            </a>
                                            <a class="action-btn bg-success ms-2" href="{{ route('quotations.showexport', $quotation->id) }}" title="{{ __('Export Excel') }}"> 
                                                <i class="ti ti-file-export text-white"></i> </a> 

                                            <a  class="action-btn bg-danger ms-2" href="{{ route('quotations.export.pdf', $quotation->id) }}" title="{{ __('Export PDF') }}"> 
                                                <i class="ti ti-file-export text-white"></i> </a> 

                                            <a  class="action-btn bg-info ms-2" href="#" data-size="lg" data-url="{{ route('quotations.quotation2saleorder', $quotation->id) }}" data-ajax-popup="true"
                                                    data-bs-toggle="tooltip" title="{{ __('Convert To so') }}" data-title="{{ __('Convert to SO ') }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="ti ti-refresh text-white"></i>
                                            </a>
                                            @can('delete quotation')
                                                <div class="action-btn bg-danger ms-2">
                                                    <form method="POST"
                                                          action="{{ route('quotations.destroy', $quotation->id) }}"
                                                          id="delete-form-{{ $quotation->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="#"
                                                           class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                           data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                           onclick="event.preventDefault(); if(confirm('{{ __('Are you sure? This action cannot be undone.') }}')) { document.getElementById('delete-form-{{ $quotation->id }}').submit(); }">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    </form>
                                                </div>

                                            @endcan

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Pagination --}}
                        <div class="mt-2">
                            {{ $quotations->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
