<form action="{{ route('sub-product.update', $productService->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
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
                    <label for="product_no" class="form-label">{{ __('Product No') }}</label><span
                        class="text-danger">*</span>
                    <input id="product_no" name="product_no" type="text" class="form-control" required="required"
                        value="{{ $productService->product_no }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="quantity" class="form-label">{{ __('Quantity') }}</label><span
                        class="text-danger">*</span>
                    <input id="quantity" name="quantity" type="text" class="form-control" required="required" value="{{ $productService->quantity }}">
                </div>
            </div>

            {{-- <div class="col-md-6">
                <div class="form-group">
                    <label for="exterior_color_id" class="form-label">{{ __('Exterior Color') }}</label><span
                        class="text-danger">*</span>
                    <select id="exterior_color_id" name="exterior_color_id" class="form-control select"
                        required="required">
                        <option value="">Select Color</option>
                        @foreach ($colors as $colorId => $colorName)
                            <option value="{{ $colorId }}" @if ($colorId === $productService->exterior_color_id) selected @endif>{{ $colorName }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}

            {{-- <div class="col-md-6">
                <div class="form-group">
                    <label for="interior_color_id" class="form-label">{{ __('Interior Color') }}</label><span
                        class="text-danger">*</span>
                    <select id="interior_color_id" name="interior_color_id" class="form-control select"
                        required="required">
                        <option value="">Select Color</option>
                        @foreach ($colors as $colorId => $colorName)
                            <option value="{{ $colorId }}" @if ($colorId === $productService->interior_color_id) selected @endif>{{ $colorName }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}

            {{-- <div class="col-md-6">
                <div class="form-group">
                    <label for="country_id" class="form-label">{{ __('Location') }}</label><span
                        class="text-danger">*</span>
                    <select id="country_id" name="country_id" class="form-control select" required="required">
                        <option value="">Select Location</option>
                        @foreach ($countries as $countryId => $countryName)
                            <option value="{{ $countryId }}" @if ($countryId === $productService->country_id) selected @endif>{{ $countryName }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_price" class="form-label">{{ __('Sale Price') }}</label><span
                        class="text-danger">*</span>
                    <input id="sale_price" name="sale_price" type="number" class="form-control" required="required"
                        step="0.01" value="{{ $productService->sale_price }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="purchase_price" class="form-label">{{ __('Purchase Price') }}</label><span
                        class="text-danger">*</span>
                    <input id="purchase_price" name="purchase_price" type="number" class="form-control"
                        required="required" step="0.01" value="{{ $productService->purchase_price }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="flag" class="form-label">{{ __('Purchase Status') }}</label>
                    <select id="flag" name="flag" class="form-control select" required="required">
                        <option value="0" {{ $productService->flag == 0 ? 'selected' : '' }}>Pending</option>
                        <option value="1" {{ $productService->flag == 1 ? 'selected' : '' }}>Purchased</option>
                        <option value="2" {{ $productService->flag == 2 ? 'selected' : '' }}>Cancelled</option>
                        <option value="3" {{ $productService->flag == 3 ? 'selected' : '' }}>Consignment</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="booked" class="form-label">{{ __('Book Status') }}</label>
                    <select id="booked" name="booked" class="form-control select" required="required">
                        <option value="0" {{ $productService->booked == 0 ? 'selected' : '' }}>Free</option>
                        <option value="1" {{ $productService->booked == 1 ? 'selected' : '' }}>Booked</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-control select">
                        <option value="">Select Warehouse</option>
                        @foreach ($warehouses as $warehouseId => $warehouseName)
                            <option value="{{ $warehouseId }}" {{ $productService->warehouse_id == $warehouseId ? 'selected' : '' }}>{{ $warehouseName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="initial_stock" class="form-label">{{ __('Initial Stock') }}</label><span
                    class="text-danger">*</span>
                <input type="number" id="initial_stock" name="initial_stock" class="form-control" value="{{ $productService->initial_stock }}">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="initial_rate" class="form-label">{{ __('Initial Rate') }}</label><span
                    class="text-danger">*</span>
                <input type="number" id="initial_rate" name="initial_rate" class="form-control" value="{{ $productService->initial_rate }}"
                    step="0.01">

            </div>
        </div>

        <div class="col-md-12 form-group">
            <label for="sub_product_images" class="form-label">{{ __('Add images') }}</label>
            <input type="file" class="form-control" name="sub_product_images[]" id="sub_product_images" accept="image/*"
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
                                    value="{{ $img->id }}" id="del_sp_img_{{ $img->id }}">
                                <label class="form-check-label small"
                                    for="del_sp_img_{{ $img->id }}">{{ __('Remove') }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- @if (!$customFields->isEmpty())
            <div class="col-md-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif --}}

        @foreach ($customFields as $customField)
            <div class="form-group">
                <label for="customField-{{ $customField->id }}">{{ $customField->name }}</label>
                <div class="input-group">
                    @php
                        $value = $customFieldValues->get($customField->id, old('customField.' . $customField->id));
                    @endphp

                    @if ($customField->type == 'text')
                        <input type="text" id="customField-{{ $customField->id }}"
                            name="customField[{{ $customField->id }}]" class="form-control"
                            value="{{ $value }}">
                    @elseif($customField->type == 'email')
                        <input type="email" id="customField-{{ $customField->id }}"
                            name="customField[{{ $customField->id }}]" class="form-control"
                            value="{{ $value }}">
                    @elseif($customField->type == 'number')
                        <input type="number" id="customField-{{ $customField->id }}"
                            name="customField[{{ $customField->id }}]" class="form-control"
                            value="{{ $value }}">
                    @elseif($customField->type == 'date')
                        <input type="date" id="customField-{{ $customField->id }}"
                            name="customField[{{ $customField->id }}]" class="form-control"
                            value="{{ $value }}">
                    @elseif($customField->type == 'textarea')
                        <textarea id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]" class="form-control">{{ $value }}</textarea>
                    @elseif($customField->type == 'dropdown')
                        @php
                            $options = json_decode($customField->options, true);
                        @endphp
                        <select id="customField-{{ $customField->id }}" name="customField[{{ $customField->id }}]"
                            class="form-control">
                            @foreach ($options as $option)
                                <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        @endforeach

        <input type="hidden" id="product_id" name="product_id" value="{{ $productService_Id }}">
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
