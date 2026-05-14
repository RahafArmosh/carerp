@extends('layouts.admin')
@section('page-title')
{{ __('Journal Entry Create') }}
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
    $(document).ready(function() {
            function initSelect2() {
                $('.js-account-search').select2({
                    width: '100%',
                    placeholder: 'Search Account...',
                    allowClear: true
                });

                $('.js-product-search').select2({
                    width: '100%',
                    placeholder: 'Search Product...',
                    allowClear: true
                });
            }

            // Initialize on page load
            initSelect2();

            // Reinitialize when new row is added dynamically
            $(document).on('click', '[data-repeater-create]', function() {
                setTimeout(() => {
                    initSelect2();
                }, 100);
            });
        });
        var selector = "body";
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
                    // for item SearchBox ( this function is  custom Js )
                    JsSearchBox();


                    // if($('.select2').length) {
                    //     $('.select2').select2();
                    // }
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".debit");
                        var totalDebit = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            totalDebit = parseFloat(totalDebit) + parseFloat($(inputs[i]).val());
                        }
                        $('.totalDebit').html(totalDebit.toFixed(2));


                        var inputs = $(".credit");
                        var totalCredit = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            totalCredit = parseFloat(totalCredit) + parseFloat($(inputs[i]).val());
                        }
                        $('.totalCredit').html(totalCredit.toFixed(2));


                    }
                },
                ready: function(setIndexes) {
                    // $dragAndDrop.on('drop', setIndexes);
                },
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

        $(document).on('keyup', '.debit', function() {
            var el = $(this).parent().parent().parent().parent();
            var debit = $(this).val();
            var credit = 0;
            el.find('.credit').val(credit);
            el.find('.amount').html(debit);


            var inputs = $(".debit");
            var totalDebit = 0;
            for (var i = 0; i < inputs.length; i++) {
                totalDebit = parseFloat(totalDebit) + parseFloat($(inputs[i]).val());
            }
            $('.totalDebit').html(totalDebit.toFixed(2));

            el.find('.credit').attr("disabled", true);
            if (debit == '') {
                el.find('.credit').attr("disabled", false);
            }
        })

        $(document).on('keyup', '.credit', function() {
            var el = $(this).parent().parent().parent().parent();
            var credit = $(this).val();
            var debit = 0;
            el.find('.debit').val(debit);
            el.find('.amount').html(credit);

            var inputs = $(".credit");
            var totalCredit = 0;
            for (var i = 0; i < inputs.length; i++) {
                totalCredit = parseFloat(totalCredit) + parseFloat($(inputs[i]).val());
            }
            $('.totalCredit').html(totalCredit.toFixed(2));

            el.find('.debit').attr("disabled", true);
            if (credit == '') {
                el.find('.debit').attr("disabled", false);
            }
        })
</script>
<script>
    $(document).ready(function() {
        let isSubmitting = false;
    
        $('#create-btn').on('click', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            $(this).prop('disabled', true).val('Processing...');
            $(this).closest('form').submit();
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
    <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
        data-url="{{ route('generate', ['journal entry']) }}" data-bs-placement="top"
        data-title="{{ __('Generate content with AI') }}">
        <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
    </a>
</div>
@endif
@endsection

@section('content')
<form action="{{ url('journal-entry') }}" method="POST" class="w-100" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label for="journal_number" class="form-label">{{ __('Journal Number') }}</label>

                                <input type="text" class="form-control"
                                    value="{{ \Auth::user()->journalNumberFormat($journalId) }}" readonly>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label for="date" class="form-label">{{ __('Transaction Date') }}</label>
                                <input type="date" name="date" class="form-control" required="required">

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label for="reference" class="form-label">{{ __('Reference') }}</label>
                                <input type="text" name="reference" class="form-control">

                            </div>
                        </div>
                        <div class="col-lg-8 col-md-8">
                            <div class="form-group">
                                <label for="description" class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
                                <select name="currency_id" id="currency_id" class="form-control">
                                    <option value="">{{ __('Default (company currency)') }}</option>
                                    @foreach ($currencies ?? [] as $cid => $cname)
                                        <option value="{{ $cid }}" {{ (string) old('currency_id') === (string) $cid ? 'selected' : '' }}>{{ $cname }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label for="currency_rate" class="form-label">{{ __('Exchange rate') }}</label>
                                <input type="number" name="currency_rate" id="currency_rate" class="form-control"
                                    step="any" min="0" value="{{ old('currency_rate') }}"
                                    placeholder="{{ __('Optional — uses currency default if empty') }}">
                                <small class="text-muted">{{ __('Multiplier to base (e.g. AED) for ledger amounts.') }}</small>
                            </div>
                        </div>
                        <div class="col-lg-12 col-md-12">
                            <div class="form-group">
                                <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                                <input type="file" name="attachment" id="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="text-muted">{{ __('Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card repeater">
                <div class="item-section py-4">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                            <a href="#" data-repeater-create="" class="btn btn-primary me-4" data-toggle="modal"
                                data-target="#add-bank">
                                <i class="ti ti-plus"></i> {{ __('Add Accounts') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table mb-0" data-repeater-list="accounts" id="sortable-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Debit') }}</th>
                                    <th>{{ __('Credit') }} </th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Product') }} </th>
                                    <th class="text-end">{{ __('Amount') }} </th>
                                    <th width="2%"></th>
                                </tr>
                            </thead>

                            <tbody class="ui-sortable" data-repeater-item>
                                <tr>
                                    <td width="25%" class="form-group">
                                        <select name="account" class="form-control js-account-search select2"
                                            required="required">
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

                                    <td class="form-group" style="width: 30%;">
                                        <select name="sub_product_id" class="form-control js-product-search select2">
                                            @foreach ($product_services as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-end amount">0.00</td>
                                    <td>
                                        <a href="#" class="ti ti-trash text-white text-danger" data-repeater-delete></a>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end"><strong>{{ __('Total Credit') }}
                                            ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                    <td class="text-end totalCredit">0.00</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td class="text-end"><strong>{{ __('Total Debit') }}
                                            ({{ \Auth::user()->currencySymbol() }})</strong></td>
                                    <td class="text-end totalDebit">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" onclick="location.href = '{{ route('journal-entry.index') }}';"
            class="btn btn-light">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary" id="create-btn">
    </div>
</form>
@endsection