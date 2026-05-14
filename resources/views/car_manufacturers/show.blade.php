@extends('layouts.admin')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('car_accessories.index') }}">{{ __('Manufacturers') }}</a></li><li class="breadcrumb-item">{{ __('Car Accessory Request') }}</li>
@endsection
@section('content')
<!-- SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container">
    <h3>Car Accessory Request: {{ $car_accessory->request_no }}</h3>

    <div class="row mb-3">
        <div class="col-md-3"><strong>Date:</strong> {{ $car_accessory->request_date }}</div>
        <div class="col-md-3"><strong>Status:</strong> {{ ucfirst($car_accessory->status) }}</div>
                <div class="col-md-6 text-end">
            @if($car_accessory->status !== 'assigned' && $car_accessory->status !== 'on_hold')
                <form action="{{ route('car_accessories.update', $car_accessory->id) }}" method="POST" class="d-inline-flex gap-2">
                    @csrf
                    @method('PUT')
                    <select name="status" class="form-select form-select-sm" style="width:auto">
                        <option value="pending" {{ $car_accessory->status=='pending'?'selected':'' }}>Pending</option>
                        <option value="approved" {{ $car_accessory->status=='approved'?'selected':'' }}>Approved</option>
                        <option value="rejected" {{ $car_accessory->status=='rejected'?'selected':'' }}>Rejected</option>
                        {{-- <option value="on_hold" {{ $car_accessory->status=='on_hold'?'selected':'' }}>On Hold</option> --}}
                    </select>
                    <button class="btn btn-sm btn-primary">Update</button>
                </form>
            @endif
            
            @if($car_accessory->status === 'on_hold')
                <button type="button" class="btn btn-sm btn-success" onclick="assignStockWithDate({{ $car_accessory->id }})">Assign Stock</button>
            @elseif($car_accessory->status === 'assigned')
                <span class="badge bg-success fs-6">Assigned</span>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Car No</th>
                    <th>Car</th>
                    <th>Accessory No</th>
                    <th>Accessory</th>
                    <th>Quantity</th>
                    @if($car_accessory->status === 'approved')
                    <th>Available</th>
                    <th>Need to Purchase</th>
                    <th>Vendor</th>
                    <th>Actions</th>
                    @endif
                    @if($car_accessory->status === 'on_hold')
                    <th>Actions</th>
                    @endif
                    <th>Sell Price</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                @foreach($car_accessory->items as $it)
                <tr data-item-id="{{ $it->product_id }}">
                    <td>{{ $it->car->product_no ?? '-' }}</td>
                    <td>
                        @php($ps = optional($it->car)->productService)
                        @if($ps)
                            {{ optional($ps->category)->name }} / {{ optional($ps->brand)->name }} / {{ optional($ps->subBrand)->name }} / {{ $ps->name }} / {{ $ps->sku }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($it->accessory_id)
                            {{ $it->accessory->product_no ?? 'N/A' }}
                            <br><small class="text-success">✓ Assigned</small>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if($it->accessory_id)
                            @php($aps = optional($it->accessory)->productService)
                            @if($aps)
                                {{ optional($aps->category)->name }} / {{ optional($aps->brand)->name }} / {{ optional($aps->subBrand)->name }} / {{ $aps->name }} / {{ $aps->sku }}
                            @else
                                -
                            @endif
                        @else
                            @php($pps = $it->product)
                            @if($pps)
                                {{ optional($pps->category)->name }} / {{ optional($pps->brand)->name }} / {{ optional($pps->subBrand)->name }} / {{ $pps->name }} / {{ $pps->sku }}
                            @else
                                Product ID: {{ $it->product_id }}
                            @endif
                        @endif
                    </td>
                    <td>{{ $it->quantity }}</td>
                    @if($car_accessory->status === 'on_hold')
                    <td>
                        @if($it->accessory_id)
                            <form action="{{ route('car_accessories.items.unhold', $it->id) }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unhold this item? This will remove the link between the car and accessory.')">
                                    Unhold
                                </button>
                            </form>
                        @else
                            <span class="badge bg-secondary">No held accessory</span>
                        @endif
                    </td>
                    @endif
                    @if($car_accessory->status === 'approved')
                    <td>
                        @if($it->accessory_id)
                            <span class="text-success">{{ $it->quantity }}</span>
                        @else
                            {{ $availableByItemId[$it->id] ?? 0 }}
                        @endif
                    </td>
                    <td>
                        @if($it->accessory_id)
                            <span class="text-success">0</span>
                        @else
                            {{ $neededByItemId[$it->id] ?? 0 }}
                        @endif
                    </td>
                    <td>
                        <select class="form-select form-select-sm vendor-select" name="vendor_id" data-row-id="{{ $it->id }}">
                            <option value="">Select</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        @if($it->accessory_id)
                            @if($car_accessory->status === 'on_hold')
                                <form action="{{ route('car_accessories.items.unhold', $it->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unhold this item? This will remove the link between the car and accessory.')">
                                        Unhold
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-success">Already Assigned</span>
                            @endif
                        @else
                            @php($need = $neededByItemId[$it->id] ?? 0)
                            @if($need > 0)
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary create-bill-single-btn"
                                        data-item-id="{{ $it->product_id }}"
                                        data-qty="{{ $need }}"
                                        data-row-id="{{ $it->id }}"
                                        disabled>
                                    Create Bill
                                </button>
                            @else
                                @php($available = $availableByItemId[$it->id] ?? 0)
                                @if($available > 0)
                                    <form action="{{ route('car_accessories.items.hold', $it->id) }}" method="POST" class="d-inline-flex align-items-center gap-2">
                                        @csrf
                                        <input type="number" name="quantity" class="form-control form-control-sm" style="width:90px" min="1" max="{{ $available }}" value="{{ $it->quantity }}">
                                        <button type="submit" class="btn btn-sm btn-warning">Hold</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">No Stock Available</span>
                                @endif
                            @endif
                        @endif
                    </td>
                    @endif
                    <td>{{ $it->sell_price }}</td>
                    <td>
                        <form method="POST" action="{{ route('car_accessories.items.delete', $it->id) }}" id="delete-form-{{ $it->id }}" class="d-inline-flex align-items-center gap-2">
                            @csrf
                            @method('DELETE')
                            <input type="date" name="delete_date" id="delete-date-{{ $it->id }}" class="form-control form-control-sm" style="width:150px" min="{{ date('Y-m-d') }}" required>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirmDeleteItem(event, {{ $it->id }})">
                                <i class="ti ti-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($car_accessory->status === 'approved')
        <div class="row mt-3">
            <div class="col-md-12 text-end">
                <button type="button" id="create-bill-all-btn" class="btn btn-primary" style="display: none;">
                    Create Bill for All Items
                </button>
            </div>
        </div>
    @endif

    {{-- Create Bill from Accessories List Section --}}
    @if(session('saved_accessories_list'))
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Create Bill from Saved Accessories List</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You have a saved accessories list. You can create bills for these items.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Select Vendor</label>
                                <select class="form-select" id="accessories-vendor-select">
                                    <option value="">Choose vendor...</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Select Accessories</label>
                                <div id="accessories-checkboxes">
                                    <!-- Accessories checkboxes will be populated here -->
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="create-bill-from-accessories-btn" class="btn btn-success" disabled>
                                    <i class="fas fa-file-invoice"></i> Create Bill
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <a href="{{ route('car_accessories.index') }}" class="btn btn-light">Back</a>
</div>
<script>
// Function to assign stock with date picker - defined globally
window.assignStockWithDate = function(requestId) {
    Swal.fire({
        title: 'Select Send Date',
        html: `
            <div class="mb-3">
                <label for="send_date" class="form-label">Send Date:</label>
                <input type="date" id="send_date" class="form-control" min="{{ date('Y-m-d') }}" required>
            </div>
            <div class="alert alert-info">
                <small><i class="fas fa-info-circle"></i> Please select a send date for this assignment. The date must not be before any purchase date of the items.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Assign Stock',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            const sendDate = document.getElementById('send_date').value;
            if (!sendDate) {
                Swal.showValidationMessage('Please select a send date');
                return false;
            }
            return sendDate;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const sendDate = result.value;
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Assignment',
                text: `Are you sure you want to assign stock with send date: ${sendDate}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Assign Stock',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Assigning stock and creating ledger entries...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Create a form and submit it with the selected date
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("car_accessories.assign", ":id") }}'.replace(':id', requestId);
                    
                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfToken);
                    
                    // Add send date
                    const sendDateInput = document.createElement('input');
                    sendDateInput.type = 'hidden';
                    sendDateInput.name = 'send_date';
                    sendDateInput.value = sendDate;
                    form.appendChild(sendDateInput);
                    
                    // Submit the form
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', function(){
    const createBillBtn = document.getElementById('create-bill-all-btn');
    const vendorSelects = document.querySelectorAll('.vendor-select');
    const createBillSingleBtns = document.querySelectorAll('.create-bill-single-btn');
    
    // Function to check if any vendor is selected and if there are items to purchase
    function checkVendorSelection() {
        let selectedVendor = null;
        let hasItemsToPurchase = false;
        let allSameVendor = true;
        
        vendorSelects.forEach(function(select) {
            const vendorId = select.value;
            const row = select.closest('tr');
            const createBillBtn = row.querySelector('.create-bill-single-btn');
            
            if (createBillBtn) {
                hasItemsToPurchase = true;
                if (selectedVendor === null) {
                    selectedVendor = vendorId;
                } else if (selectedVendor !== vendorId) {
                    allSameVendor = false;
                }
            }
        });
        
        if (hasItemsToPurchase && selectedVendor && allSameVendor) {
            createBillBtn.style.display = 'inline-block';
        } else {
            createBillBtn.style.display = 'none';
        }
    }
    
            // Function to handle individual vendor selection
        function handleVendorSelection(select) {
            const row = select.closest('tr');
            const createBillBtn = row.querySelector('.create-bill-single-btn');
            const vendorId = select.value;
            
            if (createBillBtn) {
                if (vendorId) {
                    createBillBtn.disabled = false;
                    createBillBtn.classList.remove('btn-outline-primary');
                    createBillBtn.classList.add('btn-primary');
                    
                    // Check if this is the first vendor selection and ask about applying to all
                    const allVendorSelects = document.querySelectorAll('.vendor-select');
                    const selectedVendors = Array.from(allVendorSelects).map(s => s.value).filter(v => v !== '');
                    
                    if (selectedVendors.length === 1 && vendorId) {
                        // This is the first vendor selection, ask if user wants to apply to all
                        const vendorName = select.options[select.selectedIndex].text;
                        const remainingItems = allVendorSelects.length - 1;
                        
                        // Get details about remaining items to show in confirmation
                        const remainingItemDetails = [];
                        allVendorSelects.forEach((otherSelect, index) => {
                            if (otherSelect !== select && otherSelect.value === '') {
                                const row = otherSelect.closest('tr');
                                const productName = row.querySelector('td:nth-child(4)').textContent.trim();
                                const quantity = row.querySelector('td:nth-child(5)').textContent.trim();
                                remainingItemDetails.push(`${productName} (Qty: ${quantity})`);
                            }
                        });
                        
                        // Use SweetAlert2 for confirmation
                        Swal.fire({
                            title: 'Apply Same Vendor?',
                            text: `Would you like to apply vendor "${vendorName}" to all ${remainingItems} remaining items?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Apply to All',
                            cancelButtonText: 'No, Keep Separate',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Apply the same vendor to all remaining items
                                allVendorSelects.forEach(otherSelect => {
                                    if (otherSelect !== select && otherSelect.value === '') {
                                        otherSelect.value = vendorId;
                                        // Trigger the change event to update the UI
                                        otherSelect.dispatchEvent(new Event('change'));
                                    }
                                });
                                
                                // Show success message with SweetAlert
                                Swal.fire({
                                    title: 'Success!',
                                    text: `Vendor "${vendorName}" has been applied to all remaining items. You can now create a single bill for all items.`,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                } else {
                    createBillBtn.disabled = true;
                    createBillBtn.classList.remove('btn-primary');
                    createBillBtn.classList.add('btn-outline-primary');
                }
            }
        }
    
    // Add event listeners to vendor selects
    vendorSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            handleVendorSelection(this);
            checkVendorSelection();
        });
    });
    
    // Handle individual create bill button clicks
    createBillSingleBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const vendorSelect = row.querySelector('.vendor-select');
            const vendorId = vendorSelect.value;
            
            if (!vendorId) {
                Swal.fire({
                    title: 'Vendor Required',
                    text: 'Please select a vendor first.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            const itemId = this.getAttribute('data-item-id');
            const qty = this.getAttribute('data-qty');
            
            if (itemId && qty) {
                const baseUrl = '{{ url("bill/create-from-request") }}';
                const url = `${baseUrl}/${vendorId}?items[${itemId}]=${qty}`;
                window.open(url, '_blank');
            }
        });
    });
    
    // Handle create bill for all items button click
    createBillBtn.addEventListener('click', function() {
        const selectedVendor = vendorSelects[0].value;
        const itemsToPurchase = [];
        const productQuantities = {}; // Track quantities per product
        
        vendorSelects.forEach(function(select) {
            const row = select.closest('tr');
            const createBillBtn = row.querySelector('.create-bill-single-btn');
            
            if (createBillBtn && select.value === selectedVendor) {
                const itemId = createBillBtn.getAttribute('data-item-id');
                const qty = parseInt(createBillBtn.getAttribute('data-qty'));
                
                if (itemId && qty > 0) {
                    // If this product is already in our list, add to its quantity
                    if (productQuantities[itemId]) {
                        productQuantities[itemId] += qty;
                    } else {
                        productQuantities[itemId] = qty;
                    }
                }
            }
        });
        
        // Convert the aggregated quantities to the format expected by the bill creation
        Object.keys(productQuantities).forEach(productId => {
            itemsToPurchase.push({
                item_id: productId,
                qty: productQuantities[productId]
            });
        });
        
        if (itemsToPurchase.length > 0) {
            const baseUrl = '{{ url("bill/create-from-request") }}';
            const prefillParams = itemsToPurchase.map(item => 
                `items[${item.item_id}]=${item.qty}`
            ).join('&');
            const url = `${baseUrl}/${selectedVendor}?${prefillParams}`;
            
            // Show confirmation with details
            const itemDetails = itemsToPurchase.map(item => {
                // Try to get product name from the table
                const productRow = document.querySelector(`tr[data-item-id="${item.item_id}"]`);
                const productName = productRow ? 
                    productRow.querySelector('td:nth-child(4)').textContent.trim() : 
                    `Product ID ${item.item_id}`;
                return `${productName}: ${item.qty} units`;
            }).join('\n');
            
            // Use SweetAlert2 for confirmation
            Swal.fire({
                title: 'Create Bill for All Items?',
                text: `Create bill for vendor with the following all items`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Create Bill',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(url, '_blank');
                }
            });
        } else {
            Swal.fire({
                title: 'No Items to Purchase',
                text: 'No items to purchase found. Please select vendors for items that need to be purchased.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        }
    });
    
    // Initial check
    checkVendorSelection();
    
    // Handle accessories list bill creation
    const accessoriesVendorSelect = document.getElementById('accessories-vendor-select');
    const accessoriesCheckboxes = document.getElementById('accessories-checkboxes');
    const createBillFromAccessoriesBtn = document.getElementById('create-bill-from-accessories-btn');
    
    if (accessoriesVendorSelect && accessoriesCheckboxes && createBillFromAccessoriesBtn) {
        // Load saved accessories list from session
        const savedAccessoriesList = @json(session('saved_accessories_list') ?? []);
        
        // Populate accessories checkboxes
        function populateAccessoriesCheckboxes() {
            accessoriesCheckboxes.innerHTML = '';
            
            if (savedAccessoriesList.length === 0) {
                accessoriesCheckboxes.innerHTML = '<p class="text-muted">No saved accessories found.</p>';
                return;
            }
            
            // Group by car for better organization
            const groupedByCar = {};
            savedAccessoriesList.forEach(item => {
                if (!groupedByCar[item.carId]) {
                    groupedByCar[item.carId] = [];
                }
                groupedByCar[item.carId].push(item);
            });
            
            Object.keys(groupedByCar).forEach(carId => {
                const carItems = groupedByCar[carId];
                const firstItem = carItems[0];
                
                const carSection = document.createElement('div');
                carSection.className = 'mb-3 p-2 border rounded';
                carSection.innerHTML = `
                    <div class="fw-bold text-primary mb-2">
                        <i class="fas fa-car"></i> ${firstItem.carName}
                    </div>
                `;
                
                carItems.forEach((item, index) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'form-check ms-3 mb-2';
                    itemDiv.innerHTML = `
                        <input class="form-check-input accessory-checkbox" type="checkbox" 
                               value="${item.productId}" 
                               id="accessory_${item.carId}_${index}"
                               data-quantity="${item.quantity}"
                               data-label="${item.label}">
                        <label class="form-check-label" for="accessory_${item.carId}_${index}">
                            ${item.label} (Qty: ${item.quantity})
                        </label>
                    `;
                    carSection.appendChild(itemDiv);
                });
                
                accessoriesCheckboxes.appendChild(carSection);
            });
        }
        
        // Handle vendor selection change
        accessoriesVendorSelect.addEventListener('change', function() {
            const vendorId = this.value;
            const checkedAccessories = document.querySelectorAll('.accessory-checkbox:checked');
            
            if (vendorId && checkedAccessories.length > 0) {
                createBillFromAccessoriesBtn.disabled = false;
            } else {
                createBillFromAccessoriesBtn.disabled = true;
            }
        });
        
        // Handle accessory checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('accessory-checkbox')) {
                const vendorId = accessoriesVendorSelect.value;
                const checkedAccessories = document.querySelectorAll('.accessory-checkbox:checked');
                
                if (vendorId && checkedAccessories.length > 0) {
                    createBillFromAccessoriesBtn.disabled = false;
                } else {
                    createBillFromAccessoriesBtn.disabled = true;
                }
            }
        });
        
        // Handle create bill button click
        createBillFromAccessoriesBtn.addEventListener('click', function() {
            const vendorId = accessoriesVendorSelect.value;
            const checkedAccessories = document.querySelectorAll('.accessory-checkbox:checked');
            
            if (!vendorId) {
                Swal.fire({
                    title: 'Vendor Required',
                    text: 'Please select a vendor first.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if (checkedAccessories.length === 0) {
                Swal.fire({
                    title: 'No Accessories Selected',
                    text: 'Please select at least one accessory.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Collect selected accessories with quantities
            const selectedItems = [];
            const productQuantities = {};
            
            checkedAccessories.forEach(checkbox => {
                const productId = checkbox.value;
                const quantity = parseInt(checkbox.dataset.quantity);
                const label = checkbox.dataset.label;
                
                if (productQuantities[productId]) {
                    productQuantities[productId] += quantity;
                } else {
                    productQuantities[productId] = quantity;
                }
            });
            
            // Convert to the format expected by bill creation
            Object.keys(productQuantities).forEach(productId => {
                selectedItems.push({
                    item_id: productId,
                    qty: productQuantities[productId]
                });
            });
            
            // Build the bill creation URL
            const baseUrl = '{{ url("bill/create-from-request") }}';
            const prefillParams = selectedItems.map(item => 
                `items[${item.item_id}]=${item.qty}`
            ).join('&');
            const url = `${baseUrl}/${vendorId}?${prefillParams}`;
            
            // Show confirmation with details
            const itemDetails = selectedItems.map(item => {
                // Try to get product name from the checkboxes
                const checkbox = document.querySelector(`.accessory-checkbox[value="${item.item_id}"]`);
                const productName = checkbox ? checkbox.dataset.label : `Product ID ${item.item_id}`;
                return `${productName}: ${item.qty} units`;
            }).join('\n');
            
            // Use SweetAlert2 for confirmation
            Swal.fire({
                title: 'Create Bill?',
                text: `Create bill for vendor with the following items:\n\n${itemDetails}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Create Bill',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(url, '_blank');
                }
            });
        });
        
        // Initialize accessories checkboxes
        populateAccessoriesCheckboxes();
    }
    
    // Function to confirm and delete an item
    window.confirmDeleteItem = function(event, itemId) {
        event.preventDefault();
        
        // Get the delete date from the form
        const deleteDateInput = document.getElementById('delete-date-' + itemId);
        const deleteDate = deleteDateInput.value;
        
        if (!deleteDate) {
            Swal.fire({
                title: '{{ __('Date Required') }}',
                text: '{{ __('Please select a delete date first.') }}',
                icon: 'warning',
                confirmButtonText: '{{ __('OK') }}'
            });
            return false;
        }
        
        Swal.fire({
            title: '{{ __('Delete Item') }}',
            html: `{{ __('Are you sure you want to delete this item?') }}<br><small class="text-muted">{{ __('Delete Date:') }} ${deleteDate}</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __('Yes, delete it!') }}',
            cancelButtonText: '{{ __('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form
                document.getElementById('delete-form-' + itemId).submit();
            }
        });
        
        return false;
    };
    
    
});
</script>
@endsection


