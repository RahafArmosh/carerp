@extends('layouts.auth')
@php
    //  $logo=asset(Storage::url('uploads/logo/'));
    $logo = \App\Models\Utility::get_file('uploads/logo');
    $company_logo = Utility::getValByName('company_logo');
@endphp
@section('page-title')
    {{ __('Forgot Password') }}
@endsection
@section('auth-topbar')
@endsection
@section('content')
    <div class="card-body">
        <div>
            <h2 class="mb-3 f-w-600"><span class="text-primary">{{ __('Reset Password!') }}</span></h2>
            {{-- <p>{{ __('Sign in by entering the information below?') }} </p> --}}
        </div>
        <form action="{{ route('password.update') }}" method="post" id="loginForm">
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="">
                <div class="form-group mb-3">
                    <label for="email" class="form-label">E-Mail Address</label>
                    <input type="email" id="email" name="email" class="form-control">
                    @error('email')
                        <span class="invalid-email text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control">
                    @error('password')
                        <span class="invalid-password text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="password_confirmation" class="form-label">Password Confirmation</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                    @error('password_confirmation')
                        <span class="invalid-password_confirmation text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block mt-2" id="resetBtn">Reset</button>
                </div>

            </div>
        </form>
    </div>
@endsection
