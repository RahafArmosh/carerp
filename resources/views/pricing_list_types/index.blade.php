@extends('layouts.admin')

@section('page-title')
    {{ __('Pricing List Types') }}
@endsection

@section('action-btn')
   
    <div class="float-end"><a href="{{ route('pricing-list-types.create') }}"data-size="lg" data-bs-toggle="tooltip"
            title="{{ __('Create') }}" data-title="{{ __('Add Price Rule') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i></a></div>
    
@endsection

@section('content')

<div class="card">
    <div class="card-body table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Created By') }}</th>
                    <th width="150">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($types as $type)
                    <tr>
                        <td>{{ $type->name }}</td>
                        <td>{{ $type->created_by }}</td>
                        <td>
                            <a href="{{ route('pricing-list-types.edit', $type->id) }}"
                               class="btn btn-sm btn-info">
                                {{ __('Edit') }}
                            </a>

                            <form action="{{ route('pricing-list-types.destroy', $type->id) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach

                @if($types->isEmpty())
                    <tr>
                        <td colspan="3" class="text-center text-muted">
                            {{ __('No pricing list types found.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
