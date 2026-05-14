<form method="POST" action="{{ route('warehouse.update', $warehouse->id) }}" enctype="multipart/form-data">
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
                    data-url="{{ route('generate', ['warehouse']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label class="form-label" for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control" name="name" value="{{ $warehouse->name }}" required>
                @error('name')
                    <small class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="company_name">{{ __('Company Name') }}</label>
                <input type="text" class="form-control" name="company_name" value="{{ $warehouse->company_name }}">
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="logo">{{ __('Logo') }}</label>
                @if($warehouse->logo)
                    <div class="mb-2">
                        <img src="{{ asset('storage/uploads/warehouse_logo/' . $warehouse->logo) }}" alt="Logo" style="max-height: 100px; max-width: 200px;">
                    </div>
                @endif
                <input type="file" class="form-control" name="logo" accept="image/*">
                <small class="text-muted">{{ __('Accepted formats: PNG, JPG, JPEG. Max size: 2MB') }}</small>
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="address">{{ __('Address') }}</label>
                <textarea class="form-control" name="address" rows="3">{{ $warehouse->address }}</textarea>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="city">{{ __('City') }}</label>
                <input type="text" class="form-control" name="city" value="{{ $warehouse->city }}">
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="city_zip">{{ __('Zip Code') }}</label>
                <input type="text" class="form-control" name="city_zip" value="{{ $warehouse->city_zip }}">
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="country_id">{{ __('Countries') }}<span
                        class="text-danger">*</span></label>
                <select class="form-control select" name="country_id" required>
                    @foreach ($countries as $id => $country)
                        <option value="{{ $id }}"  {{ $warehouse->country_id == $id ? 'selected' : '' }} >{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="tax_id">{{ __('Tax') }}</label>
                <select class="form-control select" name="tax_id">
                    @foreach ($taxes as $id => $tax)
                        <option value="{{ $id }}" {{ $warehouse->tax_id == $id ? 'selected' : '' }}>{{ $tax }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Edit') }}" class="btn  btn-primary">
    </div>
</form>
