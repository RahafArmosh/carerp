<form action="{{ route('company-policy.update', $companyPolicy->id) }}" method="post" enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="modal-body">
        @php
            $plan = \App\Models\Utility::getChatGPTSettings();
            $policyPath = \App\Models\Utility::get_file('uploads/companyPolicy/');
            $logo = \App\Models\Utility::get_file('uploads/companyPolicy/');
        @endphp
        @if ($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['company policy']) }}" data-bs-placement="top"
                    data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
                </a>
            </div>
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="branch" class="form-label">{{ __('Branch') }}</label>
                    <select name="branch" id="branch" class="form-control select" required>
                        <option value="">{{ __('Select Branch') }}</option>
                        @foreach ($branch as $item)
                            <option value="{{ $item->id }}"
                                {{ old('branch', $companyPolicy->branch_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="title" class="form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" id="title" class="form-control" required
                        value="{{ old('title', $companyPolicy->title) }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" id="description" class="form-control">{{ old('description', $companyPolicy->description) }}</textarea>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="attachment" class="form-label">{{ __('Attachment') }}</label>
                    <div class="choose-file form-group">
                        <label for="attachment" class="form-label">
                            <input type="file" class="form-control" name="attachment" id="attachment"
                                onchange="document.getElementById('image').src = window.URL.createObjectURL(this.files[0])">
                            <img id="image" width="25%" class="mt-3"
                                src="@if ($companyPolicy->attachment) {{ $policyPath . $companyPolicy->attachment }}@else{{ $logo . 'user-2_1654779769.jpg' }} @endif" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn  btn-primary">{{ __('Update') }}</button>
    </div>
</form>


<script>
    document.getElementById('attachment').onchange = function() {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>
