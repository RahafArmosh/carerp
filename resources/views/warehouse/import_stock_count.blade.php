<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <h5 class="mb-2">{{ __('Excel File Format') }}</h5>
                <p class="mb-1">{{ __('The Excel file should have the following structure:') }}</p>
                <ul class="mb-0">
                    <li>{{ __('First column: Sub Product No') }}</li>
                    <li>{{ __('Subsequent columns: Warehouse names (one column per warehouse)') }}</li>
                    <li>{{ __('Under each warehouse column: Quantity for that product in that warehouse') }}</li>
                </ul>
                <p class="mt-2 mb-0"><strong>{{ __('Example:') }}</strong></p>
                <table class="table table-bordered table-sm mt-2">
                    <thead>
                        <tr>
                            <th>Sub Product No</th>
                            <th>Warehouse 1</th>
                            <th>Warehouse 2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>P001</td>
                            <td>10</td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td>P002</td>
                            <td>20</td>
                            <td>15</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <form id="import-stock-count-form" action="{{ route('warehouse.stock-count.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file" class="form-label">{{ __('Select Excel File') }}</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">{{ __('Supported formats: XLSX, XLS, CSV') }}</small>
                </div>

                <div class="form-group mt-3">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-upload"></i> {{ __('Import Stock Count') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#import-stock-count-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.html();
            
            submitBtn.prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Importing...") }}');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        var message = response.message || '{{ __("Stock count imported successfully") }}';
                        if (response.errors && response.errors.length > 0) {
                            message += '\n\nErrors:\n' + response.errors.slice(0, 10).join('\n');
                            if (response.errors.length > 10) {
                                message += '\n... and ' + (response.errors.length - 10) + ' more errors';
                            }
                        }
                        show_toastr('success', message, 'success');
                        
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 2000);
                        } else {
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    } else {
                        submitBtn.prop('disabled', false).html(originalText);
                        show_toastr('error', response.message || '{{ __("Import failed") }}', 'error');
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalText);
                    var errorMsg = '{{ __("Import failed") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            var errorData = JSON.parse(xhr.responseText);
                            if (errorData.message) {
                                errorMsg = errorData.message;
                            }
                        } catch(e) {
                            // Not JSON, use default message
                        }
                    }
                    show_toastr('error', errorMsg, 'error');
                }
            });
        });
    });
</script>

