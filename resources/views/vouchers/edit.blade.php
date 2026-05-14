@extends('layouts.admin')

@section('page-title')
    {{ __('Add Combo Offer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vouchers.index') }}">{{ __('Vouchers') }}</a></li>
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
            <form method="POST" action="{{ route('vouchers.update', $voucher->id) }}">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    {{-- Show if voucher is from refund --}}
                    @if($voucher->posRefund)
                        <div class="alert alert-warning mb-3">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-arrow-back-up me-2"></i>
                                <div>
                                    <strong>{{ __('This voucher was created from a POS refund') }}</strong>
                                    @if($voucher->posRefund->pos)
                                        <br>
                                        <small>
                                            {{ __('POS') }}: 
                                            <a href="{{ route('pos.show', \Crypt::encrypt($voucher->posRefund->pos->id)) }}" target="_blank" class="text-primary">
                                                {{ \Auth::user()->posNumberFormat($voucher->posRefund->pos->pos_id) }}
                                            </a>
                                            | 
                                            {{ __('Refund ID') }}: 
                                            <a href="{{ route('pos_product_refund.print', $voucher->posRefund->id) }}" target="_blank" class="text-primary">
                                                #{{ $voucher->posRefund->id }}
                                            </a>
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="row">

                        {{-- Customer --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Customer') }}</label>
                            <select class="form-control select" name="customer_id" required>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $voucher->customer_id == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                         <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Chart Of Accounts') }}</label>
                            <select name="chart_of_account_id" class="form-control select" required>
                                <option value="" disabled selected>{{ __('Select chart of accounts') }}</option>
                                @foreach ($chart_of_accounts as $coa)
                                    <option value="{{ $coa->id }}" {{ $voucher->chart_of_account_id == $coa->id ? 'selected' : '' }}>{{ $coa->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ $voucher->amount }}" required>
                        </div>

                        {{-- Valid Until --}}
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Valid Until') }}</label>
                            <input type="date" name="valid_until" class="form-control" value="{{ $voucher->valid_until ? $voucher->valid_until : '' }}" required>
                        </div>

                        {{-- Active --}}
                        <div class="form-group col-md-12">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                                    {{ $voucher->active ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">{{ __('Active') }}</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal" onclick="history.back()">
                    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
    
    @if(isset($logs))
        @include('partials.pos_logs', ['logs' => $logs])
    @endif

@endsection

@push('script-page')>
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
        toggleFields(); // run once on page load
    });
</script>
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
    toggleFields(); // On load

    // Handle tiered pricing rows
    const wrapper = document.getElementById('tiered-price-wrapper');
    const addBtn = document.getElementById('add-tier-row');
    const jsonField = document.getElementById('tiered_prices_json');

    addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row mb-2 tiered-price-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="number" name="tiered_qty[]" class="form-control" placeholder="Quantity" min="1">
            </div>
            <div class="col-md-5">
                <input type="number" name="tiered_price[]" class="form-control" placeholder="Price" step="0.01" min="0">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-row">×</button>
            </div>
        `;
        wrapper.appendChild(row);
    });

    wrapper.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.tiered-price-row').remove();
        }
    });

    // Before submit, convert to JSON
    document.querySelector('form').addEventListener('submit', function () {
        const qtys = document.querySelectorAll('input[name="tiered_qty[]"]');
        const prices = document.querySelectorAll('input[name="tiered_price[]"]');
        const data = {};

        for (let i = 0; i < qtys.length; i++) {
            const qty = qtys[i].value;
            const price = prices[i].value;
            if (qty && price) {
                data[qty] = parseFloat(price);
            }
        }

        jsonField.value = JSON.stringify(data);
    });
});
document.querySelector('input[type="submit"]').addEventListener('click', function () {
    const qtys = document.querySelectorAll('input[name="tiered_qty[]"]');
    const prices = document.querySelectorAll('input[name="tiered_price[]"]');
    const data = {};

    for (let i = 0; i < qtys.length; i++) {
        const qty = qtys[i].value;
        const price = prices[i].value;
        if (qty && price) {
            data[qty] = parseFloat(price);
        }
    }

    document.getElementById('tiered_prices_json').value = JSON.stringify(data);
});

</script>
</script>
@endpush

