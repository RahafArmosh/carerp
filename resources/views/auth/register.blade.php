@extends('layouts.auth')
@section('page-title')
    {{ __('Register') }}
@endsection
@php
    $settings = Utility::settings();
@endphp
@push('custom-scripts')
@if ($settings['recaptcha_module'] == 'on')
        {!! NoCaptcha::renderJs() !!}
    @endif
@endpush
@php
    $languages = App\Models\Utility::languages();
@endphp
@section('language-bar')
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            {{ strtoupper($languages[$lang] ?? $lang) }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            @foreach ($languages as $code => $language)
                <li>
                    <a class="dropdown-item" href="{{ route('register', $code) }}">{{ strtoupper($language) }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection

@section('content')
    <div>
        <h1 class="h4 mb-3 fw-semibold">{{ __('Register') }}</h1>
        <p class="text-muted small mb-4">{{ __('Create your account') }}</p>
    </div>
        <form method="POST" action="{{ route('register') }}">
            @if (session('status'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ __('Email SMTP settings does not configured so please contact to your site admin.') }}
                </div>
            @endif
            @csrf
            <div class="">
                <div class="form-group mb-3">
                    <label for="name" class="form-label">{{__('Name')}}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="email" class="form-label">{{__('Email')}}</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                    <div class="invalid-feedback">
                        {{__('Please fill in your email')}}
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="password" class="form-label">{{__('Password')}}</label>
                    <input id="password" type="password" data-indicator="pwindicator" class="form-control pwstrength @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                    @error('password')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                    <div id="pwindicator" class="pwindicator">
                        <div class="bar"></div>
                        <div class="label"></div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="password_confirmation" class="form-label">{{__('Password Confirmation')}}</label>
                    <input id="password_confirmation" type="password" data-indicator="password_confirmation" class="form-control pwstrength @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password">
                    @error('password_confirmation')
                    <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                    @enderror
                    <div id="password_confirmation" class="pwindicator">
                        <div class="bar"></div>
                        <div class="label"></div>
                    </div>
                </div>
                @if ($settings['recaptcha_module'] == 'on')
                    <div class="form-group mb-3">
                        {!! NoCaptcha::display($settings['cust_darklayout']=='on' ? ['data-theme' => 'dark'] : []) !!}
                        @error('g-recaptcha-response')
                        <span class="small text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                @endif
    
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block mt-2">{{__('Register')}}</button>
                </div>
    
            </div>
            <p class="small text-center text-muted mt-4 mb-0">{{ __('Already have an account?') }}
                <a href="{{ route('login', $lang) }}" class="text-decoration-none">{{ __('Login') }}</a>
            </p>
        </form>
@endsection
