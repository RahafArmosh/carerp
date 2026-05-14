<form action="{{ route('brand.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Brand Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" required>

            </div>
            <div class="form-group col-md-6">
                <label for="category_id[]" class="form-label">{{ __('Category') }}<span
                        class="text-danger">*</span></label>
                <select name="category_id[]" id="category_id[]" class="form-control select" multiple required style="background: none;">
                    @foreach ($category as $id => $cat)
                        <option value="{{ $id }}">{{ $cat }}</option>
                    @endforeach
                </select>

                <div class=" text-xs">
                    {{ __('Please add constant category. ') }}<a
                        href="{{ route('product-category.index') }}"><b>{{ __('Add Category') }}</b></a>
                </div>
            </div>



            @if (!$customFields->isEmpty())
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                        @include('customFields.formBuilder')
                    </div>
                </div>
            @endif






        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
    </div>
</form>
