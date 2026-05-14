<form method="POST" action="{{ url('warehouse') }}" enctype="multipart/form-data">
    @csrf
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
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="company_name">{{ __('Company Name') }}</label>
                <input type="text" class="form-control" name="company_name">
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="logo">{{ __('Logo') }}</label>
                <input type="file" class="form-control" name="logo" accept="image/*">
                <small class="text-muted">{{ __('Accepted formats: PNG, JPG, JPEG. Max size: 2MB') }}</small>
            </div>
            <div class="form-group col-md-12">
                <label class="form-label" for="address">{{ __('Address') }}</label>
                <textarea class="form-control" name="address" rows="3"></textarea>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="city">{{ __('City') }}</label>
                <input type="text" class="form-control" name="city">
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="city_zip">{{ __('Zip Code') }}</label>
                <input type="text" class="form-control" name="city_zip">
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="country_id">{{ __('Countries') }}<span
                        class="text-danger">*</span></label>
                <select class="form-control select" name="country_id" required>
                    <option value="" disabled >Select Country</option>
                    @foreach ($countries as $id => $country)
                        <option value="{{ $id }}">{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="tax_id">{{ __('Tax') }}</label>
                <select class="form-control select" name="tax_id">
                    <option value="">{{ __('Select Tax') }}</option>
                    @foreach ($taxes as $id => $tax)
                        <option value="{{ $id }}">{{ $tax }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
