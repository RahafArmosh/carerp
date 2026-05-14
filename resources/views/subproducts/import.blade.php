<form method="post" action="{{ route('subproductservice.import') }}" enctype="multipart/form-data">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-3">
            <p class="text-muted small mb-0">
                {{ __('Link rows to a parent product using') }} <strong>product_sku</strong>
                ({{ __('or') }} <strong>parent_sku</strong>) {{ __('or legacy') }} <strong>product_id</strong>.
                {{ __('Required:') }} <strong>product_no</strong>, <strong>initial_rate</strong>, <strong>initial_stock</strong>.
                {{ __('Optional:') }} <strong>sale_price</strong>, <strong>purchase_price</strong>, <strong>warehouse_name</strong> ({{ __('or legacy') }} <strong>warehouse_id</strong>), {{ __('and sub-product custom fields (sample includes your field names as columns).') }}
            </p>
        </div>
        <div class="col-md-12 mb-6">
            <label for="file" class="form-label">{{ __('Download sample sub product Excel file (.xlsx)') }}</label>
            <a href="{{ route('productservice.stock.subproduct.sample') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
        </div>
        <div class="col-md-12">
            <label for="file" class="form-label">{{ __('Select CSV or Excel file') }}</label>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required accept=".csv,.xlsx,.xls">
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
