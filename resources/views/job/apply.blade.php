@php
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $setting = App\Models\Utility::colorset();
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';

    $getseo = App\Models\Utility::getSeoSetting();
    $metatitle = isset($getseo['meta_title']) ? $getseo['meta_title'] : '';
    $metsdesc = isset($getseo['meta_desc']) ? $getseo['meta_desc'] : '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = isset($getseo['meta_image']) ? $getseo['meta_image'] : '';
    $get_cookie = \App\Models\Utility::getCookieSetting();

@endphp

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>
        {{ !empty($companySettings['header_text']) ? $companySettings['header_text']->value : config('app.name', 'Orbix') }}
        - {{ __('Career') }}</title>

    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metsdesc }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:title" content="{{ $metatitle }}">
    <meta property="og:description" content="{{ $metsdesc }}">
    <meta property="og:image" content="{{ $meta_image . $meta_logo }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ env('APP_URL') }}">
    <meta property="twitter:title" content="{{ $metatitle }}">
    <meta property="twitter:description" content="{{ $metsdesc }}">
    <meta property="twitter:image" content="{{ $meta_image . $meta_logo }}">


    <link rel="icon" href="{{ $logo . '/' . (isset($favicon) && !empty($favicon) ? $favicon : 'favicon.png') }}"
        type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}" id="stylesheet">
    @if (isset($setting['cust_darklayout']) && $setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}"id="main-style-link">
    @endif
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @if (isset($setting['cust_darklayout']) && $setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('css/custom-dark.css') }}">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="{{ $color }}">
    <div class="job-wrapper">
        <div class="job-content">
            <nav class="navbar">
                <div class="container">
                    <a class="navbar-brand" href="#">
                        <img src="{{ $logo . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-light.png') }}"
                            alt="logo" style="width: 90px">

                    </a>
                </div>
            </nav>
            <section class="job-banner">
                <div class="job-banner-bg">
                    <img src="{{ asset('/storage/uploads/job/banner.png') }}" alt="">
                </div>
                <div class="container">
                    <div class="job-banner-content text-center text-white">
                        <h1 class="text-white mb-3">
                            {{ __(' We help') }} <br> {{ __('businesses grow') }}
                        </h1>
                        <p>{{ __('Work there. Find the dream job you’ve always wanted..') }}</p>
                        </p>
                    </div>
                </div>
            </section>
            <section class="apply-job-section">
                <div class="container">
                    <div class="apply-job-wrapper bg-light">
                        <div class="section-title text-center">
                            <h2 class="h1 mb-3"> {{ $job->title }}</h2>
                            <div class="d-flex flex-wrap justify-content-center gap-1 mb-4">
                                @foreach (explode(',', $job->skill) as $skill)
                                    <span class="badge rounded p-2 bg-primary">{{ $skill }}</span>
                                @endforeach
                            </div>
                            @if (!empty($job->branches) ? $job->branches->name : '')
                                <p> <i class="ti ti-map-pin ms-1"></i>
                                    {{ !empty($job->branches) ? $job->branches->name : '' }}</p>
                            @endif
                        </div>
                        <div class="apply-job-form">
                            <h2 class="mb-4">{{ __('Apply for this job') }}</h2>
                            <form action="{{ route('job.apply.data', $job->code) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" name="name" id="name"
                                                class="form-control name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" name="email" id="email" class="form-control"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" name="phone" id="phone" class="form-control"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        @if (!empty($job->applicant) && in_array('dob', explode(',', $job->applicant)))
                                            <div class="form-group">
                                                <label for="dob" class="form-label">Date of Birth</label>
                                                <input type="date" name="dob" id="dob"
                                                    class="form-control datepicker w-100" required>
                                            </div>
                                        @endif
                                    </div>
                                    @if (!empty($job->applicant) && in_array('gender', explode(',', $job->applicant)))
                                        <div class="form-group col-md-6 ">
                                            <label for="gender" class="form-label">Gender</label>
                                            <div class="d-flex radio-check">
                                                <div class="custom-control custom-radio custom-control-inline">
                                                    <input type="radio" id="g_male" value="Male"
                                                        name="gender" class="custom-control-input">
                                                    <label class="custom-control-label"
                                                        for="g_male">{{ __('Male') }}</label>
                                                </div>
                                                <div class="custom-control custom-radio custom-control-inline">
                                                    <input type="radio" id="g_female" value="Female"
                                                        name="gender" class="custom-control-input">
                                                    <label class="custom-control-label"
                                                        for="g_female">{{ __('Female') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($job->applicant) && in_array('country', explode(',', $job->applicant)))
                                        <div class="form-group col-md-6 ">
                                            <label for="country" class="form-label">Country</label>
                                            <input type="text" name="country" id="country" class="form-control"
                                                required>
                                        </div>
                                        <div class="form-group col-md-6 country">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" name="state" id="state" class="form-control"
                                                required>
                                        </div>
                                        <div class="form-group col-md-6 country">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" name="city" id="city" class="form-control"
                                                required>
                                        </div>
                                    @endif

                                    @if (!empty($job->visibility) && in_array('profile', explode(',', $job->visibility)))
                                        <div class="form-group col-md-6 ">
                                            <label for="profile" class="col-form-label">Profile</label>
                                            <input type="file" class="form-control" name="profile" id="profile"
                                                data-filename="profile_create"
                                                onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])">
                                            <img id="blah" src="" class="mt-3" width="25%" />
                                            <p class="profile_create"></p>
                                        </div>
                                    @endif

                                    @if (!empty($job->visibility) && in_array('resume', explode(',', $job->visibility)))
                                        <div class="form-group col-md-6 ">
                                            <label for="resume" class="col-form-label">CV / Resume</label>
                                            <input type="file" class="form-control" name="resume" id="resume"
                                                data-filename="resume_create"
                                                onchange="document.getElementById('blah1').src = window.URL.createObjectURL(this.files[0])"
                                                required>
                                            <img id="blah1" class="mt-3" src="" width="25%" />
                                            <p class="resume_create"></p>
                                        </div>
                                    @endif

                                    @if (!empty($job->visibility) && in_array('letter', explode(',', $job->visibility)))
                                        <div class="form-group col-md-12 ">
                                            <label for="cover_letter" class="form-label">Cover Letter</label>
                                            <textarea name="cover_letter" id="cover_letter" class="form-control" rows="3"></textarea>
                                        </div>
                                    @endif

                                    @foreach ($questions as $question)
                                        <div class="form-group col-md-12  question question_{{ $question->id }}">
                                            <label for="question_{{ $question->id }}"
                                                class="form-label">{{ $question->question }}</label>
                                            <input type="text" class="form-control"
                                                name="question[{{ $question->question }}]"
                                                {{ $question->is_required == 'yes' ? 'required' : '' }}>
                                        </div>
                                    @endforeach
                                    <div class="col-12">
                                        <div class="text-center mt-4">
                                            <button type="submit"
                                                class="btn btn-primary">{{ __('Submit your application') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white  fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"> </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>

    <script src="{{ asset('js/site.core.js') }}"></script>
    <script src="{{ asset('js/site.js') }}"></script>
    <script src="{{ asset('js/demo.js') }} "></script>
    <script src="{{ asset('js/custom.js') }}"></script>

</body>

@if ($message = Session::get('success'))
    <script>
        show_toastr('success', '{!! $message !!}');
    </script>
@endif
@if ($message = Session::get('error'))
    <script>
        show_toastr('error', '{!! $message !!}');
    </script>
@endif

@if ($get_cookie['enable_cookie'] == 'on')
    @include('layouts.cookie_consent')
@endif

</body>



</html>
