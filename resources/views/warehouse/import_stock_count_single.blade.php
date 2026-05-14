<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <h5 class="mb-2">{{ __('Excel File Format') }}</h5>
                <p class="mb-1">{{ __('The Excel file should have the following structure:') }}</p>
                <ul class="mb-0">
                    <li>{{ __('First column: Sub Product No') }}</li>
                    <li>{{ __('Second column: Quantity') }}</li>
                </ul>
                <p class="mt-2 mb-0"><strong>{{ __('Example:') }}</strong></p>
                <table class="table table-bordered table-sm mt-2">
                    <thead>
                        <tr>
                            <th>Sub Product No</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>P001</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td>P002</td>
                            <td>20</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-2 mb-0 text-warning">
                    <i class="ti ti-alert-triangle"></i> 
                    <strong>{{ __('Note:') }}</strong> {{ __('Products not found in warehouse ":warehouse" will show errors but import will continue.', ['warehouse' => $warehouse->name]) }}
                </p>
            </div>

            <form id="import-stock-count-form" action="{{ route('warehouse.stock-count.import-single.process', $warehouse->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file" class="form-label">{{ __('Select Excel File') }}</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">{{ __('Supported formats: XLSX, XLS, CSV') }}</small>
                </div>

                <div class="form-group mt-3">
                    <div class="d-flex justify-content-end align-items-center">
                        <div id="import-loading-indicator" class="me-auto d-none">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            <span class="ms-1 text-muted">{{ __('Importing stock count, please wait...') }}</span>
                        </div>
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
    (function() {
        // Function to get CSRF token from parent page (more reliable)
        function getCSRFToken() {
            var token = null;
            
            // Try parent window's meta tag first (if in modal)
            if (window.parent && window.parent !== window) {
                try {
                    var parentMeta = window.parent.document.querySelector('meta[name="csrf-token"]');
                    if (parentMeta) {
                        token = parentMeta.getAttribute('content');
                    }
                } catch(e) {
                    console.warn('Could not access parent window CSRF token:', e);
                }
            }
            
            // Try current window's meta tag
            if (!token) {
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    token = meta.getAttribute('content');
                }
            }
            
            // Try jQuery selector
            if (!token) {
                token = $('meta[name="csrf-token"]').attr('content');
            }
            
            // Fallback to form input
            if (!token) {
                token = $('#import-stock-count-form input[name="_token"]').val();
            }
            
            return token;
        }
        
        // Function to initialize the form handler
        function initImportForm() {
            var form = $('#import-stock-count-form');
            if (form.length === 0) {
                return false;
            }
            
            // Remove any existing handlers to prevent duplicates
            form.off('submit.importStockCount');
            
            // Add new handler with namespace
            form.on('submit.importStockCount', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $form = $(this);
                var formData = new FormData(this);
                var submitBtn = $form.find('button[type="submit"]');
                var loadingIndicator = $('#import-loading-indicator');
                var originalText = submitBtn.html();
                var fileInput = $form.find('input[type="file"]');
                
                // Validate file is selected
                if (!fileInput[0].files || fileInput[0].files.length === 0) {
                    show_toastr('error', '{{ __("Please select a file to import") }}', 'error');
                    return false;
                }
                
                // Get CSRF token using improved function
                var csrfToken = getCSRFToken();
                
                // Validate token exists
                if (!csrfToken || csrfToken === '' || csrfToken === null) {
                    console.error('CSRF token not found!', {
                        parentWindow: window.parent !== window,
                        metaExists: $('meta[name="csrf-token"]').length > 0,
                        formTokenExists: $form.find('input[name="_token"]').length > 0
                    });
                    show_toastr('error', '{{ __("CSRF token not found. Please refresh the page and try again.") }}', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }
                
                // Always append token to form data
                formData.append('_token', csrfToken);
                
                console.log('CSRF Token found:', csrfToken ? 'Yes (length: ' + csrfToken.length + ')' : 'No');
                
                submitBtn.prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Importing...") }}');
                if (loadingIndicator.length) {
                    loadingIndicator.removeClass('d-none');
                }
                
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json', // Explicitly expect JSON response
                    processData: false,
                    contentType: false,
                    cache: false,
                    timeout: 600000, // 10 minutes timeout for large imports (7579+ rows)
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        // Upload progress (optional)
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = (evt.loaded / evt.total) * 100;
                                console.log('Upload progress: ' + percentComplete + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        console.log('Import response received:', {
                            response: response,
                            type: typeof response,
                            is_object: typeof response === 'object',
                            has_success: response && 'success' in response,
                            success_value: response && response.success
                        });
                        
                        // Handle both string and object responses
                        if (typeof response === 'string') {
                            try {
                                response = JSON.parse(response);
                            } catch(e) {
                                console.error('Failed to parse string response:', e);
                                submitBtn.prop('disabled', false).html(originalText);
                                if (loadingIndicator.length) {
                                    loadingIndicator.addClass('d-none');
                                }
                                show_toastr('error', '{{ __("Invalid server response format") }}', 'error');
                                return;
                            }
                        }
                        
                        if (response && typeof response === 'object' && response.success !== false) {
                            var message = response.message || '{{ __("Stock count imported successfully") }}';
                            if (response.errors && Array.isArray(response.errors) && response.errors.length > 0) {
                                var errorMsg = response.errors.slice(0, 5).join('\n');
                                if (response.errors.length > 5) {
                                    errorMsg += '\n... and ' + (response.errors.length - 5) + ' {{ __("more errors") }}';
                                }
                                message += '\n\n{{ __("Errors encountered:") }}\n' + errorMsg;
                            }
                            show_toastr('success', message, 'success');
                            
                            // Close modal
                            $('#commonModal').modal('hide');
                            
                            if (response.redirect) {
                                setTimeout(function() {
                                    // Preserve debug flag from current page if present
                                    try {
                                        var currentUrl = new URL(window.location.href);
                                        var debugFlag = currentUrl.searchParams.get('debug_stock_count');
                                        var targetUrl = new URL(response.redirect, window.location.origin);
                                        if (debugFlag === '1') {
                                            targetUrl.searchParams.set('debug_stock_count', '1');
                                        }
                                        window.location.href = targetUrl.toString();
                                    } catch (e) {
                                        window.location.href = response.redirect;
                                    }
                                }, 1500);
                            } else {
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            submitBtn.prop('disabled', false).html(originalText);
                            if (loadingIndicator.length) {
                                loadingIndicator.addClass('d-none');
                            }
                            var errorMsg = (response && response.message) ? response.message : '{{ __("Import failed. Please check the file format.") }}';
                            show_toastr('error', errorMsg, 'error');
                            console.error('Import failed:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Detailed error logging for debugging
                        var errorDetails = {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error,
                            statusCode: xhr.status,
                            readyState: xhr.readyState
                        };
                        console.error('AJAX Error Details:', errorDetails);
                        
                        // Log to server if possible (for debugging)
                        if (typeof console !== 'undefined' && console.error) {
                            console.error('Full XHR object:', xhr);
                        }
                        
                        submitBtn.prop('disabled', false).html(originalText);
                        if (loadingIndicator.length) {
                            loadingIndicator.addClass('d-none');
                        }
                        var errorMsg = '{{ __("Import failed") }}';
                        
                        // Handle different error types
                        if (xhr.status === 0) {
                            errorMsg = '{{ __("Network error. Please check your connection.") }}';
                        } else if (xhr.status === 413) {
                            errorMsg = '{{ __("File too large. Please use a smaller file.") }}';
                        } else if (xhr.status === 419) {
                            errorMsg = '{{ __("Session expired. Please refresh the page and try again.") }}';
                            // Try to refresh CSRF token and show retry option
                            console.error('CSRF token expired (419). Attempting to refresh...');
                            // Reload the modal content to get fresh token
                            setTimeout(function() {
                                if (confirm('{{ __("Your session has expired. Would you like to reload the page?") }}')) {
                                    location.reload();
                                }
                            }, 1000);
                        } else if (xhr.status === 422) {
                            // Validation errors
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                var validationErrors = [];
                                $.each(xhr.responseJSON.errors, function(key, value) {
                                    if (Array.isArray(value)) {
                                        validationErrors = validationErrors.concat(value);
                                    } else {
                                        validationErrors.push(value);
                                    }
                                });
                                errorMsg = '{{ __("Validation errors:") }}\n' + validationErrors.join('\n');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else {
                                errorMsg = '{{ __("Validation error. Please check your file format.") }}';
                            }
                        } else if (xhr.status === 500) {
                            errorMsg = '{{ __("Server error. Please contact support if the problem persists.") }}';
                        } else if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            }
                        } else if (xhr.responseText) {
                            try {
                                var errorData = JSON.parse(xhr.responseText);
                                if (errorData.message) {
                                    errorMsg = errorData.message;
                                } else if (errorData.error) {
                                    errorMsg = errorData.error;
                                }
                            } catch(e) {
                                // Not JSON, might be HTML error page
                                if (xhr.responseText.indexOf('CSRF') !== -1 || xhr.responseText.indexOf('419') !== -1) {
                                    errorMsg = '{{ __("Session expired. Please refresh the page and try again.") }}';
                                }
                            }
                        }
                        
                        show_toastr('error', errorMsg, 'error');
                    },
                    complete: function() {
                        // Always re-enable button on complete
                        submitBtn.prop('disabled', false);
                        if (loadingIndicator.length) {
                            loadingIndicator.addClass('d-none');
                        }
                    }
                });
                
                return false;
            });
            
            return true;
        }
        
        // Initialize when DOM is ready
        $(document).ready(function() {
            // Try to initialize immediately
            initImportForm();
            
            // Also initialize when modal is shown (for dynamically loaded content)
            $(document).on('shown.bs.modal', '#commonModal', function() {
                setTimeout(function() {
                    initImportForm();
                }, 100);
            });
        });
        
        // Also try to initialize after a short delay (fallback)
        setTimeout(function() {
            initImportForm();
        }, 500);
    })();
</script>

