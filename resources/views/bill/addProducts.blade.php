@extends('layouts.admin')
@section('page-title')
    {{ __('Bill Edit') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bill.index') }}">{{ __('Bill') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bill Edit') }}</li>
@endsection
@php
    $products = session('productsQTY', []); // Retrieve the session data or an empty array if it doesn't exist
@endphp

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
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
            // Use a wildcard selector to detect paste on any product_no input field
            $('input[name^="items"][name$="[product_no]"]').on('paste', function(e) {
                // Prevent default paste behavior
                e.preventDefault();

                // Get the index of the row where the paste occurred
                var currentRowIndex = $(this).attr('name').match(/\d+/)[
                    0]; // Extract row index (e.g., items[0] -> 0)

                // Get pasted data
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var rows = pastedData.split('\n');

                // Update the current input field with the first pasted value after a short delay
                setTimeout(function() {
                    // Update the field where the paste occurred
                    $('input[name="items[' + currentRowIndex + '][product_no]"]').val(rows[0]);

                    // Loop through the remaining rows and update the respective product_no fields
                    for (var i = 1; i < rows.length; i++) {
                        var nextRowIndex = parseInt(currentRowIndex) + i;
                        var value = rows[i];
                        $('input[name="items[' + nextRowIndex + '][product_no]"]').val(value);
                    }
                }, 50); // Delay added to ensure proper handling after the paste event
            });
        });

        // $(document).ready(function() {
        //     // Listen for the paste event on any text-type custom field
        //     $(document).on('paste', '.custom-field-text', function(event) {
        //         event.preventDefault(); // Prevent the default paste behavior

        //         var clipboardData = event.originalEvent.clipboardData || window.clipboardData;
        //         var pastedData = clipboardData.getData('Text'); // Get the pasted text

        //         // Split pasted data into an array (assuming new lines separate values)
        //         var pastedValues = pastedData.split('\n').map(function(item) {
        //             return item.trim();
        //         }).filter(function(item) {
        //             return item.length > 0;
        //         });

        //         var fieldId = $(this).data('field-id'); // Get field ID of the pasted column

        //         // Get all input fields with the same fieldId across rows
        //         var relatedFields = $('.custom-field-text[data-field-id="' + fieldId + '"]');

        //         // Populate values vertically for the same field type in different rows
        //         relatedFields.each(function(index) {
        //             if (pastedValues[index]) {
        //                 $(this).val(pastedValues[index]);
        //             }
        //         });
        //     });
        // });
        $(document).ready(function () {
        // Listen for the paste event on ANY custom field input type
        $(document).on('paste', '.custom-field-input', function (event) {
            event.preventDefault(); // Stop default paste

            var clipboardData = event.originalEvent.clipboardData || window.clipboardData;
            var pastedData = clipboardData.getData('Text');

            // Split into rows (handling Windows/Mac line endings)
            var pastedValues = pastedData.split(/\r?\n/);

            var fieldId = $(this).data('field-id'); // Column identifier

            // Get all fields in same column across rows
            var relatedFields = $('.custom-field-input[data-field-id="' + fieldId + '"]');

            relatedFields.each(function (index) {
                if (pastedValues[index] !== undefined) {
                    $(this).val(pastedValues[index].trim());
                }
            });
        });
    });
    </script>
    <script>
        function recalculateTotals() {
            let subtotal = 0;
            let totalDiscount = 0;
            let taxRate = {{ $bill->tax->rate ?? 0 }};

            $('tbody tr').each(function() {
                let qty = parseFloat($(this).find('input[name*="[qty]"]').val()) || 0;
                let price = parseFloat($(this).find('input[name*="[purchase_price]"]').val()) || 0;
                let discount = parseFloat($(this).find('input[name*="[discount]"]').val()) || 0;

                let rowTotal = qty * price;
                subtotal += rowTotal;
                totalDiscount += discount;
            });

            let tax = (subtotal - totalDiscount) * taxRate / 100;
            let total = subtotal + tax - totalDiscount;

            $('.subTotal').text(subtotal.toFixed(2));
            $('.tax_val, .totalTax').text(tax.toFixed(2));
            $('.totalDiscount').text(totalDiscount.toFixed(2));
            $('.totalAmount').text(total.toFixed(2));
        }

        // Update qty change to recalculate properly
        $(document).on('input', 'input[name*="[qty]"]', function() {
            let qtyInput = $(this);
            let row = qtyInput.closest('tr');

            // Get the original price
            let originalPrice = parseFloat(qtyInput.data('purchase-price')) || 0;

            // Set price back
            let priceInput = row.find('input[name*="[purchase_price]"]');
            priceInput.val(originalPrice.toFixed(2)).trigger('input');

            recalculateTotals();
        });

        // Recalculate on any relevant input change
        $(document).on('input change', 'input[name*="[qty]"], input[name*="[purchase_price]"], input[name*="[discount]"]',
            function() {
                recalculateTotals();
            });


        // After adding a new item row
        $(document).on('click', '.add-item', function() {
            // Your logic to add a new row
            recalculateTotals();
        });

        // After removing an item row
        $(document).on('click', '.remove-item', function() {
            $(this).closest('tr').remove();
            recalculateTotals();
        });
    </script>
    <script>
        // Prevent browser back button navigation
        $(document).ready(function() {
            // Push a state to history to prevent back navigation
            history.pushState(null, null, location.href);
            
            // Listen for popstate event (back/forward button)
            window.onpopstate = function(event) {
                // Push state again to prevent going back
                history.pushState(null, null, location.href);
                
                // Optional: Show a message to user
                alert('{{ __("Navigation back is disabled. Please use the Cancel button to return.") }}');
            };
        });
        
    </script>
@endpush
@section('content')
    <div class="row">
        <form action="{{ route('sub-product-bill.update', $bill->id) }}" method="POST" class="w-100" enctype="multipart/form-data">
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
                                        data-url="{{ route('sub-product-bill.create', $bill->id) }}" data-ajax-popup="true"
                                        data-bs-toggle="tooltip" title="{{ __('Create New Product') }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus"></i>
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
                                        <th>{{ __('Chassis No') }}</th>
                                        <th>{{ __('QTY') }}</th>
                                        <th>{{ __('Sale Price') }}</th>
                                        <th>{{ __('Purchase Price') }}</th>
                                        <th>{{ __('Discount') }}</th>
                                        @foreach ($customFields as $customField)
                                            <th>{{ __($customField->name) }}</th>
                                        @endforeach
                                        <th>{{ __('Images') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    @foreach ($subProducts as $index => $subProduct)
                                        <tr>
                                            <td class="" width="25%">
                                                <select name="items[{{ $index }}][product_id]"
                                                    class="form-control select w-100" required disabled
                                                    style="min-width:120px;">
                                                    @foreach ($product_services as $productId => $productName)
                                                        <option value="{{ $subProduct->productService->id }}"
                                                            @if ($productId === $subProduct->productService->id) selected @endif>
                                                            {{ $productName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][product_no]"
                                                    class="form-control w-100" required
                                                    value="{{ $subProduct->chassis_no }}" style="min-width:120px;">
                                            </td>
                                            <td>
                                                @php
                                                    // Use eager loaded billProducts instead of querying
                                                    $billProduct = $subProduct->billProducts->first();
                                                    $purchasePrice = $billProduct
                                                        ? ($billProduct->exchange_price ?? $billProduct->price)
                                                        : 0;
                                                @endphp
                                                @if ($subProduct->productService->category->type == 'Qty product')
                                                    <input type="number" name="items[{{ $index }}][qty]"
                                                        class="form-control w-100" required step="0.01"
                                                        value="{{ $subProduct->quantity }}"
                                                        data-purchase-price="{{ $purchasePrice }}"
                                                        style="min-width:100px;">
                                                @else
                                                    <input type="number" name="items[{{ $index }}][qty]"
                                                        class="form-control w-100" required step="0.01" value="1"
                                                        readonly style="min-width:100px;">
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][sale_price]"
                                                    class="form-control w-100" required step="0.01"
                                                    value="{{ $subProduct->sale_price }}" style="min-width:100px;">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][purchase_price]"
                                                    class="form-control price w-100" required step="0.01"
                                                    value="{{ $purchasePrice }}" id="purchase_price_{{ $index }}"
                                                    style="min-width:100px;">
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][discount]"
                                                    class="form-control price w-100" required step="0.01"
                                                    value="{{ $billProduct->exchange_discount ?? 0 }}"
                                                    id="discount_{{ $index }}" style="min-width:100px;">
                                            </td>
                                            @php
                                                // Use eager loaded custom field values instead of querying
                                                $customFieldValues = $subProduct->customFieldValues->pluck('value', 'field_id');
                                                // Get the category ID for this subProduct
                                                $itemCategoryId = $subProduct->productService->category_id ?? null;
                                            @endphp
                                            @foreach ($customFields as $customField)
                                                @php
                                                    // Only show custom field if it belongs to this item's category
                                                    $isRelevant = $customField->categories->contains('id', $itemCategoryId);
                                                    $value = $customFieldValues[$customField->id] ?? '';
                                                @endphp
                                                <td>
                                                    <div class="form-group">
                                                        @if ($isRelevant)
                                                            @if ($customField->type == 'text')
                                                                <input type="text"
                                                                    class="form-control custom-field-input custom-field-text w-100"
                                                                     name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                    data-field-id="{{ $customField->id }}"
                                                                    value="{{ $value }}" style="min-width:100px;">
                                                            @elseif($customField->type == 'email')
                                                                <input type="email"
                                                                    name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                    class="form-control custom-field-input custom-field-email w-100"
                                                                    data-field-id="{{ $customField->id }}"
                                                                    value="{{ $value }}" style="min-width:100px;">
                                                            @elseif($customField->type == 'number')
                                                                <input type="number"
                                                                    name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                    class="form-control custom-field-input custom-field-number w-100"
                                                                    data-field-id="{{ $customField->id }}"
                                                                    value="{{ $value }}" style="min-width:100px;">
                                                            @elseif($customField->type == 'date')
                                                                <input type="date"
                                                                    name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                    class="form-control custom-field-input custom-field-date w-100"
                                                                    data-field-id="{{ $customField->id }}"
                                                                    value="{{ $value }}" style="min-width:100px;">
                                                            @elseif($customField->type == 'textarea')
                                                                <textarea
                                                                    name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                    class="form-control custom-field-input custom-field-textarea w-100"
                                                                    data-field-id="{{ $customField->id }}"
                                                                    style="min-width:100px;">{{ $value }}</textarea>
                                                            @elseif($customField->type == 'dropdown')
                                                                    @php
                                                                        $options = json_decode($customField->options, true);
                                                                    @endphp
                                                                    <select id="customField-{{ $customField->id }}"
                                                                        name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                                                        class="form-control w-100" style="min-width:100px;">
                                                                        @foreach ($options as $option)
                                                                            <option value="{{ $option }}"
                                                                                {{ $value == $option ? 'selected' : '' }}>
                                                                                {{ $option }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                            @endif
                                                        @else
                                                            {{-- Show empty cell for custom fields that don't apply to this item's category --}}
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td style="min-width: 140px;">
                                                @if ($subProduct->relationLoaded('images') && $subProduct->images->isNotEmpty())
                                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                                        @foreach ($subProduct->images->take(4) as $img)
                                                            <img src="{{ $img->url() }}" alt=""
                                                                class="rounded border"
                                                                style="width:36px;height:36px;object-fit:cover;">
                                                        @endforeach
                                                        @if ($subProduct->images->count() > 4)
                                                            <span class="text-muted small align-self-center">+{{ $subProduct->images->count() - 4 }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                <input type="file"
                                                    name="items[{{ $index }}][sub_product_images][]"
                                                    class="form-control form-control-sm"
                                                    accept="image/*" multiple>
                                            </td>
                                            <td class="Action">
                                                <div class="action-btn bg-danger ms-2">
                                                    <form
                                                        action="{{ route('sub-product-bill.delete', ['id' => $subProduct->id, 'bill_id' => $bill->id]) }}"
                                                        method="POST" id="{{ $subProduct->id }}">
                                                        @csrf
                                                        <button type="button"
                                                            onclick="confirmDelete('{{ $subProduct->id }}')"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <input type="hidden" id="sub_product_id"
                                            name="items[{{ $index }}][sub_product_id]"
                                            value="{{ $subProduct->id }}">
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Sub Total') }} ({{ $currency_symbol }})</strong>
                                        </td>
                                        <td class="text-end subTotal">{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Discount') }} ({{ $currency_symbol }})</strong>
                                        </td>
                                        <td class="text-end totalDiscount">{{ number_format($totalDiscount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end">
                                            <strong>{{ __('Tax') }} ({{ $currency_symbol }})</strong>
                                        </td>
                                        <td class="text-end totalTax">{{ number_format($totalTax, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="{{ 7 + count($customFields) }}" class="text-end blue-text">
                                            <strong>{{ __('Total Amount') }} ({{ $currency_symbol }})</strong>
                                        </td>
                                        <td class="blue-text text-end totalAmount">{{ number_format($totalAmount, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div class="d-flex justify-content-center mt-3">
                                {{ $subProducts->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                {{-- <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('bill.index') }}';" class="btn btn-light"> --}}
                <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
            </div>
        </form>
    </div>
@endsection
