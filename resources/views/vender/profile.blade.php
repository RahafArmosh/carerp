@extends('layouts.admin')
@php
    //  $profile=asset(Storage::url('uploads/avatar/'));
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
@endphp
@section('page-title')
    {{ __('Profile Account') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-12">
            <div class="card profile-card">
                <div class="icon-user avatar rounded-circle">
                    <img alt=""
                        src="{{ !empty($userDetail->avatar) ? $profile . '/' . $userDetail->avatar : $profile . '/avatar.png' }}"
                        class="">
                </div>
                <h4 class="h4 mb-0 mt-2"> {{ $userDetail->name }}</h4>
                <div class="sal-right-card">
                    <span class="badge badge-pill badge-blue">{{ $userDetail->type }}</span>
                </div>
                <h6 class="office-time mb-0 mt-4">{{ $userDetail->email }}</h6>
            </div>
        </div>
        <div class="col-xl-9 col-lg-8 col-md-8 col-sm-12">
            <section class="col-lg-12 pricing-plan card">
                <div class="our-system password-card p-3">
                    <div class="row">
                        <ul class="nav nav-tabs my-4">
                            <li>
                                <a data-toggle="tab" href="#personal-info" class="active">{{ __('Personal Info') }}</a>
                            </li>
                            <li class="annual-billing">
                                <a data-toggle="tab" href="#billing-info" class="">{{ __('Billing Info') }}</span>
                                </a>
                            </li>
                            <li class="annual-billing">
                                <a data-toggle="tab" href="#shipping-info" class="">{{ __('Shipping Info') }}</span>
                                </a>
                            </li>
                            <li class="annual-billing">
                                <a data-toggle="tab" href="#change-password"
                                    class="">{{ __('Change Password') }}</span> </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div id="personal-info" class="tab-pane in active">
                                <form action="{{ route('vender.update.profile') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    {{-- <input type="hidden" name="_method" value="POST"> --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name" class="form-label">{{ __('Name') }}</label>
                                                <input type="text" id="name" name="name"
                                                    class="form-control font-style"
                                                    placeholder="{{ __('Enter User Name') }}">
                                                @error('name')
                                                    <span class="invalid-name" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">{{ __('Email') }}</label>
                                            <input type="text" id="email" name="email" class="form-control"
                                                placeholder="{{ __('Enter User Email') }}">
                                            @error('email')
                                                <span class="invalid-email" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="contact" class="form-label">{{ __('Contact') }}</label>
                                            <input type="text" id="contact" name="contact" class="form-control"
                                                placeholder="{{ __('Enter User Contact') }}">

                                            @error('contact')
                                                <span class="invalid-contact" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        @if (!$customFields->isEmpty())
                                            <div class="col-md-6">
                                                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                    @include('customFields.formBuilder')
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-lg-6 col-md-6">
                                            <div class="form-group">
                                                <div class="choose-file">
                                                    <label for="avatar">
                                                        <div>{{ __('Choose file here') }}</div>
                                                        <input type="file" class="form-control" id="avatar"
                                                            name="profile" data-filename="profiles">
                                                    </label>
                                                    <p class="profiles"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12 text-end">
                                            <input type="submit" value="{{ __('Save Changes') }}"
                                                class="btn-create badge-blue">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="billing-info" class="tab-pane">
                                <form method="POST" action="{{ route('vender.update.billing.info') }}">
                                    @csrf
                                    {{-- <input type="hidden" name="_method" value="POST"> --}}
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_name"
                                                    class="form-label">{{ __('Billing Name') }}</label>
                                                <input id="billing_name" type="text" name="billing_name"
                                                    class="form-control" placeholder="{{ __('Enter Billing Name') }}">

                                                @error('billing_name')
                                                    <span class="invalid-billing_name" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_phone"
                                                    class="form-label">{{ __('Billing Phone') }}</label>
                                                <input id="billing_phone" type="text" name="billing_phone"
                                                    class="form-control" placeholder="{{ __('Enter Billing Phone') }}">
                                                @error('billing_phone')
                                                    <span class="invalid-billing_phone" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_zip"
                                                    class="form-label">{{ __('Billing Zip') }}</label>
                                                <input id="billing_zip" type="text" name="billing_zip"
                                                    class="form-control" placeholder="{{ __('Enter Billing Zip') }}">

                                                @error('billing_zip')
                                                    <span class="invalid-billing_zip" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_country"
                                                    class="form-label">{{ __('Billing Country') }}</label>
                                                <input id="billing_country" type="text" name="billing_country"
                                                    class="form-control" placeholder="{{ __('Enter Billing Country') }}">

                                                @error('billing_country')
                                                    <span class="invalid-billing_country" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_state"
                                                    class="form-label">{{ __('Billing State') }}</label>
                                                <input id="billing_state" type="text" name="billing_state"
                                                    class="form-control" placeholder="{{ __('Enter Billing State') }}">

                                                @error('billing_state')
                                                    <span class="invalid-billing_state" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="billing_city"
                                                    class="form-label">{{ __('Billing City') }}</label>
                                                <input id="billing_city" type="text" name="billing_city"
                                                    class="form-control" placeholder="{{ __('Enter Billing City') }}">

                                                @error('billing_city')
                                                    <span class="invalid-billing_city" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="billing_address"
                                                    class="form-label">{{ __('Billing Address') }}</label>
                                                <textarea id="billing_address" name="billing_address" class="form-control" rows="3"
                                                    placeholder="{{ __('Enter Billing Address') }}"></textarea>

                                                @error('billing_address')
                                                    <span class="invalid-billing_address" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-lg-12 text-end">
                                            <input type="submit" value="{{ __('Save Changes') }}"
                                                class="btn-create badge-blue">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="shipping-info" class="tab-pane">
                                <form action="{{ route('vender.update.shipping.info') }}" method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_name"
                                                    class="form-label">{{ __('Shipping Name') }}</label>
                                                <input type="text" name="shipping_name" id="shipping_name"
                                                    class="form-control" placeholder="{{ __('Enter Shipping Name') }}">

                                                @error('shipping_name')
                                                    <span class="invalid-shipping_name" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_phone"
                                                    class="form-label">{{ __('Shipping Phone') }}</label>
                                                <input type="text" name="shipping_phone" id="shipping_phone"
                                                    class="form-control" placeholder="{{ __('Enter Shipping Phone') }}">

                                                @error('shipping_phone')
                                                    <span class="invalid-shipping_phone" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_zip"
                                                    class="form-label">{{ __('Shipping Zip') }}</label>
                                                <input type="text" name="shipping_zip" id="shipping_zip"
                                                    class="form-control" placeholder="{{ __('Enter Shipping Zip') }}">

                                                @error('shipping_zip')
                                                    <span class="invalid-shipping_zip" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_country"
                                                    class="form-label">{{ __('Shipping Country') }}</label>
                                                <input type="text" name="shipping_country" id="shipping_country"
                                                    class="form-control"
                                                    placeholder="{{ __('Enter Shipping Country') }}">

                                                @error('shipping_country')
                                                    <span class="invalid-shipping_country" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_state"
                                                    class="form-label">{{ __('Shipping State') }}</label>
                                                <input type="text" name="shipping_state" id="shipping_state"
                                                    class="form-control" placeholder="{{ __('Enter Shipping State') }}">

                                                @error('shipping_state')
                                                    <span class="invalid-shipping_state" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="shipping_city"
                                                    class="form-label">{{ __('Shipping City') }}</label>
                                                <input type="text" name="shipping_city" id="shipping_city"
                                                    class="form-control" placeholder="{{ __('Enter Shipping City') }}">

                                                @error('shipping_city')
                                                    <span class="invalid-shipping_city" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="shipping_address"
                                                    class="form-label">{{ __('Shipping Address') }}</label>
                                                <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3"
                                                    placeholder="{{ __('Enter Shipping Address') }}"></textarea>

                                                @error('shipping_address')
                                                    <span class="invalid-billing_address" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-lg-12 text-end">
                                            <input type="submit" value="{{ __('Save Changes') }}"
                                                class="btn-create badge-blue">
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="change-password" class="tab-pane">
                                <form method="POST" action="{{ route('vender.update.password', $userDetail->id) }}">
                                    @csrf
                                    @method('POST')
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6">
                                            <div class="form-group">
                                                <label for="current_password"
                                                    class="form-label">{{ __('Current Password') }}</label>
                                                <input type="password" id="current_password" name="current_password"
                                                    class="form-control"
                                                    placeholder="{{ __('Enter Current Password') }}">

                                                @error('current_password')
                                                    <span class="invalid-current_password" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6">
                                            <div class="form-group">
                                                <label for="new_password"
                                                    class="form-label">{{ __('New Password') }}</label>
                                                <input type="password" id="new_password" name="new_password"
                                                    class="form-control" placeholder="{{ __('Enter New Password') }}">

                                                @error('new_password')
                                                    <span class="invalid-new_password" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="confirm_password"
                                                    class="form-label">{{ __('Re-type New Password') }}</label>
                                                <input type="password" id="confirm_password" name="confirm_password"
                                                    class="form-control"
                                                    placeholder="{{ __('Enter Re-type New Password') }}">

                                                @error('confirm_password')
                                                    <span class="invalid-confirm_password" role="alert">
                                                        <strong class="text-danger">{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12 text-end">
                                            <input type="submit" value="{{ __('Save Changes') }}"
                                                class="btn-create badge-blue">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
