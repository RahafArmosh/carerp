@extends('layouts.admin')

@section('page-title')
    {{ __('Add Combo Offer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vouchers.index') }}">{{ __('Voucheres') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add') }}</li>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
    <div class="col-xl-12">
        <form method="POST" action="{{ route('vouchers.store') }}">
            @csrf
            <div class="modal-body">
                <div class="row">

                    {{-- Customer --}}
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Customer') }}</label>
                        <select name="customer_id" class="form-control select2" required>
                            <option value="" disabled selected>{{ __('Select Customer') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Chart Of Accounts') }}</label>
                        <select name="chart_of_account_id" class="form-control select2" required>
                            <option value="" disabled selected>{{ __('Select chart of accounts') }}</option>
                            @foreach ($chart_of_accounts as $coa)
                                <option value="{{ $coa->id }}">{{ $coa->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Amount --}}
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="{{ __('Enter Amount') }}" required>
                    </div>

                    {{-- Valid Until --}}
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Valid Until') }}</label>
                        <input type="date" name="valid_until" class="form-control" required>
                    </div>

                    {{-- Active --}}
                    <div class="form-group col-md-12">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                            <label class="form-check-label" for="active">{{ __('Active') }}</label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal" onclick="history.back()">
                    <input type="submit" value="{{ __('Save') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
</div>

@endsection
@push('script-page')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const comboType = document.getElementById('combo_type');
        const bogoFields = document.querySelectorAll('.bogo-fields');
        const tieredFields = document.querySelectorAll('.tiered-fields');

        function toggleFields() {
            const type = comboType.value;
            bogoFields.forEach(f => f.classList.toggle('d-none', type !== 'bogo'));
            tieredFields.forEach(f => f.classList.toggle('d-none', type !== 'tiered_pricing'));
        }

        comboType.addEventListener('change', toggleFields);

        // Trigger on page load if editing
        toggleFields();
    });
</script>
@endpush


