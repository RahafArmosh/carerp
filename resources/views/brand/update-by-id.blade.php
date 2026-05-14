<form action="{{ route('brand.update-by-id') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="brand_id" class="form-label">{{ __('Brand ID') }}</label>
                <input type="number" name="brand_id" id="brand_id" class="form-control" 
                    placeholder="{{ __('Enter Brand ID') }}" required>
                <small class="text-muted">{{ __('Enter the ID of the brand you want to update') }}</small>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Brand Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" 
                    placeholder="{{ __('Enter New Brand Name') }}" required>
            </div>
        </div>

        <div class="form-group col-md-12">
            <label for="category_id" class="form-label">{{ __('Category') }}</label>
            <select name="category_id[]" id="category_id" class="form-control select" multiple>
                @foreach ($category as $id => $cat)
                    <option value="{{ $id }}">{{ $cat }}</option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('Select categories for this brand') }}</small>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>

<script>
    $(document).ready(function() {
        // Auto-fetch brand details when ID is entered
        $('#brand_id').on('blur', function() {
            var brandId = $(this).val();
            if (brandId && brandId > 0) {
                // Show loading indicator
                var $nameField = $('#name');
                var $categoryField = $('#category_id');
                $nameField.prop('disabled', true);
                $categoryField.prop('disabled', true);
                
                $.ajax({
                    url: '{{ route("brand.get-by-id", ":id") }}'.replace(':id', brandId),
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(data) {
                        // Populate form fields
                        $('#name').val(data.name || '');
                        $('#category_id').val(data.category_ids || []).trigger('change');
                        
                        // Re-enable fields
                        $nameField.prop('disabled', false);
                        $categoryField.prop('disabled', false);
                    },
                    error: function(xhr) {
                        // Re-enable fields
                        $nameField.prop('disabled', false);
                        $categoryField.prop('disabled', false);
                        
                        var errorMsg = '{{ __("Brand not found") }}';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        
                        alert(errorMsg + ' (ID: ' + brandId + ')');
                        $('#brand_id').val('');
                        $('#name').val('');
                        $('#category_id').val(null).trigger('change');
                    }
                });
            } else {
                // Clear fields if ID is empty
                $('#name').val('');
                $('#category_id').val(null).trigger('change');
            }
        });
    });
</script>
