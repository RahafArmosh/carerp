@extends('layouts.admin')

@section('page-title')
    {{ __('Alternative Parts for') }} {{ $productNo }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('alternative-parts.index') }}">{{ __('Alternative Parts') }}</a></li>
    <li class="breadcrumb-item">{{ $productNo }}</li>
@endsection

@section('action-btn')
<div class="float-end">
    <a href="#"
       data-url="{{ route('sub-products.alternatives.create', $productNo) }}"
       data-ajax-popup="true"
       data-size="lg"
       data-title="{{ __('Add Alternative Part') }}"
       title="{{ __('Add Alternatives') }}"
       class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i>
    </a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th>{{ __('Alternative Part Number') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th width="10%">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alternatives as $alt)
                            <tr>
                                <td>{{ $alt->alternativePart->product_no ?? $alt->alternative_part_number }}</td>
                                <td>{{ $alt->priority }}</td>
                                <td>
                                    @if($alt->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="Action">
                                    {{-- Toggle Active --}}
                                    <div class="action-btn bg-info ms-2">
                                        <form action="{{ route('sub-products.alternatives.update', $alt->id) }}"
                                              method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $alt->is_active ? 0 : 1 }}">
                                            <button class="btn btn-sm text-white" title="{{ __('Deactivate') }}">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                        </form>
                                    </div>

                                    {{-- Delete --}}
                                    <div class="action-btn bg-danger ms-2">
                                        <form action="{{ route('sub-products.alternatives.destroy', $alt->id) }}"
                                              method="POST" id="delete-form-{{ $alt->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <a href="#"
                                               class="btn btn-sm align-items-center bs-pass-para"
                                               title="{{ __('Delete') }}"
                                               data-confirm="{{ __('Are You Sure?') . '|' . __('This action cannot be undone.') }}"
                                               data-confirm-yes="document.getElementById('delete-form-{{ $alt->id }}').submit();" >
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                        </form>
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
