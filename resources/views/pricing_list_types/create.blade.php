@extends('layouts.admin')

@section('page-title')
    {{ __('Create Pricing List Type') }}
@endsection

@section('content')

<form action="{{ route('pricing-list-types.store') }}" method="POST">
    @csrf

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{{ __('Name') }}</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       required>
            </div>
        </div>

        <div class="card-footer text-end">
            <button class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('pricing-list-types.index') }}" class="btn btn-secondary">
                {{ __('Cancel') }}
            </a>
        </div>
    </div>
</form>

@endsection
