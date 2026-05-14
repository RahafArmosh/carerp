@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Debit Notes') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Debit Note') }}</li>
@endsection
@push('script-page')
    <script>
        $(document).on('change', '#bill', function() {

            var id = $(this).val();
            var url = "{{ route('bill.get') }}";

            $.ajax({
                url: url,
                type: 'get',
                cache: false,
                data: {
                    'bill_id': id,

                },
                success: function(data) {
                    $('#amount').val(data)
                },

            });

        })
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        @can('create debit note')
            <a href="#" data-url="{{ route('bill.custom.debit.note') }}" data-ajax-popup="true"
                data-title="{{ __('Create New Debit Note') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('Bill') }}</th>
                                    <th> {{ __('Vendor') }}</th>
                                    <th> {{ __('Date') }}</th>
                                    <th> {{ __('Account') }}</th>
                                    <th> {{ __('Amount') }}</th>
                                    <th> {{ __('Currency') }}</th>
                                    <th> {{ __('Amount in AED') }}</th>
                                    <th> {{ __('Amount in Bill Currency') }}</th>
                                    <th> {{ __('Description') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($bills as $bill)
                                    @if (!empty($bill->debitNote))
                                        @foreach ($bill->debitNote as $debitNote)
                                            <tr class="font-style">
                                                @php
                                                    $currencySymbol = $bill && $bill->currency ? $bill->currency->symbol : Auth::user()->currencySymbol();
                                                @endphp
                                                <td class="Id">
                                                    <a href="{{ route('bill.show', \Crypt::encrypt($debitNote->bill)) }}"
                                                        class="btn btn-outline-primary">{{ AUth::user()->billNumberFormat($bill->bill_id) }}

                                                    </a>
                                                </td>
                                                <td>{{ !empty($bill->vender) ? $bill->vender->name : '-' }}</td>
                                                <td>{{ Auth::user()->dateFormat($debitNote->date) }}</td>
                                                <td>{{ \App\Models\ChartOfAccount::where('id', $debitNote->account_id)->first()->name }}
                                                </td>
                                                <td>{{ $debitNote->currency_id ? Auth::user()->priceFormatCurr($debitNote->amount / $debitNote->currency_rate,$debitNote->currency->symbol ) : Auth::user()->priceFormat($debitNote->amount) }}</td>
                                                <td>{{ $debitNote->currency_id ? $debitNote->currency->name : '-'  }}</td>
                                                <td>{{ Auth::user()->priceFormat($debitNote->amount)  }}</td>
                                                <td>{{ Auth::user()->priceFormatCurr($debitNote->amount_in_currency,$currencySymbol)  }}</td>
                                                <td>{{ !empty($debitNote->description) ? $debitNote->description : '-' }}
                                                </td>
                                                <td class="Action">
                                                    <span>
                                                        @can('edit debit note')
                                                            <div class="action-btn bg-primary ms-2">
                                                                <a data-url="{{ route('bill.edit.debit.note', [$debitNote->bill, $debitNote->id]) }}"
                                                                    data-ajax-popup="true"
                                                                    data-title="{{ __('Edit Debit Note') }}" href="#"
                                                                    class="mx-3 btn btn-sm align-items-center"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                        @can('edit debit note')
                                                            <div class="action-btn bg-danger ms-2">
                                                                <form
                                                                    action="{{ route('bill.delete.debit.note', [$debitNote->bill, $debitNote->id]) }}"
                                                                    method="POST" id="delete-form-{{ $debitNote->id }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="delete_date"
                                                                        id="delete-date-{{ $debitNote->id }}">
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        onclick="confirmDeleteWithDate({{ $debitNote->id }})">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                </form>
                                                            </div>
                                                        @endcan
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    function confirmDeleteWithDate(id) {
        Swal.fire({
            title: 'Are you sure?',
            html: 'Enter the delete date:',
            icon: 'warning',
            input: 'date',
            inputAttributes: {
                required: true
            },
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            preConfirm: (date) => {
                if (!date) {
                    Swal.showValidationMessage('Delete date is required');
                    return false;
                }
                return date;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-date-' + id).value = result.value;
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
