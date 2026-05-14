<form action="{{ route('sub-product.store', ['id' => $id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-body">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="product_no" class="form-label">{{ __('Product No') }}</label><span
                        class="text-danger">*</span>
                    <input type="text" id="product_no" name="product_no" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="quantity" class="form-label">{{ __('Quantity') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="quantity" name="quantity" class="form-control" required>
                </div>
            </div>

            {{-- <div class="form-group col-md-6">
                <label for="exterior_color_id" class="form-label">{{ __('Exterior Color') }}</label><span
                    class="text-danger">*</span>
                <select id="exterior_color_id" name="exterior_color_id" class="form-control select" required>
                    <option value="">Select Color</option>
                    @foreach ($colors as $colorId => $colorName)
                        <option value="{{ $colorId }}">{{ $colorName }}</option>
                    @endforeach
                </select>

                <div class=" text-xs">
                    {{ __('Please add color.') }}<a href="{{ route('colors.index') }}"><b>{{ __('Add Color') }}</b></a>
                </div>
            </div> --}}

            {{-- <div class="form-group col-md-6">
                <label for="interior_color_id" class="form-label">{{ __('Interior Color') }}</label><span
                    class="text-danger">*</span>
                <select id="interior_color_id" name="interior_color_id" class="form-control select" required>
                    <option value="">Select Color</option>
                    @foreach ($colors as $colorId => $colorName)
                        <option value="{{ $colorId }}">{{ $colorName }}</option>
                    @endforeach
                </select>

                <div class=" text-xs">
                    {{ __('Please add color.') }}<a
                        href="{{ route('colors.index') }}"><b>{{ __('Add Color') }}</b></a>
                </div>
            </div> --}}
            {{-- <div class="form-group col-md-6">
                <label for="country_id" class="form-label">{{ __('Location') }}</label><span
                    class="text-danger">*</span>
                <select id="country_id" name="country_id" class="form-control select" required>
                    <option value="">Select Location</option>
                    @foreach ($countries as $countryId => $countryName)
                        <option value="{{ $countryId }}">{{ $countryName }}</option>
                    @endforeach
                </select>

                <div class=" text-xs">
                    {{ __('Please add country.') }}<a
                        href="{{ route('countries.index') }}"><b>{{ __('Add Country') }}</b></a>
                </div>
            </div> --}}
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_price" class="form-label">{{ __('Sale Price') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="sale_price" name="sale_price" class="form-control" required
                        step="0.01">

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="purchase_price" name="purchase_price" class="form-control" required
                        step="0.01">

                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="initial_stock" class="form-label">{{ __('Initial Stock') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="initial_stock" name="initial_stock" class="form-control" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="initial_rate" class="form-label">{{ __('Initial Rate') }}</label><span
                        class="text-danger">*</span>
                    <input type="number" id="initial_rate" name="initial_rate" class="form-control" required
                        step="0.01">

                </div>
            </div>

            @if (!$customFields->isEmpty())
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif

            <div class="col-md-12">
                <div class="form-group">
                    <label for="sub_product_images" class="form-label">{{ __('Images') }}</label>
                    <input type="file" class="form-control" name="sub_product_images[]" id="sub_product_images"
                        accept="image/*" multiple>
                    <small class="text-muted d-block mt-1">{{ __('Select one or more images (optional).') }}</small>
                </div>
            </div>

            <input type="hidden" id="product_id" name="product_id" value="{{ $id }}">

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
