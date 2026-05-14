<form method="POST" action="{{ route('colors.store') }}">
    @csrf
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            <label for="name" class="form-label">{{ __('Color Name') }}</label>
            <input id="name" type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group col-md-12">
            <label for="code" class="form-label">{{ __('Color Code') }}</label>
            <input id="code" type="text" name="code" class="form-control" required>
        </div>

        @if(!$customFields->isEmpty())
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif






    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>
</form>


