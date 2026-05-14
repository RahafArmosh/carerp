@extends('layouts.admin')
@section('page-title')
    {{ __('Create Job') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('job.index') }}">{{ __('Job') }}</a></li>
    <li class="breadcrumb-item">{{ __('Job Create') }}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">
    <link href="{{ asset('css/bootstrap-tagsinput.css') }}" rel="stylesheet" />
@endpush
@push('script-page')
    <script src="{{ asset('js/bootstrap-tagsinput.min.js') }}"></script>
    <script>
        var e = $('[data-toggle="tags"]');
        e.length && e.each(function() {
            $(this).tagsinput({
                tagClass: "badge badge-primary"
            })
        });
    </script>
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
@endpush
@php
    $plan = \App\Models\Utility::getChatGPTSettings();
@endphp
@section('action-btn')
    <div class="float-end">
        {{-- start for ai module --}}

        @if ($plan->chatgpt == 1)
            <a href="#" data-size="lg" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['job']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"> </i> <span>{{ __('Generate with AI') }}</span>
            </a>
        @endif
        {{-- end for ai module --}}
    </div>
@endsection
@section('content')
    <form action="job" method="post">
        @csrf
        <div class="row mt-3">
            <div class="col-md-6 ">
                <div class="card card-fluid">
                    <div class="card-body job-create ">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="title" class="form-label">{{ __('Job Title') }}</label>
                                <input type="text" id="title" name="title" class="form-control"
                                    value="{{ old('title') }}" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="branch" class="form-label">{{ __('Branch') }}</label>
                                <select id="branch" name="branch" class="form-control select" required>
                                    @foreach ($branches as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="category" class="form-label">{{ __('Job Category') }}</label>
                                <select id="category" name="category" class="form-control select" required>
                                    @foreach ($categories as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="position" class="form-label">{{ __('Positions') }}</label>
                                <input type="number" id="position" name="position" class="form-control"
                                    value="{{ old('positions') }}" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                <select id="status" name="status" class="form-control select" required>
                                    @foreach ($status as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                <input type="date" id="start_date" name="start_date" class="form-control"
                                    value="{{ old('start_date') }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                <input type="date" id="end_date" name="end_date" class="form-control"
                                    value="{{ old('end_date') }}">
                            </div>

                            <div class="form-group col-md-12">
                                <input type="text" class="form-control" value="" data-toggle="tags" name="skill"
                                    placeholder="Skill" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 ">
                <div class="card card-fluid">
                    <div class="card-body job-create">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <h6>{{ __('Need to ask ?') }}</h6>
                                    <div class="my-4">
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="applicant[]"
                                                value="gender" id="check-gender">
                                            <label class="form-check-label" for="check-gender">{{ __('Gender') }} </label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="applicant[]"
                                                value="dob" id="check-dob">
                                            <label class="form-check-label"
                                                for="check-dob">{{ __('Date Of Birth') }}</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="applicant[]"
                                                value="country" id="check-country">
                                            <label class="form-check-label"
                                                for="check-country">{{ __('Country') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <h6>{{ __('Need to show option ?') }}</h6>
                                    <div class="my-4">
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="visibility[]"
                                                value="profile" id="check-profile">
                                            <label class="form-check-label" for="check-profile">{{ __('Profile Image') }}
                                            </label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="visibility[]"
                                                value="resume" id="check-resume">
                                            <label class="form-check-label"
                                                for="check-resume">{{ __('Resume') }}</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="visibility[]"
                                                value="letter" id="check-letter">
                                            <label class="form-check-label"
                                                for="check-letter">{{ __('Cover Letter') }}</label>
                                        </div>
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="visibility[]"
                                                value="terms" id="check-terms">
                                            <label class="form-check-label"
                                                for="check-terms">{{ __('Terms And Conditions') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <h6>{{ __('Custom Question') }}</h6>
                                <div class="my-4">
                                    @foreach ($customQuestion as $question)
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input" name="custom_question[]"
                                                value="{{ $question->id }}" id="custom_question_{{ $question->id }}">
                                            <label class="form-check-label"
                                                for="custom_question_{{ $question->id }}">{{ $question->question }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-fluid">
                    <div class="card-body ">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="description" class="form-label">{{ __('Job Description') }}</label>
                                <textarea class="form-control summernote-simple-2" name="description" id="exampleFormControlTextarea1"
                                    rows="15"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-fluid">
                    <div class="card-body">
                        <div class="row ">
                            <div class="form-group col-6 mb-2">
                                <label for="requirement" class="form-label">{{ __('Job Requirement') }}</label>
                            </div>
                            <div class="col-6 text-end">
                                @if ($plan->chatgpt == 1)
                                    <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm"
                                        data-ajax-popup-over="true" id="grammarCheck"
                                        data-url="{{ route('grammar', ['grammar']) }}" data-bs-placement="top"
                                        data-title="{{ __('Grammar check with AI') }}">
                                        <i class="ti ti-rotate"></i> <span>{{ __('Grammar check with AI') }}</span>
                                    </a>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <textarea class="form-control summernote-simple" name="requirement" id="exampleFormControlTextarea2" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 text-end">
                <div class="form-group">
                    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
                </div>
            </div>
    </form>
    </div>
@endsection
