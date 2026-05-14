<form method="POST" action="{{ route('custom-field.update', $customField->id) }}">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">Custom Field Name</label>
                <input type="text" id="name" name="name" class="form-control" required value="{{ $customField->name }}">
            </div>

        </div>
        <div class="form-group">
            <label for="type">{{ __('Type') }}</label>
            <select name="type" id="type" class="form-control">
                @foreach(['text','email','number','date','textarea','dropdown'] as $type)
                    <option value="{{ $type }}" {{ $customField->type === $type ? 'selected' : '' }}>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="field_type" class="form-label">{{ __('Field Type') }}</label>
            <select name="field_type" id="field_type" class="form-control select" required>
                <option value="constant" {{ ($customField->field_type ?? 'constant') === 'constant' ? 'selected' : '' }}>{{ __('Constant') }}</option>
                <option value="variable" {{ ($customField->field_type ?? 'constant') === 'variable' ? 'selected' : '' }}>{{ __('Variable') }}</option>
            </select>
            <small class="form-text text-muted">{{ __('Constant: Field value is fixed. Variable: Field value can change per record.') }}</small>
        </div>
        
        {{-- Show field value depending on type --}}
        <div class="form-group" id="dropdownOptionsDiv" style="{{ $customField->type === 'dropdown' ? '' : 'display:none;' }}">
            <label for="dropdown_options" class="form-label">{{ __('Dropdown Options') }}</label>
            <div id="tag-container">
                <div class="tags" id="tags"></div>
                <textarea id="tag-input" class="form-control" rows="5"
                    placeholder="Paste multiple values from Excel (one per line) and press Enter to add all, or type one value and press Enter"></textarea>
            </div>
            <input type="hidden" name="dropdown_options" id="dropdown_options_hidden" value="@if($customField->type === 'dropdown')@php
                $optionsData = json_decode($customField->options, true);
                if (isset($optionsData['options'])) {
                    echo implode("\n", $optionsData['options']);
                } else {
                    echo implode("\n", is_array($optionsData) ? $optionsData : []);
                }
            @endphp@endif">
            <small class="form-text text-muted">{{ __('Paste multiple values from Excel (one per line) and press Enter to add all at once.') }}</small>
        </div>        
        @if($customField->module === "sub-product")
        <div class="form-group col-md-6" id="categoryIdDiv">
            <label for="category_id" class="form-label">{{ __('Categories') }}<span
                    class="text-danger">*</span></label>
            <select name="category_id[]" id="category_id" class="form-control select2" multiple required>
                @foreach ($category as $id => $name)
                    <option value="{{ $id }}" {{ $customField->categories->contains($id) ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <div class="text-xs">
                {{ __('Please add constant category. ') }}<a
                    href="{{ route('product-category.index') }}"><b>{{ __('Add Category') }}</b></a>
            </div>
        </div>
        <div class="form-group col-md-6" id="show_in_billDiv">
            <label for="show_in_bill" class="form-label">{{ __('Show In Bill') }}</label>
            <select id="show_in_bill" name="show_in_bill" class="form-control select billtype" required="required">
                <option value="0" {{ $customField->show_in_bill == 0 ? 'selected' : '' }}>No</option>
                <option value="1" {{ $customField->show_in_bill == 1 ? 'selected' : '' }}>Yes</option>
            </select>
        </div>
        @endif
        @if($customField->module === "sub-product")
        <div class="form-group col-md-6" id="show_in_invoiceDiv">
            <label for="show_in_invoice" class="form-label">{{ __('Show In Invoice') }}</label>
            <select id="show_in_invoice" name="show_in_invoice" class="form-control select invoictype" required="required">
                <option value="0" {{ $customField->show_in_invoice == 0 ? 'selected' : '' }}>No</option>
                <option value="1" {{ $customField->show_in_invoice == 1 ? 'selected' : '' }}>Yes</option>
            </select>
        </div>
        @endif
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
<style>
    .tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 10px;
    }
    .tag {
        background-color: #007bff;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        font-size: 14px;
    }
    .tag .remove {
        margin-left: 8px;
        cursor: pointer;
        font-weight: bold;
    }
    .tag .remove:hover {
        color: #ffcccc;
    }
</style>
<script>
    // Initialize when DOM is ready
    function initializeTagSystem() {
        const tagInput = document.getElementById('tag-input');
        const tagsContainer = document.getElementById('tags');
        const hiddenInput = document.getElementById('dropdown_options_hidden');
        
        if (!tagInput || !tagsContainer || !hiddenInput) {
            return; // Elements not found, exit early
        }
        
        // Initialize tags from hidden input value
        let tags = [];
        if (hiddenInput.value && hiddenInput.value.trim() !== '') {
            tags = hiddenInput.value.split('\n')
                .map(tag => tag.trim())
                .filter(tag => tag !== '');
        }

        function addTag(tagText) {
            if (tagText.trim() === '' || tags.includes(tagText.trim())) return;
            tags.push(tagText.trim());
            renderTags();
            updateHiddenInput();
        }

        function addMultipleTags(text) {
            // Split by newlines and process each line
            const lines = text.split('\n');
            let addedCount = 0;
            
            lines.forEach(line => {
                const trimmed = line.trim();
                if (trimmed !== '' && !tags.includes(trimmed)) {
                    tags.push(trimmed);
                    addedCount++;
                }
            });
            
            if (addedCount > 0) {
                renderTags();
                updateHiddenInput();
            }
            
            tagInput.value = '';
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            updateHiddenInput();
        }

        function renderTags() {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `${tag} <span class="remove" onclick="removeTag(${index})">&times;</span>`;
                tagsContainer.appendChild(tagElement);
            });
        }

        function updateHiddenInput() {
            hiddenInput.value = tags.join('\n');
        }

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const value = tagInput.value.trim();
                
                if (value === '') return;
                
                // Check if there are multiple lines (newlines in the text)
                if (value.includes('\n')) {
                    // Multiple values - add all at once
                    addMultipleTags(value);
                } else {
                    // Single value - add one
                    addTag(value);
                    tagInput.value = '';
                }
            }
        });

        // Make removeTag global for onclick
        window.removeTag = removeTag;

        // Initial render
        renderTags();
    }

    // Type change handler
    document.getElementById('type').addEventListener('change', function() {
        let selectedType = this.value;
        let dropdownOptionsDiv = document.getElementById('dropdownOptionsDiv');

        if (selectedType === 'dropdown') {
            dropdownOptionsDiv.style.display = 'block';
            // Re-initialize tags when dropdown is shown
            setTimeout(initializeTagSystem, 100);
        } else {
            dropdownOptionsDiv.style.display = 'none';
        }
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTagSystem);
    } else {
        // DOM is already loaded
        initializeTagSystem();
    }
</script>