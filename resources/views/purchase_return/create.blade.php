@extends('layouts.admin')

@section('page-title')
    {{ __('Create Purchase Return') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase.return.index') }}">{{ __('Purchase Return') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('purchase.return.store') }}" method="POST" id="purchase-return-form">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="bill_id" class="form-label">{{ __('Bill') }}</label>
                                <select name="bill_id" id="bill_id" class="form-control select2" required>
                                    <option value="">{{ __('Select Bill') }}</option>
                                    @foreach ($bills as $bill)
                                        <option value="{{ $bill->id }}">
                                            {{ \Auth::user()->billNumberFormat($bill->bill_id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="return_date" class="form-label">{{ __('Return Date') }}</label>
                                <input type="date" name="return_date" id="return_date" class="form-control"
                                    value="{{ old('return_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">{{ __('Available Items from Bill') }}</h6>
                        <div class="table-responsive">
                            <table class="table" id="available-items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Item') }}</th>
                                        <th>{{ __('Available Qty') }}</th>
                                        <th>{{ __('Unit Price') }}</th>
                                        <th>{{ __('Discount') }}</th>
                                        <th>{{ __('Tax') }}</th>
                                        <th>{{ __('Total') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="text-muted">{{ __('Select a bill to load available items.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mt-4 mb-3">{{ __('Return Items') }}</h6>
                        <div class="table-responsive">
                            <table class="table" id="selected-items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Item') }}</th>
                                        <th>{{ __('Return Qty') }}</th>
                                        <th>{{ __('Unit Price') }}</th>
                                        <th>{{ __('Discount') }}</th>
                                        <th>{{ __('Tax') }}</th>
                                        <th>{{ __('Total') }}</th>
                                        <th>{{ __('Reason') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-muted">{{ __('No items added to return yet.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end mt-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <h6>{{ __('Return Summary') }}</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Subtotal') }}</span>
                                        <strong id="summary-subtotal">0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Tax') }}</span>
                                        <strong id="summary-tax">0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('Total Return Amount') }}</span>
                                        <strong id="summary-total">0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary">{{ __('Create Purchase Return') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        (function() {
            const billSelect = document.getElementById('bill_id');
            const form = document.getElementById('purchase-return-form');
            const availableBody = document.querySelector('#available-items-table tbody');
            const selectedBody = document.querySelector('#selected-items-table tbody');
            const summarySubtotal = document.getElementById('summary-subtotal');
            const summaryTax = document.getElementById('summary-tax');
            const summaryTotal = document.getElementById('summary-total');
            const selectedItems = new Map();

            const formatNumber = (value) => Number(value || 0).toFixed(2);

            const renderAvailableInfoRow = (message) => {
                availableBody.innerHTML = `<tr><td colspan="7" class="text-muted">${message}</td></tr>`;
            };

            const renderSelectedInfoRow = () => {
                if (selectedItems.size > 0) {
                    return;
                }
                selectedBody.innerHTML = `<tr><td colspan="8" class="text-muted">{{ __('No items added to return yet.') }}</td></tr>`;
            };

            const updateSummary = () => {
                let subtotal = 0;
                let tax = 0;
                let total = 0;

                selectedItems.forEach((item) => {
                    const qty = Number(item.return_qty || 0);
                    const unitBase = Math.max(Number(item.price) - Number(item.discount), 0);
                    const lineSubtotal = unitBase * qty;
                    const lineTax = (lineSubtotal * Number(item.tax_rate || 0)) / 100;
                    subtotal += lineSubtotal;
                    tax += lineTax;
                    total += lineSubtotal + lineTax;
                });

                summarySubtotal.textContent = formatNumber(subtotal);
                summaryTax.textContent = formatNumber(tax);
                summaryTotal.textContent = formatNumber(total);
            };

            const renderSelectedItems = () => {
                if (selectedItems.size === 0) {
                    renderSelectedInfoRow();
                    updateSummary();
                    return;
                }

                let rows = '';
                let index = 0;

                selectedItems.forEach((item, key) => {
                    const qty = Number(item.return_qty || 1);
                    const unitBase = Math.max(Number(item.price) - Number(item.discount), 0);
                    const lineSubtotal = unitBase * qty;
                    const lineTax = (lineSubtotal * Number(item.tax_rate || 0)) / 100;
                    const lineTotal = lineSubtotal + lineTax;

                    rows += `
                        <tr>
                            <td>
                                <strong>${item.product_name}</strong>
                                <small class="d-block text-muted">${item.sku || ''}</small>
                                <input type="hidden" name="items[${index}][bill_product_id]" value="${item.bill_product_id}">
                            </td>
                            <td style="width: 140px;">
                                <input type="number" class="form-control return-qty" min="0.01" step="0.01"
                                    max="${item.available_qty}" data-key="${key}"
                                    name="items[${index}][quantity]" value="${qty}">
                            </td>
                            <td>${formatNumber(item.price)}</td>
                            <td>${formatNumber(item.discount)}</td>
                            <td><span class="badge bg-light text-dark">${item.tax_label || '-'}</span></td>
                            <td><strong>${formatNumber(lineTotal)}</strong></td>
                            <td>
                                <input type="text" class="form-control" placeholder="{{ __('Optional reason') }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-item" data-key="${key}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    index++;
                });

                selectedBody.innerHTML = rows;
                updateSummary();
            };

            const renderAvailableItems = (items) => {
                if (!items.length) {
                    renderAvailableInfoRow('{{ __('No unsold items available for return in this bill.') }}');
                    return;
                }

                let rows = '';
                items.forEach((item) => {
                    rows += `
                        <tr>
                            <td>
                                <strong>${item.product_name}</strong>
                                <small class="d-block text-muted">${item.sku || ''}</small>
                            </td>
                            <td>${formatNumber(item.available_qty)}</td>
                            <td>${formatNumber(item.price)}</td>
                            <td>${formatNumber(item.discount)}</td>
                            <td><span class="badge bg-light text-dark">${item.tax_label || '-'}</span></td>
                            <td>${formatNumber(item.line_total)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary add-to-return"
                                    data-item='${JSON.stringify(item).replace(/'/g, '&#39;')}'>
                                    {{ __('Add to Return') }}
                                </button>
                            </td>
                        </tr>
                    `;
                });

                availableBody.innerHTML = rows;
            };

            const clearSelection = () => {
                selectedItems.clear();
                renderSelectedInfoRow();
                updateSummary();
            };

            const loadBillItems = (billId) => {
                clearSelection();
                if (!billId) {
                    renderAvailableInfoRow('{{ __('Select a bill to load available items.') }}');
                    return Promise.resolve();
                }

                renderAvailableInfoRow('{{ __('Loading items...') }}');
                return fetch(`{{ url('purchase-return/bill-items') }}/${billId}`)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then((items) => {
                        renderAvailableItems(items);
                    })
                    .catch((error) => {
                        renderAvailableInfoRow('{{ __('Failed to load bill items.') }}' + ' (' + error.message + ')');
                    });
            };


            billSelect.addEventListener('change', function() {
                loadBillItems(this.value);
            });

            // Support Select2-triggered events (some themes/plugins do not fire native change reliably).
            if (typeof window.$ !== 'undefined') {
                $(document).on('change select2:select', '#bill_id', function() {
                    loadBillItems($(this).val());
                });
            }


            document.addEventListener('click', function(event) {
                const addBtn = event.target.closest('.add-to-return');
                if (addBtn) {
                    const itemData = addBtn.getAttribute('data-item');
                    if (!itemData) {
                        return;
                    }

                    const item = JSON.parse(itemData.replace(/&#39;/g, "'"));
                    const key = String(item.bill_product_id);

                    if (!selectedItems.has(key)) {
                        item.return_qty = 1;
                        selectedItems.set(key, item);
                    }
                    renderSelectedItems();
                    return;
                }

                const removeBtn = event.target.closest('.remove-item');
                if (removeBtn) {
                    selectedItems.delete(removeBtn.getAttribute('data-key'));
                    renderSelectedItems();
                }
            });

            document.addEventListener('input', function(event) {
                if (!event.target.classList.contains('return-qty')) {
                    return;
                }

                const key = event.target.getAttribute('data-key');
                if (!selectedItems.has(key)) {
                    return;
                }

                const item = selectedItems.get(key);
                const value = Number(event.target.value || 0);
                const boundedValue = Math.min(Math.max(value, 0.01), Number(item.available_qty || 0));
                item.return_qty = boundedValue;
                event.target.value = boundedValue;
                selectedItems.set(key, item);
                updateSummary();
            });

            form.addEventListener('submit', function(event) {
                if (selectedItems.size === 0) {
                    event.preventDefault();
                    window.alert('{{ __('Please add at least one item to return.') }}');
                }
            });

            // Handle preselected value (old input / browser restore).
            if (billSelect.value) {
                loadBillItems(billSelect.value);
            }
        })();
    </script>
@endpush
