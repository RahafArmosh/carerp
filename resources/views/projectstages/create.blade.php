<div class="card bg-none card-box">
    <form method="post" action="{{ url('projectstages') }}">
        @csrf
        <div class="row">
            <div class="form-group col-12">
                <label for="name" class="form-label">{{ __('Project Stage Name') }}</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group col-12">
                <label for="color" class="form-label">{{ __('Color') }}</label>
                <input type="color" name="color" class="form-control jscolor" value="FFFFFF" required>
                <small class="small">{{ __('For chart representation') }}</small>
            </div>
            <div class="col-12 text-end">
                <input type="submit" value="{{ __('Create') }}" class="btn-create badge-blue">
                <input type="button" value="{{ __('Cancel') }}" class="btn-create bg-gray" data-dismiss="modal">
            </div>
        </div>
    </form>
</div>
