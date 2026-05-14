<form method="post" action="{{ route('account-assets.update', $asset->id) }}" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        {{-- start for ai module --}}
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['account asset']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module --}}
        <div class="row">
            <div class="form-group  col-md-12">
                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                <select name="employee_id[]" id="choices-multiple" class="form-control select2" required>
                    @foreach ($employee as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
                <label for="amount" class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01">
            </div>
            <div class="form-group col-md-6">
                <label for="purchase_date" class="form-label">{{ __('Purchase Date') }}</label>
                <input type="date" name="purchase_date" id="purchase_date" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="supported_date" class="form-label">{{ __('Supported Date') }}</label>
                <input type="date" name="supported_date" id="supported_date" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
