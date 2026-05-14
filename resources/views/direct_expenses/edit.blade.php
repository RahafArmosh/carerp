@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Direct Expenses') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('direct_expenses.index') }}">{{ __('Direct Expenses') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Direct Expense') }}</li>
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('direct_expenses.update', $directExpense) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Header Section -->
                    <h5 class="mb-4">{{ __('Expense Header') }}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expense_number" class="form-label">{{ __('Expense Number') }}<span class="text-danger">*</span></label>
                                <input type="text" name="expense_number" id="expense_number" class="form-control" value="{{ $directExpense->expense_number }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expense_date" class="form-label">{{ __('Expense Date') }}<span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" id="expense_date" class="form-control" value="{{ $directExpense->expense_date ? \Carbon\Carbon::parse($directExpense->expense_date)->format('Y-m-d') : now()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="vendor_id" class="form-label">{{ __('Vendor') }}<span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">{{ __('Select Vendor') }}</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" {{ $directExpense->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control select2">
                                    <option value="">{{ __('Select Currency') }}</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" {{ $directExpense->currency_id == $currency->id ? 'selected' : '' }}>{{ $currency->code }} - {{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" id="exchange_rate_div" style="{{ $directExpense->currency_id ? '' : 'display:none;' }}">
                            <div class="form-group">
                                <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.0001" min="0" value="{{ $directExpense->exchange_rate ?? 1 }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                <select name="tax_id[]" id="tax_id" class="form-control select2" multiple>
                                    @php 
                                        $selectedTaxIds = $directExpense->tax_id ? explode(',', $directExpense->tax_id) : [];
                                    @endphp
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}" {{ in_array((string)$tax->id, array_map('strval', $selectedTaxIds)) ? 'selected' : '' }}>{{ $tax->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                                <input type="file" name="attachment" id="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="text-muted">{{ __('Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG') }}</small>
                                @if($directExpense->attachment)
                                    <div class="mt-2">
                                        <small class="text-muted">{{ __('Current attachment:') }}</small>
                                        <a href="{{ asset('storage/uploads/direct_expenses/' . $directExpense->attachment) }}" target="_blank" class="ms-2">
                                            <i class="ti ti-file"></i> {{ $directExpense->attachment }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <!-- Items Section -->
                    <h5 class="mb-4">{{ __('Expense Items') }}</h5>
                    <div id="items-container">
                        @foreach($directExpense->items as $index => $item)
                            <div class="item-row mb-3 border p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ __('Sub Product') }}<span class="text-danger">*</span></label>
                                            <select name="items[{{ $index }}][sub_product_id]" class="form-control select2 item-sub-product" required>
                                                <option value="">{{ __('Select Sub Product') }}</option>
                                                @foreach($subProducts as $subProductId => $subProductName)
                                                    <option value="{{ $subProductId }}" {{ $item->sub_product_id == $subProductId ? 'selected' : '' }}>{{ $subProductName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ __('Chart Account') }}</label>
                                            <select name="items[{{ $index }}][chart_account_id]" class="form-control select2">
                                                <option value="">{{ __('Select Account') }}</option>
                                                @foreach($accounts as $account)
                                                    <option value="{{ $account->id }}" {{ $item->chart_account_id == $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ __('Amount') }}<span class="text-danger">*</span></label>
                                            <input type="number" name="items[{{ $index }}][amount]" class="form-control item-amount" step="0.01" min="0" value="{{ $item->amount }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">{{ __('Description') }}</label>
                                            <textarea name="items[{{ $index }}][description]" class="form-control" rows="2">{{ $item->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-danger btn-sm remove-item" {{ $index == 0 && $directExpense->items->count() == 1 ? 'style="display:none;"' : '' }}>
                                            <i class="ti ti-trash"></i> {{ __('Remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <button type="button" class="btn btn-primary btn-sm" id="add-item">
                        <i class="ti ti-plus"></i> {{ __('Add Item') }}
                    </button>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="text-end">
                                <a href="{{ route('direct_expenses.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
$(document).ready(function() {
    let itemIndex = {{ $directExpense->items->count() }};
    
    // Initialize select2
    $('.select2').select2();
    
    // Currency change handler
    $('#currency_id').change(function() {
        var currencyId = $(this).val();
        if (currencyId === '') {
            $('#exchange_rate_div').hide();
            $('#exchange_rate').val('');
        } else {
            $('#exchange_rate_div').show();
            fetch('/get-exchange-rate/' + currencyId)
                .then(response => response.json())
                .then(data => {
                    $('#exchange_rate').val(data.exchange_rate || '');
                })
                .catch(() => {
                    // Keep current value if fetch fails
                });
        }
    });
    
    // Add item handler
    $('#add-item').click(function() {
        const newRow = $('.item-row').first().clone();
        newRow.find('input, select, textarea').val('');
        newRow.find('select[name*="[sub_product_id]"]').val('').trigger('change');
        newRow.find('select[name*="[chart_account_id]"]').val('').trigger('change');
        
        // Update name attributes
        newRow.find('input[name], select[name], textarea[name]').each(function() {
            const name = $(this).attr('name').replace(/\[\d+\]/, '[' + itemIndex + ']');
            $(this).attr('name', name);
        });
        
        // Show remove button
        newRow.find('.remove-item').show();
        
        // Reinitialize select2
        newRow.find('.select2').select2();
        
        $('#items-container').append(newRow);
        itemIndex++;
    });
    
    // Remove item handler
    $(document).on('click', '.remove-item', function() {
        const itemRows = $('.item-row');
        if (itemRows.length > 1) {
            $(this).closest('.item-row').remove();
        } else {
            alert('{{ __("At least one item is required") }}');
        }
    });
});
</script>
@endpush
