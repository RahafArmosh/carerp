@extends('layouts.admin')
@section('page-title')
    {{ __('Create Bill from Request') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Bill from Request') }}</li>
@endsection

@push('script-page')
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('Create Bill from Car Accessory Request') }}</h4>
                <div class="card-tools">
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('bill.store') }}" id="bill-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Vendor') }} <span class="text-danger">*</span></label>
                                <select class="form-control" name="vender_id" id="vender" required>
                                    <option value="">{{ __('Select Vendor') }}</option>
                                    @foreach($venders as $id => $name)
                                        <option value="{{ $id }}" {{ $id == $vendorId ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Bill Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="bill_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Warehouse') }} <span class="text-danger">*</span></label>
                                <select class="form-control" name="warehouse_id" required>
                                    {{-- <option value="">{{ __('Select Warehouse') }}</option> --}}
                                    @foreach($warehouse as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Category') }}</label>
                                <select class="form-control" name="category_id">
                                    <option value="">{{ __('Select Category') }}</option>
                                    @foreach($category as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Order Number') }}</label>
                                <input type="text" class="form-control" name="order_number" placeholder="{{ __('Enter Order Number') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Tax') }}</label>
                                <select class="form-control" name="tax_id[]" multiple required>
                                    @foreach($fullTax as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                                    <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">{{ __('Currency') }}</label>
                    <select class="form-control" name="currency_id" id="currency">
                        <option value="">{{ __('Select Currency') }}</option>
                                                            @foreach($currency as $id => $name)
                                        <option value="{{ $id }}">
                                            {{ $name }}
                                        </option>
                                    @endforeach
                    </select>
                </div>
            </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Exchange Rate') }}</label>
                                <input type="number" class="form-control" name="exchange_rate" id="exchange_rate" step="0.01" min="0" value="1.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">{{ __('Salesman') }}</label>
                                <select class="form-control" name="salesman_id">
                                    <option value="">{{ __('Select Salesman') }}</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ $id == auth()->id() ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">{{ __('Items') }}</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" id="items-table">
                            <thead>
                                <tr>
                                    <th width="40%">{{ __('Item') }}</th>
                                    <th width="15%">{{ __('Quantity') }}</th>
                                    <th width="15%">{{ __('Price') }}</th>
                                    <th width="15%">{{ __('Total') }}</th>
                                    <th width="15%">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                <!-- Items will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-info" id="add-item-btn">
                                <i class="fas fa-plus"></i> {{ __('Add Item') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="form-group">
                                <label class="form-label"><strong>{{ __('Sub Total') }}:</strong></label>
                                <span id="sub-total" class="h5">0.00</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><strong>{{ __('Tax Total') }}:</strong></label>
                                <span id="tax-total" class="h5">0.00</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><strong>{{ __('Grand Total') }}:</strong></label>
                                <span id="grand-total" class="h5">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="{{ __('Enter any additional notes here...') }}"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-end">
                                                         <button type="submit" class="btn btn-primary">
                                 <i class="fas fa-save"></i> {{ __('Create Bill') }}
                             </button>
                             {{-- <button type="button" class="btn btn-warning" onclick="testForm()">
                                 <i class="fas fa-bug"></i> Test Form
                             </button> --}}
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('Cancel') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden input for bill number -->
<input type="hidden" name="bill_numberNo" value="{{ $bill_numberNo }}">
<input type="hidden" name="form_source" value="create_from_request">

<!-- Vendor details section -->
{{-- <div id="vender_detail" class="d-none">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">{{ __('Vendor Name') }}</label>
                <input type="text" class="form-control" id="vender-name" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">{{ __('Vendor Email') }}</label>
                <input type="email" class="form-control" id="vender-email" readonly>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">{{ __('Vendor Phone') }}</label>
                <input type="text" class="form-control" id="vender-phone" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">{{ __('Vendor Address') }}</label>
                <textarea class="form-control" id="vender-address" rows="2" readonly></textarea>
            </div>
        </div>
    </div>
</div> --}}

@endsection

@push('script-page')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#vender').select2();
    $('#warehouse_id').select2();
    $('#category_id').select2();
    $('#tax_id').select2();
    $('#currency').select2();
    $('#salesman_id').select2();

    // Store the items data from the request
    const requestItems = @json($requestItems ?? []);
    let itemCounter = 0;

    // Function to add item row
    function addItemRow(item = null) {
        const row = `
            <tr class="item-row" data-row="${itemCounter}">
                <td>
                    <select class="form-control item-select" name="items[${itemCounter}][item]" required>
                        <option value="">{{ __('Select Item') }}</option>
                        @foreach($product_services as $id => $name)
                            <option value="{{ $id }}" data-price="0">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" name="items[${itemCounter}][quantity]" 
                           min="1" step="1" value="${item ? item.quantity : 1}" required>
                </td>
                <td>
                    <input type="number" class="form-control price-input" name="items[${itemCounter}][price]" 
                           min="0" step="0.01" value="${item ? item.price : ''}" required>
                </td>
                <td>
                    <span class="item-total">0.00</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#items-tbody').append(row);
        
        // Initialize Select2 for the new row
        $(`#items-tbody tr[data-row="${itemCounter}"] .item-select`).select2();
        
        // Set values if item is provided
        if (item) {
            $(`#items-tbody tr[data-row="${itemCounter}"] .item-select`).val(item.product_id).trigger('change');
            $(`#items-tbody tr[data-row="${itemCounter}"] .price-input`).val(item.price);
        }
        
        itemCounter++;
        calculateTotals();
    }

    // Function to remove item row
    $(document).on('click', '.remove-item-btn', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Function to calculate item total
    $(document).on('input', '.quantity-input, .price-input', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        const price = parseFloat(row.find('.price-input').val()) || 0;
        const total = quantity * price;
        row.find('.item-total').text(total.toFixed(2));
        calculateTotals();
    });

    // Function to calculate totals
    function calculateTotals() {
        let subTotal = 0;
        $('.item-total').each(function() {
            subTotal += parseFloat($(this).text()) || 0;
        });
        
        $('#sub-total').text(subTotal.toFixed(2));
        
        // Calculate tax (simplified - you can enhance this based on your tax logic)
        const taxRate = 0; // Default tax rate
        const taxTotal = subTotal * (taxRate / 100);
        $('#tax-total').text(taxTotal.toFixed(2));
        
        const grandTotal = subTotal + taxTotal;
        $('#grand-total').text(grandTotal.toFixed(2));
    }

    // Add item button click
    $('#add-item-btn').click(function() {
        addItemRow();
    });

    // Populate items from request
    if (requestItems.length > 0) {
        requestItems.forEach(function(item) {
            addItemRow(item);
        });
    } else {
        // Add at least one empty row
        addItemRow();
    }

    // Handle vendor selection
    $('#vender').change(function() {
        const vendorId = $(this).val();
        if (vendorId) {
            // Show vendor details
            $('#vender_detail').removeClass('d-none').addClass('d-block');
            
            // Load vendor details via AJAX
            $.ajax({
                url: '{{ route("vender.detail") }}',
                type: 'POST',
                data: {
                    id: vendorId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data) {
                        $('#vender-name').val(data.name || '');
                        $('#vender-email').val(data.email || '');
                        $('#vender-phone').val(data.phone || '');
                        $('#vender-address').val(data.address || '');
                    }
                },
                error: function() {
                    console.log('Error loading vendor details');
                }
            });
        } else {
            $('#vender_detail').removeClass('d-block').addClass('d-none');
        }
    });

    // Handle currency change
    $('#currency').change(function() {
        const currencyId = $(this).val();
        if (currencyId) {
            // Load exchange rate for selected currency
            $.ajax({
                url: '{{ route("currency.rate") }}',
                type: 'POST',
                data: {
                    currency_id: currencyId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.rate) {
                        $('#exchange_rate').val(data.rate);
                    }
                },
                error: function() {
                    console.log('Error loading exchange rate');
                }
            });
        }
    });

    // Form submission
    $('#bill-form').submit(function(e) {
        console.log('Form submission started');
        
        // Validate that at least one item is added
        if ($('.item-row').length === 0) {
            e.preventDefault();
            alert('{{ __("Please add at least one item") }}');
            return false;
        }
        
        // Validate that all items have values
        let isValid = true;
        let formData = {};
        
        $('.item-row').each(function() {
            const item = $(this).find('.item-select').val();
            const quantity = $(this).find('.quantity-input').val();
            const price = $(this).find('.price-input').val();
            
            console.log('Item data:', { item, quantity, price });
            
            if (!item || !quantity || !price) {
                isValid = false;
                return false;
            }
            
            // Build form data for debugging
            const rowIndex = $(this).data('row');
            formData[`items[${rowIndex}][item]`] = item;
            formData[`items[${rowIndex}][quantity]`] = quantity;
            formData[`items[${rowIndex}][price]`] = price;
        });
        
        console.log('Form data being submitted:', formData);
        
        if (!isValid) {
            e.preventDefault();
            alert('{{ __("Please fill in all item details") }}');
            return false;
        }
        
        console.log('Form validation passed, submitting...');
    });

         // Initialize vendor details if vendor is pre-selected
     if ($('#vender').val()) {
         $('#vender').trigger('change');
     }
     
     // Test function to debug form
     window.testForm = function() {
         console.log('Testing form...');
         console.log('Form element:', $('#bill-form')[0]);
         console.log('Form action:', $('#bill-form').attr('action'));
         console.log('Form method:', $('#bill-form').attr('method'));
         console.log('CSRF token:', $('input[name="_token"]').val());
         
         // Try to submit form programmatically
         $('#bill-form').submit();
     };
 });
 </script>
@endpush
