@extends('layouts.admin')
@section('page-title')
    {{ __('Manufacturer Create') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('manufacturers.index') }}">{{ __('Manufacturer') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manufacturer Create') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        var selector = "body";
        let TotalTax = 0;
        $(document).ready(function() {
            $('.repeater').repeater({
                show: function() {
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this item?')) {
                        $(this).slideUp(deleteElement);
                    }
                },
                isFirstItemUndeletable: false
            });
        });

        // if ($(selector + " .repeater").length) {
        //     var $dragAndDrop = $("body .repeater tbody").sortable({
        //         handle: '.sort-handler'
        //     });
        //     var $repeater = $(selector + ' .repeater').repeater({
        //         initEmpty: false,
        //         defaultValues: {
        //             'status': 1
        //         },
        //         show: function() {
        //             console.log("Adding item");
        //             $(this).slideDown();
        //             var file_uploads = $(this).find('input.multi');
        //             if (file_uploads.length) {
        //                 $(this).find('input.multi').MultiFile({
        //                     max: 3,
        //                     accept: 'png|jpg|jpeg',
        //                     max_size: 2048
        //                 });
        //             }

        //             // for item SearchBox ( this function is  custom Js )
        //             // JsSearchBox();

        //             // $('.select2').select2();
        //         },
        //         hide: function(deleteElement) {
        //             if (confirm('Are you sure you want to delete this element?')) {
        //                 $(this).slideUp(deleteElement);
        //                 $(this).remove();


        //                 var inputs = $(".amount");
        //                 var subTotal = 0;
        //                 for (var i = 0; i < inputs.length; i++) {
        //                     subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
        //                 }
        //                 $('.subTotal').html(subTotal.toFixed(2));
        //                 $('.totalAmount').html(subTotal.toFixed(2));
        //             }
        //         },
        //         ready: function(setIndexes) {
        //             $dragAndDrop.on('drop', setIndexes);
        //         },
        //         isFirstItemUndeletable: true
        //     });
        //     var value = $(selector + " .repeater").attr('data-value');
        //     if (typeof value != 'undefined' && value.length != 0) {
        //         value = JSON.parse(value);
        //         $repeater.setList(value);
        //     }

        // }

        $(document).on('change', '.item', function() {
            console.log("Main item select changed"); // Debugging log

            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);

            if (!iteams_id || !url) {
                console.error('Missing item ID or URL');
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    try {
                        var item = JSON.parse(data);
                        console.log("Item data:", item); // Debugging log

                        // $(el.parent().parent().find('.price')).val(item.product.purchase_price);

                        // Update fields
                        $(el.closest('tr').find('.price')).val(item.product.purchase_price);
                    } catch (e) {
                        console.error("Failed to parse response data", e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                }
            });
        });

        // Handle change event for sub-product (spar part item) dropdown
        $(document).on('change', '.sub_product', function() {
            var subProductId = $(this).val();
            var url = "{{ route('expense.product') }}";
            var el = $(this);

            console.log("Spar Part Item ID:", subProductId);

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': subProductId
                },
                success: function(data) {
                    var item = JSON.parse(data);

                    console.log("Spar Part Item Data:", item);

                    // Set the fetched price in the corresponding input field
                    $(el.closest('tr').find('.part-price')).val(item.product.purchase_price);

                    // Optionally update other fields based on the returned data
                    $(el.closest('tr').find('.part-amount')).html(item.totalAmount);
                    // Recalculate totals if necessary
                    // (similar to the calculations in your main item change event)
                    updateTotals();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                }
            });
        });
        // Function to update totals
        function updateTotals() {
            var subTotal = 0;
            var taxVal = 0;
            var totalAmount = 0;

            // Calculate Sub Total
            $('.part-amount').each(function() {
                var amount = parseFloat($(this).html()) || 0;
                console.log("Amount:", amount);
                subTotal += amount;
            });
            $('.subTotal').html(subTotal.toFixed(2));
            console.log("Sub Total calculated:", subTotal.toFixed(2));
            // Calculate VAT

            var totalTaxAmount = 0;


            var taxRate = TotalTax; // Implement getTaxRateById function
            console.log("Tax Rate:", taxRate);
            // console.log("VAT Type:", vatType);
            console.log("Tax Amount before type adjustment:", subTotal);
            totalTaxAmount = (subTotal * taxRate / 100);



            taxVal = totalTaxAmount.toFixed(2);

            $('.tax_val').html(taxVal);


            // Calculate Total Amount

            totalAmount = (subTotal + totalTaxAmount).toFixed(2);


            console.log("Total Amount (Sub Total + Tax Amount):", totalAmount);
            $('.totalAmount').html(totalAmount);
            $('input[name="totalAmount"]').val(totalAmount);
        }

        // Attach event handler to document
        $(document).on('keyup', '.part_quantity', function() {
            console.log("Keyup event triggered.");
            var row = $(this).closest('tr');
            var quantity = parseFloat($(this).val()) || 0;
            var price = parseFloat(row.find('.part-price').val()) || 0;
            var amount = (quantity * price).toFixed(2);
            console.log("Quantity changed:", quantity);
            // Update the amount for the current row
            row.find('.part-amount').html(amount);

            // Recalculate totals
            updateTotals();
        });
        $('#choices-multiple1').on('change', function() {
            // Get selected tax rates
            var selectedValues = $(this).val();
            var taxData = <?php echo json_encode($fullTax); ?>;
            TotalTax = 0;

            // Your logic to calculate total tax amount based on selected rates


            for (let i = 0; i < selectedValues.length; i++) {
                for (let j = 0; j < taxData.length; j++) {
                    if (taxData[j].id === parseInt(selectedValues[i])) {
                        TotalTax += parseInt(taxData[j].rate);
                    }
                }
            }

            var totalAmountValue = $('.totalAmount').text();
            totalAmountValue = parseInt(totalAmountValue) + (parseInt(totalAmountValue) * (parseInt(TotalTax) /
                100));
            var existingSubTotal = parseFloat($('.subTotal').text()) || 0;
            var TotalAmount = existingSubTotal + (existingSubTotal * (parseInt(TotalTax) / 100));

            // $('.totalAmount').text(totalAmount.toFixed(2));
            $('.totalAmount').html(TotalAmount.toFixed(2))
            $('.tax_val').text(parseInt(TotalTax));
            $('.totalTax').html(existingSubTotal * (parseInt(TotalTax) / 100));

        });

        function updateAmount(row) {
            var price = parseFloat(row.find('.sub-price').val());
            var quantity = parseFloat(row.find('.sub-quantity').val());

            if (isNaN(price) || isNaN(quantity)) {
                row.find('.accountamount').text('0.00');
                return;
            }

            var amount = (price * quantity).toFixed(2);
            row.find('.accountamount').text(amount);
            updateTotalAmounts();
        }

        // Function to update the total amounts
        function updateTotalAmounts() {
            var totalItemPrice = 0;
            $('.accountamount').each(function() {
                var amount = parseFloat($(this).text());
                if (!isNaN(amount)) {
                    totalItemPrice += amount;
                }
            });

            var totalTaxRate = parseFloat($('.totalTaxRate').val()) || 0;
            var totalTax = (totalItemPrice * totalTaxRate / 100).toFixed(2);
            var totalAmount = (totalItemPrice + parseFloat(totalTax)).toFixed(2);

            $('.subTotal').text(totalItemPrice.toFixed(2));
            $('.totalTax').text(totalTax);
            $('.totalAmount').text(totalAmount);
        }

        // Event handler for price change
        $(document).on('keyup', '.part-price', function() {
            console.log("Price changed.");

            var row = $(this).closest('tr');
            var quantity = parseFloat(row.find('.part_quantity').val()) || 0;
            var price = parseFloat($(this).val()) || 0;
            var amount = (quantity * price).toFixed(2);

            console.log("Quantity:", quantity);
            console.log("New Price:", price);
            console.log("Calculated amount after price change:", amount);

            // Update the amount for the current row
            row.find('.part-amount').html(amount);

            // Recalculate totals
            updateTotals();
        });
        // When price or quantity changes, update the amount
        $(document).on('input', '.sub-price, .sub-quantity', function() {
            var row = $(this).closest('tr');
            updateAmount(row);
        });
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
        });
    </script>
    <script>
        $(document).ready(function() {
            // Add Part Item logic
            $('#sortable-table').on('click', '.add-part-item', function() {
                let mainItemRow = $(this).closest('tr').prevAll('.item-section:first');
                let partItemTemplate = $('.part-item-template').first().clone(true).removeClass(
                    'part-item-template d-none');
                let mainIndex = $('#sortable-table tbody .item-section').index(mainItemRow);
                let partIndex = mainItemRow.find('.part-item').length;

                partItemTemplate.find('select, input').each(function() {
                    let nameAttr = $(this).attr('name');
                    if (nameAttr) {
                        nameAttr = nameAttr.replace(/items\[\d+\]\[part_items\]\[\d+\]/,
                            `items[${mainIndex}][part_items][${partIndex}]`);
                        $(this).attr('name', nameAttr);
                        console.log(`Updated name attribute: ${nameAttr}`); // Debugging line
                    }
                });

                $(this).closest('tr').before(partItemTemplate);
            });

            $('#sortable-table').on('click', '.remove-part-item', function() {
                $(this).closest('.part-item').remove();
            });

            function updatePartItemIndices() {
                $('#sortable-table tbody .item-section').each(function(mainIndex) {
                    $(this).find('.part-item').each(function(partIndex) {
                        $(this).find('select, input').each(function() {
                            let nameAttr = $(this).attr('name');
                            if (nameAttr) {
                                nameAttr = nameAttr.replace(
                                    /items\[\d+\]\[part_items\]\[\d+\]/,
                                    `items[${mainIndex}][part_items][${partIndex}]`);
                                $(this).attr('name', nameAttr);
                                console.log(
                                    `Updated name attribute: ${nameAttr}`
                                ); // Debugging line
                            }
                        });
                    });
                });
            }

            $('#sortable-table').on('click', '[data-repeater-create], [data-repeater-delete]', function() {
                setTimeout(updatePartItemIndices, 100);
            });
        });
    </script>
@endpush
@section('content')
    <div class="row">
        <form action="{{ url('manufacturers') }}" method="POST" class="w-100">
            @csrf
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_date" class="form-label">{{ __('Date') }}</label>
                                            <input type="date" name="payment_date" class="form-control"
                                                required="required">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="description" class="form-label">{{ __('Description') }}</label>
                                            <textarea class="form-control" name="description" placeholder="{{ __('Enter Description') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h5 class="d-inline-block mb-4">{{ __('Product & Services') }}</h5>
                <div class="card repeater">
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary" data-bs-toggle="modal"
                                        data-target="#add-bank">
                                        <i class="ti ti-plus"></i> {{ __('Add Item') }}
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
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('Items') }}</th>
                                        <th>{{ __('Vendor') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Price') }} </th>
                                        <th>{{ __('Tax') }} (%)</th>
                                        <th class="text-end">{{ __('Amount') }}
                                            <br><small class="text-danger font-bold">{{ __('after tax') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>
                                        <td width="20%" class="form-group">
                                            <select name="items[][category_id]" class="form-control select2 category"
                                                id="category">
                                                @foreach ($category as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td width="25%" class="form-group">
                                            <select name="items[][item_id]" class="form-control select2 item"
                                                data-url="{{ route('expense.product') }}">
                                                <option value="" disabled selected>{{ __('Select Item') }}
                                                    @foreach ($product_services as $value)
                                                <option value="{{ $value['id'] }}"
                                                    data-category-id="{{ $value['category_id'] }}">
                                                    {{ $value['name'] }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="items[][price]" class="form-control price"
                                                    placeholder="{{ __('Price') }}" readonly>
                                                <span
                                                    class="input-group-text bg-transparent">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="#"
                                                class="ti ti-trash text-white repeater-action-btn bg-danger ms-2"
                                                data-repeater-delete></a>
                                        </td>
                                    </tr>

                                    <tr class="part-item part-item-template">
                                        <td></td>
                                        <td width="25%" class="form-group">
                                            <select name="items[][part_items][][id_part]"
                                                class="form-control select2 sub_product">
                                                <option value="" disabled selected>{{ __('Select Part Item') }}
                                                </option>
                                                @foreach ($product_services_spar as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[][part_items][][vendor_id]" class="form-control vendor">
                                                @foreach ($venders as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[][part_items][][quantity]"
                                                class="form-control part_quantity" placeholder="{{ __('Qty') }}">
                                        </td>
                                        <td>
                                            <input type="text" name="items[][part_items][][price]"
                                                class="form-control part-price" placeholder="{{ __('Price') }}">
                                        </td>
                                        <td class="text-end part-amount">
                                            0.00
                                        </td>
                                        <td>
                                            <a href="#"
                                                class="ti ti-trash text-white repeater-action-btn bg-danger ms-2 remove-part-item"></a>
                                        </td>
                                    </tr>
                                    <tr class="add-part-item-template">
                                        <td colspan="6">
                                            <button type="button"
                                                class="btn btn-primary add-part-item">{{ __('Add Part
                                                                                                                                                                                            Item') }}</button>
                                        </td>
                                    </tr>

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td><strong>{{ __('Sub Total') }} ({{ \Auth::user()->currencySymbol() }})</strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>
                                            <div class="form-group col-md-12">
                                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                                <select id="choices-multiple1" name="tax_id[]"
                                                    class="form-control select2 custom-select">
                                                    <option value="" disabled selected>Select Tax</option>
                                                    @foreach ($fullTax as $value)
                                                        <option value="{{ $value['id'] }}">
                                                            {{ $value['name'] . ' (' . $value['type'] . ')' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td class="text-end tax_val">0.00</td>

                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text"><strong>{{ __('Total Amount') }}
                                                ({{ \Auth::user()->currencySymbol() }})</strong></td>

                                        <td class="blue-text text-end totalAmount">0.00</td>
                                        <input type="hidden" name="totalAmount" class="form-control totalAmount">

                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal-footer">
                <input type="button" value="{{ __('Cancel') }}"
                    onclick="location.href = '{{ route('manufacturers.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
            </div>
        </form>
    </div>
@endsection
