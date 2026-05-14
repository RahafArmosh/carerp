@extends('layouts.admin')
@section('page-title')
    {{ __('Journal Detail') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('journal-entry.index') }}">{{ __('Journal Entry') }}</a></li>
    <li class="breadcrumb-item">{{ Auth::user()->journalNumberFormat($journalEntry->journal_id) }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h2>{{ __('Journal') }}</h2>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h3 class="invoice-number">
                                        {{ \AUth::user()->journalNumberFormat($journalEntry->journal_id) }}</h3>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="font-style">
                                        <strong>{{ __('To') }} :</strong><br>
                                        {{ !empty($settings['company_name']) ? $settings['company_name'] : '' }}<br>
                                        {{ !empty($settings['company_telephone']) ? $settings['company_telephone'] : '' }}<br>
                                        {{ !empty($settings['company_address']) ? $settings['company_address'] : '' }}<br>
                                        {{ !empty($settings['company_city']) ? $settings['company_city'] : '' . ', ' }}
                                        {{ !empty($settings['company_state']) ? $settings['company_state'] : '' . ', ' }}
                                        {{ !empty($settings['company_country']) ? $settings['company_country'] : '' . '.' }}
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small>
                                        <strong>{{ __('Journal No') }} :</strong>
                                        {{ \Auth::user()->journalNumberFormat($journalEntry->journal_id) }}
                                    </small><br>
                                    <small>
                                        <strong>{{ __('Journal Ref') }} :</strong>
                                        {{ $journalEntry->reference }}
                                    </small> <br>
                                    <small>
                                        <strong>{{ __('Journal Date') }} :</strong>
                                        {{ \Auth::user()->dateFormat($journalEntry->date) }}
                                    </small>
                                    <br>
                                    <small>
                                        <strong>{{ __('Currency') }} :</strong>
                                        @if($journalEntry->currency)
                                            {{ $journalEntry->currency->name }} ({{ $journalEntry->currency->code }} — {{ $journalEntry->currency->symbol }})
                                            @if($journalEntry->currency_rate !== null && $journalEntry->currency_rate !== '')
                                                <br><strong>{{ __('Exchange rate') }} :</strong> {{ number_format((float) $journalEntry->currency_rate, 6) }}
                                            @endif
                                        @else
                                            {{ __('Default (company currency)') }}
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{ __('Journal Account Summary') }}</div>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 ">
                                            <tr>
                                                <th data-width="40" class="text-dark">#</th>
                                                <th class="text-dark">{{ __('Account') }}</th>
                                                <th class="text-dark" width="25%">{{ __('Description') }}</th>
                                                <th class="text-dark">{{ __('Debit') }}</th>
                                                <th class="text-dark">{{ __('Credit') }}</th>
                                                <th class="text-dark">{{ __('Product') }}</th>
                                                <th class="text-dark">{{ __('Amount') }}</th>
                                                {{-- <th></th> --}}
                                            </tr>

                                            @foreach ($accounts as $key => $account)
                                            @php
                                                $subProduct = \App\Models\SubProduct::with(['productService.category', 'productService.brand', 'productService.subBrand'])
                                                ->find($account->sub_product_id);

                                            @endphp
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ !empty($account->accounts) ? $account->accounts->code . ' - ' . $account->accounts->name : '' }}
                                                    </td>
                                                    <td>{{ !empty($account->description) ? $account->description : '-' }}</td>
                                                    <td>{{ $journalEntry->formatMoney($account->debit) }}</td>
                                                    <td>{{ $journalEntry->formatMoney($account->credit) }}</td>
                                                    <td>@if ($subProduct)
                                                        {{ $subProduct->productService->category->name ?? '' }} /
                                                        {{ $subProduct->productService->brand->name ?? '' }} /
                                                        {{ $subProduct->productService->subBrand->name ?? '' }} /
                                                        {{ $subProduct->productService->name ?? '' }} /
                                                        {{ $subProduct->chassis_no ?? '' }}
                                                    @endif</td>
                                                    <td>
                                                        @if ($account->debit != 0)
                                                            {{ $journalEntry->formatMoney($account->debit) }}
                                                        @else
                                                            {{ $journalEntry->formatMoney($account->credit) }}
                                                        @endif
                                                    </td>
                                                    {{-- <td>
                                                        <div class="action-btn bg-danger ms-2">
                                                            <form method="POST"
                                                                action="{{ route('journal.destroy', $account->id) }}"
                                                                id="delete-form-{{ $account->id }}">
                                                                @csrf
                                                                @method('DELETE')

                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i>
                                                                </a>
                                                            </form>

                                                        </div>
                                                    </td> --}}
                                                </tr>
                                            @endforeach

                                            <tfoot>

                                                <tr>
                                                    <td colspan="5"></td>
                                                    <td><b>{{ __('Total Credit') }}</b></td>
                                                    <td>{{ $journalEntry->formatMoney($journalEntry->totalCredit()) }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5"></td>
                                                    <td><b>{{ __('Total Debit') }}</b></td>
                                                    <td>{{ $journalEntry->formatMoney($journalEntry->totalDebit()) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="font-bold mt-2">
                                        {{ __('Description') }} : <br>
                                    </div>
                                    <small>{{ $journalEntry->description }}</small>
                                    
                                    @if($journalEntry->attachment)
                                    <div class="font-bold mt-3">
                                        {{ __('Attachment') }} : <br>
                                    </div>
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <a href="{{ route('journal-entry.attachment.view', $journalEntry->id) }}" target="_blank" class="btn btn-sm btn-secondary">
                                            <i class="ti ti-eye"></i> {{ __('View Attachment') }}
                                        </a>
                                        <a href="{{ route('journal-entry.attachment.download', $journalEntry->id) }}" class="btn btn-sm btn-primary ms-2">
                                            <i class="ti ti-download"></i> {{ __('Download Attachment') }}
                                        </a>
                                        <small class="ms-2 text-muted">{{ $journalEntry->attachment }}</small>
                                    </div>
                                @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-end">
                        @can('ledger report')
                        <a href="{{ route('journal-entry.ledger', $journalEntry->id) }}" target="_blank" class="btn btn-primary me-2" data-bs-toggle="tooltip" title="{{ __('Show Accounting') }}">
                            <i class="ti ti-file-invoice me-1"></i>{{ __('Show Accounting') }}
                        </a>
                        @endcan
                        @can('create journal entry')
                        <a href="{{ route('journal-entry.duplicate', $journalEntry->id) }}" class="btn btn-info me-2" data-bs-toggle="tooltip" title="{{ __('Duplicate Journal') }}">
                            <i class="ti ti-copy me-1"></i>{{ __('Duplicate') }}
                        </a>
                        @endcan
                        @can('edit journal entry')
                        <a href="{{ route('journal-entry.edit', $journalEntry->id) }}" class="btn btn-primary me-2" data-bs-toggle="tooltip" title="{{ __('Edit Journal') }}">
                            <i class="ti ti-pencil me-1"></i>{{ __('Edit') }}
                        </a>
                        @endcan
                        <a href="{{ route('journal-entry.index') }}" class="btn btn-secondary" data-bs-toggle="tooltip" title="{{ __('Back') }}">
                            <i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
