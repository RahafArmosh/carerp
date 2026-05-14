<form action="{{ route('countries.update', $country->id) }}" method="POST">
    @csrf
    @method('PUT')
<div class="modal-body">

    <div class="row">
        <div class="form-group col-md-12">
            <label for="name" class="form-label">{{ __('Country Name') }}</label>
            <input type="text" id="name" name="name" class="form-control font-style" required value="{{ $country->name }}">
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
