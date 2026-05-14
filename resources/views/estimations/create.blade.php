<div class="card bg-none card-box">
    <form action="estimations" method="post">
        @csrf
        <div class="row">
            <div class="col-6 form-group">
                <label for="client_id" class="form-label">Client</label>
                <select name="client_id" class="form-control select2" required>
                    <option value=""></option>
                    @foreach ($client as $c)
                        <option value="{{ $c->id }}"></option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 form-group">
                <label for="issue_date" class="form-label">Issue Date</label>
                <input type="text" name="issue_date" class="form-control datepicker" required>
            </div>
            <div class="col-6 form-group">
                <label for="tax_id" class="form-label">Tax %</label>
                <select name="tax_id" class="form-control select2" required>
                    <option value=""></option>
                    @foreach ($taxes as $t)
                        <option value="{{ $t->id }}"></option>
                    @endforeach
                </select>
                @if (count($taxes) <= 0)
                    <div class="text-muted text-xs">
                        Please create new Tax <a href="{{ route('taxes.index') }}">here</a>.
                    </div>
                @endif
            </div>
            <div class="col-12 form-group">
                <label for="terms" class="form-label">Terms</label>
                <textarea name="terms" class="form-control"></textarea>
            </div>
            <div class="col-12 text-end">
                <input type="submit" value="Create" class="btn-create badge-blue">
                <input type="button" value="Cancel" class="btn-create bg-gray" data-dismiss="modal">
            </div>
        </div>
    </form>
</div>
