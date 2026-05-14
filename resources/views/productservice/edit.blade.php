<form method="POST" action="{{ route('productservice.update', $productService->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        {{-- @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['productservice']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
        @endif --}}
        {{-- end for ai module --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label><span class="text-danger">*</span>
                    <input type="text" id="name" name="name" class="form-control" required
                        value="{{ $productService->name }}">

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sku" class="form-label">{{ __('SKU') }}</label><span
                        class="text-danger">*</span>
                    <input type="text" id="sku" name="sku" class="form-control" required
                        value="{{ $productService->sku }}">

                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_price_base" class="form-label">{{ __('Sale Price') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="sale_price_base" name="sale_price_base" class="form-control" required
                        step="0.01" value="{{ $productService->sale_price_base }}">

                </div>
            </div>
            {{-- <div class="form-group col-md-6">
                {{ Form::label('sale_chartaccount_id', __('Income Account'),['class'=>'form-label']) }}
                {{ Form::select('sale_chartaccount_id',$incomeChartAccounts,null, array('class' => 'form-control
                select','required'=>'required')) }}
            </div> --}}
            <div class="col-md-6">
                <div class="form-group">
                    <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="purchase_price" name="purchase_price" class="form-control" required
                        step="0.01" value="{{ $productService->purchase_price }}">

                </div>
            </div>
            {{-- <div class="form-group col-md-6">
                {{ Form::label('expense_chartaccount_id', __('Expense Account'),['class'=>'form-label']) }}
                {{ Form::select('expense_chartaccount_id',$expenseChartAccounts,null, array('class' => 'form-control
                select','required'=>'required')) }}
            </div> --}}

            <div class="form-group col-md-6">
                <label for="choices-multiple1" class="form-label">{{ __('Tax') }}</label>
                <select id="choices-multiple1" name="tax_id" class="form-control select2">
                    @foreach ($tax as $id => $name)
                        <option value="{{ $id }}" @if (in_array($id, $productService->tax_id)) selected @endif>
                            {{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_price" class="form-label">{{ __('Sale Price With VAT') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="sale_price" name="sale_price" class="form-control" readonly step="0.01"
                        value="{{ $productService->sale_price }}">

                </div>
            </div>
            <div class="form-group  col-md-6">
                <label for="category_id" class="form-label">Category<span class="text-danger">*</span></label>
                <select name="category_id" id="categorySelect" class="form-control select" required>
                    @foreach ($category as $id => $name)
                        <option value="{{ $id }}" @if ($id === $productService->category_id) selected @endif>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group  col-md-6">
                <label for="brand_id" class="form-label">Brand<span class="text-danger">*</span></label>
                <select name="brand_id" id="brandSelect" class="form-control select">
                    <option value="">Select Brand</option>
                    @foreach ($brands as $id => $brand)
                        <option value="{{ $id }}" @if ($id === $productService->brand_id) selected @endif>
                            {{ $brand }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group  col-md-6">
                <label for="sub_brand_id" class="form-label">{{ __('Model') }}<span class="text-danger">*</span></label>
                <select name="sub_brand_id" id="SubBrandSelect" class="form-control select">
                    <option value="">{{ __('Select Model') }}</option>
                    @foreach ($subBrands as $id => $subBrand)
                        <option value="{{ $id }}" @if ($id === $productService->sub_brand_id) selected @endif>
                            {{ $subBrand }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group  col-md-6">
                <label for="unit_id" class="form-label">Unit<span class="text-danger">*</span></label>
                <select name="unit_id" id="unit_id" class="form-control select" required>
                    <option value="">Select Unit</option>
                    @foreach ($unit as $id => $name)
                        <option value="{{ $id }}" @if ($id === $productService->unit_id) selected @endif>
                            {{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 form-group">
                <label for="pro_image" class="form-label">{{ __('Product image (primary)') }}</label>
                <div class="choose-file ">
                    <label for="pro_image" class="form-label">
                        <input type="file" class="form-control" name="pro_image" id="pro_image"
                            data-filename="pro_image_create" accept="image/*">
                        <img id="image" class="mt-3" width="100"
                            src="@if ($productService->pro_image) {{ URL::to('/') . '/' . 'storage/uploads/pro_image' . '/' . $productService->pro_image }}@else{{ asset(Storage::url('uploads/pro_image/user-2_1654779769.jpg')) }} @endif" />
                    </label>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label for="product_images" class="form-label">{{ __('Add more images') }}</label>
                <input type="file" class="form-control" name="product_images[]" id="product_images" accept="image/*"
                    multiple>
                <small class="text-muted d-block mt-1">{{ __('Select multiple files to append to the gallery.') }}</small>
            </div>

            @if ($productService->images->isNotEmpty())
                <div class="col-md-12 form-group">
                    <label class="form-label">{{ __('Gallery') }}</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($productService->images as $img)
                            <div class="border rounded p-2 text-center" style="width:120px;">
                                <img src="{{ $img->url() }}" alt="" class="img-fluid mb-1" style="max-height:72px;">
                                <div class="form-check justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="delete_image_ids[]"
                                        value="{{ $img->id }}" id="del_img_{{ $img->id }}">
                                    <label class="form-check-label small"
                                        for="del_img_{{ $img->id }}">{{ __('Remove') }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif



            <div class="col-md-6">
                <div class="form-group">
                    <label class="d-block form-label">{{ __('Type') }}</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input type" id="customRadio5" name="type"
                                    value="product" @if ($productService->type == 'product') checked @endif>
                                <label class="custom-control-label form-label"
                                    for="customRadio5">{{ __('Product') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input type" id="customRadio6" name="type"
                                    value="service" @if ($productService->type == 'service') checked @endif>
                                <label class="custom-control-label form-label"
                                    for="customRadio6">{{ __('Service') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group  col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="2">{{ $productService->description }}</textarea>
            </div>


        </div>
        @if (!$customFields->isEmpty())
            <div class="col-md-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
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
    var brands = {!! json_encode($allbrands) !!};
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

        var subBrands = {!! json_encode($allsubBrands) !!};
        var filteredSubBrands = subBrands.filter(function(brand) {
            return brand.brand_id == $('#brandSelect').val();
        });
        // Populate the brand dropdown with filtered brands
        populateSubBrandDropdown(filteredSubBrands);
    }
</script>


<script>
    var subBrands = {!! json_encode($allsubBrands) !!};

    // Handle change event on the category dropdown
    $('#brandSelect').on('change', function() {
        var selectedBrandId = $(this).val();
        console.log('Selected Brand ID:', selectedBrandId);

        if (Array.isArray(subBrands)) {
            // Filter brands based on the selected category
            var filteredSubBrands = subBrands.filter(function(brand) {
                return brand.brand_id == selectedBrandId;
            });

            console.log('Filtered Sub Brands:', filteredSubBrands);
            // Populate the brand dropdown with filtered brands
            populateSubBrandDropdown(filteredSubBrands);
        } else {
            console.error("'Sub brands' is not an array.");
        }
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
