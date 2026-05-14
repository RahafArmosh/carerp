<form method="POST" action="{{ url('custom-field') }}">
    @csrf

    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Custom Field Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group col-md-12">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control select" required>
                    @foreach ($types as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="field_type" class="form-label">{{ __('Field Type') }}</label>
                <select name="field_type" id="field_type" class="form-control select" required>
                    <option value="constant" selected>{{ __('Constant') }}</option>
                    <option value="variable">{{ __('Variable') }}</option>
                </select>
                <small
                    class="form-text text-muted">{{ __('Constant: Field value is fixed. Variable: Field value can change per record.') }}</small>
            </div>
            <div class="form-group col-md-12" id="dropdownOptionsDiv" style="display: none;">
                <label for="dropdown_options" class="form-label">{{ __('Dropdown Options') }}</label>
                <div id="tag-container">
                    <div class="tags" id="tags"></div>
                    <textarea id="tag-input" class="form-control" rows="5"
                        placeholder="Paste multiple values from Excel (one per line) and press Enter to add all, or type one value and press Enter"></textarea>
                </div>
                <input type="hidden" name="dropdown_options" id="dropdown_options_hidden">
                <small class="form-text text-muted">{{ __('Paste multiple values from Excel (one per line) and press Enter to add all at once.') }}</small>
            </div>
            <div class="form-group col-md-12">
                <label for="module" class="form-label">{{ __('Module') }}</label>
                <select name="module[]" id="moduleSelect" class="form-control select" multiple required>
                    @foreach ($modules as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">{{ __('You can select multiple modules. The same field will be created once per module.') }}</small>
            </div>

            <div class="form-group col-md-6" id="categoryIdDiv" style="display: none;">
                <label for="category_id" class="form-label">{{ __('Categories') }}<span
                        class="text-danger">*</span></label>
                <select name="category_id[]" id="category_id" class="form-control select2" multiple>
                    @foreach ($category as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <div class="text-xs">
                    {{ __('Please add constant category. ') }}<a
                        href="{{ route('product-category.index') }}"><b>{{ __('Add Category') }}</b></a>
                </div>
            </div>
            <div class="form-group col-md-6" id="show_in_billDiv" style="display: none;">
                <label for="show_in_bill" class="form-label">{{ __('Show In Bill') }}</label>
                <select id="show_in_bill" name="show_in_bill" class="form-control select billtype">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="form-group col-md-6" id="show_in_invoiceDiv" style="display: none;">
                <label for="show_in_invoice" class="form-label">{{ __('Show In Invoice') }}</label>
                <select id="show_in_invoice" name="show_in_invoice" class="form-control select invoictype"
                    >
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
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
    // Tag functionality - declare variables first
    const tagInput = document.getElementById('tag-input');
    const tagsContainer = document.getElementById('tags');
    const hiddenInput = document.getElementById('dropdown_options_hidden');
    let tags = [];

    document.getElementById('moduleSelect').addEventListener('change', function() {
        let selectedModules = Array.from(this.selectedOptions || []).map(o => o.value);
        const productToShowCategory = 'sub-product';

        let categoryDiv = document.getElementById('categoryIdDiv');
        let showInBillDiv = document.getElementById('show_in_billDiv');
        let showInInvoiceDiv = document.getElementById('show_in_invoiceDiv');
        let categorySelect = document.getElementById('category_id');
        let showInBillSelect = document.getElementById('show_in_bill');
        let showInInvoiceSelect = document.getElementById('show_in_invoice');

        if (selectedModules.includes(productToShowCategory)) {
            categoryDiv.style.display = 'block';
            showInBillDiv.style.display = 'block';
            showInInvoiceDiv.style.display = 'block';

            if (categorySelect) {
                categorySelect.required = true;
                categorySelect.disabled = false;
            }
            if (showInBillSelect) {
                showInBillSelect.required = true;
                showInBillSelect.disabled = false;
            }
            if (showInInvoiceSelect) {
                showInInvoiceSelect.required = true;
                showInInvoiceSelect.disabled = false;
            }
        } else {
            categoryDiv.style.display = 'none';
            showInBillDiv.style.display = 'none';
            showInInvoiceDiv.style.display = 'none';

            if (categorySelect) {
                categorySelect.required = false;
                categorySelect.disabled = true;
            }
            if (showInBillSelect) {
                showInBillSelect.required = false;
                showInBillSelect.disabled = true;
            }
            if (showInInvoiceSelect) {
                showInInvoiceSelect.required = false;
                showInInvoiceSelect.disabled = true;
            }
        }
    });

    document.getElementById('type').addEventListener('change', function() {
        let selectedType = this.value;
        let dropdownOptionsDiv = document.getElementById('dropdownOptionsDiv');

        if (selectedType === 'dropdown') {
            dropdownOptionsDiv.style.display = 'block';
            // Reset tags and input when switching to dropdown
            tags = [];
            renderTags();
        } else {
            dropdownOptionsDiv.style.display = 'none';
            // Clear tags when switching away from dropdown
            tags = [];
            renderTags();
        }
    });

    function addTag(tagText) {
        if (tagText.trim() === '') return;
        
        // Check if tag already exists
        if (tags.includes(tagText.trim())) {
            return;
        }
        
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

    // Check initial state on page load
    document.addEventListener('DOMContentLoaded', function() {
        let selectedType = document.getElementById('type').value;
        let dropdownOptionsDiv = document.getElementById('dropdownOptionsDiv');
        
        if (selectedType === 'dropdown') {
            dropdownOptionsDiv.style.display = 'block';
        }
    });
</script>
