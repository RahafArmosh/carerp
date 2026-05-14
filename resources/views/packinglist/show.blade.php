@extends('layouts.admin')
@section('page-title')
    {{ __('Packing List Details') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('packinglist.index') }}">{{ __('Packing Lists') }}</a></li>
    <li class="breadcrumb-item">{{ __('Show') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create sale order')
            @if(($packingList->status ?? '') !== 'packing_completed')
            <a href="{{ route('packinglist.edit', \Crypt::encrypt($packingList->id)) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i> {{ __('Add Products') }}
            </a>
            @endif
            @if($packingList->saleOrder)
                <a href="{{ route('saleorder.show', \Crypt::encrypt($packingList->sale_order_id)) }}" class="btn btn-sm btn-info ms-2">
                    <i class="ti ti-file-text"></i> {{ __('View Sale Order') }}
                </a>
            @endif
            @if($packingList->pickList)
                <a href="{{ route('picklist.show', \Crypt::encrypt($packingList->pick_list_id)) }}" class="btn btn-sm btn-warning ms-2">
                    <i class="ti ti-clipboard-list"></i> {{ __('View Pick List') }}
                </a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Packing List Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Packing List No') }}</th>
                                    <td>{{ \Auth::user()->packingListNumberFormat($packingList->packing_list_no) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Sale Order') }}</th>
                                    <td>
                                        @if($packingList->saleOrder)
                                            <a href="{{ route('saleorder.show', \Crypt::encrypt($packingList->sale_order_id)) }}" class="btn btn-outline-primary btn-sm">
                                                {{ \Auth::user()->saleOrderNumberFormat($packingList->saleOrder->sale_order_no) }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Pick List') }}</th>
                                    <td>
                                        @if($packingList->pickList)
                                            <a href="{{ route('picklist.show', \Crypt::encrypt($packingList->pick_list_id)) }}" class="btn btn-outline-info btn-sm">
                                                #{{ $packingList->pickList->id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <td>{{ $packingList->customer->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th>{{ __('Packing Ref') }}</th>
                                    <td>{{ $packingList->packing_ref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Packing List Date') }}</th>
                                    <td>{{ Auth::user()->dateFormat($packingList->packing_list_date) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Packed By') }}</th>
                                    <td>{{ $packingList->packer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <td>
                                        <form action="{{ route('packinglist.update-status', \Crypt::encrypt($packingList->id)) }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto" style="max-width: 200px;">
                                                <option value="draft" {{ ($packingList->status ?? 'draft') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                                <option value="under_packing" {{ ($packingList->status ?? '') === 'under_packing' ? 'selected' : '' }}>{{ __('Under Packing') }}</option>
                                                <option value="partially_packed" {{ ($packingList->status ?? '') === 'partially_packed' ? 'selected' : '' }}>{{ __('Partially Packed') }}</option>
                                                <option value="packing_completed" {{ in_array($packingList->status ?? '', ['packing_completed', 'packed', 'shipped', 'delivered']) ? 'selected' : '' }}>{{ __('Packing Completed') }}</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Update') }}</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created By') }}</th>
                                    <td>{{ $packingList->creator->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('Created At') }}</th>
                                    <td>{{ Auth::user()->dateFormat($packingList->created_at) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>{{ __('Items') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('Box No') }}</th>
                                    <th>{{ __('Part No') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Packed QTY') }}</th>
                                    <th>{{ __('Box L') }}</th>
                                    <th>{{ __('Box W') }}</th>
                                    <th>{{ __('Box H') }}</th>
                                    <th>{{ __('Box Weight') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packingList->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->box_no ?? '-' }}</td>
                                        <td>{{ $item->part_no ?? '-' }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td>{{ number_format($item->packed_qty, 2) }}</td>
                                        <td>{{ $item->box_l ? number_format($item->box_l, 2) : '-' }}</td>
                                        <td>{{ $item->box_w ? number_format($item->box_w, 2) : '-' }}</td>
                                        <td>{{ $item->box_h ? number_format($item->box_h, 2) : '-' }}</td>
                                        <td>{{ $item->box_weight ? number_format($item->box_weight, 2) : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('No items found') }}</td>
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
