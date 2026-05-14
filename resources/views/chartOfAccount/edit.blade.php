<form action="{{ route('chart-of-account-new.update', $chartOfAccount->id) }}" method="post">
    {{ csrf_field() }}
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['chart of account']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" id="name" name="name" class="form-control" required="required"
                    value="{{ old('name', $chartOfAccount->name) }}">
            </div>
            <div class="form-group col-md-6">
                <label for="code" class="form-label">{{ __('Code') }}</label>
                <input type="number" id="code" name="code" class="form-control" required="required"
                    value="{{ old('code', $chartOfAccount->code) }}">
            </div>
            <div class="form-group col-md-6">
                <label for="type" class="form-label">{{ __('Account Type') }}</label>
                <select id="type" name="type" class="form-control select" required="required">
                    @foreach ($types as $key => $value)
                        <option value="{{ $key }}" {{ old('type', $chartOfAccount->type) == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="sub_type" class="form-label">{{ __('Sub Type') }}</label>
                <select class="form-control select" name="sub_type" id="sub_type" required>
                    @foreach ($subTypes as $key => $value)
                        <option value="{{ $key }}" {{ old('sub_type', $chartOfAccount->sub_type) == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="is_enabled" class="form-label">{{ __('Is Enabled') }}</label>
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="is_enabled" id="is_enabled"
                        {{ $chartOfAccount->is_enabled == 1 ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_enabled"></label>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $chartOfAccount->description) }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>

<script>
    $(document).on('change', '#type', function() {
        var type = $(this).val();
        var currentSubType = '{{ old('sub_type', $chartOfAccount->sub_type) }}';
        
        $.ajax({
            url: '{{ route('charofAccount.subType') }}',
            type: 'POST',
            data: {
                "type": type,
                "_token": "{{ csrf_token() }}",
            },
            success: function(data) {
                $('#sub_type').empty();
                $.each(data, function(key, value) {
                    var selected = (key == currentSubType) ? 'selected' : '';
                    $('#sub_type').append('<option value="' + key + '" ' + selected + '>' + value + '</option>');
                });
            }
        });
    });
</script>
