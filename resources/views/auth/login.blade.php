@extends('layouts.auth')
@php
    use App\Models\Utility;
    $settings = Utility::settings();
@endphp
@push('custom-scripts')
    @if ($settings['recaptcha_module'] == 'on')
        {!! NoCaptcha::renderJs() !!}
    @endif
@endpush
@section('page-title')
    {{ __('Login') }}
@endsection

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
                    <a class="dropdown-item" href="{{ route('login', $code) }}">{{ strtoupper($language) }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection

@section('content')
    <div>
        <h1 class="h4 mb-3 fw-semibold">{{ __('Login') }}</h1>
        <p class="text-muted small mb-4">{{ __('Sign in to continue') }}</p>
    </div>
    <form action="{{ route('login') }}" method="POST" id="loginForm" class="login-form">
        @csrf
        @if (session('status'))
            <div class="alert alert-danger small mb-3" role="alert">
                {{ __('Your Account is disable,please contact your Administrator.') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger small mb-3" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->has('_token'))
            <div class="alert alert-danger small mb-3" role="alert">
                {{ __('Your session has expired. Please refresh the page and try again.') }}
            </div>
        @endif
        <div class="mb-3">
            <label class="form-label" for="login-email">{{ __('Email') }}</label>
            <input id="login-email" type="email" name="email" class="form-control" placeholder="{{ __('Email') }}"
                value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="login-password">{{ __('Password') }}</label>
            <input id="login-password" type="password" name="password" class="form-control"
                placeholder="{{ __('Password') }}" required autocomplete="current-password">
            @error('password')
                <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request', $lang) }}">{{ __('Forgot your password?') }}</a>
            @endif
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary" id="saveBtn">{{ __('Login') }}</button>
        </div>
        @if ($settings['enable_signup'] == 'on')
            <p class="small text-center text-muted mt-4 mb-0">{{ __("Don't have an account?") }}
                <a href="{{ route('register', $lang) }}" class="text-decoration-none">{{ __('Register') }}</a>
            </p>
        @endif
        @if ($settings['recaptcha_module'] == 'on')
            <div class="mt-3">
                {!! NoCaptcha::display($settings['cust_darklayout'] == 'on' ? ['data-theme' => 'dark'] : []) !!}
                @error('g-recaptcha-response')
                    <div class="text-danger small mt-2" role="alert">{{ $message }}</div>
                @enderror
            </div>
        @endif
    </form>
@endsection

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $("#loginForm").on('submit', function() {
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"], #saveBtn');
            var tokenInput = $form.find('input[name="_token"]');
            var metaToken = $('meta[name="csrf-token"]');
            if (!tokenInput.length && metaToken.length) {
                $form.append('<input type="hidden" name="_token" value="' + metaToken.attr('content') + '">');
            } else if (tokenInput.length && metaToken.length) {
                tokenInput.val(metaToken.attr('content'));
            }
            $submitBtn.prop('disabled', true).text('{{ __('Logging in...') }}');
            return true;
        });
        $("#form_data").on('submit', function() {
            $("#login_button").attr("disabled", true);
            return true;
        });
        if (window.location.search.indexOf('csrf_error') !== -1) {
            setTimeout(function() {
                window.location.href = window.location.pathname;
            }, 2000);
        }
    });
</script>
