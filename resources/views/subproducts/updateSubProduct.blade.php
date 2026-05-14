@extends('layouts.admin')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('productservice.index') }}">{{ __('Product & Services') }}</a></li>
<li class="breadcrumb-item">{{ __('Edit Products') }}</li>
@endsection
@push('script-page')
<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
<script src="{{ asset('js/jquery-searchbox.js') }}"></script>
<script>
    var selector = "body"; <
        script >
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
    function confirmSalePriceChange(element) {
            const newPrice = parseFloat(element.value);
            const confirmation = confirm('Do you want to change all item sale prices to ' + newPrice + '?');

            if (confirmation) {
                // Update all sale prices
                document.querySelectorAll('input[name^="items["][name$="][sale_price]"]').forEach(input => {
                    input.value = newPrice;
                });
            } else {
                // Reset the changed input to its previous value
                const originalPrice = parseFloat(element.getAttribute('data-original-price'));
                element.value = originalPrice;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const salePriceInputs = document.querySelectorAll('input[name^="items["][name$="][sale_price]"]');
            const submitButton = document.querySelector('input[type="submit"]');

            // Check if any sale price input is changed
            salePriceInputs.forEach(input => {
                input.addEventListener('change', () => {
                    submitButton.disabled = false; // Enable the submit button
                });
            });
        });

        function confirmPurchasePriceChange(element) {
            const newPrice = parseFloat(element.value);
            const confirmation = confirm('Do you want to change all item purchase prices to ' + newPrice + '?');

            if (confirmation) {
                // Update all purchase prices
                document.querySelectorAll('input[name^="items["][name$="][purchase_price]"]').forEach(input => {
                    input.value = newPrice;
                });
            } else {
                // Reset the changed input to its previous value
                const originalPrice = parseFloat(element.getAttribute('data-original-price'));
                element.value = originalPrice;
            }
        }
        function confirmQuantityChange(element) {
            const newPrice = parseFloat(element.value);
            const confirmation = confirm('Do you want to change all item quantity to ' + newPrice + '?');

            if (confirmation) {
                // Update all purchase prices
                document.querySelectorAll('input[name^="items["][name$="][quantity]"]').forEach(input => {
                    input.value = newPrice;
                });
            } else {
                // Reset the changed input to its previous value
                const originalPrice = parseFloat(element.getAttribute('data-original-quantity'));
                element.value = originalPrice;
            }
        }
</script>
<script>
    function confirmColorChange(selectElement) {
            var confirmChange = confirm('Do you want to change the color for all items?');
            if (confirmChange) {
                // Get the selected color ID
                var selectedColorId = selectElement.value;

                // Loop through all select elements with the same name and update their selected option
                var selectElements = document.querySelectorAll('select[name^="items["][name$="[interior_color_id]"]');
                selectElements.forEach(function(element) {
                    element.value = selectedColorId;
                });
            }
        }
</script>
<script>
    function confirmColorChangeEX(selectElement) {
            var confirmChange = confirm('Do you want to change the color for all items?');
            if (confirmChange) {
                // Get the selected color ID
                var selectedColorId = selectElement.value;

                // Loop through all select elements with the same name and update their selected option
                var selectElements = document.querySelectorAll('select[name^="items["][name$="[exterior_color_id]"]');
                selectElements.forEach(function(element) {
                    element.value = selectedColorId;
                });
            }
        }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form'); // Assuming your form has a unique selector

            // Listen for key press events on the form
            form.addEventListener('keydown', function(event) {
                // Check if the Enter key was pressed (keyCode 13)
                if (event.keyCode === 13) {
                    event.preventDefault(); // Prevent the default form submission behavior
                }
            });
        });
</script>
@endpush
@section('content')
<div class="row">
    <form action="{{ route('sub-product-update.update', $product_id) }}" method="POST" class="w-100">
        @method('PUT')
        @csrf
        <div class="col-12">
            <h5 class="d-inline-block mb-4">{{ __('Sub Product') }}</h5>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table mb-0" data-repeater-list="items" id="sortable-table">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Product No') }}</th>
                                <th>{{ __('Sale Price') }}</th>
                                <th>{{ __('Purchase Price') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                @foreach ($customFields as $customField)
                                <th>{{ __($customField->name) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="ui-sortable" data-repeater-item>
                            @foreach ($subProducts as $index => $subProduct)
                            <tr>
                                <td width="15%" class="">

                                    <div>

                                        <select name="items[{{ $index }}][product_id]" class="form-control select"
                                            required disabled>
                                            <option value="{{ $subProduct->productService->id }}">
                                                {{ $subProduct->productService->name }}</option>
                                            @foreach ($product_services as $productId => $productName)
                                            <option value="{{ $productId }}">{{ $productName }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </td>
                                <td>
                                    <div>
                                        <div>

                                            <input type="text" name="items[{{ $index }}][product_no]"
                                                class="form-control" required value="{{ $subProduct->chassis_no }}">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>

                                            <input type="number" name="items[{{ $index }}][sale_price]"
                                                class="form-control" required step="0.01"
                                                value="{{ $subProduct->sale_price }}"
                                                onchange="confirmSalePriceChange(this)">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>

                                            <input type="number" name="items[{{ $index }}][purchase_price]"
                                                class="form-control price" required step="0.01"
                                                value="{{ $subProduct->purchase_price }}"
                                                id="purchase_price_{{ $index }}"
                                                onchange="confirmPurchasePriceChange(this)">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>

                                            <input type="number" name="items[{{ $index }}][quantity]"
                                                class="form-control quantity" required step="0.01"
                                                value="{{ $subProduct->quantity }}" id="quantity_{{ $index }}"
                                                onchange="confirmQuantityChange(this)">
                                        </div>
                                    </div>
                                </td>
                                @foreach ($customFields as $customField)
                                @php
                                // Ensure that we safely access the value
                                $value = isset($customFieldValues[$subProduct->id][$customField->id])
                                ? $customFieldValues[$subProduct->id][$customField->id]
                                : '';
                                @endphp
                                <td>
                                    @if ($customField->type == 'text')
                                    <input type="text" name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-input" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        value="{{ $value }}"
                                        onkeypress="handleCustomFieldEnter(event, this, {{ $customField->id }})">
                                    @elseif($customField->type == 'email')
                                    <input type="email" name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-input" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        value="{{ $value }}"
                                        onkeypress="handleCustomFieldEnter(event, this, {{ $customField->id }})">
                                    @elseif($customField->type == 'number')
                                    <input type="number" name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-input" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        value="{{ $value }}"
                                        onkeypress="handleCustomFieldEnter(event, this, {{ $customField->id }})">
                                    @elseif($customField->type == 'date')
                                    <input type="date" name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-input" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        value="{{ $value }}"
                                        onkeypress="handleCustomFieldEnter(event, this, {{ $customField->id }})">
                                    @elseif($customField->type == 'textarea')
                                    <textarea name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-input" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        onkeydown="handleCustomFieldEnterTextarea(event, this, {{ $customField->id }})">{{ $value }}</textarea>
                                    @elseif($customField->type == 'dropdown')
                                    @php
                                    $options = json_decode($customField->options, true);
                                    @endphp
                                    <select name="items[{{ $index }}][customField][{{ $customField->id }}]"
                                        class="form-control custom-field-select" 
                                        data-custom-field-id="{{ $customField->id }}"
                                        data-custom-field-name="{{ __($customField->name) }}"
                                        onchange="confirmCustomFieldChange(this, {{ $customField->id }})">
                                        @foreach ($options as $option)
                                        <option value="{{ $option }}" {{ $value==$option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            <input type="hidden" id="sub_product_id" name="items[{{ $index }}][sub_product_id]"
                                value="{{ $subProduct->id }}">
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>


<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}"
        onclick="location.href = '{{ route('subProducts', ['id' => $subProduct->productService->id]) }}';"
        class="btn btn-light">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
</form>
</div>
{{ $subProducts->appends(request()->query())->links() }}
@endsection
<script>
    // Flag to prevent multiple confirmations
    var isApplyingCustomFieldChange = false;

    // Handle Enter key press on custom field inputs
    function handleCustomFieldEnter(event, element, customFieldId) {
        if (event.keyCode === 13 || event.which === 13) {
            event.preventDefault();
            confirmCustomFieldChange(element, customFieldId);
        }
    }

    // Handle Ctrl+Enter key press on textarea (Enter alone creates new line)
    function handleCustomFieldEnterTextarea(event, element, customFieldId) {
        if ((event.keyCode === 13 || event.which === 13) && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            confirmCustomFieldChange(element, customFieldId);
        }
    }

    // Confirm and apply custom field value to all rows
    function confirmCustomFieldChange(element, customFieldId) {
        // Prevent multiple confirmations
        if (isApplyingCustomFieldChange) {
            return;
        }

        const newValue = element.value;
        const fieldType = element.tagName.toLowerCase();
        
        // Get the custom field name for display
        const fieldLabel = element.getAttribute('data-custom-field-name') || 'this field';
        
        const confirmation = confirm('Do you want to apply "' + newValue + '" to all items in ' + fieldLabel + '?');
        
        if (confirmation) {
            // Set flag to prevent recursive calls
            isApplyingCustomFieldChange = true;
            
            // Find all fields with the same custom field ID
            const fieldsToUpdate = document.querySelectorAll('[data-custom-field-id="' + customFieldId + '"]');
            
            fieldsToUpdate.forEach(targetField => {
                // Skip the original field that triggered the change
                if (targetField === element) {
                    return;
                }
                
                if (targetField.tagName.toLowerCase() === 'select') {
                    // Handle select elements
                    const options = targetField.options;
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].value === newValue) {
                            options[i].selected = true;
                        } else {
                            options[i].selected = false;
                        }
                    }
                } else {
                    // Handle input and textarea elements
                    targetField.value = newValue;
                }
            });
            
            // Reset flag after a short delay
            setTimeout(function() {
                isApplyingCustomFieldChange = false;
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Also handle change event for custom fields (optional - for consistency)
        const customFields = document.querySelectorAll('.custom-field-input, .custom-field-select');
        
        // Note: We're not adding change handler here since Enter key handler covers it
        // But if you want change-on-blur behavior, you can add it here
    });
</script>