<form action="{{ url('users') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control" name="name"
                        placeholder="{{ __('Enter User Name') }}" required>
                    @error('name')
                        <small class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input id="email" type="email" class="form-control" name="email"
                        placeholder="{{ __('Enter User Email') }}" required>
                    @error('email')
                        <small class="invalid-email" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            @php
                $defaultManagerId = old('manager_id', $defaultManagerId ?? null);
            @endphp

            <div class="col-6 form-group">
                <label for="manager_id" class="form-label">{{ __('Manager') }}<span class="text-danger">*</span></label>
                <select name="manager_id" id="manager_id" class="form-control select2">
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}" {{ $id == $defaultManagerId ? 'selected' : '' }}>{{ $user }}</option>
                    @endforeach
                </select>
            </div>
            @php
                $defaultPipelineId = old('default_pipeline', $defaultPipelineId ?? null);
            @endphp

            <div class="col-6 form-group">
                <label for="default_pipeline" class="form-label">{{ __('Pipeline') }}<span class="text-danger">*</span></label>
                <select name="default_pipeline" id="default_pipeline" class="form-control select2">
                    @foreach ($pipeline as $id => $value)
                        <option value="{{ $value->id }}" {{ $value->id == $defaultPipelineId ? 'selected' : '' }}>{{ $value->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (\Auth::user()->type != 'super admin')
                <div class="form-group col-md-6">
                    <label for="role" class="form-label">{{ __('User Role') }}</label>
                    <select id="role" name="role" class="form-control select" required>
                        @foreach ($roles as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <small class="invalid-role" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            @elseif(\Auth::user()->type == 'super admin')
                <input type="hidden" name="role" value="company">
                <div class="form-group col-md-6">
                    <label for="company_categories" class="form-label">{{ __('Company Categories') }}</label>
                    <select id="company_categories" name="company_categories[]" class="form-control select2" multiple>
                        @isset($availableCompanyCategories)
                            @foreach($availableCompanyCategories as $key => $label)
                                <option value="{{ $key }}">{{ __($label) }}</option>
                            @endforeach
                        @endisset
                    </select>
                    <small class="text-muted">{{ __('Choose one or more categories to seed for this company') }}</small>
                </div>
            @endif
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input id="password" type="password" class="form-control" name="password"
                        placeholder="{{ __('Enter User Password') }}" required minlength="6">
                    @error('password')
                        <small class="invalid-password" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
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
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
</form>

<script>
$(document).ready(function() {
    // Initialize select2 when modal is shown (for AJAX-loaded modals)
    $(document).on('shown.bs.modal', '.modal', function() {
        var modal = $(this);
        // Initialize select2 for all select fields within this modal
        modal.find('.select2').each(function() {
            var $select = $(this);
            var options = {
                dropdownParent: modal,
                allowClear: true
            };
            
            // Add placeholder for company_categories
            if ($select.attr('id') === 'company_categories') {
                options.placeholder = '{{ __('Select Categories') }}';
            }
            
            $select.select2(options);
        });
    });
    
    // Also initialize immediately if modal is already visible (for AJAX-loaded content)
    setTimeout(function() {
        var modal = $('.modal.show, .modal.in').last();
        if (modal.length) {
            modal.find('.select2').each(function() {
                var $select = $(this);
                var options = {
                    dropdownParent: modal,
                    allowClear: true
                };
                
                // Add placeholder for company_categories
                if ($select.attr('id') === 'company_categories') {
                    options.placeholder = '{{ __('Select Categories') }}';
                }
                
                $select.select2(options);
            });
        }
    }, 100);
});
</script>
