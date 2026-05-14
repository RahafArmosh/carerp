@extends('layouts.admin')
@section('page-title')
    {{ __('Journal Entry Edit') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Double Entry') }}</li>
    <li class="breadcrumb-item">{{ __('Journal Entry') }}</li>
@endsection
@push('script-page')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        let deletedItems = [];
        var selector = "body";
        
        // Function to calculate totals for both debit and credit
        function calculateTotals() {
            // Calculate total debit
            var debitInputs = $(".debit");
            var totalDebit = 0;
            for (var i = 0; i < debitInputs.length; i++) {
                var debitValue = parseFloat($(debitInputs[i]).val()) || 0;
                if (!isNaN(debitValue)) {
                    totalDebit = totalDebit + debitValue;
                }
            }
            $('.totalDebit').html(totalDebit.toFixed(2));

            // Calculate total credit
            var creditInputs = $(".credit");
            var totalCredit = 0;
            for (var i = 0; i < creditInputs.length; i++) {
                var creditValue = parseFloat($(creditInputs[i]).val()) || 0;
                if (!isNaN(creditValue)) {
                    totalCredit = totalCredit + creditValue;
                }
            }
            $('.totalCredit').html(totalCredit.toFixed(2));
        }
        
        if ($(selector + " .repeater").length) {
            // var $dragAndDrop = $("body .repeater tbody").sortable({
            //     handle: '.sort-handler'
            // });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
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
                    // Initialize select2 for new items
                    $(this).find('.select2').select2();
                    // Recalculate totals when new item is added
                    setTimeout(function() {
                        calculateTotals();
                    }, 100);
                },
                hide: function(deleteElement) {
                    const $row = $(this); // the row to delete
                    const id = $row.find('.id').val(); // get the item ID

                    Swal.fire({
                        title: "Are you sure?",
                        text: "This item will be removed from the view.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#e3342f",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Yes, delete it",
                        cancelButtonText: "Cancel",
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $(this).slideUp(deleteElement);
                            $(this).remove();

                            if (id) {
                                deletedItems.push(id); // Add to deleted list
                            }
                            
                            // Recalculate totals after deletion
                            calculateTotals();

                            Swal.fire({
                                title: "Deleted!",
                                text: "The item has been removed.",
                                icon: "success",
                                timer: 1200,
                                showConfirmButton: false
                            });
                        } else {
                            // If user cancels deletion, keep the row
                        }
                    });
                },
                // ready: function (setIndexes) {
                //     $dragAndDrop.on('drop', setIndexes);
                // },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');

            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
                
                // Wait for repeater to render, then update amounts and calculate totals
                setTimeout(function() {
                    $('input[name*="[credit]"]').each(function() {
                        var creditVal = parseFloat($(this).val()) || 0;
                        if (creditVal > 0) {
                            $(this).trigger('keyup');
                        }
                    });
                    
                    $('input[name*="[debit]"]').each(function() {
                        var debitVal = parseFloat($(this).val()) || 0;
                        if (debitVal > 0) {
                            $(this).trigger('keyup');
                        }
                    });
                    
                    // Calculate totals after all values are set
                    calculateTotals();
                }, 300);
            }

        }

        $(document).on('keyup', '.debit', function() {
            var el = $(this).closest('tr');
            var debit = parseFloat($(this).val()) || 0;
            var credit = 0;
            el.find('.credit').val(credit);

            el.find('.amount').html(debit.toFixed(2));

            calculateTotals();

            el.find('.credit').attr("disabled", true);
            if ($(this).val() == '' || $(this).val() == '0') {
                el.find('.credit').attr("disabled", false);
            }
        })

        $(document).on('keyup', '.credit', function() {
            var el = $(this).closest('tr');
            var credit = parseFloat($(this).val()) || 0;
            var debit = 0;
            el.find('.debit').val(debit);

            el.find('.amount').html(credit.toFixed(2));

            calculateTotals();

            el.find('.debit').attr("disabled", true);
            if ($(this).val() == '' || $(this).val() == '0') {
                el.find('.debit').attr("disabled", false);
            }
        })


        // Initialize totals on page load and after repeater renders
        $(document).ready(function() {
            // Calculate totals after a delay to ensure DOM is ready
            setTimeout(function() {
                calculateTotals();
            }, 800);
            
            // Also calculate when inputs change (handles paste, etc.)
            $(document).on('input change', '.debit, .credit', function() {
                setTimeout(function() {
                    calculateTotals();
                }, 50);
            });
        });
    </script>
    <script>
        $('form').on('submit', function(e) {
    // Convert deleted items array to JSON string and set hidden input
    $('#deleted-items-field').val(JSON.stringify(deletedItems));
});
    window.addEventListener('load', () => {
        const redirectUrl = sessionStorage.getItem('redirectAfterReload');
        if (redirectUrl) {
            sessionStorage.removeItem('redirectAfterReload');
            window.location.href = redirectUrl;
        }
    });

    var currencyRates = @json($currencyRates ?? []);
    $('#currency_id').on('change', function() {
        var id = $(this).val();
        if (!id) {
            $('#currency_rate').val('');
            return;
        }
        if (currencyRates[id] !== undefined && currencyRates[id] !== null && currencyRates[id] !== '') {
            $('#currency_rate').val(currencyRates[id]);
        }
    });
    </script>
@endpush

@section('action-btn')
    @php
        $user = \App\Models\User::find(\Auth::user()->creatorId());
        $plan = \App\Models\Plan::getPlan($user->plan);
    @endphp
    @if ($plan->chatgpt == 1)
        <div class="float-end">
            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['journal entry']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
@endsection


@section('content')
    <form action="{{ route('journal-entry.update', $journalEntry->id) }}" method="POST" class="w-100" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
        <input type="hidden" name="deleted_items" id="deleted-items-field">
        <div class="row mt-4">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label for="journal_number" class="form-label">{{ __('Journal Number') }}</label>
                                    <div class="form-icon-user">
                                        <input type="text" class="form-control"
                                            value="{{ \Auth::user()->journalNumberFormat($journalEntry->journal_id) }}"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label for="date" class="form-label">{{ __('Transaction Date') }}</label>

                                    <div class="form-icon-user">
                                        <input type="date" name="date" class="form-control" required="required"
                                            value="{{ $journalEntry->date }}">

                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label for="reference" class="form-label">{{ __('Reference') }}</label>

                                    <div class="form-icon-user">
                                        <input type="text" name="reference" class="form-control"
                                            value="{{ $journalEntry->reference }}">

                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-8">
                                <div class="form-group">
                                    <label for="description" class="form-label">{{ __('Description') }}</label>
                                    <textarea name="description" class="form-control" rows="2">{{ $journalEntry->description }}</textarea>

                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                    <select name="currency_id" id="currency_id" class="form-control">
                                        <option value="">{{ __('Default (company currency)') }}</option>
                                        @foreach ($currencies ?? [] as $cid => $cname)
                                            <option value="{{ $cid }}" {{ (string) old('currency_id', $journalEntry->currency_id) === (string) $cid ? 'selected' : '' }}>{{ $cname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label for="currency_rate" class="form-label">{{ __('Exchange rate') }}</label>
                                    <input type="number" name="currency_rate" id="currency_rate" class="form-control"
                                        step="any" min="0" value="{{ old('currency_rate', $journalEntry->currency_rate) }}"
                                        placeholder="{{ __('Optional — uses currency default if empty') }}">
                                    <small class="text-muted">{{ __('Multiplier to base (e.g. AED) for ledger amounts.') }}</small>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="form-group">
                                    <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                                    <input type="file" name="attachment" id="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    <small class="text-muted">{{ __('Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG') }}</small>
                                    @if($journalEntry->attachment)
                                        <div class="mt-2">
                                            <small class="text-muted">{{ __('Current attachment:') }}</small>
                                            <a href="{{ route('journal-entry.attachment.download', $journalEntry->id) }}" target="_blank" class="ms-2">
                                                <i class="ti ti-file"></i> {{ $journalEntry->attachment }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card repeater" data-value='@json($journalEntry->accounts)'>
                    <div class="item-section py-4">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                                <div class="all-button-box">
                                    <a href="#" data-repeater-create="" class="btn btn-primary me-4"
                                        data-toggle="modal" data-target="#add-bank">
                                        <i class="ti ti-plus"></i> {{ __('Add Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="mb-0 table" data-repeater-list="accounts" id="sortable-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Account') }}</th>
                                        <th>{{ __('Debit') }}</th>
                                        <th>{{ __('Credit') }} </th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-end">{{ __('Amount') }} </th>
                                        <th width="2%"></th>
                                    </tr>
                                </thead>

                                <tbody class="ui-sortable" data-repeater-item>

                                    <tr>
                                        <input type="hidden" name="id" class="form-control id">

                                        {{-- <td width="25%"> --}}
                                        {{-- <div class="form-group"> --}}
                                        {{-- {{ Form::select('account', $accounts,'', array('class' => 'form-control
                                            js-searchBox','required'=>'required')) }} --}}

                                        {{-- </div> --}}
                                        {{-- </td> --}}

                                        <td width="25%" class="form-group">
                                            <select name="account" class="form-control select2" required="required">
                                                <option value="">Select Account</option>
                                                @foreach ($accounts as $accountId => $account)
                                                    <option value="{{ $accountId }}">{{ $account }}</option>
                                                @endforeach
                                            </select>

                                        </td>


                                        <td>
                                            <div class="form-group price-input">
                                                <input type="text" name="debit" class="form-control debit"
                                                    required="required" placeholder="{{ __('Debit') }}">

                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group price-input">
                                                <input type="text" name="credit" class="form-control credit"
                                                    required="required" placeholder="{{ __('Credit') }}">

                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input type="text" name="description" class="form-control"
                                                    placeholder="{{ __('Description') }}">

                                            </div>
                                        </td>
                                        <td class="amount text-end">0.00</td>
                                        <td>
                                            <a href="#" class="ti ti-trash text-danger"
                                                data-repeater-delete></a>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td class="text-end"><strong>{{ __('Total Credit') }}
                                                ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                        <td class="totalCredit text-end">0.00</td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="text-end"><strong>{{ __('Total Debit') }}
                                                ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                        <td class="totalDebit text-end">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" onclick="
                sessionStorage.setItem('redirectAfterReload', '{{ route('journal-entry.index') }}');
                location.reload();
            ">
            <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
        </div>
    </form>
@endsection
