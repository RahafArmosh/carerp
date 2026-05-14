<form method="post" action="{{ route('productservice.spare.parts.stock.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample item master Excel file') }}</label>
            <a href="{{ route('productservice.spare.parts.stock.sample') }}" class="btn btn-sm btn-primary" target="_blank">
                <i class="ti ti-download"></i> {{__('Download Sample')}}
            </a>
            <small class="d-block text-muted mt-1">{{ __('Sample file includes your company\'s custom fields') }}</small>
        </div>
        <div class="col-md-12">
            <div class="alert alert-info">
                <h6>{{ __('Item Master Import Guide') }}</h6>
                <p class="mb-2">{{ __('This import will create/update:') }}</p>
                <ul class="mb-0">
                    <li>{{ __('Categories (GROUPE) - created if not exists') }}</li>
                    <li>{{ __('Brands (BRAND) - created if not exists') }}</li>
                    <li>{{ __('Sub Brands (PARTS TYPE) - created if not exists') }}</li>
                    <li>{{ __('Products (Description) - created if not exists') }}</li>
                    <li>{{ __('Sub Products (Part No) - created or updated') }}</li>
                    <li>{{ __('Custom Fields - mapped from additional columns') }}</li>
                </ul>
                <hr>
                <p class="mb-1"><strong>{{ __('Required Columns:') }}</strong></p>
                <ul class="mb-0">
                    <li><strong>part no</strong> - Sub Product Number</li>
                    <li><strong>description</strong> - Product Name</li>
                    <li><strong>GROUPE</strong> - Category (will be created if not exists)</li>
                    <li><strong>PARTS TYPE</strong> - Sub Brand (will be created if not exists)</li>
                    <li><strong>BRAND</strong> - Brand (will be created if not exists)</li>
                </ul>
                <p class="mb-1 mt-2"><strong>{{ __('Additional Columns:') }}</strong></p>
                <p class="mb-0">{{ __('Any other columns will be mapped to Custom Fields if they match existing custom field names.') }}</p>
                <p class="mb-0 mt-2"><small class="text-info"><i class="ti ti-info-circle"></i> {{ __('The sample file will include all your company\'s custom fields for products. If you don\'t have custom fields yet, create them first in the product settings.') }}</small></p>
                <hr>
                <div class="alert alert-warning mb-0 mt-2">
                    <strong><i class="ti ti-alert-triangle"></i> {{ __('Important:') }}</strong>
                    <ul class="mb-0 mt-1">
                        <li>{{ __('Do NOT use VLOOKUP, HLOOKUP, or any Excel formulas in your import file.') }}</li>
                        <li>{{ __('All values must be actual data, not formulas. Copy and paste as values before importing.') }}</li>
                        <li>{{ __('Formulas will cause import errors and data inconsistencies.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select Excel/CSV File') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".xlsx,.xls,.csv">
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Upload')}}" class="btn  btn-primary">
</div>
</form>

<script>
    // Display selected file name
    document.getElementById('file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || '';
        document.querySelector('.upload_file').textContent = fileName;
    });
</script>

