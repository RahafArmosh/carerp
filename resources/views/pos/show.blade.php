@php
    $logo=\App\Models\Utility::get_file('uploads/logo');
    $company_logo=Utility::getValByName('company_logo');
@endphp

@if (!empty($sales) && count($sales) > 0)
    {{-- Hidden fields for form submission --}}
    <input type="hidden" id="customer_id" value="{{ request('customer_id') ?? request('vc_name') }}">
    <input type="hidden" id="vc_name_hidden" value="{{ request('customer_id') ?? request('vc_name') }}">
    <input type="hidden" id="warehouse_name_hidden" value="{{ request('warehouse_name') }}">
    <input type="hidden" id="discount_hidden" value="{{ request('discount', 0) }}">
    <input type="hidden" id="tax_hidden" value="{{ request('tax_id') ?? session('tax_id') }}">
    <input type="hidden" id="user_id_hidden" value="{{ request('user_id') ?? session('pos_user_id') ?? Auth::user()->id }}">
    <div class="card">
        <div class="card-body">
            <div class="row mt-2">
                <div class="col-6">
                    <img src="{{$logo.'/'.(isset($company_logo) && !empty($company_logo)?$company_logo:'logo-dark.png')}}" width="120px;">
                </div>
{{--                <div class="col-6 text-end">--}}
{{--                    <a href="#" class="btn btn-sm btn-primary" onclick="saveAsPDF()"><span class="ti ti-download"></span></a>--}}
{{--                </div>--}}
            </div>
            <div id="printableArea">
                <div class="row mt-3">
                    <div class="col-6">
                        <h1 class="invoice-id h6">{{ $details['pos_id'] }}</h1>
                        <div class="date"><b>{{ __('Date') }}: </b>{{ $details['date'] }}</div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-dark "><b>{{ __('Warehouse Name') }}: </b>
                            {!! $details['warehouse']['details'] !!}
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col contacts d-flex justify-content-between pb-4">
                        <div class="invoice-to">
                            <div class="text-dark h6"><b>{{ __('Billed To :') }}</b></div>
                            {!! $details['customer']['details'] !!}
                        </div>

                        @if(!empty( $details['customer']['shippdetails']))
                            <div class="invoice-to">
                                <div class="text-dark h6"><b>{{ __('Shipped To :') }}</b></div>
                                {!! $details['customer']['shippdetails'] !!}
                            </div>
                        @endif

                        <div class="company-details">
                            <div class="text-dark h6"><b>{{ __('From:') }}</b></div>
                            {!! $details['user']['details'] !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <table class="table">
                        <thead>
                        <tr>
                            <th class="text-left">{{ __('Items') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th class="text-right">{{ __('Price') }}</th>
                            <th> {{ __('Discount %') }}</th>
                            <th> {{ __('Combo') }}</th>
                            <th></th>
                            {{-- <th class="text-right">{{ __('Tax') }}</th>
                            <th class="text-right">{{ __('Tax Amount') }}</th> --}}
                            <th class="text-right">{{ __('Total') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @php
                                $taxPrice = 0;
                                $taxname ='';
                            @endphp
                        @foreach ($sales['data'] as $key => $value)
                            {{-- @dd($value)--}}
                            @php
                                    $taxRecord = null;
                                    if ($tax_id != null) {
                                        $taxRecord = App\Models\Tax::where('id', $tax_id)->first();
                                    }
                                    $taxPrice = $taxRecord ? $taxRecord->rate : 0;
                                    $taxname = $taxRecord ? $taxRecord->name : '';
                            @endphp
                            <tr>
                                <td class="cart-summary-table text-left">
                                    {{ $value['name'] }}
                                </td>
                                <td class="cart-summary-table">
                                    {{ $value['quantity'] }}
                                </td>
                                <td class="text-right cart-summary-table">
                                    {{ $value['price'] }}
                                </td>
                                
                            <td class="text-left cart-summary-table">
                                    {{ $value['discount'] }}
                            </td>
                            <td>
                                @if ($value['compo_id'] == 0)
                                    no combo
                                @else
                                    {{$value['compo_text']}}
                                @endif
                            </td>
                            <td></td>
                                <td class="text-right cart-summary-table">
                                    {{$value['subtotal']}}
                                </td>
                            </tr>
                        @endforeach
                        
                        </tbody>
                        
                        <tfoot>
                        <tr>
                            <td class="">{{ __('Sub Total') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ $sales['sub_total'] }}</td>
                        </tr>
                        
                        @if(!empty($vouchers))
                        <tr>
                            <th class="text-left">{{ __('Voucher ID') }}</th>
                            <th></th>
                            <th class="text-right"></th>
                            <th></th>
                            <th>{{ __('Validity') }}</th>
                            <th></th>
                            {{-- <th class="text-right">{{ __('Tax') }}</th>
                            <th class="text-right">{{ __('Tax Amount') }}</th> --}}
                            <th class="text-right">{{ __('Amount') }}</th>
                        </tr>
                        
                            @foreach ($vouchers as $key => $value)
                                @php
                                    $voucherDetails = isset($vouchersWithDetails) && isset($vouchersWithDetails[$key]) 
                                        ? $vouchersWithDetails[$key] 
                                        : null;
                                    $validUntil = $voucherDetails && isset($voucherDetails['valid_until']) && $voucherDetails['valid_until'] 
                                        ? \Carbon\Carbon::parse($voucherDetails['valid_until']) 
                                        : null;
                                    $isExpired = $validUntil ? $validUntil->isPast() : false;
                                    $isExpiringSoon = $validUntil && $validUntil->isFuture() && $validUntil->diffInDays(now()) <= 7;
                                @endphp
                                    <tr>
                                        <td class="">{{ $key }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            @if($validUntil)
                                                <span class="badge {{ $isExpired ? 'bg-danger' : ($isExpiringSoon ? 'bg-warning' : 'bg-success') }}" 
                                                      title="{{ $isExpired ? __('Expired') : ($isExpiringSoon ? __('Expiring Soon') : __('Valid')) }}">
                                                    {{ \Auth::user()->dateFormat($validUntil) }}
                                                </span>
                                                @if($isExpired)
                                                    <small class="text-danger d-block">{{ __('Expired') }}</small>
                                                @elseif($isExpiringSoon)
                                                    <small class="text-warning d-block">{{ __('Expiring Soon') }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td></td>
                                        <td class="text-right">{{ \Auth::user()->priceFormat($value['amount']) }}</td>
                                    </tr>
                            @endforeach
                        @endif
                        @php
                            $total_vouchers = 0.0;
                        @endphp
                        
                        @if(!empty($vouchers))
                            @foreach ($vouchers as $key => $value)
                               @php
                                   $total_vouchers += $value['amount'];
                               @endphp
                            @endforeach
                        @endif
                        
                        <tr>
                            <td class="">{{ __('New Sub Total') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ $sales['sub_total_number']}}</td>
                        </tr>

                        <tr>
                            <td class="">{{ __('Discount') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ $sales['discount'] }}</td>
                        </tr>
                        <tr>
                            <td class="">{{ __('Tax') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ $sales['tax_amount'] ?? '0.00' }}</td>
                        </tr>
                        <tr class="pos-header">
                            <td class="">{{ __('Total') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td
                                id="pay_full_amount"
                                class="text-right"
                                data-total="{{ isset($sales['total_number']) ? number_format((float) $sales['total_number'], 2, '.', '') : number_format(0, 2, '.', '') }}">
                                {{ $sales['total'] }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <br>

            @if ($details['pay'] == 'show')
                {{-- <a href="#" class="btn btn-success btn-done-payment rounded mt-2 float-right"--}}
                {{-- data-url="{{ route('pos.data.store') }}">{{ __('Cash Payment') }}</a>--}}
                {{-- <button class="btn btn-info">
                </button> --}}
                @if($payment_methods && count($payment_methods) > 0)
                    @foreach ($payment_methods as $item)
                        <div class="mb-3">
                            <button type="button"
                                    class="btn btn-outline-primary toggle-amount-field"
                                    data-id="{{ $item->id }}">
                                {{ $item->name }}
                            </button>

                            <div class="mt-2 d-none amount-input" id="amount-field-{{ $item->id }}">
                                <label for="amount_{{ $item->id }}" class="form-label">{{ __('Amount for') }} {{ $item->name }}</label>
                                <input
                                    type="number"
                                    name="amount[{{ $item->id }}]"
                                    id="amount_{{ $item->id }}"
                                    class="form-control AP"
                                    data-method-id="{{ $item->id }}"
                                    placeholder="{{ __('Enter amount') }}"
                                    step="0.01">
                            </div>

                        </div>
                    @endforeach

                    <!-- Return Amount Display -->
                    <div id="return-amount-display" class="alert alert-info d-none mt-2" style="display: none;">
                        <strong>{{ __('Return Amount') }}:</strong> <span id="return-amount-value">0.00</span>
                    </div>
                    
                    <button class="btn btn-success payment-done-btn d-none rounded mt-2 float-right" data-url="{{ route('pos.temp-printview') }}" data-ajax-popup="true" data-size="sm"
                            data-bs-toggle="tooltip" data-title="{{ __('POS Invoice') }}">
                        {{ __('Pay') }}
                    </button>
                @else
                    <div class="alert alert-warning">
                        <h5>{{ __('No Payment Methods Available') }}</h5>
                        <p>{{ __('No payment methods have been configured for this warehouse. Please contact your administrator to set up payment methods.') }}</p>
                        <a href="#" class="btn btn-primary" onclick="alert('{{ __('Please contact your administrator to configure payment methods for this warehouse.') }}')">
                            {{ __('Configure Payment Methods') }}
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </div>

@endif

<!-- Print Container for Receipt -->
<div id="printContainer" style="display: none;"></div>


<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>

<script>
    // Global state of payment amounts per method ID
    var paymentAmountsState = {};

    function checkTotalAndTogglePayButton() {
        let totalAmount = 0;

        $('.AP').each(function () {
            let $input = $(this);
            let val = $input.val();
            
            // Remove any non-numeric characters except decimal point
            val = val.toString().replace(/[^0-9.]/g, '');
            
            // Parse as float
            let numVal = parseFloat(val);
            if (!isNaN(numVal) && numVal > 0) {
                totalAmount += numVal;
            }
        });

        // Get total amount - prefer data attribute over parsed text (keep decimals)
        let fullAmount = parseFloat($('#pay_full_amount').data('total')) || parseFloat($('#pay_full_amount').text().replace(/[^0-9.]/g, ''));
        // Normalize to 2 decimal places without rounding to whole number
        if (!isNaN(fullAmount)) {
            fullAmount = parseFloat(fullAmount.toFixed(2));
        }

        console.log('Entered Total:', totalAmount);
        console.log('Required Total:', fullAmount);

        // Calculate return amount if payment is more than required
        let returnAmount = 0;
        if (totalAmount > fullAmount + 0.01) { // More than required (with tolerance)
            returnAmount = totalAmount - fullAmount;
        }

        // Show/hide return amount display
        let $returnDisplay = $('#return-amount-display');
        let $returnValue = $('#return-amount-value');
        
        if (returnAmount > 0) {
            // Format return amount with currency symbol
            let formattedReturn = parseFloat(returnAmount).toFixed(2);
            $returnValue.text(formattedReturn);
            $returnDisplay.removeClass('d-none').show();
            console.log('Return amount:', returnAmount);
        } else {
            $returnDisplay.addClass('d-none').hide();
        }

        // Use a small tolerance for floating point comparison
        if (totalAmount >= fullAmount - 0.01) {
            $('.payment-done-btn').removeClass('d-none');
            console.log('Pay button shown');
        } else {
            $('.payment-done-btn').addClass('d-none');
            console.log('Pay button hidden');
        }
    }

     // Listen to multiple events to catch all input changes
     $(document).on('change input keyup blur','.AP',function(e){
        console.log('AP input event:', e.type, 'Value:', $(this).val());

        var $input = $(this);
        var name = $input.attr('name');
        var methodId = $input.data('method-id');
        if (!methodId && name) {
            var match = name.match(/amount[s]?\[(\d+)\]/);
            methodId = match ? match[1] : null;
        }

        if (methodId) {
            var rawVal = $input.val() || '';
            rawVal = rawVal.toString().replace(/[^0-9.]/g, '');
            var numVal = parseFloat(rawVal);
            if (!isNaN(numVal) && numVal > 0) {
                paymentAmountsState[methodId] = numVal.toFixed(2);
            } else {
                delete paymentAmountsState[methodId];
            }
        }

        checkTotalAndTogglePayButton();
    });
    
    // Also trigger check when Enter key is pressed
    $(document).on('keypress', '.AP', function(e) {
        if (e.which === 13 || e.keyCode === 13) { // Enter key
            e.preventDefault();
            $(this).trigger('change');
            checkTotalAndTogglePayButton();
        }
    }); 
</script>
<script>

    var filename = $('#filename').val()

    function saveAsPDF() {
        var element = document.getElementById('printableArea');
        var opt = {
            margin: 0.3,
            filename: filename,
            image: {type: 'jpeg', quality: 1},
            html2canvas: {scale: 4, dpi: 72, letterRendering: true},
            jsPDF: {unit: 'in', format: 'A2'}
        };
        html2pdf().set(opt).from(element).save();
    }
    // $(document).on('change', )
    
   
    // Track if any payment method has been opened
    var firstPaymentMethodOpened = false;
    var isUserInteracting = false; // Flag to prevent reset during user interaction
    var resetTimeout = null; // Timeout for scheduled reset
    var resetScheduled = false; // Track if reset has been scheduled for this modal open
    var lastToggleTime = {}; // Track last toggle time per field to prevent double-clicks
    var toggleDebounceDelay = 300; // Minimum time between toggles (ms)
    
    // Function to reinitialize Select2 dropdowns (callable from parent page)
    function reinitializeSelect2Dropdowns() {
        // Try to call function from parent window (if modal is in iframe/popup)
        if (window.parent && window.parent !== window && typeof window.parent.reinitializeSelect2 === 'function') {
            try {
                window.parent.reinitializeSelect2();
                console.log('Called reinitializeSelect2 from parent window');
                return;
            } catch(e) {
                console.warn('Error calling parent reinitializeSelect2:', e);
            }
        }
        
        // Try to call function from current window
        if (typeof window.reinitializeSelect2 === 'function') {
            try {
                window.reinitializeSelect2();
                console.log('Called reinitializeSelect2 from current window');
                return;
            } catch(e) {
                console.warn('Error calling reinitializeSelect2:', e);
            }
        }
        
        // Fallback: Try to reinitialize warehouse select directly
        // Only if it's broken, not if it's working fine
        if (typeof $.fn.select2 !== 'undefined') {
            var $warehouse = $('#warehouse');
            if ($warehouse.length > 0) {
                try {
                    // Check if Select2 is already working
                    if ($warehouse.hasClass('select2-hidden-accessible')) {
                        var select2Data = $warehouse.data('select2');
                        if (select2Data && select2Data.$container && select2Data.$container.length > 0) {
                            // Check if it has options and is functional
                            if ($warehouse.find('option').length > 0 && select2Data.$container.find('.select2-selection').length > 0) {
                                console.log('Warehouse Select2 is already working, skipping fallback reinitialization');
                                return; // Already working, don't reinitialize
                            }
                        }
                    }
                    
                    // Only reinitialize if broken or not initialized
                    // Check if options exist first
                    if ($warehouse.find('option').length === 0) {
                        console.warn('Warehouse select has no options, cannot reinitialize');
                        return;
                    }
                    
                    // Destroy if already initialized
                    if ($warehouse.hasClass('select2-hidden-accessible')) {
                        $warehouse.select2('destroy');
                    }
                    // Reinitialize
                    $warehouse.select2({
                        theme: 'default',
                        width: '100%',
                        allowClear: false
                    });
                    console.log('Warehouse Select2 reinitialized (fallback)');
                } catch(e) {
                    console.warn('Error reinitializing warehouse Select2:', e);
                }
            }
        }
    }
    
    // Reset flags when modal closes (so they're ready for next open)
    $(document).on('hidden.bs.modal', '#commonModal', function() {
        isUserInteracting = false;
        firstPaymentMethodOpened = false;
        resetScheduled = false;
        // Clear user-opened data attributes
        $('.amount-input').removeData('user-opened');
        // Clear toggle timing
        lastToggleTime = {};
        // Clear any pending resets
        if (resetTimeout) {
            clearTimeout(resetTimeout);
            resetTimeout = null;
        }
        console.log('Modal closed - flags reset, all timeouts cleared, user-opened data cleared, toggle timing cleared');
        
        // Always check and fix Select2 after modal closes
        // Modal interactions might break Select2 event handlers
        setTimeout(function() {
            console.log('Checking Select2 after modal close...');
            reinitializeSelect2Dropdowns();
        }, 300);
    });
    
    // Function to reset payment methods state - FORCE reset when called
    function resetPaymentMethods() {
        // Don't reset if user is currently interacting (but allow if modal just opened)
        if (isUserInteracting) {
            console.log('Skipping reset - user is interacting');
            return;
        }
        
        // Don't reset if any fields are currently open (user might have opened them)
        // Check both class and visibility to be sure
        var openFields = $('.amount-input').filter(function() {
            var $field = $(this);
            var hasShowClass = $field.hasClass('show');
            var isVisible = $field.is(':visible');
            var displayStyle = $field.css('display') !== 'none';
            return (!$field.hasClass('d-none') || hasShowClass) && (isVisible || displayStyle);
        });
        
        if (openFields.length > 0) {
            console.log('Skipping reset -', openFields.length, 'fields are already open (user opened)');
            return;
        }
        
        console.log('Resetting payment methods...');
        
        // Reset flag and payment state
        firstPaymentMethodOpened = false;
        paymentAmountsState = {};
        
        // Force hide all payment method fields and remove any inline styles
        // BUT only if they're not explicitly opened by user (check for show class and data attribute)
        $('.amount-input').each(function() {
            var $field = $(this);
            var isUserOpened = $field.hasClass('show') || $field.data('user-opened') === true;
            // Only reset if field doesn't have 'show' class or user-opened data (user-opened fields have these)
            if (!isUserOpened) {
                // Remove all inline styles and force hide
                $field.removeData('user-opened')
                    .removeAttr('style')
                    .addClass('d-none')
                    .removeClass('show')
                    .css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
            } else {
                console.log('Skipping reset for field with show class/data (user-opened)');
            }
        });
        
        // Only clear values for fields that are being reset (not user-opened ones)
        $('.amount-input.d-none .AP, .amount-input:not(.show) .AP').val('');
        
        // Hide pay button only if no fields are open
        if (openFields.length === 0) {
            $('.payment-done-btn').addClass('d-none');
        }
        
        // Ensure all toggle buttons are enabled and clickable
        $('.toggle-amount-field').each(function() {
            var $btn = $(this);
            $btn.prop('disabled', false)
                .removeClass('disabled')
                .removeAttr('disabled')
                .css({
                    'pointer-events': 'auto',
                    'cursor': 'pointer',
                    'opacity': '1'
                });
        });
        
        console.log('Payment methods reset -', $('.toggle-amount-field').length, 'buttons enabled');
    }
    
    // CRITICAL: Reset when content is loaded fresh via AJAX
    // Since content loads fresh via AJAX each time, always reset when content loads
    function scheduleReset() {
        // Don't schedule if already scheduled or if user is interacting
        if (resetScheduled || isUserInteracting) {
            console.log('Skipping scheduleReset - already scheduled or user interacting');
            return;
        }
        
        // Clear any pending reset
        if (resetTimeout) {
            clearTimeout(resetTimeout);
        }
        
        resetScheduled = true;
        
        // Schedule reset with a delay to ensure DOM is ready
        resetTimeout = setTimeout(function() {
            // Check if payment methods exist in the current modal content
            var $modal = $('#commonModal');
            if ($modal.length > 0 && $modal.find('.toggle-amount-field').length > 0) {
                // Only reset if no fields are open and user is not interacting
                if (!isUserInteracting) {
                    resetPaymentMethods();
                }
            }
            resetScheduled = false; // Allow scheduling again after reset completes
        }, 200); // Increased delay to give more time for DOM
    }
    
    // Reset when script loads (content is loaded fresh via AJAX)
    // Don't schedule immediately - wait for modal to be shown
    // scheduleReset(); // Commented out - will be called from shown.bs.modal
    
    // Reset when modal starts to show (Bootstrap 4/5) - reset flags early
    $(document).on('show.bs.modal', '#commonModal', function() {
        // Reset flags immediately when modal starts opening
        isUserInteracting = false;
        firstPaymentMethodOpened = false;
        resetScheduled = false; // Reset the scheduled flag
        // Clear any pending resets
        if (resetTimeout) {
            clearTimeout(resetTimeout);
            resetTimeout = null;
        }
        console.log('Modal opening - flags reset, pending resets cleared');
    });
    
    // Reset when modal is shown (Bootstrap 4/5) - this fires AFTER content is loaded
    $(document).on('shown.bs.modal', '#commonModal', function() {
        var $modal = $(this);
        // Check if this modal contains payment methods
        if ($modal.find('.toggle-amount-field').length > 0) {
            // Reset flags first (in case they weren't reset in show.bs.modal)
            isUserInteracting = false;
            firstPaymentMethodOpened = false;
            resetScheduled = false;
            // Then schedule reset (only once)
            scheduleReset();
        }
    });
    
    // Reset when modal content is loaded via AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if this is the pos.create route
        if (settings.url && (settings.url.indexOf('pos.create') !== -1 || settings.url.indexOf('pos/create') !== -1)) {
            // Reset flags first
            isUserInteracting = false;
            firstPaymentMethodOpened = false;
            // Don't schedule reset here - let shown.bs.modal handle it
            // This prevents multiple resets
        }
    });
    
    // Ensure buttons are always clickable - use event delegation on document
    $(document).on('click', '.toggle-amount-field', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation(); // Prevent other handlers from firing
        
        var $button = $(this);
        var paymentMethodId = $button.data('id');
        
        if (!paymentMethodId) {
            console.error('Payment method ID not found');
            return false;
        }
        
        // Debounce: Prevent rapid double-clicks
        var now = Date.now();
        var lastTime = lastToggleTime[paymentMethodId] || 0;
        if (now - lastTime < toggleDebounceDelay) {
            console.log('Toggle debounced for ID:', paymentMethodId, '- Time since last:', now - lastTime, 'ms');
            return false;
        }
        lastToggleTime[paymentMethodId] = now;
        
        // CRITICAL: Cancel any pending reset timeouts
        if (resetTimeout) {
            clearTimeout(resetTimeout);
            resetTimeout = null;
            console.log('Cancelled pending reset timeout');
        }
        
        // Set interaction flag to prevent reset from interfering
        isUserInteracting = true;
        
        // Ensure button is enabled
        $button.prop('disabled', false).removeClass('disabled');
        
        var amountField = $('#amount-field-' + paymentMethodId);
        if (amountField.length === 0) {
            console.error('Amount field not found for ID:', paymentMethodId);
            isUserInteracting = false;
            return false;
        }
        
        // Check if field is currently hidden (more reliable check)
        // Also check data attribute to see if it was user-opened
        var isUserOpened = amountField.data('user-opened') === true;
        var isCurrentlyVisible = amountField.is(':visible') && 
                                 amountField.css('display') !== 'none' &&
                                 !amountField.hasClass('d-none');
        var isCurrentlyHidden = !isCurrentlyVisible || (!isUserOpened && !amountField.hasClass('show'));
        
        // If field is user-opened and visible, user wants to close it
        // Otherwise, if hidden, user wants to open it
        if (isCurrentlyHidden || !isUserOpened) {
            // CRITICAL: Cancel any scheduled resets before opening
            resetScheduled = false;
            if (resetTimeout) {
                clearTimeout(resetTimeout);
                resetTimeout = null;
            }
            
            // Field is being opened - remove all hiding classes and styles
            // Add 'show' class FIRST to mark it as user-opened (prevents reset from closing it)
            // Use data attribute as additional protection
            amountField.data('user-opened', true)
                .addClass('show')
                .removeClass('d-none')
                .removeAttr('style')
                .css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            
            // Also ensure parent containers don't hide it
            amountField.parent().css({
                'display': 'block',
                'visibility': 'visible'
            });
            
            // Field is being opened
            var amountInput = amountField.find('input');
            
            // If this is the first payment method being opened, auto-fill with total (without rounding to whole number)
            if (!firstPaymentMethodOpened) {
                var totalAmount = parseFloat($('#pay_full_amount').data('total')) || parseFloat($('#pay_full_amount').text().replace(/[^0-9.]/g, ''));
                // Normalize to 2 decimal places
                if (!isNaN(totalAmount)) {
                    totalAmount = parseFloat(totalAmount.toFixed(2));
                }
                if (!isNaN(totalAmount) && totalAmount > 0) {
                    amountInput.val(totalAmount.toFixed(2));
                    firstPaymentMethodOpened = true;
                    console.log('Auto-filled first payment method with total:', totalAmount);
                }
            } else {
                // Subsequent payment methods - set to empty
                amountInput.val('');
            }
            
            // Trigger multiple events to ensure the check function runs
            amountInput.trigger('input').trigger('change').trigger('keyup');
            
            // Also manually trigger the check function
            setTimeout(function() {
                checkTotalAndTogglePayButton();
            }, 100);
            
            // Keep interaction flag true to prevent any resets
            isUserInteracting = true;
            
            console.log('Payment method field opened for ID:', paymentMethodId, '- Field visible:', amountField.is(':visible'), '- Show class:', amountField.hasClass('show'), '- User opened:', amountField.data('user-opened'));
            
            // Prevent immediate closing - add a small delay before allowing close
            setTimeout(function() {
                // Field is now safely open, allow future toggles
            }, toggleDebounceDelay);
        } else {
            // Field is being closed by user - explicitly hide it
            // Only allow closing if field was user-opened AND it's been open for a bit (prevent accidental closes)
            if (isUserOpened && isCurrentlyVisible) {
                // Double-check it's still visible and user-opened before closing
                if (amountField.data('user-opened') === true && amountField.is(':visible')) {
                    amountField.removeData('user-opened')
                        .removeClass('show')
                        .removeAttr('style')
                        .addClass('d-none')
                        .css({
                            'display': 'none',
                            'visibility': 'hidden',
                            'opacity': '0'
                        });
                    // Clear the value
                    amountField.find('input').val('');
                    console.log('Payment method field closed for ID:', paymentMethodId, '(user closed)');
                    
                    // Check if any fields are still open
                    var stillOpen = $('.amount-input.show').filter(function() {
                        return $(this).is(':visible') && $(this).data('user-opened') === true;
                    }).length > 0;
                    
                    // Only clear interaction flag if no fields are open
                    if (!stillOpen) {
                        setTimeout(function() {
                            isUserInteracting = false;
                            console.log('User interaction flag cleared - all fields closed');
                        }, 500);
                    }
                } else {
                    console.log('Prevented closing - field state changed:', paymentMethodId);
                }
            } else {
                // Field is not user-opened or not visible, don't allow closing (prevent accidental closes)
                console.log('Prevented closing field - not user-opened or not visible:', paymentMethodId, '- isUserOpened:', isUserOpened, '- isCurrentlyVisible:', isCurrentlyVisible);
                // Re-open it to be safe if it should be open
                if (!isCurrentlyVisible) {
                    amountField.data('user-opened', true)
                        .addClass('show')
                        .removeClass('d-none')
                        .css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                }
            }
        }
        
        return false; // Prevent any further event propagation
        
        checkTotalAndTogglePayButton();
        
        // Keep interaction flag true as long as any field with 'show' class or user-opened data is open
        // This prevents resets from closing user-opened fields
        var openFields = $('.amount-input').filter(function() {
            var $field = $(this);
            return ($field.hasClass('show') || $field.data('user-opened') === true) && $field.is(':visible');
        });
        
        if (openFields.length > 0) {
            // Keep interaction flag true while fields are open
            isUserInteracting = true;
            console.log('Keeping interaction flag true -', openFields.length, 'fields with show class/data are open');
        }
    });  


    // Flag to prevent multiple submissions
    var isProcessingPayment = false;
    
    $(document).on('click', '.payment-done-btn', function (e) {
        e.preventDefault();
        
        // Prevent multiple clicks while processing
        if (isProcessingPayment) {
            console.log('Payment already processing, ignoring click');
            return false;
        }
        
        var ele = $(this);
        
        // Build payment amounts from global state (kept in sync with inputs)
        let paymentAmounts = Object.assign({}, paymentAmountsState);
        let pay_total_customer = 0.0;
        Object.keys(paymentAmounts).forEach(function (k) {
            let v = parseFloat(paymentAmounts[k]) || 0;
            if (v > 0) {
                pay_total_customer += v;
            } else {
                delete paymentAmounts[k];
            }
        });
        
        // Get the POS total amount - prefer data attribute over parsed text (keep decimals)
        let posTotal = parseFloat($('#pay_full_amount').data('total')) || parseFloat($('#pay_full_amount').text().replace(/[^0-9.]/g, '')) || 0;
        // Normalize to 2 decimal places without rounding to whole number
        if (!isNaN(posTotal)) {
            posTotal = parseFloat(posTotal.toFixed(2));
        }
        
        // Validate: payment total must be >= POS total
        if (pay_total_customer < posTotal - 0.01) { // Allow small floating point tolerance
            let shortfall = posTotal - pay_total_customer;
            show_toastr('error', '{{ __("Payment amount is insufficient") }}. {{ __("Total required") }}: ' + posTotal.toFixed(2) + ', {{ __("Total entered") }}: ' + pay_total_customer.toFixed(2) + '. {{ __("Shortfall") }}: ' + shortfall.toFixed(2), 'error');
            console.error('Payment validation failed:', {
                posTotal: posTotal,
                payTotal: pay_total_customer,
                shortfall: shortfall
            });
            // Re-enable the button for retry
            ele.prop('disabled', false);
            return false; // Prevent form submission
        }
        
        // Calculate and show return amount ONLY when payment is truly more than POS total
        // If posTotal could not be read (0 or NaN), treat it as equal to what the customer pays
        if (!posTotal || isNaN(posTotal) || posTotal <= 0) {
            posTotal = pay_total_customer;
        }

        let returnAmount = 0;
        const diff = pay_total_customer - posTotal;
        if (diff > 0.01) { // More than required (with tolerance)
            returnAmount = diff;
            // Show confirmation message with return amount
            let confirmMessage = '{{ __("Payment amount exceeds total") }}. {{ __("Return amount") }}: ' + returnAmount.toFixed(2) + '. {{ __("Do you want to proceed?") }}';
            if (!confirm(confirmMessage)) {
                ele.prop('disabled', false);
                return false; // User cancelled
            }
            // Show info toast with return amount
            show_toastr('info', '{{ __("Return amount to customer") }}: ' + returnAmount.toFixed(2), 'info');
        }
        
        var printUrl = "{{ route('pos.printview') }}";
        console.log('Payment validation passed:', {
            posTotal: posTotal,
            payTotal: pay_total_customer,
            returnAmount: returnAmount
        });
        
        let formData = {
            customer_id: $('#customer_id').val() || $('#vc_name_hidden').val(),
            vc_name: $('#vc_name_hidden').val(), // Keep for backward compatibility
            warehouse_name: $('#warehouse_name_hidden').val(),
            discount: $('#discount_hidden').val(),
            payments_total: parseFloat(pay_total_customer),
        };

        
        console.log(formData);
        
        // Set processing flag
        isProcessingPayment = true;
        ele.prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Processing...") }}');
        
        $.ajax({
            url: "{{ route('pos.data.store') }}",
            method: 'GET',
            data: {
                customer_id: $('#customer_id').val() || $('#vc_name_hidden').val(), // Send customer_id
                vc_name: $('#vc_name_hidden').val(), // Keep for backward compatibility
                warehouse_name: $('#warehouse_name_hidden').val(),
                discount : $('#discount_hidden').val(),
                tax_id: $('#tax_hidden').val(),
                user_id: $('#user_id').val() || $('#user_id_hidden').val(), // Cashier/User who made this POS transaction
                date: new Date().toISOString().split('T')[0], // Add date field
                payments: paymentAmounts,
                payments_json: JSON.stringify(paymentAmounts),
            },
            beforeSend: function () {
                // Button already disabled above
            },
            success: function (data) {
                console.log(data);
                // return false;
                if (data.code == 200) {
                    show_toastr('success', data.success, 'success');
                    
                    // CRITICAL FIX: Add the saved POS ID to formData for printview
                    // Use pos_id_numeric if available (from database), otherwise use formatted pos_id
                    if (data.pos_id_numeric) {
                        formData.pos_id = data.pos_id_numeric;
                    } else if (data.pos_id) {
                        // Extract numeric part from formatted POS ID if only formatted is available
                        formData.pos_id = data.pos_id.replace(/[^0-9]/g, '');
                    } else {
                        // Fallback: use the preview POS ID from details
                        formData.pos_id = '{{ $details["pos_id"] }}'.replace(/[^0-9]/g, '');
                    }
                    
                    console.log('Passing POS ID to printview:', formData.pos_id);
                    
                    // First, load and show the receipt
                    $.ajax({
                        url: printUrl,
                        method: 'GET', 
                        data: formData,
                        success: function (printData) {
                            // Show print container and display receipt
                            $("#printContainer").html(printData).show();
                            
                            // Scroll to receipt
                            $('html, body').animate({
                                scrollTop: $("#printContainer").offset().top
                            }, 500);
                            
                            // Auto-print the receipt after a short delay
                            setTimeout(function() {
                                window.print();
                            }, 500);
                            
                            // Then empty cart after receipt is shown
                            var session_key = 'pos';
                            $.ajax({
                                url: '{{ url("empty-cart") }}',
                                method: 'POST',
                                data: {
                                    session_key: session_key,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(emptyData) {
                                    console.log('Cart emptied successfully');
                                    // Reload parent page cart if in modal/popup, otherwise reload current page
                                    // Delay reload to allow receipt to be printed
                                    setTimeout(function() {
                                        if (window.opener || window.parent !== window) {
                                            // If opened in popup/modal, reload parent
                                            if (window.opener) {
                                                window.opener.location.reload();
                                            } else if (window.parent) {
                                                window.parent.location.reload();
                                            }
                                    } else {
                                        // Reload cart HTML to show empty state
                                        $('#carthtml').load(document.URL + ' #carthtml', function() {
                                            // Reinitialize Select2 dropdowns after cart reload
                                            // Delay to ensure DOM is ready
                                            setTimeout(function() {
                                                reinitializeSelect2Dropdowns();
                                            }, 300);
                                        });
                                    }
                                    }, 2000); // Wait 2 seconds for printing
                                },
                                error: function(err) {
                                    console.error('Error emptying cart:', err);
                                    // Still reload even if empty fails
                                    setTimeout(function() {
                                        if (window.opener || window.parent !== window) {
                                            if (window.opener) {
                                                window.opener.location.reload();
                                            } else if (window.parent) {
                                                window.parent.location.reload();
                                            }
                                        } else {
                                            $('#carthtml').load(document.URL + ' #carthtml', function() {
                                                // Reinitialize Select2 dropdowns after cart reload
                                                // Delay to ensure DOM is ready
                                                setTimeout(function() {
                                                    reinitializeSelect2Dropdowns();
                                                }, 300);
                                            });
                                        }
                                    }, 2000);
                                }
                            });
                        },
                        error: function (err) {
                            console.error('Print Error:', err);
                            show_toastr('Error', 'Print view request failed.', 'error');
                            
                            // Still empty cart even if receipt fails
                            var session_key = 'pos';
                            $.ajax({
                                url: '{{ url("empty-cart") }}',
                                method: 'POST',
                                data: {
                                    session_key: session_key,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(emptyData) {
                                    if (window.opener || window.parent !== window) {
                                        if (window.opener) {
                                            window.opener.location.reload();
                                        } else if (window.parent) {
                                            window.parent.location.reload();
                                        }
                                    } else {
                                        $('#carthtml').load(document.URL + ' #carthtml', function() {
                                            // Reinitialize Select2 dropdowns after cart reload
                                            // Delay to ensure DOM is ready
                                            setTimeout(function() {
                                                reinitializeSelect2Dropdowns();
                                            }, 300);
                                        });
                                    }
                                }
                            });
                        }
                    });
                }
            },
            error: function (data) {
                isProcessingPayment = false; // Reset flag on error
                data = data.responseJSON;
                show_toastr('{{ __("Error") }}', data.error || '{{ __("An error occurred while processing payment") }}', 'error');
                // Re-enable button on error
                ele.prop('disabled', false).html('{{ __("Pay") }}');
            }

        });
    });
</script>

