<form action="{{ route('brand.update', $brand->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">

        <div class="row">
            <div class="form-group col-md-12">
                <label for="name" class="form-label">{{ __('Brand Name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $brand->name }}"
                    required>
            </div>
        </div>

        <div class="form-group  col-md-6">
            <label for="category_id" class="form-label">{{ __('Category') }}</label><span class="text-danger">*</span>
            <select name="category_id[]" id="category_id[]" class="form-control select"  multiple required>
                @foreach ($category as $id => $cat)
                    <option value="{{ $id }}">{{ $cat }}</option>
                @endforeach
            </select>

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
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
    </div>
</form>
