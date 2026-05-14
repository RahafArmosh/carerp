@extends('layouts.admin')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('car_accessories.index') }}">{{ __('Manufacturers') }}</a></li><li class="breadcrumb-item">{{ __('Car Accessory Search') }}</li>
@endsection
@push('script-page')
<style>
    .accessories-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #dee2e6;
    }
    
    .accessories-list .card {
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .accessories-list .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .lock-accessory-btn {
        background: linear-gradient(45deg, #007bff, #0056b3);
        border: none;
        transition: all 0.3s ease;
    }
    
    .lock-accessory-btn:hover {
        background: linear-gradient(45deg, #0056b3, #004085);
        transform: scale(1.05);
    }
    
    .search-results-section {
        background: white;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #dee2e6;
    }
    
    .alert-info {
        background: linear-gradient(45deg, #d1ecf1, #bee5eb);
        border: 1px solid #bee5eb;
        color: #0c5460;
    }
    .accessory-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .accessories-form {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #dee2e6;
    }
    
    .selected-accessory-item {
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .selected-accessory-item:hover {
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    
    .remove-accessory {
        padding: 2px 6px;
        font-size: 0.75rem;
    }
    
    .accessory-select {
        font-size: 0.875rem;
    }
    
    .saved-list-section {
        background: #e8f5e8;
        border: 1px solid #28a745;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .car-accessory-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
    }
    
    .car-info {
        background: #f8f9fa;
        padding: 8px;
        border-radius: 4px;
        margin-bottom: 8px;
        font-weight: 500;
    }
</style>
<script>
    $(document).ready(function() {
       function initSelect2() {
           $('.select2').select2({
               width: '100%',
               allowClear: true
           });
       }

       // Initialize on page load
       initSelect2();

       // Handle car selection
       $('input[name="selected_cars[]"]').on('change', function() {
           updateAccessoriesSection();
       });

       // Initialize Select2 for accessory dropdown
       $('#accessory-select').select2({
           placeholder: 'Search and select accessory...',
           allowClear: true,
           width: '100%'
       });

       // Handle add accessory button
       $('#add-accessory-btn').on('click', function() {
           addAccessoryToList();
       });

       // Handle save list button
       $('#save-list-btn').on('click', function() {
           saveAccessoriesList();
       });

       // Handle create request button
       $('#create-request-btn').on('click', function() {
           createAccessoriesRequest();
       });

       // Handle clear saved list button
       $('#clear-saved-list-btn').on('click', function() {
           clearSavedList();
       });

       // Store selected accessories with car associations
       let selectedAccessories = [];
       let savedAccessoriesList = [];

       function addAccessoryToList() {
           const accessoryId = $('#accessory-select').val();
           const quantity = $('#accessory-qty').val();
           const price = $('#accessory-price').val();
           const selectedCars = $('input[name="selected_cars[]"]:checked').map(function() {
               return {
                   id: $(this).val(),
                   name: $(this).closest('tr').find('td:nth-child(3)').text().trim()
               };
           }).get();

           if (!accessoryId) {
               alert('Please select an accessory.');
               return;
           }

           if (!quantity || quantity <= 0) {
               alert('Please enter a valid quantity.');
               return;
           }

           if (selectedCars.length === 0) {
               alert('Please select at least one car.');
               return;
           }

           const selectedOption = $('#accessory-select option:selected');
           const productId = selectedOption.data('product-id');
           const label = selectedOption.data('label');
           const available = selectedOption.data('available');

           // Add accessory for each selected car
           selectedCars.forEach(car => {
               selectedAccessories.push({
                   accessoryId: accessoryId,
                   productId: productId,
                   label: label,
                   quantity: parseInt(quantity),
                   price: price,
                   available: available,
                   carId: car.id,
                   carName: car.name
               });
           });

           updateSelectedAccessoriesList();
           resetAccessoryForm();
       }

       function updateSelectedAccessoriesList() {
           const container = $('#selected-accessories-list');
           container.empty();

           if (selectedAccessories.length === 0) {
               $('#save-list-btn').hide();
               return;
           }

           $('#save-list-btn').show();

           // Group by car for better display
           const groupedByCar = {};
           selectedAccessories.forEach(item => {
               if (!groupedByCar[item.carId]) {
                   groupedByCar[item.carId] = [];
               }
               groupedByCar[item.carId].push(item);
           });

           Object.keys(groupedByCar).forEach(carId => {
               const carItems = groupedByCar[carId];
               const firstItem = carItems[0];
               
               const carSection = $(`
                   <div class="car-accessory-item">
                       <div class="car-info">
                           <i class="fas fa-car"></i> ${firstItem.carName}
                       </div>
                   </div>
               `);
               
               carItems.forEach((item, index) => {
                   const itemHtml = `
                       <div class="row align-items-center mb-2">
                           <div class="col-6">
                               <small class="text-muted">${item.label}</small>
                               <br>
                               <small class="text-info">
                                   <i class="fas fa-boxes"></i> Available: ${item.available}
                               </small>
                           </div>
                           <div class="col-2">
                               <small class="text-success">Qty: ${item.quantity}</small>
                           </div>
                           <div class="col-2">
                               <small class="text-primary">$${item.price || '0.00'}</small>
                           </div>
                           <div class="col-2">
                               <button type="button" class="btn btn-sm btn-danger remove-accessory" 
                                       data-car-id="${item.carId}" data-accessory-id="${item.accessoryId}">
                                   <i class="fas fa-times"></i>
                               </button>
                           </div>
                       </div>
                   `;
                   carSection.append(itemHtml);
               });
               
               container.append(carSection);
           });
       }

       function resetAccessoryForm() {
           $('#accessory-select').val('').trigger('change');
           $('#accessory-qty').val('1');
           $('#accessory-price').val('');
       }

       // Handle remove accessory
       $(document).on('click', '.remove-accessory', function() {
           const carId = $(this).data('car-id');
           const accessoryId = $(this).data('accessory-id');
           
           selectedAccessories = selectedAccessories.filter(item => 
               !(item.carId === carId && item.accessoryId === accessoryId)
           );
           
           updateSelectedAccessoriesList();
       });

       function saveAccessoriesList() {
           if (selectedAccessories.length === 0) {
               alert('No accessories to save.');
               return;
           }

           $.ajax({
               url: '{{ route("car_accessories.saveList") }}',
               method: 'POST',
               data: {
                   _token: '{{ csrf_token() }}',
                   accessories: selectedAccessories
               },
               success: function(response) {
                   if (response.success) {
                       showMessage('Accessories list saved successfully!', 'success');
                       savedAccessoriesList = [...selectedAccessories];
                       selectedAccessories = [];
                       updateSelectedAccessoriesList();
                       updateSavedListDisplay();
                       $('#save-list-btn').hide();
                       
                       // Show message that they can continue adding more
                       $('.accessories-section .alert-info').html(`
                           <i class="fas fa-check-circle text-success"></i> 
                           List saved! You can continue adding more accessories or create the request now.
                       `);
                   } else {
                       showMessage('Error saving list: ' + response.message, 'danger');
                   }
               },
               error: function() {
                   showMessage('An error occurred while saving the list.', 'danger');
               }
           });
       }

       function updateSavedListDisplay() {
           const container = $('#saved-accessories-list');
           container.empty();

           if (savedAccessoriesList.length === 0) {
               $('#saved-list-section').hide();
               return;
           }

           $('#saved-list-section').show();

           // Group by car for better display
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
               
               const carSection = $(`
                   <div class="car-accessory-item">
                       <div class="car-info">
                           <i class="fas fa-car"></i> ${firstItem.carName}
                       </div>
                   </div>
               `);
               
               carItems.forEach((item, index) => {
                   const itemHtml = `
                       <div class="row align-items-center mb-2">
                           <div class="col-8">
                               <small class="text-muted">${item.label}</small>
                               <br>
                               <small class="text-info">
                                   <i class="fas fa-boxes"></i> Available: ${item.available}
                               </small>
                           </div>
                           <div class="col-2">
                               <small class="text-success">Qty: ${item.quantity}</small>
                           </div>
                           <div class="col-2">
                               <small class="text-primary">$${item.price || '0.00'}</small>
                           </div>
                       </div>
                   `;
                   carSection.append(itemHtml);
               });
               
               container.append(carSection);
           });
       }

       function createAccessoriesRequest() {
           if (savedAccessoriesList.length === 0) {
               alert('No saved accessories to create request for.');
               return;
           }

           $.ajax({
               url: '{{ route("car_accessories.createRequestFromList") }}',
               method: 'POST',
               data: {
                   _token: '{{ csrf_token() }}',
                   accessories: savedAccessoriesList
               },
               success: function(response) {
                   if (response.success) {
                       showMessage('Accessories request created successfully!', 'success');
                       setTimeout(function() {
                           window.location.href = response.redirect_url;
                       }, 2000);
                   } else {
                       showMessage('Error creating request: ' + response.message, 'danger');
                   }
               },
               error: function() {
                   showMessage('An error occurred while creating the request.', 'danger');
               }
           });
       }

       function clearSavedList() {
           if (savedAccessoriesList.length === 0) {
               alert('No saved list to clear.');
               return;
           }

           if (confirm('Are you sure you want to clear the saved accessories list? This action cannot be undone.')) {
               $.ajax({
                   url: '{{ route("car_accessories.clearSavedList") }}',
                   method: 'POST',
                   data: {
                       _token: '{{ csrf_token() }}'
                   },
                   success: function(response) {
                       if (response.success) {
                           showMessage('Saved accessories list cleared successfully!', 'success');
                           savedAccessoriesList = [];
                           updateSavedListDisplay();
                           $('#saved-list-section').hide();
                           
                           // Reset the accessories section message
                           $('.accessories-section .alert-info').html(`
                               <i class="fas fa-info-circle"></i> 
                               Select cars to add accessories to the list
                           `);
                       } else {
                           showMessage('Error clearing list: ' + response.message, 'danger');
                       }
                   },
                   error: function() {
                       showMessage('An error occurred while clearing the list.', 'danger');
                   }
               });
           }
       }

       function updateAccessoriesSection() {
           const selectedCars = $('input[name="selected_cars[]"]:checked').length;
           if (selectedCars > 0) {
               $('.accessories-section').show();
               $('.accessories-section .alert-info').text(`Selected ${selectedCars} car(s). You can now add accessories to link with them.`);
           } else {
               $('.accessories-section').hide();
           }
       }

       function showMessage(message, type) {
           const container = $('#message-container');
           const alert = $('#message-alert');
           const text = $('#message-text');
           
           // Set message text and alert type
           text.text(message);
           alert.removeClass().addClass('alert alert-' + type);
           
           // Show the message
           container.show();
           
           // Auto-hide after 5 seconds
           setTimeout(function() {
               container.hide();
           }, 5000);
       }

       // Initialize accessories section visibility
       updateAccessoriesSection();
       
       // Load saved list if exists
       @if(session('saved_accessories_list'))
           savedAccessoriesList = @json(session('saved_accessories_list'));
           updateSavedListDisplay();
       @endif
   });
</script>
@endpush
@section('content')
<div class="container">
    <h3>Search Cars</h3>

    {{-- Success/Error Messages --}}
    <div id="message-container" style="display: none;" class="mb-3">
        <div class="alert" id="message-alert">
            <span id="message-text"></span>
        </div>
    </div>

    <form action="{{ route('car_accessories.doSearch') }}" method="POST" class="mb-4">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-2">
                <label>Invoice</label>
                <select name="invoice_id" class="form-select select2">
                    <option value="">All</option>
                    @isset($invoices)
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}">{{ Auth::user()->invoiceNumberFormat($inv->invoice_id) }}</option>
                    @endforeach
                    @endisset
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label>Bill</label>
                <select name="bill_id" class="form-select select2">
                    <option value="">All</option>
                    @isset($bills)
                    @foreach($bills as $bill)
                        <option value="{{ $bill->id }}">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</option>
                    @endforeach
                    @endisset
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label>VINs (multi)</label>
                <input type="text" name="vins" class="form-control" placeholder="Enter VINs, separated by space">
            </div>
            <div class="col-md-4 mb-2">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-select select2">
                    <option value="">All</option>
                    @isset($warehouses)
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                    @endisset
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label>Customer</label>
                <select name="customer_id" class="form-select select2">
                    <option value="">All</option>
                    @isset($customers)
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                    @endisset
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label>Vendor</label>
                <select name="vender_id" class="form-select select2">
                    <option value="">All</option>
                    @isset($vendors)
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                    @endisset
                </select>
            </div>
        </div>
        <button class="btn btn-primary">Search</button>
        <a href="{{ route('car_accessories.search') }}" class="btn btn-light ms-2">Clear</a>
    </form>

    {{-- Search Results --}}
    @isset($cars)
        <div class="row">
            <div class="col-md-8 search-results-section">
                <h5>Search Results - Cars</h5>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> Only cars with completed purchase status are shown in the results.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>VIN / Product No</th>
                                <th>Product</th>
                                <th>Warehouse</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cars as $car)
                            <tr>
                                <td><input type="checkbox" name="selected_cars[]" value="{{ $car->id }}"></td>
                                <td>{{ $car->product_no }}</td>
                                <td>
                                    @if($car->category_name || $car->brand_name || $car->sub_brand_name || $car->product_name || $car->product_sku)
                                        {{ $car->category_name ?? '' }} / {{ $car->brand_name ?? '' }} / {{ $car->sub_brand_name ?? '' }} / {{ $car->product_name ?? '' }} / {{ $car->product_sku ?? '' }}
                                    @else
                                        {{ $car->product_name ?? 'N/A' }}
                                    @endif
                                </td>
                                <td>{{ $car->warehouse_name }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">No results found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Accessories Section --}}
            <div class="col-md-4">
                <div class="accessories-section" style="display: none;">
                    <h5>Add Accessories to List</h5>
                    <div class="alert alert-info">
                        Select cars to add accessories to the list
                    </div>
                    
                    <div class="accessories-form">
                        <div class="row mb-2">
                            <div class="col-12">
                                <label class="form-label small">Accessory Product</label>
                                <select class="form-select form-select-sm accessory-select" id="accessory-select">
                                    <option value="">Search and select accessory...</option>
                                    @if(isset($accessories) && $accessories->count() > 0)
                                        @foreach($accessories as $accessory)
                                            <option value="{{ $accessory->product_id }}" 
                                                    data-product-id="{{ $accessory->product_id }}"
                                                    data-label="{{ $accessory->label }}"
                                                    data-available="0">
                                                {{ $accessory->label }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label small">Quantity</label>
                                <input type="number" class="form-control form-control-sm" id="accessory-qty" placeholder="Qty" min="1" value="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Sell Price</label>
                                <input type="number" class="form-control form-control-sm" id="accessory-price" placeholder="Price" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-sm btn-success" id="add-accessory-btn">
                                <i class="fas fa-plus"></i> Add to List
                            </button>
                        </div>
                        
                        <div class="selected-accessories-list" id="selected-accessories-list">
                            <!-- Selected accessories will be displayed here -->
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-warning" id="save-list-btn" style="display: none;">
                                <i class="fas fa-save"></i> Save List
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Saved List Section --}}
        <div id="saved-list-section" class="saved-list-section" style="display: none;">
            <h5><i class="fas fa-list"></i> Saved Accessories List</h5>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Your accessories list has been saved. You can continue adding more items or create the request now.
            </div>
            
            <div id="saved-accessories-list">
                <!-- Saved accessories will be displayed here -->
            </div>
            
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary" id="create-request-btn">
                    <i class="fas fa-paper-plane"></i> Create Accessories Request
                </button>
                <button type="button" class="btn btn-secondary" id="clear-saved-list-btn">
                    <i class="fas fa-trash"></i> Clear Saved List
                </button>
            </div>
        </div>

        {{-- Navigation --}}
        @if($cars->count())
            <div class="mt-3">
                <a href="{{ route('car_accessories.clearSession') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Done
                </a>
            </div>
        @endif
    @endisset
</div>
@endsection
