@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        var selector = "body";
        var taxes = '';
        var tax = [];
        var VATAmount = 0;

        // Unified recalculation for edit page (treats .price as line amount)
        function recalcTotalsAddProducts() {
            var taxType = <?php echo json_encode($taxType); ?>; // 'add' or 'subtract'
            var taxRate = parseFloat(<?php echo json_encode($totalTaxPrice); ?>) || 0;

            var subTotal = 0;
            var totalDiscount = 0;
            var totalExpense = 0;

            // Sum line amounts (price inputs hold line totals on this page)
            $('.price').each(function() {
                var v = parseFloat($(this).val());
                if (!isNaN(v)) subTotal += v;
            });

            // Sum discounts (assumed as line discount amounts)
            $('.discount').each(function() {
                var d = parseFloat($(this).val());
                if (!isNaN(d)) totalDiscount += d;
            });

            // Sum expenses
            $('.expense-amount').each(function() {
                var e = parseFloat($(this).val());
                if (!isNaN(e)) totalExpense += e;
            });

            var net = subTotal - totalDiscount;
            var totalTax = 0;
            var totalAmount = 0;
            if (taxType === 'add') {
                totalTax = net * (taxRate / 100);
                totalAmount = net + totalTax + totalExpense;
            } else {
                // tax included in price
                totalTax = (net * (taxRate / 100)) / (1 + (taxRate / 100));
                totalAmount = net - totalTax + totalExpense;
            }

            // Update DOM
            $('.subTotal').html(subTotal.toFixed(2));
            $('.totalDiscount').html(totalDiscount.toFixed(2));
            $('.totalTax').html(totalTax.toFixed(2));
            $('.totalAmount').html(totalAmount.toFixed(2));

            // Update hidden fields
            $('.itemTaxRate').val(taxRate);
            $('input[name="itemTaxPrice"]').val(totalTax.toFixed(2));
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Bind recalculation on relevant input changes
            $(document).on('keyup change input', '.price, .discount, .expense-amount', function() {
                recalcTotalsAddProducts();
            });

            // Initial calculation on load
            recalcTotalsAddProducts();
        });
    </script>

    <script>
        // Recalculate totals whenever price changes as well
        $(document).on('keyup change', '.price', function() {
            recalcTotalsAddProducts();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (confirm('Are you sure you want to delete this item?')) {
                        const subProductId = button.getAttribute('data-id');
                        const invoiceId = button.getAttribute('data-invoice-id');
                        const form = document.getElementById('delete-form-template');

                        // Set the action URL dynamically
                        form.action = `/sub-productservice_invoice/${subProductId}/${invoiceId}`;

                        form.submit();
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('input[name="items[0][chassis_no]"]').on('paste', function(e) {
                // Prevent default paste behavior
                e.preventDefault();

                // Get pasted data
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var rows = pastedData.split('\n');

                // Update the first input field with the first value after a short delay
                setTimeout(function() {
                    var firstRowValue = rows[0].split('\t')[0]; // Assuming tab-separated data
                    $('input[name="items[0][chassis_no]"]').val(firstRowValue);

                    // Update the rest of the rows
                    for (var i = 1; i < rows.length; i++) {
                        var cols = rows[i].split('\t');
                        if (cols.length > 0) {
                            $('input[name="items[' + i + '][chassis_no]"]').val(cols[0]);
                        }
                    }
                }, 50); // Delay added to ensure proper handling after the paste event
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('input[name^="items[0][engine_no]"]').on('paste', function(e) {
                // Prevent default paste behavior
                e.preventDefault();

                // Get pasted data
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var rows = pastedData.split('\n');

                // Update the first input field with the first value after a short delay
                setTimeout(function() {
                    var firstRowValue = rows[0]; // Assuming each row has only one value
                    $('input[name^="items[0][engine_no]"]').val(firstRowValue);

                    // Update the rest of the items
                    for (var i = 1; i < rows.length; i++) {
                        var value = rows[i];
                        // Update the respective number input field for each item
                        $('input[name^="items[' + i + '][engine_no]"]').val(value);
                    }
                }, 50); // Delay added to ensure proper handling after the paste event
            });
        });
    </script>
    <script>
        function redirectToInvoice() {
            var type = '{{ $type }}'; // Assuming you have this variable available in your Blade template

            var redirectRoute = '';

            // Determine the route based on the type
            if (type === 'regular') {
                redirectRoute = '{{ route('invoice.index') }}';
            } else if (type === 'rent') {
                redirectRoute = '{{ route('rentinvoice.index') }}';
            }

            // Redirect to the appropriate route
            if (redirectRoute !== '') {
                location.href = redirectRoute;
            } else {
                console.error('Invalid type for redirection.');
            }
        }
    </script>
    <script>
        $(document).on('click', '.delete-expense-btn', function(e) {
            e.preventDefault();
            let expenseId = $(this).data('id');
            let row = $(this).closest('tr');

            if (confirm('Are you sure you want to delete this expense?')) {
                $.ajax({
                    url: '/invoice-expense/delete/' + expenseId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        row.remove();
                        recalcTotalsAddProducts();
                    },
                    error: function() {
                        alert('Failed to delete expense.');
                    }
                });
            }
        });
    </script>
@endpush
@section('content')
    <div class="row">
        @if (session()->has('errorArray'))
            <div class="alert alert-danger">
                <ul>
                    @foreach (session('errorArray') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('sub-product-invoice.update', $invoice->id) }}" method="POST" class="w-100">
            @csrf
            @method('POST')
            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Product & Services') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-size="lg"
                                        data-url="{{ route('sub-product-invoice.create', $invoice->id) }}"
                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                        title="{{ __('Create New Product') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                </div>
                                <div class="all-button-box me-2">
                                    <a href="#" data-size="lg"
                                        data-url="{{ route('sub-product-invoice.createExpense', $invoice->id) }}"
                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                        title="{{ __('Create New Expense') }}" class="btn btn-sm btn-secondary">
                                        <i class="ti ti-exchange"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table mb-0" data-repeater-list="items" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th width="20%">{{ __('Product No') }}</th>
                                        <th width="20%">{{ __('Qty') }}</th>
                                        <th width="20%">{{ __('Sale Price') }}</th>
                                        <th width="20%">{{ __('Discount') }}</th>
                                        <th>{{ __('Custom Fields') }}</th>
                                        <th>{{ __('Action') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    @foreach ($invoiceProducts as $index => $item)
                                        <tr>
                                            @php
                                                $cat = $item->product->category->type;
                                            @endphp
                                            <td width="25%">

                                                <div>

                                                    <select name="items[{{ $index }}][product_id]"
                                                        class="form-control select" required readonly>
                                                        @foreach ($product_services as $productId => $productName)
                                                            <option value="{{ $item->product->id }}"
                                                                @if ($productId === $item->product->id) selected @endif>
                                                                {{ $productName . '/' . $item->product->sku }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                            </td>
                                            <td>
                                                <div>
                                                    <div>

                                                        <input type="text" name="items[{{ $index }}][product_no]"
                                                            class="form-control" readonly
                                                            value="{{ $item->subProduct->chassis_no }}">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div>
                                                        @if($cat == "Qty product")
                                                        <input type="number" name="items[{{ $index }}][qty]"
                                                            class="form-control quantity" required min="1" step="1"
                                                            value="{{ $item->quantity }}">
                                                        @else
                                                        <input type="text" name="items[{{ $index }}][qty]"
                                                            class="form-control quantity" readonly
                                                            value="{{ $item->quantity }}">
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div>

                                                        <input type="number" name="items[{{ $index }}][sale_price]"
                                                            class="form-control price" required step="0.01"
                                                            value="{{ $item->price * $item->quantity }}">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div>

                                                        <input type="number" name="items[{{ $index }}][discount]"
                                                            class="form-control discount" required step="0.01"
                                                            value="{{ $item->discount }}">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $customFields = \App\Models\CustomField::where('module', 'sub-product')
                                                        ->forCategory($item->subProduct->productService->category_id)
                                                        ->get();
                                                    $customFieldValues = \App\Models\CustomFieldValue::where(
                                                        'record_id',
                                                        $item->subProduct->id,
                                                    )
                                                        ->get()
                                                        ->pluck('value', 'field_id');
                                                @endphp
                                                <div>
                                                    @foreach ($customFields as $customField)
                                                        <div>
                                                            <strong>{{ $customField->name }}:</strong>
                                                            {{ $customFieldValues[$customField->id] ?? 'N/A' }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>

                                            <td class="Action">

                                                <div class="action-btn bg-danger ms-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para delete-btn"
                                                        data-id="{{ $item->subProduct->id }}"
                                                        data-invoice-id="{{ $invoice->id }}" data-bs-toggle="tooltip"
                                                        title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                </div>

                                            </td>
                                        </tr>
                                        <input type="hidden" id="sub_product_id"
                                            name="items[{{ $index }}][sub_product_id]"
                                            value="{{ $item->subProduct->id }}">
                                    @endforeach

                                    <!-- Invoice Expenses Section -->
                                    @php
                                        $totalExpenseAmount = 0;
                                        $expenses = \App\Models\InvoiceExpense::where(
                                            'invoice_id',
                                            $invoice->id,
                                        )->get();
                                    @endphp

                                    @foreach ($expenses as $expenseIndex => $expense)
                                        @php
                                            $totalExpenseAmount += $expense->amount;
                                        @endphp
                                        <tr class="expense-row">
                                            <input type="hidden" name="expenses[{{ $expenseIndex }}][id]"
                                                value="{{ $expense->id }}">
                                            <td colspan="2">
                                                <strong>Expense:</strong>
                                                <input type="text" name="expenses[{{ $expenseIndex }}][account_id]"
                                                    class="form-control"
                                                    value="{{ \App\Models\ChartOfAccount::where('id', $expense->account_id)->first()->name }}">
                                            </td>
                                            <td colspan="2">
                                                <strong>Expense Amount:</strong>
                                                <input type="number" name="expenses[{{ $expenseIndex }}][amount]"
                                                    class="form-control expense-amount" value="{{ $expense->amount }}"
                                                    step="0.01">
                                            </td>
                                            <td colspan="3">
                                                <strong>Description:</strong>
                                                <input type="text" name="expenses[{{ $expenseIndex }}][description]"
                                                    class="form-control" value="{{ $expense->description ?? '' }}">
                                            </td>
                                            <td class="Action">
                                                <div class="action-btn bg-danger ms-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm align-items-center delete-expense-btn"
                                                        data-id="{{ $expense->id }}"
                                                        data-invoice-id="{{ $invoice->id }}" data-bs-toggle="tooltip"
                                                        title="{{ __('Delete Expense') }}">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td>
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="taxes">{{ $totalTaxName }}</div>
                                                    <input type="hidden" name="tax" class="form-control tax">
                                                    <input type="hidden" name="itemTaxPrice"
                                                        class="form-control itemTaxPrice">
                                                    <input type="hidden" name="itemTaxRate"
                                                        class="form-control itemTaxRate">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end tax_val">{{ $totalTaxPrice . '%' }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end subTotal">{{ $invoice->getSubTotal() }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Discount') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end totalDiscount">{{ $invoice->getTotalDiscount() }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Tax') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end totalTax">{{ $invoice->getTotalTax() }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                        <td class="blue-text text-end totalAmount">{{ $invoice->getTotal() }}</td>

                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}" onclick="redirectToInvoice();" class="btn btn-light">
                <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
            </div>
        </form>
    </div>
    <form method="POST" action="" id="delete-form-template" class="d-none">
        @csrf
        @method('POST')
    </form>
@endsection
