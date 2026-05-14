<form method="POST" action="productservice" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['productservice']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">Name<span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sku" class="form-label">SKU<span class="text-danger">*</span></label>
                    <input type="text" id="sku" name="sku" class="form-control" required>

                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_price_base" class="form-label">Sale Price<span class="text-danger">*</span></label>
                    <input type="number" id="sale_price_base" name="sale_price_base" class="form-control" required
                        step="0.01">

                </div>
            </div>
            {{-- <div class="form-group col-md-6">
                {{ Form::label('sale_chartaccount_id', __('Income Account'),['class'=>'form-label']) }}
                {{ Form::select('sale_chartaccount_id',$incomeChartAccounts,null, array('class' => 'form-control
                select','required'=>'required')) }}
            </div> --}}
            <div class="col-md-6">
                <div class="form-group">
                    <label for="purchase_price" class="form-label">Purchase Price<span
                            class="text-danger">*</span></label>
                    <input type="number" id="purchase_price" name="purchase_price" class="form-control" required
                        step="0.01">

                </div>
            </div>
            {{-- <div class="form-group col-md-6">
                {{ Form::label('expense_chartaccount_id', __('Expense Account'),['class'=>'form-label']) }}
                {{ Form::select('expense_chartaccount_id',$expenseChartAccounts,null, array('class' => 'form-control
                select','required'=>'required')) }}
            </div> --}}

            <div class="form-group col-md-6">
                <label for="tax" class="form-label">Tax</label>
                <select name="tax_id[]" id="choices-multiple1" class="form-control select2" multiple
                    data-placeholder="{{ __('Select Tax') }}">
                    <option value=""></option>
                    @foreach ($tax as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    
                    <label for="sale_price" class="form-label">Sale Price With VAT<span
                            class="text-danger">*</span></label>
                    <input type="number" id="sale_price" name="sale_price" class="form-control" readonly
                        step="0.01">

                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label">Category<span class="text-danger">*</span></label>
                <select name="category_id" id="categorySelect" class="form-control select" required>
                    <option value="">Select Category</option>
                    @foreach ($category as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>


                <div class=" text-xs">
                    {{ __('Please add constant category. ') }}<a
                        href="{{ route('product-category.index') }}"><b>{{ __('Add Category') }}</b></a>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="brand_id" class="form-label">Brand<span class="text-danger">*</span></label>
                <select name="brand_id" id="brandSelect" class="form-control select">
                    <option value="">Select Brand</option>
                    @foreach ($brands as $id => $brand)
                        <option value="{{ $id }}">{{ $brand }}</option>
                    @endforeach
                </select>

                <div class=" text-xs">
                    {{ __('Please add constant brand.') }}<a
                        href="{{ route('brand.index') }}"><b>{{ __('Add Brand') }}</b></a>
                </div>
            </div>

            <div class="form-group col-md-6">
                <label for="sub_brand_id" class="form-label">{{ __('Model') }}<span class="text-danger">*</span></label>
                <select name="sub_brand_id" id="SubBrandSelect" class="form-control select">
                    <option value="">{{ __('Select Model') }}</option>
                    @foreach ($subBrands as $subBrand)
                        <option value="{{ $subBrand->id }}">{{ $subBrand->name }}</option>
                    @endforeach
                </select>


                <div class=" text-xs">
                    {{ __('Please add a model for this brand.') }}<a
                        href="{{ route('sub-brand.index') }}"><b>{{ __('Add Model') }}</b></a>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="unit_id" class="form-label">Unit<span class="text-danger">*</span></label>
                <select name="unit_id" id="unit_id" class="form-control select" required>
                    @foreach ($unit as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

            </div>
            <div class="col-md-6 form-group">
                <label for="pro_image" class="form-label">{{ __('Product image (primary)') }}</label>
                <div class="choose-file ">
                    <label for="pro_image" class="form-label">
                        <input type="file" class="form-control" name="pro_image" id="pro_image"
                            data-filename="pro_image_create" accept="image/*">
                        <img id="image" class="mt-3" style="width:25%;" />

                    </label>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label for="product_images" class="form-label">{{ __('Additional images') }}</label>
                <input type="file" class="form-control" name="product_images[]" id="product_images" accept="image/*"
                    multiple>
                <small class="text-muted d-block mt-1">{{ __('Select multiple files if needed.') }}</small>
            </div>



            <div class="col-md-6">
                <div class="form-group">
                    <div class="btn-box">
                        <label class="d-block form-label">{{ __('Type') }}</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input type" id="customRadio5"
                                        name="type" value="product" checked="checked">
                                    <label class="custom-control-label form-label"
                                        for="customRadio5">{{ __('Product') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input type" id="customRadio6"
                                        name="type" value="service">
                                    <label class="custom-control-label form-label"
                                        for="customRadio6">{{ __('Service') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="2"></textarea>

            </div>

            @if (!$customFields->isEmpty())
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>


<script>
    document.getElementById('pro_image').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }

    //hide & show quantity

    $(document).on('click', '.type', function() {
        var type = $(this).val();
        if (type == 'product') {
            $('.quantity').removeClass('d-none')
            $('.quantity').addClass('d-block');
        } else {
            $('.quantity').addClass('d-none')
            $('.quantity').removeClass('d-block');
        }
    });
</script>
<script>
    var brands = {!! json_encode($brands) !!};
    // var brandsArray = Object.entries(brands).map(([id, name,category_id]) => ({ id, name ,category_id}));

    // Handle change event on the category dropdown
        $('#categorySelect').on('change', function() {
        var selectedCategoryId = $(this).val();
        console.log('Selected Category ID:', selectedCategoryId);
        // Make an AJAX request to fetch brands associated with the selected category
        $.ajax({
            url: '/fetch-brands',
            method: 'GET',
            data: {
                category_id: selectedCategoryId
            },
            success: function(response) {
                var filteredBrands = response.brands;
                console.log('Filtered Brands:', filteredBrands);
                populateBrandDropdown(filteredBrands);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });

    });

    // Function to populate the brand dropdown
    function populateBrandDropdown(filteredBrands) {
        $('#brandSelect').empty();

        filteredBrands.forEach(function(brand) {
            $('#brandSelect').append($('<option>', {
                value: brand.id,
                text: brand.name
            }));
        });

        var subBrands = {!! json_encode($subBrands) !!};
        var filteredSubBrands = subBrands.filter(function(brand) {
            return brand.brand_id == $('#brandSelect').val();
        });
        // Populate the brand dropdown with filtered brands
        populateSubBrandDropdown(filteredSubBrands);
    }
</script>


<script>
    var subBrands = {!! json_encode($subBrands) !!};

    // Handle change event on the brand dropdown
    $('#brandSelect').on('change', function() {
        var selectedBrandId = $(this).val();
        console.log('Selected Brand ID:', selectedBrandId);

        if (!selectedBrandId) {
            // No brand selected, clear sub-brand dropdown
            $('#SubBrandSelect').empty();
            $('#SubBrandSelect').append($('<option>', { value: '', text: @json(__('Select Model')) }));
            return;
        }

        // Fetch sub-brands via AJAX using the new endpoint
        $.ajax({
            url: '/fetch-sub-brands',
            method: 'GET',
            data: {
                brand_id: selectedBrandId
            },
            success: function(response) {
                console.log('Fetched Sub Brands:', response.sub_brands);
                populateSubBrandDropdown(response.sub_brands);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching sub-brands:', error);
                // Fallback to client-side filtering if AJAX fails
                if (Array.isArray(subBrands)) {
                    var filteredSubBrands = subBrands.filter(function(brand) {
                        return brand.brand_id == selectedBrandId;
                    });
                    populateSubBrandDropdown(filteredSubBrands);
                }
            }
        });
    });

    // Function to populate the brand dropdown
    function populateSubBrandDropdown(filteredBrands) {
        $('#SubBrandSelect').empty();

        filteredBrands.forEach(function(brand) {
            $('#SubBrandSelect').append($('<option>', {
                value: brand.id,
                text: brand.name
            }));
        });
    }
</script>

<script>
    $(document).ready(function() {

        fetchBrands();

    });

    // Function to fetch brands
    function fetchBrands() {
        var category_id = $('#categorySelect').val();

        // Make an AJAX request to fetch brands associated with the selected category
        $.ajax({
            url: '/fetch-brands',
            method: 'GET',
            data: {
                category_id: category_id
            },
            success: function(response) {
                var filteredBrands = response.brands;
                populateBrandDropdown(filteredBrands);

                // Optionally, you can also fetch sub-brands associated with the selected brand
                var initialBrandValue = $('#brandSelect').val();
                var filteredSubBrands = filteredBrands.filter(function(brand) {
                    return brand.id == initialBrandValue;
                });
                populateSubBrandDropdown(filteredSubBrands);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }
</script>
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
<script>
    $(document).ready(function() {
        const taxData = {!! json_encode($fullTax ?? []) !!};

        function calculateSalePrice() {
            let basePrice = parseFloat($('#sale_price_base').val()) || 0;
            let selectedIds = $('#choices-multiple1').val() || [];
            let vatTotal = 0;

            // Sum VAT rates for selected tax IDs
            selectedIds.forEach(function(id) {
                let tax = Array.isArray(taxData) ? taxData.find(function(t) {
                    return parseInt(t.id) === parseInt(id);
                }) : null;
                if (tax && tax.rate) {
                    vatTotal += parseFloat(tax.rate) || 0;
                }
            });

            // Calculate the sale price with VAT (assumes base price is excl. VAT)
            if (vatTotal > 0) {
                let vatMultiplier = (vatTotal / 100) + 1;
                let salePrice = basePrice * vatMultiplier;
                $('#sale_price').val(salePrice.toFixed(2));
            } else {
                $('#sale_price').val(basePrice.toFixed(2));
            }
        }

        // Attach the function to input and select changes
        $('#sale_price_base').on('input', calculateSalePrice);
        $('#choices-multiple1').on('change', calculateSalePrice);

        // Initialize on load
        calculateSalePrice();
    });
</script>
