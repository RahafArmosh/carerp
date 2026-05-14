@extends('layouts.admin')

@section('page-title')
    {{ __('Create Sales Return (Import)') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.return.index') }}">{{ __('Sales Return') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('sales.return.store') }}" method="POST" id="sales-return-import-form">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="invoice_id" class="form-label">{{ __('Invoice') }}</label>
                                <select name="invoice_id" id="invoice_id" class="form-control select2" required>
                                    <option value="">{{ __('Select Invoice') }}</option>
                                    @foreach ($invoices as $invoice)
                                        <option value="{{ $invoice->id }}">
                                            {{ \Auth::user()->invoiceNumberFormat($invoice->invoice_id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="return_date" class="form-label">{{ __('Return Date') }}</label>
                                <input type="date" name="return_date" id="return_date" class="form-control"
                                    value="{{ old('return_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="form-group col-md-4 d-flex align-items-end">
                                <a href="{{ route('sales.return.import.sample') }}" class="btn btn-outline-secondary w-100">
                                    {{ __('Download Sample File') }}
                                </a>
                            </div>
                            <div class="form-group col-md-9">
                                <label for="items_file" class="form-label">{{ __('Import Items from Excel') }}</label>
                                <input type="file" id="items_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <small class="text-muted">{{ __('Template columns: sub_product_id, sub_product_no, quantity') }}</small>
                            </div>
                            <div class="form-group col-md-3 d-flex align-items-end">
                                <button type="button" id="import-items-btn" class="btn btn-primary w-100">
                                    {{ __('Import & Preview') }}
                                </button>
                            </div>
                            <div class="form-group col-md-12">
                                <div id="import-feedback" class="alert d-none mb-0"></div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table" id="selected-items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Item') }}</th>
                                        <th>{{ __('Available Qty') }}</th>
                                        <th>{{ __('Return Qty') }}</th>
                                        <th>{{ __('Unit Price') }}</th>
                                        <th>{{ __('Tax') }}</th>
                                        <th>{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-muted">{{ __('No imported items yet.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success">{{ __('Create Sales Return') }}</button>
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
            const form = document.getElementById('sales-return-import-form');
            const invoiceSelect = document.getElementById('invoice_id');
            const importFileInput = document.getElementById('items_file');
            const importBtn = document.getElementById('import-items-btn');
            const feedback = document.getElementById('import-feedback');
            const selectedBody = document.querySelector('#selected-items-table tbody');
            const selectedItems = new Map();

            const formatNumber = (value) => Number(value || 0).toFixed(2);
            const showFeedback = (message, type = 'danger') => {
                feedback.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning', 'alert-info');
                feedback.classList.add('alert-' + type);
                feedback.textContent = message;
            };
            const clearFeedback = () => {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            };

            const renderItems = () => {
                if (selectedItems.size === 0) {
                    selectedBody.innerHTML = `<tr><td colspan="6" class="text-muted">{{ __('No imported items yet.') }}</td></tr>`;
                    return;
                }

                let rows = '';
                let index = 0;
                selectedItems.forEach((item, key) => {
                    const qty = Number(item.return_qty || 0);
                    const unitBase = Math.max(Number(item.price) - Number(item.discount), 0);
                    const lineSubtotal = unitBase * qty;
                    const lineTax = (lineSubtotal * Number(item.tax_rate || 0)) / 100;
                    rows += `
                        <tr>
                            <td>
                                <strong>${item.product_name}</strong>
                                <small class="d-block text-muted">${item.sku || ''}</small>
                                <input type="hidden" name="items[${index}][invoice_product_id]" value="${item.invoice_product_id}">
                            </td>
                            <td>${formatNumber(item.available_qty)}</td>
                            <td style="width: 160px;">
                                <input type="number" class="form-control return-qty" name="items[${index}][quantity]"
                                    min="0.01" step="0.01" max="${item.available_qty}" data-key="${key}" value="${qty}">
                            </td>
                            <td>${formatNumber(item.price)}</td>
                            <td><span class="badge bg-light text-dark">${item.tax_label || '-'}</span></td>
                            <td><strong>${formatNumber(lineSubtotal + lineTax)}</strong></td>
                        </tr>
                    `;
                    index++;
                });
                selectedBody.innerHTML = rows;
            };

            importBtn.addEventListener('click', function() {
                clearFeedback();
                const invoiceId = invoiceSelect.value;
                const file = importFileInput.files[0];
                if (!invoiceId) {
                    showFeedback('{{ __('Please select an invoice first.') }}', 'warning');
                    return;
                }
                if (!file) {
                    showFeedback('{{ __('Please choose an Excel file first.') }}', 'warning');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('invoice_id', invoiceId);
                formData.append('file', file);

                importBtn.disabled = true;
                importBtn.textContent = '{{ __('Importing...') }}';

                fetch(`{{ route('sales.return.import.items') }}`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || '{{ __('Failed to import items.') }}');
                        }
                        return data;
                    })
                    .then((items) => {
                        const normalizedItems = Array.isArray(items) ? items : Object.values(items || {});
                        if (!normalizedItems.length) {
                            throw new Error('{{ __('Import completed but no valid rows matched selected invoice items. Please verify Sub Product ID/No and quantity.') }}');
                        }
                        selectedItems.clear();
                        normalizedItems.forEach((item) => {
                            const key = String(item.invoice_product_id);
                            item.return_qty = Math.min(Math.max(Number(item.return_qty || item.quantity || 1), 0.01), Number(item.available_qty || 0));
                            selectedItems.set(key, item);
                        });
                        renderItems();
                        showFeedback('{{ __('Items imported successfully.') }}', 'success');
                    })
                    .catch((error) => showFeedback(error.message || '{{ __('Failed to import items.') }}', 'danger'))
                    .finally(() => {
                        importBtn.disabled = false;
                        importBtn.textContent = '{{ __('Import & Preview') }}';
                    });
            });

            document.addEventListener('input', function(event) {
                if (!event.target.classList.contains('return-qty')) return;
                const key = event.target.getAttribute('data-key');
                if (!selectedItems.has(key)) return;
                const item = selectedItems.get(key);
                const value = Number(event.target.value || 0);
                const bounded = Math.min(Math.max(value, 0.01), Number(item.available_qty || 0));
                item.return_qty = bounded;
                selectedItems.set(key, item);
                event.target.value = bounded;
            });

            form.addEventListener('submit', function(event) {
                if (selectedItems.size === 0) {
                    event.preventDefault();
                    window.alert('{{ __('Please import at least one item.') }}');
                }
            });
        })();
    </script>
@endpush
