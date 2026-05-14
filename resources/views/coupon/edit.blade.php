<form action="{{ route('coupons.update', $coupon->id) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $settings = \App\Models\Utility::settings();
        @endphp
        @if (!empty($settings['chat_gpt_key']))
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['coupon']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" class="form-control font-style" required
                    value="{{ old('name', $coupon->name) }}" />
            </div>
            <div class="form-group col-md-6">
                <label for="discount" class="form-label">{{ __('Discount') }}</label>
                <input type="number" name="discount" class="form-control" required step="0.01"
                    value="{{ old('discount', $coupon->discount) }}" />
                <span class="small">{{ __('Note: Discount in Percentage') }}</span>
            </div>
            <div class="form-group col-md-6">
                <label for="limit" class="form-label">{{ __('Limit') }}</label>
                <input type="number" name="limit" class="form-control" required
                    value="{{ old('limit', $coupon->limit) }}" />
            </div>
            <div class="form-group col-md-12">
                <label for="code" class="form-label">{{ __('Code') }}</label>
                <input type="text" name="code" class="form-control" required
                    value="{{ old('code', $coupon->code) }}" />
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
