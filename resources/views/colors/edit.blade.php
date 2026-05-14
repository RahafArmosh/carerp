<form method="POST" action="{{ route('colors.update', $color->id) }}">
    @method('PUT')
    @csrf
<div class="modal-body">

    <div class="row">
        <div class="form-group col-md-12">
            <label for="name" class="form-label">{{ __('Color Name') }}</label>
            <input type="text" id="name" name="name" class="form-control font-style" required value="{{ $color->name }}">
        </div>

        <div class="form-group col-md-12">
            <label for="code" class="form-label">{{ __('Color Code') }}</label>
            <input type="text" id="code" name="code" class="form-control font-style" required value="{{ $color->code }}">
        </div>


        @if(!$customFields->isEmpty())
            <div class="col-md-6">
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
