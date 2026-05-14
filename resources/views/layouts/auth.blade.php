<!DOCTYPE html>
@php
    use App\Models\Utility;

    $setting = Utility::settings();
    $company_favicon = $setting['company_favicon'] ?? '';

    $SITE_RTL = isset($setting['SITE_RTL']) ? $setting['SITE_RTL'] : 'off';
    $lang = \App::getLocale('lang');
    if ($lang == 'ar' || $lang == 'he') {
        $setting['SITE_RTL'] = 'on';
    } elseif ($setting['SITE_RTL'] == 'on') {
        $setting['SITE_RTL'] = 'on';
    } else {
        $setting['SITE_RTL'] = 'off';
    }

    $metatitle = isset($setting['meta_title']) ? $setting['meta_title'] : '';
    $metsdesc = isset($setting['meta_desc']) ? $setting['meta_desc'] : '';

    $get_cookie = isset($setting['enable_cookie']) ? $setting['enable_cookie'] : '';

@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ isset($setting['SITE_RTL']) && $setting['SITE_RTL'] == 'on' ? 'rtl' : '' }}">

<head>
    <title>
        {{ Utility::getValByName('title_text') ? Utility::getValByName('title_text') : config('app.name', 'AutoCore') }}
        - @yield('page-title')</title>

    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metsdesc }}">

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon"
        href="{{ URL::to('/') . '/' . 'storage/uploads/logo' . '/' . (!empty($company_favicon) ? $company_favicon : 'favicon.png') }}"
        type="image/x-icon" />

    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">

    @if ($setting['SITE_RTL'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
    @endif

    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @endif

    @if ($setting['SITE_RTL'] != 'on' && $setting['cust_darklayout'] != 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    @endif

    <style>
        .auth-simple {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f1f5f9;
            color: #0f172a;
        }

        .auth-simple-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
        }

        .auth-simple-header-inner {
            max-width: 28rem;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .auth-simple-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 1.05rem;
            color: #0f172a;
            text-decoration: none;
        }

        .auth-simple-brand:hover {
            color: #2563eb;
        }

        .auth-simple-brand img {
            height: 2rem;
            width: auto;
            max-width: 10rem;
            object-fit: contain;
        }

        .auth-simple-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-simple-panel {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .auth-simple-footer {
            text-align: center;
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            color: #64748b;
            background: #fff;
            border-top: 1px solid #e2e8f0;
        }

        .auth-simple .form-label {
            font-weight: 500;
            font-size: 0.875rem;
        }

        .auth-simple .btn-primary {
            font-weight: 500;
        }

        .auth-simple-lang .dropdown-menu {
            min-width: 8rem;
        }
    </style>

    @if (\App\Models\Utility::getValByName('cust_darklayout') == 'on')
        <style>
            .g-recaptcha {
                filter: invert(1) hue-rotate(180deg) !important;
            }
        </style>
    @endif
</head>

<body class="auth-simple">
    <header class="auth-simple-header">
        <div class="auth-simple-header-inner">
            <a class="auth-simple-brand" href="{{ url('/') }}">
                <img src="{{ asset(config('app.brand_logo')) }}" alt="{{ config('app.name', 'AutoCore') }}">
                <span>{{ config('app.name', 'AutoCore') }}</span>
            </a>
            <div class="auth-simple-lang">
                @yield('language-bar')
            </div>
        </div>
    </header>

    <main class="auth-simple-main">
        <div class="auth-simple-panel">
            @yield('content')
        </div>
    </main>

    <footer class="auth-simple-footer">
        &copy; {{ date('Y') }}
        {{ App\Models\Utility::getValByName('footer_text') ? App\Models\Utility::getValByName('footer_text') : config('app.name', 'AutoCore') }}
    </footer>

    @if ($get_cookie == 'on')
        @include('layouts.cookie_consent')
    @endif

    <script src="{{ asset('assets/js/vendor-all.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    var tokenInput = form.querySelector('input[name="_token"]');
                    var metaToken = document.querySelector('meta[name="csrf-token"]');
                    if (!tokenInput && metaToken) {
                        var hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = '_token';
                        hiddenInput.value = metaToken.getAttribute('content');
                        form.appendChild(hiddenInput);
                    } else if (tokenInput && metaToken) {
                        tokenInput.value = metaToken.getAttribute('content');
                    }
                });
            });
        });
    </script>

    @stack('custom-scripts')
</body>

</html>
