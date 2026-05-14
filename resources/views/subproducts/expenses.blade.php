@extends('layouts.admin')
@section('page-title')
    {{ __('Item Expenses') }}
@endsection
@push('script-page')


    @section('content')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="invoice">
                            <div class="invoice-print">
                                <div class="row invoice-title mt-2">
                                    <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                        <h4 class="invoice-number">
                                            @if ($subProduct)
                                                {{ $subProduct->productService->category->name ?? '' }} /
                                                {{ $subProduct->productService->brand->name ?? '' }} /
                                                {{ $subProduct->productService->subBrand->name ?? '' }} /
                                                {{ $subProduct->productService->name ?? '' }} /
                                                {{ $subProduct->chassis_no ?? '' }}
                                            @endif
                                        </h4>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                        <h4>{{ __('Product') }}</h4>

                                    </div>
                                    <div class="col-12">
                                        <hr>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="font-bold mb-2">{{ __('Product Expenses Summary') }}</div>

                                        @if (empty($journalEntries) || $journalEntries->isEmpty())
                                            <div class="alert alert-warning text-center">
                                                {{ __('No data available') }}
                                            </div>
                                        @else
                                            <div class="table-responsive mt-3">
                                                <table class="table mb-0 table-striped">
                                                    <tr>
                                                        {{-- <th class="text-dark" data-width="40">#</th> --}}
                                                        <th> {{ __('Journal ID') }}</th>
                                                        <th> {{ __('Journal Date') }}</th>
                                                        <th> {{ __('Journal Account') }}</th>
                                                        <th> {{ __('Description') }}</th>
                                                        <th> {{ __('Debit') }}</th>
                                                        <th> {{ __('Credit') }}</th>
                                                        {{-- <th class="text-end text-dark" width="12%">{{ __('Amount') }} --}}
                                                        </th>
                                                    </tr>
                                                    @php

                                                        $totalCredit = 0;
                                                        $totalDebit = 0;

                                                    @endphp
                                                    @foreach ($journalEntries as $item)
                                                        @php
                                                            if ($item->debit != 0) {
                                                                $totalDebit = $totalDebit + $item->debit;
                                                            }
                                                            if ($item->credit != 0) {
                                                                $totalCredit = $totalCredit + $item->credit;
                                                            }

                                                        @endphp
                                                        <tr>
                                                            <td class="Id">
                                                                <a href="{{ route('journal-entry.show', $item->journal) }}"
                                                                    class="btn btn-outline-primary">{{ \Auth::user()->journalNumberFormat(\App\Models\JournalEntry::where('id', $item->journal)->first()->journal_id) }}</a>
                                                            </td>
                                                            <td>
                                                                {{ \App\Models\JournalEntry::where('id', $item->journal)->first()->date }}
                                                            </td>
                                                            <td>
                                                                {{ !empty($item->accounts) ? $item->accounts->code . ' - ' . $item->accounts->name : '' }}
                                                            </td>
                                                            <td>
                                                                {{ $item->description }}
                                                            </td>
                                                            <td>
                                                                {{ $item->debit }}
                                                            </td>
                                                            <td>
                                                                {{ $item->credit }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    <tfoot>

                                                        <tr>
                                                            <td colspan="4"></td>
                                                            <td><b>{{ __('Total Credit') }}</b></td>
                                                            <td>{{ \Auth::user()->priceFormat($totalCredit) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="4"></td>
                                                            <td><b>{{ __('Total Debit') }}</b></td>
                                                            <td>{{ \Auth::user()->priceFormat($totalDebit) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Direct Expenses Section --}}
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <div class="font-bold mb-2">{{ __('Direct Expenses') }}</div>
                                            @if(empty($directExpenses) || $directExpenses->isEmpty())
                                                <div class="alert alert-info text-center">{{ __('No direct expenses for this item') }}</div>
                                            @else
                                                <div class="table-responsive mt-3">
                                                    <table class="table mb-0 table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Date') }}</th>
                                                                <th>{{ __('Expense #') }}</th>
                                                                <th>{{ __('Vendor') }}</th>
                                                                <th>{{ __('Account') }}</th>
                                                                <th>{{ __('Amount') }}</th>
                                                                <th>{{ __('Tax') }}</th>
                                                                <th>{{ __('Total') }}</th>
                                                                <th>{{ __('Description') }}</th>
                                                                <th>{{ __('Actions') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($directExpenses as $dx)
                                                                @php
                                                                    $matchingItems = $dx->items->where('sub_product_id', $subProduct->id);
                                                                    // Calculate expense total amount for proportional tax calculation
                                                                    $expenseTotalAmount = $dx->getTotalAmount();
                                                                    $expenseTaxAmount = 0;
                                                                    
                                                                    // Calculate total tax for the expense if tax exists
                                                                    if ($dx->tax_id && $expenseTotalAmount > 0) {
                                                                        $taxIds = $dx->getTaxIds();
                                                                        $totalTaxRate = 0;
                                                                        foreach ($taxIds as $taxId) {
                                                                            $tax = \App\Models\Tax::find($taxId);
                                                                            if ($tax) {
                                                                                $totalTaxRate += $tax->rate;
                                                                            }
                                                                        }
                                                                        $expenseTaxAmount = ($totalTaxRate / 100) * $expenseTotalAmount;
                                                                    }
                                                                @endphp
                                                                @if($matchingItems->count() > 0)
                                                                    @foreach($matchingItems as $item)
                                                                        @php
                                                                            // Calculate proportional tax for this item
                                                                            $itemTaxAmount = 0;
                                                                            if ($expenseTotalAmount > 0 && $expenseTaxAmount > 0) {
                                                                                $itemTaxAmount = ($item->amount / $expenseTotalAmount) * $expenseTaxAmount;
                                                                            }
                                                                            $itemTotal = $item->amount + $itemTaxAmount;
                                                                        @endphp
                                                                        <tr>
                                                                            <td>{{ $dx->created_at->format('Y-m-d') }}</td>
                                                                            <td>
                                                                                <a href="{{ route('direct_expenses.show', $dx->id) }}">
                                                                                    {{ \Auth::user()->expenseNumberFormat($dx->expense_number) }}
                                                                                </a>
                                                                            </td>
                                                                            <td>{{ optional($dx->vendor)->name }}</td>
                                                                            <td>
                                                                                @if($item->chart_account_id && $item->chartAccount)
                                                                                    {{ $item->chartAccount->code }} - {{ $item->chartAccount->name }}
                                                                                @else
                                                                                    <span class="text-muted">{{ __('Category Purchase Account') }}</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ \Auth::user()->priceFormat($item->amount) }}</td>
                                                                            <td>{{ \Auth::user()->priceFormat($itemTaxAmount) }}</td>
                                                                            <td><strong>{{ \Auth::user()->priceFormat($itemTotal) }}</strong></td>
                                                                            <td>{{ $item->description }}</td>
                                                                            <td>
                                                                                @if(\Auth::user()->can('manage expense') || \Auth::user()->can('create bill'))
                                                                                <form action="{{ route('direct_expenses.destroy_item', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this expense item? This will create reversal ledger entries for this item only.') }}');">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <input type="hidden" name="redirect_to" value="{{ route('sub-product.expenses', $subProduct->id) }}">
                                                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete Expense Item') }}">
                                                                                        <i class="ti ti-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Linked Accessories Section --}}
                                    @if(!empty($linkedAccessories) && $linkedAccessories->count() > 0)
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="font-bold mb-2">{{ __('Linked Accessories') }}</div>
                                                <div class="table-responsive mt-3">
                                                    <table class="table mb-0 table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Request No') }}</th>
                                                                <th>{{ __('Request Date') }}</th>
                                                                <th>{{ __('Accessory Product') }}</th>
                                                                <th>{{ __('Quantity') }}</th>
                                                                <th>{{ __('Sell Price') }}</th>
                                                                <th>{{ __('Status') }}</th>
                                                                <th>{{ __('Actions') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($linkedAccessories as $requestId => $items)
                                                                @foreach($items as $item)
                                                                    <tr>
                                                                        <td>
                                                                            <a href="{{ route('car_accessories.show', $item->request->id) }}" 
                                                                               class="btn btn-outline-primary btn-sm">
                                                                                {{ $item->request->request_no }}
                                                                            </a>
                                                                        </td>
                                                                        <td>{{ $item->request->request_date }}</td>
                                                                        <td>
                                                                            @if($item->product)
                                                                                {{ $item->product->name ?? 'Product ID: ' . $item->product_id }}
                                                                            @else
                                                                                <span class="text-muted">Product not found</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ $item->quantity ?? '-' }}</td>
                                                                        <td>{{ $item->sell_price ? \Auth::user()->priceFormat($item->sell_price) : '-' }}</td>
                                                                        <td>
                                                                            <span class="badge bg-{{ $item->request->status === 'approved' ? 'success' : ($item->request->status === 'pending' ? 'warning' : ($item->request->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                                                                {{ ucfirst($item->request->status) }}
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <a href="{{ route('car_accessories.show', $item->request->id) }}" 
                                                                               class="btn btn-sm btn-outline-info">
                                                                                <i class="fas fa-eye"></i> View Request
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="font-bold mb-2">{{ __('Linked Accessories') }}</div>
                                                <div class="alert alert-info text-center">
                                                    {{ __('No accessories linked to this car') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    @endsection
