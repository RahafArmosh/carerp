@extends('layouts.admin')
@push('script-page')
@endpush
@section('page-title')
    {{ __('Transaction') }}
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="row d-flex justify-content-end mt-2">
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                            <form action="{{ route('vender.transaction') }}" method="GET" id="frm_submit">
                                @csrf
                                <div class="all-select-box">
                                    <div class="btn-box">
                                        <label for="date" class="text-type">{{ __('Date') }}</label>
                                        <input type="text" id="date" name="date"
                                            class="form-control datepicker-range"
                                            value="{{ isset($_GET['date']) ? $_GET['date'] : '' }}">

                                    </div>
                                </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                            <div class="all-select-box">
                                <div class="btn-box">
                                    <label for="category" class="text-type">{{ __('Category') }}</label>
                                    <select id="category" name="category" class="form-control select2">
                                        <option value="">All</option>
                                        @foreach ($category as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ isset($_GET['category']) && $_GET['category'] == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>
                            </div>
                        </div>
                        <div class="col-auto my-auto">
                            <a href="#" class="apply-btn"
                                onclick="document.getElementById('frm_submit').submit(); return false;"
                                data-toggle="tooltip" data-original-title="{{ __('apply') }}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                            <a href="{{ route('vender.transaction') }}" class="reset-btn" data-toggle="tooltip"
                                data-original-title="{{ __('Reset') }}">
                                <span class="btn-inner--icon"><i class="ti ti-trash-restore-alt"></i></span>
                            </a>

                        </div>
                    </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 dataTable">
                            <thead>
                                <tr>
                                    <th> {{ __('Date') }}</th>
                                    <th> {{ __('Amount') }}</th>
                                    <th> {{ __('Account') }}</th>
                                    <th> {{ __('Type') }}</th>
                                    <th> {{ __('Category') }}</th>
                                    <th> {{ __('Description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ Auth::user()->dateFormat($transaction->date) }}</td>
                                        <td>{{ Auth::user()->priceFormat($transaction->amount) }}</td>
                                        <td>{{ !empty($transaction->bankAccount()) ? $transaction->bankAccount()->bank_name . ' ' . $transaction->bankAccount()->holder_name : '' }}
                                        </td>
                                        <td>{{ $transaction->type }}</td>
                                        <td>{{ $transaction->category }}</td>
                                        <td>{{ $transaction->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
