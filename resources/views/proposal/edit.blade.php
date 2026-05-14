@extends('layouts.admin')
@section('page-title')
    {{ __('Proposal Edit') }}
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeaer tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: true,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }

                },
                hide: function(deleteElement) {
                    $(this).slideUp(deleteElement);
                    $(this).remove();
                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }
                    $('.subTotal').html(subTotal.toFixed(2));
                    $('.totalAmount').html(subTotal.toFixed(2));

                },
                ready:

                    function(setIndexes) {
                        $dragAndDrop.on('drop', setIndexes);
                    }

                    ,
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');

            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
                for (var i = 0; i < value.length; i++) {
                    var tr = $('#sortable-table .id[value="' + value[i].id + '"]').parent();
                    tr.find('.item').val(value[i].product_id);
                    changeItem(tr.find('.item'));
                }
            }

        }


        $(document).on('change', '#customer', function() {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    if (data != '') {
                        $('#customer_detail').html(data);
                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }
                },

            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.item', function() {
            changeItem($(this));
        });

        var proposal_id = '{{ $proposal->id }}';

        function changeItem(element) {
            var iteams_id = element.val();

            var url = element.data('url');
            var el = element;
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
                    var item = JSON.parse(data);

                    $.ajax({
                        url: '{{ route('proposal.items') }}',
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': jQuery('#token').val()
                        },
                        data: {
                            'proposal_id': proposal_id,
                            'product_id': iteams_id,
                        },
                        cache: false,
                        success: function(data) {
                            var proposalItems = JSON.parse(data);
                            var oldExchangeRate = parseFloat('{{ $proposal->exchange_rate ?? 1 }}') || 1;
                            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;

                            if (proposalItems != null) {
                                var amount = (proposalItems.price);
                                
                                // Calculate base price: if there's a currency, divide by exchange rate
                                var basePrice, baseDiscount;
                                if (proposalItems.exchange_price != null && proposalItems.exchange_price > 0) {
                                    basePrice = parseFloat(proposalItems.exchange_price) || 0;
                                } else {
                                    // Divide by old exchange rate to get base price (saved price / exchange rate)
                                    var savedPrice = parseFloat(proposalItems.price) || 0;
                                    basePrice = oldExchangeRate > 0 && oldExchangeRate != 1 ? (savedPrice / oldExchangeRate) : savedPrice;
                                }
                                
                                if (proposalItems.exchange_discount != null && proposalItems.exchange_discount > 0) {
                                    baseDiscount = parseFloat(proposalItems.exchange_discount) || 0;
                                } else {
                                    var savedDiscount = parseFloat(proposalItems.discount) || 0;
                                    baseDiscount = oldExchangeRate > 0 && oldExchangeRate != 1 ? (savedDiscount / oldExchangeRate) : savedDiscount;
                                }
                                
                                // Ensure basePrice and baseDiscount are numbers
                                basePrice = parseFloat(basePrice) || 0;
                                baseDiscount = parseFloat(baseDiscount) || 0;
                                
                                // Show currency price (exchange_price) in price field if currency is selected, otherwise show AED price
                                var currencyId = $('#currency_id').val();
                                var displayPrice, displayDiscount;
                                
                                if (currencyId && currencyId !== '') {
                                    // Show currency price (exchange_price) directly in price field
                                    displayPrice = basePrice; // basePrice is already exchange_price
                                    displayDiscount = baseDiscount; // baseDiscount is already exchange_discount
                                    
                                    // Display currency price (exchange_price) below price field
                                    var currencyPriceDisplay = $(el.parent().parent().parent().find('.currency-price-display'));
                                    var currencyPriceValue = $(el.parent().parent().parent().find('.currency-price-value'));
                                    var currencyPriceLabel = $(el.parent().parent().parent().find('.currency-price-label'));
                                    // Use exchange_price from database, or basePrice if exchange_price is not available
                                    var exchangePrice = (proposalItems.exchange_price != null && proposalItems.exchange_price > 0) 
                                        ? parseFloat(proposalItems.exchange_price) 
                                        : parseFloat(basePrice) || 0;
                                    currencyPriceValue.text(exchangePrice.toFixed(2));
                                    currencyPriceValue.attr('data-exchange-price', exchangePrice.toFixed(2));
                                    currencyPriceLabel.text($('.currency-symbol').first().text().trim() || 'Currency');
                                    currencyPriceDisplay.show();
                                } else {
                                    // Show AED price
                                    displayPrice = parseFloat(proposalItems.price) || 0; // AED price from database
                                    displayDiscount = parseFloat(proposalItems.discount) || 0; // AED discount from database
                                    $(el.parent().parent().parent().find('.currency-price-display')).hide();
                                }
                                
                                // Ensure displayPrice and displayDiscount are numbers
                                displayPrice = parseFloat(displayPrice) || 0;
                                displayDiscount = parseFloat(displayDiscount) || 0;

                                $(el.parent().parent().parent().find('.quantity')).val(proposalItems.quantity);
                                $(el.parent().parent().parent().find('.price')).val(displayPrice.toFixed(2));
                                $(el.parent().parent().parent().find('.discount')).val(displayDiscount.toFixed(2));
                                $(el.parent().parent().parent().find('.pro_description')).val(
                                    proposalItems.description);
                                // $('.pro_description').text(proposalItems.description);

                            } else {
                                $(el.parent().parent().parent().find('.quantity')).val(1);
                                // Apply current exchange rate to product sale price
                                var basePrice = parseFloat(item.product.currency_price) || 0;
                                var displayPrice = basePrice * currentExchangeRate;
                                $(el.parent().parent().parent().find('.price')).val(basePrice.toFixed(2));
                                $(el.parent().parent().parent().find('.discount')).val(0);
                                $(el.parent().parent().parent().find('.pro_description')).val(item
                                    .product.description);
                                
                                // Display currency price if currency is selected
                                var currencyId = $('#currency_id').val();
                                if (currencyId && currencyId !== '') {
                                    var currencyPriceDisplay = $(el.parent().parent().parent().find('.currency-price-display'));
                                    var currencyPriceValue = $(el.parent().parent().parent().find('.currency-price-value'));
                                    var currencyPriceLabel = $(el.parent().parent().parent().find('.currency-price-label'));
                                    currencyPriceValue.text(basePrice.toFixed(2));
                                    currencyPriceValue.attr('data-exchange-price', basePrice.toFixed(2));
                                    currencyPriceLabel.text($('.currency-symbol').first().text().trim() || 'Currency');
                                    currencyPriceDisplay.show();
                                } else {
                                    $(el.parent().parent().parent().find('.currency-price-display')).hide();
                                }
                                // $('.pro_description').text(item.product.sale_price);
                            }


                            // Tax is now calculated at proposal level, not item level
                            // Clear item tax fields
                            $(el.parent().parent().parent().find('.itemTaxPrice')).val(0);
                            $(el.parent().parent().parent().find('.itemTaxRate')).val(0);
                            $(el.parent().parent().parent().find('.taxes')).html('');
                            $(el.parent().parent().parent().find('.tax')).val('');
                            $(el.parent().parent().parent().find('.unit')).html(item.unit);

                            // Calculate item amount without tax (tax is at proposal level)
                            var discount = parseFloat($(el.parent().parent().parent().find('.discount')).val()) || 0;
                            var quantity = parseFloat($(el.parent().parent().parent().find('.quantity')).val()) || 0;
                            var price = parseFloat($(el.parent().parent().parent().find('.price')).val()) || 0;
                            
                            // Convert to AED for calculations if currency is selected
                            var currencyId = $('#currency_id').val();
                            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                            var actualPrice = price;
                            var actualDiscount = discount;
                            
                            if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                actualPrice = price * currentExchangeRate;
                                actualDiscount = discount * currentExchangeRate;
                            }
                            
                            var itemAmount = (actualPrice * quantity) - actualDiscount;
                            $(el.parent().parent().parent().find('.amount')).html(itemAmount.toFixed(2));

                            // Calculate totals
                            var totalItemPrice = 0;
                            var inputs_quantity = $(".quantity");
                            var priceInput = $('.price');
                            for (var j = 0; j < priceInput.length; j++) {
                                var unitPrice = parseFloat(priceInput[j].value) || 0;
                                var qty = parseFloat(inputs_quantity[j].value) || 0;
                                
                                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                    unitPrice = unitPrice * currentExchangeRate;
                                }
                                
                                totalItemPrice += unitPrice * qty;
                            }

                            var totalItemDiscountPrice = 0;
                            var itemDiscountPriceInput = $('.discount');
                            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                                var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                                var qty = parseFloat(inputs_quantity[k].value) || 0;
                                
                                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                    discountValue = discountValue * currentExchangeRate;
                                }
                                
                                totalItemDiscountPrice += discountValue * qty;
                            }

                            updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);


                        }
                    });


                },
            });
        }

        $(document).on('keyup', '.quantity', function() {
            var el = $(this).parent().parent().parent().parent();

            var quantity = parseFloat($(this).val()) || 0;
            var price = parseFloat($(el.find('.price')).val()) || 0;
            var discount = parseFloat($(el.find('.discount')).val()) || 0;

            // Convert to AED for calculations if currency is selected
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            var actualPrice = price;
            var actualDiscount = discount;
            
            if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                actualPrice = price * currentExchangeRate;
                actualDiscount = discount * currentExchangeRate;
            }

            var totalItemPrice = (quantity * actualPrice) - actualDiscount;
            var amount = totalItemPrice;

            // Tax is calculated at proposal level, not item level
            $(el.find('.itemTaxPrice')).val(0);
            $(el.find('.amount')).html(amount.toFixed(2));

            // Calculate total item price in AED (convert currency prices to AED if needed)
            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            for (var j = 0; j < priceInput.length; j++) {
                var unitPrice = parseFloat(priceInput[j].value) || 0;
                var qty = parseFloat(inputs_quantity[j].value) || 0;
                
                // If currency is selected, price is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    unitPrice = unitPrice * currentExchangeRate;
                }
                
                totalItemPrice += unitPrice * qty;
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html()) || 0;
            }

            // Calculate total discount in AED (convert currency discounts to AED if needed)
            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                var qty = parseFloat(inputs_quantity[k].value) || 0;
                
                // If currency is selected, discount is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    discountValue = discountValue * currentExchangeRate;
                }
                
                totalItemDiscountPrice += discountValue * qty;
            }

            updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = parseFloat($(this).val()) || 0;
            var quantity = parseFloat($(el.find('.quantity')).val()) || 0;
            var discount = parseFloat($(el.find('.discount')).val()) || 0;
            
            // Update currency price display if currency is selected
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            var actualPrice = price; // Price to use for calculations
            
            if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                // Price field shows currency price (exchange_price) - unit price per item
                var currencyPriceDisplay = el.find('.currency-price-display');
                var currencyPriceValue = el.find('.currency-price-value');
                
                // Store the currency price (exchange_price) in data attribute
                currencyPriceValue.text(price.toFixed(2));
                currencyPriceValue.attr('data-exchange-price', price.toFixed(2));
                currencyPriceLabel = el.find('.currency-price-label');
                if (currencyPriceLabel.length) {
                    currencyPriceLabel.text($('.currency-symbol').first().text().trim() || 'Currency');
                }
                currencyPriceDisplay.show();
                
                // For calculations, convert currency price to AED
                actualPrice = price * currentExchangeRate;
            } else {
                // No currency - price is already in AED
                actualPrice = price;
                el.find('.currency-price-display').hide();
            }

            // Calculate total item price (price * quantity - discount) in AED for calculations
            var totalItemPrice = (actualPrice * quantity) - discount;
            var amount = totalItemPrice;

            // Tax is calculated at proposal level, not item level
            $(el.find('.itemTaxPrice')).val(0);
            $(el.find('.amount')).html(amount.toFixed(2));

            // Calculate total item price in AED (convert currency prices to AED if needed)
            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            for (var j = 0; j < priceInput.length; j++) {
                var unitPrice = parseFloat(priceInput[j].value) || 0;
                var qty = parseFloat(inputs_quantity[j].value) || 0;
                
                // If currency is selected, price is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    unitPrice = unitPrice * currentExchangeRate;
                }
                
                totalItemPrice += unitPrice * qty;
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html()) || 0;
            }

            // Calculate total discount in AED (convert currency discounts to AED if needed)
            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                var qty = parseFloat(inputs_quantity[k].value) || 0;
                
                // If currency is selected, discount is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    discountValue = discountValue * currentExchangeRate;
                }
                
                totalItemDiscountPrice += discountValue * qty;
            }

            updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);

        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = parseFloat($(this).val()) || 0;
            var price = parseFloat($(el.find('.price')).val()) || 0;
            var quantity = parseFloat($(el.find('.quantity')).val()) || 0;
            
            // Convert to AED for calculations if currency is selected
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            var actualPrice = price;
            var actualDiscount = discount;
            
            if (currencyId && currencyId !== '') {
                // Price and discount are in currency, convert to AED for calculations
                actualPrice = price * currentExchangeRate;
                actualDiscount = discount * currentExchangeRate;
            }
            
            var totalItemPrice = (quantity * actualPrice) - actualDiscount;
            var amount = totalItemPrice;

            // Tax is calculated at proposal level, not item level
            $(el.find('.itemTaxPrice')).val(0);
            $(el.find('.amount')).html(amount.toFixed(2));

            // Calculate total item price in AED (convert currency prices to AED if needed)
            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            for (var j = 0; j < priceInput.length; j++) {
                var unitPrice = parseFloat(priceInput[j].value) || 0;
                var qty = parseFloat(inputs_quantity[j].value) || 0;
                
                // If currency is selected, price is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    unitPrice = unitPrice * currentExchangeRate;
                }
                
                totalItemPrice += unitPrice * qty;
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html()) || 0;
            }

            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');
            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                totalItemDiscountPrice += (parseFloat(itemDiscountPriceInput[k].value) || 0) * (parseFloat(inputs_quantity[k].value) || 0);
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));

        })

        $(document).on('click', '[data-repeater-create]', function() {
            $('.item :selected').each(function() {
                var id = $(this).val();
                $(".item option[value=" + id + "]").prop("disabled", true);
            });
        })

        $(document).on('click', '[data-repeater-delete]', function() {
            // $('.delete_item').click(function () {
            if (confirm('Are you sure you want to delete this element?')) {
                var el = $(this).parent().parent();
                var id = $(el.find('.id')).val();

                $.ajax({
                    url: '{{ route('proposal.product.destroy') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': jQuery('#token').val()
                    },
                    data: {
                        'id': id
                    },
                    cache: false,
                    success: function(data) {

                    },
                });

            }
        });
    </script>

    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
    <script>
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
                        vatType = taxData[j].type || 'add';
                    }
                }
            }

            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            // Get totals in AED first
            // Calculate total item price in AED (convert currency prices to AED if needed)
            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            
            for (var j = 0; j < priceInput.length; j++) {
                var unitPrice = parseFloat(priceInput[j].value) || 0;
                var qty = parseFloat(inputs_quantity[j].value) || 0;
                
                // If currency is selected, price is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    unitPrice = unitPrice * currentExchangeRate;
                }
                
                totalItemPrice += unitPrice * qty;
            }
            
            // Calculate total discount in AED (convert currency discounts to AED if needed)
            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');
            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                var qty = parseFloat(inputs_quantity[k].value) || 0;
                
                // If currency is selected, discount is in currency, convert to AED for calculations
                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                    discountValue = discountValue * currentExchangeRate;
                }
                
                totalItemDiscountPrice += discountValue * qty;
            }
            
            // Convert to currency if needed
            var existingSubTotal = totalItemPrice;
            var existingDiscount = totalItemDiscountPrice;
            
            if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                existingSubTotal = totalItemPrice / currentExchangeRate;
                existingDiscount = totalItemDiscountPrice / currentExchangeRate;
            }
            
            var TotalAmount = 0;
            var VATAmount = 0;
            if (vatType === 'add') {
                VATAmount = (existingSubTotal - existingDiscount) * (parseInt(TotalTax) / 100);
                TotalAmount = (existingSubTotal - existingDiscount) + VATAmount;
            } else if (vatType === 'subtract') {
                VATAmount = (existingSubTotal * (parseInt(TotalTax) / 100)) / (1 + (parseInt(TotalTax) / 100));
                TotalAmount = (existingSubTotal - existingDiscount) - VATAmount;
            }

            $('.totalAmount').html(TotalAmount.toFixed(2));
            $('.tax_val').text(parseInt(TotalTax));
            $('.totalTax').html(VATAmount.toFixed(2));
            
            // Update other totals
            $('.subTotal').html(existingSubTotal.toFixed(2));
            $('.totalDiscount').html(existingDiscount.toFixed(2));
            
            // Also trigger updateTotalsInCurrency to ensure consistency
            updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);

        });
    </script>
    <script>
        // Helper function to update totals in currency
        function updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, totalItemTaxPrice) {
            var currencyId = $('#currency_id').val();
            var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            
            // Calculate proposal-level tax from tax_id selector
            var proposalTax = 0;
            var selectedTaxIds = $('#choices-multiple1').val() || [];
            var taxData = <?php echo json_encode($fullTax); ?>;
            var totalTaxRate = 0;
            var vatType = 'add';
            
            if (selectedTaxIds && selectedTaxIds.length > 0) {
                for (let i = 0; i < selectedTaxIds.length; i++) {
                    for (let j = 0; j < taxData.length; j++) {
                        if (taxData[j].id === parseInt(selectedTaxIds[i])) {
                            totalTaxRate += parseFloat(taxData[j].rate) || 0;
                            vatType = taxData[j].type || 'add';
                        }
                    }
                }
            }
            
            // Calculate taxable amount (subtotal - discount)
            var taxableAmount = totalItemPrice - totalItemDiscountPrice;
            
            // Calculate tax based on vatType
            if (totalTaxRate > 0) {
                if (vatType === 'add') {
                    proposalTax = taxableAmount * (totalTaxRate / 100);
                } else if (vatType === 'subtract') {
                    proposalTax = (taxableAmount * (totalTaxRate / 100)) / (1 + (totalTaxRate / 100));
                }
            }
            
            if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                // Calculate totals from exchange_price and exchange_discount of items
                var currencySubTotal = 0;
                var currencyDiscount = 0;
                
                $('.item').each(function() {
                    if ($(this).val()) {
                        var row = $(this).closest('tr');
                        var quantity = parseFloat(row.find('.quantity').val()) || 0;
                        var currencyPriceValue = row.find('.currency-price-value');
                        
                        // Get exchange_price from currency price display data attribute or text, or calculate from current price
                        var exchangePrice = 0;
                        if (currencyPriceValue.length) {
                            // First try to get from data attribute (most reliable - comes from database)
                            var dataExchangePrice = currencyPriceValue.attr('data-exchange-price');
                            if (dataExchangePrice && parseFloat(dataExchangePrice) > 0) {
                                exchangePrice = parseFloat(dataExchangePrice);
                            } else if (currencyPriceValue.text() && currencyPriceValue.text() !== '0.00') {
                                // Fallback to text value
                                exchangePrice = parseFloat(currencyPriceValue.text()) || 0;
                            } else {
                                // Calculate from current price (AED) divided by exchange rate
                                var currentPrice = parseFloat(row.find('.price').val()) || 0;
                                exchangePrice = currentPrice / currentExchangeRate;
                            }
                        } else {
                            // Calculate from current price (AED) divided by exchange rate
                            var currentPrice = parseFloat(row.find('.price').val()) || 0;
                            exchangePrice = currentPrice / currentExchangeRate;
                        }
                        
                        // Get exchange_discount - discount field shows currency discount when currency is selected
                        var currentDiscount = parseFloat(row.find('.discount').val()) || 0;
                        var exchangeDiscount = 0;
                        
                        // If currency is selected, discount field shows currency discount (exchange_discount)
                        if (currencyId && currencyId !== '') {
                            exchangeDiscount = currentDiscount; // Already in currency
                        } else {
                            // No currency, discount is in AED, convert to currency
                            exchangeDiscount = currentDiscount / currentExchangeRate;
                        }
                        
                        currencySubTotal += (exchangePrice * quantity);
                        currencyDiscount += exchangeDiscount;
                    }
                });
                
                // Calculate tax in currency
                var currencyTaxableAmount = currencySubTotal - currencyDiscount;
                var currencyTax = 0;
                if (totalTaxRate > 0) {
                    if (vatType === 'add') {
                        currencyTax = currencyTaxableAmount * (totalTaxRate / 100);
                    } else if (vatType === 'subtract') {
                        currencyTax = (currencyTaxableAmount * (totalTaxRate / 100)) / (1 + (totalTaxRate / 100));
                    }
                }
                
                var currencyTotal = (currencySubTotal - currencyDiscount + currencyTax);
                
                $('.subTotal').html(currencySubTotal.toFixed(2));
                $('.totalTax').html(currencyTax.toFixed(2));
                $('.totalAmount').html(currencyTotal.toFixed(2));
                $('.totalDiscount').html(currencyDiscount.toFixed(2));
            } else {
                // Show in AED
                $('.subTotal').html(totalItemPrice.toFixed(2));
                $('.totalTax').html(proposalTax.toFixed(2));
                $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) + parseFloat(proposalTax)).toFixed(2));
                $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));
            }
        }

        // Helper function to update currency symbol labels
        function updateCurrencySymbolLabels() {
            var currencyId = $('#currency_id').val();
            var symbol = '{{ \Auth::user()->currencySymbol() }}';
            
            if (currencyId && currencyId !== '') {
                var currencySymbol = $('.currency-symbol').first().text().trim() || symbol;
                $('.currency-symbol-label').text(currencySymbol);
            } else {
                $('.currency-symbol-label').text(symbol);
            }
        }

        $(document).ready(function() {
            var exchangeRate = parseFloat($('#exchange_rate').val()) || 1;
            var oldExchangeRate = parseFloat('{{ $proposal->exchange_rate ?? 1 }}') || 1;
            const exchangeRateDiv = document.getElementById('exchange_rate_div');

            // Set default currency symbol on page load
            $('.currency-symbol').text('{{ \Auth::user()->currencySymbol() }}');
            updateCurrencySymbolLabels();

            // Function to recalculate all prices with exchange rate
            function recalculateWithExchangeRate() {
                var currencyId = $('#currency_id').val();
                
                $('.item').each(function() {
                    if ($(this).val()) {
                        var row = $(this).closest('tr');
                        var currencyPriceValue = row.find('.currency-price-value');
                        
                        if (currencyId && currencyId !== '') {
                            // When currency is selected, price field should show currency price (exchange_price)
                            // Get the exchange_price from data attribute or calculate from current price
                            var exchangePrice = 0;
                            if (currencyPriceValue.length) {
                                var dataExchangePrice = currencyPriceValue.attr('data-exchange-price');
                                if (dataExchangePrice && parseFloat(dataExchangePrice) > 0) {
                                    exchangePrice = parseFloat(dataExchangePrice) || 0;
                                } else {
                                    // Calculate from current price (which might be AED) divided by exchange rate
                                    var currentPrice = parseFloat(row.find('.price').val()) || 0;
                                    exchangePrice = (currentPrice / exchangeRate) || 0;
                                    currencyPriceValue.text(exchangePrice.toFixed(2));
                                    currencyPriceValue.attr('data-exchange-price', exchangePrice.toFixed(2));
                                }
                            } else {
                                // Calculate from current price
                                var currentPrice = parseFloat(row.find('.price').val()) || 0;
                                exchangePrice = (currentPrice / exchangeRate) || 0;
                            }
                            
                            // Ensure exchangePrice is a number
                            exchangePrice = parseFloat(exchangePrice) || 0;
                            
                            // Keep currency price in price field (don't convert to AED)
                            row.find('.price').val(exchangePrice.toFixed(2));
                            
                            // Update discount - get exchange_discount
                            var currentDiscount = parseFloat(row.find('.discount').val()) || 0;
                            var exchangeDiscount = currentDiscount / exchangeRate;
                            row.find('.discount').val(exchangeDiscount.toFixed(2));
                        } else {
                            // No currency - prices are in AED, recalculate if exchange rate changed
                            var currentPrice = parseFloat(row.find('.price').val()) || 0;
                            var currentDiscount = parseFloat(row.find('.discount').val()) || 0;
                            
                            if (oldExchangeRate > 0 && currentPrice > 0) {
                                var basePrice = currentPrice / oldExchangeRate;
                                var baseDiscount = currentDiscount / oldExchangeRate;
                                
                                var newPrice = basePrice * exchangeRate;
                                var newDiscount = baseDiscount * exchangeRate;
                                
                                row.find('.price').val(newPrice.toFixed(2));
                                row.find('.discount').val(newDiscount.toFixed(2));
                            }
                        }
                        
                        // Trigger price change to recalculate totals
                        row.find('.price').trigger('change');
                    }
                });
                oldExchangeRate = exchangeRate;
            }

            $('#currency_id').change(function() {
                var currencyId = $(this).val();
                var symbol = '{{ \Auth::user()->currencySymbol() }}'; // Default

                if (currencyId === '') {
                    // Default selected (empty value)
                    $('.currency-symbol').text(symbol);
                    updateCurrencySymbolLabels();
                    $('#exchange_rate_div').hide();
                    $('.currency-price-display').hide();
                    $('#exchange_rate').val('');
                    exchangeRate = 1;
                    recalculateWithExchangeRate();
                } else {
                    // Fetch symbol and exchange rate from backend
                    fetch('/get-exchange-rate/' + currencyId)
                        .then(response => response.json())
                        .then(data => {
                            var currencySymbol = data.symbol || data.code || symbol;
                            $('.currency-symbol').text(currencySymbol);
                            updateCurrencySymbolLabels();
                            $('#exchange_rate_div').show();
                            exchangeRate = parseFloat(data.exchange_rate) || 1;
                            $('#exchange_rate').val(data.exchange_rate);
                            
                            // Update currency price displays for all items
                            $('.item').each(function() {
                                if ($(this).val()) {
                                    var row = $(this).closest('tr');
                                    var priceInput = row.find('.price');
                                    if (priceInput.val()) {
                                        var currentPrice = parseFloat(priceInput.val()) || 0;
                                        // If price is in AED, convert to currency; otherwise use current price as currency price
                                        var currencyPrice = currentPrice;
                                        var currencyPriceDisplay = row.find('.currency-price-display');
                                        var currencyPriceValue = row.find('.currency-price-value');
                                        var currencyPriceLabel = row.find('.currency-price-label');
                                        
                                        // Check if we have exchange_price in data attribute
                                        var dataExchangePrice = currencyPriceValue.attr('data-exchange-price');
                                        if (dataExchangePrice && parseFloat(dataExchangePrice) > 0) {
                                            currencyPrice = parseFloat(dataExchangePrice) || 0;
                                        } else {
                                            // Assume current price might be AED, convert to currency
                                            currencyPrice = (currentPrice / exchangeRate) || 0;
                                        }
                                        
                                        // Ensure currencyPrice is a number
                                        currencyPrice = parseFloat(currencyPrice) || 0;
                                        
                                        // Update price field to show currency price
                                        priceInput.val(currencyPrice.toFixed(2));
                                        
                                        currencyPriceValue.text(currencyPrice.toFixed(2));
                                        currencyPriceValue.attr('data-exchange-price', currencyPrice.toFixed(2));
                                        currencyPriceLabel.text(currencySymbol);
                                        currencyPriceDisplay.show();
                                    }
                                }
                            });
                            
                            recalculateWithExchangeRate();
                        })
                        .catch(() => {
                            $('.currency-symbol').text(symbol);
                        });
                }
            });

            // Handle exchange rate manual change
            $('#exchange_rate').on('keyup change', function() {
                exchangeRate = parseFloat($(this).val()) || 1;
                if (exchangeRate <= 0) {
                    exchangeRate = 1;
                    $(this).val(1);
                }
                
                // Update currency price displays for all items
                var currencySymbol = $('.currency-symbol').first().text().trim() || 'Currency';
                $('.item').each(function() {
                    if ($(this).val()) {
                        var row = $(this).closest('tr');
                        var priceInput = row.find('.price');
                        if (priceInput.val()) {
                            var currentPrice = parseFloat(priceInput.val()) || 0;
                            var currencyPriceDisplay = row.find('.currency-price-display');
                            var currencyPriceValue = row.find('.currency-price-value');
                            var currencyPriceLabel = row.find('.currency-price-label');
                            
                            // Check if we have exchange_price in data attribute
                            var dataExchangePrice = currencyPriceValue.attr('data-exchange-price');
                            var currencyPrice = 0;
                            
                            if (dataExchangePrice && parseFloat(dataExchangePrice) > 0) {
                                // Use stored exchange_price
                                currencyPrice = parseFloat(dataExchangePrice) || 0;
                            } else {
                                // Calculate from current price (assume it might be AED)
                                currencyPrice = (currentPrice / exchangeRate) || 0;
                            }
                            
                            // Ensure currencyPrice is a number
                            currencyPrice = parseFloat(currencyPrice) || 0;
                            
                            // Update price field to show currency price (not AED)
                            priceInput.val(currencyPrice.toFixed(2));
                            
                            currencyPriceValue.text(currencyPrice.toFixed(2));
                            currencyPriceValue.attr('data-exchange-price', currencyPrice.toFixed(2));
                            currencyPriceLabel.text(currencySymbol);
                            currencyPriceDisplay.show();
                        }
                    }
                });
                
                recalculateWithExchangeRate();
                
                // Recalculate totals in currency
                var totalItemPrice = 0;
                var inputs_quantity = $(".quantity");
                var priceInput = $('.price');
                for (var j = 0; j < priceInput.length; j++) {
                    totalItemPrice += (parseFloat(priceInput[j].value) || 0) * (parseFloat(inputs_quantity[j].value) || 0);
                }
                
                var totalItemDiscountPrice = 0;
                var itemDiscountPriceInput = $('.discount');
                var currencyId = $('#currency_id').val();
                var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                
                for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                    var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                    var qty = parseFloat(inputs_quantity[k].value) || 0;
                    
                    if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                        discountValue = discountValue * currentExchangeRate;
                    }
                    
                    totalItemDiscountPrice += discountValue * qty;
                }
                
                updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);
            });
            
            // Initialize currency symbol and exchange rate on page load if currency is already selected
            var selectedCurrencyId = $('#currency_id').val();
            if (selectedCurrencyId) {
                fetch('/get-exchange-rate/' + selectedCurrencyId)
                    .then(response => response.json())
                    .then(data => {
                        $('.currency-symbol').text(data.symbol || data.code || '{{ \Auth::user()->currencySymbol() }}');
                        if (data.exchange_rate) {
                            exchangeRate = parseFloat(data.exchange_rate) || 1;
                            $('#exchange_rate').val(data.exchange_rate);
                        }
                    })
                    .catch(() => {
                        // Keep default symbol
                    });
            }

            // Update changeItem function to apply exchange rate
            var originalChangeItem = window.changeItem;
            window.changeItem = function(element) {
                var iteams_id = element.val();
                var url = element.data('url');
                var el = element;
                var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                
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
                        var item = JSON.parse(data);

                        $.ajax({
                            url: '{{ route('proposal.items') }}',
                            type: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': jQuery('#token').val()
                            },
                            data: {
                                'proposal_id': proposal_id,
                                'product_id': iteams_id,
                            },
                            cache: false,
                            success: function(data) {
                                var proposalItems = JSON.parse(data);
                                var basePrice, baseDiscount;

                                if (proposalItems != null) {
                                    // Use exchange_price if available (base price), otherwise divide by old exchange rate
                                    if (proposalItems.exchange_price != null && proposalItems.exchange_price > 0) {
                                        basePrice = parseFloat(proposalItems.exchange_price) || 0;
                                    } else {
                                        // Divide by old exchange rate to get base price
                                        var oldRate = parseFloat('{{ $proposal->exchange_rate ?? 1 }}') || 1;
                                        var savedPrice = parseFloat(proposalItems.price) || 0;
                                        basePrice = oldRate > 0 ? (savedPrice / oldRate) : savedPrice;
                                    }
                                    
                                    if (proposalItems.exchange_discount != null && proposalItems.exchange_discount > 0) {
                                        baseDiscount = parseFloat(proposalItems.exchange_discount) || 0;
                                    } else {
                                        var oldRate = parseFloat('{{ $proposal->exchange_rate ?? 1 }}') || 1;
                                        var savedDiscount = parseFloat(proposalItems.discount) || 0;
                                        baseDiscount = oldRate > 0 ? (savedDiscount / oldRate) : savedDiscount;
                                    }
                                    
                                    // Ensure basePrice and baseDiscount are numbers
                                    basePrice = parseFloat(basePrice) || 0;
                                    baseDiscount = parseFloat(baseDiscount) || 0;

                                    $(el.parent().parent().parent().find('.quantity')).val(proposalItems.quantity);
                                    
                                    // Show currency price (exchange_price) in price field if currency is selected, otherwise show AED price
                                    var currencyId = $('#currency_id').val();
                                    var displayPrice, displayDiscount;
                                    
                                    if (currencyId && currencyId !== '') {
                                        // Show currency price (exchange_price) directly in price field
                                        displayPrice = basePrice; // basePrice is already exchange_price
                                        displayDiscount = baseDiscount; // baseDiscount is already exchange_discount
                                        
                                        // Display currency price (exchange_price) below price field
                                        var currencyPriceDisplay = $(el.parent().parent().parent().find('.currency-price-display'));
                                        var currencyPriceValue = $(el.parent().parent().parent().find('.currency-price-value'));
                                        var currencyPriceLabel = $(el.parent().parent().parent().find('.currency-price-label'));
                                        // Use exchange_price from database, or basePrice if exchange_price is not available
                                        var exchangePrice = (proposalItems.exchange_price != null && proposalItems.exchange_price > 0) 
                                            ? parseFloat(proposalItems.exchange_price) 
                                            : (parseFloat(basePrice) || 0);
                                        currencyPriceValue.text(exchangePrice.toFixed(2));
                                        currencyPriceValue.attr('data-exchange-price', exchangePrice.toFixed(2));
                                        currencyPriceLabel.text($('.currency-symbol').first().text().trim() || 'Currency');
                                        currencyPriceDisplay.show();
                                    } else {
                                        // Show AED price
                                        displayPrice = parseFloat(proposalItems.price) || 0; // AED price from database
                                        displayDiscount = parseFloat(proposalItems.discount) || 0; // AED discount from database
                                        $(el.parent().parent().parent().find('.currency-price-display')).hide();
                                    }
                                    
                                    // Ensure displayPrice and displayDiscount are numbers
                                    displayPrice = parseFloat(displayPrice) || 0;
                                    displayDiscount = parseFloat(displayDiscount) || 0;
                                    
                                    $(el.parent().parent().parent().find('.price')).val(displayPrice.toFixed(2));
                                    $(el.parent().parent().parent().find('.discount')).val(displayDiscount.toFixed(2));
                                    $(el.parent().parent().parent().find('.pro_description')).val(proposalItems.description);
                                } else {
                                    basePrice = parseFloat(item.product.sale_price) || 0;
                                    baseDiscount = 0;
                                    // Apply current exchange rate to price
                                    var displayPrice = basePrice * currentExchangeRate;
                                    $(el.parent().parent().parent().find('.quantity')).val(1);
                                    $(el.parent().parent().parent().find('.price')).val(displayPrice.toFixed(2));
                                    $(el.parent().parent().parent().find('.discount')).val(0);
                                    $(el.parent().parent().parent().find('.pro_description')).val(item.product.description);
                                    
                                    // Display currency price if currency is selected
                                    var currencyId = $('#currency_id').val();
                                    if (currencyId && currencyId !== '') {
                                        var currencyPriceDisplay = $(el.parent().parent().parent().find('.currency-price-display'));
                                        var currencyPriceValue = $(el.parent().parent().parent().find('.currency-price-value'));
                                        var currencyPriceLabel = $(el.parent().parent().parent().find('.currency-price-label'));
                                        currencyPriceValue.text(basePrice.toFixed(2));
                                        currencyPriceValue.attr('data-exchange-price', basePrice.toFixed(2));
                                        currencyPriceLabel.text($('.currency-symbol').first().text().trim() || 'Currency');
                                        currencyPriceDisplay.show();
                                    } else {
                                        $(el.parent().parent().parent().find('.currency-price-display')).hide();
                                    }
                                }

                                // Tax is now calculated at proposal level, not item level
                                $(el.parent().parent().parent().find('.itemTaxPrice')).val(0);
                                $(el.parent().parent().parent().find('.itemTaxRate')).val(0);
                                $(el.parent().parent().parent().find('.taxes')).html('');
                                $(el.parent().parent().parent().find('.tax')).val('');
                                $(el.parent().parent().parent().find('.unit')).html(item.unit);

                                var discount = parseFloat($(el.parent().parent().parent().find('.discount')).val()) || 0;
                                var quantity = parseFloat($(el.parent().parent().parent().find('.quantity')).val()) || 1;
                                var price = parseFloat($(el.parent().parent().parent().find('.price')).val()) || 0;
                                
                                // Convert to AED for calculations if currency is selected
                                var currencyId = $('#currency_id').val();
                                var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                                var actualPrice = price;
                                var actualDiscount = discount;
                                
                                if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                    actualPrice = price * currentExchangeRate;
                                    actualDiscount = discount * currentExchangeRate;
                                }

                                var amount = (actualPrice * quantity) - actualDiscount;
                                $(el.parent().parent().parent().find('.amount')).html(amount.toFixed(2));

                                // Recalculate totals
                                var inputs = $(".amount");
                                var subTotal = 0;
                                for (var i = 0; i < inputs.length; i++) {
                                    subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                                }

                                var totalItemPrice = 0;
                                var inputs_quantity = $(".quantity");
                                var priceInput = $('.price');
                                var currencyId = $('#currency_id').val();
                                var currentExchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                                
                                for (var j = 0; j < priceInput.length; j++) {
                                    var unitPrice = parseFloat(priceInput[j].value) || 0;
                                    var qty = parseFloat(inputs_quantity[j].value) || 0;
                                    
                                    if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                        unitPrice = unitPrice * currentExchangeRate;
                                    }
                                    
                                    totalItemPrice += unitPrice * qty;
                                }

                                var totalItemDiscountPrice = 0;
                                var itemDiscountPriceInput = $('.discount');
                                for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                                    var discountValue = parseFloat(itemDiscountPriceInput[k].value) || 0;
                                    var qty = parseFloat(inputs_quantity[k].value) || 0;
                                    
                                    if (currencyId && currencyId !== '' && currentExchangeRate > 0) {
                                        discountValue = discountValue * currentExchangeRate;
                                    }
                                    
                                    totalItemDiscountPrice += discountValue * qty;
                                }

                                updateTotalsInCurrency(totalItemPrice, totalItemDiscountPrice, 0);
                            }
                        });
                    },
                });
            };
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proposal.index') }}">{{ __('Proposal') }}</a></li>
    <li class="breadcrumb-item">{{ __('Proposal Edit') }}</li>
@endsection
@section('content')
    <div class="row">
        <form action="{{ route('proposal.update', $proposal->id) }}" method="POST" class="w-100">
            @csrf
            @method('PUT')
            <div class="col-12">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="customer-box">
                                    <label for="customer_id" class="form-label">{{ __('Customer') }}</label>
                                    <select name="customer_id" id="customer" class="form-control select" required>
                                        <option value="" selected disabled>{{ __('Select Customer') }}</option>
                                        @foreach ($customers as $id => $customer)
                                            <option value="{{ $id }}"
                                                {{ $proposal->customer_id == $id ? 'selected' : '' }}> {{ $customer }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>
                                <div id="customer_detail" class="d-none">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="issue_date" class="form-label">{{ __('Issue Date') }}</label>
                                            <div class="form-icon-user">
                                                <input type="date" name="issue_date" id="issue_date" class="form-control"
                                                    value="{{ $proposal->issue_date }}" required>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">{{ __('Category') }}</label>
                                        <select name="category_id" id="category_id" class="form-control select" required>
                                            @foreach ($category as $key => $value)
                                                <option value="{{ $key }}"
                                                    {{ $proposal->category_id == $key ? 'selected' : '' }}>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="proposal_number"
                                                class="form-label">{{ __('Proposal Number') }}</label>

                                            <div class="form-icon-user">
                                                <input type="text" class="form-control" value="{{ $proposal_number }}"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_account_id" class="form-label">{{ __('Bank') }}</label>
                                            <select name="bank_account_id"
                                                class="form-control select2"data-placeholder="{{ __('Select Bank') }}">
                                                <option value=""></option>
                                                @foreach ($accounts as $key => $value)
                                                    <option value="{{ $key }}"
                                                        {{ $proposal->bank_account_id == $key ? 'selected' : '' }}>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-check custom-checkbox mt-4"> --}}
                                    {{-- <input class="form-check-input" type="checkbox" name="discount_apply"
                                            id="discount_apply" {{$proposal->discount_apply==1?'checked':''}}> --}}
                                    {{-- <label class="form-check-label " for="discount_apply">{{__('Discount
                                            Apply')}}</label> --}}
                                    {{-- </div> --}}
                                    {{-- </div> --}}


                                    {{-- <div class="col-md-6"> --}}
                                    {{-- <div class="form-group"> --}}
                                    {{-- {{Form::label('sku',__('SKU')) }} --}}
                                    {{-- {!!Form::text('sku', null,array('class' =>
                                        'form-control','required'=>'required')) !!} --}}
                                    {{-- </div> --}}
                                    {{-- </div> --}}
                                    @if (!$customFields->isEmpty())
                                        <div class="col-md-6">
                                            <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                                @include('customFields.formBuilder')
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                            <select name="currency_id" id="currency_id" class="form-control select2">
                                                @foreach ($currency as $key => $value)
                                                    <option value="{{ $key }}"
                                                        {{ $proposal->currency_id == $key ? 'selected' : '' }}>
                                                        {{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="exchange_rate_div"
                                        style="display: {{ $proposal->currency_id ? 'block' : 'none' }};">
                                        <div class="form-group">
                                            <label for="exchange_rate" class="form-label">{{ __('Exchange Rate') }}</label>
                                            <div class="form-icon-user">
                                                <span><i class="ti ti-joint"></i></span>
                                                <input type="text" name="exchange_rate" id="exchange_rate"
                                                    class="form-control" value="{{ $proposal->exchange_rate }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="descriptionProposal"
                                                class="form-label">{{ __('Description') }}</label>
                                            <textarea name="descriptionProposal" id="descriptionProposal" class="form-control" rows="3"
                                                placeholder="{{ __('Enter proposal description') }}">{{ old('descriptionProposal', $proposal->descriptionProposal ?? ($proposal->description ?? '')) }}</textarea>
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
                <div class="card repeater" data-value='{!! json_encode($items) !!}'>
                    <div class="item-section py-2">
                        <div class="row justify-content-between align-items-center">
                            <div
                                class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box me-2">
                                    <a href="#" data-repeater-create="" class="btn btn-primary"
                                        data-bs-toggle="modal" data-target="#add-bank">
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
                                        <th>{{ __('Items') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Price') }} </th>
                                        <th>{{ __('Discount') }}</th>
                                        <th>{{ __('Tax') }} (%)</th>

                                        <th class="text-end">{{ __('Amount') }} <br><small
                                                class="text-danger font-weight-bold">{{ __('after tax & discount') }}</small>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable" data-repeater-item>
                                    <tr>
                                        <input type="hidden" name="id" value="" class="form-control id">
                                        <td width="25%" class="form-group">
                                            <select name="item" class="form-control select item"
                                                data-url="{{ route('proposal.product') }}">
                                                @foreach ($product_services as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="quantity" value=""
                                                    class="form-control quantity" required
                                                    placeholder="{{ __('Qty') }}">

                                                <span class="unit input-group-text bg-transparent"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="price" value=""
                                                    class="form-control price" required
                                                    placeholder="{{ __('Price') }}">
                                                <span
                                                    class="input-group-text bg-transparent currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                            <small class="text-muted currency-price-display" style="display: none;">
                                                <span class="currency-price-label"></span>: <span class="currency-price-value" data-exchange-price="0.00">0.00</span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="form-group price-input input-group search-form">
                                                <input type="text" name="discount" value=""
                                                    class="form-control discount" required
                                                    placeholder="{{ __('Discount') }}">

                                                <span
                                                    class="input-group-text bg-transparent currency-symbol">{{ \Auth::user()->currencySymbol() }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="taxes"></div>
                                                    <input type="hidden" name="tax" value=""
                                                        class="form-control tax">
                                                    <input type="hidden" name="itemTaxPrice" value=""
                                                        class="form-control itemTaxPrice">
                                                    <input type="hidden" name="itemTaxRate" value=""
                                                        class="form-control itemTaxRate">

                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-end amount">0.00</td>
                                        <td>
                                            @can('delete proposal product')
                                                <a href="#"
                                                    class="ti ti-trash text-white repeater-action-btn bg-danger ms-2"
                                                    data-repeater-delete></a>
                                            @endcan
                                        </td>
                                    </tr>
                                    <tr class="product-custom-fields-row" style="display:none;">
                                        <td colspan="7">
                                            <div class="product-custom-fields"></div>
                                        </td>
                                    </tr>
                                    @if (isset($proposalItemCustomFields) && !$proposalItemCustomFields->isEmpty())
                                        <tr class="proposal-item-custom-fields-row">
                                            <td colspan="7">
                                                <div class="mt-1">
                                                    @include('proposal.partials.proposalItemCustomFieldsInputs', [
                                                        'fields' => $proposalItemCustomFields,
                                                        'inputNamePrefix' => 'proposal_item_custom_fields_',
                                                    ])
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2">
                                            <div class="form-group">
                                                <textarea name="description" class="form-control pro_description" rows="2" placeholder="Description"></textarea>

                                            </div>
                                        </td>
                                        <td colspan="5"></td>
                                    </tr>

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>
                                            <div class="form-group col-md-10">
                                                <label for="tax_id" class="form-label">{{ __('Tax') }}</label>
                                                <select name="tax_id[]" id="choices-multiple1"
                                                    class="form-control select2 custom-select" multiple>
                                                    @foreach ($fullTax as $value)
                                                        <option value="{{ $value->id }}"
                                                            data-rate="{{ $value->rate }}"
                                                            @if ($proposal->tax_id == $value->id) selected @endif>
                                                            {{ $value->name }}
                                                        </option>
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
                                        <td></td>
                                        <td><strong>{{ __('Sub Total') }} <span class="currency-symbol-label">{{ \Auth::user()->currencySymbol() }}</span></strong>
                                        </td>
                                        <td class="text-end subTotal">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Discount') }} <span class="currency-symbol-label">{{ \Auth::user()->currencySymbol() }}</span></strong>
                                        </td>
                                        <td class="text-end totalDiscount">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td><strong>{{ __('Tax') }} <span class="currency-symbol-label">{{ \Auth::user()->currencySymbol() }}</span></strong>
                                        </td>
                                        <td class="text-end totalTax">0.00</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="blue-text border-none"><strong>{{ __('Total Amount') }}
                                                <span class="currency-symbol-label">{{ \Auth::user()->currencySymbol() }}</span></strong></td>
                                        <td class="text-end totalAmount blue-text border-none">0.00</td>
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
                    onclick="location.href = '{{ route('proposal.index') }}';" class="btn btn-light">
                <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
            </div>
        </form>
    </div>
@endsection
