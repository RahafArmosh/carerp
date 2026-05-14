@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Chart of Accounts') }}
@endsection
@push('css-page')
    <style>
        .card-accordion-toggle {
            user-select: none;
        }

        .card-toggle-icon {
            transition: transform 0.3s ease;
        }

        .card-toggle-icon.rotated {
            transform: rotate(90deg);
        }

        .accordion-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .accordion-toggle:hover {
            background-color: #f8f9fa;
        }

        .sub-table-row {
            display: none;
            background-color: #f8f9fa;
        }

        .sub-table-row.show {
            display: table-row;
        }

        .sub-table {
            margin: 0;
            border: none;
        }

        .sub-table td {
            border-top: none;
            padding: 0.5rem;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .toggle-icon.rotated {
            transform: rotate(90deg);
        }

        .main-table {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .sub-table-container {
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #007bff;
        }
    </style>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Chart of Account') }}</li>
@endsection
@section('content')
    <div class="container">
        <h3 class="mb-4">Chart of Accounts - Opening Balances</h3>
        <!-- Excel Import -->
        <div class="mb-3">
            <label for="excelUpload" class="form-label">Import Customer Opening Balance (Excel)</label>
            <input type="file" id="excelUpload" accept=".xlsx,.xls" class="form-control w-50">
            <small class="text-muted">Expected columns: <strong>Customer</strong>, <strong>Debit</strong>,
                <strong>Credit</strong></small>
        </div>

        <!-- Excel Import -->
        <div class="mb-3">
            <label for="vendorExcel" class="form-label">Import Vendor Excel</label>
            <input type="file" id="vendorExcel" accept=".xlsx,.xls" class="form-control">
            <small class="text-muted">Expected columns: <strong>Vendor</strong>, <strong>Debit</strong>,
                <strong>Credit</strong></small>
        </div>
        <form id="openingBalanceForm" action="{{ route('accounts.chart.submit') }}" method="POST">
            @csrf
            <div id="sendDateWrapper"  class="mt-3 mb-3">
                <label for="send_date">Send Date</label>
                <input type="date" name="send_date" id="send_date" class="form-control" required value="{{ $today }}"/>
            </div>
            @php
                $totalDebit = 0;
                $totalCredit = 0;
            @endphp
            @foreach ($accounts as $type => $accountGroup)
                @php $cardId = 'card-' . md5($type); @endphp
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white card-accordion-toggle" data-target="{{ $cardId }}"
                        style="cursor:pointer;display:flex;align-items:center;">
                        <i class="fas fa-chevron-right card-toggle-icon me-2"></i>
                        <span>{{ strtoupper($accountGroup->first()->types->name) }}</span>
                    </div>
                    <div class="card-body p-2 card-accordion-body" id="{{ $cardId }}" style="display:none;">
                        <table class="table table-bordered table-sm mb-0 main-table">
                            <thead>
                                <tr>
                                    <th width="40"></th>
                                    <th>Account Name</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accountGroup as $account)
                                    @php
                                        $isReceivable = strtolower($account->name) === 'account receivables';
                                        $isPayable = strtolower($account->name) === 'account payable';
                                    @endphp
                                    <tr class="{{ $isReceivable || $isPayable ? 'accordion-toggle' : '' }}"
                                        data-target="sub-{{ $account->id }}">
                                        <td>
                                            @if ($isReceivable || $isPayable)
                                                <i class="fas fa-chevron-right toggle-icon text-primary"></i>
                                            @endif
                                        </td>
                                        @php
                                            $accountName = strtolower($account->name);
                                            $accountBalance = App\Models\Utility::getOpenAccountBalance($account->id);
                                            $isLinkedToProduct = \App\Models\ProductServiceCategory::where('purchase_account_id', $account->id)
                                                ->where('created_by', \Auth::user()->creatorId())
                                                ->exists();
                                            $isLinkedToBank = \App\Models\BankAccount::where('chart_account_id', $account->id)
                                                ->where('created_by', \Auth::user()->creatorId())
                                                ->exists();
                                        @endphp
                                        <td>
                                            @if ($isLinkedToProduct)
                                                <a href="{{ route('accounts.products', $account->id) }}" class="text-primary" target="_blank">
                                                    {{ $account->name }} <i class="fas fa-link ms-1"></i>
                                                </a>
                                            @else
                                                {{ $account->name }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isLinkedToProduct || $isLinkedToBank)
                                                <input type="number" step="0.01" name="accounts[{{ $account->id }}][debit]"
                                                class="form-control opening-debit" disabled
                                                value="{{ $accountBalance['debit'] ?? 0 }}">
                                            @else
                                                <input type="number" step="0.01" name="accounts[{{ $account->id }}][debit]"
                                                    class="form-control opening-debit"
                                                    value="{{ $accountBalance['debit'] ?? 0 }}">
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isLinkedToProduct || $isLinkedToBank)
                                                <input type="number" step="0.01"
                                                name="accounts[{{ $account->id }}][credit]"
                                                class="form-control opening-credit" disabled
                                                value="{{ $accountBalance['credit'] ?? 0 }}">
                                            @else
                                                <input type="number" step="0.01"
                                                    name="accounts[{{ $account->id }}][credit]"
                                                    class="form-control opening-credit"
                                                    value="{{ $accountBalance['credit'] ?? 0 }}">
                                                @endif
                                        </td>
                                    </tr>
                                    @if ($isReceivable)
                                        <tr class="sub-table-row" id="sub-{{ $account->id }}">
                                            <td colspan="4">
                                                <div class="sub-table-container">
                                                    <h6 class="mb-3"><i class="fas fa-users me-2"></i>Customers</h6>
                                                    <table class="table table-sm sub-table">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>→ Customer</th>
                                                                <th>Debit</th>
                                                                <th>Credit</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($customers as $customer)
                                                                @php
                                                                    $customerBalance = App\Models\Utility::getOpenAccountBalance($account->id, $customer->id);
                                                                @endphp
                                                                <tr class="bg-light"
                                                                    data-customer-name="{{ trim(strtolower($customer->name)) }}">
                                                                    <td>{{ $customer->name }}</td>
                                                                    <td>
                                                                        <input type="number" step="0.01"
                                                                            name="customers[{{ $customer->id }}][debit]"
                                                                            class="form-control  debit-input"
                                                                            value="{{ $customerBalance['debit'] ?? 0 }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" step="0.01"
                                                                            name="customers[{{ $customer->id }}][credit]"
                                                                            class="form-control  credit-input"
                                                                            value="{{ $customerBalance['credit'] ?? 0 }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($isPayable)
                                        <tr class="sub-table-row" id="sub-{{ $account->id }}">
                                            <td colspan="4">
                                                <div class="sub-table-container">
                                                    <h6 class="mb-3"><i class="fas fa-user-tie me-2"></i>Vendors</h6>
                                                    <table class="table table-sm sub-table">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>→ Vendor</th>
                                                                <th>Debit</th>
                                                                <th>Credit</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="vendors-table-body">
                                                            @foreach ($venders as $vender)
                                                                @php
                                                                    $vendorBalance = App\Models\Utility::getOpenAccountBalance($account->id, $vender->id);
                                                                @endphp
                                                                <tr class="bg-light">
                                                                    <td>{{ $vender->name }}</td>
                                                                    <td>
                                                                        <input type="number" step="0.01"
                                                                            name="venders[{{ $vender->id }}][debit]"
                                                                            class="form-control"
                                                                            value="{{ $vendorBalance['debit'] ?? 0 }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" step="0.01"
                                                                            name="venders[{{ $vender->id }}][credit]"
                                                                            class="form-control"
                                                                            value="{{ $vendorBalance['credit'] ?? 0 }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Total Debit: <span id="total-debit">{{ $totalDebit }}</span></h5>
                </div>
                <div class="col-md-6 text-end">
                    <h5>Total Credit: <span id="total-credit">{{ $totalCredit }}</span></h5>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save Opening Balances</button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Disable both debit & credit for Receivables and Payables rows
                document.querySelectorAll('tr').forEach(function (row) {
                    let accountNameCell = row.querySelector('td:nth-child(2)');
                    if (accountNameCell) {
                        let name = accountNameCell.textContent.trim().toLowerCase();

                        if (name === 'account receivables' || name === 'account payable' || name === 'Inventory') {
                            row.querySelectorAll('input[type="number"]').forEach(function (input) {
                                input.disabled = true;
                            });
                        }
                    }
                });
                // Select all rows in the main table
                document.querySelectorAll('.main-table tbody tr').forEach(function (row) {
                    let debitInput = row.querySelector('.opening-debit');
                    let creditInput = row.querySelector('.opening-credit');

                    if (debitInput && creditInput) {
                        // When typing in debit, clear & disable credit
                        debitInput.addEventListener('input', function () {
                            if (this.value && parseFloat(this.value) !== 0) {
                                creditInput.value = '';
                                creditInput.disabled = true;
                            } else {
                                creditInput.disabled = false;
                            }
                        });

                        // When typing in credit, clear & disable debit
                        creditInput.addEventListener('input', function () {
                            if (this.value && parseFloat(this.value) !== 0) {
                                debitInput.value = '';
                                debitInput.disabled = true;
                            } else {
                                debitInput.disabled = false;
                            }
                        });
                    }
                });
            });
            // Function to calculate totals
            function calculateTotals() {
                let debitTotal = 0;
                let creditTotal = 0;
                
                // Main table inputs - exclude only Account Receivables and Account Payable
                document.querySelectorAll('.main-table .opening-debit').forEach(el => {
                    // Get the account name from the same row
                    const row = el.closest('tr');
                    const accountNameCell = row.querySelector('td:nth-child(2)');
                    const accountName = accountNameCell ? accountNameCell.textContent.trim().toLowerCase() : '';
                    
                    // Skip only Account Receivables and Account Payable
                    if (accountName !== 'account receivables' && accountName !== 'account payable') {
                        debitTotal += parseFloat(el.value) || 0;
                    }
                });
                document.querySelectorAll('.main-table .opening-credit').forEach(el => {
                    // Get the account name from the same row
                    const row = el.closest('tr');
                    const accountNameCell = row.querySelector('td:nth-child(2)');
                    const accountName = accountNameCell ? accountNameCell.textContent.trim().toLowerCase() : '';
                    
                    // Skip only Account Receivables and Account Payable
                    if (accountName !== 'account receivables' && accountName !== 'account payable') {
                        creditTotal += parseFloat(el.value) || 0;
                    }
                });
                
                // Sub-table inputs (customers and vendors) - these are the individual accounts
                document.querySelectorAll('.sub-table input[name*="[debit]"]').forEach(el => {
                    debitTotal += parseFloat(el.value) || 0;
                });
                document.querySelectorAll('.sub-table input[name*="[credit]"]').forEach(el => {
                    creditTotal += parseFloat(el.value) || 0;
                });
                
                // Update the display
                document.getElementById('total-debit').textContent = debitTotal.toFixed(2);
                document.getElementById('total-credit').textContent = creditTotal.toFixed(2);
            }

            // Set up event listeners for all input fields
            function setupInputListeners() {
                // Main table inputs
                document.querySelectorAll('.main-table .opening-debit, .main-table .opening-credit').forEach(input => {
                    input.addEventListener('input', calculateTotals);
                });
                
                // Sub-table inputs (customers and vendors) - only one selector needed
                document.querySelectorAll('.sub-table input[name*="[debit]"], .sub-table input[name*="[credit]"]').forEach(input => {
                    input.addEventListener('input', calculateTotals);
                });
            }

            // Initialize when DOM is loaded
            document.addEventListener('DOMContentLoaded', function() {
                // Card accordion toggle
                document.querySelectorAll('.card-accordion-toggle').forEach(header => {
                    header.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');
                        const body = document.getElementById(targetId);
                        const icon = this.querySelector('.card-toggle-icon');
                        
                        if (body.style.display === 'none') {
                            body.style.display = 'block';
                            setTimeout(() => {
                                body.classList.add('show');
                                icon.classList.add('rotated');
                            }, 10);
                        } else {
                            body.classList.remove('show');
                            icon.classList.remove('rotated');
                            setTimeout(() => {
                                body.style.display = 'none';
                            }, 200);
                        }
                    });
                });

                // Sub-table row toggle
                document.querySelectorAll('.accordion-toggle').forEach(row => {
                    row.addEventListener('click', function(e) {
                        if (e.target.tagName === 'INPUT') return;
                        
                        const targetId = this.getAttribute('data-target');
                        const targetRow = document.getElementById(targetId);
                        const icon = this.querySelector('.toggle-icon');
                        
                        if (targetRow.style.display === 'none') {
                            targetRow.style.display = 'table-row';
                            setTimeout(() => {
                                targetRow.classList.add('show');
                                icon.classList.add('rotated');
                            }, 10);
                        } else {
                            targetRow.classList.remove('show');
                            icon.classList.remove('rotated');
                            setTimeout(() => {
                                targetRow.style.display = 'none';
                            }, 200);
                        }
                    });
                });

                // Set up all input listeners
                setupInputListeners();
                
                // Calculate initial totals
                calculateTotals();
            });

            // Form submission validation
            document.getElementById('openingBalanceForm').addEventListener('submit', function(e) {
                console.log('Form submission started');
                
                let totalDebit = 0;
                let totalCredit = 0;
                
                // Main table inputs - exclude only Account Receivables and Account Payable
                document.querySelectorAll('.main-table .opening-debit').forEach(el => {
                    // Get the account name from the same row
                    const row = el.closest('tr');
                    const accountNameCell = row.querySelector('td:nth-child(2)');
                    const accountName = accountNameCell ? accountNameCell.textContent.trim().toLowerCase() : '';
                    
                    // Skip only Account Receivables and Account Payable
                    if (accountName !== 'account receivables' && accountName !== 'account payable') {
                        totalDebit += parseFloat(el.value) || 0;
                    }
                });
                document.querySelectorAll('.main-table .opening-credit').forEach(el => {
                    // Get the account name from the same row
                    const row = el.closest('tr');
                    const accountNameCell = row.querySelector('td:nth-child(2)');
                    const accountName = accountNameCell ? accountNameCell.textContent.trim().toLowerCase() : '';
                    
                    // Skip only Account Receivables and Account Payable
                    if (accountName !== 'account receivables' && accountName !== 'account payable') {
                        totalCredit += parseFloat(el.value) || 0;
                    }
                });
                
                // Sub-table inputs (customers and vendors) - these are the individual accounts
                document.querySelectorAll('.sub-table input[name*="[debit]"]').forEach(el => {
                    totalDebit += parseFloat(el.value) || 0;
                });
                document.querySelectorAll('.sub-table input[name*="[credit]"]').forEach(el => {
                    totalCredit += parseFloat(el.value) || 0;
                });
                
                console.log('Total Debit:', totalDebit, 'Total Credit:', totalCredit);
                
                // Only validate if there are actual values entered
                if (totalDebit > 0 || totalCredit > 0) {
                    if (totalDebit.toFixed(2) !== totalCredit.toFixed(2)) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Unbalanced Entry',
                            text: `Total Debit (${totalDebit.toFixed(2)}) does not equal Total Credit (${totalCredit.toFixed(2)}). Do you want to continue anyway?`,
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Continue',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Submit the form
                                this.submit();
                            }
                        });
                    }
                } else {
                    console.log('No values entered, allowing submission');
                }
            });

            // Excel import for customers
            document.getElementById('excelUpload').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet);

                    let notFound = [];

                    rows.forEach(row => {
                        const name = row.Customer?.toString().trim().toLowerCase();
                        const debit = parseFloat(row.Debit) || 0;
                        const credit = parseFloat(row.Credit) || 0;

                        const tr = document.querySelector(`tr[data-customer-name="${name}"]`);
                        if (tr) {
                            const debitInput = tr.querySelector('.debit-input');
                            const creditInput = tr.querySelector('.credit-input');

                            if (debitInput) debitInput.value = debit.toFixed(2);
                            if (creditInput) creditInput.value = credit.toFixed(2);
                        } else {
                            notFound.push(row.Customer);
                        }
                    });

                    if (notFound.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Some customers not found',
                            html: 'These names were not matched:<br><ul>' + notFound.map(n => `<li>${n}</li>`).join('') + '</ul>',
                            confirmButtonText: 'OK'
                        });
                    }

                    calculateTotals();
                };
                reader.readAsArrayBuffer(file);
            });

            // Excel import for vendors
            document.getElementById('vendorExcel').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet);

                    let notFound = [];

                    rows.forEach(row => {
                        const name = row.Vendor?.toString().trim().toLowerCase();
                        const debit = parseFloat(row.Debit) || 0;
                        const credit = parseFloat(row.Credit) || 0;

                        const tr = [...document.querySelectorAll('#vendors-table-body tr')].find(r => {
                            const cellText = r.querySelector('td')?.textContent?.trim().toLowerCase();
                            return cellText === name;
                        });

                        if (tr) {
                            const debitInput = tr.querySelector('input[name*="[debit]"]');
                            const creditInput = tr.querySelector('input[name*="[credit]"]');

                            if (debitInput) debitInput.value = debit.toFixed(2);
                            if (creditInput) creditInput.value = credit.toFixed(2);
                        } else {
                            notFound.push(row.Vendor);
                        }
                    });

                    if (notFound.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Some vendors not found',
                            html: 'These names were not matched:<br><ul>' + notFound.map(n => `<li>${n}</li>`).join('') + '</ul>',
                            confirmButtonText: 'OK'
                        });
                    }

                    calculateTotals();
                };
                reader.readAsArrayBuffer(file);
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    </div>
@endsection