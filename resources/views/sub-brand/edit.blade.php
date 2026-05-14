<form method="POST" action="{{ route('sub-brand.update', $sub_brand->id) }}">
    @csrf
    @method('PUT')
<div class="modal-body">

    <div class="row">
        <div class="form-group col-md-12">
            <label for="name" class="form-label">{{ __('Model Name') }}</label>
                <input type="text" id="name" name="name" value="{{ $sub_brand->name }}" class="form-control font-style" required>
        </div>

        <div class="form-group col-md-12">
            <label for="brand_id" class="form-label">{{ __('Brand') }}</label><span class="text-danger">*</span>
                <select id="brand_id" name="brand_id" class="form-control select2" required>
                    <option value="">{{ __('Select Brand') }}</option>
                    @foreach($brands as $id => $brand)
                        <option value="{{ $id }}" @if($id == $sub_brand->brand_id) selected @endif>{{ $brand }}</option>
                    @endforeach
                </select>
        </div>

        @if(!$customFields->isEmpty())
            <div class="col-md-12">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
</div>
</form>
