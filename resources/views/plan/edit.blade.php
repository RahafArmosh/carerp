<form action="{{ route('plans.update', $plan->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $settings = \App\Models\Utility::settings();
        @endphp
        @if (!empty($settings['chat_gpt_key']))
            <div class="text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['plan']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}

        <div class="row">
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control font-style"
                    placeholder="{{ __('Enter Plan Name') }}" value="{{ $plan->name }}" required>
            </div>
            @if ($plan->price > 0)
                <div class="form-group col-md-6">
                    <label for="price" class="form-label">{{ __('Price') }}</label>
                    <input type="number" name="price" id="price" class="form-control"
                        placeholder="{{ __('Enter Plan Price') }}" value="{{ $plan->price }}" required>
                </div>
            @endif
            <div class="form-group col-md-6">
                <label for="duration" class="form-label">{{ __('Duration') }}</label>
                <select name="duration" id="duration" class="form-control select" required>
                    @foreach ($arrDuration as $key => $value)
                        <option value="{{ $key }}" {{ $plan->duration == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="max_users" class="form-label">{{ __('Maximum Users') }}</label>
                <input type="number" name="max_users" id="max_users" class="form-control"
                    value="{{ $plan->max_users }}" required>
                <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
            </div>
            <div class="form-group col-md-6">
                <label for="max_customers" class="form-label">{{ __('Maximum Customers') }}</label>
                <input type="number" name="max_customers" id="max_customers" class="form-control"
                    value="{{ $plan->max_customers }}" required>
                <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
            </div>
            <div class="form-group col-md-6">
                <label for="max_venders" class="form-label">{{ __('Maximum Venders') }}</label>
                <input type="number" name="max_venders" id="max_venders" class="form-control"
                    value="{{ $plan->max_venders }}" required>
                <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
            </div>
            <div class="form-group col-md-6">
                <label for="max_clients" class="form-label">{{ __('Maximum Clients') }}</label>
                <input type="number" name="max_clients" id="max_clients" class="form-control"
                    value="{{ $plan->max_clients }}" required>
                <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
            </div>
            <div class="form-group col-md-6">
                <label for="storage_limit" class="form-label">{{ __('Storage limit') }}</label>
                <div class="input-group">
                    <input type="number" name="storage_limit" id="storage_limit" class="form-control"
                        value="{{ $plan->storage_limit }}" required>
                    <div class="input-group-append">
                        <span class="input-group-text" id="basic-addon2">{{ __('MB') }}</span>
                    </div>
                </div>
            </div>

            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" rows="2">{{ $plan->description }}</textarea>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_crm" id="enable_crm"
                        {{ $plan->crm == 1 ? 'checked' : '' }}>
                    <label for="enable_crm" class="custom-control-label form-label">{{ __('CRM') }}</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_project" id="enable_project"
                        {{ $plan->project == 1 ? 'checked' : '' }}>
                    <label for="enable_project" class="custom-control-label form-label">{{ __('Project') }}</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_hrm" id="enable_hrm"
                        {{ $plan->hrm == 1 ? 'checked' : '' }}>
                    <label for="enable_hrm" class="custom-control-label form-label">{{ __('HRM') }}</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_account" id="enable_account"
                        {{ $plan->account == 1 ? 'checked' : '' }}>
                    <label for="enable_account" class="custom-control-label form-label">{{ __('Account') }}</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_pos" id="enable_pos"
                        {{ $plan->pos == 1 ? 'checked' : '' }}>
                    <label for="enable_pos" class="custom-control-label form-label">{{ __('POS') }}</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="enable_chatgpt" id="enable_chatgpt"
                        {{ $plan->chatgpt == 1 ? 'checked' : '' }}>
                    <label for="enable_chatgpt" class="custom-control-label form-label">{{ __('Chat GPT') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
    </div>
</form>
